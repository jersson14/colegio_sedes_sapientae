<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/parametros.php';
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();
    // Bloques [{idhora, idasig, dia}]: se guardan todos o ninguno; un cruce de aula o de docente se explica en el mensaje
    $bloques = json_decode($_POST['componentes'] ?? '', true);
    if (!is_array($bloques) || !$bloques || count($bloques) > 200) {
        denegar(400, 'No hay bloques de horario para guardar');
    }
    foreach ($bloques as $b) {
        if (!is_array($b) || !filter_var($b['idhora'] ?? null, FILTER_VALIDATE_INT) || !filter_var($b['idasig'] ?? null, FILTER_VALIDATE_INT)
            || !in_array($b['dia'] ?? '', array_merge(['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'], parametro('clases_sabado') === '1' ? ['SABADO'] : []), true)) {
            denegar(400, 'Bloque de horario inválido');
        }
    }
    responder_json($MHR->Registrar_Horarios_Lote($bloques));
?>
