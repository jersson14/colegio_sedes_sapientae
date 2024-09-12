<?php
    require_once 'model_conexion.php';

    class Modelo_Horario extends conexionBD{
        

        public function Listar_Horario(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_HORA()";
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
        public function TraerDatos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_TRAER_DATOS()";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function TraerHora($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_TRAER_DATOS2(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Horario($id,$horai,$horaf){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_HORARIO(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$horai);
            $query ->bindParam(3,$horaf);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
     
    }




?>