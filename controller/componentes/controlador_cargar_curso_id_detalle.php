<?php
    require '../../model/model_componentes.php';
    $MCOM = new Modelo_Componentes();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MCOM->Cargar_Id_Detalle($id);
    echo json_encode($consulta);
 
?>
