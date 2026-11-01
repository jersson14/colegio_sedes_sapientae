<?php
require_once __DIR__ . '/_matricula.php';
require_once __DIR__ . '/../../model/model_caja.php';
// Mis pagos: pensiones del año con su estado y los pagos hechos, agrupados por recibo
$pensiones = [];
$recibos = [];
if ($MAT) {
    $pensiones = $ME->Pensiones((int) $MAT['matricula']);
    $recibos = pagos_por_recibo((new Modelo_Pago_Pension())->Listar_pagos_alu((int) $MAT['matricula'])['data'] ?? []);
}
estudiante_responder(['pensiones' => $pensiones, 'recibos' => $recibos, 'hoy' => date('Y-m-d')]);
