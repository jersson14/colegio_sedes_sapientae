<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    exigir_tarea_propia((string)$id); // IDOR
   

    $consulta = $MTA->Eliminar_Tareas($id);
    echo $consulta;



?>