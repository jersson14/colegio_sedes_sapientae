<?php
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $estatus = strtoupper(htmlspecialchars($_POST['estatus'],ENT_QUOTES,'UTF-8'));

    $consulta = $MTA->Modificar_Tarea_Estatus($id,$estatus);
    echo $consulta;



?>