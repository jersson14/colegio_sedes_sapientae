<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_empleado.php';

    $ME = new Modelo_Empleado();//Instaciamos
    $consulta = $ME->listar_total_Empleados();
    echo json_encode($consulta);

?>