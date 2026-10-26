// Matrícula paso a paso (Fase 13): Estudiante → Padres → Aula y vacantes → Pagos y beca → Resumen.
// Los datos de cada paso salen de controller/matricula/controlador_contexto.php; el registro usa el mismo
// controlador de siempre (controlador_registro_matriculas.php) y, si se eligió beca, controlador_beca_guardar.php.
var MP = { paso: 1, ctx: null, est: null, aula: null, montosEditados: false };

function MP_Num(v) { var n = parseFloat(String(v || '0').replace(',', '.')); return isNaN(n) ? 0 : n; }
function MP_Fecha(iso) { return iso ? String(iso).substr(0, 10).split('-').reverse().join('/') : ''; }
function MP_Aula(a) { return a.nivel + ' - ' + a.grado + (a.seccion && a.seccion !== 'UNICO' ? ' ' + a.seccion : ''); }

function Matricula_Abrir() {
  MP.paso = 1; MP.est = null; MP.aula = null; MP.montosEditados = false;
  $('#modal_registro').find('input[type=text], input[type=email], input[type=hidden]').val('');
  $('#txt_admision, #txt_alum_nuevo, #txt_matricula').val('0');
  $('#checkboxSuccess1').prop('checked', false);
  $('#mp_beca_tipo').val('NINGUNA');
  $('#select_estudiante').val('').trigger('change.select2');
  $('#mp_estudiante, #mp_padres, #mp_aulas, #mp_resumen').empty();
  Cargar_Formas_Pago_Matricula();
  Matricula_Contexto(function () { Matricula_Ir(1); }, true);
  $('#modal_registro').modal({ backdrop: 'static', keyboard: false }).modal('show');
}

// Contexto del año (aulas, pensiones, montos, estudiantes sin matrícula) y, si hay estudiante elegido, sus datos.
// conLista: rehace la lista de estudiantes (al abrir y al cambiar de año)
function Matricula_Contexto(listo, conLista) {
  var estu = $('#select_estudiante').val() || '';
  $.ajax({
    url: '../controller/matricula/controlador_contexto.php', type: 'POST', dataType: 'json',
    data: { anio: $('#select_año').val() || 0, estu: estu }
  }).done(function (r) {
    MP.ctx = r;
    if ($('#select_año option[value="' + r.anio + '"]').length) { $('#select_año').val(r.anio); }
    if (conLista) { Matricula_Lista(r.estudiantes, estu); }
    MP.est = estu && $('#select_estudiante').val() ? r.estudiante : null;
    if (MP.aula && !r.aulas.some(function (a) { return +a.id === +MP.aula.id; })) { MP.aula = null; }
    Matricula_Pintar_Estudiante();
    if (listo) { listo(); }
  });
}

// Estudiantes que todavía no tienen matrícula en el año elegido (nuevos y los que vuelven)
function Matricula_Lista(lista, elegido) {
  var doc = (window.REGIONAL && REGIONAL.doc_nombre) || 'Doc.';
  var h = '<option value="">' + (lista.length ? 'Busque por apellido, nombre o documento' : 'Todos los estudiantes ya están matriculados en este año') + '</option>';
  lista.forEach(function (e) {
    h += '<option value="' + e.id + '">' + textoSeguro(textoPlano(e.apellidos + ', ' + e.nombres)) + ' — ' + textoSeguro(doc) + ' ' + textoSeguro(e.documento)
      + (e.tipo === 'NUEVO' ? ' (nuevo)' : '') + '</option>';
  });
  var $s = $('#select_estudiante');
  if ($s.hasClass('select2-hidden-accessible')) { $s.select2('destroy'); }
  $s.html(h).val(lista.some(function (e) { return String(e.id) === String(elegido); }) ? elegido : '');
  $s.select2({ dropdownParent: $('#modal_registro'), width: '100%' });
}

