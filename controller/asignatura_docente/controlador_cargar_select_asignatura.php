<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_asignatura_docente.php';
    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MASD->Cargar_Select_Asignatura($id);
    echo json_encode($consulta);
 
?>
