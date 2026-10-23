<?php
require_once __DIR__ . '/../_seguridad.php';
require '../../model/model_pago_pension.php';
$MPP = new Modelo_Pago_Pension();
$id = (int) ($_POST['id'] ?? 0);
// El estudiante envía el id de SU matrícula: se verifica que le pertenezca
if (rol_actual() === 'ESTUDIANTE' && !$MPP->Matricula_Es_Del_Usuario($id, usuario_actual_id())) {
    registrar_acceso_denegado('controller/pago_pension/controlador_listar_tabla_pagos.php', 'MATRICULA_AJENA');
    denegar(403, 'No tiene permiso para ver estos pagos');
}
$consulta = $MPP->Listar_pagos_alu($id);

if ($consulta) {
    echo json_encode($consulta);
} else {
    echo json_encode(array(
        "sEcho" => 1,
        "iTotalRecords" => "0",
        "iTotalDisplayRecords" => "0",
        "data" => array(),  // Cambiado de "aaData" a "data" para mantener consistencia
        "total_sub_total" => 0
    ));
}
?>
