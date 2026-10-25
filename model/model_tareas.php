<?php
    require_once 'model_conexion.php';

    class Modelo_Tareas extends conexionBD{
        

        public function Listar_tareas(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS()";
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
        public function Listar_tareas_filtros($aula,$fechainicio,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS_FILTRO(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$aula);
            $query->bindParam(2,$fechainicio);
            $query->bindParam(3,$fechafin);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }

        public function Listar_alumnos_tareas($año,$grado,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS_PROFESOR(?,?,?)";
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
        public function Listar_alumnos_tareas_id($año,$grado,$curso,$id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS_ESTUDIANTES(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$grado);
            $query->bindParam(3,$curso);
            $query->bindParam(4,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_alumnos_tareas_id_solo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS_ESTUDIANTES_SOLO(?)";
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
        public function Listar_alumnos_tareas_solo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS_PROFESOR_ID(?)";
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
        public function Listar_alumnos_tareas_id_solo_pendiente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TAREAS_ESTUDIANTES_ID_PENDIENTE(?)";
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
        public function Cargar_Id_Detalle_profesor($id,$idpro){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ID_DETALLE_PROFESOR(?,?)";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$idpro);

            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }

        public function Cargar_Id_Detalle_estudiante($id,$idestu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ID_DETALLE_ESTUDIANTE(?,?)";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$idestu);

            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
       
        public function Cargar_aulas_por_estudiante($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_AULAS_POR_ESTUDIANTE(?)";
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
        public function Cargar_CursoDocente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_CURSO_DOCENTE(?)";
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
        
        public function Registrar_Tarea($asig, $tema, $fecha, $descrip, $carpeta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_TAREA(?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$asig);
            $query ->bindParam(2,$tema);
            $query ->bindParam(3,$fecha);
            $query ->bindParam(4,$descrip);
            $query ->bindParam(5,$carpeta);


            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Tarea($id, $asig, $tema, $fecha, $descrip, $ruta_carpeta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_TAREAS(?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$asig);
            $query ->bindParam(3,$tema);
            $query ->bindParam(4,$fecha);
            $query ->bindParam(5,$descrip);
            $query ->bindParam(6,$ruta_carpeta);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        // El curso (detalle_asignatura_docente) está asignado al docente con ese usuario
        public function Curso_Es_Del_Docente($iddetalle, $usu_id){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("SELECT 1 FROM detalle_asignatura_docente dd
                                  INNER JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                                  INNER JOIN docentes d ON d.Id_docente = ad.Id_docente
                                  WHERE dd.Id_detalle_asig_docente = ? AND d.id_asusuario = ? LIMIT 1");
            $query->execute([$iddetalle, $usu_id]);
            return (bool) $query->fetchColumn();
        }
        // La tarea es de un curso del docente con ese usuario
        public function Tarea_Es_Del_Docente($idtarea, $usu_id){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("SELECT id_detalle_asignatura FROM tareas WHERE id_tarea = ?");
            $query->execute([$idtarea]);
            $iddetalle = $query->fetchColumn();
            return $iddetalle !== false && $this->Curso_Es_Del_Docente($iddetalle, $usu_id);
        }
        // El envío (detalle_tarea) es de una tarea del docente con ese usuario
        public function Envio_Es_Del_Docente($iddetalle, $usu_id){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("SELECT id_tarea FROM detalle_tarea WHERE id_detalle_tarea = ?");
            $query->execute([$iddetalle]);
            $idtarea = $query->fetchColumn();
            return $idtarea !== false && $this->Tarea_Es_Del_Docente($idtarea, $usu_id);
        }
        // Carpeta guardada de la tarea ('' si no existe)
        public function Carpeta_Tarea($idtarea){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("SELECT IFNULL(archivo_tarea,'') FROM tareas WHERE id_tarea = ?");
            $query->execute([$idtarea]);
            return (string) $query->fetchColumn();
        }
        // Enlaces de video de la tarea (JSON en tareas.enlaces_video), por la carpeta de la tarea
        public function Guardar_Enlaces_Video($carpeta, $enlaces_json){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("UPDATE tareas SET enlaces_video = ? WHERE archivo_tarea = ?");
            return $query->execute([$enlaces_json, $carpeta]) ? 1 : 0;
        }
        public function Enlaces_Video($carpeta){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("SELECT enlaces_video FROM tareas WHERE archivo_tarea = ? LIMIT 1");
            $query->execute([$carpeta]);
            return (string) $query->fetchColumn();
        }
        // Devuelve la carpeta enviada actualmente si el detalle es del usuario; null si no le pertenece
        public function Detalle_Tarea_Propio($iddetalle,$usu_id){
            $c = conexionBD::conexionPDO();
            $sql = "SELECT IFNULL(d.archivo_evnio_tarea,'') FROM detalle_tarea d
                    INNER JOIN matricula m ON d.id_matriculado = m.id_matricula
                    WHERE d.id_detalle_tarea = ? AND m.usu_id = ?";
            $query = $c->prepare($sql);
            $query->execute([$iddetalle,$usu_id]);
            $archivo = $query->fetchColumn();
            conexionBD::cerrar_conexion();
            return $archivo === false ? null : $archivo;
        }
        public function Registrar_envio_tarea($iddetalle,$ruta_carpeta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ENVIAR_TAREA(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$iddetalle);
            $query ->bindParam(2,$ruta_carpeta);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_envio_tarea($iddetalle,$ruta_carpeta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_ENVIAR_TAREA(?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$iddetalle);
            $query ->bindParam(2,$ruta_carpeta);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Tareas($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_TAREA(?)";
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
        public function Listar_tareas_enviadas($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTAR_TAREAS_ENVIADAS(?)";
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
        public function Modificar_Tarea_Estatus($id,$estatus){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_TAREA_ESTATUS(?,?)";
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
    }




?>