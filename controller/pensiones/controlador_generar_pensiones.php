<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();
    // Cronograma de un nivel: meses desde/hasta (1-12, puede cruzar el fin de año), día de vencimiento (0 = último día)
    $anio = entero_post('anio', 1, 'año escolar');
    $nivel = entero_post('nivel', 1, 'nivel académico');
    $desde = entero_post('desde', 1, 'mes inicial');
    $hasta = entero_post('hasta', 1, 'mes final');
    $dia = entero_post('dia', 0, 'día de vencimiento');
    if ($desde > 12 || $hasta > 12 || $dia > 31) {
        denegar(400, 'Mes o día de vencimiento fuera de rango');
    }
    $precio = monto_post('precio', 0.01, 999999999999.99, 'precio');
    if (trim($_POST['mora'] ?? '') === '') { $_POST['mora'] = '0'; }
    $mora = monto_post('mora', 0, 999999999999.99, 'mora');
    responder_json($MPE->Generar_Pensiones($anio, $nivel, $desde, $hasta, $dia, $precio, $mora));
?>
