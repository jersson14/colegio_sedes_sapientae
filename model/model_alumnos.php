<?php
    require_once 'model_conexion.php';

    // Altas, cambios, bajas y foto de alumnos: src/Services/GestionarAlumnos.php.
    class Modelo_Alumnos extends conexionBD{

        public function Listar_Alumnos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS()";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
    }




?>
