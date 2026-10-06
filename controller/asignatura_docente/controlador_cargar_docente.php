<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'AUXILIAR');
    require '../../model/model_asignatura_docente.php';
    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $consulta = $MASD->Cargar_Select_Docente();
    echo json_encode($consulta);
 
?>
