<?php
    require_once 'model_conexion.php';

    class Modelo_Asignaturas extends conexionBD{
        

        public function Listar_Asignaturas(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ASIGNATURAS()";
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
        public function Listar_asignaturas_filtro($grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ASIGNATURA_FILTRO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$grado);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Grados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_GRADO()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        // Registro, cambios y baja de asignaturas: src/Services/GestionarHorarios.php.
    }




?>