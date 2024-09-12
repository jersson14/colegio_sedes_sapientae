<?php
    require_once 'model_conexion.php';

    class Modelo_Periodos extends conexionBD{
        

        public function Listar_Periodos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PERIODOS()";
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
             public function Cargar_Id_Año($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTAR_PERIODOS_POR_AÑO(?)";
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
        public function Registrar_Periodo($anio, $tipoPeriodo, $periodo, $fechaInicio, $fechaFin) {
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_PERIODO(?,?,?,?,?)"; // Procedimiento almacenado para registrar un componente
            $query = $c->prepare($sql);
            $query->bindParam(1, $anio);
            $query->bindParam(2, $tipoPeriodo);
            $query->bindParam(3, $periodo);
            $query->bindParam(4, $fechaInicio);
            $query->bindParam(5, $fechaFin);
            $query->execute();
            $resultado = $query->fetchColumn();
            
            conexionBD::cerrar_conexion(); // Cerramos la conexión después de la ejecución
            return $resultado; // Devuelve el resultado del procedimiento almacenado (1: registrado, 0: ya existe)
        }
        public function Modificar_Periodo($anio, $tipoPeriodo, $periodo, $fechaInicio, $fechaFin) {
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_PERIODO(?,?,?,?,?)"; // Procedimiento almacenado para registrar un periodo
            $query = $c->prepare($sql);
        
            // Convertir fechas a formato YYYY-MM-DD si es necesario
            $fechaInicio = date('Y-m-d', strtotime($fechaInicio));
            $fechaFin = date('Y-m-d', strtotime($fechaFin));
        
            $query->bindParam(1, $anio, PDO::PARAM_INT);
            $query->bindParam(2, $tipoPeriodo, PDO::PARAM_STR);
            $query->bindParam(3, $periodo, PDO::PARAM_STR);
            $query->bindParam(4, $fechaInicio, PDO::PARAM_STR);
            $query->bindParam(5, $fechaFin, PDO::PARAM_STR);
            
            $query->execute();
            $resultado = $query->fetchColumn();
            
            conexionBD::cerrar_conexion(); // Cerramos la conexión después de la ejecución
            return $resultado; // Devuelve el resultado del procedimiento almacenado (1: registrado, 2: ya existe, 0: error)
        }
        
        
        
        
        public function Eliminar_periodo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_PERIODO(?)";
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
        public function Eliminar_periodo_unico($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_PERIODO_UNICO(?)";
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