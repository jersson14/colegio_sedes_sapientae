<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/propiedad_docente.php';
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    // el docente solo maneja exámenes de sus cursos
    exigir_curso_propio(curso_del_examen($_POST['id'] ?? ''), 'controller/examenes/controlador_modificar_examen.php');
    exigir_curso_propio($_POST['asig'] ?? 0, 'controller/examenes/controlador_modificar_examen.php');
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $asig = strtoupper(htmlspecialchars($_POST['asig'],ENT_QUOTES,'UTF-8'));
    $tema = strtoupper(htmlspecialchars($_POST['tema'],ENT_QUOTES,'UTF-8'));
    $fecha = strtoupper(htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MEXA->Modificar_Examenes($id,$asig,$tema,$fecha,$descrip);
    echo $consulta;



?>