<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    exigir_matricula_propia((string)$id); // IDOR: id = matrícula

    $consulta = $MNOTAS->Cargar_bimestres_estudiante($id);
    echo json_encode($consulta);
 
?>
