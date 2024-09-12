<?php
    require '../../model/model_asignatura_docente.php';
    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $id_docente = strtoupper(htmlspecialchars($_POST['id_docente'],ENT_QUOTES,'UTF-8'));
    $consulta = $MASD->Registrar_asignatura_docente($id_docente);
    echo $consulta;



?>