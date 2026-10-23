<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/regional.php';
    require '../../model/model_regional.php';
    $MR = new Modelo_Regional();

    $pais = entero_post('pais', 1, 'país');
    $moneda = entero_post('moneda', 1, 'moneda');
    $zona = texto_post('zona', 60, true, 'zona horaria');
    if (!in_array($zona, timezone_identifiers_list(), true)) {
        denegar(400, 'Zona horaria no válida');
    }
    $id_trib = strtoupper(texto_post('id_tributario', 20, false, 'identificación tributaria'));
    $extranjero = !empty($_POST['extranjero']) ? 1 : 0;

    // Cambiar la moneda con pagos ya registrados no convierte los montos anteriores: se pide confirmación
    $actual = $MR->Traer_Configuracion();
    if ((int) $actual['moneda_id'] !== $moneda && $MR->Hay_Movimientos() && empty($_POST['confirmar_moneda'])) {
        responder_json(['ok' => false, 'confirmar' => true,
            'mensaje' => 'Ya hay pagos y egresos registrados. Los montos anteriores NO se convierten a la nueva moneda: '
                       . 'solo cambia el símbolo y el formato con que se muestran. ¿Desea continuar?']);
    }

    $r = $MR->Guardar_Configuracion($pais, $moneda, $zona, $id_trib, $extranjero, usuario_actual_id());
    if ($r['ok']) {
        config_regional(true); // refresca la configuración guardada en la sesión
    }
    responder_json($r);
?>
