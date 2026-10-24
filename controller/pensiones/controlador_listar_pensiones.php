<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();
    // 0 = todos los años / niveles
    $anio = isset($_POST['anio']) ? entero_post('anio', 0, 'año escolar') : 0;
    $nivel = isset($_POST['nivel']) ? entero_post('nivel', 0, 'nivel') : 0;
    responder_json(['data' => $MPE->Listar_Pensiones($anio, $nivel)]);
?>
