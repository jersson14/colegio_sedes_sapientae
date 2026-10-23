<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();//Instaciamos
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $mes = strtoupper(htmlspecialchars($_POST['mes'],ENT_QUOTES,'UTF-8'));
    $fecha = fecha_post('fecha', 'fecha de vencimiento');
    $precio = monto_post('precio', 0.01, 999999999999.99, 'precio');
    if (trim($_POST['mora'] ?? '') === '') { $_POST['mora'] = '0'; }
    $mora = monto_post('mora', 0, 999999999999.99, 'mora');

    $consulta = $MPE->Registrar_Pensiones($nivel,$mes,$fecha,$precio,$mora);
    echo $consulta;



?>