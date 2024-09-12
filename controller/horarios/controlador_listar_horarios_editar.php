<?php
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');

    $consulta = $MHR->Listar_componentes_horarios($id);
    if($consulta){
        echo json_encode($consulta);
    }else{
        echo '{
            "sEcho": 1,
            "iTotalRecords": "0",
            "iTotalDisplayRecords": "0",
            "aaData": []
        }';
    }
?>
