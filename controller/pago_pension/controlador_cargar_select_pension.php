<?php
  require '../../model/model_pago_pension.php';
  $MPP = new Modelo_Pago_Pension(); // Instanciamos
  
  if (isset($_POST['id']) && !empty($_POST['id'])) {
      $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
      $consulta = $MPP->Cargar_Pension($id);
      echo json_encode($consulta);
  } else {
      echo json_encode(["error" => "El parámetro id no está presente o está vacío"]);
  }
  
?>
