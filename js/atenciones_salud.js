// Enfermería y Psicología: atenciones agrupadas por día, resumen, motivos frecuentes, buscador de estudiantes e historial.
// Guarda con los controladores de siempre (el usuario que atiende sale de la sesión).
// Rutas de guardado: las pasa cada vista (enfermería o psicología), así cada rol solo usa sus controladores
var SA = { tipo: null, guardar: null, datos: null, texto: '', rango: '30' };
var SA_URL = '../controller/salud/controlador_atenciones.php';
var SA_TEXTOS = {
  ENFERMERIA: { motivo: 'Motivo', diagnostico: 'Diagnóstico', observaciones: 'Tratamiento y observaciones', icono: 'fa-briefcase-medical' },
  PSICOLOGIA: { motivo: 'Motivo de consulta', diagnostico: 'Evaluación', observaciones: 'Acuerdos y seguimiento', icono: 'fa-brain' }
};

function SA_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function SA_Aula(e) { return SA_Titulo(e.grado) + (e.seccion && e.seccion !== 'UNICO' ? ' ' + e.seccion : '') + ' · ' + SA_Titulo(e.nivel); }
function SA_Nombre(e) { return SA_Titulo(e.nombres + ' ' + e.apellidos); }
function SA_Iso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }

function Salud_Iniciar(tipo, guardar) {
  SA.tipo = tipo;
  SA.guardar = guardar;
  SA.texto = '';
  SA.rango = '30';
  $(document).off('.sa');
  $(document).on('click.sa', '#sa_nueva', function () { SA_Formulario(null); });
  $(document).on('input.sa', '#sa_buscar', function () { SA.texto = $(this).val(); SA_Lista(); });
  $(document).on('click.sa', '.sa-rango', function () { SA.rango = String($(this).data('rango')); SA_Cargar(); });
  $(document).on('change.sa', '#sa_desde, #sa_hasta', function () { SA.rango = 'otro'; SA_Cargar(); });
  $(document).on('click.sa', '.sa-editar', function () { SA_Formulario(SA.datos.atenciones[+$(this).data('i')]); });
  $(document).on('click.sa', '.sa-historial', function () { SA_Historial(SA.datos.atenciones[+$(this).data('i')]); });
  SA_Cargar();
}

function SA_Cargar() {
  var hoy = new Date(), desde = new Date();
  var params = { accion: 'listar', tipo: SA.tipo };
  if (SA.rango === 'otro') { params.desde = $('#sa_desde').val(); params.hasta = $('#sa_hasta').val(); }
  else {
    if (SA.rango === 'anio') { desde = new Date(hoy.getFullYear(), 0, 1); }
    else if (SA.rango === 'todo') { desde = new Date(2000, 0, 1); }
    else { desde.setDate(hoy.getDate() - (+SA.rango)); }
    params.desde = SA_Iso(desde); params.hasta = SA_Iso(hoy);
  }
  $.post(SA_URL, params, null, 'json').done(function (d) { SA.datos = d; SA_Pintar(); });
}

function SA_Pintar() {
  var d = SA.datos, r = d.resumen, t = SA_TEXTOS[SA.tipo];
  var resumen = '<section class="pe-resumen">' +
    '<div class="pe-dato pe-dato-grande"><span>Atenciones de hoy</span><b>' + (r.hoy || 0) + '</b></div>' +
    '<div class="pe-dato"><span>Esta semana</span><b>' + (r.semana || 0) + '</b></div>' +
    '<div class="pe-dato"><span>Este mes</span><b>' + (r.mes || 0) + '</b></div>' +
    '<div class="pe-dato"><span>Estudiantes atendidos</span><b>' + (r.estudiantes || 0) + '</b><small>en el año</small></div></section>';
  var rangos = [['0', 'Hoy'], ['7', '7 días'], ['30', '30 días'], ['anio', 'Este año'], ['todo', 'Todo']];
  var barra = '<div class="pe-barra"><div class="pe-filtros">' + rangos.map(function (x) {
    return '<button type="button" class="pe-filtro sa-rango' + (SA.rango === x[0] ? ' activo' : '') + '" data-rango="' + x[0] + '">' + x[1] + '</button>';
  }).join('') + '<span class="sa-fechas"><input type="date" class="form-control form-control-sm" id="sa_desde" value="' + d.desde + '" aria-label="Desde"> a ' +
    '<input type="date" class="form-control form-control-sm" id="sa_hasta" value="' + d.hasta + '" aria-label="Hasta"></span></div>' +
    '<input type="search" class="form-control sa-buscar" id="sa_buscar" placeholder="Buscar estudiante, DNI o motivo…" value="' + textoSeguro(SA.texto) + '"></div>';
  var max = d.motivos.length ? +d.motivos[0].veces : 1;
  var motivos = '<aside class="sa-motivos"><h4>Motivos más frecuentes</h4>' + (d.motivos.length ? d.motivos.map(function (m) {
    return '<p><span>' + textoSeguro(SA_Titulo(m.motivo)) + '</span><b>' + m.veces + '</b><i style="width:' + Math.round(+m.veces * 100 / max) + '%"></i></p>';
  }).join('') : '<p class="text-muted">Sin atenciones en estas fechas.</p>') + '</aside>';
  $('#sa_contenido').html(resumen + barra + '<div class="sa-cuerpo"><div id="sa_lista"></div>' + motivos + '</div>');
  SA_Lista();
}

