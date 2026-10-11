<?php
    require '../../core/sesion.php';
    require '../../core/auditoria.php';
    if (sesion_activa()) {
        auditoria_registrar((int) $_SESSION['S_ID'], (string) $_SESSION['S_USU'], (string) $_SESSION['S_ROL'], 'sesion', 'cerrar_sesion', 200, null);
    }
    sesion_destruir();
    header('Location: ../../index.php');
?>
