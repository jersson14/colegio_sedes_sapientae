
<?php
    require '../../model/model_pago_pension.php';

    $MPP= new Modelo_Pago_Pension();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $consulta = $MPP->TraerMonto($id);
    echo json_encode($consulta);
   
?>