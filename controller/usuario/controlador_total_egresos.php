<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_usuario.php';

    $MUSU= new Modelo_Usuario();//Instaciamos
    $consulta = $MUSU->listar_total_egresos_hoy();
    echo json_encode($consulta);

?>