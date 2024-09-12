<?php
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $estu = strtoupper(htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8'));
    $año = strtoupper(htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8'));
    $aula = strtoupper(htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8'));
    $admi = strtoupper(htmlspecialchars($_POST['admi'],ENT_QUOTES,'UTF-8'));
    $nuevo = strtoupper(htmlspecialchars($_POST['nuevo'],ENT_QUOTES,'UTF-8'));
    $matri = strtoupper(htmlspecialchars($_POST['matri'],ENT_QUOTES,'UTF-8'));
    $proce = strtoupper(htmlspecialchars($_POST['proce'],ENT_QUOTES,'UTF-8'));
    $pro = strtoupper(htmlspecialchars($_POST['pro'],ENT_QUOTES,'UTF-8'));
    $depa = strtoupper(htmlspecialchars($_POST['depa'],ENT_QUOTES,'UTF-8'));

    $consulta = $MMAT->Modificar_Matricula($id,$estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa);
    echo $consulta;



?>