function Matricula_Ir(n) {
  MP.paso = Math.max(1, Math.min(5, n));
  $('#modal_registro .mp-paso').prop('hidden', true).filter('[data-paso="' + MP.paso + '"]').prop('hidden', false);
  $('#modal_registro .mp-pasos li').each(function () {
    var p = +$(this).data('paso');
    $(this).toggleClass('hecho', p < MP.paso).toggleClass('actual', p === MP.paso).attr('aria-current', p === MP.paso ? 'step' : null);
  });
  $('#mp_atras').prop('hidden', MP.paso === 1);
  $('#mp_siguiente').prop('hidden', MP.paso === 5);
  $('#mp_registrar').prop('hidden', MP.paso !== 5);
  if (MP.paso === 2) { Matricula_Pintar_Padres(); }
  if (MP.paso === 3) { Matricula_Pintar_Aulas(); }
  if (MP.paso === 4) { Matricula_Preparar_Pagos(); }
  if (MP.paso === 5) { Matricula_Pintar_Resumen(); }
  var primero = $('#modal_registro .mp-paso:not([hidden])').find('input:visible, select:visible, button.mp-aula:not(:disabled)').first();
  if (primero.length && MP.paso > 1) { setTimeout(function () { primero.trigger('focus'); }, 50); }
}

function Matricula_Aviso(t) {
  Swal.fire({ toast: true, position: 'top-end', timer: 3500, showConfirmButton: false, icon: 'warning', title: t });
  return false;
}

function Matricula_Validar(paso) {
  if (paso === 1) {
    if (!$('#select_estudiante').val()) { Formulario.validarObligatorios($('.mp-paso[data-paso="1"]')); return false; }
    if (!MP.est) { return Matricula_Aviso('Espere: cargando los datos del estudiante'); }
    if (MP.est.en_anio) { return Matricula_Aviso('Ya está matriculado en ' + MP.est.en_anio.anio + ' (' + MP.est.en_anio.aula + ')'); }
    return true;
  }
  if (paso === 3) {
    return MP.aula ? true : Matricula_Aviso('Elija un aula');
  }
  if (paso === 4) {
    var sec = $('.mp-paso[data-paso="4"]');
    if (!Formulario.validarObligatorios(sec)) { return false; }
    var malMonto = ['txt_admision', 'txt_alum_nuevo', 'txt_matricula'].filter(function (id) {
      return $('#' + id).is(':visible') && !/^\d{1,9}([.,]\d{1,2})?$/.test($.trim($('#' + id).val() || '0'));
    });
    if (malMonto.length) { $('#' + malMonto[0]).trigger('focus'); return Matricula_Aviso('Escriba los montos solo con números (por ejemplo 150 o 150.50)'); }
    if (!MP.est.usuario) {
      var u = $.trim($('#txt_usu').val()), c = $.trim($('#txt_contra').val()), m = $.trim($('#txt_correo').val());
      if (c.length < 8) { $('#txt_contra').trigger('focus'); return Matricula_Aviso('La contraseña debe tener al menos 8 caracteres (puede usar Generar)'); }
      if (c.toUpperCase() === u.toUpperCase()) { $('#txt_contra').trigger('focus'); return Matricula_Aviso('La contraseña no puede ser igual al usuario'); }
      if (m && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(m)) { $('#txt_correo').trigger('focus'); return Matricula_Aviso('Revise el correo electrónico'); }
    }
    var bt = $('#mp_beca_tipo').val(), bv = MP_Num($('#mp_beca_valor').val());
    if (bt !== 'NINGUNA' && (bv <= 0 || (bt === 'PORCENTAJE' && bv > 100))) {
      $('#mp_beca_valor').trigger('focus');
      return Matricula_Aviso(bt === 'PORCENTAJE' ? 'El porcentaje de beca debe estar entre 1 y 100' : 'Indique el monto de la beca');
    }
    return true;
  }
  return true;
}

// ---------- Paso 1: estudiante ----------
function Matricula_Pintar_Estudiante() {
  var e = MP.est, $c = $('#mp_estudiante');
  if (!e) { $c.empty(); return; }
  var h = '<div class="mp-tarjeta"><div class="mp-tarjeta-titulo"><b>' + textoSeguro(PanelComun.titulo(e.nombre)) + '</b> <span class="mp-chip">'
    + textoSeguro(e.documento) + '</span> <span class="mp-chip ' + (e.tipo === 'NUEVO' ? 'mp-chip-nuevo' : '') + '">' + (e.tipo === 'NUEVO' ? 'Nuevo en el colegio' : 'Estudiante antiguo') + '</span></div><ul class="mp-datos">';
  h += '<li><i class="fas fa-history"></i>' + (e.ultima ? 'Último año: ' + textoSeguro(e.ultima.anio) + ' en ' + textoSeguro(e.ultima.aula) : 'Primera matrícula en el sistema') + '</li>';
  h += '<li><i class="fas fa-user-lock"></i>' + (e.usuario ? 'Conserva su usuario <b>' + textoSeguro(e.usuario) + '</b>' : 'Se le creará un usuario en el paso de pagos') + '</li></ul>';
  if (e.en_anio) {
    h += '<div class="alert alert-warning py-2 mb-0 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i>Ya está matriculado en <b>' + textoSeguro(e.en_anio.anio)
      + '</b> (' + textoSeguro(e.en_anio.aula) + '). Elija otro estudiante u otro año.</div>';
  }
  $c.html(h + '</div>');
}

