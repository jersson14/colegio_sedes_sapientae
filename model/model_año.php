<?php
    require_once 'model_conexion.php';

    class Modelo_Años extends conexionBD{
        

        public function Listar_Años(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_AÑOS()";
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
        public function Registrar_Años($año,$nombre,$inicio,$fin,$descrip){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_AÑOS(?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$año);
            $query ->bindParam(2,$nombre);
            $query ->bindParam(3,$inicio);
            $query ->bindParam(4,$fin);
            $query ->bindParam(5,$descrip);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Años($id,$año,$nombre,$inicio,$fin,$descrip,$esta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_AÑO(?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$año);
            $query ->bindParam(3,$nombre);
            $query ->bindParam(4,$inicio);
            $query ->bindParam(5,$fin);
            $query ->bindParam(6,$descrip);
            $query ->bindParam(7,$esta);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Año($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_AÑO(?)";
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