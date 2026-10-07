<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    $asig = strtoupper(htmlspecialchars($_POST['asig'],ENT_QUOTES,'UTF-8'));
    exigir_curso_propio((string)$asig); // IDOR: curso del docente
    $tema = strtoupper(htmlspecialchars($_POST['tema'],ENT_QUOTES,'UTF-8'));
    $fecha = strtoupper(htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MEXA->Registrar_Examenes($asig,$tema,$fecha,$descrip);
    echo $consulta;



?>