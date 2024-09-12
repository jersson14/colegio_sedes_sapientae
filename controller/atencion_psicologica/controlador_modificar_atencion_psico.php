<?php
    require '../../model/model_atencion_psico.php';
    $MAPSI = new Modelo_Atencion_Psico();//Instaciamos}

    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $estu = strtoupper(htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8'));
    $motivo = strtoupper(htmlspecialchars($_POST['motivo'],ENT_QUOTES,'UTF-8'));
    $diagno = strtoupper(htmlspecialchars($_POST['diagno'],ENT_QUOTES,'UTF-8'));
    $observa = strtoupper(htmlspecialchars($_POST['observa'],ENT_QUOTES,'UTF-8'));
    $idusu = strtoupper(htmlspecialchars($_POST['idusu'],ENT_QUOTES,'UTF-8'));

    $consulta = $MAPSI->Modificar_Atencion_Psico($id,$estu,$motivo,$diagno,$observa,$idusu);
    echo $consulta;



?>