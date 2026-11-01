<?php
require_once __DIR__ . '/_matricula.php';
require_once __DIR__ . '/../../utilitario/libreta.php';
// Mis notas: la libreta del estudiante (mismo cálculo que la libreta impresa) con el detalle por criterio
$libreta = null;
if ($MAT) {
    $L = libreta_aula((int) $MAT['anio_id'], (int) $MAT['aula']);
    if ($L) {
        foreach ($L['estudiantes'] as $e) {
            if ((int) $e['matricula'] === (int) $MAT['matricula']) {
                unset($e['documento']);
                $libreta = ['periodos' => $L['periodos'], 'cursos' => $L['cursos'], 'yo' => $e,
                            'total_aula' => count($L['estudiantes']), 'escala' => $L['escala']];
            }
        }
    }
}
estudiante_responder(['libreta' => $libreta]);
