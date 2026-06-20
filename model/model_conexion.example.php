<?php
// Renombrar este archivo a "model_conexion.php" y completar con tus credenciales reales.
// El archivo "model_conexion.php" está ignorado por git (ver .gitignore) para no exponer credenciales.
class conexionBD {
    private $pdo;

    public function conexionPDO() {
        $host       = "localhost";
        $puerto     = 3306;
        $usuario    = "tu_usuario";
        $contrasena = "tu_contraseña";
        $bdName     = "colegio";
        $this->pdo = null;

        try {
            $this->pdo = new PDO("mysql:host=$host;port=$puerto;dbname=$bdName", $usuario, $contrasena);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
            return $this->pdo;
        } catch (PDOException $e) {
            echo 'Falló la conexión: ' . $e->getMessage();
        }
    }

    public function cerrar_conexion() {
        $this->pdo = null;
    }
}
?>
