<?php
    require_once 'model_conexion.php';

    class Modelo_Atencion_Psico extends conexionBD{
        

        public function Listar_Atencion_Psicologica(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ATENCION_PSICO()";
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
        public function Listar_atenciones_filtros($grado,$fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ATENCIONES_PSDICOLOGICAS_FILTRO(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$grado);
            $query->bindParam(2,$fechaini);
            $query->bindParam(3,$fechafin);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Matriculados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_MATRICULADOS()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
    
       
        // Registro y edición: src/Services/GestionarBienestar.php.
    }




?>