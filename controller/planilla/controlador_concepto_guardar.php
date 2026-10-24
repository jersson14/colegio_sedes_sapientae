<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $tipo = ($_POST['tipo'] ?? '') === 'DESCUENTO' ? 'DESCUENTO' : 'INGRESO';
    $forma = ($_POST['forma'] ?? '') === 'PORCENTAJE' ? 'PORCENTAJE' : 'MONTO';
    if (trim($_POST['valor'] ?? '') === '') { $_POST['valor'] = '0'; }
    $valor = monto_post('valor', 0, $forma === 'PORCENTAJE' ? 100 : 999999999999.99, 'valor por defecto');
    $estado = ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
    responder_json($MP->Guardar_Concepto($id, strtoupper(texto_post('nombre', 80, true, 'nombre')), $tipo, $forma, $valor, $estado));
?>
