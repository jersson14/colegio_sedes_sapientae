<?php
    require '../../model/model_asignatura_docente.php';
    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $consulta = $MASD->Listar_Asignatura_Docente();
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
