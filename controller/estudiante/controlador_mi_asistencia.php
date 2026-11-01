<?php
require_once __DIR__ . '/_matricula.php';
require_once __DIR__ . '/../../utilitario/parametros.php';
// Mi asistencia: cada día registrado del año, con los nombres de las marcas de Parámetros del sistema
estudiante_responder([
    'dias' => $MAT ? $ME->Asistencia((int) $MAT['matricula']) : [],
    'nombres' => parametros_asistencia(),
    'sabado' => parametro('clases_sabado') === '1',
    'hoy' => date('Y-m-d'),
]);
