<?php
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MDO->Eliminar_Docente($id);
    echo $consulta;



?>