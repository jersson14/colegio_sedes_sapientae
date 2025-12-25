<?php
    require '../../model/model_ingresos.php';
    $MING = new Modelo_Ingresos();//Instaciamos
    $indi = strtoupper(htmlspecialchars($_POST['indi'],ENT_QUOTES,'UTF-8'));
    $cantidad = strtoupper(htmlspecialchars($_POST['cantidad'],ENT_QUOTES,'UTF-8'));
    $monto = strtoupper(htmlspecialchars($_POST['monto'],ENT_QUOTES,'UTF-8'));
    $obse = strtoupper(htmlspecialchars($_POST['obse'],ENT_QUOTES,'UTF-8'));
    $usu = strtoupper(htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8'));

    $consulta = $MING->Registrar_Ingresos($indi,$cantidad,$monto,$obse,$usu);
    echo $consulta;



?>