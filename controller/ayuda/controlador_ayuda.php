<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
// Ayuda contextual (Fase 13, paso 16): devuelve secciones de docs/guia_rapida.md en HTML.
// secciones = títulos "## ..." de la guía separados por "|"; solo se devuelven los que existen.
$pedidas = array_filter(array_map('trim', explode('|', (string) ($_POST['secciones'] ?? ''))));
if (!$pedidas || count($pedidas) > 5) {
    denegar(400, 'Indique la sección de ayuda');
}
$guia = file_get_contents(__DIR__ . '/../../docs/guia_rapida.md');
$partes = preg_split('/^## /m', str_replace("\r\n", "\n", $guia));
$html = '';
foreach ($pedidas as $titulo) {
    foreach ($partes as $p) {
        [$cab, $cuerpo] = array_pad(explode("\n", $p, 2), 2, '');
        if (trim($cab) === $titulo) {
            $html .= '<h5 class="ayuda-seccion">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h5>' . ayuda_markdown($cuerpo);
        }
    }
}
if ($html === '') {
    denegar(404, 'No hay ayuda para esta pantalla');
}
responder_json(['ok' => true, 'html' => $html]);

/** Markdown sencillo de la guía: ### títulos, listas con - o 1., **negrita**, `código` y párrafos. */
function ayuda_markdown($texto) {
    $linea_html = function ($t) {
        $t = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
        $t = preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $t);
        $t = preg_replace('/\*(.+?)\*/u', '<i>$1</i>', $t);
        return preg_replace('/`(.+?)`/u', '<code>$1</code>', $t);
    };
    $out = '';
    $pila = [];   // listas abiertas: [sangría, etiqueta]
    $parrafo = [];
    $cerrar_parrafo = function () use (&$parrafo, &$out, $linea_html) {
        if ($parrafo) { $out .= '<p>' . $linea_html(implode(' ', $parrafo)) . '</p>'; $parrafo = []; }
    };
    $cerrar_listas = function ($hasta) use (&$pila, &$out) {
        while ($pila && end($pila)[0] > $hasta) { $out .= '</li></' . array_pop($pila)[1] . '>'; }
    };
    foreach (explode("\n", $texto) as $l) {
        if (trim($l) === '') { $cerrar_parrafo(); continue; }
        if (preg_match('/^###\s+(.*)$/', $l, $m)) {
            $cerrar_parrafo(); $cerrar_listas(-1);
            $out .= '<h6 class="mt-3">' . $linea_html($m[1]) . '</h6>';
        } elseif (preg_match('/^(\s*)(-|\d+\.)\s+(.*)$/', $l, $m)) {
            $cerrar_parrafo();
            $sangria = strlen($m[1]);
            $tag = $m[2] === '-' ? 'ul' : 'ol';
            $cerrar_listas($sangria);
            if ($pila && end($pila)[0] === $sangria) {
                $out .= '</li><li>';
            } else {
                $out .= '<' . $tag . '><li>';
                $pila[] = [$sangria, $tag];
            }
            $out .= $linea_html($m[3]);
        } elseif ($pila) {
            $out .= ' ' . $linea_html(trim($l));   // continuación del punto de la lista
        } else {
            $parrafo[] = trim($l);
        }
    }
    $cerrar_parrafo(); $cerrar_listas(-1);
    return $out;
}
