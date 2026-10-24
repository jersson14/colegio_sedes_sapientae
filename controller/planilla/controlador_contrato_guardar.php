<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    // Contrato del docente o del personal administrativo: tipo (tiempo completo / parcial / por horas), sueldo o tarifa y forma de pago
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    foreach (['sueldo', 'tarifa', 'horas'] as $k) { if (trim($_POST[$k] ?? '') === '') { $_POST[$k] = '0'; } }
    responder_json($MP->Guardar_Contrato($id, tipo_personal_post(), entero_post('docente', 1, 'persona'), entero_post('tipo', 1, 'tipo de contrato'),
        fecha_post('inicio', 'fecha de inicio'), monto_post('sueldo', 0, 999999999999.99, 'sueldo'), monto_post('tarifa', 0, 999999999999.99, 'tarifa por hora'),
        monto_post('horas', 0, 168, 'horas semanales'), entero_post('metodo', 1, 'forma de pago'), strtoupper(texto_post('banco', 100, false, 'banco')),
        strtoupper(texto_post('cuenta', 40, false, 'cuenta del docente')), strtoupper(texto_post('observacion', 255, false, 'observación')), usuario_actual_id()));
?>
