<?php
    require '../../model/model_componentes.php';
    $MCOM = new Modelo_Componentes();//Instaciamos
    $aula = htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8');

    $consulta = $MCOM->Listar_criterios_filtros($aula);
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
