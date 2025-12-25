<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $idrol = htmlspecialchars($_POST['idrol'],ENT_QUOTES,'UTF-8');

    $consulta = $MU->Listar_usuarios_filtro($idrol);
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
