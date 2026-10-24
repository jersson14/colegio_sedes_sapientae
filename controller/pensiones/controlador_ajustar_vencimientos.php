<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();
    // aplicar = 0: solo muestra las pensiones con vencimiento fuera del año y la fecha propuesta; 1: las corrige
    $anio = entero_post('anio', 1, 'año escolar');
    $aplicar = isset($_POST['aplicar']) && $_POST['aplicar'] === '1' ? 1 : 0;
    $filas = $MPE->Ajustar_Vencimientos($anio, $aplicar, usuario_actual_id());
    responder_json(['ok' => true, 'aplicado' => $aplicar, 'data' => $filas]);
?>
