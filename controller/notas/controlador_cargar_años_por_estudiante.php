<?php
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MNOTAS->Cargar_año_por_estudiante($id);
    echo json_encode($consulta);
 
?>
