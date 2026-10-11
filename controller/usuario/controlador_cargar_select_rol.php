<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();//Instaciamos
    $consulta = $MU->Cargar_Select_Rol();
    // Fase 5.9: la secretaría académica es un rol de instituto; en un colegio no se ofrece.
    require_once __DIR__ . '/../../core/institucion.php';
    if (!institucion_config()->matriculaPorUnidad()) {
        $consulta = array_values(array_filter($consulta, static fn ($rol) => $rol['tipo_rol'] !== 'SECRETARIA'));
    }
    echo json_encode($consulta);
 
?>
