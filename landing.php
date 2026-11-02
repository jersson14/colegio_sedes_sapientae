<?php
// Página pública del colegio. El contenido se edita en Configuración → Página web (utilitario/pagina_web.php);
// el nombre, el escudo y los colores salen de Configuración → Apariencia.
require_once __DIR__ . '/utilitario/tema.php';
require_once __DIR__ . '/utilitario/pagina_web.php';
$TEMA = tema_actual();
$W = pagina_web_datos();
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$lineas = function ($t) { return array_values(array_filter(array_map('trim', explode("\n", (string) $t)), 'strlen')); };
$parrafos = function ($t) { return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $t)), 'strlen')); };
$nombre = $TEMA['nombre'] ?: 'Colegio';
$escudo = $TEMA['logo'] ?: 'img/logo1.png';
$portada = pagina_web_imagen($W['hero_imagen'], 'hero_imagen');
$foto_nosotros = pagina_web_imagen($W['nosotros_imagen'], 'nosotros_imagen');
$niveles = array_values(array_filter($W['niveles'], function ($n) { return !empty($n['mostrar']); }));
$cifras = array_values(array_filter($W['cifras'], function ($c) { return trim($c['texto']) !== ''; }));
$valores = array_values(array_filter($W['valores'], function ($v) { return trim($v['titulo']) !== ''; }));
$redes = array_filter(['facebook' => 'fab fa-facebook-f', 'instagram' => 'fab fa-instagram', 'tiktok' => 'fab fa-tiktok', 'youtube' => 'fab fa-youtube'],
    function ($k) use ($W) { return $W[$k] !== ''; }, ARRAY_FILTER_USE_KEY);
