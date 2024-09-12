<?php
require '../../model/model_tareas.php';

$MTA = new Modelo_Tareas(); // Instanciamos

// Verificamos si los parámetros existen antes de usarlos
$id = isset($_POST['id']) ? htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8') : null;
$idpro = isset($_POST['idpro']) ? htmlspecialchars($_POST['idpro'], ENT_QUOTES, 'UTF-8') : null;

// Verificamos que ambos parámetros tengan valor antes de hacer la consulta
if ($id !== null && $idpro !== null) {
    $consulta = $MTA->Cargar_Id_Detalle_profesor($id, $idpro);
    echo json_encode($consulta);
} else {
    // Si falta algún parámetro, devolvemos un error
    echo json_encode(array('error' => 'Faltan parámetros requeridos'));
}
?>