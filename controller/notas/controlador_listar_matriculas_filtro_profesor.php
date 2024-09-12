<?php
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $año = htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8');
    $grado = htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8');

    $consulta = $MNOTAS->Listar_alumnos_notas_profesor($año,$grado);
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
