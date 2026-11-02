<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/pagina_web.js?rev=<?php echo time(); ?>"></script>
<style>
  .pw-seccion { margin-bottom: 18px; }
  .pw-seccion .card-header h3 { font-size: 1.05rem; font-weight: 700; margin: 0; }
  .pw-seccion .card-header small { color: #6B6F76; display: block; font-weight: 400; }
  .pw-fila { display: grid; grid-template-columns: 70px 110px 1fr; gap: 8px; margin-bottom: 8px; }
  .pw-fila-valor { display: grid; grid-template-columns: 70px 1fr 2fr; gap: 8px; margin-bottom: 8px; }
  .pw-icono { text-align: center; font-size: 1.2rem; }
  .pw-nivel { border: 1px solid #E6E3DE; border-radius: 12px; padding: 14px; height: 100%; background: #fff; }
  .pw-nivel.oculto { opacity: .55; background: #FAF9F7; }
  .pw-foto { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
  .pw-foto img { width: 180px; height: 110px; object-fit: cover; border-radius: 10px; border: 1px solid #E6E3DE; background: #F6F5F2; }
  .pw-barra { position: sticky; bottom: 0; z-index: 5; background: var(--tema-fondo); padding: 12px 0; border-top: 1px solid #E6E3DE; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
  @media (max-width: 575.98px) { .pw-fila, .pw-fila-valor { grid-template-columns: 60px 1fr; } .pw-fila > :last-child, .pw-fila-valor > :last-child { grid-column: 1 / -1; } }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Página web</h1>
        <p class="pagina-ayuda">Lo que ven las familias en la página pública del colegio, antes de ingresar al sistema. El nombre, el escudo y los colores se cambian en <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_apariencia.php');return false;">Apariencia</a>.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <a class="btn btn-outline-secondary" href="../" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i>Ver la página</a>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid" id="pw_form">
    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-flag mr-1"></i>Portada<small>Lo primero que se ve, sobre la foto grande.</small></h3></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 form-group"><label for="pw_hero_saludo">Saludo</label><input class="form-control" id="pw_hero_saludo" data-campo="hero_saludo" maxlength="60"></div>
          <div class="col-md-8 form-group"><label for="pw_hero_titulo">Título <span class="text-danger">*</span></label><input class="form-control" id="pw_hero_titulo" data-campo="hero_titulo" maxlength="90"></div>
          <div class="col-md-4 form-group"><label for="pw_hero_etiqueta">Etiqueta</label><input class="form-control" id="pw_hero_etiqueta" data-campo="hero_etiqueta" maxlength="60" placeholder="Ej.: Colegio Diocesano"></div>
          <div class="col-md-8 form-group"><label for="pw_hero_texto">Frase</label><input class="form-control" id="pw_hero_texto" data-campo="hero_texto" maxlength="250"></div>
        </div>
        <label>Foto de portada</label>
        <div class="pw-foto" data-foto="hero">
          <img alt="Foto de portada">
          <div>
            <label class="btn btn-outline-primary btn-sm mb-1"><i class="fas fa-upload mr-1"></i>Cambiar foto<input type="file" accept="image/jpeg,image/png,image/webp" hidden></label>
            <button type="button" class="btn btn-link btn-sm pw-quitar-foto">Volver a la foto original</button>
            <small class="d-block text-muted">Horizontal, de buena calidad (por ejemplo 1600 × 900). Máximo 5 MB.</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-chart-bar mr-1"></i>Cifras<small>Los cuatro números destacados debajo de la portada. Deje el texto vacío para no mostrar una.</small></h3></div>
      <div class="card-body" id="pw_cifras"></div>
    </div>

    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-school mr-1"></i>Nuestra institución</h3></div>
      <div class="card-body">
        <div class="form-group"><label for="pw_nosotros_subtitulo">Frase de la sección</label><input class="form-control" id="pw_nosotros_subtitulo" data-campo="nosotros_subtitulo" maxlength="200"></div>
        <div class="form-group"><label for="pw_nosotros_encabezado">Encabezado <span class="text-danger">*</span></label><input class="form-control" id="pw_nosotros_encabezado" data-campo="nosotros_encabezado" maxlength="120"></div>
        <div class="form-group"><label for="pw_nosotros_texto">Presentación del colegio</label><textarea class="form-control" id="pw_nosotros_texto" data-campo="nosotros_texto" rows="6" maxlength="3000"></textarea><small class="text-muted">Deje una línea en blanco para separar párrafos.</small></div>
        <div class="form-group"><label for="pw_nosotros_puntos">Puntos destacados</label><textarea class="form-control" id="pw_nosotros_puntos" data-campo="nosotros_puntos" rows="4" maxlength="600"></textarea><small class="text-muted">Uno por línea.</small></div>
        <label>Foto o imagen</label>
        <div class="pw-foto" data-foto="nosotros">
          <img alt="Foto de la sección Nosotros">
          <div>
            <label class="btn btn-outline-primary btn-sm mb-1"><i class="fas fa-upload mr-1"></i>Cambiar foto<input type="file" accept="image/jpeg,image/png,image/webp" hidden></label>
            <button type="button" class="btn btn-link btn-sm pw-quitar-foto">Volver a la imagen original</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-layer-group mr-1"></i>Niveles educativos<small>Solo los niveles que se muestran aparecen en la página y en el formulario de contacto.</small></h3></div>
      <div class="card-body">
        <div class="form-group"><label for="pw_niveles_subtitulo">Frase de la sección</label><input class="form-control" id="pw_niveles_subtitulo" data-campo="niveles_subtitulo" maxlength="200"></div>
        <div class="row" id="pw_niveles"></div>
      </div>
    </div>

    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-heart mr-1"></i>Valores<small>Hasta seis. Deje el título vacío para no mostrar uno; sin ninguno, la sección no aparece.</small></h3></div>
      <div class="card-body">
        <div class="form-group"><label for="pw_valores_subtitulo">Frase de la sección</label><input class="form-control" id="pw_valores_subtitulo" data-campo="valores_subtitulo" maxlength="200"></div>
        <div id="pw_valores"></div>
      </div>
    </div>

    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-address-book mr-1"></i>Contacto y redes sociales<small>Lo que quede vacío no se muestra.</small></h3></div>
      <div class="card-body">
        <div class="form-group"><label for="pw_contacto_subtitulo">Frase de la sección</label><input class="form-control" id="pw_contacto_subtitulo" data-campo="contacto_subtitulo" maxlength="200"></div>
        <div class="row">
          <div class="col-md-6 form-group"><label for="pw_direccion">Dirección</label><input class="form-control" id="pw_direccion" data-campo="direccion" maxlength="150"></div>
          <div class="col-md-6 form-group"><label for="pw_horario">Horario de atención</label><input class="form-control" id="pw_horario" data-campo="horario" maxlength="120"></div>
          <div class="col-md-4 form-group"><label for="pw_telefono">Teléfono</label><input class="form-control" id="pw_telefono" data-campo="telefono" maxlength="40"></div>
          <div class="col-md-4 form-group"><label for="pw_email">Correo</label><input type="email" class="form-control" id="pw_email" data-campo="email" maxlength="120"></div>
          <div class="col-md-4 form-group"><label for="pw_whatsapp">WhatsApp</label><input class="form-control" id="pw_whatsapp" data-campo="whatsapp" maxlength="20" placeholder="51987654321"><small class="text-muted">Con el código del país. Muestra un botón de WhatsApp.</small></div>
          <div class="col-md-6 form-group"><label for="pw_facebook"><i class="fab fa-facebook mr-1"></i>Facebook</label><input class="form-control" id="pw_facebook" data-campo="facebook" maxlength="250" placeholder="https://facebook.com/..."></div>
          <div class="col-md-6 form-group"><label for="pw_instagram"><i class="fab fa-instagram mr-1"></i>Instagram</label><input class="form-control" id="pw_instagram" data-campo="instagram" maxlength="250" placeholder="https://instagram.com/..."></div>
          <div class="col-md-6 form-group"><label for="pw_tiktok"><i class="fab fa-tiktok mr-1"></i>TikTok</label><input class="form-control" id="pw_tiktok" data-campo="tiktok" maxlength="250" placeholder="https://tiktok.com/@..."></div>
          <div class="col-md-6 form-group"><label for="pw_youtube"><i class="fab fa-youtube mr-1"></i>YouTube</label><input class="form-control" id="pw_youtube" data-campo="youtube" maxlength="250" placeholder="https://youtube.com/@..."></div>
        </div>
      </div>
    </div>

    <div class="card pw-seccion">
      <div class="card-header"><h3><i class="fas fa-shoe-prints mr-1"></i>Pie de página y buscadores</h3></div>
      <div class="card-body">
        <div class="form-group"><label for="pw_pie_texto">Texto del pie de página</label><input class="form-control" id="pw_pie_texto" data-campo="pie_texto" maxlength="300"></div>
        <div class="form-group mb-0"><label for="pw_meta_descripcion">Descripción para Google</label><input class="form-control" id="pw_meta_descripcion" data-campo="meta_descripcion" maxlength="300"><small class="text-muted">Una o dos frases; es lo que aparece debajo del enlace en los resultados de búsqueda.</small></div>
      </div>
    </div>

    <div class="pw-barra">
      <a class="btn btn-outline-secondary" href="../" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i>Ver la página</a>
      <button type="button" class="btn btn-success" id="pw_guardar"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
    </div>
  </div>
</div>
<script>
  PaginaWeb.iniciar();
</script>
