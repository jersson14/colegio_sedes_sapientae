<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_egresos.php';
    $MEGR= new Modelo_Egresos();//Instaciamos
    $indi = entero_post('indi', 1, 'indicador');
    $cantidad = entero_post('cantidad', 1, 'cantidad');
    $monto = monto_post('monto', 0.01, 999999999999.99, 'monto');
    $obse = strtoupper(htmlspecialchars(texto_post('obse', 255, false, 'observación'),ENT_QUOTES,'UTF-8'));
    $usu = (int) usuario_actual_id();

    $consulta = $MEGR->Registrar_Egreso($indi,$cantidad,$monto,$obse,$usu);
    echo $consulta;



?>