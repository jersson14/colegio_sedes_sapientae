<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    $consulta = $MDO->Cargar_Select_Especialidad();
    echo json_encode($consulta);
 
?>
