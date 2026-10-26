<?php
require_once 'model_conexion.php';

/**
 * Ficha única del estudiante y buscador general (Fase 13, paso 6).
 * Qué secciones ve cada rol lo decide el controlador; aquí solo se arman los datos.
 */
class Modelo_Ficha extends conexionBD {
    private $c;

    private function uno($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetch(PDO::FETCH_ASSOC) ?: []; }
    private function todos($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetchAll(PDO::FETCH_ASSOC); }

    private static function nombreAula() {
        return "CONCAT(n.Nivel_academico, ' · ', a.Grado, IF(s.seccion_nombre IS NOT NULL AND s.seccion_nombre <> 'UNICO', CONCAT(' ', s.seccion_nombre), ''))";
    }

    /** Hasta 8 estudiantes cuyo nombre (cada palabra, en cualquier orden) o documento coincide. */
    public function Buscar($texto) {
        $this->c = conexionBD::conexionPDO();
        $palabras = array_slice(preg_split('/\s+/u', trim($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 5);
        if (!$palabras) { return []; }
        $where = [];
        $p = [];
        foreach ($palabras as $w) {
            $where[] = "(CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) LIKE ? OR al.alum_dni LIKE ?)";
            $p[] = '%' . $w . '%';
            $p[] = $w . '%';
        }
        $aula = self::nombreAula();
        return $this->todos("SELECT al.Id_alumno AS id, al.alum_dni AS documento,
                                    CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, al.alum_nombre AS nombres,
                                    al.alum_fotoperfil AS foto,
                                    (SELECT CONCAT(ae.`año_escolar`, ' · ', $aula) FROM matricula m
                                       JOIN `año_escolar` ae ON ae.`Id_año_escolar` = m.`id_año`
                                       JOIN aulas a ON a.Id_aula = m.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                                       LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                                      WHERE m.id_alumno = al.Id_alumno ORDER BY ae.fecha_inicio DESC, m.id_matricula DESC LIMIT 1) AS aula
                             FROM alumnos al
                             WHERE " . implode(' AND ', $where) . "
                             ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre LIMIT 8", $p);
    }

    /**
     * Ficha del estudiante. $secciones: lista de secciones permitidas (datos, padres, asistencia, notas, tareas, pensiones, salud).
     * $id_matricula: matrícula a mostrar (de este estudiante); por defecto, la más reciente.
     */
    public function Ficha($id_alumno, array $secciones, $id_matricula = null) {
        $this->c = conexionBD::conexionPDO();
        $al = $this->uno("SELECT Id_alumno AS id, alum_dni AS documento, alum_nombre AS nombres,
                                 CONCAT_WS(' ', alum_apepat, alum_apemat) AS apellidos, alum_sexo AS sexo,
                                 alum_fechanacimiento AS nacimiento, TIMESTAMPDIFF(YEAR, alum_fechanacimiento, CURDATE()) AS edad,
                                 alum_movil AS telefono, alum_direccion AS direccion, tipo_alum AS tipo, alum_fotoperfil AS foto
                          FROM alumnos WHERE Id_alumno = ?", [$id_alumno]);
        if (!$al) { return null; }
        $aula = self::nombreAula();
        $matriculas = $this->todos("SELECT m.id_matricula AS id, ae.`año_escolar` AS anio, ae.estado AS estado_anio, $aula AS aula,
                                           DATE(m.created_at) AS fecha, m.procedencia_colegio AS procedencia,
                                           m.beca_tipo, m.beca_valor + 0 AS beca_valor, u.usu_usuario AS usuario, u.usu_estatus AS acceso
                                    FROM matricula m JOIN `año_escolar` ae ON ae.`Id_año_escolar` = m.`id_año`
                                    JOIN aulas a ON a.Id_aula = m.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                                    LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                                    LEFT JOIN usuario u ON u.usu_id = m.usu_id
                                    WHERE m.id_alumno = ? ORDER BY ae.fecha_inicio DESC, m.id_matricula DESC", [$id_alumno]);
        $actual = $matriculas[0] ?? null;
        foreach ($matriculas as $m) {
            if ($id_matricula !== null && (int) $m['id'] === (int) $id_matricula) { $actual = $m; }
        }
        $r = ['alumno' => $al, 'matriculas' => $matriculas, 'matricula' => $actual, 'secciones' => $secciones];
        if (in_array('padres', $secciones, true)) {
            $r['padres'] = $this->uno("SELECT Datos_papa AS padre, Dni_papa AS dni_padre, Celular_papa AS cel_padre,
                                              Datos_mama AS madre, Dni_mama AS dni_madre, Celular_mama AS cel_madre
                                       FROM padres WHERE id_alu = ? ORDER BY id_papas DESC LIMIT 1", [$id_alumno]);
        }
        if (!$actual) { return $r; }
        $mid = $actual['id'];
        if (in_array('asistencia', $secciones, true)) {
            $r['asistencia'] = [
                'total' => $this->uno("SELECT COUNT(*) AS dias, SUM(estado = 'PRESENTE') AS P, SUM(estado = 'TARDE') AS T,
                                              SUM(estado = 'AUSENTE') AS F, SUM(estado = 'JUSTIFICADO') AS J
                                       FROM asistencia WHERE id_matricula = ?", [$mid]),
                'meses' => $this->todos("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS dias, SUM(estado = 'PRESENTE') AS P,
                                                SUM(estado = 'TARDE') AS T, SUM(estado = 'AUSENTE') AS F, SUM(estado = 'JUSTIFICADO') AS J
                                         FROM asistencia WHERE id_matricula = ? GROUP BY DATE_FORMAT(fecha, '%Y-%m') ORDER BY mes", [$mid]),
                'faltas' => $this->todos("SELECT fecha, estado, observacion FROM asistencia
                                          WHERE id_matricula = ? AND estado IN ('AUSENTE', 'TARDE', 'JUSTIFICADO')
                                          ORDER BY fecha DESC LIMIT 6", [$mid]),
            ];
        }
        if (in_array('notas', $secciones, true)) {
            $r['notas'] = $this->todos("SELECT asg.nombre_asig AS curso, p.id_periodo AS id_periodo, p.periodos AS periodo, n.nota
                                        FROM notas n JOIN criterios c ON c.id_criterio = n.id_criterio
                                        JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = c.id_detalle_asignatura
                                        JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                        JOIN periodos p ON p.id_periodo = n.id_bimestre
                                        WHERE n.id_matricula = ? AND TRIM(IFNULL(n.nota, '')) <> ''
                                        ORDER BY asg.nombre_asig, p.fecha_inicio, c.id_criterio", [$mid]);
        }
        if (in_array('tareas', $secciones, true)) {
            $r['tareas'] = [
                'resumen' => $this->uno("SELECT COUNT(*) AS total, SUM(x.estado IN ('ENVIADO', 'CALIFICADO')) AS entregadas,
                                                SUM(x.estado = 'CALIFICADO') AS calificadas,
                                                SUM(x.estado = 'PENDIENTE' AND t.fecha_entrega < NOW()) AS vencidas
                                         FROM detalle_tarea x JOIN tareas t ON t.id_tarea = x.id_tarea WHERE x.id_matriculado = ?", [$mid]),
                'vencidas' => $this->todos("SELECT t.tema, t.fecha_entrega AS entrega, asg.nombre_asig AS curso
                                            FROM detalle_tarea x JOIN tareas t ON t.id_tarea = x.id_tarea
                                            JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                                            JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                            WHERE x.id_matriculado = ? AND x.estado = 'PENDIENTE' AND t.fecha_entrega < NOW()
                                            ORDER BY t.fecha_entrega DESC LIMIT 5", [$mid]),
            ];
        }
        if (in_array('pensiones', $secciones, true)) {
            $r['pensiones'] = $this->todos("SELECT mes, fecha_vencimiento AS vence, precio, pagado_pension + pagado_mora AS pagado,
                                                   saldo_pension + saldo_mora AS saldo, vencida, exonerada
                                            FROM vw_estado_pensiones WHERE id_matricula = ? ORDER BY mes_num", [$mid]);
            $r['pagos'] = $this->todos("SELECT CONCAT(p.serie, '-', LPAD(p.numero, 6, '0')) AS comprobante, DATE(p.fecha) AS fecha,
                                               GROUP_CONCAT(DISTINCT pp.concepto ORDER BY pp.concepto SEPARATOR ', ') AS conceptos, p.total, p.estado
                                        FROM pagos p JOIN pago_pensiones pp ON pp.id_pago = p.id_pago
                                        WHERE p.id_matricula = ? GROUP BY p.id_pago ORDER BY p.fecha DESC, p.id_pago DESC", [$mid]);
        }
        if (in_array('salud', $secciones, true)) {
            $r['salud'] = $this->todos("SELECT tipo_atencion AS tipo, DATE(created_at) AS fecha, motivo_consulta AS motivo, diagnostico
                                        FROM atencion_salud WHERE id_matricula IN (SELECT id_matricula FROM matricula WHERE id_alumno = ?)
                                        ORDER BY created_at DESC LIMIT 10", [$id_alumno]);
        }
        return $r;
    }
}
