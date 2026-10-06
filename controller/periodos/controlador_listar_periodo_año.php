<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_periodos.php';
    $MPERI = new Modelo_Periodos();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MPERI->Cargar_Id_Año($id);
    echo json_encode($consulta);
 
?>
