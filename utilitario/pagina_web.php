<?php
/**
 * Página pública del colegio (landing.php), administrable desde Configuración → Página web.
 * PW_DEFECTO tiene el contenido de fábrica; la tabla pagina_web guarda solo lo que el colegio cambió.
 * pagina_web_limpiar() valida todo lo que llega del formulario (longitudes, listas, enlaces) antes de guardarlo.
 */
require_once __DIR__ . '/../model/model_conexion.php';

const PW_IMAGENES_DIR = 'img/web';   // fotos que sube el colegio (portada y "Nosotros")

const PW_DEFECTO = [
    'hero_saludo' => 'Bienvenidos al',
    'hero_titulo' => 'Colegio Sedes Sapientiae',
    'hero_etiqueta' => 'Colegio Diocesano',
    'hero_texto' => 'Formando líderes con excelencia académica y valores cristianos en Abancay, Apurímac',
    'hero_imagen' => 'img/fondo.jpeg',
    'cifras' => [
        ['icono' => '🎓', 'numero' => 25, 'texto' => 'Años de Experiencia'],
        ['icono' => '👨‍🎓', 'numero' => 500, 'texto' => 'Estudiantes Activos'],
        ['icono' => '👨‍🏫', 'numero' => 45, 'texto' => 'Docentes Calificados'],
        ['icono' => '⭐', 'numero' => 98, 'texto' => '% Satisfacción'],
    ],
    'nosotros_subtitulo' => 'Una tradición de excelencia educativa al servicio de la comunidad de Abancay',
    'nosotros_encabezado' => 'Educación de Calidad con Valores Cristianos',
    'nosotros_texto' => "El Colegio Sedes Sapientiae es una institución educativa diocesana comprometida con la formación integral de niños y jóvenes en Abancay, Apurímac. Nuestra misión es brindar una educación de excelencia que combine el desarrollo académico con la formación en valores cristianos.\n\nContamos con infraestructura moderna, docentes altamente calificados y un enfoque pedagógico innovador que prepara a nuestros estudiantes para los desafíos del siglo XXI, manteniendo siempre nuestros principios y valores institucionales.",
    'nosotros_puntos' => "Infraestructura moderna y segura\nDocentes especializados y comprometidos\nEducación basada en valores cristianos\nMetodología de enseñanza innovadora",
    'nosotros_imagen' => 'img/icono.jpeg',
    'niveles_subtitulo' => 'Acompañamos el crecimiento de nuestros estudiantes en cada etapa de su formación',
    'niveles' => [
        ['clave' => 'inicial', 'mostrar' => true, 'destacado' => false, 'icono' => '🎨', 'titulo' => 'Educación Inicial',
         'texto' => 'Estimulación temprana y desarrollo de habilidades básicas en un ambiente seguro y acogedor para niños de 3 a 5 años.',
         'puntos' => "Desarrollo psicomotor\nEstimulación del lenguaje\nSocialización y valores\nActividades lúdicas", 'edad' => '3 - 5 años'],
        ['clave' => 'primaria', 'mostrar' => true, 'destacado' => true, 'icono' => '📚', 'titulo' => 'Educación Primaria',
         'texto' => 'Formación académica sólida con énfasis en lectura, matemáticas, ciencias y desarrollo de habilidades sociales.',
         'puntos' => "Currículo completo MINEDU\nRefuerzo académico\nActividades extracurriculares\nFormación en valores", 'edad' => '6 - 11 años'],
        ['clave' => 'secundaria', 'mostrar' => true, 'destacado' => false, 'icono' => '🎯', 'titulo' => 'Educación Secundaria',
         'texto' => 'Preparación integral para la educación superior con enfoque en excelencia académica y proyecto de vida.',
         'puntos' => "Preparación pre-universitaria\nOrientación vocacional\nTalleres especializados\nLiderazgo y emprendimiento", 'edad' => '12 - 16 años'],
    ],
    'valores_subtitulo' => 'Formamos personas íntegras guiadas por principios sólidos',
    'valores' => [
        ['icono' => '🙏', 'titulo' => 'Fe y Espiritualidad', 'texto' => 'Fomentamos el crecimiento espiritual y la vivencia de los valores cristianos en nuestra comunidad educativa.'],
        ['icono' => '🎓', 'titulo' => 'Excelencia Académica', 'texto' => 'Buscamos la mejora continua y el desarrollo del máximo potencial de cada estudiante.'],
        ['icono' => '🤝', 'titulo' => 'Respeto y Solidaridad', 'texto' => 'Promovemos el respeto mutuo, la empatía y el servicio a los demás en nuestra comunidad.'],
        ['icono' => '💡', 'titulo' => 'Innovación', 'texto' => 'Implementamos metodologías modernas que preparan a nuestros estudiantes para el futuro.'],
        ['icono' => '👨‍👩‍👧‍👦', 'titulo' => 'Familia y Comunidad', 'texto' => 'Trabajamos en conjunto con las familias para el desarrollo integral de nuestros estudiantes.'],
        ['icono' => '🌱', 'titulo' => 'Responsabilidad', 'texto' => 'Formamos ciudadanos responsables, comprometidos con su entorno y su desarrollo personal.'],
    ],
    'contacto_subtitulo' => 'Estamos aquí para resolver tus dudas sobre el proceso de matrícula',
    'direccion' => 'Abancay, Apurímac, Perú',
    'telefono' => '(083) XXX-XXX',
    'email' => 'informes@sedessapientiae.edu.pe',
    'horario' => 'Lunes a Viernes: 8:00 AM - 4:00 PM',
    'whatsapp' => '',
    'facebook' => '',
    'instagram' => '',
    'tiktok' => '',
    'youtube' => '',
    'pie_texto' => 'Formando líderes con excelencia académica y valores cristianos desde hace más de 25 años en Abancay, Apurímac.',
    'meta_descripcion' => 'Colegio Sedes Sapientiae - Colegio Diocesano en Abancay, Apurímac. Educación inicial, primaria y secundaria de calidad.',
];

