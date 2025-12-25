<?php
    require_once 'model_conexion.php';

    class Modelo_Asignaturas extends conexionBD{
        

        public function Listar_Asignaturas(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ASIGNATURAS()";
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
        public function Listar_asignaturas_filtro($grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ASIGNATURA_FILTRO(?)";
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
        public function Cargar_Select_Grados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_GRADO()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
       
        public function Registrar_Asignaturas($asigna,$grado,$obse){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_ASIGNATURAS(?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$asigna);
            $query ->bindParam(2,$grado);
            $query ->bindParam(3,$obse);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Asignaturas($id,$asigna,$grado,$observa){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_ASIGNATURA(?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$asigna);
            $query ->bindParam(3,$grado);
            $query ->bindParam(4,$observa);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Asignatura($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL ELIMINAR_ASIGNATURA(?)";
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