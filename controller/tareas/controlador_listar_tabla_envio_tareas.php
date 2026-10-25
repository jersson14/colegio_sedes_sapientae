<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = htmlspecialchars($_POST['id'] ?? '', ENT_QUOTES, 'UTF-8');
    // El docente solo ve los envíos de tareas de sus cursos
    if (rol_actual() === 'DOCENTE' && !$MTA->Tarea_Es_Del_Docente($id, usuario_actual_id())) {
        registrar_acceso_denegado('controller/tareas/controlador_listar_tabla_envio_tareas.php', 'TAREA_AJENA');
        denegar(403, 'Esa tarea no es de sus cursos');
    }
    $consulta = $MTA->Listar_tareas_enviadas($id);

    if ($consulta) {
    echo json_encode($consulta);
    } else {
    echo json_encode(array(
    "sEcho" => 1,
    "iTotalRecords" => "0",
    "iTotalDisplayRecords" => "0",
    "data" => array(),  // Cambiado de "aaData" a "data" para mantener consistencia
    "total_sub_total" => 0
    ));
    }
?>
