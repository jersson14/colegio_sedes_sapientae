<?php
require '../../model/model_componentes.php';
$MCOM = new Modelo_Componentes(); // Instanciamos

$componentes = json_decode($_POST['componentes'], true); // Decodificar JSON a un array PHP
$exito = true; // Bandera de éxito global
$registros_nuevos = false; // Bandera para detectar si algunos registros son nuevos

foreach ($componentes as $componente) {
    // Llamar al método para registrar el componente
    $resultado = $MCOM->Modificar_Componente(
        $componente['id_asignatura'],
        $componente['componente'],
        $componente['observacion']
    );

    if ($resultado == 1) {
        // Componente registrado
        $registros_nuevos = true; // Detectar si al menos uno fue registrado
    } elseif ($resultado == 0) {
        // Si el componente ya existe, no hacemos nada
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
        echo 2; // No se registraron nuevos componentes
    }
} else {
    echo 0; // Error en el registro
}
?>
