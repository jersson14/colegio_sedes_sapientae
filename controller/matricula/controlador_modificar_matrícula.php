<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $id = entero_post('id', 1, 'matrícula');
    $estu = entero_post('estu', 1, 'estudiante');
    $año = entero_post('año', 1, 'año académico');
    $aula = entero_post('aula', 1, 'aula');
    if (trim($_POST['admi'] ?? '') === '') { $_POST['admi'] = '0'; }
    $admi = monto_post('admi', 0, 999999999999.99, 'admisión');
    if (trim($_POST['nuevo'] ?? '') === '') { $_POST['nuevo'] = '0'; }
    $nuevo = monto_post('nuevo', 0, 999999999999.99, 'alumno nuevo');
    if (trim($_POST['matri'] ?? '') === '') { $_POST['matri'] = '0'; }
    $matri = monto_post('matri', 0, 999999999999.99, 'matrícula');
    $proce = strtoupper(htmlspecialchars($_POST['proce'],ENT_QUOTES,'UTF-8'));
    $pro = strtoupper(htmlspecialchars($_POST['pro'],ENT_QUOTES,'UTF-8'));
    $depa = strtoupper(htmlspecialchars($_POST['depa'],ENT_QUOTES,'UTF-8'));

    $consulta = $MMAT->Modificar_Matricula($id,$estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa,usuario_actual_id());
    echo $consulta;



?>