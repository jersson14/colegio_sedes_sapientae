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
            // El sistema se construyó sobre el sql_mode permisivo de XAMPP (p. ej. '' en un parámetro INT
            // se toma como 0). Con el modo estricto por defecto de otros servidores esas llamadas fallan
            // con 500: se fija explícitamente para no depender de la configuración del servidor.
            $this->pdo->prepare("SET SESSION sql_mode = ?")->execute([config('DB_SQL_MODE', 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION')]);
            // Solo pruebas: congela NOW()/CURDATE() para que los SP que filtran por fecha sean deterministas.
            if (config('APP_ENTORNO') === 'prueba' && config('DB_FECHA_PRUEBA')) {
                $this->pdo->prepare("SET SESSION timestamp = UNIX_TIMESTAMP(?)")->execute([config('DB_FECHA_PRUEBA')]);
            }
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
