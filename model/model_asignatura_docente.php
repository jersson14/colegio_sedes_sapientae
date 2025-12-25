<?php
    require_once 'model_conexion.php';

    class Modelo_Asignatura_Docente extends conexionBD{
        

        public function Listar_Asignatura_Docente(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL LISTAR_ASIGNATURA_DOCENTE()";
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
        public function Listar_asignatura_docente_filtro($grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ADIGNDOCENTE_FILTRO(?)";
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
        public function Cargar_Select_Docente(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_DOCENTE()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Asignatura($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ASIGNATURA(?)";
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
        public function Registrar_asignatura_docente($añoaca,$id_docente){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_ASIGNATURA_DOCENTE(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$añoaca);
            $query ->bindParam(2,$id_docente);

            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();

        }
        function Registrar_detalle_AsignDocente($id,$array_asignatura){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_DETALLE_ASIGNA_DOCENTE(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$array_asignatura);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
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
        public function Eliminar_asignatura_docente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_ASIGNACION_DOCENTE(?)";
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
        public function Eliminar_asignatura_docente_solo_uno($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_ASIGNACION_DOCENTE_UNICO(?)";
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
        public function Listar_cursos_docente($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTAR_CURSOS_DOCENTE(?)";
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
        public function Modificar_asig_docente_unico($id,$curso){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_ASIG_DOCENTE_UNICO(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$curso);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
    }




?>