<?php
    require_once __DIR__ . '/../../core/guard.php';
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = (string)$_SESSION['S_ID']; // IDOR: solo lo usa el panel para el propio usuario

    $consulta = $MU->Cargar_datos_usuario($id);
    echo json_encode($consulta);
 
?>
