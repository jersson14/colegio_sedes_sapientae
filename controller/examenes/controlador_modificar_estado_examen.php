<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/propiedad_docente.php';
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    // el docente solo maneja exámenes de sus cursos
    exigir_curso_propio(curso_del_examen($_POST['id'] ?? ''), 'controller/examenes/controlador_modificar_estado_examen.php');
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $estatus = strtoupper(htmlspecialchars($_POST['estatus'],ENT_QUOTES,'UTF-8'));

    $consulta = $MEXA->Modificar_Examen_Estatus($id,$estatus);
    echo $consulta;



?>