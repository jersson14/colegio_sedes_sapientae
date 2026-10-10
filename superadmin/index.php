<?php

declare(strict_types=1);

/**
 * Panel de superadministrador (Fase 4, hito 4.8): instituciones, estados, altas y auditoría.
 *
 * Solo en modo múltiple y solo en SUPERADMIN_HOST (p. ej. panel.miapp.pe): en cualquier otro host,
 * el mismo 404 que una institución inexistente. Cuentas propias (BD maestra, tools/crear_superadmin.php),
 * sesión propia (SGE_SUPERADMIN), token CSRF en todo POST y límite de intentos.
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/../core/limite_login.php';

use App\Comercial\Facturacion;
use App\Comercial\Plan;
use App\Comercial\Recurso;
use App\Core\Conexion;
use App\Superadmin\Auditoria;
use App\Superadmin\CuentasSuperadmin;
use App\Superadmin\PanelInstituciones;
use App\Tenancy\AltaInstitucion;
use App\Tenancy\ConversionDemo;
use App\Tenancy\EstadoTenant;
use App\Tenancy\MigradorPhinx;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;
use App\Tenancy\SolicitudAlta;

const SA_INACTIVIDAD = 1800;
const SA_FALLOS_MAX = 5;

function sa_no_encontrado(): never
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Institución no encontrada.');
}

$hostPanel = ResolverTenant::normalizarHost((string) config('SUPERADMIN_HOST', ''));
if (ResolverTenant::desdeConfig()->modo() !== ModoTenant::Multiple || $hostPanel === null
    || ResolverTenant::normalizarHost((string) ($_SERVER['HTTP_HOST'] ?? '')) !== $hostPanel) {
    sa_no_encontrado();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('SGE_SUPERADMIN');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

$maestro = Conexion::maestro();
$cuentas = new CuentasSuperadmin($maestro);
$auditoria = new Auditoria($maestro);
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$e = static fn (?string $t): string => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');

// Sesión vencida o cuenta desactivada desde que se abrió.
if (isset($_SESSION['sa_usuario'])) {
    $vencida = time() - (int) ($_SESSION['sa_actividad'] ?? 0) > SA_INACTIVIDAD;
    if ($vencida || !$cuentas->activa((string) $_SESSION['sa_usuario'])) {
        $_SESSION = ['csrf' => bin2hex(random_bytes(32))];
        session_regenerate_id(true);
    } else {
        $_SESSION['sa_actividad'] = time();
    }
}
$actor = isset($_SESSION['sa_usuario']) ? (string) $_SESSION['sa_usuario'] : null;

/** Mensaje para la siguiente página (patrón POST → redirección → GET). */
function sa_aviso(string $tipo, string $texto): void
{
    $_SESSION['aviso'] = [$tipo, $texto];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string) $_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Token CSRF inválido. Recarga la página.');
    }
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'entrar') {
        $usuario = strtolower(trim((string) ($_POST['usuario'] ?? '')));
        $clave = 'sa|' . $usuario . '|' . limite_ip();
        $estado = limite_actualizar($clave, static fn (array $s): array => $s);
        if ($estado['bloqueado_hasta'] > time()) {
            http_response_code(429);
            sa_aviso('danger', 'Demasiados intentos. Espera unos minutos.');
        } elseif (($cuenta = $cuentas->autenticar($usuario, (string) ($_POST['clave'] ?? ''))) !== null) {
            limite_actualizar($clave, static fn (array $s): array => ['fallos' => [], 'bloqueado_hasta' => 0, 'reincidencias' => 0]);
            session_regenerate_id(true);
            $_SESSION = ['csrf' => bin2hex(random_bytes(32)), 'sa_usuario' => $cuenta['usuario'], 'sa_nombre' => $cuenta['nombre'], 'sa_actividad' => time()];
            $auditoria->registrar($cuenta['usuario'], 'ENTRADA', null, '', $ip);
        } else {
            limite_actualizar($clave, static function (array $s, int $ahora): array {
                $s['fallos'][] = $ahora;
                if (count($s['fallos']) >= SA_FALLOS_MAX) {
                    $s['bloqueado_hasta'] = $ahora + LIMITE_BLOQUEO_BASE;
                    $s['fallos'] = [];
                }
                return $s;
            });
            $auditoria->registrar(mb_substr($usuario, 0, 60), 'ENTRADA_FALLIDA', null, '', $ip);
            sa_aviso('danger', 'Usuario o contraseña incorrectos.');
        }
    } elseif ($actor === null) {
        http_response_code(401);
        exit('Sesión vencida. Vuelve a entrar.');
    } elseif ($accion === 'salir') {
        $auditoria->registrar($actor, 'SALIDA', null, '', $ip);
        $_SESSION = [];
        session_destroy();
        header('Location: ./', true, 303);
        exit;
    } else {
        $alta = new AltaInstitucion(
            Conexion::administracion(),
            $maestro,
            static fn (string $base): PDO => Conexion::administracion($base),
            static fn (string $base): bool => MigradorPhinx::ejecutar('migrate', $base),
        );
        $panel = new PanelInstituciones(
            $maestro,
            static fn (string $base): PDO => Conexion::administracion($base),
            $auditoria,
            $alta,
            (int) config('RETENCION_DIAS_BAJA', '90')
        );
        try {
            if ($accion === 'estado') {
                $estado = EstadoTenant::tryFrom((string) ($_POST['estado'] ?? '')) ?? throw new InvalidArgumentException('Estado inválido.');
                $slug = (string) ($_POST['slug'] ?? '');
                $panel->cambiarEstado($slug, $estado, $actor, $ip);
                sa_aviso('success', "«{$slug}» pasa a {$estado->value}.");
            } elseif ($accion === 'plan') {
                $campo = static fn (string $c): ?string => trim((string) ($_POST[$c] ?? '')) !== '' ? trim((string) $_POST[$c]) : null;
                $entero = static function (?string $v): ?int {
                    if ($v !== null && preg_match('/^\d{1,9}$/', $v) !== 1) {
                        throw new InvalidArgumentException("Límite inválido: «{$v}» (un número, o vacío = sin límite).");
                    }
                    return $v === null ? null : (int) $v;
                };
                $plan = new Plan(
                    strtoupper((string) $campo('codigo')),
                    (string) $campo('nombre'),
                    [
                        Recurso::Alumnos->value => $entero($campo('max_alumnos')),
                        Recurso::Usuarios->value => $entero($campo('max_usuarios')),
                        Recurso::AlmacenamientoMb->value => $entero($campo('max_almacenamiento_mb')),
                    ],
                    $campo('precio_mensual'),
                    $campo('precio_por_alumno'),
                    strtoupper($campo('moneda') ?? 'PEN'),
                );
                $panel->guardarPlan($plan, $actor, $ip);
                sa_aviso('success', "Plan {$plan->codigo} guardado.");
            } elseif ($accion === 'cobro_pagado' || $accion === 'cobro_anulado') {
                $facturacion = new Facturacion($maestro, (int) config('FACTURA_DIAS_PAGO', '10'), (int) config('FACTURA_DIAS_GRACIA', '5'));
                $numero = (string) ($_POST['numero'] ?? '');
                $hoy = new DateTimeImmutable('today');
                $resultado = $accion === 'cobro_pagado'
                    ? $facturacion->registrarPago($numero, trim((string) ($_POST['medio'] ?? '')), trim((string) ($_POST['referencia'] ?? '')), $hoy)
                    : $facturacion->anular($numero, $hoy);
                $auditoria->registrar(
                    $actor,
                    $accion === 'cobro_pagado' ? 'COBRO_PAGADO' : 'COBRO_ANULADO',
                    $resultado['slug'],
                    $numero . ($resultado['reactivado'] ? '; MOROSO → ACTIVO' : ''),
                    $ip
                );
                sa_aviso('success', "Cobro $numero " . ($accion === 'cobro_pagado' ? 'pagado' : 'anulado') . '.'
                    . ($resultado['reactivado'] ? " «{$resultado['slug']}» vuelve a ACTIVO." : ''));
            } elseif ($accion === 'convertir_demo') {
                $campo = static fn (string $c): string => trim((string) ($_POST[$c] ?? ''));
                $datos = new SolicitudAlta(
                    $campo('slug'),
                    $campo('razon'),
                    $campo('email'),
                    $campo('admin_dni'),
                    $campo('admin_nombres'),
                    $campo('admin_apellidos'),
                    'admin',
                    'COLEGIO',
                    EstadoTenant::tryFrom($campo('estado')) ?? EstadoTenant::Activo,
                    null,
                    ConversionDemo::baseCliente($campo('slug')),
                );
                $almacenes = rtrim(config('ALMACEN_DIR') ?: dirname(__DIR__) . '/storage/tenants', '/\\');
                $resultado = (new ConversionDemo(Conexion::administracion(), $maestro, $alta, $almacenes))->convertir($datos);
                $auditoria->registrar($actor, 'DEMO_CONVERTIDA', $datos->slug, "base {$resultado['base']}", $ip);
                sa_aviso('success', "«{$datos->slug}» es ya cliente, con una base limpia. Administrador: admin · contraseña inicial: "
                    . "{$resultado['clave']} (se muestra solo ahora).");
            } elseif ($accion === 'asignar_plan') {
                $slug = (string) ($_POST['slug'] ?? '');
                $hasta = trim((string) ($_POST['prueba_hasta'] ?? ''));
                $panel->asignarPlan($slug, (string) ($_POST['plan'] ?? ''), $hasta !== '' ? $hasta : null, $actor, $ip);
                sa_aviso('success', "«{$slug}» tiene el plan " . (string) ($_POST['plan'] ?? '') . '.');
            } elseif ($accion === 'alta') {
                $campo = static fn (string $c): string => trim((string) ($_POST[$c] ?? ''));
                $solicitud = new SolicitudAlta(
                    $campo('slug'),
                    $campo('razon'),
                    $campo('email'),
                    $campo('admin_dni'),
                    $campo('admin_nombres'),
                    $campo('admin_apellidos'),
                    $campo('admin_usuario') ?: 'admin',
                    $campo('tipo') ?: 'COLEGIO',
                    EstadoTenant::tryFrom($campo('estado')) ?? EstadoTenant::Prueba,
                    null,
                    null,
                    $campo('demo') === '1',
                );
                $claveInicial = $panel->darDeAlta($solicitud, $actor, $ip);
                sa_aviso('success', "Institución «{$solicitud->slug}» creada. Administrador: {$solicitud->adminUsuario} · contraseña inicial: "
                    . "$claveInicial (se muestra solo ahora; entrégala por un canal seguro).");
            }
        } catch (InvalidArgumentException | DomainException $ex) {
            sa_aviso('warning', $ex->getMessage());
        } catch (Throwable $ex) {
            error_log('Panel superadministrador: ' . $ex->getMessage());
            sa_aviso('danger', 'No se completó la operación: ' . $ex->getMessage());
        }
    }
    header('Location: ./', true, 303);
    exit;
}

