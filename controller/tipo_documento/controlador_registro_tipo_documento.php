<?php
    require '../../model/model_tipo_documento.php';
    $MTD = new Modelo_Tipo_Documento();
    $tipodoc = strtoupper(htmlspecialchars($_POST['tipodoc'],ENT_QUOTES,'UTF-8'));
    $requisi = strtoupper(htmlspecialchars($_POST['requisi'],ENT_QUOTES,'UTF-8'));
    $consulta = $MTD->Registrar_Tipo_Doc($tipodoc,$requisi);
    echo $consulta;



?>