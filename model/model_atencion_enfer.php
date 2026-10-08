<?php
    require_once 'model_conexion.php';

    class Modelo_Atencion_Enfer extends conexionBD{
        

        public function Listar_Atencion_Enfermeria(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ATENCION_ENFERME()";
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
        public function Listar_atenciones_filtros_enfermeria($grado,$fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ATENCIONES_ENFERMERIA_FILTRO(?,?,?)";
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
    
       
        // Registro y edición: src/Services/GestionarBienestar.php.
    }




?>