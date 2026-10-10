<?php

/**
 * Router del servidor de PHP para las pruebas (php -S … tests/E2E/router.php): reproduce la regla del
 * .htaccess que el servidor de PHP no lee. Las URLs de fotos que no son un archivo real se sirven con
 * controller/archivo/controlador_ver_archivo.php (Fase 4.6); todo lo demás, como siempre.
 */

declare(strict_types=1);

$raiz = dirname(__DIR__, 2);
$ruta = ltrim(rawurldecode((string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

if (preg_match('#^controller/((alumnos|docentes|personal_administrativo|comunicados)/fotos|(empleado|empresa)/FOTOS)/[^/]+$#', $ruta) === 1
    && !is_file($raiz . '/' . $ruta)) {
    $_GET['ruta'] = $ruta;
    chdir($raiz . '/controller/archivo');
    require $raiz . '/controller/archivo/controlador_ver_archivo.php';
    return true;
}
return false;
