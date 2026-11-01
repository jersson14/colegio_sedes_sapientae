<?php
require_once 'model_conexion.php';

/**
 * Pantallas propias del estudiante: Mis tareas, Mis notas, Mis pagos y Mi asistencia.
 * Todo parte de la matrícula del estudiante en sesión: nunca de un id que mande el navegador.
 */
class Modelo_Estudiante extends conexionBD {

    /** Años en que el estudiante tiene matrícula, el más reciente primero, con su aula. */
    public function Matriculas($usu) {
        $q = conexionBD::conexionPDO()->prepare("SELECT m.id_matricula AS matricula, m.`id_año` AS anio_id, ae.`año_escolar` AS anio, ae.estado,
                                                        ae.fecha_inicio, ae.fecha_fin, a.Id_aula AS aula, a.Grado AS grado,
                                                        s.seccion_nombre AS seccion, n.Nivel_academico AS nivel
                                                 FROM matricula m
                                                 JOIN `año_escolar` ae ON ae.`Id_año_escolar` = m.`id_año`
                                                 JOIN aulas a ON a.Id_aula = m.id_aula
                                                 JOIN seccion s ON s.seccion_id = a.id_seccion
                                                 JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                                                 WHERE m.usu_id = ?
                                                 ORDER BY ae.`año_escolar` DESC, m.id_matricula DESC");
        $q->execute([$usu]);
        $res = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (!isset($res[$r['anio_id']])) { $res[$r['anio_id']] = $r; }   // una por año
        }
        return array_values($res);
    }

    /** La matrícula del año pedido; si no se pide, la del año activo o la más reciente. null si no tiene ninguna. */
    public function Elegir(array $matriculas, $anio_id) {
        foreach ($matriculas as $m) {
            if ((int) $m['anio_id'] === (int) $anio_id) { return $m; }
        }
        foreach ($matriculas as $m) {
            if ($m['estado'] === 'ACTIVO') { return $m; }
        }
        return $matriculas[0] ?? null;
    }

    /** Todas las tareas de los cursos del aula, con la entrega del estudiante. */
    public function Tareas($matricula, $aula, $anio_id) {
        $c = conexionBD::conexionPDO();
        // Las tareas publicadas antes de su matrícula también le corresponden (como en el Registro de tareas)
        $c->prepare("INSERT INTO detalle_tarea(id_tarea, id_matriculado, archivo_evnio_tarea, calificacion, observacion, estado, fecha_envio, created_at, updated_at)
                     SELECT t.id_tarea, ?, '', NULL, '', 'PENDIENTE', NULL, NOW(), NULL
                     FROM tareas t
                     JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                     JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                     JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                     WHERE asg.Id_grado = ? AND ad.`id_año` = ? AND t.estado = 'PENDIENTE'
                       AND NOT EXISTS (SELECT 1 FROM detalle_tarea x WHERE x.id_tarea = t.id_tarea AND x.id_matriculado = ?)")
          ->execute([$matricula, $aula, $anio_id, $matricula]);
        $q = $c->prepare("SELECT t.id_tarea AS id, t.tema, t.descripcion, t.created_at AS publicada, t.fecha_entrega, t.estado,
                                 t.archivo_tarea AS material, t.enlaces_video AS videos,
                                 dt.id_detalle_tarea AS envio, dt.estado AS envio_estado, dt.archivo_evnio_tarea AS envio_archivo,
                                 dt.calificacion, dt.observacion, dt.fecha_envio,
                                 dd.Id_detalle_asig_docente AS curso, asg.nombre_asig AS asignatura,
                                 CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente
                          FROM detalle_tarea dt
                          JOIN tareas t ON t.id_tarea = dt.id_tarea
                          JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                          JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                          JOIN docentes d ON d.Id_docente = ad.Id_docente
                          JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                          WHERE dt.id_matriculado = ?
                          ORDER BY t.fecha_entrega DESC, t.id_tarea DESC");
        $q->execute([$matricula]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Pensiones del año con su estado (vista vw_estado_pensiones). */
    public function Pensiones($matricula) {
        $q = conexionBD::conexionPDO()->prepare("SELECT id_pension AS id, mes, mes_num, fecha_vencimiento AS vence, precio, mora,
                                                        pagado_pension, pagado_mora, exonerada, vencida, saldo_pension, saldo_mora
                                                 FROM vw_estado_pensiones WHERE id_matricula = ? ORDER BY mes_num, id_pension");
        $q->execute([$matricula]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Días con asistencia registrada: fecha, código P/T/F/J y observación. */
    public function Asistencia($matricula) {
        $q = conexionBD::conexionPDO()->prepare("SELECT fecha,
                                                        CASE estado WHEN 'PRESENTE' THEN 'P' WHEN 'TARDE' THEN 'T' WHEN 'AUSENTE' THEN 'F' ELSE 'J' END AS codigo,
                                                        observacion
                                                 FROM asistencia WHERE id_matricula = ? ORDER BY fecha");
        $q->execute([$matricula]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
