<?php
require_once __DIR__ . '/../utilitario/regional.php';

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

            // Zona horaria del país configurado (Configuración > Datos regionales); MySQL sigue a PHP
            date_default_timezone_set(config_regional()['zona_horaria']);
            $this->pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");

            // Auditoría (Fase 8D): los triggers guardan quién hizo cada cambio y desde qué IP
            $usuario = (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['S_ID'])) ? (int) $_SESSION['S_ID'] : null;
            $q = $this->pdo->prepare("SET @app_usuario = ?, @app_ip = ?");
            $q->execute([$usuario, substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45)]);

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
