<?php
    require_once __DIR__ . '/../../core/guard.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_usuario_propio($id); // IDOR: la UI siempre envía el id propio

    $consulta = $MTA->Listar_alumnos_tareas_solo($id);
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
