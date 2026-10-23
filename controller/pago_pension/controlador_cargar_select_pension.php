<?php
require_once __DIR__ . '/../_seguridad.php';
  require '../../model/model_pago_pension.php';
  $MPP = new Modelo_Pago_Pension(); // Instanciamos
  
  // Con id_matri: pensiones del nivel de esa matrícula con su año y estado (pantalla de pagos)
  if (!empty($_POST['id_matri'])) {
      echo json_encode($MPP->Cargar_Pension_Matricula((int) $_POST['id_matri']));
  } elseif (isset($_POST['id']) && !empty($_POST['id'])) {
      $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
      $consulta = $MPP->Cargar_Pension($id);
      echo json_encode($consulta);
  } else {
      echo json_encode(["error" => "El parámetro id no está presente o está vacío"]);
  }
  
?>
