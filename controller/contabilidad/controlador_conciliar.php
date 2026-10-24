<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_contabilidad.php';
    $MC = new Modelo_Contabilidad();
    // Conciliación bancaria: marcar/desmarcar un movimiento como verificado contra el extracto
    $r = $MC->Conciliar(entero_post('id', 1, 'movimiento'), !empty($_POST['valor']) ? 1 : 0);
    responder_json(['ok' => (int) $r['cambiados'] > 0]);
?>
