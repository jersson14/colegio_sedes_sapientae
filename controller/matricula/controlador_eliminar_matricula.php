<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $id = entero_post('id', 1, 'matrícula');

    $consulta = $MMAT->Eliminar_matricula($id);
    echo $consulta;



?>