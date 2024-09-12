<?php
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $matri = htmlspecialchars($_POST['matri'],ENT_QUOTES,'UTF-8');
    $bime = htmlspecialchars($_POST['bime'],ENT_QUOTES,'UTF-8');

    $consulta = $MNOTAS->Listar_criterios_nota_mostrar($matri,$bime);
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
