<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_ingresos.php';
    $MING = new Modelo_Ingresos();//Instaciamos
    $id = entero_post('id', 1, 'registro');
    $indi = entero_post('indi', 1, 'indicador');
    $cantidad = entero_post('cantidad', 1, 'cantidad');
    $monto = monto_post('monto', 0.01, 999999999999.99, 'monto');
    $obser = strtoupper(htmlspecialchars(texto_post('obser', 255, false, 'observación'),ENT_QUOTES,'UTF-8'));
    $usu = (int) usuario_actual_id();

    $consulta = $MING->Modificar_Ingresos($id,$indi,$cantidad,$monto,$obser,$usu);
    echo $consulta;



?>