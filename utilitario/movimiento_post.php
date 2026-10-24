<?php
/**
 * Lectura común de los campos de un ingreso/egreso manual (Fase 5): fecha, forma de pago, caja/cuenta y referencia.
 * Requiere utilitario/validacion.php.
 */
function datos_forma_pago_post() {
    $fecha = trim($_POST['fecha'] ?? '') === '' ? date('Y-m-d') : fecha_post('fecha', 'fecha');
    return [
        'fecha'      => $fecha,
        'metodo'     => entero_post('metodo', 1, 'forma de pago'),
        'cuenta'     => filter_var($_POST['cuenta'] ?? null, FILTER_VALIDATE_INT) ?: null,
        'referencia' => strtoupper(texto_post('referencia', 60, false, 'referencia')),
    ];
}
