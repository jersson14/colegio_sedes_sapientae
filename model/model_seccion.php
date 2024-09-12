<?php
    require_once 'model_conexion.php';

    class Modelo_Secciones extends conexionBD{
        

        public function Listar_Secciones(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_SECCIONES()";
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
        public function Registrar_Seccion($seccion,$descrip){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_SECCION(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$seccion);
            $query ->bindParam(2,$descrip);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Seccion($id,$seccion,$descrip,$esta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_SECCION(?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$seccion);
            $query ->bindParam(3,$descrip);
            $query ->bindParam(4,$esta);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Seccion($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_SECCION(?)";
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