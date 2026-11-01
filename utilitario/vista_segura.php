<?php
// Las sub-vistas (view/*/view_*.php) solo se muestran con sesión iniciada.
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/regional.php';
iniciar_sesion_segura();
if (!hay_sesion()) {
    http_response_code(401);
    exit('Sesión no iniciada o expirada');
}
require_once __DIR__ . '/demo.php';
$__vista = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME']));
$__vista = preg_replace('#^.*/view/#', '', $__vista);
if (demo_vista_bloqueada($__vista)) {
    http_response_code(403);
    exit('<div class="content"><div class="container-fluid pt-4"><div class="alert alert-info"><i class="fas fa-lock mr-2"></i>'
       . 'Esta pantalla está deshabilitada en la versión de demostración.</div></div></div>');
}
