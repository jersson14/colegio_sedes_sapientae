<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require_once __DIR__ . '/../../utilitario/pagina_web.php';
// Página pública del colegio (solo administrador). accion=cargar | guardar (datos en JSON) | imagen (campo + archivo) | quitar_imagen (campo)
$accion = $_POST['accion'] ?? 'cargar';
$campos_imagen = ['hero' => 'hero_imagen', 'nosotros' => 'nosotros_imagen'];

if ($accion === 'cargar') {
    $d = pagina_web_datos();
    foreach ($campos_imagen as $campo) { $d[$campo] = pagina_web_imagen($d[$campo], $campo); }
    responder_json(['ok' => true, 'datos' => $d, 'defecto' => PW_DEFECTO]);
}

if ($accion === 'guardar') {
    $entrada = json_decode((string) ($_POST['datos'] ?? ''), true);
    if (!is_array($entrada)) {
        denegar(400, 'Datos inválidos');
    }
    [$datos, $error] = pagina_web_limpiar($entrada);
    if ($error !== '') {
        denegar(400, $error);
    }
    pagina_web_guardar($datos);
    responder_json(['ok' => true]);
}

if ($accion === 'imagen' || $accion === 'quitar_imagen') {
    $campo = $campos_imagen[$_POST['campo'] ?? ''] ?? null;
    if (!$campo) {
        denegar(400, 'Imagen inválida');
    }
    $anterior = pagina_web_datos()[$campo];
    $ruta = PW_DEFECTO[$campo];
    if ($accion === 'imagen') {
        $nombre = preparar_foto_subida('imagen', 'nueva');   // valida que sea JPG, PNG, GIF o WEBP de hasta 5 MB
        if ($nombre === '') {
            denegar(400, 'Elija una imagen');
        }
        $dir = __DIR__ . '/../../' . PW_IMAGENES_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . '/' . $nombre)) {
            denegar(500, 'No se pudo guardar la imagen');
        }
        $ruta = PW_IMAGENES_DIR . '/' . $nombre;
    }
    pagina_web_guardar([$campo => $ruta]);
    if ($anterior !== $ruta && strpos((string) $anterior, PW_IMAGENES_DIR . '/') === 0) {
        borrar_archivo_seguro($anterior, PW_IMAGENES_DIR);   // la foto que se reemplazó ya no se usa
    }
    responder_json(['ok' => true, 'ruta' => $ruta]);
}

denegar(400, 'Acción inválida');