$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);
$csrf = $e((string) $_SESSION['csrf']);
$panel = $actor !== null ? new PanelInstituciones($maestro, static fn (string $base): PDO => Conexion::administracion($base), $auditoria) : null;
$planes = $panel?->planes() ?? [];
$cobros = $panel !== null ? (new Facturacion($maestro))->recientes() : [];
$dominio = (string) config('TENANT_DOMINIO', '');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Panel de superadministrador</title>
  <link rel="stylesheet" href="../plantilla/dist/css/adminlte.min.css">
  <style>body{background:#f4f6f9} .contenedor{max-width:1200px;margin:2rem auto;padding:0 1rem} .badge-PRUEBA{background:#17a2b8} .badge-ACTIVO{background:#28a745} .badge-MOROSO{background:#ffc107;color:#000} .badge-SUSPENDIDO{background:#dc3545} .badge-CANCELADO{background:#6c757d} .badge{color:#fff}</style>
</head>
<body>
<div class="contenedor">
  <h1 class="h3 mb-3">Panel de superadministrador</h1>
  <?php if ($aviso !== null) { ?>
    <div class="alert alert-<?= $e($aviso[0]) ?>" role="alert" id="aviso"><?= $e($aviso[1]) ?></div>
  <?php } ?>

  <?php if ($panel === null) { ?>
    <form method="post" class="card card-body" style="max-width:380px" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="accion" value="entrar">
      <label for="usuario">Usuario</label>
      <input class="form-control mb-2" id="usuario" name="usuario" required>
      <label for="clave">Contraseña</label>
      <input class="form-control mb-3" id="clave" name="clave" type="password" required>
      <button class="btn btn-primary" type="submit">Entrar</button>
    </form>
  <?php } else { ?>
    <form method="post" class="mb-3 text-right">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="accion" value="salir">
      <span class="mr-2"><?= $e((string) $_SESSION['sa_nombre']) ?></span>
      <button class="btn btn-sm btn-outline-secondary" type="submit">Salir</button>
    </form>

    <div class="card"><div class="card-header"><b>Instituciones</b></div>
      <div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped mb-0" id="tabla_instituciones">
          <thead><tr><th>Slug</th><th>Institución</th><th>Tipo</th><th>Estado</th><th>Alumnos</th><th>Usuarios</th><th>Migración</th><th>Alta</th><th>Plan</th><th>Cambiar estado</th></tr></thead>
          <tbody>
          <?php foreach ($panel->listar() as $t) { ?>
            <tr data-slug="<?= $e($t['slug']) ?>">
              <td><a href="https://<?= $e($t['dominio'] ?? "{$t['slug']}.$dominio") ?>/" rel="noopener" target="_blank"><?= $e($t['slug']) ?></a></td>
              <td><?= $e($t['razon_social']) ?><br><small class="text-muted"><?= $e($t['base_datos']) ?></small></td>
              <td><?= $e($t['tipo']) ?></td>
              <td><span class="badge badge-<?= $e($t['estado']) ?>"><?= $e($t['estado']) ?></span>
                <?php if ($t['demo']) { ?><span class="badge badge-warning" style="color:#000">DEMO</span>
                  <details class="mt-1"><summary><small>Convertir en cliente</small></summary>
                    <form method="post" autocomplete="off" class="mt-1" style="min-width:220px">
                      <input type="hidden" name="csrf" value="<?= $csrf ?>">
                      <input type="hidden" name="accion" value="convertir_demo">
                      <input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
                      <input class="form-control form-control-sm mb-1" name="razon" value="<?= $e($t['razon_social']) ?>" required aria-label="Razón social">
                      <input class="form-control form-control-sm mb-1" name="email" type="email" placeholder="Correo" required>
                      <input class="form-control form-control-sm mb-1" name="admin_dni" placeholder="DNI del administrador" required pattern="\d{8}">
                      <input class="form-control form-control-sm mb-1" name="admin_nombres" placeholder="Nombres" required>
                      <input class="form-control form-control-sm mb-1" name="admin_apellidos" placeholder="Apellidos" required>
                      <select class="form-control form-control-sm mb-1" name="estado" aria-label="Estado"><option>ACTIVO</option><option>PRUEBA</option></select>
                      <button class="btn btn-sm btn-warning" type="submit">Convertir (borra los datos de ejemplo)</button>
                    </form>
                  </details>
                <?php } ?>
              </td>
              <?php if ($t['error'] !== null) { ?>
                <td colspan="3" class="text-danger"><?= $e($t['error']) ?></td>
              <?php } else { ?>
                <td><?= (int) $t['alumnos'] ?><?= $t['max_alumnos'] !== null ? ' / ' . (int) $t['max_alumnos'] : '' ?></td>
                <td><?= (int) $t['usuarios'] ?><?= $t['max_usuarios'] !== null ? ' / ' . (int) $t['max_usuarios'] : '' ?></td>
                <td><small><?= $e($t['migracion']) ?></small></td>
              <?php } ?>
              <td><small><?= $e($t['fecha_alta']) ?></small></td>
              <td>
                <form method="post" class="form-inline">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="accion" value="asignar_plan">
                  <input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
                  <select name="plan" class="form-control form-control-sm mr-1" aria-label="Plan de <?= $e($t['slug']) ?>">
                    <?php if ($t['plan'] === null) { ?><option value="">(sin plan)</option><?php } ?>
                    <?php foreach ($planes as $p) { ?>
                      <option value="<?= $e($p->codigo) ?>"<?= $p->codigo === $t['plan'] ? ' selected' : '' ?>><?= $e($p->codigo) ?></option>
                    <?php } ?>
                  </select>
                  <input type="date" name="prueba_hasta" class="form-control form-control-sm mr-1" value="<?= $e($t['prueba_hasta']) ?>" title="Fin de la prueba (vacío si no está en prueba)">
                  <button class="btn btn-sm btn-outline-primary" type="submit">Asignar</button>
                </form>
              </td>
              <td>
                <form method="post" class="form-inline">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
                  <select name="estado" class="form-control form-control-sm mr-1" aria-label="Estado de <?= $e($t['slug']) ?>">
                    <?php foreach (EstadoTenant::cases() as $estado) { ?>
                      <option value="<?= $e($estado->value) ?>"<?= $estado->value === $t['estado'] ? ' selected' : '' ?>><?= $e($estado->value) ?></option>
                    <?php } ?>
                  </select>
                  <button class="btn btn-sm btn-primary" type="submit">Aplicar</button>
                </form>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer"><small>PRUEBA, ACTIVO y MOROSO entran (MOROSO sin altas ni matrículas); SUSPENDIDO: solo su
        administrador, para descargar sus datos, durante la ventana de exportación; CANCELADO responde 404. Nada se borra.</small></div>
    </div>

    <div class="card"><div class="card-header"><b>Planes</b> <small class="text-muted">— límites vacíos = sin límite; precios vacíos = no se cobra por ese concepto</small></div>
      <div class="card-body table-responsive p-0">
        <table class="table table-sm mb-0" id="tabla_planes">
          <thead><tr><th>Código</th><th>Nombre</th><th>Alumnos</th><th>Usuarios</th><th>MB</th><th>Mensual</th><th>Por alumno</th><th>Moneda</th></tr></thead>
          <tbody>
          <?php foreach ($planes as $p) { ?>
            <tr data-plan="<?= $e($p->codigo) ?>"><td><?= $e($p->codigo) ?></td><td><?= $e($p->nombre) ?></td>
              <td><?= $e((string) ($p->limite(Recurso::Alumnos) ?? '—')) ?></td><td><?= $e((string) ($p->limite(Recurso::Usuarios) ?? '—')) ?></td>
              <td><?= $e((string) ($p->limite(Recurso::AlmacenamientoMb) ?? '—')) ?></td><td><?= $e($p->precioMensual ?? '—') ?></td>
              <td><?= $e($p->precioPorAlumno ?? '—') ?></td><td><?= $e($p->moneda) ?></td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
      <form method="post" class="card-body border-top" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="accion" value="plan">
        <div class="form-row">
          <div class="col-md-2 mb-2"><label for="plan_codigo">Código</label><input class="form-control" id="plan_codigo" name="codigo" required pattern="[A-Za-z0-9_]{2,30}" placeholder="BASICO"></div>
          <div class="col-md-3 mb-2"><label for="plan_nombre">Nombre</label><input class="form-control" id="plan_nombre" name="nombre" required maxlength="100"></div>
          <div class="col-md-1 mb-2"><label for="plan_alumnos">Alumnos</label><input class="form-control" id="plan_alumnos" name="max_alumnos" inputmode="numeric"></div>
          <div class="col-md-1 mb-2"><label for="plan_usuarios">Usuarios</label><input class="form-control" id="plan_usuarios" name="max_usuarios" inputmode="numeric"></div>
          <div class="col-md-1 mb-2"><label for="plan_mb">MB</label><input class="form-control" id="plan_mb" name="max_almacenamiento_mb" inputmode="numeric"></div>
          <div class="col-md-1 mb-2"><label for="plan_mensual">Mensual</label><input class="form-control" id="plan_mensual" name="precio_mensual" inputmode="decimal"></div>
          <div class="col-md-1 mb-2"><label for="plan_por_alumno">Por alumno</label><input class="form-control" id="plan_por_alumno" name="precio_por_alumno" inputmode="decimal"></div>
          <div class="col-md-1 mb-2"><label for="plan_moneda">Moneda</label><input class="form-control" id="plan_moneda" name="moneda" value="PEN" maxlength="3"></div>
        </div>
        <button class="btn btn-success" type="submit">Guardar plan</button> <small class="text-muted">Con un código existente, lo modifica.</small>
      </form>
    </div>

    <div class="card"><div class="card-header"><b>Cobros</b> <small class="text-muted">— los emite cada día tools/facturacion.php; son
      cobros internos, no comprobantes SUNAT (esos se emiten con un OSE/PSE)</small></div>
      <div class="card-body table-responsive p-0">
        <table class="table table-sm mb-0" id="tabla_cobros">
          <thead><tr><th>Número</th><th>Institución</th><th>Periodo</th><th>Vence</th><th>Monto</th><th>Detalle</th><th>Estado</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($cobros as $c) { ?>
            <tr data-cobro="<?= $e($c['numero']) ?>">
              <td><?= $e($c['numero']) ?></td><td><?= $e($c['slug']) ?></td>
              <td><small><?= $e($c['periodo_inicio']) ?> – <?= $e($c['periodo_fin']) ?></small></td><td><?= $e($c['vencimiento']) ?></td>
              <td><?= $e($c['moneda']) ?> <?= $e($c['monto']) ?></td><td><small><?= $e($c['detalle']) ?></small></td>
              <td><?= $e($c['estado']) ?><?= $c['estado'] === 'PAGADA' ? '<br><small>' . $e($c['pagada_en']) . ' · ' . $e($c['medio_pago']) . ' ' . $e($c['referencia_pago']) . '</small>' : '' ?></td>
              <td>
                <?php if ($c['estado'] === 'PENDIENTE') { ?>
                  <form method="post" class="form-inline">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="numero" value="<?= $e($c['numero']) ?>">
                    <input name="medio" class="form-control form-control-sm mr-1" placeholder="Medio (transferencia…)" maxlength="40" style="width:150px">
                    <input name="referencia" class="form-control form-control-sm mr-1" placeholder="N.º de operación" maxlength="100" style="width:130px">
                    <button class="btn btn-sm btn-success mr-1" type="submit" name="accion" value="cobro_pagado">Pagado</button>
                    <button class="btn btn-sm btn-outline-danger" type="submit" name="accion" value="cobro_anulado">Anular</button>
                  </form>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card"><div class="card-header"><b>Dar de alta una institución</b> <small class="text-muted">— crea la base, la migra y crea su administrador (unos segundos)</small></div>
      <form method="post" class="card-body" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="accion" value="alta">
        <div class="form-row">
          <div class="col-md-3 mb-2"><label for="slug">Subdominio</label><input class="form-control" id="slug" name="slug" required pattern="[a-z0-9]([a-z0-9-]*[a-z0-9])?" placeholder="colegio-x"></div>
          <div class="col-md-5 mb-2"><label for="razon">Razón social</label><input class="form-control" id="razon" name="razon" required maxlength="200"></div>
          <div class="col-md-4 mb-2"><label for="email">Correo</label><input class="form-control" id="email" name="email" type="email" required></div>
          <div class="col-md-2 mb-2"><label for="admin_dni">DNI administrador</label><input class="form-control" id="admin_dni" name="admin_dni" required pattern="\d{8}"></div>
          <div class="col-md-3 mb-2"><label for="admin_nombres">Nombres</label><input class="form-control" id="admin_nombres" name="admin_nombres" required></div>
          <div class="col-md-3 mb-2"><label for="admin_apellidos">Apellidos</label><input class="form-control" id="admin_apellidos" name="admin_apellidos" required></div>
          <div class="col-md-2 mb-2"><label for="tipo">Tipo</label><select class="form-control" id="tipo" name="tipo"><option>COLEGIO</option><option>INSTITUTO</option><option>CETPRO</option></select></div>
          <div class="col-md-2 mb-2"><label for="estado">Estado</label><select class="form-control" id="estado" name="estado"><option>PRUEBA</option><option>ACTIVO</option></select></div>
          <div class="col-md-12 mb-2"><div class="form-check"><input class="form-check-input" type="checkbox" id="demo" name="demo" value="1">
            <label class="form-check-label" for="demo">Demo: con datos de ejemplo (ficticios) para que el colegio pruebe el sistema; luego se convierte en cliente</label></div></div>
        </div>
        <button class="btn btn-success" type="submit">Crear institución</button>
      </form>
    </div>

    <div class="card"><div class="card-header"><b>Auditoría</b> <small class="text-muted">— últimas 50 acciones</small></div>
      <div class="card-body table-responsive p-0">
        <table class="table table-sm mb-0" id="tabla_auditoria">
          <thead><tr><th>Fecha</th><th>Quién</th><th>Acción</th><th>Institución</th><th>Detalle</th><th>IP</th></tr></thead>
          <tbody>
          <?php foreach ($auditoria->recientes() as $a) { ?>
            <tr><td><small><?= $e($a['fecha']) ?></small></td><td><?= $e($a['actor']) ?></td><td><?= $e($a['accion']) ?></td><td><?= $e($a['tenant']) ?></td><td><small><?= $e($a['detalle']) ?></small></td><td><small><?= $e($a['ip']) ?></small></td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php } ?>
</div>
</body>
</html>
