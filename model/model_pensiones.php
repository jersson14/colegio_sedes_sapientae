<?php
    require_once 'model_conexion.php';

    class Modelo_Pensiones extends conexionBD{
        

        public function Listar_Pensiones(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PENSIONES()";
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
        public function Listar_pensiones_filtros($nivel){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PENSIONES_FILTRO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$nivel);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Nivel(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_NIVELACA()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        // Registro, cambios y baja: src/Services/GestionarPensiones.php.
    }




?>