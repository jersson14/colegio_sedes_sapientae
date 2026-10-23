<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_regional.php';
    $MR = new Modelo_Regional();
    // Zonas horarias de América para el selector
    $zonas = array_values(array_filter(timezone_identifiers_list(), function ($z) { return strpos($z, 'America/') === 0; }));
    responder_json([
        'ok'        => true,
        'config'    => $MR->Traer_Configuracion(),
        'paises'    => $MR->Listar_Paises(),
        'monedas'   => $MR->Listar_Monedas(),
        'zonas'     => $zonas,
        'historial' => $MR->Listar_Historial(),
    ]);
?>
