<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE');
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $nivel = htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8');
    $aula = htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8');
    exigir_aula_propia((string)$aula); // IDOR: aula donde dicta
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_usuario_propio($id); // IDOR: la UI siempre envía el id propio

    $consulta = $MNOTAS->Listar_criterios_nota_profesor($nivel,$aula,$id);
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
