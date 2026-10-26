<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/claves.php';
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
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
    $usu = strtoupper(htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8'));
    // El estudiante que ya tiene usuario (de un año anterior) lo conserva; si es nuevo, su contraseña inicial
    // debe tener al menos 8 caracteres y es temporal (la cambia en su primer ingreso)
    $q = (new conexionBD())->conexionPDO()->prepare("SELECT 1 FROM matricula WHERE id_alumno = ? AND usu_id IS NOT NULL LIMIT 1");
    $q->execute([$estu]);
    $usuario_nuevo = !$q->fetchColumn();
    $contra = $usuario_nuevo ? clave_post('contra', $usu) : '';

    $correo = strtoupper(htmlspecialchars($_POST['correo'],ENT_QUOTES,'UTF-8'));

    // Forma de pago del recibo de matrícula (se valida en el SP; código 5 si no es válida)
    $metodo = filter_var($_POST['metodo'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $cuenta = filter_var($_POST['cuenta'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $referencia = strtoupper(texto_post('referencia', 60, false, 'referencia'));
    $consulta = $MMAT->Registrar_Matricula($estu,$año,$aula,$admi,$nuevo,$matri,$proce,$pro,$depa,$usu,$contra,$correo,usuario_actual_id(),$metodo,$cuenta,$referencia);
    if ($consulta == 1 && $usuario_nuevo) { marcar_clave_temporal(null, trim($usu)); }
    echo $consulta;



?>