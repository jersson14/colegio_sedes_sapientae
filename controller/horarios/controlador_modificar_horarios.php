<?php
require '../../model/model_horarios.php';
$MH = new Modelo_Horarios();

$horas_aula = json_decode($_POST['componentes'], true); // Decodificar JSON a un array PHP
$exito = true; // Bandera de éxito global
$registros_nuevos = false; // Bandera para detectar si algunos registros son nuevos

foreach ($horas_aula as $hora) {
    // Llamar al método para modificar el registro de hora y aula
    $resultado = $MH->Modificar_Horario(
        $hora['idhora'],   // ID de la hora
        $hora['idasig'],   // ID de la asignatura
        $hora['dia']       // Día del horario
    );

    if ($resultado == 1) {
        // Registro de hora y aula modificado
        $registros_nuevos = true; // Detectar si al menos uno fue registrado
    } elseif ($resultado == 0) {
        // Si el registro ya existe o no se realizó ninguna modificación, no hacemos nada
    } else {
        // Si ocurre algún error, marcamos como fallo
        $exito = false;
        break;
    }
}

if ($exito) {
    if ($registros_nuevos) {
        echo 1; // Algunos registros fueron modificados
    } else {
        echo 2; // No se modificaron nuevos registros
    }
} else {
    echo 0; // Error en la modificación
}
?>
