<?php
class conexionBD {
    private $pdo;

    public function conexionPDO() {
        $config = require __DIR__ . '/../config/config.php';
        $db = $config['db'];
        $this->pdo = null;

        try {
            $dsn = "mysql:host={$db['host']};port={$db['puerto']};dbname={$db['nombre']}";
            $this->pdo = new PDO($dsn, $db['usuario'], $db['contrasena']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("SET NAMES utf8");

            // La zona horaria de MySQL sigue a la de PHP (configurable en config.php)
            date_default_timezone_set($config['zona_horaria']);
            $this->pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");

            return $this->pdo;

        } catch (PDOException $e) {
            // No mostrar detalles de la conexión al navegador
            error_log('Error de conexión a la BD: ' . $e->getMessage());
            http_response_code(500);
            exit('Error de conexión con la base de datos');
        }
    }

    public function cerrar_conexion() {
        $this->pdo = null;
    }
}
?>
