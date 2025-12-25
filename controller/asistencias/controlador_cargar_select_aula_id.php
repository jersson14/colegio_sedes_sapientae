<?php
require '../../model/model_asistencia.php';

// Check if 'id' is set in the POST data
if (isset($_POST['id'])) {
    $MASIS = new Modelo_Asistencia(); // Instantiate the model
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
    $consulta = $MASIS->Cargar_Aula($id);
    echo json_encode($consulta);
} else {
    // If 'id' is not set, return an error message
    echo json_encode(array('error' => 'No se proporcionó un ID válido'));
}
?>