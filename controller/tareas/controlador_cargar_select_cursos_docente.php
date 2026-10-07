<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_docente_propio($id); // IDOR: el docente solo ve sus cursos
    $consulta = $MTA->Cargar_CursoDocente($id);
    echo json_encode($consulta);
 
?>