$contacto = array_filter(['📍' => ['Dirección', $W['direccion']], '📞' => ['Teléfono', $W['telefono']], '✉️' => ['Email', $W['email']], '🕐' => ['Horario de Atención', $W['horario']]],
    function ($x) { return $x[1] !== ''; });
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $e($W['meta_descripcion']); ?>">
    <title><?php echo $e($W['hero_titulo']); ?><?php echo $W['hero_etiqueta'] !== '' ? ' - ' . $e($W['hero_etiqueta']) : ''; ?></title>
    <link rel="stylesheet" href="landing.css?v=<?php echo filemtime(__DIR__ . '/landing.css'); ?>">
    <link rel="stylesheet" href="plantilla/plugins/fontawesome-free/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="<?php echo $e($escudo); ?>">
    <?php echo tema_css_variables($TEMA); ?>
    <style>
        /* Colores del colegio (Configuración → Apariencia) en lugar de los de fábrica de la página */
        :root {
            --primary-color: var(--tema-tinta); --primary-dark: var(--tema-primario-oscuro);
            --primary-light: color-mix(in srgb, var(--tema-primario) 70%, #fff);
            --secondary-color: var(--tema-acento); --secondary-dark: var(--tema-acento-oscuro);
            --accent-purple: var(--tema-primario-oscuro);
            --bg-gradient: linear-gradient(135deg, var(--tema-primario) 0%, var(--tema-primario-oscuro) 100%);
        }
        .hero { background-image: url('<?php echo $e($portada); ?>'); }
        .hero-overlay { background: linear-gradient(135deg, color-mix(in srgb, var(--tema-primario) 86%, transparent) 0%, color-mix(in srgb, var(--tema-primario-oscuro) 78%, transparent) 100%); }
        .level-age { background: var(--tema-primario-claro); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { box-shadow: 0 0 0 3px var(--tema-primario-claro); }
        .redes { display: flex; gap: 10px; margin-top: 16px; }
        .redes a { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,.12); color: #fff; transition: background .2s; }
        .redes a:hover { background: var(--tema-acento); color: var(--tema-acento-texto); }
        .whatsapp-flotante { position: fixed; right: 20px; bottom: 20px; z-index: 50; width: 58px; height: 58px; border-radius: 50%; background: #25D366; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: 0 8px 20px rgba(0,0,0,.25); }
        .whatsapp-flotante:hover { color: #fff; transform: scale(1.05); }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="<?php echo $e($escudo); ?>" alt="Escudo de <?php echo $e($nombre); ?>">
                <div class="logo-text">
                    <span class="logo-title"><?php echo $e($nombre); ?></span>
                    <?php if ($W['hero_etiqueta'] !== '') { ?><span class="logo-subtitle"><?php echo $e($W['hero_etiqueta']); ?></span><?php } ?>
                </div>
            </div>
            <button class="nav-toggle" id="navToggle" aria-label="Abrir el menú">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#inicio" class="nav-link active">Inicio</a></li>
                <li><a href="#nosotros" class="nav-link">Nosotros</a></li>
                <li><a href="#niveles" class="nav-link">Niveles Educativos</a></li>
                <?php if ($valores) { ?><li><a href="#valores" class="nav-link">Valores</a></li><?php } ?>
                <li><a href="#contacto" class="nav-link">Contacto</a></li>
                <li><a href="index.php" class="btn-ingresar">Ingresar al Sistema</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <?php if ($W['hero_saludo'] !== '') { ?><span class="hero-subtitle"><?php echo $e($W['hero_saludo']); ?></span><?php } ?>
                    <?php echo $e($W['hero_titulo']); ?>
                    <?php if ($W['hero_etiqueta'] !== '') { ?><span class="hero-badge"><?php echo $e($W['hero_etiqueta']); ?></span><?php } ?>
                </h1>
                <?php if ($W['hero_texto'] !== '') { ?><p class="hero-description"><?php echo $e($W['hero_texto']); ?></p><?php } ?>
                <div class="hero-buttons">
                    <a href="#contacto" class="btn btn-primary">
                        <span>Solicitar Información</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    <a href="#niveles" class="btn btn-secondary">Conocer Niveles Educativos</a>
                </div>
            </div>
        </div>
        <div class="hero-scroll">
            <span>Desliza para explorar</span>
            <div class="scroll-indicator"></div>
        </div>
    </section>

    <?php if ($cifras) { ?>
    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <?php foreach ($cifras as $c) { ?>
                <div class="stat-card">
                    <div class="stat-icon"><?php echo $e($c['icono']); ?></div>
                    <div class="stat-number" data-target="<?php echo (int) $c['numero']; ?>">0</div>
                    <div class="stat-label"><?php echo $e($c['texto']); ?></div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <!-- About Section -->
    <section class="about" id="nosotros">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Conócenos</span>
                <h2 class="section-title">Nuestra Institución</h2>
                <?php if ($W['nosotros_subtitulo'] !== '') { ?><p class="section-description"><?php echo $e($W['nosotros_subtitulo']); ?></p><?php } ?>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <h3><?php echo $e($W['nosotros_encabezado']); ?></h3>
                    <?php foreach ($parrafos($W['nosotros_texto']) as $p) { ?><p><?php echo nl2br($e($p)); ?></p><?php } ?>
                    <?php if ($lineas($W['nosotros_puntos'])) { ?>
                    <div class="about-features">
                        <?php foreach ($lineas($W['nosotros_puntos']) as $p) { ?>
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <span><?php echo $e($p); ?></span>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <div class="about-image">
                    <div class="image-card" style="padding: 1rem; overflow: hidden; background: white;">
                        <img src="<?php echo $e($foto_nosotros); ?>" alt="<?php echo $e($nombre); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Levels Section -->
    <section class="levels" id="niveles">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Educación Integral</span>
                <h2 class="section-title">Niveles Educativos</h2>
                <?php if ($W['niveles_subtitulo'] !== '') { ?><p class="section-description"><?php echo $e($W['niveles_subtitulo']); ?></p><?php } ?>
            </div>
            <div class="levels-grid">
                <?php foreach ($niveles as $n) { ?>
                <div class="level-card<?php echo !empty($n['destacado']) ? ' featured' : ''; ?>">
                    <?php if (!empty($n['destacado'])) { ?><div class="level-badge">Más Popular</div><?php } ?>
                    <div class="level-icon"><?php echo $e($n['icono']); ?></div>
                    <h3 class="level-title"><?php echo $e($n['titulo']); ?></h3>
                    <?php if ($n['texto'] !== '') { ?><p class="level-description"><?php echo $e($n['texto']); ?></p><?php } ?>
                    <?php if ($lineas($n['puntos'])) { ?>
                    <ul class="level-features">
                        <?php foreach ($lineas($n['puntos']) as $p) { ?><li><?php echo $e($p); ?></li><?php } ?>
                    </ul>
                    <?php } ?>
                    <?php if ($n['edad'] !== '') { ?><div class="level-age"><?php echo $e($n['edad']); ?></div><?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php if ($valores) { ?>
    <!-- Values Section -->
    <section class="values" id="valores">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Nuestros Pilares</span>
                <h2 class="section-title">Valores Institucionales</h2>
                <?php if ($W['valores_subtitulo'] !== '') { ?><p class="section-description"><?php echo $e($W['valores_subtitulo']); ?></p><?php } ?>
            </div>
            <div class="values-grid">
                <?php foreach ($valores as $v) { ?>
                <div class="value-card">
                    <div class="value-icon"><?php echo $e($v['icono']); ?></div>
                    <h3><?php echo $e($v['titulo']); ?></h3>
                    <?php if ($v['texto'] !== '') { ?><p><?php echo $e($v['texto']); ?></p><?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <!-- Contact Section -->
    <section class="contact" id="contacto">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <div class="section-header">
                        <span class="section-tag">Contáctanos</span>
                        <h2 class="section-title">Solicita Información</h2>
                        <?php if ($W['contacto_subtitulo'] !== '') { ?><p class="section-description"><?php echo $e($W['contacto_subtitulo']); ?></p><?php } ?>
                    </div>
                    <div class="info-items">
                        <?php foreach ($contacto as $icono => [$titulo, $valor]) { ?>
                        <div class="info-item">
                            <div class="info-icon"><?php echo $icono; ?></div>
                            <div class="info-content">
                                <h4><?php echo $e($titulo); ?></h4>
                                <p><?php echo $e($valor); ?></p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="contact-form-container">
                    <form class="contact-form" id="contactForm">
                        <!-- Anti-spam: campo trampa (las personas no lo ven; los robots suelen llenarlo) y hora de apertura -->
                        <div style="position:absolute; left:-10000px; width:1px; height:1px; overflow:hidden" aria-hidden="true">
                            <label for="sitio_web">No complete este campo</label>
                            <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
                        </div>
                        <input type="hidden" name="abierto_ms" id="abierto_ms">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nivel">Nivel de Interés</label>
                            <select id="nivel" name="nivel" required>
                                <option value="">Seleccione un nivel</option>
                                <?php foreach ($niveles as $n) { ?><option value="<?php echo $e($n['clave']); ?>"><?php echo $e($n['titulo']); ?></option><?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mensaje">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <span>Enviar Solicitud</span>
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M18 2L9 11M18 2L12 18L9 11M18 2L2 8L9 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="<?php echo $e($escudo); ?>" alt="Escudo de <?php echo $e($nombre); ?>">
                        <div class="logo-text">
                            <span class="logo-title"><?php echo $e($nombre); ?></span>
                            <?php if ($W['hero_etiqueta'] !== '') { ?><span class="logo-subtitle"><?php echo $e($W['hero_etiqueta']); ?></span><?php } ?>
                        </div>
                    </div>
                    <?php if ($W['pie_texto'] !== '') { ?><p class="footer-description"><?php echo $e($W['pie_texto']); ?></p><?php } ?>
                    <?php if ($redes) { ?>
                    <div class="redes">
                        <?php foreach ($redes as $red => $icono) { ?><a href="<?php echo $e($W[$red]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo $e(ucfirst($red)); ?>"><i class="<?php echo $icono; ?>"></i></a><?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <div class="footer-section">
                    <h4>Enlaces Rápidos</h4>
                    <ul class="footer-links">
                        <li><a href="#inicio">Inicio</a></li>
                        <li><a href="#nosotros">Nosotros</a></li>
                        <li><a href="#niveles">Niveles Educativos</a></li>
                        <?php if ($valores) { ?><li><a href="#valores">Valores</a></li><?php } ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Niveles</h4>
                    <ul class="footer-links">
                        <?php foreach ($niveles as $n) { ?><li><a href="#niveles"><?php echo $e($n['titulo']); ?></a></li><?php } ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <ul class="footer-links">
                        <?php foreach (['direccion', 'telefono', 'email'] as $k) { if ($W[$k] !== '') { ?><li><?php echo $e($W[$k]); ?></li><?php } } ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $e($W['hero_titulo']); ?><?php echo $W['hero_etiqueta'] !== '' ? ' - ' . $e($W['hero_etiqueta']) : ''; ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <?php if ($W['whatsapp'] !== '') { ?>
    <a class="whatsapp-flotante" href="https://wa.me/<?php echo $e($W['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Escribir por WhatsApp"><i class="fab fa-whatsapp"></i></a>
    <?php } ?>

    <script src="landing.js?v=<?php echo filemtime(__DIR__ . '/landing.js'); ?>"></script>
</body>

</html>
