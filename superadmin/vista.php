<?php

declare(strict_types=1);

/**
 * Vista del panel de superadministrador: tablero con menú lateral y secciones (?s=resumen|instituciones|
 * institucion&slug=…|alta|planes|cobros|modulos|auditoria). La incluye superadmin/index.php después de
 * comprobar el host, la sesión y procesar los POST; pedida directamente responde 404.
 */

if (!defined('SA_SECCIONES')) {
    http_response_code(404);
    exit;
}

use App\Comercial\Facturacion;
use App\Comercial\ModuloOpcional;
use App\Comercial\PdoRepositorioModulos;
use App\Comercial\Recurso;
use App\Core\Conexion;
use App\Superadmin\Indicadores;
use App\Superadmin\PanelInstituciones;
use App\Superadmin\Auditoria;
use App\Tenancy\EstadoTenant;

/** @param \Closure(?string): string $e escape HTML */
function sa_mostrar_panel(\Closure $e, ?string $actor, PDO $maestro, Auditoria $auditoria): void
{
    $aviso = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    $csrf = $e((string) $_SESSION['csrf']);
    $seccion = in_array($_GET['s'] ?? '', SA_SECCIONES, true) ? (string) $_GET['s'] : 'resumen';
    $panel = $actor !== null ? new PanelInstituciones($maestro, static fn (string $base): PDO => Conexion::administracion($base), $auditoria) : null;
    $dominio = (string) config('TENANT_DOMINIO', '');
    $hoy = new DateTimeImmutable('today');
    $titulos = [
        'resumen' => ['Resumen', 'fa-tachometer-alt'], 'instituciones' => ['Instituciones', 'fa-university'], 'institucion' => ['Institución', 'fa-university'],
        'alta' => ['Dar de alta', 'fa-plus-circle'], 'planes' => ['Planes', 'fa-layer-group'], 'cobros' => ['Cobros', 'fa-file-invoice-dollar'],
        'modulos' => ['Módulos e IA', 'fa-puzzle-piece'], 'auditoria' => ['Auditoría', 'fa-history'],
    ];
    $colorEstado = ['PRUEBA' => 'info', 'ACTIVO' => 'success', 'MOROSO' => 'warning', 'SUSPENDIDO' => 'danger', 'CANCELADO' => 'secondary'];
    $colorTipo = ['COLEGIO' => 'primary', 'INSTITUTO' => 'purple', 'CETPRO' => 'teal'];
    $insignia = static fn (string $texto, string $color): string => '<span class="badge badge-' . $color . '">' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</span>';
    $dinero = static fn (float $n): string => 'S/ ' . number_format($n, 2);

    // Datos de cada sección (la lista de instituciones abre la base de cada una: solo donde se usa).
    $lista = [];
    $modulos = [];
    $consumoIa = [];
    $ultimaMigracion = Indicadores::ultimaMigracion(dirname(__DIR__) . '/database/migrations');
    if ($panel !== null) {
        $indicadores = new Indicadores($maestro);
        $repoModulos = new PdoRepositorioModulos($maestro);
        $modulos = $repoModulos->todos();
        $consumoIa = $repoModulos->consumoIa($hoy->format('Y-m'));
        if (in_array($seccion, ['resumen', 'instituciones', 'institucion', 'modulos'], true)) {
            $lista = $panel->listar();
        }
    }
    $moduloActivo = static fn (string $slug, ModuloOpcional $m): bool => (bool) ($modulos[$slug][$m->value]['activo'] ?? false);

    /** Formulario de un módulo opcional de una institución (activar / desactivar). */
    $formModulo = static function (array $t, ModuloOpcional $m, string $volver) use ($csrf, $e, $modulos, $consumoIa): string {
        $estado = $modulos[$t['slug']][$m->value] ?? ['activo' => false, 'limite' => null];
        $uso = $m->tieneLimite() && $estado['activo']
            ? '<small class="ml-1 text-muted" title="Consultas de este mes (las actualiza el cron)">' . (int) ($consumoIa[$t['slug']]['consultas'] ?? 0) . ' / '
                . ($estado['limite'] !== null ? (int) $estado['limite'] . ' al mes' : 'sin límite') . '</small>'
            : '';
        $limite = $m->tieneLimite() && !$estado['activo']
            ? '<input type="number" min="0" name="limite" class="form-control form-control-sm mr-1" style="width:100px" placeholder="Límite/mes" title="Consultas al mes (vacío = sin límite)">'
            : '';
        return '<form method="post" class="form-inline mb-1" data-modulo="' . $e($m->value) . '">'
            . '<input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="accion" value="modulo"><input type="hidden" name="volver" value="' . $e($volver) . '">'
            . '<input type="hidden" name="slug" value="' . $e($t['slug']) . '"><input type="hidden" name="modulo" value="' . $e($m->value) . '">'
            . '<input type="hidden" name="activo" value="' . ($estado['activo'] ? '0' : '1') . '">'
            . '<span class="badge badge-' . ($estado['activo'] ? 'success' : 'light') . ' mr-1" style="min-width:150px">' . $e($m->etiqueta()) . '</span>' . $limite
            . '<button class="btn btn-xs ' . ($estado['activo'] ? 'btn-outline-danger' : 'btn-outline-success') . '" type="submit">' . ($estado['activo'] ? 'Desactivar' : 'Activar') . '</button>' . $uso . '</form>';
    };

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= $e($titulos[$seccion][0]) ?> · Panel de superadministrador</title>
  <link rel="stylesheet" href="../plantilla/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plantilla/dist/css/adminlte.min.css">
  <style>
    .badge-purple{background:#6f42c1;color:#fff} .badge-teal{background:#20c997;color:#fff} .badge-light{border:1px solid #ccc}
    .tabla-panel td,.tabla-panel th{vertical-align:middle} .small-box .inner p{margin-bottom:0}
    .barra{height:8px;border-radius:4px;background:#e9ecef;overflow:hidden} .barra>div{height:100%}
    .brand-link .brand-text{font-weight:600} .cifras .small-box .inner{min-height:118px}
  </style>
</head>
<?php if ($panel === null) { ?>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo"><i class="fas fa-shield-alt"></i> <b>Panel</b> SaaS</div>
  <?php if ($aviso !== null) { ?>
    <div class="alert alert-<?= $e($aviso[0]) ?>" role="alert" id="aviso"><?= $e($aviso[1]) ?></div>
  <?php } ?>
  <div class="card"><div class="card-body login-card-body">
    <p class="login-box-msg">Acceso de superadministrador</p>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="accion" value="entrar">
      <div class="input-group mb-3"><input class="form-control" id="usuario" name="usuario" placeholder="Usuario" required aria-label="Usuario">
        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div></div>
      <div class="input-group mb-3"><input class="form-control" id="clave" name="clave" type="password" placeholder="Contraseña" required aria-label="Contraseña">
        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div></div>
      <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>
  </div></div>
</div>
</body>
</html>
<?php return;
} ?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Menú"><i class="fas fa-bars"></i></a></li></ul>
    <ul class="navbar-nav ml-auto align-items-center">
      <li class="nav-item mr-2"><i class="fas fa-user-shield mr-1"></i><?= $e((string) $_SESSION['sa_nombre']) ?></li>
      <li class="nav-item"><form method="post" class="mb-0"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="salir">
        <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fas fa-sign-out-alt"></i> Salir</button></form></li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="./" class="brand-link"><i class="fas fa-shield-alt brand-image mt-1 ml-3"></i><span class="brand-text">Panel SaaS</span></a>
    <div class="sidebar"><nav class="mt-2"><ul class="nav nav-pills nav-sidebar flex-column" role="menu">
      <?php foreach (['resumen', 'instituciones', 'alta', 'planes', 'cobros', 'modulos', 'auditoria'] as $s) { ?>
        <li class="nav-item"><a href="./?s=<?= $s ?>" class="nav-link<?= $seccion === $s || ($s === 'instituciones' && $seccion === 'institucion') ? ' active' : '' ?>">
          <i class="nav-icon fas <?= $titulos[$s][1] ?>"></i><p><?= $e($titulos[$s][0]) ?></p></a></li>
      <?php } ?>
    </ul></nav></div>
  </aside>

  <div class="content-wrapper">
    <div class="content-header"><div class="container-fluid"><div class="row mb-2">
      <div class="col-sm-8"><h1 class="m-0"><i class="fas <?= $titulos[$seccion][1] ?> mr-2"></i><?= $e($titulos[$seccion][0]) ?></h1></div>
      <div class="col-sm-4 text-sm-right text-muted pt-2"><?= $e($hoy->format('d/m/Y')) ?></div>
    </div></div></div>
    <section class="content"><div class="container-fluid">
      <?php if ($aviso !== null) { ?>
        <div class="alert alert-<?= $e($aviso[0]) ?> alert-dismissible" role="alert" id="aviso"><button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">&times;</button><?= $e($aviso[1]) ?></div>
      <?php } ?>

<?php if ($seccion === 'resumen') {
    $estados = $indicadores->porEstado();
    $tipos = $indicadores->porTipo();
    $cobros = $indicadores->cobros($hoy);
    $serie = $indicadores->cobrosPorMes($hoy);
    $ia = $indicadores->consumoIa($hoy->format('Y-m'));
    $alertas = $indicadores->alertas($hoy);
    $alumnos = array_sum(array_map(static fn (array $t): int => (int) $t['alumnos'], $lista));
    $usuarios = array_sum(array_map(static fn (array $t): int => (int) $t['usuarios'], $lista));
    $total = array_sum($estados);
    foreach ($lista as $t) {
        if ($t['error'] !== null && $t['estado'] !== 'CANCELADO') {
            $alertas[] = ['tipo' => 'base', 'nivel' => 'danger', 'slug' => $t['slug'], 'texto' => $t['error']];
        } elseif ($t['migracion'] !== null && $t['migracion'] !== $ultimaMigracion) {
            $alertas[] = ['tipo' => 'migracion', 'nivel' => 'warning', 'slug' => $t['slug'], 'texto' => "Base sin migrar ({$t['migracion']}; la última es $ultimaMigracion): php tools/migrar_tenants.php"];
        }
    } ?>
      <div class="row cifras">
        <?php foreach ([
            ['Instituciones', $total, ($estados['ACTIVO'] ?? 0) . ' activas · ' . ($estados['PRUEBA'] ?? 0) . ' en prueba', 'primary', 'fa-university', 'instituciones'],
            ['Alumnos activos', $alumnos, $usuarios . ' usuarios en total', 'info', 'fa-user-graduate', 'instituciones'],
            ['Cobrado este mes', $dinero($cobros['cobrado_mes']), $dinero($cobros['por_cobrar']) . ' por cobrar', 'success', 'fa-coins', 'cobros'],
            ['Vencido', $dinero($cobros['vencido']), $cobros['morosos'] . ' institución(es) morosa(s)', $cobros['vencido'] > 0 ? 'danger' : 'secondary', 'fa-exclamation-circle', 'cobros'],
            ['Consultas de IA del mes', $ia['consultas'], number_format($ia['tokens_entrada'] + $ia['tokens_salida']) . ' tokens', 'purple', 'fa-robot', 'modulos'],
            ['Alertas', count($alertas), 'requieren atención', count($alertas) > 0 ? 'warning' : 'secondary', 'fa-bell', 'resumen'],
        ] as [$titulo, $valor, $detalle, $color, $icono, $destino]) { ?>
          <div class="col-lg-2 col-md-4 col-6"><div class="small-box bg-<?= $color ?>"><div class="inner"><h3 style="font-size:1.6rem"><?= $e((string) $valor) ?></h3>
            <p><?= $e($titulo) ?><br><small><?= $e($detalle) ?></small></p></div><div class="icon"><i class="fas <?= $icono ?>"></i></div>
            <a href="./?s=<?= $destino ?>" class="small-box-footer">Ver <i class="fas fa-arrow-circle-right"></i></a></div></div>
        <?php } ?>
      </div>
      <div class="row">
        <div class="col-lg-8"><div class="card"><div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Cobros: emitido y cobrado (últimos 6 meses)</h3></div>
          <div class="card-body"><canvas id="grafico_cobros" height="110"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Instituciones</h3></div>
          <div class="card-body">
            <?php foreach ($estados as $estado => $n) { ?>
              <div class="d-flex justify-content-between"><span><?= $insignia($estado, $colorEstado[$estado]) ?></span><b><?= $n ?></b></div>
              <div class="barra mb-2"><div class="bg-<?= $colorEstado[$estado] ?>" style="width:<?= $total > 0 ? round(100 * $n / $total) : 0 ?>%"></div></div>
            <?php } ?>
            <hr>
            <?php foreach ($tipos as $tipo => $n) { ?>
              <div class="d-flex justify-content-between"><span><?= $insignia($tipo, $colorTipo[$tipo]) ?></span><b><?= $n ?></b></div>
            <?php } ?>
          </div></div></div>
      </div>
      <div class="row">
        <div class="col-lg-6"><div class="card card-outline card-warning"><div class="card-header"><h3 class="card-title"><i class="fas fa-bell mr-1"></i> Alertas</h3></div>
          <div class="card-body p-0"><ul class="list-group list-group-flush" id="lista_alertas">
            <?php if ($alertas === []) { ?><li class="list-group-item text-muted">Todo en orden.</li><?php } ?>
            <?php foreach ($alertas as $a) { ?>
              <li class="list-group-item d-flex align-items-start"><span class="badge badge-<?= $e($a['nivel']) ?> mr-2 mt-1">&nbsp;</span>
                <div><a href="./?s=institucion&amp;slug=<?= $e(rawurlencode($a['slug'])) ?>"><b><?= $e($a['slug']) ?></b></a> — <?= $e($a['texto']) ?></div></li>
            <?php } ?>
          </ul></div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-1"></i> Actividad reciente</h3>
          <div class="card-tools"><a href="./?s=auditoria" class="btn btn-tool">Ver todo</a></div></div>
          <div class="card-body p-0"><table class="table table-sm mb-0 tabla-panel"><tbody>
            <?php foreach ($auditoria->recientes(10) as $a) { ?>
              <tr><td><small class="text-muted"><?= $e($a['fecha']) ?></small></td><td><?= $e($a['actor']) ?></td><td><?= $e($a['accion']) ?></td><td><?= $e($a['tenant']) ?></td></tr>
            <?php } ?>
          </tbody></table></div></div></div>
      </div>
      <script src="../plantilla/plugins/chart.js/Chart.min.js"></script>
      <script>
        new Chart(document.getElementById('grafico_cobros'), {
          type: 'bar',
          data: {
            labels: <?= json_encode(array_column($serie, 'mes')) ?>,
            datasets: [
              { label: 'Emitido', backgroundColor: '#6c757d', data: <?= json_encode(array_column($serie, 'emitido')) ?> },
              { label: 'Cobrado', backgroundColor: '#28a745', data: <?= json_encode(array_column($serie, 'cobrado')) ?> }
            ]
          },
          options: { legend: { position: 'bottom' }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } }
        });
      </script>

<?php } elseif ($seccion === 'instituciones') {
    $q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
    $fEstado = (string) ($_GET['estado'] ?? '');
    $fTipo = (string) ($_GET['tipo'] ?? '');
    $filtradas = array_filter($lista, static fn (array $t): bool => ($q === '' || str_contains(mb_strtolower($t['slug'] . ' ' . $t['razon_social']), $q))
        && ($fEstado === '' || $t['estado'] === $fEstado) && ($fTipo === '' || $t['tipo'] === $fTipo)); ?>
      <form class="form-inline mb-3" method="get">
        <input type="hidden" name="s" value="instituciones">
        <input class="form-control mr-2 mb-2" name="q" value="<?= $e($q) ?>" placeholder="Buscar por nombre o subdominio" aria-label="Buscar">
        <select class="form-control mr-2 mb-2" name="estado" aria-label="Estado"><option value="">Todos los estados</option>
          <?php foreach (EstadoTenant::cases() as $est) { ?><option<?= $fEstado === $est->value ? ' selected' : '' ?>><?= $e($est->value) ?></option><?php } ?></select>
        <select class="form-control mr-2 mb-2" name="tipo" aria-label="Tipo"><option value="">Todos los tipos</option>
          <?php foreach (['COLEGIO', 'INSTITUTO', 'CETPRO'] as $tipo) { ?><option<?= $fTipo === $tipo ? ' selected' : '' ?>><?= $tipo ?></option><?php } ?></select>
        <button class="btn btn-primary mb-2 mr-2" type="submit"><i class="fas fa-search"></i> Filtrar</button>
        <a class="btn btn-success mb-2 ml-auto" href="./?s=alta"><i class="fas fa-plus"></i> Dar de alta</a>
      </form>
      <div class="card"><div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0 tabla-panel" id="tabla_instituciones">
          <thead><tr><th>Institución</th><th>Tipo</th><th>Estado</th><th>Plan</th><th>Alumnos</th><th>Usuarios</th><th>Módulos</th><th>Base</th><th>Alta</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($filtradas as $t) { ?>
            <tr data-slug="<?= $e($t['slug']) ?>">
              <td><b><?= $e($t['razon_social']) ?></b><br><a href="https://<?= $e($t['dominio'] ?? "{$t['slug']}.$dominio") ?>/" rel="noopener" target="_blank"><small><?= $e($t['dominio'] ?? "{$t['slug']}.$dominio") ?></small></a></td>
              <td><?= $insignia($t['tipo'], $colorTipo[$t['tipo']] ?? 'secondary') ?></td>
              <td><?= $insignia($t['estado'], $colorEstado[$t['estado']] ?? 'secondary') ?><?= $t['demo'] ? ' ' . $insignia('DEMO', 'warning') : '' ?>
                <?= $t['prueba_hasta'] !== null && $t['estado'] === 'PRUEBA' ? '<br><small class="text-muted">hasta ' . $e($t['prueba_hasta']) . '</small>' : '' ?></td>
              <td><?= $e($t['plan'] ?? '—') ?></td>
              <?php if ($t['error'] !== null) { ?>
                <td colspan="2" class="text-danger"><small><?= $e($t['error']) ?></small></td>
              <?php } else { ?>
                <td><?= (int) $t['alumnos'] ?><?= $t['max_alumnos'] !== null ? ' / ' . (int) $t['max_alumnos'] : '' ?></td>
                <td><?= (int) $t['usuarios'] ?><?= $t['max_usuarios'] !== null ? ' / ' . (int) $t['max_usuarios'] : '' ?></td>
              <?php } ?>
              <td><?php foreach (ModuloOpcional::cases() as $m) { ?>
                <i class="fas <?= $m === ModuloOpcional::AsistenteIa ? 'fa-robot' : 'fa-file-invoice' ?> mr-1 <?= $moduloActivo($t['slug'], $m) ? 'text-success' : 'text-black-50' ?>" title="<?= $e($m->etiqueta()) ?>: <?= $moduloActivo($t['slug'], $m) ? 'activo' : 'no' ?>"></i>
              <?php } ?></td>
              <td><small class="<?= $t['migracion'] !== null && $t['migracion'] !== $ultimaMigracion ? 'text-danger' : 'text-muted' ?>"><?= $e($t['migracion'] ?? '—') ?></small></td>
              <td><small><?= $e(substr($t['fecha_alta'], 0, 10)) ?></small></td>
              <td><a class="btn btn-sm btn-outline-primary" href="./?s=institucion&amp;slug=<?= $e(rawurlencode($t['slug'])) ?>">Gestionar</a></td>
            </tr>
          <?php } ?>
          <?php if ($filtradas === []) { ?><tr><td colspan="10" class="text-muted">Ninguna institución con esos filtros.</td></tr><?php } ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer"><small>PRUEBA, ACTIVO y MOROSO entran (MOROSO sin altas ni matrículas); SUSPENDIDO: solo su administrador, para
        descargar sus datos, durante la ventana de exportación; CANCELADO responde 404. Nada se borra.</small></div></div>

<?php } elseif ($seccion === 'institucion') {
    $slug = (string) ($_GET['slug'] ?? '');
    $t = array_values(array_filter($lista, static fn (array $x): bool => $x['slug'] === $slug))[0] ?? null;
    if ($t === null) { ?>
      <div class="alert alert-warning">No existe la institución «<?= $e($slug) ?>». <a href="./?s=instituciones">Volver a la lista</a>.</div>
    <?php } else {
        $planes = $panel->planes(); ?>
      <div class="row">
        <div class="col-lg-4">
          <div class="card card-primary card-outline"><div class="card-body box-profile">
            <h3 class="profile-username text-center mb-1"><?= $e($t['razon_social']) ?></h3>
            <p class="text-center mb-2"><?= $insignia($t['tipo'], $colorTipo[$t['tipo']] ?? 'secondary') ?> <?= $insignia($t['estado'], $colorEstado[$t['estado']] ?? 'secondary') ?><?= $t['demo'] ? ' ' . $insignia('DEMO', 'warning') : '' ?></p>
            <ul class="list-group list-group-unbordered mb-3">
              <li class="list-group-item">Dirección <a class="float-right" target="_blank" rel="noopener" href="https://<?= $e($t['dominio'] ?? "{$t['slug']}.$dominio") ?>/"><?= $e($t['dominio'] ?? "{$t['slug']}.$dominio") ?></a></li>
              <li class="list-group-item">Base de datos <span class="float-right"><?= $e($t['base_datos']) ?></span></li>
              <li class="list-group-item">Alumnos <b class="float-right"><?= $t['error'] === null ? (int) $t['alumnos'] . ($t['max_alumnos'] !== null ? ' / ' . (int) $t['max_alumnos'] : '') : '—' ?></b></li>
              <li class="list-group-item">Usuarios <b class="float-right"><?= $t['error'] === null ? (int) $t['usuarios'] . ($t['max_usuarios'] !== null ? ' / ' . (int) $t['max_usuarios'] : '') : '—' ?></b></li>
              <li class="list-group-item">Migración <span class="float-right <?= $t['migracion'] !== $ultimaMigracion ? 'text-danger' : '' ?>"><?= $e($t['migracion'] ?? '—') ?></span></li>
              <li class="list-group-item">Alta <span class="float-right"><?= $e($t['fecha_alta']) ?></span></li>
              <li class="list-group-item">Consultas de IA del mes <b class="float-right"><?= (int) ($consumoIa[$t['slug']]['consultas'] ?? 0) ?></b></li>
            </ul>
            <?php if ($t['error'] !== null) { ?><div class="alert alert-danger mb-0"><?= $e($t['error']) ?></div><?php } ?>
          </div></div>
        </div>
        <div class="col-lg-8">
          <div class="card"><div class="card-header"><h3 class="card-title">Estado, tipo y plan</h3></div><div class="card-body">
            <form method="post" class="form-inline mb-3">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="volver" value="institucion">
              <input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
              <label class="mr-2" for="estado_<?= $e($t['slug']) ?>" style="width:60px">Estado</label>
              <select id="estado_<?= $e($t['slug']) ?>" name="estado" class="form-control mr-2">
                <?php foreach (EstadoTenant::cases() as $est) { ?><option value="<?= $e($est->value) ?>"<?= $est->value === $t['estado'] ? ' selected' : '' ?>><?= $e($est->value) ?></option><?php } ?>
              </select>
              <button class="btn btn-primary" type="submit">Aplicar</button>
            </form>
            <form method="post" class="form-inline mb-3">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="tipo"><input type="hidden" name="volver" value="institucion">
              <input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
              <label class="mr-2" for="tipo_<?= $e($t['slug']) ?>" style="width:60px">Tipo</label>
              <select id="tipo_<?= $e($t['slug']) ?>" name="tipo" class="form-control mr-2">
                <?php foreach (['COLEGIO', 'INSTITUTO', 'CETPRO'] as $tipo) { ?><option<?= $tipo === $t['tipo'] ? ' selected' : '' ?>><?= $tipo ?></option><?php } ?>
              </select>
              <button class="btn btn-primary" type="submit">Cambiar</button>
              <small class="text-muted ml-2">Cambia sus reglas académicas por defecto, su menú y su tablero.</small>
            </form>
            <form method="post" class="form-inline">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="asignar_plan"><input type="hidden" name="volver" value="institucion">
              <input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
              <label class="mr-2" for="plan_<?= $e($t['slug']) ?>" style="width:60px">Plan</label>
              <select id="plan_<?= $e($t['slug']) ?>" name="plan" class="form-control mr-2">
                <?php if ($t['plan'] === null) { ?><option value="">(sin plan)</option><?php } ?>
                <?php foreach ($planes as $p) { ?><option value="<?= $e($p->codigo) ?>"<?= $p->codigo === $t['plan'] ? ' selected' : '' ?>><?= $e($p->codigo . ' · ' . $p->nombre) ?></option><?php } ?>
              </select>
              <input type="date" name="prueba_hasta" class="form-control mr-2" value="<?= $e($t['prueba_hasta']) ?>" title="Fin de la prueba (vacío si no está en prueba)" aria-label="Fin de la prueba">
              <button class="btn btn-outline-primary" type="submit">Asignar</button>
            </form>
          </div></div>
          <div class="card"><div class="card-header"><h3 class="card-title">Módulos opcionales</h3></div><div class="card-body">
            <?php foreach (ModuloOpcional::cases() as $m) {
                echo $formModulo($t, $m, 'institucion');
            } ?>
          </div></div>
          <?php if ($t['demo']) { ?>
          <div class="card card-warning card-outline"><div class="card-header"><h3 class="card-title">Convertir la demo en cliente</h3></div>
            <form method="post" autocomplete="off" class="card-body">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="convertir_demo"><input type="hidden" name="slug" value="<?= $e($t['slug']) ?>">
              <div class="form-row">
                <div class="col-md-6 mb-2"><input class="form-control" name="razon" value="<?= $e($t['razon_social']) ?>" required aria-label="Razón social"></div>
                <div class="col-md-6 mb-2"><input class="form-control" name="email" type="email" placeholder="Correo" required></div>
                <div class="col-md-4 mb-2"><input class="form-control" name="admin_dni" placeholder="DNI del administrador" required pattern="\d{8}"></div>
                <div class="col-md-4 mb-2"><input class="form-control" name="admin_nombres" placeholder="Nombres" required></div>
                <div class="col-md-4 mb-2"><input class="form-control" name="admin_apellidos" placeholder="Apellidos" required></div>
                <div class="col-md-4 mb-2"><select class="form-control" name="estado" aria-label="Estado"><option>ACTIVO</option><option>PRUEBA</option></select></div>
              </div>
              <button class="btn btn-warning" type="submit">Convertir (borra los datos de ejemplo)</button>
            </form></div>
          <?php } ?>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Cobros</h3></div><div class="card-body table-responsive p-0">
          <table class="table table-sm mb-0"><thead><tr><th>Número</th><th>Periodo</th><th>Vence</th><th>Monto</th><th>Estado</th></tr></thead><tbody>
            <?php foreach ($indicadores->cobrosDe($t['slug']) as $c) { ?>
              <tr><td><?= $e($c['numero']) ?></td><td><small><?= $e($c['periodo_inicio']) ?> – <?= $e($c['periodo_fin']) ?></small></td><td><?= $e($c['vencimiento']) ?></td>
                <td><?= $e($c['moneda']) ?> <?= $e($c['monto']) ?></td><td><?= $e($c['estado']) ?></td></tr>
            <?php } ?>
          </tbody></table></div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Auditoría de esta institución</h3></div><div class="card-body table-responsive p-0">
          <table class="table table-sm mb-0"><thead><tr><th>Fecha</th><th>Quién</th><th>Acción</th><th>Detalle</th></tr></thead><tbody>
            <?php foreach ($indicadores->auditoriaDe($t['slug']) as $a) { ?>
              <tr><td><small><?= $e($a['fecha']) ?></small></td><td><?= $e($a['actor']) ?></td><td><?= $e($a['accion']) ?></td><td><small><?= $e($a['detalle']) ?></small></td></tr>
            <?php } ?>
          </tbody></table></div></div></div>
      </div>
    <?php } ?>

<?php } elseif ($seccion === 'alta') { ?>
      <div class="card card-success card-outline"><div class="card-header"><h3 class="card-title">Nueva institución</h3>
        <small class="text-muted ml-2">Crea su base, la migra y crea su administrador (unos segundos).</small></div>
        <form method="post" class="card-body" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="alta">
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
              <label class="form-check-label" for="demo">Demo: con datos de ejemplo (ficticios) para que la institución pruebe el sistema; luego se convierte en cliente</label></div></div>
          </div>
          <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i> Crear institución</button>
        </form></div>

<?php } elseif ($seccion === 'planes') { ?>
      <div class="card"><div class="card-header"><h3 class="card-title">Planes</h3><small class="text-muted ml-2">Límites vacíos = sin límite; precios vacíos = no se cobra por ese concepto.</small></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover mb-0 tabla-panel" id="tabla_planes">
            <thead><tr><th>Código</th><th>Nombre</th><th>Alumnos</th><th>Usuarios</th><th>MB</th><th>Mensual</th><th>Por alumno</th><th>Moneda</th></tr></thead>
            <tbody>
            <?php foreach ($panel->planes() as $p) { ?>
              <tr data-plan="<?= $e($p->codigo) ?>"><td><b><?= $e($p->codigo) ?></b></td><td><?= $e($p->nombre) ?></td>
                <td><?= $e((string) ($p->limite(Recurso::Alumnos) ?? '—')) ?></td><td><?= $e((string) ($p->limite(Recurso::Usuarios) ?? '—')) ?></td>
                <td><?= $e((string) ($p->limite(Recurso::AlmacenamientoMb) ?? '—')) ?></td><td><?= $e($p->precioMensual ?? '—') ?></td>
                <td><?= $e($p->precioPorAlumno ?? '—') ?></td><td><?= $e($p->moneda) ?></td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
        <form method="post" class="card-body border-top" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="accion" value="plan">
          <div class="form-row">
            <div class="col-md-2 mb-2"><label for="plan_codigo">Código</label><input class="form-control" id="plan_codigo" name="codigo" required pattern="[A-Za-z0-9_]{2,30}" placeholder="BASICO"></div>
            <div class="col-md-3 mb-2"><label for="plan_nombre">Nombre</label><input class="form-control" id="plan_nombre" name="nombre" required maxlength="100"></div>
            <div class="col-md-1 mb-2"><label for="plan_alumnos">Alumnos</label><input class="form-control" id="plan_alumnos" name="max_alumnos" inputmode="numeric"></div>
            <div class="col-md-1 mb-2"><label for="plan_usuarios">Usuarios</label><input class="form-control" id="plan_usuarios" name="max_usuarios" inputmode="numeric"></div>
            <div class="col-md-1 mb-2"><label for="plan_mb">MB</label><input class="form-control" id="plan_mb" name="max_almacenamiento_mb" inputmode="numeric"></div>
            <div class="col-md-1 mb-2"><label for="plan_mensual">Mensual</label><input class="form-control" id="plan_mensual" name="precio_mensual" inputmode="decimal"></div>
            <div class="col-md-2 mb-2"><label for="plan_por_alumno">Por alumno</label><input class="form-control" id="plan_por_alumno" name="precio_por_alumno" inputmode="decimal"></div>
            <div class="col-md-1 mb-2"><label for="plan_moneda">Moneda</label><input class="form-control" id="plan_moneda" name="moneda" value="PEN" maxlength="3"></div>
          </div>
          <button class="btn btn-success" type="submit">Guardar plan</button> <small class="text-muted">Con un código existente, lo modifica.</small>
        </form></div>

<?php } elseif ($seccion === 'cobros') {
    $cobrosResumen = $indicadores->cobros($hoy); ?>
      <div class="row">
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-coins"></i></span><div class="info-box-content"><span class="info-box-text">Cobrado este mes</span><span class="info-box-number"><?= $e($dinero($cobrosResumen['cobrado_mes'])) ?></span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-info"><i class="fas fa-hourglass-half"></i></span><div class="info-box-content"><span class="info-box-text">Por cobrar</span><span class="info-box-number"><?= $e($dinero($cobrosResumen['por_cobrar'])) ?></span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span><div class="info-box-content"><span class="info-box-text">Vencido</span><span class="info-box-number"><?= $e($dinero($cobrosResumen['vencido'])) ?></span></div></div></div>
      </div>
      <div class="card"><div class="card-header"><h3 class="card-title">Cobros</h3><small class="text-muted ml-2">Los emite cada día tools/facturacion.php; son cobros internos,
        no comprobantes SUNAT (esos se emiten con un OSE/PSE).</small></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm mb-0 tabla-panel" id="tabla_cobros">
            <thead><tr><th>Número</th><th>Institución</th><th>Periodo</th><th>Vence</th><th>Monto</th><th>Detalle</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ((new Facturacion($maestro))->recientes(100) as $c) { ?>
              <tr data-cobro="<?= $e($c['numero']) ?>">
                <td><?= $e($c['numero']) ?></td><td><a href="./?s=institucion&amp;slug=<?= $e(rawurlencode((string) $c['slug'])) ?>"><?= $e($c['slug']) ?></a></td>
                <td><small><?= $e($c['periodo_inicio']) ?> – <?= $e($c['periodo_fin']) ?></small></td><td><?= $e($c['vencimiento']) ?></td>
                <td><?= $e($c['moneda']) ?> <?= $e($c['monto']) ?></td><td><small><?= $e($c['detalle']) ?></small></td>
                <td><?= $insignia((string) $c['estado'], ['PENDIENTE' => 'warning', 'PAGADA' => 'success', 'ANULADA' => 'secondary'][$c['estado']] ?? 'light') ?>
                  <?= $c['estado'] === 'PAGADA' ? '<br><small>' . $e($c['pagada_en']) . ' · ' . $e($c['medio_pago']) . ' ' . $e($c['referencia_pago']) . '</small>' : '' ?></td>
                <td>
                  <?php if ($c['estado'] === 'PENDIENTE') { ?>
                    <form method="post" class="form-inline">
                      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="numero" value="<?= $e($c['numero']) ?>">
                      <input name="medio" class="form-control form-control-sm mr-1" placeholder="Medio" maxlength="40" style="width:120px" aria-label="Medio de pago">
                      <input name="referencia" class="form-control form-control-sm mr-1" placeholder="N.º de operación" maxlength="100" style="width:130px" aria-label="Referencia">
                      <button class="btn btn-sm btn-success mr-1" type="submit" name="accion" value="cobro_pagado">Pagado</button>
                      <button class="btn btn-sm btn-outline-danger" type="submit" name="accion" value="cobro_anulado">Anular</button>
                    </form>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div></div>

<?php } elseif ($seccion === 'modulos') {
    $ia = $indicadores->consumoIa($hoy->format('Y-m')); ?>
      <div class="row">
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-purple"><i class="fas fa-robot"></i></span><div class="info-box-content"><span class="info-box-text">Consultas de IA del mes</span><span class="info-box-number"><?= $ia['consultas'] ?></span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-secondary"><i class="fas fa-sign-in-alt"></i></span><div class="info-box-content"><span class="info-box-text">Tokens de entrada</span><span class="info-box-number"><?= number_format($ia['tokens_entrada']) ?></span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-secondary"><i class="fas fa-sign-out-alt"></i></span><div class="info-box-content"><span class="info-box-text">Tokens de salida</span><span class="info-box-number"><?= number_format($ia['tokens_salida']) ?></span></div></div></div>
      </div>
      <div class="card"><div class="card-header"><h3 class="card-title">Módulos por institución</h3><small class="text-muted ml-2">El consumo de IA del mes lo actualiza el cron cada día.</small></div>
        <div class="card-body table-responsive p-0">
          <table class="table mb-0 tabla-panel" id="tabla_modulos">
            <thead><tr><th>Institución</th><?php foreach (ModuloOpcional::cases() as $m) { ?><th><?= $e($m->etiqueta()) ?></th><?php } ?></tr></thead>
            <tbody>
            <?php foreach ($lista as $t) { ?>
              <tr data-slug="<?= $e($t['slug']) ?>"><td><a href="./?s=institucion&amp;slug=<?= $e(rawurlencode($t['slug'])) ?>"><b><?= $e($t['razon_social']) ?></b></a><br><small class="text-muted"><?= $e($t['slug']) ?></small></td>
                <?php foreach (ModuloOpcional::cases() as $m) { ?><td><?= $formModulo($t, $m, 'modulos') ?></td><?php } ?></tr>
            <?php } ?>
            </tbody>
          </table>
        </div></div>

<?php } elseif ($seccion === 'auditoria') { ?>
      <div class="card"><div class="card-header"><h3 class="card-title">Auditoría del panel</h3><small class="text-muted ml-2">Últimas 200 acciones.</small></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm table-striped mb-0" id="tabla_auditoria">
            <thead><tr><th>Fecha</th><th>Quién</th><th>Acción</th><th>Institución</th><th>Detalle</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($auditoria->recientes(200) as $a) { ?>
              <tr><td><small><?= $e($a['fecha']) ?></small></td><td><?= $e($a['actor']) ?></td><td><?= $e($a['accion']) ?></td><td><?= $e($a['tenant']) ?></td><td><small><?= $e($a['detalle']) ?></small></td><td><small><?= $e($a['ip']) ?></small></td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div></div>
<?php } ?>
    </div></section>
  </div>
  <footer class="main-footer"><small>Panel de superadministrador · las acciones quedan en la auditoría.</small></footer>
</div>
<script src="../plantilla/plugins/jquery/jquery.min.js"></script>
<script src="../plantilla/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plantilla/dist/js/adminlte.min.js"></script>
</body>
</html>
<?php
}
