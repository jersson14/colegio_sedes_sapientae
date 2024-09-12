<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $usu = htmlspecialchars($_POST['u'],ENT_QUOTES,'UTF-8');
    $con = htmlspecialchars($_POST['c'],ENT_QUOTES,'UTF-8');
    $consulta = $MU->Verificar_Usuario($usu,$con);
    if(count($consulta)>0){
        echo json_encode($consulta);
    }else{
        echo 0;
    }


?>