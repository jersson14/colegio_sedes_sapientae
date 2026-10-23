<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_formas_pago.php';
    $MFP = new Modelo_Formas_Pago();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $tipo = strtoupper(texto_post('tipo', 20, true, 'tipo'));
    if (!in_array($tipo, ['CAJA', 'BANCO', 'BILLETERA'], true)) {
        denegar(400, 'Tipo de cuenta no válido');
    }
    $nombre = strtoupper(texto_post('nombre', 100, true, 'nombre'));
    $banco = strtoupper(texto_post('banco', 100, false, 'banco')) ?: null;
    $numero = strtoupper(texto_post('numero', 40, false, 'número de cuenta')) ?: null;
    $cci = strtoupper(texto_post('cci', 40, false, 'código interbancario')) ?: null;
    $titular = strtoupper(texto_post('titular', 150, false, 'titular')) ?: null;
    if (trim($_POST['saldo'] ?? '') === '') { $_POST['saldo'] = '0'; }
    $saldo = monto_post('saldo', 0, 999999999999.99, 'saldo inicial');
    $fecha_saldo = trim($_POST['fecha_saldo'] ?? '') === '' ? date('Y-m-d') : fecha_post('fecha_saldo', 'fecha del saldo inicial');
    $estado = ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
    responder_json($MFP->Guardar_Cuenta($id, $tipo, $nombre, $banco, $numero, $cci, $titular, $saldo, $fecha_saldo, $estado));
?>
