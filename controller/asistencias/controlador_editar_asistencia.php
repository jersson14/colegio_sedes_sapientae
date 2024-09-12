<?php
require '../../model/model_asistencia.php';
$MASIS = new Modelo_Asistencia();

// Decodificar los registros enviados desde el frontend
$registros = json_decode($_POST['registros'], true);

// Variable para almacenar la respuesta global
$respuesta = 1;  // Inicialmente asumimos éxito

foreach ($registros as $registro) {
    $id_asis = $registro['id_asis'];
    $fecha = $registro['fecha'];
    $esta = $registro['esta'];
    $obse = $registro['obse'];
    
    // Intentar actualizar cada registro
    $resultado = $MASIS->Editar_Asistencia($id_asis, $fecha, $esta, $obse);
    
    // Si alguna actualización falla, marcamos la respuesta como 2 (error)
    if (!$resultado) {
        $respuesta = 2;
        break;  // Opcional: salir del bucle si hay un error
    }
}

// Retornar la respuesta global
echo $respuesta;
