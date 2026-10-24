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

    // Escala de notas: mínimo < máximo, nota aprobatoria dentro del rango, 0 a 2 decimales;
    // calificaciones literales opcionales (p. ej. AD,A,B,C) y las que aprueban deben estar entre ellas.
    // Si no se envía (otras pantallas o integraciones), se conserva la escala actual.
    if (!isset($_POST['nota_min'])) {
        $actual_escala = $MR->Traer_Configuracion();
        foreach (['nota_min', 'nota_max', 'nota_aprobatoria', 'nota_decimales', 'notas_literales', 'literales_aprobatorios'] as $k) {
            $_POST[$k] = (string) $actual_escala[$k];
        }
    }
    $escala = [
        'min' => (float) monto_post('nota_min', 0, 1000, 'nota mínima'),
        'max' => (float) monto_post('nota_max', 0.01, 1000, 'nota máxima'),
        'aprobatoria' => (float) monto_post('nota_aprobatoria', 0, 1000, 'nota aprobatoria'),
        'decimales' => entero_post('nota_decimales', 0, 'decimales de la nota'),
    ];
    if ($escala['min'] >= $escala['max'] || $escala['aprobatoria'] < $escala['min'] || $escala['aprobatoria'] > $escala['max'] || $escala['decimales'] > 2) {
        denegar(400, 'Escala de notas inválida: el mínimo debe ser menor que el máximo, la nota aprobatoria debe estar entre ambos y los decimales entre 0 y 2');
    }
    $lista = function ($campo) {
        $t = strtoupper(preg_replace('/\s+/', '', (string) ($_POST[$campo] ?? '')));
        if ($t === '') { return []; }
        $items = explode(',', $t);
        foreach ($items as $i) {
            if (!preg_match('/^[A-Z][A-Z+-]{0,2}$/', $i)) {
                denegar(400, 'Calificaciones literales inválidas: use letras separadas por comas (p. ej. AD,A,B,C)');
            }
        }
        return array_values(array_unique($items));
    };
    $literales = $lista('notas_literales');
    $aprobatorios = $lista('literales_aprobatorios');
    if (array_diff($aprobatorios, $literales) || ($literales && !$aprobatorios)) {
        denegar(400, 'Indique qué calificaciones literales aprueban (deben estar en la lista de literales)');
    }
    $escala['literales'] = implode(',', $literales);
    $escala['aprobatorios'] = implode(',', $aprobatorios);

    // Cambiar la moneda con pagos ya registrados no convierte los montos anteriores: se pide confirmación
    $actual = $MR->Traer_Configuracion();
    if ((int) $actual['moneda_id'] !== $moneda && $MR->Hay_Movimientos() && empty($_POST['confirmar_moneda'])) {
        responder_json(['ok' => false, 'confirmar' => true,
            'mensaje' => 'Ya hay pagos y egresos registrados. Los montos anteriores NO se convierten a la nueva moneda: '
                       . 'solo cambia el símbolo y el formato con que se muestran. ¿Desea continuar?']);
    }

    $r = $MR->Guardar_Configuracion($pais, $moneda, $zona, $id_trib, $extranjero, $escala, usuario_actual_id());
    if ($r['ok']) {
        config_regional(true); // refresca la configuración guardada en la sesión
    }
    responder_json($r);
?>
