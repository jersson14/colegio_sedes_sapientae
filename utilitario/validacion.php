<?php
/**
 * Validación de datos de entrada en el servidor (Fase 2).
 * Requiere controller/_seguridad.php (usa denegar()). Ante un dato inválido responde 400 con el motivo.
 */

/** Entero >= $min. */
function entero_post($campo, $min = 1, $etiqueta = null) {
    $v = filter_var($_POST[$campo] ?? null, FILTER_VALIDATE_INT);
    if ($v === false || $v < $min) {
        denegar(400, 'Dato inválido: ' . ($etiqueta ?? $campo));
    }
    return $v;
}

/**
 * Monto con hasta 2 decimales entre $min y $max. Acepta "1500.50" o "1500,50" (sin separador de miles).
 * Devuelve el texto normalizado ("1500.50") para enviarlo a DECIMAL(14,2) sin errores de redondeo.
 */
function monto_post($campo, $min = 0.01, $max = 999999999999.99, $etiqueta = null) {
    $t = str_replace(',', '.', trim((string) ($_POST[$campo] ?? '')));
    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $t) || (float) $t < $min || (float) $t > $max) {
        denegar(400, 'Monto inválido: ' . ($etiqueta ?? $campo));
    }
    return number_format((float) $t, 2, '.', '');
}

/** Texto recortado, con largo máximo; si $requerido y está vacío responde 400. */
function texto_post($campo, $max = 255, $requerido = false, $etiqueta = null) {
    $t = trim((string) ($_POST[$campo] ?? ''));
    if ($requerido && $t === '') {
        denegar(400, 'Falta: ' . ($etiqueta ?? $campo));
    }
    if (mb_strlen($t, 'UTF-8') > $max) {
        denegar(400, ($etiqueta ?? $campo) . ': máximo ' . $max . ' caracteres');
    }
    return $t;
}

/** Correo electrónico válido (se guarda en minúsculas). Vacío solo si no es requerido. */
function email_post($campo, $requerido = true, $etiqueta = 'correo') {
    $t = strtolower(trim((string) ($_POST[$campo] ?? '')));
    if ($t === '' && !$requerido) {
        return '';
    }
    if (strlen($t) > 150 || !filter_var($t, FILTER_VALIDATE_EMAIL)) {
        denegar(400, 'Escriba un ' . $etiqueta . ' válido, por ejemplo nombre@gmail.com');
    }
    return $t;
}

/** Fecha AAAA-MM-DD válida. */
function fecha_post($campo, $etiqueta = null) {
    $t = trim((string) ($_POST[$campo] ?? ''));
    $d = DateTime::createFromFormat('Y-m-d', $t);
    if (!$d || $d->format('Y-m-d') !== $t) {
        denegar(400, 'Fecha inválida: ' . ($etiqueta ?? $campo));
    }
    return $t;
}

/** Rango de fechas de un reporte: desde ≤ hasta y como máximo 10 años. Devuelve [desde, hasta]. */
function rango_fechas_post($campo_desde = 'desde', $campo_hasta = 'hasta') {
    $desde = fecha_post($campo_desde, 'fecha desde');
    $hasta = fecha_post($campo_hasta, 'fecha hasta');
    if ($desde > $hasta) {
        denegar(400, 'La fecha "desde" no puede ser mayor que la fecha "hasta"');
    }
    if ((new DateTime($desde))->diff(new DateTime($hasta))->y >= 10) {
        denegar(400, 'El rango de fechas no puede superar 10 años');
    }
    return [$desde, $hasta];
}

/** Tipo de personal de la planilla (Fase 8B): DOCENTE (por defecto) o ADMINISTRATIVO. */
function tipo_personal_post($campo = 'tipo_personal') {
    $v = $_POST[$campo] ?? 'DOCENTE';
    if (!in_array($v, ['DOCENTE', 'ADMINISTRATIVO'], true)) {
        denegar(400, 'Tipo de personal inválido');
    }
    return $v;
}

/** Responde JSON y termina. */
function responder_json($datos) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos);
    exit;
}

/** Documento de identidad según el país configurado (utilitario/regional.php). Devuelve el valor normalizado. */
function documento_post($campo, $requerido = true, $etiqueta = null) {
    require_once __DIR__ . '/regional.php';
    $v = normalizar_documento($_POST[$campo] ?? '');
    if ($v === '') {
        if ($requerido) {
            denegar(400, 'Falta: ' . ($etiqueta ?? nombre_documento()));
        }
        return '';
    }
    if (!documento_valido($v)) {
        $c = config_regional();
        denegar(400, ($etiqueta ?? nombre_documento()) . ' no válido (ej. ' . $c['doc_ejemplo'] . ')');
    }
    return $v;
}

/** Teléfono: 6 a 15 dígitos; se admiten +, espacios, guiones y paréntesis. */
function telefono_post($campo, $requerido = false, $etiqueta = null) {
    require_once __DIR__ . '/regional.php';
    $v = trim((string) ($_POST[$campo] ?? ''));
    if ($v === '') {
        if ($requerido) {
            denegar(400, 'Falta: ' . ($etiqueta ?? 'teléfono'));
        }
        return '';
    }
    if (!telefono_valido($v)) {
        denegar(400, ($etiqueta ?? 'Teléfono') . ' no válido');
    }
    return $v;
}
