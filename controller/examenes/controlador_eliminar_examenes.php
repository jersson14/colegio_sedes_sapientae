<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/propiedad_docente.php';
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    // el docente solo maneja exámenes de sus cursos
    exigir_curso_propio(curso_del_examen($_POST['id'] ?? ''), 'controller/examenes/controlador_eliminar_examenes.php');
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));


    $consulta = $MEXA->Eliminar_Examen($id);
    echo $consulta;



?>