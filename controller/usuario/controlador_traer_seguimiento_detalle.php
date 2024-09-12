<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();//Instaciamos
    $codigo = strtoupper(htmlspecialchars($_POST['codigo'],ENT_QUOTES,'UTF-8'));

    $consulta = $MU->Traer_Datos_Detalle_Seguimiento($codigo);
    echo json_encode($consulta);
 
?>
