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
                    // El hash de la contraseña nunca sale del servidor
                    unset($resp['usu_contra'], $resp[6]);
                    $arreglo[]=$resp;
                }
            }
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        public function Login_Bloqueado($usu,$max,$minutos){
            $c = conexionBD::conexionPDO();
            $sql = "SELECT COUNT(*) FROM login_intentos WHERE usuario = ? AND fecha > (NOW() - INTERVAL ? MINUTE)";
            $query = $c->prepare($sql);
            $query->bindValue(1,$usu);
            $query->bindValue(2,(int)$minutos,PDO::PARAM_INT);
            $query->execute();
            $total = (int)$query->fetchColumn();
            conexionBD::cerrar_conexion();
            return $total >= $max;
        }
        public function Registrar_Intento_Fallido($usu,$ip){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("INSERT INTO login_intentos(usuario,ip,fecha) VALUES(?,?,NOW())");
            $query->execute([$usu,$ip]);
            conexionBD::cerrar_conexion();
        }
        public function Limpiar_Intentos($usu){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("DELETE FROM login_intentos WHERE usuario = ?");
            $query->execute([$usu]);
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
            // 1 actualizado, 2 el nombre de usuario ya lo usa otra cuenta, 0 vacío
            $query->execute();
            $r = (int) $query->fetchColumn();
            conexionBD::cerrar_conexion();
            return $r;
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
         
        public function Cargar_datos_usuario($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_CARGAR_DATOS_USUARIO(?)";
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
        public function listar_total_estudiantes(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_ESTUDIANTES()";
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
        public function listar_total_docentes(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_DOCENTES()";
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
        public function listar_total_administrativos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_ADMINISTRATIVOS()";
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
        public function listar_total_usuarios(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_USUARIOS()";
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
        public function Listar_usuarios_filtro($idrol){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_USUARIOS_FILTRO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$idrol);


            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function listar_total_ingresos_hoy(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_INGRESOS_HOY()";
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
        public function listar_total_egresos_hoy(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_EGRESOS_HOY()";
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
        public function listar_total_psicologia(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_PSICOLOGIA()";
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
        public function listar_total_enfermeria(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_ENFERMERIA()";
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
    }




?>