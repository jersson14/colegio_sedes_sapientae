<?php
require '../../model/model_periodos.php'; // Incluye el modelo de periodos
$MPER = new Modelo_Periodos(); // Instancia del modelo

$periodos = json_decode($_POST['periodos'], true); // Decodificar JSON a un array PHP
$exito = true; // Bandera de éxito global
$ya_existen = false; // Bandera para detectar si algunos registros ya existen

foreach ($periodos as $periodo) {
    // Llama al procedimiento almacenado a través del modelo
    $resultado = $MPER->Registrar_Periodo(
        $periodo['año_escolar'],
        $periodo['tipo_periodo'],
        $periodo['periodo'],
        $periodo['fecha_inicio'],
        $periodo['fecha_fin']
    );

    if ($resultado == 2) {
        $ya_existen = true; // Detecta si al menos uno ya existe
    }

    if ($resultado != 1 && $resultado != 2) {
        $exito = false; // Si alguno falla, marca como fallo
        break;
    }
}

if ($exito) {
    if ($ya_existen) {
        echo 2; // Algunos registros ya existen
    } else {
        echo 1; // Todos los registros fueron insertados con éxito
    }
} else {
    echo 0; // Error en la inserción
}
?>
