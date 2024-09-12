<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();// Instanciamos
    $idarea = strtoupper(htmlspecialchars($_POST['idarea'],ENT_QUOTES,'UTF-8'));
    $consulta = $MU->Listar_notificacion_tramite($idarea);
    echo json_encode($consulta);
 
?>