/** Largo máximo de cada texto. */
const PW_LARGOS = [
    'hero_saludo' => 60, 'hero_titulo' => 90, 'hero_etiqueta' => 60, 'hero_texto' => 250,
    'nosotros_subtitulo' => 200, 'nosotros_encabezado' => 120, 'nosotros_texto' => 3000, 'nosotros_puntos' => 600,
    'niveles_subtitulo' => 200, 'valores_subtitulo' => 200, 'contacto_subtitulo' => 200,
    'direccion' => 150, 'telefono' => 40, 'email' => 120, 'horario' => 120, 'whatsapp' => 20,
    'facebook' => 250, 'instagram' => 250, 'tiktok' => 250, 'youtube' => 250, 'pie_texto' => 300, 'meta_descripcion' => 300,
];
const PW_REDES = ['facebook', 'instagram', 'tiktok', 'youtube'];

/** Contenido completo de la página: lo de fábrica con lo que el colegio cambió encima. */
function pagina_web_datos() {
    $d = PW_DEFECTO;
    try {
        $q = (new conexionBD())->conexionPDO()->query("SELECT clave, valor FROM pagina_web");
        foreach ($q->fetchAll(PDO::FETCH_KEY_PAIR) as $clave => $valor) {
            if (!array_key_exists($clave, $d)) { continue; }
            $v = json_decode((string) $valor, true);
            if (gettype($v) === gettype($d[$clave]) || (is_int($d[$clave]) && is_numeric($v))) { $d[$clave] = $v; }
        }
    } catch (Throwable $e) {
        // sin la tabla (migración pendiente) la página se muestra con el contenido de fábrica
    }
    return $d;
}

