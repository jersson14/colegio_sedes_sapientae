<?php
    require '../../model/model_usuario.php';
    require '../../core/sesion.php';
    $MU = new Modelo_Usuario();
    $usu = htmlspecialchars($_POST['u'] ?? '',ENT_QUOTES,'UTF-8');
    $con = htmlspecialchars($_POST['c'] ?? '',ENT_QUOTES,'UTF-8');
    $consulta = $MU->Verificar_Usuario($usu,$con);

    // Respuesta: 0 = credenciales incorrectas, 2 = usuario inactivo, 1 = sesión creada.
    // Ya no se devuelve la fila completa (incluía el hash de la contraseña).
    if(count($consulta)==0){
        echo 0;
    }else if($consulta[0]['usu_estatus']=="INACTIVO"){
        echo 2;
    }else{
        sesion_crear($consulta[0]);
        echo 1;
    }
?>
