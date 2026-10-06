<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $año = htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8');
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_usuario_propio($id); // IDOR: el estudiante solo consulta lo suyo

    $consulta = $MNOTAS->Listar_alumnos_notas_alumnos($año,$id);
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
