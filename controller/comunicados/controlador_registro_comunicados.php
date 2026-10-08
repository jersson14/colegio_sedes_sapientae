<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaBienestar;

// Respuesta: 1 = publicado, 2 = duplicado, 0 = datos inválidos. Foto inválida: 422.
// Autor: el usuario de la sesión (antes el «usu» del formulario).
try {
    $bienestar = FabricaBienestar::gestionar((new conexionBD())->conexionPDO());
    echo $bienestar->publicarComunicado($_POST, ($_POST['nombrefoto'] ?? '') !== '', (int) $_SESSION['S_ID']);
} catch (InvalidArgumentException) {
    echo 0;
}
