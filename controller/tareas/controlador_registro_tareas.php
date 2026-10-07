<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/subidas.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// DATOS DE REMITENTE
$asig = strtoupper(htmlspecialchars($_POST['asig'], ENT_QUOTES, 'UTF-8'));
exigir_curso_propio((string)$asig); // IDOR: curso del docente
$tema = strtoupper(htmlspecialchars($_POST['tema'], ENT_QUOTES, 'UTF-8'));
$fecha = strtoupper(htmlspecialchars($_POST['fecha'], ENT_QUOTES, 'UTF-8'));
$descrip = strtoupper(htmlspecialchars($_POST['descrip'], ENT_QUOTES, 'UTF-8'));

// Crear una carpeta única para este conjunto de archivos
$timestamp = time();
$carpeta = 'controller/tareas/documentos/tarea_alumnos_' . $timestamp; // Cambié la ruta base
// Fase 0.3: tipos y nombres validados ANTES de crear la carpeta o tocar la BD.
$documentos = documentos_validados('archivos');

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}
foreach ($documentos as $doc) {
    if (!move_uploaded_file($doc['tmp'], $carpeta . '/' . $doc['nombre'])) {
        echo "Error al mover el archivo: " . $doc['nombre'];
        exit;
    }
}

// Registrar la tarea en la base de datos
$consulta = $MTA->Registrar_Tarea($asig, $tema, $fecha, $descrip, $carpeta); // Pasar solo la ruta de la carpeta

echo $consulta;
?>
