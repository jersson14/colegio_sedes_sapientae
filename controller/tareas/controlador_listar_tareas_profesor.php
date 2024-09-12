<?php
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $año = htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8');
    $grado = htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8');
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MTA->Listar_alumnos_tareas($año,$grado,$id);
    if($consulta){
        echo json_encode($consulta);
    }else{
        echo '{
            "sEcho": 1,
            "iTotalRecords": "0",
            "iTotalDisplayRecords": "0",
            "aaData": []
        }';
    }
?>
