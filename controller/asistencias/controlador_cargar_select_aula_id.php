<?php
    require '../../model/model_asistencia.php';
    $MASIS = new Modelo_Asistencia();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MASIS->Cargar_Aula($id);
    echo json_encode($consulta);
 
?>
