<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos    $nota = strtoupper(htmlspecialchars($_POST['nota'],ENT_QUOTES,'UTF-8'));
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    exigir_envio_calificable((string)$id); // IDOR: envío de una tarea suya
    $nota = strtoupper(htmlspecialchars($_POST['nota'],ENT_QUOTES,'UTF-8'));
    $obser = strtoupper(htmlspecialchars($_POST['obser'],ENT_QUOTES,'UTF-8'));

    $consulta = $MTA->Registrar_calificación($id,$nota,$obser);
    echo $consulta;



?>