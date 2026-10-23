<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_egresos.php';
    $MEGR= new Modelo_Egresos();//Instaciamos
    $id = entero_post('id', 1, 'registro');
    $obser = strtoupper(htmlspecialchars(texto_post('obser', 255, true, 'motivo de la anulación'),ENT_QUOTES,'UTF-8'));
    $usu = (int) usuario_actual_id();

    $consulta = $MEGR->Anular_Egreso($id,$obser,$usu);
    echo $consulta;



?>