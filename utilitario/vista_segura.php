<?php
// Las sub-vistas (view/*/view_*.php) solo se muestran con sesión iniciada.
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/regional.php';
iniciar_sesion_segura();
if (!hay_sesion()) {
    http_response_code(401);
    exit('Sesión no iniciada o expirada');
}