// ---------- Paso 2: padres ----------
function Matricula_Pintar_Padres() {
  var p = MP.est.padres || {};
  var persona = function (rol, nombre, doc, cel) {
    var faltan = [];
    if (!nombre) { faltan.push('nombre'); }
    if (!cel) { faltan.push('celular'); }
    return '<div class="mp-persona' + (faltan.length ? ' mp-incompleto' : '') + '"><span class="mp-rol">' + rol + '</span>'
      + (nombre ? '<b>' + textoSeguro(PanelComun.titulo(nombre)) + '</b>' : '<b class="text-muted">Sin registrar</b>')
      + '<small>' + (doc ? textoSeguro(REGIONAL.doc_nombre || 'Documento') + ' ' + textoSeguro(doc) : 'Sin documento')
      + (cel ? ' · <a href="tel:' + textoSeguro(cel) + '">' + textoSeguro(cel) + '</a>' : '') + '</small>'
      + (faltan.length && nombre ? '<small class="text-danger">Falta: ' + faltan.join(', ') + '</small>' : '') + '</div>';
  };
  var h = '<div class="mp-personas">' + persona('Padre', p.padre, p.dni_padre, p.cel_padre) + persona('Madre', p.madre, p.dni_madre, p.cel_madre) + '</div>';
  if (!p.madre && !p.padre) {
    h += '<div class="alert alert-warning py-2 mt-2 mb-0">No tiene padres registrados. Puede matricular igual y completarlos luego con <b>Editar</b> en Estudiantes.</div>';
  } else if (!p.cel_madre && !p.cel_padre) {
    h += '<div class="alert alert-warning py-2 mt-2 mb-0">No hay ningún celular de contacto. Complételo luego con <b>Editar</b> en Estudiantes.</div>';
  }
  $('#mp_padres').html(h);
}

// ---------- Paso 3: aula y vacantes ----------
function Matricula_Pintar_Aulas() {
  var aulas = MP.ctx.aulas, h = '', nivel = null;
  $('#mp_aula_ayuda').html('Elija el aula. Las que ya no tienen vacantes no se pueden elegir.'
    + (MP.est.ultima ? ' <b>El año anterior estuvo en ' + textoSeguro(MP.est.ultima.aula) + '.</b>' : ''));
  if (!aulas.length) { $('#mp_aulas').html('<p class="text-muted">No hay aulas registradas.</p>'); return; }
  aulas.forEach(function (a) {
    if (a.nivel !== nivel) { h += (nivel ? '</div>' : '') + '<p class="mp-nivel">' + textoSeguro(PanelComun.titulo(a.nivel)) + '</p><div class="mp-aulas">'; nivel = a.nivel; }
    var llena = a.vacantes !== null && +a.vacantes <= 0;
    var detalle = a.capacidad === null ? a.matriculados + ' matriculados' : (llena ? 'Sin vacantes' : a.vacantes + ' de ' + a.capacidad + ' vacantes');
    h += '<button type="button" class="mp-aula' + (MP.aula && +MP.aula.id === +a.id ? ' elegida' : '') + (llena ? ' llena' : '') + '" data-id="' + a.id + '"'
      + (llena ? ' disabled aria-disabled="true"' : '') + ' aria-pressed="' + (MP.aula && +MP.aula.id === +a.id) + '"><b>' + textoSeguro(PanelComun.titulo(a.grado))
      + (a.seccion && a.seccion !== 'UNICO' ? ' ' + textoSeguro(a.seccion) : '') + '</b><span>' + detalle + '</span></button>';
  });
  $('#mp_aulas').html(h + '</div>');
}

