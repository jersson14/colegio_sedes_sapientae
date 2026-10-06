<?php
// Las credenciales viven en colegio.env, fuera de htdocs (ver core/config.php).
// Este archivo ya no contiene secretos y se versiona.
require_once __DIR__ . '/../core/config.php';

class conexionBD {
    private $pdo;

    public function conexionPDO() {
        $host       = config('DB_HOST', 'localhost');
        $puerto     = (int) config('DB_PORT', '3306');
        $usuario    = config('DB_USER', '');
        $contrasena = config('DB_PASS', '');
        $bdName     = config('DB_NAME', 'colegio');
        $this->pdo = null;

        try {
            $this->pdo = new PDO("mysql:host=$host;port=$puerto;dbname=$bdName", $usuario, $contrasena);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
            return $this->pdo;
        } catch (PDOException $e) {
            // El detalle va al log; al cliente nunca (expone host, usuario y motor).
            error_log('Conexión PDO fallida: ' . $e->getMessage());
            http_response_code(500);
            exit('Error de conexión con la base de datos.');
        }
    }

    public function cerrar_conexion() {
        $this->pdo = null;
    }
}
?>
