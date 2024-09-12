<?php
    require '../../model/model_tipo_documento.php';
    $MTD = new Modelo_Tipo_Documento();
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));
    $requisi = strtoupper(htmlspecialchars($_POST['requisi'],ENT_QUOTES,'UTF-8'));

    $consulta = $MTD->Modificar_Tipo_Documento($id,$tipo,$esta,$requisi);
    echo $consulta;



?>