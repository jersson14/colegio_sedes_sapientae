<?php
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MNOTAS->Cargar_aulas_por_docente($id);
    echo json_encode($consulta);
 
?>
