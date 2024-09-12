<?php
class conexionBD {
    private $pdo;

    public function conexionPDO() {
        $host       = "localhost";
        $usuario    = "root";
        $contrasena = "";
        $bdName     = "colegio";

        try {
            // Asignar el objeto PDO a la propiedad de la clase
            $this->pdo = new PDO("mysql:host=$host;dbname=$bdName", $usuario, $contrasena);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
            return $this->pdo;
        } catch (PDOException $e) {
            echo 'Falló la conexión: ' . $e->getMessage();
        }
    }

    public function cerrar_conexion() {
        // Correcta asignación de null para cerrar la conexión
        $this->pdo = null;
    }
}
?>
