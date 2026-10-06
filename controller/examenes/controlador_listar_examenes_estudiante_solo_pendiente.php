<?php
    require_once __DIR__ . '/../../core/guard.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos


    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_usuario_propio($id); // IDOR: el estudiante solo consulta lo suyo

    $consulta = $MEXA->Listar_alumnos_examenes_id_solo_pendiente($id);
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
