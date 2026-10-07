<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/subidas.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// DATOS DE LA TAREA
$id = strtoupper(htmlspecialchars($_POST['id'] ?? '', ENT_QUOTES, 'UTF-8'));
$asig = strtoupper(htmlspecialchars($_POST['asig'] ?? '', ENT_QUOTES, 'UTF-8'));
exigir_curso_propio((string)$asig); // IDOR
$tema = strtoupper(htmlspecialchars($_POST['tema'] ?? '', ENT_QUOTES, 'UTF-8'));
$fecha = strtoupper(htmlspecialchars($_POST['fecha'] ?? '', ENT_QUOTES, 'UTF-8'));
$descrip = strtoupper(htmlspecialchars($_POST['descrip'] ?? '', ENT_QUOTES, 'UTF-8'));
$archivoactual = htmlspecialchars($_POST['archivoactual'] ?? '', ENT_QUOTES, 'UTF-8');
// IDOR: la tarea debe ser de un curso del docente y la carpeta a reemplazar sale de la BD.
$archivoactual = exigir_tarea_propia((string)$id, $archivoactual);

// Crear una carpeta única para este conjunto de archivos
$timestamp = time();
$carpeta = 'controller/tareas/documentos/' . $timestamp;
// Fase 0.3: tipos y nombres validados ANTES de crear carpetas o tocar la BD.
// (La ruta física es relativa a controller/tareas/, igual que antes.)
$documentos = documentos_validados('archivos');

if ($documentos) {
    // Solo se borra la carpeta anterior si tiene la forma que genera el sistema.
    $anterior = carpeta_tarea_valida($archivoactual);
    if ($anterior !== null) {
        $carpeta_anterior = 'controller/tareas/documentos/' . $anterior;
        if (is_dir($carpeta_anterior)) {
            foreach (glob($carpeta_anterior . '/*') ?: [] as $archivo) {
                if (is_file($archivo)) {
                    unlink($archivo);
                }
            }
            @rmdir($carpeta_anterior);
        }
    }

    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }
    foreach ($documentos as $doc) {
        if (!move_uploaded_file($doc['tmp'], $carpeta . '/' . $doc['nombre'])) {
            echo "Error al mover el archivo: " . $doc['nombre'];
            exit;
        }
    }
    $ruta_carpeta = $carpeta;
} else {
    // Si no hay archivos, mantenemos la ruta anterior
    $ruta_carpeta = $archivoactual;
}

// Actualizar tarea en la base de datos con la ruta de la carpeta
$consulta = $MTA->Modificar_Tarea($id, $asig, $tema, $fecha, $descrip, $ruta_carpeta); // Pasar la ruta de la carpeta

echo $consulta;
?>
