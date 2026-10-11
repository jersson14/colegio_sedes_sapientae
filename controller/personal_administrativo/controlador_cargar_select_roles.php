<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    $consulta = $MPAD->Cargar_Select_Roles();
    // Fase 5.9: la secretaría académica es un rol de instituto; en un colegio no se ofrece.
    require_once __DIR__ . '/../../core/institucion.php';
    if (!institucion_config()->matriculaPorUnidad()) {
        $consulta = array_values(array_filter($consulta, static fn ($rol) => $rol['tipo_rol'] !== 'SECRETARIA'));
    }
    echo json_encode($consulta);
 
?>
