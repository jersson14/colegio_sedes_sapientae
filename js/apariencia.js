// Configuración > Apariencia (Fase 12): colores, menú, lema y escudo del colegio, con vista previa en vivo en todo el sistema.
window.Apariencia = (function () {
  var URL = '../controller/configuracion/controlador_apariencia.php';
  var actual = null, presets = [], estilo = 'COLOR', espera = null, cambios = false;

  function hexValido(v) { return /^#[0-9A-F]{6}$/i.test(v); }

  function pintarPresets() {
    var p = $('#ap_primario').val().toUpperCase(), a = $('#ap_acento').val().toUpperCase();
    $('#ap_presets').html(presets.map(function (x, i) {
      var activo = x.color_primario === p && x.color_acento === a;
      return '<button type="button" class="ap-preset' + (activo ? ' activo' : '') + '" data-i="' + i + '"><span class="ap-chips"><i style="background:' + x.color_primario
        + '"></i><i style="background:' + x.color_acento + '"></i></span>' + textoSeguro(x.nombre) + '</button>';
    }).join(''));
  }

  function poner(p, a) {
    $('#ap_primario, #ap_primario_sel').val(p.toUpperCase());
    $('#ap_acento, #ap_acento_sel').val(a.toUpperCase());
    previsualizar();
  }

  // Aplica los colores elegidos a todo el sistema (solo en esta pantalla, hasta guardar)
  function previsualizar() {
    var p = $('#ap_primario').val(), a = $('#ap_acento').val();
    pintarPresets();
    $('#ap_estilos .ap-estilo').removeClass('activo').filter('[data-estilo="' + estilo + '"]').addClass('activo');
    if (!hexValido(p) || !hexValido(a)) { return; }
    clearTimeout(espera);
    espera = setTimeout(function () {
      $.post(URL, { accion: 'previsualizar', primario: p, acento: a, estilo_menu: estilo }, null, 'json').done(function (r) {
        Object.keys(r.variables).forEach(function (k) { document.documentElement.style.setProperty(k, r.variables[k]); });
        $('body').removeClass('tema-menu-color tema-menu-oscuro tema-menu-claro').addClass('tema-menu-' + estilo.toLowerCase());
        $('#ap_aviso').html(r.aviso ? '<div class="alert alert-warning small">' + textoSeguro(r.aviso) + '</div>' : '');
        cambios = true;
      });
    }, 150);
  }

  function guardar() {
    var p = $('#ap_primario').val(), a = $('#ap_acento').val();
    if (!hexValido(p) || !hexValido(a)) { return Swal.fire('Color inválido', 'Escriba el color como #RRGGBB, por ejemplo #7A1F2B.', 'warning'); }
    $.post(URL, { accion: 'guardar', primario: p, acento: a, estilo_menu: estilo, lema: $('#ap_lema').val() }, null, 'json').done(function () {
      cambios = false;
      Swal.fire({ icon: 'success', title: 'Apariencia guardada', text: 'Todos verán los nuevos colores al entrar o recargar.', timer: 2200, showConfirmButton: false })
        .then(function () { location.reload(); });
    });
  }

  function descartar() { location.reload(); }

  function cambiarEscudo() {
    $.post('../controller/empresa/controlador_listar_empresa.php', {}, null, 'json').done(function (r) {
      var e = (r.data || [])[0];
      if (e && typeof abrirFotoEmpresa === 'function') { abrirFotoEmpresa(e); }
    });
  }

  function iniciar() {
    $.post(URL, { accion: 'cargar' }, null, 'json').done(function (r) {
      actual = r.tema; presets = r.presets; estilo = actual.estilo_menu;
      $('#ap_lema').val(textoPlano(actual.lema || ''));
      $('#ap_logo, #ap_m_logo').attr('src', '../' + (actual.logo || 'img/logo.jpeg'));
      $('#ap_m_nombre').text(textoPlano(actual.nombre || ''));
      $('#ap_m_lema').text(textoPlano(actual.lema || ''));
      $('#ap_primario, #ap_primario_sel').val(actual.primario);
      $('#ap_acento, #ap_acento_sel').val(actual.acento);
      pintarPresets();
      $('#ap_estilos .ap-estilo').removeClass('activo').filter('[data-estilo="' + estilo + '"]').addClass('activo');
    });
    $('#ap_presets').off('click').on('click', '.ap-preset', function () { var x = presets[+$(this).data('i')]; poner(x.color_primario, x.color_acento); });
    $('#ap_primario_sel').off('input').on('input', function () { $('#ap_primario').val(this.value.toUpperCase()); previsualizar(); });
    $('#ap_acento_sel').off('input').on('input', function () { $('#ap_acento').val(this.value.toUpperCase()); previsualizar(); });
    $('#ap_primario').off('input').on('input', function () { if (hexValido(this.value)) { $('#ap_primario_sel').val(this.value); } previsualizar(); });
    $('#ap_acento').off('input').on('input', function () { if (hexValido(this.value)) { $('#ap_acento_sel').val(this.value); } previsualizar(); });
    $('#ap_estilos').off('click').on('click', '.ap-estilo', function () { estilo = $(this).data('estilo'); previsualizar(); });
    $('#ap_lema').off('input').on('input', function () { $('#ap_m_lema').text(this.value); cambios = true; });
  }

  return { iniciar: iniciar, guardar: guardar, descartar: descartar, cambiarEscudo: cambiarEscudo };
})();
