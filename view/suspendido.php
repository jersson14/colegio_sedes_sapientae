<?php
/**
 * Panel de una institución SUSPENDIDA (Fase 4B.3): solo la descarga de sus datos, y solo para su
 * administrador, durante la ventana de exportación. Lo incluye view/index.php; nada más funciona
 * (core/guard.php responde 403 a cualquier otro endpoint).
 */
$condiciones = comercial_condiciones();
$hasta = $condiciones->exportacionHasta?->format('d/m/Y');
$esAdministrador = ($_SESSION['S_ROL'] ?? '') === 'ADMINISTRADOR';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= marca_html(marca()->nombre) ?> · servicio suspendido</title>
  <link rel="stylesheet" href="../plantilla/dist/css/adminlte.min.css">
  <?= marca_estilo() ?>
</head>
<body class="hold-transition" style="background:#f4f6f9">
  <div class="container" style="max-width:640px;margin-top:4rem">
    <div class="card card-outline card-danger">
      <div class="card-header"><h1 class="h4 m-0"><?= marca_html(marca()->nombre) ?></h1></div>
      <div class="card-body" id="suspendido">
        <p><b>El servicio está suspendido.</b> Los datos de la institución no se han borrado.</p>
        <?php if ($esAdministrador) { ?>
          <p>Puedes descargar todos sus datos (un archivo por tabla y los documentos) <?= $hasta !== null ? 'hasta el <b>' . marca_html($hasta) . '</b>' : '' ?>.</p>
          <a class="btn btn-primary" id="descargar_datos" href="../controller/exportacion/controlador_exportar_datos.php">Descargar los datos</a>
        <?php } else { ?>
          <p>Comunícate con la dirección de la institución.</p>
        <?php } ?>
        <a class="btn btn-link" href="../controller/usuario/controlador_cerrar_sesion.php">Cerrar sesión</a>
      </div>
    </div>
  </div>
</body>
</html>
