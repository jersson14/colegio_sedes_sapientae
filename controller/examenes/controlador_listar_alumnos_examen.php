<?php
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
    $consulta = $MEXA->Listar_alumnos_examenes($id);

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
