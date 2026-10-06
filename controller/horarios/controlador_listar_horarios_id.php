<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'ESTUDIANTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    exigir_aula_propia((string)$id); // IDOR: id = aula

    $consulta = $MHR->Cargar_Id_aula_horarios($id);
    echo json_encode($consulta);
 
?>
