<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pago_pension.php';
    $MPP = new Modelo_Pago_Pension(); //Instanciamos

    // El monto de cada pensión lo valida el servidor contra su saldo (precio + mora pendiente)
    $id_matri = entero_post('id_matri', 1, 'matrícula');
    $lineas = json_decode($_POST['lineas'] ?? '', true);
    if (!is_array($lineas) || count($lineas) == 0 || count($lineas) > 24) {
        responder_json(['ok' => false, 'mensaje' => 'La tabla de pagos debe contener al menos un registro']);
    }

    $conceptos = ['PENSION', 'MATRICULA', 'ALUMNO NUEVO', 'ADMISION'];
    $limpias = [];
    foreach ($lineas as $l) {
        $concepto = strtoupper(trim((string) ($l['concepto'] ?? '')));
        $monto = str_replace(',', '.', trim((string) ($l['monto'] ?? '')));
        if (!in_array($concepto, $conceptos, true)) {
            responder_json(['ok' => false, 'mensaje' => 'Concepto no válido']);
        }
        if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $monto)) {
            responder_json(['ok' => false, 'mensaje' => 'Monto no válido en ' . $concepto]);
        }
        $id_pension = null;
        if ($concepto === 'PENSION') {
            $id_pension = filter_var($l['id_pension'] ?? null, FILTER_VALIDATE_INT);
            if (!$id_pension) {
                responder_json(['ok' => false, 'mensaje' => 'Seleccione la pensión a pagar']);
            }
        }
        $limpias[] = [
            'concepto'   => $concepto,
            'id_pension' => $id_pension,
            'monto'      => number_format((float) $monto, 2, '.', ''),
            'exonerar'   => !empty($l['exonerar']) ? 1 : 0,
            'motivo'     => mb_substr(strtoupper(trim((string) ($l['motivo'] ?? ''))), 0, 200, 'UTF-8'),
        ];
    }

    responder_json($MPP->Registrar_Pago($id_matri, $limpias, usuario_actual_id()));
?>
