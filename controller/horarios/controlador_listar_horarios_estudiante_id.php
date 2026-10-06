<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'ESTUDIANTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
    $id = id_usuario_propio($id); // IDOR: el estudiante solo consulta lo suyo
    $consulta = $MHR->Listar_pagos_por_id_estudiante($id);

    if ($consulta) {
    echo json_encode($consulta);
    } else {
    echo json_encode(array(
    "sEcho" => 1,
    "iTotalRecords" => "0",
    "iTotalDisplayRecords" => "0",
    "data" => array(),  // Cambiado de "aaData" a "data" para mantener consistencia
    "total_sub_total" => 0
    ));
    }
?>
