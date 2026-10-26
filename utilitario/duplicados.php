<?php
/**
 * Aviso de posibles duplicados (Fase 13): misma persona registrada dos veces con otro documento
 * (suele ser un error de tipeo en el documento). Devuelve el documento del registro parecido o null.
 * El formulario pregunta "¿Es otra persona?" y, si se confirma, vuelve a enviar con confirmar_duplicado=1.
 */
require_once __DIR__ . '/../model/model_conexion.php';

function posible_duplicado_alumno($nombre, $apepa, $apema, $fecha, $dni) {
    $q = (new conexionBD())->conexionPDO()->prepare("SELECT alum_dni FROM alumnos
          WHERE alum_nombre = ? AND alum_apepat = ? AND alum_apemat = ? AND alum_fechanacimiento = ? AND alum_dni <> ? LIMIT 1");
    $q->execute([$nombre, $apepa, $apema, $fecha, $dni]);
    return $q->fetchColumn() ?: null;
}

function posible_duplicado_docente($nombre, $apellidos, $fecha, $dni) {
    $q = (new conexionBD())->conexionPDO()->prepare("SELECT docente_dni FROM docentes
          WHERE docente_nombre = ? AND docente_apelli = ? AND docente_fechanacimiento = ? AND docente_dni <> ? LIMIT 1");
    $q->execute([$nombre, $apellidos, $fecha, $dni]);
    return $q->fetchColumn() ?: null;
}

/** Corta el registro con "7|documento" si hay un parecido y aún no se confirmó que es otra persona. */
function exigir_confirmar_duplicado($documento_parecido) {
    if ($documento_parecido !== null && empty($_POST['confirmar_duplicado'])) {
        echo '7|' . $documento_parecido;
        exit;
    }
}
