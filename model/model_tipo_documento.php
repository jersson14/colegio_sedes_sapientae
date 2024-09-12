<?php
    require_once 'model_conexion.php';

    class Modelo_Tipo_Documento extends conexionBD{
        

        public function Listar_Tipo_Documento(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TIPO_DOCUMENTO()";
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
         public function Registrar_Tipo_Doc($tipodoc,$requisi){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_TIPO_DOCUMENTO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$tipodoc);
            $query ->bindParam(2,$requisi);
            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Tipo_Documento($id,$tipo,$esta,$requisi){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_TIPO_DOCUMENTO(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$tipo);
            $query ->bindParam(3,$esta);
            $query ->bindParam(4,$requisi);
            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
    }




?>