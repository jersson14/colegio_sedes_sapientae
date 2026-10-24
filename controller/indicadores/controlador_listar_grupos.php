<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_indicadores.php';
    $MIN = new Modelo_Indicadores();
    responder_json(['ok' => true, 'data' => $MIN->Listar_Grupos()]);
?>
