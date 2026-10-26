<?php
/**
 * Apariencia del colegio (Configuración > Apariencia): colores, estilo del menú, logo, nombre y lema.
 * tema_actual() lee la tabla empresa; tema_css_variables() escribe las variables CSS que usa css/tema.css.
 * Los tonos derivados (oscuro, suave) y el color de texto legible sobre cada color se calculan aquí,
 * así cualquier color que elija el colegio mantiene el contraste.
 */
require_once __DIR__ . '/../model/model_conexion.php';

const TEMA_DEFECTO = ['color_primario' => '#7A1F2B', 'color_acento' => '#C9A227', 'estilo_menu' => 'COLOR'];

/** Combinaciones sugeridas en la pantalla de apariencia. */
const TEMA_PRESETS = [
    ['nombre' => 'Escudo: granate y dorado', 'color_primario' => '#7A1F2B', 'color_acento' => '#C9A227'],
    ['nombre' => 'Azul marino y dorado', 'color_primario' => '#1B2F55', 'color_acento' => '#D4A72C'],
    ['nombre' => 'Verde institucional', 'color_primario' => '#1E5B3A', 'color_acento' => '#E0B03B'],
    ['nombre' => 'Azul clásico (anterior)', 'color_primario' => '#0A5D86', 'color_acento' => '#1FA0E0'],
    ['nombre' => 'Vino y plata', 'color_primario' => '#5B1A3A', 'color_acento' => '#B8BEC6'],
];

function tema_color_valido($c) {
    return is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c);
}

function tema_rgb($hex) {
    return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
}

function tema_hex(array $rgb) {
    return sprintf('#%02X%02X%02X', ...array_map(function ($v) { return max(0, min(255, (int) round($v))); }, $rgb));
}

/** Mezcla $hex con $otro en la proporción $t (0 = $hex, 1 = $otro). */
function tema_mezclar($hex, $otro, $t) {
    $a = tema_rgb($hex);
    $b = tema_rgb($otro);
    return tema_hex([$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t, $a[2] + ($b[2] - $a[2]) * $t]);
}

/** Luminancia relativa WCAG. */
function tema_luminancia($hex) {
    $c = array_map(function ($v) {
        $v /= 255;
        return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    }, tema_rgb($hex));
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function tema_contraste($a, $b) {
    $la = tema_luminancia($a);
    $lb = tema_luminancia($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

/** Texto legible sobre un fondo: blanco o un casi negro, el de mayor contraste. */
function tema_texto_sobre($fondo) {
    return tema_contraste($fondo, '#FFFFFF') >= tema_contraste($fondo, '#1A1A1A') ? '#FFFFFF' : '#1A1A1A';
}

/** Datos de apariencia del colegio (con valores por defecto si aún no se configuró). */
function tema_actual() {
    static $tema = null;
    if ($tema !== null) {
        return $tema;
    }
    $fila = [];
    try {
        $pdo = (new conexionBD())->conexionPDO();
        $fila = $pdo->query("SELECT * FROM empresa ORDER BY empresa_id LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // sin base no se cae la página: se usan los colores por defecto
    }
    $p = tema_color_valido($fila['color_primario'] ?? null) ? strtoupper($fila['color_primario']) : TEMA_DEFECTO['color_primario'];
    $a = tema_color_valido($fila['color_acento'] ?? null) ? strtoupper($fila['color_acento']) : TEMA_DEFECTO['color_acento'];
    $tema = [
        'nombre' => $fila['emp_razon'] ?? '',
        'lema' => $fila['lema'] ?? '',
        'logo' => $fila['emp_logo'] ?? '',
        'estilo_menu' => in_array($fila['estilo_menu'] ?? '', ['COLOR', 'OSCURO', 'CLARO'], true) ? $fila['estilo_menu'] : TEMA_DEFECTO['estilo_menu'],
        'primario' => $p,
        'acento' => $a,
    ];
    return $tema;
}

/** Variables CSS del tema (todas derivadas de los dos colores elegidos). */
function tema_variables(array $t) {
    $p = $t['primario'];
    $a = $t['acento'];
    $oscuro = tema_mezclar($p, '#000000', 0.28);
    $menu = ['COLOR' => $oscuro, 'OSCURO' => '#1F2226', 'CLARO' => '#FFFFFF'][$t['estilo_menu']];
    return [
        '--tema-primario' => $p,
        '--tema-primario-texto' => tema_texto_sobre($p),
        '--tema-primario-oscuro' => $oscuro,
        '--tema-primario-claro' => tema_mezclar($p, '#FFFFFF', 0.88),
        '--tema-primario-borde' => tema_mezclar($p, '#FFFFFF', 0.70),
        '--tema-acento' => $a,
        '--tema-acento-texto' => tema_texto_sobre($a),
        '--tema-acento-claro' => tema_mezclar($a, '#FFFFFF', 0.82),
        '--tema-acento-oscuro' => tema_mezclar($a, '#000000', 0.35),
        // texto del color principal sobre fondo blanco (títulos, enlaces): se oscurece si no llega a 4.5:1
        '--tema-tinta' => tema_contraste($p, '#FFFFFF') >= 4.5 ? $p : $oscuro,
        '--tema-menu' => $menu,
        '--tema-menu-texto' => tema_texto_sobre($menu),
        '--tema-menu-texto-suave' => tema_mezclar(tema_texto_sobre($menu), $menu, 0.30),
        '--tema-menu-activo' => $t['estilo_menu'] === 'CLARO' ? $p : $a,
        '--tema-menu-activo-texto' => tema_texto_sobre($t['estilo_menu'] === 'CLARO' ? $p : $a),
        '--tema-fondo' => tema_mezclar($p, '#F6F5F2', 0.94),
    ];
}

/** <style> con las variables del tema, para el <head>. */
function tema_css_variables(?array $t = null) {
    $t = $t ?? tema_actual();
    $css = '';
    foreach (tema_variables($t) as $k => $v) {
        $css .= "$k:$v;";
    }
    return '<style id="tema-variables">:root{' . $css . '}</style>';
}
