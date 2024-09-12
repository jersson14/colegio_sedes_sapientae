<?php
date_default_timezone_set('UTC');

    $contra = password_hash('123456',PASSWORD_DEFAULT,['cost'=>12]);
    echo $contra;
    echo '<br>';
    echo '<br>';echo '<br>';
    date_default_timezone_set('America/Lima');
    $horaActual = date("H:i:s");
    echo $horaActual;
    ?>