// ---------- Paso 4: pagos, cuenta y beca ----------
function Matricula_Preparar_Pagos() {
  var e = MP.est, nivel = MP.aula.id_nivel, m = (MP.ctx.montos || {})[nivel];
  if (!MP.montosEditados) {
    $('#checkboxSuccess1').prop('checked', e.tipo === 'NUEVO' && !e.ultima);
    $('#txt_matricula').val(m ? (+m.matricula).toFixed(2) : '0');
    $('#txt_admision').val(m && m.admision !== null ? (+m.admision).toFixed(2) : '0');
    $('#txt_alum_nuevo').val(m && m.nuevo !== null ? (+m.nuevo).toFixed(2) : '0');
    $('#mp_montos_ayuda').text(m ? 'Montos sugeridos: los de la última matrícula de ' + PanelComun.titulo(MP.aula.nivel) + ' este año. Puede cambiarlos.' : '');
  }
  if (!e.usuario && !$.trim($('#txt_usu').val())) {
    var nombre = textoPlano(e.nombre).split(' ')[0].normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^A-Za-z]/g, '').toUpperCase();
    $('#txt_usu').val(nombre + String(e.documento).slice(-3));
  }
  Matricula_Actualizar_Pagos();
}

function Matricula_Actualizar_Pagos() {
  var nuevo = $('#checkboxSuccess1').is(':checked');
  $('.mp-nuevo').toggle(nuevo);
  if (!nuevo) { $('#txt_admision, #txt_alum_nuevo').val('0'); }
  $('.mp-cuenta').toggle(!!MP.est && !MP.est.usuario);
  $('.mp-cobro').toggle(Matricula_Total() > 0);
  var bt = $('#mp_beca_tipo').val();
  $('.mp-beca').toggle(bt !== 'NINGUNA');
  $('#mp_beca_valor_lb').text(bt === 'PORCENTAJE' ? 'Porcentaje (%):' : 'Descuento por pensión:');
}

function Matricula_Total() {
  return MP_Num($('#txt_admision').val()) + MP_Num($('#txt_alum_nuevo').val()) + MP_Num($('#txt_matricula').val());
}

// ---------- Paso 5: resumen ----------
function Matricula_Pintar_Resumen() {
  var e = MP.est, a = MP.aula, p = (MP.ctx.pensiones || {})[a.id_nivel], h = '';
  var fila = function (t, v) { return '<tr><td>' + t + '</td><td class="text-right">' + v + '</td></tr>'; };
  h += '<div class="mp-resumen-cab"><div><span class="mp-rol">Estudiante</span><b>' + textoSeguro(PanelComun.titulo(e.nombre)) + '</b><small>' + textoSeguro(e.documento) + '</small></div>'
    + '<div><span class="mp-rol">Año y aula</span><b>' + textoSeguro($('#select_año option:selected').text()) + ' · ' + textoSeguro(MP_Aula(a)) + '</b>'
    + '<small>' + (a.vacantes === null ? 'Aula sin límite de vacantes' : 'Quedarán ' + (a.vacantes - 1) + ' vacantes') + '</small></div></div>';
  // Lo que se cobra hoy
  var total = Matricula_Total(), cobros = '';
  if (MP_Num($('#txt_admision').val())) { cobros += fila('Admisión', formatoMoneda(MP_Num($('#txt_admision').val()))); }
  if (MP_Num($('#txt_alum_nuevo').val())) { cobros += fila('Alumno nuevo', formatoMoneda(MP_Num($('#txt_alum_nuevo').val()))); }
  cobros += fila('Matrícula', formatoMoneda(MP_Num($('#txt_matricula').val())));
  h += '<h6 class="mp-sub">Se cobra hoy</h6><table class="table table-sm mp-tabla"><tbody>' + cobros + '</tbody><tfoot><tr><th>Total</th><th class="text-right">' + formatoMoneda(total) + '</th></tr></tfoot></table>';
  if (total > 0) {
    h += '<p class="mp-nota">Forma de pago: ' + textoSeguro($('#select_metodo_matricula option:selected').text()) + ' · ' + textoSeguro($('#select_cuenta_matricula option:selected').text())
      + ($.trim($('#txt_ref_matricula').val()) ? ' · Ref. ' + textoSeguro($('#txt_ref_matricula').val()) : '') + '. Se emitirá el recibo.</p>';
  } else {
    h += '<p class="mp-nota">No se cobra nada hoy: no se emite recibo.</p>';
  }
  // Pensiones del año
  h += '<h6 class="mp-sub">Pensiones del año</h6>';
  if (!p) {
    h += '<p class="mp-nota text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Este nivel todavía no tiene pensiones configuradas para el año.</p>';
  } else {
    var bt = $('#mp_beca_tipo').val(), bv = MP_Num($('#mp_beca_valor').val());
    var precio = +p.minimo === +p.maximo ? formatoMoneda(p.minimo) : 'entre ' + formatoMoneda(p.minimo) + ' y ' + formatoMoneda(p.maximo);
    h += '<p class="mp-nota">' + p.cantidad + ' pensiones de ' + precio + ' (total ' + formatoMoneda(p.total) + '). La primera vence el ' + MP_Fecha(p.primera) + '.';
    if (bt !== 'NINGUNA') {
      var con = bt === 'PORCENTAJE' ? +p.minimo * (1 - bv / 100) : Math.max(0, +p.minimo - bv);
      h += ' <b>Con beca ' + (bt === 'PORCENTAJE' ? 'del ' + bv + '%' : 'de ' + formatoMoneda(bv)) + ': ' + formatoMoneda(con) + ' cada una.</b>';
    }
    h += '</p>';
  }
  // Acceso al sistema
  h += '<h6 class="mp-sub">Acceso al sistema</h6><p class="mp-nota">' + (e.usuario
    ? 'Conserva su usuario <b>' + textoSeguro(e.usuario) + '</b>.'
    : 'Se creará el usuario <b>' + textoSeguro($.trim($('#txt_usu').val()).toUpperCase()) + '</b> con contraseña temporal <b>' + textoSeguro($('#txt_contra').val()) + '</b> (la cambiará en su primer ingreso).') + '</p>';
  $('#mp_resumen').html(h);
}

