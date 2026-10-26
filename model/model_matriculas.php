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
            $arreglo = array();
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
            $arreglo = array();
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
        public function Registrar_Matricula($estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa,$usu,$contra,$correo,$usureg,$metodo,$cuenta,$referencia){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_MATRICULA(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
            $query ->bindParam(13,$usureg);
            $query ->bindParam(14,$metodo);
            $query ->bindParam(15,$cuenta);
            $query ->bindParam(16,$referencia);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Matricula($id,$estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa,$usureg){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_MATRICULA(?,?,?,?,?,?,?,?,?,?,?)";
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
            $query ->bindParam(11,$usureg);
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
        // Beca o descuento en las pensiones (Fase 8D)
        public function Traer_Beca($id){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("SELECT id_matricula, beca_tipo, beca_valor, beca_desde, beca_motivo FROM matricula WHERE id_matricula = ?");
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        public function Guardar_Beca($id, $tipo, $valor, $desde, $motivo){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("CALL SP_GUARDAR_BECA(?,?,?,?,?)");
            $q->execute([$id, $tipo, $valor, $desde, $motivo]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }

        /**
         * Matrícula paso a paso (Fase 13): lo que el asistente necesita para el año elegido.
         * Aulas con vacantes, pensiones de cada nivel y montos sugeridos (los de la última matrícula del nivel).
         */
        public function Contexto_Anio($anio){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("SELECT a.Id_aula AS id, n.Id_nivel AS id_nivel, n.Nivel_academico AS nivel, a.Grado AS grado, s.seccion_nombre AS seccion,
                                     a.capacidad, (SELECT COUNT(*) FROM matricula m WHERE m.id_aula = a.Id_aula AND m.`id_año` = ?) AS matriculados,
                                     FN_VACANTES_AULA(a.Id_aula, ?, 0) AS vacantes
                              FROM aulas a JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                              LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                              WHERE IFNULL(a.estado, 'ACTIVO') = 'ACTIVO'
                              ORDER BY n.Id_nivel, a.Id_aula");
            $q->execute([$anio, $anio]);
            $aulas = $q->fetchAll(PDO::FETCH_ASSOC);
            $q = $c->prepare("SELECT id_nivel_academico AS id_nivel, COUNT(*) AS cantidad, SUM(precio) AS total, MIN(precio) AS minimo, MAX(precio) AS maximo,
                                     MIN(fecha_vencimiento) AS primera
                              FROM pensiones WHERE id_anio = ? GROUP BY id_nivel_academico");
            $q->execute([$anio]);
            $pensiones = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $p) { $pensiones[$p['id_nivel']] = $p; }
            // Montos de la última matrícula de cada nivel (el pago de matrícula) y del último estudiante nuevo (admisión y alumno nuevo)
            $q = $c->prepare("SELECT a.id_nivel_academico AS id_nivel, m.pago_matricula, m.pago_admi, m.pago_alu_nuevo
                              FROM matricula m JOIN aulas a ON a.Id_aula = m.id_aula
                              WHERE m.`id_año` = ? ORDER BY m.id_matricula DESC");
            $q->execute([$anio]);
            $montos = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $n = $m['id_nivel'];
                if (!isset($montos[$n])) { $montos[$n] = ['matricula' => $m['pago_matricula'], 'admision' => null, 'nuevo' => null]; }
                if ($montos[$n]['admision'] === null && ((float) $m['pago_admi'] > 0 || (float) $m['pago_alu_nuevo'] > 0)) {
                    $montos[$n]['admision'] = $m['pago_admi'];
                    $montos[$n]['nuevo'] = $m['pago_alu_nuevo'];
                }
            }
            // Estudiantes que aún no tienen matrícula en el año (nuevos y los que vuelven de un año anterior)
            $q = $c->prepare("SELECT al.Id_alumno AS id, al.alum_dni AS documento,
                                     CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, al.alum_nombre AS nombres, al.tipo_alum AS tipo
                              FROM alumnos al
                              WHERE NOT EXISTS (SELECT 1 FROM matricula m WHERE m.id_alumno = al.Id_alumno AND m.`id_año` = ?)
                              ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre");
            $q->execute([$anio]);
            $estudiantes = $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return ['aulas' => $aulas, 'pensiones' => (object) $pensiones, 'montos' => (object) $montos, 'estudiantes' => $estudiantes];
        }

        /** Datos del estudiante para el asistente: usuario que ya tiene, última matrícula, padres y si ya está matriculado en el año. */
        public function Contexto_Estudiante($estu, $anio){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("SELECT Id_alumno AS id, alum_dni AS documento, CONCAT_WS(' ', alum_nombre, alum_apepat, alum_apemat) AS nombre, tipo_alum AS tipo
                              FROM alumnos WHERE Id_alumno = ?");
            $q->execute([$estu]);
            $al = $q->fetch(PDO::FETCH_ASSOC);
            if (!$al) { return null; }
            $q = $c->prepare("SELECT u.usu_usuario FROM matricula m JOIN usuario u ON u.usu_id = m.usu_id
                              WHERE m.id_alumno = ? ORDER BY m.id_matricula DESC LIMIT 1");
            $q->execute([$estu]);
            $al['usuario'] = $q->fetchColumn() ?: null;
            $q = $c->prepare("SELECT m.id_matricula AS id, m.`id_año` AS id_anio, ae.`año_escolar` AS anio, m.id_aula,
                                     CONCAT(n.Nivel_academico, ' - ', a.Grado, IF(s.seccion_nombre IS NOT NULL AND s.seccion_nombre <> 'UNICO', CONCAT(' ', s.seccion_nombre), '')) AS aula
                              FROM matricula m JOIN `año_escolar` ae ON ae.`Id_año_escolar` = m.`id_año`
                              JOIN aulas a ON a.Id_aula = m.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                              LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                              WHERE m.id_alumno = ? ORDER BY ae.fecha_inicio DESC, m.id_matricula DESC");
            $q->execute([$estu]);
            $mats = $q->fetchAll(PDO::FETCH_ASSOC);
            $al['en_anio'] = null;
            $al['ultima'] = null;
            foreach ($mats as $m) {
                if ((int) $m['id_anio'] === (int) $anio) { $al['en_anio'] = $m; }
                elseif (!$al['ultima']) { $al['ultima'] = $m; }
            }
            $q = $c->prepare("SELECT Datos_papa AS padre, Dni_papa AS dni_padre, Celular_papa AS cel_padre,
                                     Datos_mama AS madre, Dni_mama AS dni_madre, Celular_mama AS cel_madre
                              FROM padres WHERE id_alu = ? ORDER BY id_papas DESC LIMIT 1");
            $q->execute([$estu]);
            $al['padres'] = $q->fetch(PDO::FETCH_ASSOC) ?: null;
            conexionBD::cerrar_conexion();
            return $al;
        }
    }




?>