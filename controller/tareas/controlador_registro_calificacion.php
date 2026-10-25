<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/regional.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = entero_post('id', 1, 'tarea');
    // El docente solo califica envíos de tareas de sus cursos
    if (rol_actual() === 'DOCENTE' && !$MTA->Envio_Es_Del_Docente($id, usuario_actual_id())) {
        registrar_acceso_denegado('controller/tareas/controlador_registro_calificacion.php', 'TAREA_AJENA');
        denegar(403, 'Esa tarea no es de sus cursos');
    }
    // La calificación sigue la escala del colegio (número con sus decimales o literal como AD/A/B/C)
    $nota = nota_normalizada($_POST['nota'] ?? '');
    if ($nota === null) {
        denegar(400, 'La nota debe estar entre ' . descripcion_escala());
    }
    $obser = strtoupper(htmlspecialchars(trim($_POST['obser'] ?? ''), ENT_QUOTES, 'UTF-8'));

    echo $MTA->Registrar_calificación($id, $nota, $obser);
?>
