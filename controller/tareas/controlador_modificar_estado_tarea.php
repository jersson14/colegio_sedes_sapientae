<?php
require_once __DIR__ . '/../_seguridad.php';
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    // El docente solo cambia tareas de sus cursos
    if (rol_actual() === 'DOCENTE' && !$MTA->Tarea_Es_Del_Docente($id, usuario_actual_id())) {
        registrar_acceso_denegado('controller/tareas/controlador_modificar_estado_tarea.php', 'TAREA_AJENA');
        denegar(403, 'Esa tarea no es de sus cursos');
    }
    $estatus = strtoupper(htmlspecialchars($_POST['estatus'],ENT_QUOTES,'UTF-8'));

    $consulta = $MTA->Modificar_Tarea_Estatus($id,$estatus);
    echo $consulta;



?>