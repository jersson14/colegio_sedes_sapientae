<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/movimiento_post.php';
    require '../../model/model_egresos.php';
    $MEGR= new Modelo_Egresos();//Instaciamos
    $id = entero_post('id', 1, 'registro');
    $indi = entero_post('indi', 1, 'indicador');
    $cantidad = entero_post('cantidad', 1, 'cantidad');
    $monto = monto_post('monto', 0.01, 999999999999.99, 'monto');
    $obser = strtoupper(htmlspecialchars(texto_post('obser', 255, false, 'observación'),ENT_QUOTES,'UTF-8'));
    $usu = (int) usuario_actual_id();
    $fp = datos_forma_pago_post();
    $benef = strtoupper(texto_post('beneficiario', 150, false, 'beneficiario'));
    $comprob = strtoupper(texto_post('comprobante', 30, false, 'n.° de comprobante'));

    responder_json($MEGR->Modificar_Egresos($id,$indi,$cantidad,$monto,$obser,$usu,$fp['fecha'],$fp['metodo'],$fp['cuenta'],$fp['referencia'],$benef,$comprob));
?>
