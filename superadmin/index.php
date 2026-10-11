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
use App\Comercial\ModuloOpcional;
use App\Comercial\PdoRepositorioModulos;
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
const SA_SECCIONES = ['resumen', 'instituciones', 'institucion', 'alta', 'planes', 'cobros', 'modulos', 'auditoria'];

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
            } elseif ($accion === 'tipo') {
                $slug = (string) ($_POST['slug'] ?? '');
                $panel->cambiarTipo($slug, (string) ($_POST['tipo'] ?? ''), $actor, $ip);
                sa_aviso('success', "«{$slug}» es ahora " . (string) ($_POST['tipo'] ?? '') . '.');
            } elseif ($accion === 'modulo') {
                $slug = (string) ($_POST['slug'] ?? '');
                $modulo = ModuloOpcional::tryFrom((string) ($_POST['modulo'] ?? '')) ?? throw new InvalidArgumentException('Módulo inválido.');
                $limite = trim((string) ($_POST['limite'] ?? ''));
                if ($limite !== '' && preg_match('/^\d{1,9}$/', $limite) !== 1) {
                    throw new InvalidArgumentException("Límite inválido: «{$limite}» (un número, o vacío = sin límite).");
                }
                $activo = ($_POST['activo'] ?? '') === '1';
                $panel->cambiarModulo($slug, $modulo, $activo, $limite === '' ? null : (int) $limite, $actor, $ip);
                sa_aviso('success', "«{$slug}»: {$modulo->etiqueta()} " . ($activo ? 'activado' : 'desactivado') . '.');
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
    // Cada acción vuelve a su sección (o a la ficha de la institución desde la que se hizo).
    $porDefecto = match ($accion) {
        'plan' => 'planes',
        'cobro_pagado', 'cobro_anulado' => 'cobros',
        'alta' => 'instituciones',
        'entrar' => 'resumen',
        default => 'instituciones',
    };
    $volver = in_array($_POST['volver'] ?? '', SA_SECCIONES, true) ? (string) $_POST['volver'] : $porDefecto;
    $destino = './?s=' . $volver . ($volver === 'institucion' ? '&slug=' . rawurlencode((string) ($_POST['slug'] ?? '')) : '');
    header('Location: ' . $destino, true, 303);
    exit;
}

require __DIR__ . '/vista.php';
sa_mostrar_panel($e, $actor, $maestro, $auditoria);
