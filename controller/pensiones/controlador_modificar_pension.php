<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();//Instaciamos
    $id = entero_post('id', 1, 'pensión');
    $anio = entero_post('anio', 1, 'año escolar');
    $nivel = entero_post('nivel', 1, 'nivel académico');
    $mes = strtoupper(trim($_POST['mes'] ?? ''));
    $fecha = fecha_post('fecha', 'fecha de vencimiento');
    $precio = monto_post('precio', 0.01, 999999999999.99, 'precio');
    if (trim($_POST['mora'] ?? '') === '') { $_POST['mora'] = '0'; }
    $mora = monto_post('mora', 0, 999999999999.99, 'mora');

    echo $MPE->Modificar_Pensiones($id, $anio, $nivel, $mes, $fecha, $precio, $mora);
?>
