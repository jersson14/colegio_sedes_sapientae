<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
    require '../../model/model_pago_pension.php';
    $MPP = new Modelo_Pago_Pension(); //Instanciamos

    // El monto de cada pensión lo valida el servidor contra su saldo (precio + mora pendiente)
    $id_matri = entero_post('id_matri', 1, 'matrícula');
    $lineas = json_decode($_POST['lineas'] ?? '', true);
    $metodos = json_decode($_POST['metodos'] ?? '[]', true);
    $obs = mb_substr(strtoupper(trim((string) ($_POST['observacion'] ?? ''))), 0, 255, 'UTF-8');
    if (!is_array($lineas) || count($lineas) == 0 || count($lineas) > 24) {
        responder_json(['ok' => false, 'mensaje' => 'La tabla de pagos debe contener al menos un registro']);
    }
    if (!is_array($metodos) || count($metodos) > 6) {
        responder_json(['ok' => false, 'mensaje' => 'Formas de pago no válidas']);
    }
    $es_monto = function ($v) { return preg_match('/^\d{1,12}(\.\d{1,2})?$/', $v); };
    $centavos = function ($v) { return (int) round(((float) $v) * 100); };

    $conceptos = ['PENSION', 'MATRICULA', 'ALUMNO NUEVO', 'ADMISION'];
    $limpias = [];
    $total = 0;
    foreach ($lineas as $l) {
        $concepto = strtoupper(trim((string) ($l['concepto'] ?? '')));
        $monto = str_replace(',', '.', trim((string) ($l['monto'] ?? '')));
        if (!in_array($concepto, $conceptos, true)) {
            responder_json(['ok' => false, 'mensaje' => 'Concepto no válido']);
        }
        if (!$es_monto($monto)) {
            responder_json(['ok' => false, 'mensaje' => 'Monto no válido en ' . $concepto]);
        }
        $id_pension = null;
        if ($concepto === 'PENSION') {
            $id_pension = filter_var($l['id_pension'] ?? null, FILTER_VALIDATE_INT);
            if (!$id_pension) {
                responder_json(['ok' => false, 'mensaje' => 'Seleccione la pensión a pagar']);
            }
        }
        $total += $centavos($monto);
        $limpias[] = [
            'concepto'   => $concepto,
            'id_pension' => $id_pension,
            'monto'      => number_format((float) $monto, 2, '.', ''),
            'exonerar'   => !empty($l['exonerar']) ? 1 : 0,
            'motivo'     => mb_substr(strtoupper(trim((string) ($l['motivo'] ?? ''))), 0, 200, 'UTF-8'),
        ];
    }

    // Formas de pago: deben sumar exactamente el total (el SP lo vuelve a verificar al cerrar el recibo)
    $formas = [];
    $suma = 0;
    foreach ($metodos as $m) {
        $monto = str_replace(',', '.', trim((string) ($m['monto'] ?? '')));
        $id_metodo = filter_var($m['id_metodo'] ?? null, FILTER_VALIDATE_INT);
        if (!$id_metodo || !$es_monto($monto) || (float) $monto <= 0) {
            responder_json(['ok' => false, 'mensaje' => 'Revise las formas de pago (método y monto)']);
        }
        $suma += $centavos($monto);
        $formas[] = [
            'id_metodo'  => $id_metodo,
            'id_cuenta'  => filter_var($m['id_cuenta'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'monto'      => number_format((float) $monto, 2, '.', ''),
            'referencia' => mb_substr(strtoupper(trim((string) ($m['referencia'] ?? ''))), 0, 60, 'UTF-8'),
        ];
    }
    if ($total > 0 && count($formas) == 0) {
        responder_json(['ok' => false, 'mensaje' => 'Indique la forma de pago']);
    }
    if ($suma !== $total) {
        responder_json(['ok' => false, 'mensaje' => 'Las formas de pago suman ' . number_format($suma / 100, 2, '.', '')
            . ' y el total a pagar es ' . number_format($total / 100, 2, '.', '')]);
    }

    $voucher = validar_voucher_subido('voucher');
    $r = $MPP->Registrar_Pago($id_matri, $limpias, $formas, $obs, usuario_actual_id());
    if ($r['ok'] && $voucher) {
        $ruta = guardar_voucher($voucher);
        if ($ruta) {
            $MPP->Guardar_Voucher($r['id_pago'], $ruta);
        }
    }
    responder_json($r);
?>
