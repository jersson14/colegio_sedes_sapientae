<?php
/**
 * Verifica que todo controlador incluya core/guard.php (Fase 0.2).
 * Uso:  php tools/verificar_guard.php     → código de salida 1 si falta alguno.
 *
 * Al agregar un endpoint público, decláralo en $publicos y justifícalo.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);
$guard = realpath($raiz . '/core/guard.php');

$publicos = [
    'controller/usuario/controlador_iniciar_sesion.php', // login
    'controller/usuario/controlador_cerrar_sesion.php',  // logout
    'controller/controlador_solicitudes.php',            // formulario de la landing
];
// Carpeta de subidas: no contiene controladores (se trata en la Fase 0.3).
$excluir = 'controller/tareas/controller/';

$fallos = [];
$total = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/controller'));
foreach ($it as $archivo) {
    if ($archivo->getExtension() !== 'php') {
        continue;
    }
    $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen($raiz) + 1));
    if (in_array($rel, $publicos, true) || str_starts_with($rel, $excluir)) {
        continue;
    }
    $total++;
    $fuente = file_get_contents($archivo->getPathname());
    if (!preg_match("#^(?:\xEF\xBB\xBF)?<\?php\s+require_once __DIR__ \. '([^']+)';#", $fuente, $m)) {
        $fallos[] = "$rel: no incluye el guard como primera instrucción";
    } elseif (realpath($archivo->getPath() . $m[1]) !== $guard) {
        $fallos[] = "$rel: la ruta del guard no resuelve a core/guard.php";
    }
}

foreach ($fallos as $f) {
    echo "FALLA  $f\n";
}
echo ($total - count($fallos)) . "/$total controladores protegidos\n";
exit($fallos ? 1 : 0);
