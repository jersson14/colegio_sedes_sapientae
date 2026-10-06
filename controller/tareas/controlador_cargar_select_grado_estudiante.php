<?php
    require_once __DIR__ . '/../../core/guard.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_usuario_propio($id); // IDOR: el estudiante solo consulta lo suyo

    $consulta = $MTA->Cargar_aulas_por_estudiante($id);
    echo json_encode($consulta);
 
?>
