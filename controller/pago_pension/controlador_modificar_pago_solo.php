<?php
require '../../model/model_pago_pension.php';
$MPP = new Modelo_Pago_Pension();
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $monto = strtoupper(htmlspecialchars($_POST['monto'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MPP->Modificar_Pago_pension($id,$monto,$descrip);
    echo $consulta;



?>