function SA_Lista() {
  var d = SA.datos, t = SA_TEXTOS[SA.tipo], q = $.trim(SA.texto).toLowerCase();
  d.atenciones.forEach(function (a, i) { a._i = i; });
  var lista = d.atenciones.filter(function (a) {
    return !q || (a.nombres + ' ' + a.apellidos + ' ' + a.documento + ' ' + a.motivo + ' ' + (a.diagnostico || '')).toLowerCase().indexOf(q) !== -1;
  });
  if (!lista.length) { $('#sa_lista').html('<div class="av-vacio"><i class="fas ' + t.icono + '"></i><p>' + (q ? 'Nada coincide con la búsqueda.' : 'No hay atenciones en estas fechas.') + '</p></div>'); return; }
  var hoy = SA_Iso(new Date()), ayer = new Date(); ayer.setDate(ayer.getDate() - 1); ayer = SA_Iso(ayer);
  var grupos = {}, orden = [];
  lista.forEach(function (a) { var f = a.fecha.slice(0, 10); if (!grupos[f]) { grupos[f] = []; orden.push(f); } grupos[f].push(a); });
  $('#sa_lista').html(orden.map(function (f) {
    var titulo = f === hoy ? 'Hoy' : f === ayer ? 'Ayer' : new Date(f + 'T00:00:00').toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    return '<h3 class="pe-grupo">' + titulo.charAt(0).toUpperCase() + titulo.slice(1) + ' <span>' + grupos[f].length + '</span></h3><div class="av-flujo">' + grupos[f].map(function (a) {
      var color = Curso_Color(a.nombres + a.apellidos);
      return '<article class="av-tarea sa-atencion"><span class="av-avatar av-avatar-sm" style="background:' + color + '">' + textoSeguro(a.nombres.charAt(0)) + '</span><div class="av-tarea-cuerpo">' +
        '<p class="sa-quien"><b>' + textoSeguro(SA_Nombre(a)) + '</b> <span>· ' + textoSeguro(SA_Aula(a)) + ' · DNI ' + textoSeguro(a.documento) + '</span><time>' + a.fecha.slice(11, 16) + '</time></p>' +
        '<h4>' + textoSeguro(SA_Titulo(a.motivo)) + '</h4>' +
        (a.diagnostico ? '<p class="sa-campo"><small>' + t.diagnostico + '</small>' + textoSeguro(a.diagnostico) + '</p>' : '') +
        (a.observaciones ? '<p class="sa-campo"><small>' + t.observaciones + '</small>' + textoSeguro(a.observaciones) + '</p>' : '') +
        '<div class="av-tarea-acciones"><span class="small text-muted mr-auto">Atendió: ' + textoSeguro(SA_Titulo(a.atendio || '')) + '</span>' +
        '<button type="button" class="btn btn-outline-secondary btn-sm sa-historial" data-i="' + a._i + '"><i class="fas fa-history mr-1"></i>Historial</button>' +
        '<button type="button" class="btn btn-outline-secondary btn-sm sa-editar" data-i="' + a._i + '"><i class="fas fa-edit mr-1"></i>Editar</button></div></div></article>';
    }).join('') + '</div>';
  }).join(''));
}

