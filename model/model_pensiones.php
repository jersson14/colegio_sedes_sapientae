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
        public function Registrar_Pensiones($nivel,$mes,$fecha,$precio,$mora){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_PENSIONES(?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$nivel);
            $query ->bindParam(2,$mes);
            $query ->bindParam(3,$fecha);
            $query ->bindParam(4,$precio);
            $query ->bindParam(5,$mora);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Pensiones($id,$nivel,$mes,$fecha,$precio,$mora){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_PENSIONES(?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$nivel);
            $query ->bindParam(3,$mes);
            $query ->bindParam(4,$fecha);
            $query ->bindParam(5,$precio);
            $query ->bindParam(6,$mora);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Pensiones($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_PENSION(?)";
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