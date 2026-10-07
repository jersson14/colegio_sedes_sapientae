<?php
    require '../../model/model_usuario.php';
    require '../../core/sesion.php';
    require '../../core/limite_login.php';
    $MU = new Modelo_Usuario();
    $usu = htmlspecialchars($_POST['u'] ?? '',ENT_QUOTES,'UTF-8');
    $con = htmlspecialchars($_POST['c'] ?? '',ENT_QUOTES,'UTF-8');

    // H-07: límite de intentos, antes de tocar la BD (429 + Retry-After).
    $espera = limite_bloqueo_restante($usu);
    if ($espera > 0) {
        http_response_code(429);
        header('Retry-After: ' . $espera);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => 'Demasiados intentos fallidos. Intenta de nuevo en '
            . (int)ceil($espera / 60) . ' minuto(s).']));
    }

    $consulta = $MU->Verificar_Usuario($usu,$con);

    // Respuesta: 0 = credenciales incorrectas, 2 = usuario inactivo, 1 = sesión creada.
    // Ya no se devuelve la fila completa (incluía el hash de la contraseña).
    if(count($consulta)==0){
        limite_registrar_fallo($usu);
        echo 0;
    }else if($consulta[0]['usu_estatus']=="INACTIVO"){
        echo 2;
    }else{
        limite_registrar_exito($usu);
        sesion_crear($consulta[0]);
        echo 1;
    }
?>
