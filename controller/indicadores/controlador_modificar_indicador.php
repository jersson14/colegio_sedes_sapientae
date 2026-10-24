<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/datos_indicador.php';
    require '../../model/model_indicadores.php';
    $MIN = new Modelo_Indicadores();//Instaciamos
    $id = entero_post('id', 1, 'categoría');
    $esta = in_array($_POST['esta'] ?? '', ['ACTIVO', 'INACTIVO'], true) ? $_POST['esta'] : denegar(400, 'Estado inválido');
    // Un grupo no tiene grupo padre: el SP ignora el valor
    if (($_POST['padre'] ?? '') === '') { $_POST['padre'] = '1'; }
    [$tipo, $nombre, $descrip, $padre] = datos_indicador_post();
    echo $MIN->Modificar_Indicador($id, $tipo, $nombre, $descrip, $esta, $padre);
?>