// ---------- Registrar ----------
function Matricula_Registrar() {
  var e = MP.est, nuevoUsuario = !e.usuario;
  var datos = {
    estu: e.id, 'año': $('#select_año').val(), aula: MP.aula.id,
    admi: MP_Num($('#txt_admision').val()).toFixed(2), nuevo: MP_Num($('#txt_alum_nuevo').val()).toFixed(2), matri: MP_Num($('#txt_matricula').val()).toFixed(2),
    proce: $('#txt_procedencia').val(), pro: $('#txt_provincia').val(), depa: $('#txt_departamento').val(),
    usu: nuevoUsuario ? $.trim($('#txt_usu').val()) : '', contra: nuevoUsuario ? $('#txt_contra').val() : '', correo: nuevoUsuario ? $.trim($('#txt_correo').val()) : '',
    metodo: $('#select_metodo_matricula').val(), cuenta: $('#select_cuenta_matricula').val(), referencia: $('#txt_ref_matricula').val()
  };
  var mensajes = {
    2: 'El estudiante ya está matriculado en ese año escolar.',
    3: 'Ese nombre de usuario ya existe. Vuelva al paso de pagos y escriba otro.',
    4: 'Falta el usuario del estudiante (es su primera matrícula).',
    5: 'Revise la forma de pago: elija la caja o cuenta e indique el n.° de operación si corresponde.',
    6: 'El aula elegida ya no tiene vacantes. Vuelva al paso del aula y elija otra.'
  };
  $.ajax({ url: '../controller/matricula/controlador_registro_matriculas.php', type: 'POST', data: datos }).done(function (resp) {
    resp = parseInt(resp, 10);
    if (resp !== 1) {
      Swal.fire('No se registró la matrícula', mensajes[resp] || 'No se completó el registro.', 'warning');
      if (resp === 3) { Matricula_Ir(4); } else if (resp === 6) { Matricula_Contexto(function () { Matricula_Ir(3); }); }
      return;
    }
    var terminar = function (notaBeca) {
      var html = '<p>Matrícula de <b>' + textoSeguro(PanelComun.titulo(e.nombre)) + '</b> en <b>' + textoSeguro(MP_Aula(MP.aula)) + '</b>.</p>' + (notaBeca || '');
      if (nuevoUsuario) {
        html += '<div class="alert alert-info text-left mb-0">Entregue estos datos al estudiante o a su familia:<br>Usuario: <b>' + textoSeguro(datos.usu.toUpperCase())
          + '</b><br>Contraseña temporal: <b>' + textoSeguro(datos.contra) + '</b><br><small>Se le pedirá cambiarla en su primer ingreso.</small></div>';
      }
      $('#modal_registro').data('fm-salir', true).modal('hide');
      Swal.fire({ icon: 'success', title: 'Matrícula registrada', html: html });
      if (window.tbl_matricula) { tbl_matricula.ajax.reload(); }
    };
    var bt = $('#mp_beca_tipo').val();
    if (bt === 'NINGUNA') { return terminar(); }
    // La beca se guarda sobre la matrícula recién creada
    Matricula_Contexto(function () {
      var m = MP.est && MP.est.en_anio;
      if (!m) { return terminar('<p class="text-warning">No se pudo aplicar la beca: asígnela desde la lista con el botón Beca.</p>'); }
      $.ajax({ url: '../controller/matricula/controlador_beca_guardar.php', type: 'POST', dataType: 'json',
               data: { id: m.id, tipo: bt, valor: MP_Num($('#mp_beca_valor').val()).toFixed(2), desde: '', motivo: $('#mp_beca_motivo').val() } })
        .done(function (r) { terminar(r.codigo == 1 ? '<p>Beca aplicada a sus pensiones.</p>' : '<p class="text-warning">La beca no se aplicó: ' + textoSeguro(r.mensaje) + '</p>'); })
        .fail(function () { terminar('<p class="text-warning">La beca no se aplicó: asígnela desde la lista con el botón Beca.</p>'); });
    });
  });
}

