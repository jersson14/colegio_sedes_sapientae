// Ayudas comunes de los formularios (Fase 13). Se carga en view/index.php y actúa sobre TODAS las ventanas (modales)
// sin tocar cada pantalla:
//  - Obligatorios: al pulsar Guardar, cada campo marcado (*) que esté vacío se señala junto a él y no se envía.
//  - Doble clic: el botón que envía queda bloqueado hasta que el servidor responde.
//  - Cambios sin guardar: al cerrar una ventana con datos modificados, se pregunta antes de descartarlos.
//  - Fechas con límites con sentido (nacimiento, entregas, pagos).
//  - Enter guarda (en campos de una línea) y el cursor empieza en el primer campo.
//  - Posible duplicado de personas ("¿Es otra persona?").
window.Formulario = (function ($) {
  var ultimoBoton = null, ultimoClic = 0;

  function hoy(dias) {
    var d = new Date(); d.setDate(d.getDate() + (dias || 0));
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  function anios(n) { var d = new Date(); d.setFullYear(d.getFullYear() - n); return d.toISOString().substr(0, 10); }

  // ---------- Campos obligatorios (*) ----------
  function campoDe(label) {
    var cont = $(label).closest('.form-group, [class*="col-"]');
    if (!cont.length) { cont = $(label).parent(); }
    var c = cont.find('input, select, textarea').filter(function () {
      var t = (this.type || '').toLowerCase();
      if (t === 'hidden' || t === 'file' || t === 'checkbox' || t === 'radio' || this.readOnly || this.disabled) { return false; }
      // visible, o en una pestaña no abierta de la ventana (p. ej. "Datos de los padres")
      return $(this).is(':visible') || $(this).hasClass('select2-hidden-accessible') || $(this).closest('.tab-pane').length > 0;
    }).first();
    return c.length ? c : null;
  }

  function nombreCampo(label) {
    return $.trim($(label).clone().children().remove().end().text()).replace(/[:*()]+/g, '').trim() || 'este dato';
  }

  function marcar(campo, mensaje) {
    var destino = campo.hasClass('select2-hidden-accessible') ? campo.next('.select2') : campo;
    campo.addClass('is-invalid');
    destino.addClass('fm-invalido');
    var fb = destino.nextAll('.fm-error').first();
    if (!fb.length) { fb = $('<div class="fm-error invalid-feedback d-block"></div>').insertAfter(destino); }
    fb.text(mensaje);
  }
  function limpiar(campo) {
    var destino = campo.hasClass('select2-hidden-accessible') ? campo.next('.select2') : campo;
    campo.removeClass('is-invalid'); destino.removeClass('fm-invalido');
    destino.nextAll('.fm-error').first().remove();
  }

  // Devuelve true si todos los obligatorios visibles de la ventana tienen valor
  function validarObligatorios(modal) {
    var primero = null;
    // Ventanas que arman una lista con "Agregar" (horarios, periodos, pagos…): sus (*) son de la fila a agregar
    // y la lista tiene su propia validación; aquí solo se revisan las fechas
    var armaLista = $(modal).find('[onclick]').filter(function () { return /agregar/i.test($(this).attr('onclick')) && !esBotonGuardar(this); }).length > 0;
    $(modal).find('label').filter(function () { return !armaLista && /\(\*\)/.test($(this).text()); }).each(function () {
      var campo = campoDe(this);
      if (!campo) { return; }
      var v = campo.val();
      var vacio = v === null || (Array.isArray(v) ? !v.length : !$.trim(String(v)));
      if (vacio) {
        marcar(campo, 'Falta: ' + nombreCampo(this));
        primero = primero || campo;
      } else {
        limpiar(campo);
      }
    });
    // Fechas escritas a mano fuera de los límites (el calendario ya no las ofrece)
    $(modal).find('input[type="date"], input[type="datetime-local"]').filter(':visible').not('[readonly], :disabled').each(function () {
      var v = this.value, c = $(this);
      if (!v || c.hasClass('is-invalid')) { return; }
      var f = function (x) { return x.substr(0, 10).split('-').reverse().join('/'); };
      if ((this.min && v < this.min) || (this.max && v > this.max)) {
        marcar(c, 'La fecha debe estar ' + (this.min && this.max ? 'entre el ' + f(this.min) + ' y el ' + f(this.max)
          : (this.min ? 'desde el ' + f(this.min) : 'hasta el ' + f(this.max))));
        primero = primero || c;
      }
    });
    if (primero) {
      var pestana = primero.closest('.tab-pane');
      if (pestana.length && !pestana.hasClass('active') && pestana.attr('id')) {
        $(modalDe(primero)).find('[href="#' + pestana.attr('id') + '"], [data-target="#' + pestana.attr('id') + '"]').tab('show');
      }
      (primero.hasClass('select2-hidden-accessible') ? primero.next('.select2').find('.select2-selection') : primero).trigger('focus');
      Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: 'warning', title: 'Complete los campos marcados en rojo' });
      return false;
    }
    return true;
  }

  function modalDe(el) { return $(el).closest('.modal'); }

  function esBotonGuardar(b) {
    var $b = $(b);
    return $b.is('button, a') && $b.closest('.modal-footer').length && !$b.is('[data-dismiss]') && !!$b.attr('onclick')
      && !/cerrar|cancelar|imprimir|descargar/i.test($b.text());
  }

  // ---------- Fechas con límites ----------
  function limitarFechas(raiz) {
    $(raiz).find('input[type="date"], input[type="datetime-local"]').each(function () {
      var id = (this.id || '') + ' ' + (this.name || '');
      var dt = this.type === 'datetime-local';
      if (/naci|fecha_na(_|$|\s)|fechana/i.test(id)) {
        // nacimiento: entre 100 y 2 años atrás
        if (!this.max) { this.max = anios(2); }
        if (!this.min) { this.min = anios(100); }
      } else if (/^txt_fecha_(entre|exa)\s/.test(id + ' ')) {
        // nueva tarea o examen (no al editar, que puede tener una fecha ya pasada): desde hoy
        if (!this.min) { this.min = dt ? hoy() + 'T00:00' : hoy(); }
      } else if (/fecha_pago|pagar|fecha_ade?(\s|$)|fecha_tr(\s|$)|anulacion|fecha_cierre/i.test(id)) {
        // pagos, adelantos, depósitos, anulaciones y cierres: no a futuro
        if (!this.max) { this.max = dt ? hoy() + 'T23:59' : hoy(); }
      }
    });
  }

  // ---------- Cambios sin guardar ----------
  function estado(modal) {
    return $(modal).find('input, select, textarea').not('[type="file"], [type="hidden"], [readonly], :disabled').map(function () {
      return (this.type === 'checkbox' || this.type === 'radio') ? this.checked : $(this).val();
    }).get().join('\u0001');
  }

  // ---------- Posible duplicado de personas ----------
  // "7|documento" desde el servidor: pregunta si es otra persona y, si se confirma, reenvía con confirmar_duplicado.
  function duplicado(resp, ajax, quien) {
    if (String(resp).indexOf('7|') !== 0) { return false; }
    var doc = String(resp).substr(2);
    var etiqueta = (window.REGIONAL && REGIONAL.doc_nombre) || 'documento';
    Swal.fire({
      icon: 'question',
      title: '¿Es otra persona?',
      html: 'Ya hay un ' + textoSeguro(quien || 'registro') + ' con el <b>mismo nombre y fecha de nacimiento</b>, con ' + textoSeguro(etiqueta)
        + ' <b>' + textoSeguro(doc) + '</b>.<br><small class="text-muted">Si es la misma persona, revise el ' + textoSeguro(etiqueta)
        + ' escrito: puede ser un error de tipeo.</small>',
      showCancelButton: true,
      confirmButtonText: 'Sí, es otra persona: registrar',
      cancelButtonText: 'Revisar los datos'
    }).then(function (r) {
      if (!r.isConfirmed) { return; }
      var datos = ajax.data;
      if (datos instanceof FormData) { datos.set('confirmar_duplicado', '1'); } else { datos = (datos ? datos + '&' : '') + 'confirmar_duplicado=1'; }
      $.ajax($.extend({}, ajax, { data: datos }));
    });
    return true;
  }

  // ---------- Conexión con todas las ventanas ----------
  function iniciar() {
    // Guardar: validar obligatorios antes de que corra el onclick de la pantalla (fase de captura)
    document.addEventListener('click', function (ev) {
      var b = ev.target.closest && ev.target.closest('button, a');
      if (!b || !esBotonGuardar(b)) { return; }
      if (b.disabled || $(b).hasClass('fm-enviando')) { ev.preventDefault(); ev.stopImmediatePropagation(); return; }
      var modal = $(b).closest('.modal');
      if (!validarObligatorios(modal)) { ev.preventDefault(); ev.stopImmediatePropagation(); return; }
      ultimoBoton = b; ultimoClic = Date.now();
    }, true);

    // Doble clic: el botón que disparó el envío queda bloqueado hasta la respuesta
    $(document).on('ajaxSend', function (e, xhr, op) {
      if (!ultimoBoton || Date.now() - ultimoClic > 1500 || String(op.type).toUpperCase() !== 'POST') { return; }
      var b = ultimoBoton; ultimoBoton = null;
      $(b).addClass('fm-enviando').prop('disabled', true).attr('aria-busy', 'true');
      xhr.always(function () {
        $(b).removeClass('fm-enviando').prop('disabled', false).removeAttr('aria-busy');
        var m = $(b).closest('.modal');
        // guardado: la pantalla puede limpiar y cerrar la ventana sin que se pregunte por cambios
        if (xhr.status >= 200 && xhr.status < 300) { m.data('fm-salir', true); }
      });
    });

    // Al escribir se quita la marca de error de ese campo
    $(document).on('input change', '.is-invalid', function () { limpiar($(this)); });

    // Ventanas: límites de fechas, foco en el primer campo y foto del estado inicial
    $(document).on('shown.bs.modal', '.modal', function () {
      var m = $(this);
      limitarFechas(m);
      m.find('.fm-error').remove();
      m.find('.is-invalid').removeClass('is-invalid');
      m.find('.fm-invalido').removeClass('fm-invalido');
      m.data('fm-estado', estado(m)).data('fm-salir', false);
      if (!m.find(':focus').length) {
        m.find('.modal-body').find('input:not([type=hidden]):not([readonly]):not(:disabled), select:not(:disabled), textarea:not([readonly])').filter(':visible').first().trigger('focus');
      }
    });

    // Cerrar con cambios sin guardar: confirmar
    $(document).on('hide.bs.modal', '.modal', function (ev) {
      var m = $(this);
      if (m.data('fm-salir') || !m.find('.modal-footer [onclick]').length) { return; }
      var inicial = m.data('fm-estado');
      if (inicial === undefined || inicial === estado(m)) { return; }
      ev.preventDefault();
      Swal.fire({ icon: 'question', title: '¿Descartar los cambios?', text: 'Hay datos escritos que no se guardaron.',
        showCancelButton: true, confirmButtonText: 'Sí, descartar', cancelButtonText: 'Seguir editando' })
        .then(function (r) { if (r.isConfirmed) { m.data('fm-salir', true).modal('hide'); } });
    });

    // Enter en un campo de una línea = pulsar Guardar de esa ventana
    $(document).on('keydown', '.modal input:not([type=file]):not([type=checkbox]):not([type=radio])', function (ev) {
      if (ev.key !== 'Enter' || ev.isComposing || this.type === 'search' || $(this).closest('.dataTables_wrapper, .tm-panel, .select2-container').length) { return; }
      var b = $(this).closest('.modal').find('.modal-footer').find('button, a').filter(':visible').filter(function () { return esBotonGuardar(this); }).first();
      if (b.length) { ev.preventDefault(); b[0].click(); }
    });

    // Pantallas (no ventanas): límites de fechas al cargarse
    $(document).on('ajaxComplete', function () { limitarFechas(document.getElementById('contenido_principal') || document.body); });
  }

  $(iniciar);

  return { duplicado: duplicado, validarObligatorios: validarObligatorios, limitarFechas: limitarFechas };
})(window.jQuery);
