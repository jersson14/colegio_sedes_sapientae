<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_indicadores.php';
    $MIN = new Modelo_Indicadores();//Instaciamos
    echo $MIN->Eliminar_Indicador(entero_post('id', 1, 'categoría'));
?>
