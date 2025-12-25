<?php
    require_once 'model_conexion.php';

    class Modelo_Asistencia extends conexionBD{
        

        public function Listar_asitencia(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ASISTENCIA()";
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
        public function Listar_Asistencias_Fecha($fechainicio,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ASISTENCIAS_FECHAS(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fechainicio);
            $query->bindParam(2,$fechafin);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Nivel(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_NIVELACA()";
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
        public function Cargar_Aula($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_AULA_ID(?)";
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

        public function Listar_alumnos_asistencia($grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS_GRADO_ANIO(?)";
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
        public function Listar_alumnos_asistencia2($fecha,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS_FECHA(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fecha);
            $query->bindParam(2,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }

        public function Listar_alumnos_totales($año,$mes,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS_TOTALES(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$mes);
            $query->bindParam(3,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_alumnos_totales_estu($id,$año,$mes,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS_TOTALES_ESTUDIANTE(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$año);
            $query->bindParam(3,$mes);
            $query->bindParam(4,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_alumnos_totales_dia($año,$mes,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS_TOTALES_DIA(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$mes);
            $query->bindParam(3,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_alumnos_totales_dia_estudiante($id,$año,$mes,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS_TOTALES_DIA_ESTUDIANTE(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$año);
            $query->bindParam(3,$mes);
            $query->bindParam(4,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Registrar_Asistencias($id_matri, $fecha, $esta, $obse) {
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_ASISTENCIA(?,?,?,?)";
            $query  = $c->prepare($sql);
            
            // Vinculación de parámetros
            $query->bindParam(1, $id_matri);
            $query->bindParam(2, $fecha);
            $query->bindParam(3, $esta);
            $query->bindParam(4, $obse);
        
            try {
                // Ejecutar consulta
                $query->execute();
        
                // Obtener resultado
                if ($row = $query->fetchColumn()) {
                    return $row;  // Devolver el resultado del procedimiento almacenado
                }
            } catch (PDOException $e) {
                // Manejar errores
                return 0; // Enviar un valor que indique error
            } finally {
                // Asegurar que la conexión se cierra
                conexionBD::cerrar_conexion();
            }
        }
        
       
        public function Editar_Asistencia($id_asis, $fecha, $esta, $obse){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ACTUALIZAR_ASISTENCIA(?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id_asis);
            $query ->bindParam(2,$fecha);
            $query ->bindParam(3,$esta);
            $query ->bindParam(4,$obse);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }        
        public function Eliminar_Asistencia($fecha,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_ASISTENCIA_POR_FECHA_Y_AULA(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$fecha);
            $query ->bindParam(2,$aula);

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