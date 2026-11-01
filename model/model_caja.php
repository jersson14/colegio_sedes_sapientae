<?php
require_once 'model_conexion.php';
require_once 'model_estudiante.php';
require_once 'model_pago_pension.php';

/** Pagos de una matrícula (SP_LISTAR_PAGOS) agrupados por recibo, el más reciente primero. */
function pagos_por_recibo(array $pagos) {
    $recibos = [];
    foreach ($pagos as $p) {
        $k = $p['id_pago'] ? 'R' . $p['id_pago'] : 'P' . $p['id_pago_pension'];
        if (!isset($recibos[$k])) {
            $recibos[$k] = ['id_pago' => $p['id_pago'], 'id_pago_pension' => $p['id_pago_pension'], 'recibo' => $p['recibo'],
                            'fecha' => $p['fecha_pago'], 'metodos' => $p['metodos'], 'total' => 0, 'conceptos' => []];
        }
        $recibos[$k]['total'] += (float) $p['sub_total'];
        // La mora se guarda en su propia línea (concepto MORA) junto a la pensión del mismo mes
        if ($p['concepto'] === 'MORA') {
            $nombre = 'MORA ' . $p['mes'] . (!empty($p['mora_exonerada']) ? ' (EXONERADA)' : '');
        } else {
            $nombre = $p['mes'] ? 'PENSIÓN ' . $p['mes'] : $p['concepto'];
        }
        $recibos[$k]['conceptos'][] = ['concepto' => $nombre, 'monto' => (float) $p['sub_total']];
    }
    usort($recibos, function ($a, $b) { return strcmp((string) $b['fecha'], (string) $a['fecha']) ?: ((int) $b['id_pago'] <=> (int) $a['id_pago']); });
    return array_values($recibos);
}

/**
 * Caja: cobrar pensiones y otros conceptos a un estudiante (pantalla Cobrar pensión).
 * Solo lee: el cobro y la anulación usan los controladores de pago de siempre.
 */
class Modelo_Caja extends conexionBD {

    const ESTUDIANTE_SQL = "SELECT m.id_matricula AS matricula, m.`id_año` AS anio, al.alum_dni AS documento, al.alum_nombre AS nombres,
                                   CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, a.Grado AS grado, s.seccion_nombre AS seccion,
                                   n.Nivel_academico AS nivel, m.beca_tipo, m.beca_valor
                            FROM matricula m
                            JOIN alumnos al ON al.Id_alumno = m.id_alumno
                            JOIN aulas a ON a.Id_aula = m.id_aula
                            JOIN seccion s ON s.seccion_id = a.id_seccion
                            JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico";

    /** Estudiantes del año que coinciden con el texto (nombre o DNI), con su deuda vencida y total. */
    public function Buscar($anio, $texto) {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $texto) . '%';
        $q = conexionBD::conexionPDO()->prepare(self::ESTUDIANTE_SQL . " WHERE m.`id_año` = ?
                AND (al.alum_dni LIKE ? OR CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) LIKE ?
                     OR CONCAT_WS(' ', al.alum_apepat, al.alum_apemat, al.alum_nombre) LIKE ?)
                ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre LIMIT 15");
        $q->execute([$anio, $like, $like, $like]);
        $res = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($res as &$e) { $e += $this->Deuda($e['matricula']); }
        return $res;
    }

    /** Deuda de pensiones de la matrícula: vencida y total pendiente (sin exoneradas). */
    public function Deuda($matricula) {
        $q = conexionBD::conexionPDO()->prepare("SELECT IFNULL(SUM(IF(vencida = 1, saldo_pension + saldo_mora, 0)), 0) AS vencido,
                                                        IFNULL(SUM(saldo_pension + saldo_mora), 0) AS pendiente
                                                 FROM vw_estado_pensiones WHERE id_matricula = ? AND exonerada = 0");
        $q->execute([$matricula]);
        return array_map('floatval', $q->fetch(PDO::FETCH_ASSOC));
    }

    public function Estudiante($matricula) {
        $q = conexionBD::conexionPDO()->prepare(self::ESTUDIANTE_SQL . " WHERE m.id_matricula = ?");
        $q->execute([$matricula]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Conceptos de la matrícula (admisión, alumno nuevo, matrícula): monto acordado, pagado y saldo. */
    public function Otros($matricula) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT pago_admi, pago_alu_nuevo, pago_matricula FROM matricula WHERE id_matricula = ?");
        $q->execute([$matricula]);
        $m = $q->fetch(PDO::FETCH_ASSOC) ?: [];
        $q = $c->prepare("SELECT concepto, SUM(sub_total) AS pagado FROM pago_pensiones WHERE id_matri = ? AND estado = 'VALIDO' AND concepto <> 'PENSION' GROUP BY concepto");
        $q->execute([$matricula]);
        $pagado = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { $pagado[$r['concepto']] = (float) $r['pagado']; }
        $res = [];
        foreach (['MATRICULA' => 'pago_matricula', 'ADMISION' => 'pago_admi', 'ALUMNO NUEVO' => 'pago_alu_nuevo'] as $concepto => $col) {
            $monto = (float) ($m[$col] ?? 0);
            $p = $pagado[$concepto] ?? 0;
            $res[] = ['concepto' => $concepto, 'monto' => $monto, 'pagado' => $p, 'saldo' => max(0, round($monto - $p, 2))];
        }
        return $res;
    }

    public function Estado($matricula) {
        return [
            'estudiante' => $this->Estudiante($matricula),
            'pensiones' => (new Modelo_Estudiante())->Pensiones($matricula),
            'otros' => $this->Otros($matricula),
            'recibos' => pagos_por_recibo((new Modelo_Pago_Pension())->Listar_pagos_alu($matricula)['data'] ?? []),
        ];
    }
}
