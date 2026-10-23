<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_formas_pago.php';
    $MFP = new Modelo_Formas_Pago();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $nombre = strtoupper(texto_post('nombre', 60, true, 'nombre'));
    $tipo = strtoupper(texto_post('tipo', 20, true, 'tipo'));
    if (!in_array($tipo, ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'DEPOSITO', 'BILLETERA', 'CHEQUE', 'OTRO'], true)) {
        denegar(400, 'Tipo de método no válido');
    }
    if (trim($_POST['comision'] ?? '') === '') { $_POST['comision'] = '0'; }
    $comision = monto_post('comision', 0, 100, 'comisión (%)');
    $estado = ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
    responder_json($MFP->Guardar_Metodo($id, $nombre, $tipo, !empty($_POST['requiere_referencia']) ? 1 : 0,
                                        !empty($_POST['requiere_cuenta']) ? 1 : 0, $comision, $estado));
?>
