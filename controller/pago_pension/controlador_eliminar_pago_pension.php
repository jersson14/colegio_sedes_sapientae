<?php
  require '../../model/model_pago_pension.php';
  $MPP = new Modelo_Pago_Pension(); // Instanciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MPP->Eliminar_pago_pension($id);
    echo $consulta;



?>