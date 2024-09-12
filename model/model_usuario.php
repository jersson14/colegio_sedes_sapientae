<?php
    require_once 'model_conexion.php';

    class Modelo_Usuario extends conexionBD{
        
        public function Verificar_Usuario($usu,$con){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_VERIFICAR_USUARIO(?)";
            $arreglo = array();
            $query = $c->prepare($sql);
            $query->bindParam(1,$usu);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                if(password_verify($con,$resp['usu_contra'])){
                    $arreglo[]=$resp;
                }
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        } 
        public function Listar_Usuario(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_USUARIO()";
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
      
        public function Cargar_Select_Rol(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ROL()";
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
        public function Registrar_Usuario($usu,$con,$ide,$ida,$rol){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_USUARIO(?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$usu);
            $query ->bindParam(2,$con);
            $query ->bindParam(3,$ide);
            $query ->bindParam(4,$ida);
            $query ->bindParam(5,$rol);
            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Usuario($id,$usu,$rol,$correo){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_USUARIO(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$usu);
            $query ->bindParam(3,$rol);
            $query ->bindParam(4,$correo);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Usuario_Contra($id,$con){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_USUARIO_CONTRA(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$con);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Usuario_Estatus($id,$estatus){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_USUARIO_ESTATUS(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$estatus);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
         
        public function Cargar_Select_Datos_Seguimiento($numero,$dni){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SEGUIMIENTO_TRAMITE(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$numero);
            $query ->bindParam(2,$dni);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Traer_Datos_Detalle_Seguimiento($codigo){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SEGUIMIENTO_TRAMITE_DETALLE(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$codigo);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_notificacion_tramite($idarea){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_NOTIFICACION_TRAMITE(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$idarea);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
    }




?>