// Formulario de atención: buscador de estudiante (solo al registrar), motivo con sugerencias, diagnóstico y observaciones
function SA_Formulario(a) {
  var t = SA_TEXTOS[SA.tipo], elegido = a ? { matricula: a.matricula, nombres: a.nombres, apellidos: a.apellidos, grado: a.grado, seccion: a.seccion, nivel: a.nivel, documento: a.documento } : null;
  var sugerencias = '<datalist id="sa_f_sugerencias">' + SA.datos.motivos.map(function (m) { return '<option value="' + textoSeguro(m.motivo) + '">'; }).join('') + '</datalist>';
  var html = '<div class="text-left">' +
    (a ? '' : '<div class="form-group"><label for="sa_f_buscar">Estudiante <b class="text-danger">(*)</b></label><input type="search" class="form-control" id="sa_f_buscar" placeholder="Escriba nombre, apellido o DNI" autocomplete="off"><div id="sa_f_resultados" class="sa-resultados"></div></div>') +
    '<div id="sa_f_elegido"></div>' +
    '<div class="form-group"><label for="sa_f_motivo">' + t.motivo + ' <b class="text-danger">(*)</b></label><input class="form-control" id="sa_f_motivo" maxlength="255" list="sa_f_sugerencias" value="' + textoSeguro(a ? a.motivo : '') + '">' + sugerencias + '</div>' +
    '<div class="form-group"><label for="sa_f_diag">' + t.diagnostico + '</label><textarea class="form-control" id="sa_f_diag" rows="2" maxlength="255">' + textoSeguro(a ? a.diagnostico || '' : '') + '</textarea></div>' +
    '<div class="form-group mb-0"><label for="sa_f_obs">' + t.observaciones + '</label><textarea class="form-control" id="sa_f_obs" rows="2" maxlength="255">' + textoSeguro(a ? a.observaciones || '' : '') + '</textarea></div></div>';
  var pintarElegido = function () {
    $('#sa_f_elegido').html(elegido ? '<div class="sa-elegido"><span class="av-avatar av-avatar-sm" style="background:' + Curso_Color(elegido.nombres + elegido.apellidos) + '">' + textoSeguro(elegido.nombres.charAt(0)) + '</span>' +
      '<div><b>' + textoSeguro(SA_Nombre(elegido)) + '</b><small>' + textoSeguro(SA_Aula(elegido)) + ' · DNI ' + textoSeguro(elegido.documento) + '</small></div>' +
      (a ? '' : '<button type="button" class="btn btn-link btn-sm ml-auto" id="sa_f_cambiar">Cambiar</button>') + '</div>' : '');
    $('#sa_f_buscar').closest('.form-group').toggle(!elegido);
  };
  var temporizador = null, encontrados = [];
  Swal.fire({
    title: a ? 'Editar atención' : 'Nueva atención', html: html, width: 620, showCancelButton: true, focusConfirm: false,
    confirmButtonText: '<i class="fas fa-save mr-1"></i>Guardar', cancelButtonText: 'Cancelar',
    didOpen: function () {
      pintarElegido();
      $('#sa_f_buscar').on('input', function () {
        var q = $.trim($(this).val());
        clearTimeout(temporizador);
        if (q.length < 2) { $('#sa_f_resultados').empty(); return; }
        temporizador = setTimeout(function () {
          $.post(SA_URL, { accion: 'buscar', q: q }, null, 'json').done(function (r) {
            encontrados = r.estudiantes;
            $('#sa_f_resultados').html(encontrados.length ? encontrados.map(function (e, i) {
              return '<button type="button" class="sa-resultado" data-i="' + i + '"><b>' + textoSeguro(SA_Nombre(e)) + '</b><small>' + textoSeguro(SA_Aula(e)) + ' · DNI ' + textoSeguro(e.documento) + '</small></button>';
            }).join('') : '<p class="text-muted small mb-0">No se encontró ningún estudiante matriculado este año.</p>');
          });
        }, 250);
      }).focus();
      $(Swal.getHtmlContainer()).on('click', '.sa-resultado', function () { elegido = encontrados[+$(this).data('i')]; pintarElegido(); $('#sa_f_motivo').focus(); });
      $(Swal.getHtmlContainer()).on('click', '#sa_f_cambiar', function () { elegido = null; pintarElegido(); $('#sa_f_buscar').val('').focus(); $('#sa_f_resultados').empty(); });
    },
    preConfirm: function () {
      if (!elegido) { Swal.showValidationMessage('Elija al estudiante'); return false; }
      var motivo = $.trim($('#sa_f_motivo').val());
      if (!motivo) { Swal.showValidationMessage('Escriba el ' + t.motivo.toLowerCase()); return false; }
      return { estu: elegido.matricula, motivo: motivo, diagno: $.trim($('#sa_f_diag').val()), observa: $.trim($('#sa_f_obs').val()), idusu: 0 };
    }
  }).then(function (r) {
    if (!r.isConfirmed) { return; }
    var datos = r.value;
    if (a) { datos.id = a.id; }
    $.post(SA.guardar[a ? 'editar' : 'nuevo'], datos).done(function (resp) {
      if (String(resp).trim() === '1') {
        Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: a ? 'Atención actualizada' : 'Atención registrada' });
        SA_Cargar();
      } else {
        Swal.fire('No se pudo guardar', 'Revise los datos e intente de nuevo.', 'warning');
      }
    });
  });
}

function SA_Historial(a) {
  var t = SA_TEXTOS[SA.tipo];
  $.post(SA_URL, { accion: 'historial', tipo: SA.tipo, matricula: a.matricula }, null, 'json').done(function (r) {
    Swal.fire({
      title: SA_Nombre(a), width: 640, confirmButtonText: 'Cerrar',
      html: '<p class="text-muted">' + textoSeguro(SA_Aula(a)) + ' · ' + r.atenciones.length + (r.atenciones.length === 1 ? ' atención' : ' atenciones') + '</p><div class="sa-historial-lista">' +
        r.atenciones.map(function (x) {
          return '<div><time>' + new Date(x.fecha.replace(' ', 'T')).toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' }) + ', ' + x.fecha.slice(11, 16) + '</time>' +
            '<b>' + textoSeguro(SA_Titulo(x.motivo)) + '</b>' + (x.diagnostico ? '<p><small>' + t.diagnostico + ':</small> ' + textoSeguro(x.diagnostico) + '</p>' : '') +
            (x.observaciones ? '<p><small>' + t.observaciones + ':</small> ' + textoSeguro(x.observaciones) + '</p>' : '') + '</div>';
        }).join('') + '</div>'
    });
  });
}
