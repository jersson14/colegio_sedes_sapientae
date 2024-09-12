<?php
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $estu = strtoupper(htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8'));
    $año = strtoupper(htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8'));
    $aula = strtoupper(htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8'));
    $admi = strtoupper(htmlspecialchars($_POST['admi'],ENT_QUOTES,'UTF-8'));
    $nuevo = strtoupper(htmlspecialchars($_POST['nuevo'],ENT_QUOTES,'UTF-8'));
    $matri = strtoupper(htmlspecialchars($_POST['matri'],ENT_QUOTES,'UTF-8'));
    $proce = strtoupper(htmlspecialchars($_POST['proce'],ENT_QUOTES,'UTF-8'));
    $pro = strtoupper(htmlspecialchars($_POST['pro'],ENT_QUOTES,'UTF-8'));
    $depa = strtoupper(htmlspecialchars($_POST['depa'],ENT_QUOTES,'UTF-8'));
    $usu = strtoupper(htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8'));
    $contra = password_hash(htmlspecialchars($_POST['contra'],ENT_QUOTES,'UTF-8'),PASSWORD_DEFAULT,['cost'=>12]);

    $correo = strtoupper(htmlspecialchars($_POST['correo'],ENT_QUOTES,'UTF-8'));

    $consulta = $MMAT->Registrar_Matricula($estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa,$usu,$contra,$correo);
    echo $consulta;



?>