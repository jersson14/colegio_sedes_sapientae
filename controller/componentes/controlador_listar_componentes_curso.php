<?php
    require '../../model/model_componentes.php';
    $MCOM = new Modelo_Componentes();//Instaciamos
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
    $consulta = $MCOM->Listar_compo_cursos($id);

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
