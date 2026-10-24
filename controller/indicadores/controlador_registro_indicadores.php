<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/datos_indicador.php';
    require '../../model/model_indicadores.php';
    $MIN = new Modelo_Indicadores();//Instaciamos
    [$tipo, $nombre, $descrip, $padre] = datos_indicador_post();
    echo $MIN->Registrar_Indicador($tipo, $nombre, $descrip, $padre);
?>
