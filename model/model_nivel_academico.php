<?php
    require_once 'model_conexion.php';

    class Modelo_Nivel_Academico extends conexionBD{
        

        public function Listar_Nivel_Academico(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_NIVEL_ACADEMICO()";
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
        public function Registrar_Nivel_Academic($nivel,$descrip){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_NIVEL_ACADEMICO(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$nivel);
            $query ->bindParam(2,$descrip);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Nivel_Academico($id,$nivel,$descrip,$esta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_NIVEL_ACADEMICO(?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$nivel);
            $query ->bindParam(3,$descrip);
            $query ->bindParam(4,$esta);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Nivel_Academico($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_NIVEL_ACADEMICO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
    
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