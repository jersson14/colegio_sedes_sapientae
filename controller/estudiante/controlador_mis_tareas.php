<?php
require_once __DIR__ . '/_matricula.php';
// Mis tareas: todas las tareas de los cursos del estudiante en el año, con su entrega y nota
estudiante_responder([
    'tareas' => $MAT ? $ME->Tareas((int) $MAT['matricula'], (int) $MAT['aula'], (int) $MAT['anio_id']) : [],
    'ahora' => date('Y-m-d H:i:s'),
]);
