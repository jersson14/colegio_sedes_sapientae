<?php
    require_once __DIR__ . '/../core/guard.php';
/**
 * Controlador para actualizar el estado de una solicitud
 */
require_once '../model/model_solicitudes.php';

$solicitud_model = new Solicitud_Model();

$id = $_POST['id'];
$estado = $_POST['estado'];
$observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : null;
$usuario_id = $_SESSION['S_ID'];

$resultado = $solicitud_model->Actualizar_Estado($id, $estado, $observaciones, $usuario_id);

if ($resultado['status'] === 'success') {
    echo 1;
} else {
    echo 0;
}
?>
