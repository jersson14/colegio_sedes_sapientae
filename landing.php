<?php

declare(strict_types=1);

/**
 * Página pública de la institución del host (Fase 4.7). landing.html llega aquí por .htaccess.
 *
 * - Modo único y sin textos propios en «Empresa → Personalizar»: la página de siempre (landing.html).
 * - En otro caso: esta plantilla con el nombre, el logo, el color, el contacto y los textos de la
 *   empresa de SU base. Una sección sin contenido no se muestra (no se inventan cifras ni valores).
 */

require __DIR__ . '/core/marca.php';

use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

$m = marca();
$p = $m->pagina;
if ($p === null && ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/landing.html');
    exit;
}
$e = 'marca_html';
$parrafos = $p !== null && $p->nosotrosTexto !== '' ? preg_split('/\n\s*\n/', $p->nosotrosTexto) : [];
$niveles = $p->niveles ?? [];
$valores = $p->valores ?? [];
$cifras = $p->cifras ?? [];
$caracteristicas = $p->caracteristicas ?? [];
$lema = $p->lema ?? '';
$color = $m->color;
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $e($m->nombre . ($lema !== '' ? ' — ' . $lema : '')) ?>">
    <title><?= $e($m->nombre) ?></title>
    <link rel="stylesheet" href="landing.css?v=1.2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="<?= $e($m->icono) ?>">
    <?php if ($color !== null) { ?>
    <style>:root{--primary-color:<?= $color->hex ?>;--primary-dark:<?= $color->oscuro() ?>;--primary-light:<?= $color->hex ?>;--bg-gradient:linear-gradient(135deg,<?= $color->hex ?> 0%,<?= $color->oscuro(0.6) ?> 100%)}</style>
    <?php } ?>
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="<?= $e($m->logoAcceso) ?>" alt="<?= $e($m->nombre) ?>">
                <div class="logo-text">
                    <span class="logo-title"><?= $e($m->nombre) ?></span>
                    <?php if ($lema !== '') { ?><span class="logo-subtitle"><?= $e($lema) ?></span><?php } ?>
                </div>
            </div>
            <button class="nav-toggle" id="navToggle" aria-label="Menú"><span></span><span></span><span></span></button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#inicio" class="nav-link active">Inicio</a></li>
                <?php if ($parrafos !== []) { ?><li><a href="#nosotros" class="nav-link">Nosotros</a></li><?php } ?>
                <?php if ($niveles !== []) { ?><li><a href="#niveles" class="nav-link">Niveles</a></li><?php } ?>
                <?php if ($valores !== []) { ?><li><a href="#valores" class="nav-link">Valores</a></li><?php } ?>
                <li><a href="#contacto" class="nav-link">Contacto</a></li>
                <li><a href="index.php" class="btn-ingresar">Ingresar al Sistema</a></li>
            </ul>
        </div>
    </nav>

    <section class="hero" id="inicio">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <span class="hero-subtitle">Bienvenidos a</span>
                    <?= $e($m->nombre) ?>
                    <?php if ($lema !== '') { ?><span class="hero-badge"><?= $e($lema) ?></span><?php } ?>
                </h1>
                <?php if (($p->bienvenida ?? '') !== '') { ?><p class="hero-description"><?= $e($p->bienvenida) ?></p><?php } ?>
                <div class="hero-buttons">
                    <a href="#contacto" class="btn btn-primary"><span>Solicitar Información</span></a>
                    <a href="index.php" class="btn btn-secondary">Ingresar al Sistema</a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($cifras !== []) { ?>
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <?php foreach ($cifras as $c) { ?>
                <div class="stat-card">
                    <div class="stat-number" data-target="<?= (int) $c['valor'] ?>">0</div>
                    <div class="stat-label"><?= $e($c['etiqueta']) ?></div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if ($parrafos !== []) { ?>
    <section class="about" id="nosotros">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Conócenos</span>
                <h2 class="section-title"><?= $e($p->nosotrosTitulo !== '' ? $p->nosotrosTitulo : 'Nuestra Institución') ?></h2>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <?php foreach ($parrafos as $parrafo) { ?><p><?= nl2br($e(trim($parrafo))) ?></p><?php } ?>
                    <?php if ($caracteristicas !== []) { ?>
                    <div class="about-features">
                        <?php foreach ($caracteristicas as $c) { ?>
                        <div class="feature-item"><div class="feature-icon">✓</div><span><?= $e($c) ?></span></div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <div class="about-image">
                    <div class="image-card" style="padding: 1rem; overflow: hidden; background: white;">
                        <img src="<?= $e($m->logoPanel) ?>" alt="<?= $e($m->nombre) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if ($niveles !== []) { ?>
    <section class="levels" id="niveles">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Educación</span>
                <h2 class="section-title">Niveles Educativos</h2>
            </div>
            <div class="levels-grid">
                <?php foreach ($niveles as $n) { ?>
                <div class="level-card">
                    <h3 class="level-title"><?= $e($n['nombre']) ?></h3>
                    <?php if ($n['texto'] !== '') { ?><p class="level-description"><?= $e($n['texto']) ?></p><?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if ($valores !== []) { ?>
    <section class="values" id="valores">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Nuestros Pilares</span>
                <h2 class="section-title">Valores Institucionales</h2>
            </div>
            <div class="values-grid">
                <?php foreach ($valores as $v) { ?>
                <div class="value-card">
                    <h3><?= $e($v['nombre']) ?></h3>
                    <?php if ($v['texto'] !== '') { ?><p><?= $e($v['texto']) ?></p><?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="contact" id="contacto">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <div class="section-header">
                        <span class="section-tag">Contáctanos</span>
                        <h2 class="section-title">Solicita Información</h2>
                    </div>
                    <div class="info-items">
                        <?php foreach ([['📍', 'Dirección', $m->direccion], ['📞', 'Teléfono', $m->telefono], ['✉️', 'Email', $m->email], ['🕐', 'Horario de Atención', $p->horario ?? '']] as [$icono, $titulo, $valor]) {
                            if ($valor === '') {
                                continue;
                            } ?>
                        <div class="info-item">
                            <div class="info-icon"><?= $icono ?></div>
                            <div class="info-content"><h4><?= $titulo ?></h4><p><?= $e($valor) ?></p></div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="contact-form-container">
                    <form class="contact-form" id="contactForm">
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
                                <option value="inicial">Educación Inicial</option>
                                <option value="primaria">Educación Primaria</option>
                                <option value="secundaria">Educación Secundaria</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mensaje">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><span>Enviar Solicitud</span></button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= $e($m->nombre) ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="landing.js"></script>
</body>
</html>
