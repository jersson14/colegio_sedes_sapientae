<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/regional.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = entero_post('id', 1, 'tarea');
    // La calificación sigue la escala del colegio (número con sus decimales o literal como AD/A/B/C)
    $nota = nota_normalizada($_POST['nota'] ?? '');
    if ($nota === null) {
        denegar(400, 'La nota debe estar entre ' . descripcion_escala());
    }
    $obser = strtoupper(htmlspecialchars(trim($_POST['obser'] ?? ''), ENT_QUOTES, 'UTF-8'));

    echo $MTA->Registrar_calificación($id, $nota, $obser);
?>
