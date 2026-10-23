<?php
require_once __DIR__ . '/../../utilitario/regional.php';
// Conexión mysqli para los reportes MPDF; usa la misma configuración que los modelos.
$config = require __DIR__ . '/../../config/config.php';
$db = $config['db'];
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = @new mysqli($db['host'], $db['usuario'], $db['contrasena'], $db['nombre'], $db['puerto']);
if ($mysqli->connect_errno) {
    error_log('Error de conexión a la BD (MPDF): ' . $mysqli->connect_error);
    http_response_code(500);
    exit('Error de conexión con la base de datos');
}
date_default_timezone_set(config_regional()['zona_horaria']);
$mysqli->query("SET time_zone = '" . (new DateTime())->format('P') . "'");
?>
