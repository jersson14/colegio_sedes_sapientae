<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    // El docente solo cambia tareas de sus cursos
    if (rol_actual() === 'DOCENTE' && !$MTA->Tarea_Es_Del_Docente($id, usuario_actual_id())) {
        registrar_acceso_denegado('controller/tareas/controlador_eliminar_tarea.php', 'TAREA_AJENA');
        denegar(403, 'Esa tarea no es de sus cursos');
    }
   

    $consulta = $MTA->Eliminar_Tareas($id);
    echo $consulta;



?>