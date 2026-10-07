<?php

/**
 * Lint de sintaxis (php -l) de todo el PHP propio del proyecto.
 * Uso: php tools/lint.php   → código de salida 1 si algún archivo falla.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);
$excluir = '#^(vendor|node_modules|view/MPDF/vendor|plantilla|utilitario|storage)(/|$)|^controller/tareas/controller/#';

$archivos = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($raiz) + 1));
    if ($f->getExtension() === 'php' && !preg_match($excluir, $rel) && !str_starts_with($rel, '.')) {
        $archivos[] = $rel;
    }
}
sort($archivos);

$fallos = 0;
foreach ($archivos as $rel) {
    $salida = [];
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($raiz . '/' . $rel) . ' 2>&1', $salida, $codigo);
    if ($codigo !== 0) {
        $fallos++;
        echo "FALLA  $rel\n    " . implode("\n    ", $salida) . "\n";
    }
}
echo (count($archivos) - $fallos) . '/' . count($archivos) . " archivos PHP sin errores de sintaxis\n";
exit($fallos ? 1 : 0);
