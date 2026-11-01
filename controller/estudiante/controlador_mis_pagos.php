<?php
require_once __DIR__ . '/_matricula.php';
require_once __DIR__ . '/../../model/model_pago_pension.php';
// Mis pagos: pensiones del año con su estado y los pagos hechos, agrupados por recibo
$pensiones = [];
$recibos = [];
if ($MAT) {
    $pensiones = $ME->Pensiones((int) $MAT['matricula']);
    $pagos = (new Modelo_Pago_Pension())->Listar_pagos_alu((int) $MAT['matricula'])['data'] ?? [];
    foreach ($pagos as $p) {
        $k = $p['id_pago'] ? 'R' . $p['id_pago'] : 'P' . $p['id_pago_pension'];
        if (!isset($recibos[$k])) {
            $recibos[$k] = ['id_pago' => $p['id_pago'], 'id_pago_pension' => $p['id_pago_pension'], 'recibo' => $p['recibo'],
                            'fecha' => $p['fecha_pago'], 'metodos' => $p['metodos'], 'total' => 0, 'conceptos' => []];
        }
        $recibos[$k]['total'] += (float) $p['sub_total'];
        $recibos[$k]['conceptos'][] = ['concepto' => $p['mes'] ? 'PENSIÓN ' . $p['mes'] : $p['concepto'], 'monto' => (float) $p['sub_total']];
    }
    usort($recibos, function ($a, $b) { return strcmp((string) $b['fecha'], (string) $a['fecha']); });
}
estudiante_responder(['pensiones' => $pensiones, 'recibos' => array_values($recibos), 'hoy' => date('Y-m-d')]);
