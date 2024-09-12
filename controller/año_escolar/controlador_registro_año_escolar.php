<?php
    require '../../model/model_año.php';
    $MANIO = new Modelo_Años();//Instaciamos
    $año = strtoupper(htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8'));
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $inicio = strtoupper(htmlspecialchars($_POST['inicio'],ENT_QUOTES,'UTF-8'));
    $fin = strtoupper(htmlspecialchars($_POST['fin'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MANIO->Registrar_Años($año,$nombre,$inicio,$fin,$descrip);
    echo $consulta;



?>