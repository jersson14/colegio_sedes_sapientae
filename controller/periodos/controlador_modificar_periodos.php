<?php
require '../../model/model_periodos.php';
$MPER = new Modelo_Periodos(); // Instanciamos

$periodos = json_decode($_POST['periodos'], true); // Decodificar JSON a un array PHP
$exito = true; // Bandera de éxito global
$registros_nuevos = false; // Bandera para detectar si algunos registros son nuevos

foreach ($periodos as $periodo) {
    // Llamar al método para registrar el periodo
    $resultado = $MPER->Modificar_Periodo(
        $periodo['año'],
        $periodo['tipo_periodo'],
        $periodo['periodo'],
        $periodo['fecha_inicio'],
        $periodo['fecha_fin']
    );

    if ($resultado == 1) {
        // Periodo registrado
        $registros_nuevos = true; // Detectar si al menos uno fue registrado
    } elseif ($resultado == 2) {
        // Periodo ya existe, no hacemos nada
        // No es necesario hacer nada aquí
    } else {
        // Si ocurre algún error, marcamos como fallo
        $exito = false;
        break;
    }
}

if ($exito) {
    if ($registros_nuevos) {
        echo 1; // Algunos registros fueron nuevos
    } else {
        echo 2; // No se registraron nuevos periodos
    }
} else {
    echo 0; // Error en el registro
}
?>
