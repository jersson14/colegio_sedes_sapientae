<?php
    require_once 'model_conexion.php';

    class Modelo_Matriculas extends conexionBD{
        

        public function Listar_Matriculados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL
             SP_LISTAR_MATRICULADOS()";
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
        public function Cargar_Año(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_AÑO()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_estudiante(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ESTUDIANTE()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_matriculados_filtro($año,$grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_MATRICULADOS_FILTRO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$grado);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function TraerNivel($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAE_NIVEL(?)";
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
        public function TraerTipo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TIPO(?)";
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
        public function Registrar_Matricula($estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa,$usu,$contra,$correo){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_MATRICULA(?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$estu);
            $query ->bindParam(2,$año);
            $query ->bindParam(3,$aula);
            $query ->bindParam(4,$admi);
            $query ->bindParam(5,$nuevo);
            $query ->bindParam(6,$matri);
            $query ->bindParam(7,$proce);
            $query ->bindParam(8,$pro);
            $query ->bindParam(9,$depa);
            $query ->bindParam(10,$usu);
            $query ->bindParam(11,$contra);
            $query ->bindParam(12,$correo);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Matricula($id,$estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_MATRICULA(?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$estu);
            $query ->bindParam(3,$año);
            $query ->bindParam(4,$aula);
            $query ->bindParam(5,$admi);
            $query ->bindParam(6,$nuevo);
            $query ->bindParam(7,$matri);
            $query ->bindParam(8,$proce);
            $query ->bindParam(9,$pro);
            $query ->bindParam(10,$depa);
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
        public function Eliminar_matricula($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_MATRICULA(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
    
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
    }




?>