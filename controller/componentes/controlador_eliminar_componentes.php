<?php
    require '../../model/model_componentes.php';
    $MCOM = new Modelo_Componentes();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MCOM->Eliminar_componentes($id);
    echo $consulta;



?>