function pw_texto($v, $max) {
    $t = trim(preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', str_replace("\r\n", "\n", (string) $v)));
    return mb_substr($t, 0, $max, 'UTF-8');
}

/**
 * Valida lo que llega del formulario. Devuelve [datos limpios, error o ''].
 * Las imágenes no se cambian aquí (se suben aparte con pagina_web_guardar_imagen).
 */
function pagina_web_limpiar(array $in) {
    $d = [];
    foreach (PW_LARGOS as $clave => $max) {
        $d[$clave] = pw_texto($in[$clave] ?? '', $max);
    }
    foreach (['hero_titulo' => 'el título de la portada', 'nosotros_encabezado' => 'el encabezado de "Nosotros"'] as $clave => $nombre) {
        if ($d[$clave] === '') { return [null, 'Escriba ' . $nombre]; }
    }
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) { return [null, 'El correo no es válido']; }
    $d['whatsapp'] = preg_replace('/\D/', '', $d['whatsapp']);
    if ($d['whatsapp'] !== '' && (strlen($d['whatsapp']) < 9 || strlen($d['whatsapp']) > 15)) {
        return [null, 'El WhatsApp debe tener entre 9 y 15 dígitos (con el código del país, por ejemplo 51987654321)'];
    }
    foreach (PW_REDES as $red) {
        if ($d[$red] === '') { continue; }
        if (!preg_match('~^https?://~i', $d[$red])) { $d[$red] = 'https://' . $d[$red]; }
        if (!filter_var($d[$red], FILTER_VALIDATE_URL)) { return [null, 'El enlace de ' . ucfirst($red) . ' no es válido']; }
    }
    $d['cifras'] = [];
    foreach (array_slice((array) ($in['cifras'] ?? []), 0, 4) as $c) {
        $d['cifras'][] = ['icono' => pw_texto($c['icono'] ?? '', 16), 'numero' => max(0, min(999999, (int) ($c['numero'] ?? 0))), 'texto' => pw_texto($c['texto'] ?? '', 40)];
    }
    $d['niveles'] = [];
    foreach (PW_DEFECTO['niveles'] as $i => $base) {   // siempre los tres niveles, en su orden (el formulario de contacto los usa)
        $n = (array) (($in['niveles'] ?? [])[$i] ?? []);
        $d['niveles'][] = ['clave' => $base['clave'], 'mostrar' => !empty($n['mostrar']), 'destacado' => !empty($n['destacado']),
            'icono' => pw_texto($n['icono'] ?? '', 16), 'titulo' => pw_texto($n['titulo'] ?? '', 60) ?: $base['titulo'],
            'texto' => pw_texto($n['texto'] ?? '', 400), 'puntos' => pw_texto($n['puntos'] ?? '', 400), 'edad' => pw_texto($n['edad'] ?? '', 40)];
    }
    if (!array_filter(array_column($d['niveles'], 'mostrar'))) { return [null, 'Muestre al menos un nivel educativo']; }
    $d['valores'] = [];
    foreach (array_slice((array) ($in['valores'] ?? []), 0, 6) as $v) {
        $d['valores'][] = ['icono' => pw_texto($v['icono'] ?? '', 16), 'titulo' => pw_texto($v['titulo'] ?? '', 60), 'texto' => pw_texto($v['texto'] ?? '', 300)];
    }
    return [$d, ''];
}

/** Guarda los datos ya limpios (una fila por dato). */
function pagina_web_guardar(array $d) {
    $c = (new conexionBD())->conexionPDO();
    $q = $c->prepare("INSERT INTO pagina_web (clave, valor, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");
    $c->beginTransaction();
    foreach ($d as $clave => $valor) {
        if (array_key_exists($clave, PW_DEFECTO)) { $q->execute([$clave, json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]); }
    }
    $c->commit();
}

/** Ruta de una imagen solo si es de fábrica o de la carpeta de la página y existe; si no, la de fábrica. */
function pagina_web_imagen($ruta, $campo) {
    $ruta = (string) $ruta;
    $valida = ($ruta === PW_DEFECTO[$campo] || preg_match('#^' . PW_IMAGENES_DIR . '/[\w.-]+\.(jpe?g|png|gif|webp)$#i', $ruta)) && is_file(__DIR__ . '/../' . $ruta);
    return $valida ? $ruta : PW_DEFECTO[$campo];
}
