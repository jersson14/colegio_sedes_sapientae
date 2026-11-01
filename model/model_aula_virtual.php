<?php
require_once 'model_conexion.php';

/**
 * Calendario de horarios y aula virtual (Fase 13, después del paso 16).
 * Un "curso" es un registro de detalle_asignatura_docente: una asignatura de un aula con su docente en un año.
 */
class Modelo_Aula_Virtual extends conexionBD {

    const CURSO_SQL = "SELECT dd.Id_detalle_asig_docente AS id, asg.nombre_asig AS asignatura, a.Id_aula AS aula,
                              a.Grado AS grado, s.seccion_nombre AS seccion, n.Nivel_academico AS nivel,
                              CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente, d.docente_fotoperfil AS foto,
                              d.id_asusuario AS usu_docente, ad.`id_año` AS anio
                       FROM detalle_asignatura_docente dd
                       JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                       JOIN docentes d ON d.Id_docente = ad.Id_docente
                       JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                       JOIN aulas a ON a.Id_aula = asg.Id_grado
                       JOIN seccion s ON s.seccion_id = a.id_seccion
                       JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico";

    /** Matrícula del estudiante (por su usuario) en el año: [matricula, aula] o null. */
    public function Matricula_Estudiante($usu, $anio) {
        $q = conexionBD::conexionPDO()->prepare("SELECT id_matricula AS matricula, id_aula AS aula FROM matricula
                                                 WHERE usu_id = ? AND `id_año` = ? ORDER BY id_matricula DESC LIMIT 1");
        $q->execute([$usu, $anio]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aulas con horas de clase o estudiantes en el año; el docente, solo donde enseña. */
    public function Aulas($anio, $usu_docente = null) {
        $sql = "SELECT a.Id_aula AS id, a.Grado AS grado, s.seccion_nombre AS seccion, n.Nivel_academico AS nivel
                FROM aulas a
                JOIN seccion s ON s.seccion_id = a.id_seccion
                JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                WHERE (a.Id_aula IN (SELECT id_aula FROM horas_aula WHERE `id_año_academico` = ? AND estado = 'ACTIVO')
                       OR a.Id_aula IN (SELECT id_aula FROM matricula WHERE `id_año` = ?))";
        $p = [$anio, $anio];
        if ($usu_docente !== null) {
            $sql .= " AND a.Id_aula IN (SELECT asg.Id_grado FROM detalle_asignatura_docente dd
                        JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                        JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                        JOIN docentes d ON d.Id_docente = ad.Id_docente
                        WHERE d.id_asusuario = ? AND ad.`id_año` = ?)";
            $p[] = $usu_docente;
            $p[] = $anio;
        }
        $q = conexionBD::conexionPDO()->prepare($sql . " ORDER BY n.Id_nivel, a.Id_aula");
        $q->execute($p);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Clases de la semana: de un aula, de un docente (todas sus aulas) o de un curso. */
    public function Clases($anio, array $filtro) {
        $sql = "SELECT h.id_horario AS id, h.dia, TIME_FORMAT(ha.hora_inicio, '%H:%i') AS inicio, TIME_FORMAT(ha.hora_fin, '%H:%i') AS fin,
                       dd.Id_detalle_asig_docente AS curso, asg.nombre_asig AS asignatura,
                       CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente,
                       a.Id_aula AS aula, a.Grado AS grado, s.seccion_nombre AS seccion, n.Nivel_academico AS nivel
                FROM horarios h
                JOIN horas_aula ha ON ha.id_hora = h.id_hora_aula
                JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = h.id_detalle_asig_docente
                JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                JOIN docentes d ON d.Id_docente = ad.Id_docente
                JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                JOIN aulas a ON a.Id_aula = ha.id_aula
                JOIN seccion s ON s.seccion_id = a.id_seccion
                JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                WHERE h.estado = 'ACTIVO' AND ha.estado = 'ACTIVO' AND ha.`id_año_academico` = ?";
        $p = [$anio];
        if (!empty($filtro['aula'])) { $sql .= " AND ha.id_aula = ?"; $p[] = $filtro['aula']; }
        if (!empty($filtro['docente'])) { $sql .= " AND d.id_asusuario = ?"; $p[] = $filtro['docente']; }
        if (!empty($filtro['curso'])) { $sql .= " AND dd.Id_detalle_asig_docente = ?"; $p[] = $filtro['curso']; }
        $q = conexionBD::conexionPDO()->prepare($sql . " ORDER BY FIELD(h.dia,'LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO'), ha.hora_inicio");
        $q->execute($p);
        $dias = ['LUNES' => 1, 'MARTES' => 2, 'MIERCOLES' => 3, 'JUEVES' => 4, 'VIERNES' => 5, 'SABADO' => 6];
        return array_map(function ($c) use ($dias) { $c['dia'] = $dias[$c['dia']] ?? 1; return $c; }, $q->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Cursos del aula virtual. $quien: ['aula' => id] | ['docente' => usu_id]; con matrícula, cuenta las tareas del estudiante. */
    public function Cursos($anio, array $quien, $matricula = null) {
        $sql = self::CURSO_SQL . " WHERE ad.`id_año` = ?";
        $p = [$anio];
        if (!empty($quien['aula'])) { $sql .= " AND a.Id_aula = ?"; $p[] = $quien['aula']; }
        if (!empty($quien['docente'])) { $sql .= " AND d.id_asusuario = ?"; $p[] = $quien['docente']; }
        $q = conexionBD::conexionPDO()->prepare($sql . " ORDER BY n.Id_nivel, a.Id_aula, asg.nombre_asig");
        $q->execute($p);
        $cursos = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cursos as &$c) { $c += $this->Conteos($c['id'], $matricula); }
        return $cursos;
    }

    /** Números de la tarjeta: para el estudiante, por entregar y vencidas; para el docente, por revisar y total de tareas. */
    private function Conteos($curso, $matricula) {
        $c = conexionBD::conexionPDO();
        if ($matricula) {
            $q = $c->prepare("SELECT SUM(dt.estado = 'PENDIENTE' AND t.estado = 'PENDIENTE' AND t.fecha_entrega >= NOW()) AS por_entregar,
                                     SUM(dt.estado = 'PENDIENTE' AND (t.estado = 'FINALIZADO' OR t.fecha_entrega < NOW())) AS vencidas,
                                     COUNT(*) AS tareas
                              FROM tareas t JOIN detalle_tarea dt ON dt.id_tarea = t.id_tarea AND dt.id_matriculado = ?
                              WHERE t.id_detalle_asignatura = ?");
            $q->execute([$matricula, $curso]);
        } else {
            $q = $c->prepare("SELECT (SELECT COUNT(*) FROM tareas t WHERE t.id_detalle_asignatura = ?) AS tareas,
                                     (SELECT COUNT(*) FROM tareas t JOIN detalle_tarea dt ON dt.id_tarea = t.id_tarea
                                      WHERE t.id_detalle_asignatura = ? AND dt.estado = 'ENVIADO') AS por_revisar");
            $q->execute([$curso, $curso]);
        }
        return array_map('intval', $q->fetch(PDO::FETCH_ASSOC) ?: []);
    }

    public function Curso($id) {
        $q = conexionBD::conexionPDO()->prepare(self::CURSO_SQL . " WHERE dd.Id_detalle_asig_docente = ?");
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Tareas del curso, la más nueva primero; con matrícula trae la entrega del estudiante, sin ella los conteos de entregas. */
    public function Tareas($curso, $matricula = null) {
        $c = conexionBD::conexionPDO();
        if ($matricula) {
            $q = $c->prepare("SELECT t.id_tarea AS id, t.tema, t.descripcion, t.created_at AS publicada, t.fecha_entrega, t.estado,
                                     t.archivo_tarea AS material, t.enlaces_video AS videos,
                                     dt.id_detalle_tarea AS envio, dt.estado AS envio_estado, dt.archivo_evnio_tarea AS envio_archivo,
                                     dt.calificacion, dt.observacion, dt.fecha_envio
                              FROM tareas t
                              LEFT JOIN detalle_tarea dt ON dt.id_tarea = t.id_tarea AND dt.id_matriculado = ?
                              WHERE t.id_detalle_asignatura = ?
                              ORDER BY t.created_at DESC, t.id_tarea DESC");
            $q->execute([$matricula, $curso]);
        } else {
            $q = $c->prepare("SELECT t.id_tarea AS id, t.tema, t.descripcion, t.created_at AS publicada, t.fecha_entrega, t.estado,
                                     t.archivo_tarea AS material, t.enlaces_video AS videos,
                                     SUM(dt.estado = 'ENVIADO') AS entregadas, SUM(dt.estado = 'CALIFICADO') AS calificadas, COUNT(dt.id_detalle_tarea) AS asignadas
                              FROM tareas t
                              LEFT JOIN detalle_tarea dt ON dt.id_tarea = t.id_tarea
                              WHERE t.id_detalle_asignatura = ?
                              GROUP BY t.id_tarea
                              ORDER BY t.created_at DESC, t.id_tarea DESC");
            $q->execute([$curso]);
        }
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Estudiantes del aula del curso en su año (solo nombres). */
    public function Estudiantes($aula, $anio) {
        $q = conexionBD::conexionPDO()->prepare("SELECT al.alum_nombre AS nombres, CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos
                                                 FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno
                                                 WHERE m.id_aula = ? AND m.`id_año` = ?
                                                 ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre");
        $q->execute([$aula, $anio]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