// ---------- Eventos ----------
$(document).off('.mp');
$(document).on('click.mp', '#mp_siguiente', function () { if (Matricula_Validar(MP.paso)) { Matricula_Ir(MP.paso + 1); } });
$(document).on('click.mp', '#mp_atras', function () { Matricula_Ir(MP.paso - 1); });
$(document).on('click.mp', '.mp-pasos li.hecho', function () { Matricula_Ir(+$(this).data('paso')); });
$(document).on('change.mp', '#select_estudiante', function () {
  MP.est = null; MP.montosEditados = false;
  $('#txt_usu, #txt_contra, #txt_correo').val('');
  if ($(this).val()) { Matricula_Contexto(); } else { $('#mp_estudiante').empty(); }
});
$(document).on('change.mp', '#select_año', function () { if ($('#modal_registro').hasClass('show')) { MP.aula = null; Matricula_Contexto(null, true); } });
$(document).on('click.mp', '#mp_aulas .mp-aula:not(:disabled)', function () {
  var id = +$(this).data('id');
  MP.aula = MP.ctx.aulas.filter(function (a) { return +a.id === id; })[0];
  $('#select_aula').val(id);
  MP.montosEditados = false;
  Matricula_Pintar_Aulas();
});
$(document).on('input.mp', '#txt_admision, #txt_alum_nuevo, #txt_matricula', function () { MP.montosEditados = true; $('.mp-cobro').toggle(Matricula_Total() > 0); });
$(document).on('change.mp', '#checkboxSuccess1', function () { MP.montosEditados = true; Matricula_Actualizar_Pagos(); });
$(document).on('change.mp', '#mp_beca_tipo', Matricula_Actualizar_Pagos);
$(document).on('click.mp', '#mp_generar', function () {
  var letras = 'abcdefghjkmnpqrstuvwxyz', numeros = '23456789', c = '';
  var al = function (t) { var a = new Uint32Array(1); crypto.getRandomValues(a); return t[a[0] % t.length]; };
  for (var i = 0; i < 6; i++) { c += al(letras); }
  for (var j = 0; j < 3; j++) { c += al(numeros); }
  $('#txt_contra').val(c.charAt(0).toUpperCase() + c.substr(1)).trigger('change');
});
// Enter avanza al paso siguiente (en el último, el Enter global pulsa "Matricular")
$(document).on('keydown.mp', '#modal_registro input', function (ev) {
  if (ev.key !== 'Enter' || MP.paso === 5 || $(this).closest('.select2-container').length) { return; }
  ev.preventDefault();
  ev.stopPropagation();
  $('#mp_siguiente').trigger('click');
});
