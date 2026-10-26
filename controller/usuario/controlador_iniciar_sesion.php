<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_usuario.php';
    $config = require __DIR__ . '/../../config/config.php';
    header('Content-Type: application/json; charset=utf-8');

    // Mismo escape que al registrar la contraseña (password_hash(htmlspecialchars(...)))
    $usu = htmlspecialchars(trim($_POST['u'] ?? ''), ENT_QUOTES, 'UTF-8');
    $con = htmlspecialchars($_POST['c'] ?? '', ENT_QUOTES, 'UTF-8');
    if ($usu === '' || $con === '') {
        echo json_encode(['ok' => false, 'motivo' => 'VACIO']);
        exit;
    }

    $MU = new Modelo_Usuario();
    $max = $config['login_max_intentos'];
    $minutos = $config['login_minutos_bloqueo'];
    if ($MU->Login_Bloqueado($usu, $max, $minutos)) {
        echo json_encode(['ok' => false, 'motivo' => 'BLOQUEADO', 'minutos' => $minutos]);
        exit;
    }

    $consulta = $MU->Verificar_Usuario($usu, $con);
    if (count($consulta) == 0) {
        $MU->Registrar_Intento_Fallido($usu, $_SERVER['REMOTE_ADDR'] ?? '');
        echo json_encode(['ok' => false, 'motivo' => 'CREDENCIALES']);
        exit;
    }

    $u = $consulta[0];
    if ($u['usu_estatus'] !== 'ACTIVO') {
        echo json_encode(['ok' => false, 'motivo' => 'INACTIVO']);
        exit;
    }
    $MU->Limpiar_Intentos($usu);

    // La sesión se arma en el servidor con los datos de la BD (antes los enviaba el navegador).
    // Los índices son las columnas de SP_VERIFICAR_USUARIO (UNION de docentes, personal y alumnos).
    $e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['S_ID'] = $e($u['usu_id']);
    $_SESSION['S_USU'] = $e($u['usu_usuario']);
    $_SESSION['S_NOMBRE'] = $e($u[0]);
    $_SESSION['S_COMPLETO'] = $e($u[2]);
    $_SESSION['S_ROL'] = $e($u['tipo_rol']);
    $_SESSION['S_FOTO'] = $e($u[3]);
    $_SESSION['S_MOVIL'] = $e($u[16]);
    $_SESSION['S_DIRECCION'] = $e($u[17]);
    $_SESSION['S_FECHANACIMIENTO'] = $e($u[18]);
    $_SESSION['S_EMAIL'] = $e($u['usu_email']);
    $_SESSION['S_DNI'] = $e($u[19]);
    // Contraseña temporal (puesta por el administrador): hasta cambiarla solo se muestra la pantalla de cambio
    $q = (new conexionBD())->conexionPDO()->prepare("SELECT debe_cambiar_clave FROM usuario WHERE usu_id = ?");
    $q->execute([$u['usu_id']]);
    if ((int) $q->fetchColumn() === 1) {
        $_SESSION['S_DEBE_CAMBIAR'] = 1;
    }

    echo json_encode(['ok' => true]);
?>
