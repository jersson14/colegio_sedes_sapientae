<?php
    require '../../model/model_aula_horas.php';
    $MAH = new Modelo_aula_Horas();//Instaciamos

$componentes = json_decode($_POST['componentes'], true); // Decodificar JSON a un array PHP
$exito = true; // Bandera de éxito global
$ya_existen = false; // Bandera para detectar si algunos registros ya existen

foreach ($componentes as $componente) {
    $resultado = $MAH->Registrar_Aulas_horas(
        $componente['año'],
        $componente['aula'],
        $componente['turno'],
        $componente['inicio'],
        $componente['fin']
    );

    if ($resultado == 2) {
        $ya_existen = true; // Detectar si al menos uno ya existe
    }

    if ($resultado != 1 && $resultado != 2) {
        $exito = false; // Si alguno falla, marcamos como fallo
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
