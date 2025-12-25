<?php
    require_once 'model_conexion.php';

    class Modelo_Examenes extends conexionBD{
        

        public function Listar_examenes(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EXAMENES()";
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
        public function Listar_examenes_filtro($grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EXAMENES_FILTRO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$grado);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_alumnos_examenes_id_solo_pendiente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EXAMENES_ESTUDIANTES_ID_PENDIENTE(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_CursoDocente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_CURSO_DOCENTE(?)";
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
        public function Registrar_Examenes($asig,$tema,$fecha,$descrip){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_EXAMENES(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$asig);
            $query ->bindParam(2,$tema);
            $query ->bindParam(3,$fecha);
            $query ->bindParam(4,$descrip);


            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Examenes($id,$asig,$tema,$fecha,$descrip){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_EXAMENES(?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$asig);
            $query ->bindParam(3,$tema);
            $query ->bindParam(4,$fecha);
            $query ->bindParam(5,$descrip);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Examen_Estatus($id,$estatus){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_EXAMEN_ESTATUS(?,?)";
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
        public function Eliminar_Examen($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_EXAMEN(?)";
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
        public function Listar_alumnos_examenes($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTAR_ALUMNOS_EXAMEN(?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Registrar_calificación($id,$nota,$obser){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_CALIFICACION(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);            
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$nota);
            $query ->bindParam(3,$obser);

            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Listar_examenes_profesor($año,$grado,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EXAMENES_PROFESOR(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$grado);
            $query->bindParam(3,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_examenes_profesor_solo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EXAMENES_PROFESOR_SOLO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);

            $query->bindParam(1,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
    }




?>