<?php
    require_once 'model_conexion.php';

    class Modelo_Notas extends conexionBD{
        


        public function Listar_alumnos_notas($año,$grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_NOTAS_AULA_AÑO(?,?)";
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
        public function Listar_alumnos_notas_profesor($año,$grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_NOTAS_AULA_AÑO_PROFESOR(?,?)";
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
        public function Listar_alumnos_notas_alumnos($año,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_NOTAS_AULA_AÑO_ALUMNOS(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_aulas_por_docente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_AULAS_POR_DOCENTE(?)";
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

        public function Cargar_año_por_estudiante($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_AÑO_POR_ESTUDIANTE(?)";
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
        public function Cargar_Periodos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_PERIODO()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Periodos2(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_PERIODO2()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Periodos_cargados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_PERIODO_CARGADOS()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_bimestres_profesor($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_BIMESTRE_DOCENTE(?)";
            $arreglo = [];
            $query  = $c->prepare($sql);
            $query->bindParam(1, $id);
            $query->execute();
            $resultado = $query->fetchAll();
        
     
        
            // Si hay resultados, se agregan al arreglo
            foreach($resultado as $resp){
                $arreglo[] = $resp;
            }
        
            conexionBD::cerrar_conexion();
        
            // Devuelve el arreglo (siempre retornará un array, aunque esté vacío)
            return $arreglo;
        }
        public function Cargar_bimestres_estudiante($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_BIMESTRE_ESTUDIANTE(?)";
            $query  = $c->prepare($sql);
            $query->bindParam(1, $id);
            $query->execute();
            $resultado = $query->fetchAll();
        
            // Inicializa la variable $arreglo como un array vacío
            $arreglo = [];
        
            // Si hay resultados, se agregan al arreglo
            foreach($resultado as $resp){
                $arreglo[] = $resp;
            }
        
            conexionBD::cerrar_conexion();
        
            // Devuelve el arreglo (siempre retornará un array, aunque esté vacío)
            return $arreglo;
        }
        public function Listar_criterios_nota($nivel,$aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_CRITERIOS_NOTA(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$nivel);
            $query->bindParam(2,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_criterios_nota_profesor($nivel,$aula,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_CRITERIOS_NOTA_DOCENTE(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$nivel);
            $query->bindParam(2,$aula);
            $query->bindParam(3,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_criterios_nota_mostrar($matri,$bime){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_CRITERIOS_NOTA_MOSTRAR(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$matri);
            $query->bindParam(2,$bime);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_criterios_nota_mostrar_estudiante($matri,$bime,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_ESTUDIANTE(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$matri);
            $query->bindParam(2,$bime);
            $query->bindParam(3,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_criterios_nota_mostrar_profesor($matri,$bime,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_PROFESOR(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$matri);
            $query->bindParam(2,$bime);
            $query->bindParam(3,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }

        public function Listar_criterios_nota_mostrar_padres($matri,$bime){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_PADRES(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$matri);
            $query->bindParam(2,$bime);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
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
        // Registro y edición de notas (del alumno y de los padres): src/Services/GestionarNotas.php.
    }




?>