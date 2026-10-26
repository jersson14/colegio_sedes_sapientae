<?php
require_once 'model_conexion.php';

/**
 * Paneles de inicio por rol (Fase 13, paso 4): "lo de hoy" para docentes, estudiantes, auxiliar y salud.
 * Todo se calcula para el usuario de la sesión (el docente ve sus cursos; el estudiante, su matrícula).
 */
class Modelo_Panel_Rol extends conexionBD {
    private $c;
    const DIAS = [1 => 'LUNES', 2 => 'MARTES', 3 => 'MIERCOLES', 4 => 'JUEVES', 5 => 'VIERNES'];

    private function uno($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetch(PDO::FETCH_ASSOC) ?: []; }
    private function todos($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetchAll(PDO::FETCH_ASSOC); }

    /** Año escolar activo, sus periodos y el día de hoy (comunes a todos los paneles). */
    public function Comun() {
        $this->c = conexionBD::conexionPDO();
        $anio = $this->uno("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, fecha_inicio, fecha_fin
                            FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1");
        return [
            'hoy' => date('Y-m-d'),
            'ahora' => date('Y-m-d H:i:s'),
            'dia' => self::DIAS[(int) date('N')] ?? null,
            'anio' => $anio ?: null,
            'periodos' => $anio ? $this->todos("SELECT periodos AS nombre, fecha_inicio AS inicio, fecha_fin AS fin FROM periodos
                                                WHERE `id_año_escolar` = ? ORDER BY fecha_inicio", [$anio['id']]) : [],
        ];
    }

    /** Comunicados activos recientes; el estudiante solo ve los generales y los de su aula. */
    private function comunicados($aula = null) {
        $filtro = $aula ? "AND (c.tipo = 'GENERAL' OR c.id_aula = ?)" : '';
        return $this->todos("SELECT c.id_comunicado AS id, c.tipo, c.titulo, c.created_at AS fecha,
                                    CONCAT(IFNULL(a.Grado, ''), IF(s.seccion_nombre IS NOT NULL AND s.seccion_nombre <> 'UNICO', CONCAT(' ', s.seccion_nombre), '')) AS grado
                             FROM comunicados c LEFT JOIN aulas a ON a.Id_aula = c.id_aula LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                             WHERE c.estado = 'ACTIVO' $filtro ORDER BY c.created_at DESC, c.id_comunicado DESC LIMIT 4", $aula ? [$aula] : []);
    }

    private function nombreAula() {
        return "CONCAT(n.Nivel_academico, ' · ', a.Grado, IF(s.seccion_nombre <> 'UNICO', CONCAT(' ', s.seccion_nombre), ''))";
    }

    public function Docente($usu, array $comun) {
        $anio = $comun['anio']['id'] ?? 0;
        $aula = $this->nombreAula();
        $cursos = "SELECT dd.Id_detalle_asig_docente FROM detalle_asignatura_docente dd
                   JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente JOIN docentes d ON d.Id_docente = ad.Id_docente
                   WHERE d.id_asusuario = ? AND ad.`id_año` = ?";
        $clases = $comun['dia'] ? $this->todos("SELECT TIME_FORMAT(ha.hora_inicio, '%H:%i') AS inicio, TIME_FORMAT(ha.hora_fin, '%H:%i') AS fin,
                                       asg.nombre_asig AS curso, $aula AS aula
                                FROM horarios h JOIN horas_aula ha ON ha.id_hora = h.id_hora_aula
                                JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = h.id_detalle_asig_docente
                                JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                JOIN aulas a ON a.Id_aula = ha.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                                JOIN seccion s ON s.seccion_id = a.id_seccion
                                WHERE h.dia = ? AND h.estado = 'ACTIVO' AND dd.Id_detalle_asig_docente IN ($cursos)
                                ORDER BY ha.hora_inicio", [$comun['dia'], $usu, $anio]) : [];
        // Aulas donde enseña y si ya tienen asistencia hoy
        $aulas = $this->todos("SELECT DISTINCT a.Id_aula AS id, $aula AS aula,
                                      EXISTS (SELECT 1 FROM asistencia x JOIN matricula m ON m.id_matricula = x.id_matricula
                                              WHERE m.id_aula = a.Id_aula AND m.`id_año` = ? AND x.fecha = CURDATE()) AS registrada
                               FROM detalle_asignatura_docente dd JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                               JOIN aulas a ON a.Id_aula = asg.Id_grado JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                               JOIN seccion s ON s.seccion_id = a.id_seccion
                               WHERE dd.Id_detalle_asig_docente IN ($cursos)
                                 AND EXISTS (SELECT 1 FROM matricula m WHERE m.id_aula = a.Id_aula AND m.`id_año` = ?)
                               ORDER BY n.Id_nivel, a.Id_aula", [$anio, $usu, $anio, $anio]);
        $por_calificar = $this->todos("SELECT t.id_tarea AS id, t.tema, asg.nombre_asig AS curso, COUNT(*) AS entregas
                                       FROM detalle_tarea x JOIN tareas t ON t.id_tarea = x.id_tarea
                                       JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                                       JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                       WHERE x.estado = 'ENVIADO' AND t.id_detalle_asignatura IN ($cursos)
                                       GROUP BY t.id_tarea ORDER BY t.fecha_entrega LIMIT 5", [$usu, $anio]);
        $examenes = $this->todos("SELECT e.tema_examen AS tema, e.fecha_examen AS fecha, asg.nombre_asig AS curso, $aula AS aula
                                  FROM examen e JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = e.id_detalle_asignatura
                                  JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura JOIN aulas a ON a.Id_aula = asg.Id_grado
                                  JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico JOIN seccion s ON s.seccion_id = a.id_seccion
                                  WHERE e.fecha_examen >= CURDATE() AND e.estado = 'PENDIENTE' AND e.id_detalle_asignatura IN ($cursos)
                                  ORDER BY e.fecha_examen LIMIT 4", [$usu, $anio]);
        return ['clases' => $clases, 'aulas' => $aulas, 'por_calificar' => $por_calificar, 'examenes' => $examenes, 'comunicados' => $this->comunicados()];
    }

    public function Estudiante($usu, array $comun) {
        $anio = $comun['anio']['id'] ?? 0;
        $aula = $this->nombreAula();
        $m = $this->uno("SELECT m.id_matricula AS id, m.id_aula, $aula AS aula FROM matricula m
                         JOIN aulas a ON a.Id_aula = m.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                         JOIN seccion s ON s.seccion_id = a.id_seccion
                         WHERE m.usu_id = ? AND m.`id_año` = ? ORDER BY m.id_matricula DESC LIMIT 1", [$usu, $anio]);
        if (!$m) {
            return ['matricula' => null, 'comunicados' => $this->comunicados()];
        }
        $clases = $comun['dia'] ? $this->todos("SELECT TIME_FORMAT(ha.hora_inicio, '%H:%i') AS inicio, TIME_FORMAT(ha.hora_fin, '%H:%i') AS fin,
                                       asg.nombre_asig AS curso, CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente
                                FROM horarios h JOIN horas_aula ha ON ha.id_hora = h.id_hora_aula
                                JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = h.id_detalle_asig_docente
                                JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente JOIN docentes d ON d.Id_docente = ad.Id_docente
                                WHERE ha.id_aula = ? AND ha.`id_año_academico` = ? AND h.dia = ? AND h.estado = 'ACTIVO'
                                ORDER BY ha.hora_inicio", [$m['id_aula'], $anio, $comun['dia']]) : [];
        $tareas = $this->todos("SELECT t.tema, t.fecha_entrega AS entrega, asg.nombre_asig AS curso, t.fecha_entrega < NOW() AS vencida
                                FROM detalle_tarea x JOIN tareas t ON t.id_tarea = x.id_tarea
                                JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                                JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                WHERE x.id_matriculado = ? AND x.estado = 'PENDIENTE' AND t.estado = 'PENDIENTE'
                                ORDER BY t.fecha_entrega LIMIT 6", [$m['id']]);
        $notas = $this->todos("SELECT t.tema, x.calificacion AS nota, asg.nombre_asig AS curso
                               FROM detalle_tarea x JOIN tareas t ON t.id_tarea = x.id_tarea
                               JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                               JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                               WHERE x.id_matriculado = ? AND x.estado = 'CALIFICADO'
                               ORDER BY IFNULL(x.updated_at, x.created_at) DESC LIMIT 4", [$m['id']]);
        $asistencia = $this->uno("SELECT SUM(estado = 'PRESENTE') AS P, SUM(estado = 'TARDE') AS T, SUM(estado = 'AUSENTE') AS F, SUM(estado = 'JUSTIFICADO') AS J
                                  FROM asistencia WHERE id_matricula = ? AND fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$m['id']]);
        $pensiones = $this->todos("SELECT mes, fecha_vencimiento AS vence, saldo_pension + saldo_mora AS saldo, vencida
                                   FROM vw_estado_pensiones WHERE id_matricula = ? AND saldo_pension + saldo_mora > 0
                                   ORDER BY fecha_vencimiento LIMIT 3", [$m['id']]);
        $examenes = $this->todos("SELECT e.tema_examen AS tema, e.fecha_examen AS fecha, asg.nombre_asig AS curso
                                  FROM examen e JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = e.id_detalle_asignatura
                                  JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                  WHERE asg.Id_grado = ? AND e.fecha_examen >= CURDATE() AND e.estado = 'PENDIENTE'
                                  ORDER BY e.fecha_examen LIMIT 4", [$m['id_aula']]);
        return [
            'matricula' => ['aula' => $m['aula']],
            'clases' => $clases, 'tareas' => $tareas, 'notas' => $notas, 'examenes' => $examenes,
            'asistencia' => array_map('intval', $asistencia), 'pensiones' => $pensiones,
            'comunicados' => $this->comunicados($m['id_aula']),
        ];
    }

    /** Auxiliar: asistencia del día por aula. */
    public function Auxiliar(array $comun) {
        $anio = $comun['anio']['id'] ?? 0;
        $aula = $this->nombreAula();
        $aulas = $this->todos("SELECT a.Id_aula AS id, $aula AS aula,
                                      (SELECT COUNT(*) FROM matricula m WHERE m.id_aula = a.Id_aula AND m.`id_año` = ?) AS estudiantes,
                                      (SELECT COUNT(*) FROM asistencia x JOIN matricula m ON m.id_matricula = x.id_matricula
                                        WHERE m.id_aula = a.Id_aula AND m.`id_año` = ? AND x.fecha = CURDATE()) AS registrados
                               FROM aulas a JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico JOIN seccion s ON s.seccion_id = a.id_seccion
                               WHERE EXISTS (SELECT 1 FROM matricula m WHERE m.id_aula = a.Id_aula AND m.`id_año` = ?)
                               ORDER BY n.Id_nivel, a.Id_aula", [$anio, $anio, $anio]);
        return ['aulas' => $aulas, 'comunicados' => $this->comunicados()];
    }

    /** Enfermería o psicología: atenciones recientes y del mes. */
    public function Salud($tipo, array $comun) {
        $recientes = $this->todos("SELECT DATE_FORMAT(x.created_at, '%Y-%m-%d %H:%i') AS fecha, x.motivo_consulta AS motivo,
                                          CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS estudiante
                                   FROM atencion_salud x JOIN matricula m ON m.id_matricula = x.id_matricula JOIN alumnos al ON al.Id_alumno = m.id_alumno
                                   WHERE x.tipo_atencion = ? ORDER BY x.created_at DESC LIMIT 6", [$tipo]);
        $cuenta = $this->uno("SELECT SUM(DATE(created_at) = CURDATE()) AS hoy, SUM(created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS mes
                              FROM atencion_salud WHERE tipo_atencion = ?", [$tipo]);
        return ['tipo' => $tipo, 'recientes' => $recientes, 'atenciones_hoy' => (int) ($cuenta['hoy'] ?? 0), 'atenciones_mes' => (int) ($cuenta['mes'] ?? 0),
                'comunicados' => $this->comunicados()];
    }
}
