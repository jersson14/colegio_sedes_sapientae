<?php
    require_once 'model_conexion.php';

    class Modelo_Comunicados extends conexionBD{
        

        public function Listar_Comunicados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_COMUNICADOS()";
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
        public function Listar_comunicados_filtros($fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_COMUNICADOS_FILTRO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fechaini);
            $query->bindParam(2,$fechafin);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_Comunicados2(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_COMUNICADOS2()";
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
        public function Registrar_Comunicado($tipo,$grado,$titulo,$descripcion,$ruta,$usu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_COMUNICADOS(?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$tipo);
            $query ->bindParam(2,$grado);
            $query ->bindParam(3,$titulo);
            $query ->bindParam(4,$descripcion);
            $query ->bindParam(5,$ruta);
            $query ->bindParam(6,$usu);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Comunicado($id, $tipo, $grado, $titulo, $descripcion, $esta, $ruta,$usu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_COMUNICADO(?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$tipo);
            $query ->bindParam(3,$grado);
            $query ->bindParam(4,$titulo);
            $query ->bindParam(5,$descripcion);
            $query ->bindParam(6,$esta);
            $query ->bindParam(7,$ruta);
            $query ->bindParam(8,$usu);

       
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Comunicado($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_COMUNICADO(?)";
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