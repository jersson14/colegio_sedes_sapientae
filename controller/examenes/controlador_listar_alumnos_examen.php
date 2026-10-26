<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/propiedad_docente.php';
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    // el docente solo maneja exámenes de sus cursos
    exigir_curso_propio($_POST['id'] ?? 0, 'controller/examenes/controlador_listar_alumnos_examen.php');
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
    $consulta = $MEXA->Listar_alumnos_examenes($id);

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
