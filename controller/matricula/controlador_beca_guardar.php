<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_matriculas.php';
    $MMA = new Modelo_Matriculas();
    // Beca o descuento de las pensiones de una matrícula: NINGUNA, PORCENTAJE (0-100) o MONTO por pensión
    $tipo = $_POST['tipo'] ?? '';
    if (!in_array($tipo, ['NINGUNA', 'PORCENTAJE', 'MONTO'], true)) { denegar(400, 'Tipo de beca no válido'); }
    $valor = $tipo === 'NINGUNA' ? '0' : monto_post('valor', 0.01, 999999999999.99, 'valor de la beca');
    $desde = trim($_POST['desde'] ?? '') === '' ? null : fecha_post('desde', 'fecha desde');
    responder_json($MMA->Guardar_Beca(entero_post('id', 1, 'matrícula'), $tipo, $valor, $desde,
        mb_strtoupper(texto_post('motivo', 255, false, 'motivo'), 'UTF-8')));
?>
