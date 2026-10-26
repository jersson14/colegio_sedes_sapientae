<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/claves.php';
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $id = (int) ($_POST['id'] ?? 0);
    // Contraseña puesta por el administrador (restablecer): mínimo 8 caracteres y temporal
    $con = clave_post('con');

    $consulta = $MU->Modificar_Usuario_Contra($id,$con);
    if ($consulta > 0) { marcar_clave_temporal($id); }
    echo $consulta;



?>