<?php
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $consulta = $MTA->Cargar_CursoDocente($id);
    echo json_encode($consulta);
 
?>
