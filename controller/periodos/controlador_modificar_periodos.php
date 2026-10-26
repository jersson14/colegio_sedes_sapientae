<?php
require_once __DIR__ . '/../_seguridad.php';
require '../../model/model_periodos.php';
$MPER = new Modelo_Periodos(); // Instanciamos

$periodos = json_decode($_POST['periodos'] ?? '', true); // Decodificar JSON a un array PHP
if (!is_array($periodos) || !$periodos) {
    denegar(400, 'No hay periodos para guardar');
}
// La lista completa del año: cada periodo dentro del año escolar, inicio antes que fin y sin cruces entre ellos
require_once __DIR__ . '/../../utilitario/periodos.php';
validar_periodos((int) ($periodos[0]['año'] ?? 0), $periodos, false);
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
