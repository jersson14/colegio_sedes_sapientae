// Configuración → Página web: contenido de la página pública del colegio (landing.php).
window.PaginaWeb = (function () {
  var URL = '../controller/configuracion/controlador_pagina_web.php';
  var datos = null, cambios = false;

  function esc(t) { return textoSeguro(t == null ? '' : String(t)); }

  function pintarCifras() {
    $('#pw_cifras').html(datos.cifras.map(function (c, i) {
      return '<div class="pw-fila" data-i="' + i + '"><input class="form-control pw-icono" data-c="icono" maxlength="16" value="' + esc(c.icono) + '" aria-label="Ícono de la cifra ' + (i + 1) + '">' +
        '<input type="number" min="0" max="999999" class="form-control" data-c="numero" value="' + esc(c.numero) + '" aria-label="Número de la cifra ' + (i + 1) + '">' +
        '<input class="form-control" data-c="texto" maxlength="40" value="' + esc(c.texto) + '" placeholder="Texto (vacío = no se muestra)" aria-label="Texto de la cifra ' + (i + 1) + '"></div>';
    }).join(''));
  }

  function pintarNiveles() {
    $('#pw_niveles').html(datos.niveles.map(function (n, i) {
      return '<div class="col-lg-4 mb-3"><div class="pw-nivel' + (n.mostrar ? '' : ' oculto') + '" data-i="' + i + '">' +
        '<div class="d-flex justify-content-between align-items-center mb-2"><div class="custom-control custom-switch">' +
        '<input type="checkbox" class="custom-control-input" id="pw_nivel_mostrar_' + i + '" data-c="mostrar"' + (n.mostrar ? ' checked' : '') + '>' +
        '<label class="custom-control-label font-weight-bold" for="pw_nivel_mostrar_' + i + '">Mostrar</label></div>' +
        '<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="pw_nivel_dest_' + i + '" data-c="destacado"' + (n.destacado ? ' checked' : '') + '>' +
        '<label class="custom-control-label" for="pw_nivel_dest_' + i + '">Destacado</label></div></div>' +
        '<div class="d-flex mb-2" style="gap:8px"><input class="form-control pw-icono" style="width:64px" data-c="icono" maxlength="16" value="' + esc(n.icono) + '" aria-label="Ícono">' +
        '<input class="form-control" data-c="titulo" maxlength="60" value="' + esc(n.titulo) + '" aria-label="Nombre del nivel"></div>' +
        '<textarea class="form-control mb-2" rows="3" data-c="texto" maxlength="400" aria-label="Descripción">' + esc(n.texto) + '</textarea>' +
        '<label class="small mb-1">Puntos (uno por línea)</label><textarea class="form-control mb-2" rows="4" data-c="puntos" maxlength="400">' + esc(n.puntos) + '</textarea>' +
        '<input class="form-control" data-c="edad" maxlength="40" value="' + esc(n.edad) + '" placeholder="Edades, ej.: 3 - 5 años" aria-label="Edades"></div></div>';
    }).join(''));
  }

  function pintarValores() {
    var lista = datos.valores.slice();
    while (lista.length < 6) { lista.push({ icono: '', titulo: '', texto: '' }); }
    $('#pw_valores').html(lista.map(function (v, i) {
      return '<div class="pw-fila-valor" data-i="' + i + '"><input class="form-control pw-icono" data-c="icono" maxlength="16" value="' + esc(v.icono) + '" aria-label="Ícono del valor ' + (i + 1) + '">' +
        '<input class="form-control" data-c="titulo" maxlength="60" value="' + esc(v.titulo) + '" placeholder="Título" aria-label="Título del valor ' + (i + 1) + '">' +
        '<input class="form-control" data-c="texto" maxlength="300" value="' + esc(v.texto) + '" placeholder="Descripción" aria-label="Descripción del valor ' + (i + 1) + '"></div>';
    }).join(''));
  }

  function pintarFotos() {
    $('.pw-foto').each(function () {
      var campo = $(this).data('foto') + '_imagen';
      $(this).find('img').attr('src', '../' + datos[campo] + '?v=' + Date.now());
      $(this).find('.pw-quitar-foto').toggle(datos[campo] !== PaginaWeb.defecto[campo]);
    });
  }

  function leerFilas(sel) {
    return $(sel).find('[data-i]').map(function () {
      var o = {};
      $(this).find('[data-c]').each(function () { o[$(this).data('c')] = this.type === 'checkbox' ? this.checked : $(this).val(); });
      return o;
    }).get();
  }

  function guardar() {
    var d = {};
    $('#pw_form [data-campo]').each(function () { d[$(this).data('campo')] = $(this).val(); });
    d.cifras = leerFilas('#pw_cifras');
    d.niveles = leerFilas('#pw_niveles');
    d.valores = leerFilas('#pw_valores');
    if (!$.trim(d.hero_titulo)) { $('#pw_hero_titulo').focus(); return Swal.fire('Falta el título', 'Escriba el título de la portada.', 'warning'); }
    if (!d.niveles.some(function (n) { return n.mostrar; })) { return Swal.fire('Sin niveles', 'Muestre al menos un nivel educativo.', 'warning'); }
    var $b = $('#pw_guardar').prop('disabled', true);
    $.post(URL, { accion: 'guardar', datos: JSON.stringify(d) }, null, 'json')
      .done(function () {
        cambios = false;
        Swal.fire({ icon: 'success', title: 'Página guardada', text: 'Los cambios ya se ven en la página pública.', showCancelButton: true,
          confirmButtonText: 'Ver la página', cancelButtonText: 'Seguir editando' }).then(function (r) { if (r.isConfirmed) { window.open('../', '_blank'); } });
      })
      .fail(function (x) { Swal.fire('No se pudo guardar', textoSeguro((x.responseJSON && x.responseJSON.mensaje) || 'Intente de nuevo.'), 'error'); })
      .always(function () { $b.prop('disabled', false); });
  }

  function subirFoto(foto, archivo) {
    var f = new FormData();
    f.append('accion', 'imagen');
    f.append('campo', foto);
    f.append('imagen', archivo);
    $.ajax({ url: URL, type: 'POST', data: f, processData: false, contentType: false, dataType: 'json' })
      .done(function (r) { datos[foto + '_imagen'] = r.ruta; pintarFotos(); Swal.fire({ icon: 'success', title: 'Foto cambiada', timer: 1500, showConfirmButton: false }); })
      .fail(function (x) { Swal.fire('No se pudo subir la foto', textoSeguro((x.responseJSON && x.responseJSON.mensaje) || 'Intente de nuevo.'), 'error'); });
  }

  function quitarFoto(foto) {
    $.post(URL, { accion: 'quitar_imagen', campo: foto }, null, 'json').done(function (r) { datos[foto + '_imagen'] = r.ruta; pintarFotos(); });
  }

  function iniciar() {
    $.post(URL, { accion: 'cargar' }, null, 'json').done(function (r) {
      datos = r.datos;
      PaginaWeb.defecto = r.defecto;
      $('#pw_form [data-campo]').each(function () { $(this).val(datos[$(this).data('campo')]); });
      pintarCifras(); pintarNiveles(); pintarValores(); pintarFotos();
    });
    $('#pw_form').off('.pw')
      .on('input.pw change.pw', 'input, textarea', function () { cambios = true; })
      .on('change.pw', '[data-c="mostrar"]', function () { $(this).closest('.pw-nivel').toggleClass('oculto', !this.checked); })
      .on('change.pw', '.pw-foto input[type=file]', function () { if (this.files[0]) { subirFoto($(this).closest('.pw-foto').data('foto'), this.files[0]); } this.value = ''; })
      .on('click.pw', '.pw-quitar-foto', function () { quitarFoto($(this).closest('.pw-foto').data('foto')); });
    $('#pw_guardar').off('click').on('click', guardar);
  }

  return { iniciar: iniciar, defecto: {} };
})();
