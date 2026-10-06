<?php
    require '../../core/sesion.php';
    sesion_destruir();
    header('Location: ../../index.php');
?>
