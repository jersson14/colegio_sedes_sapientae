
<?php
    require '../../model/model_matriculas.php';

    $MMAT= new Modelo_Matriculas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $consulta = $MMAT->TraerTipo($id);
    echo json_encode($consulta);
   
?>