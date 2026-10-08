<?php
// Las credenciales viven en colegio.env, fuera de htdocs (ver core/config.php).
// Este archivo ya no contiene secretos y se versiona.
// La conexión se crea en src/Core/Conexion.php, la misma que usa el código nuevo.
// config.php se carga AQUÍ y no solo al conectar: al cargarse apaga display_errors, y todo
// controlador lo necesita desde el principio (si no, un aviso muestra rutas del servidor).
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/autoload.php';

class conexionBD {
    private $pdo;

    public function conexionPDO() {
        $this->pdo = null;
        try {
            $this->pdo = \App\Core\Conexion::crear();
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
