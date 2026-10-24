<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $seccion = strtoupper(htmlspecialchars($_POST['seccion'],ENT_QUOTES,'UTF-8'));
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));
    $estatus = strtoupper(htmlspecialchars($_POST['estatus'],ENT_QUOTES,'UTF-8'));

    // Capacidad: vacía = sin límite de vacantes
    $capacidad = trim($_POST['capacidad'] ?? '') === '' ? null : entero_post('capacidad', 1, 'capacidad');
    $consulta = $MAU->Modificar_Aulas($id,$grado,$seccion,$nivel,$descrip,$estatus,$capacidad);
    echo $consulta;



?>