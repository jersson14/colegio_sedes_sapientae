<?php
require_once 'model_conexion.php';

/**
 * Avisos a las familias (Fase 13, paso 14): faltas del día, tareas vencidas sin entregar, pensiones por vencer o
 * vencidas y comunicados sin leer. Cada aviso trae el contacto de la madre o del padre y si ya se dio.
 * El canal hoy es WhatsApp con el mensaje ya escrito (lo envía la persona); cada envío queda en avisos_enviados.
 */
class Modelo_Avisos extends conexionBD {
    const TIPOS = ['FALTA', 'TAREA', 'PENSION', 'COMUNICADO'];
    const DIAS_TAREA = 14;        // tareas vencidas en las últimas dos semanas
    const DIAS_COMUNICADO = 15;   // comunicados publicados en las últimas dos semanas
    private $c;

    private function todos($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetchAll(PDO::FETCH_ASSOC); }

    public function Config() {
        $this->c = $this->conexionPDO();
        $cfg = ['falta' => '1', 'tarea' => '1', 'pension' => '1', 'comunicado' => '1', 'dias_pension' => '3'];
        foreach ($this->todos("SELECT clave, valor FROM avisos_config") as $r) { $cfg[$r['clave']] = $r['valor']; }
        return $cfg;
    }

    public function Guardar_Config(array $cfg) {
        $this->c = $this->conexionPDO();
        $q = $this->c->prepare("INSERT INTO avisos_config(clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        foreach ($cfg as $k => $v) { $q->execute([$k, (string) $v]); }
    }

    /** Datos comunes de cada aviso: estudiante, aula, contactos y si ya se avisó. */
    private function columnas($tipo, $referencia) {
        return "'$tipo' AS tipo, $referencia AS referencia, m.id_matricula AS matricula,
                CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS estudiante, CONCAT(n.Nivel_academico, ' · ', a.Grado) AS aula,
                (SELECT NULLIF(TRIM(p.Datos_mama), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS madre,
                (SELECT NULLIF(TRIM(p.Celular_mama), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS cel_madre,
                (SELECT NULLIF(TRIM(p.Datos_papa), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS padre,
                (SELECT NULLIF(TRIM(p.Celular_papa), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS cel_padre,
                e.enviado_at, u.usu_usuario AS enviado_por";
    }

    private function uniones($tipo, $referencia) {
        return "JOIN alumnos al ON al.Id_alumno = m.id_alumno JOIN aulas a ON a.Id_aula = m.id_aula
                JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                LEFT JOIN avisos_enviados e ON e.tipo = '$tipo' AND e.referencia = CONVERT($referencia USING utf8mb4) COLLATE utf8mb4_general_ci
                    AND e.id_matricula = m.id_matricula
                LEFT JOIN usuario u ON u.usu_id = e.usu_id";
    }

    /**
     * Avisos de los tipos pedidos. $fecha: día de las faltas (aaaa-mm-dd). $con_enviados: incluir los ya avisados.
     * Solo estudiantes del año escolar activo.
     */
    public function Pendientes(array $tipos, $fecha, $con_enviados, $dias_pension) {
        $this->c = $this->conexionPDO();
        $anio = "(SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1)";
        $solo = $con_enviados ? '' : 'AND e.id_aviso IS NULL';
        $res = [];
        if (in_array('FALTA', $tipos, true)) {
            $res = array_merge($res, $this->todos("SELECT " . $this->columnas('FALTA', 'x.fecha') . ", x.fecha AS fecha, x.observacion AS detalle
                FROM asistencia x JOIN matricula m ON m.id_matricula = x.id_matricula " . $this->uniones('FALTA', 'x.fecha') . "
                WHERE x.fecha = ? AND x.estado = 'AUSENTE' AND m.`id_año` = $anio $solo ORDER BY n.Id_nivel, a.Id_aula, al.alum_apepat", [$fecha]));
        }
        if (in_array('TAREA', $tipos, true)) {
            $res = array_merge($res, $this->todos("SELECT " . $this->columnas('TAREA', 't.id_tarea') . ", t.fecha_entrega AS fecha,
                       CONCAT(t.tema, ' (', asg.nombre_asig, ')') AS detalle
                FROM detalle_tarea x JOIN tareas t ON t.id_tarea = x.id_tarea JOIN matricula m ON m.id_matricula = x.id_matriculado
                JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                " . $this->uniones('TAREA', 't.id_tarea') . "
                WHERE x.estado = 'PENDIENTE' AND t.fecha_entrega < NOW() AND t.fecha_entrega >= NOW() - INTERVAL " . self::DIAS_TAREA . " DAY
                  AND m.`id_año` = $anio $solo ORDER BY t.fecha_entrega DESC, al.alum_apepat"));
        }
        if (in_array('PENSION', $tipos, true)) {
            // Un solo aviso por familia y por mes: meses vencidos, los que vencen pronto y el total adeudado
            $mes = "DATE_FORMAT(CURDATE(), '%Y-%m')";
            $res = array_merge($res, $this->todos("SELECT " . $this->columnas('PENSION', $mes) . ", MIN(v.fecha_vencimiento) AS fecha,
                       CONCAT(SUM(v.vencida), '|', GROUP_CONCAT(v.mes ORDER BY v.mes_num SEPARATOR ', '), '|', SUM(v.saldo_pension + v.saldo_mora)) AS detalle
                FROM vw_estado_pensiones v JOIN matricula m ON m.id_matricula = v.id_matricula " . $this->uniones('PENSION', $mes) . "
                WHERE v.saldo_pension + v.saldo_mora > 0 AND v.exonerada = 0 AND m.`id_año` = $anio
                  AND (v.vencida = 1 OR v.fecha_vencimiento <= CURDATE() + INTERVAL ? DAY) $solo
                GROUP BY m.id_matricula, e.id_aviso
                ORDER BY SUM(v.vencida) DESC, MIN(v.fecha_vencimiento), al.alum_apepat", [(int) $dias_pension]));
        }
        if (in_array('COMUNICADO', $tipos, true)) {
            $res = array_merge($res, $this->todos("SELECT " . $this->columnas('COMUNICADO', 'c.id_comunicado') . ", c.created_at AS fecha, c.titulo AS detalle
                FROM comunicados c JOIN matricula m ON m.`id_año` = $anio AND (c.tipo = 'GENERAL' OR m.id_aula = c.id_aula)
                " . $this->uniones('COMUNICADO', 'c.id_comunicado') . "
                WHERE c.estado = 'ACTIVO' AND c.created_at >= CURDATE() - INTERVAL " . self::DIAS_COMUNICADO . " DAY
                  AND NOT EXISTS (SELECT 1 FROM comunicado_lecturas l WHERE l.id_comunicado = c.id_comunicado AND l.usu_id = m.usu_id) $solo
                ORDER BY c.created_at DESC, n.Id_nivel, al.alum_apepat"));
        }
        return $res;
    }

    /** Registra que se dio el aviso (una sola vez por familia y motivo). */
    public function Marcar($tipo, $referencia, $matricula, $destino, $usu) {
        $this->c = $this->conexionPDO();
        $q = $this->c->prepare("INSERT IGNORE INTO avisos_enviados(tipo, referencia, id_matricula, canal, destino, usu_id, enviado_at) VALUES (?, ?, ?, 'WHATSAPP', ?, ?, NOW())");
        $q->execute([$tipo, $referencia, $matricula, $destino, $usu]);
        return $q->rowCount();
    }
}
