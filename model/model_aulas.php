<?php
    require_once 'model_conexion.php';

    class Modelo_Aulas extends conexionBD{
        

        public function Listar_Aulas(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_AULAS()";
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
        public function Listar_aulas_filtro($nivel){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_AULAS_FILTRO(?)";
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
        public function Cargar_Select_Seccion(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_SECCION()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
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
        public function Registrar_Aulas($grado,$seccion,$nivel,$descrip){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_AULAS(?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$grado);
            $query ->bindParam(2,$seccion);
            $query ->bindParam(3,$nivel);
            $query ->bindParam(4,$descrip);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Aulas($id,$grado,$seccion,$nivel,$descrip,$estatus){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_AULAS(?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$grado);
            $query ->bindParam(3,$seccion);
            $query ->bindParam(4,$nivel);
            $query ->bindParam(5,$descrip);
            $query ->bindParam(6,$estatus);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Aulas($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_AULA(?)";
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