<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $seccion = strtoupper(htmlspecialchars($_POST['seccion'],ENT_QUOTES,'UTF-8'));
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    // Capacidad: vacía = sin límite de vacantes
    $capacidad = trim($_POST['capacidad'] ?? '') === '' ? null : entero_post('capacidad', 1, 'capacidad');
    $consulta = $MAU->Registrar_Aulas($grado,$seccion,$nivel,$descrip,$capacidad);
    echo $consulta;



?>