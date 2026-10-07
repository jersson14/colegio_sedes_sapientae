<?php
// Las credenciales viven en colegio.env, fuera de htdocs (ver core/config.php).
// Este archivo ya no contiene secretos y se versiona.
require_once __DIR__ . '/../../core/config.php';

try {
    $mysqli = new mysqli(
        config('DB_HOST', 'localhost'),
        config('DB_USER', ''),
        config('DB_PASS', ''),
        config('DB_NAME', 'colegio'),
        (int) config('DB_PORT', '3306')
    );
    // Solo pruebas: congela NOW()/CURDATE() (ver model/model_conexion.php).
    if (config('APP_ENTORNO') === 'prueba' && config('DB_FECHA_PRUEBA')) {
        $fijar = $mysqli->prepare('SET SESSION timestamp = UNIX_TIMESTAMP(?)');
        $fecha = config('DB_FECHA_PRUEBA');
        $fijar->bind_param('s', $fecha);
        $fijar->execute();
    }
} catch (mysqli_sql_exception $e) {
    // El detalle va al log; al cliente nunca (expone host, usuario y motor).
    error_log('Conexión MySQLi fallida: ' . $e->getMessage());
    http_response_code(500);
    exit('Error de conexión con la base de datos.');
}
?>
