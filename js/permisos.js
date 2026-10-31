// Permisos por rol (Fase 13, paso 15): Configuración → Permisos por rol.
var PM = { datos: null };
var PM_URL = '../controller/configuracion/controlador_permisos.php';

function Permisos_Iniciar() {
  $(document).off('.pm');
  $(document).on('change.pm', '#pm_cuerpo input[type=checkbox]', Permisos_Cambiar);
  $(document).on('input.pm', '#pm_buscar', Permisos_Filtrar);
  $(document).on('click.pm', '#pm_restablecer_todo', function () { Permisos_Restablecer(''); });
  $(document).on('click.pm', '.pm-restablecer-rol', function () { Permisos_Restablecer($(this).data('rol')); });
  Permisos_Cargar();
}

function Permisos_Cargar() {
  $.post(PM_URL, { accion: 'listar' }, null, 'json').done(function (d) {
    PM.datos = d;
    Permisos_Pintar();
  });
}

function Permisos_NombreRol(r) {
  return r.charAt(0) + r.slice(1).toLowerCase();
}

function Permisos_Pintar() {
  var d = PM.datos, cab = '<th class="text-left">Pantalla</th>';
  d.roles.forEach(function (r) {
    cab += '<th>' + textoSeguro(Permisos_NombreRol(r)) +
           '<br><button type="button" class="btn btn-link btn-sm p-0 pm-restablecer-rol" data-rol="' + textoSeguro(r) + '" title="Volver este rol a fábrica"><i class="fas fa-undo"></i></button></th>';
  });
  $('#pm_cabecera').html(cab);
  var html = '', seccion = null;
  d.pantallas.forEach(function (p) {
    if (p.seccion !== seccion) {
      seccion = p.seccion;
      html += '<tr class="pm-seccion"><td colspan="' + (d.roles.length + 1) + '">' + textoSeguro(seccion || 'OTRAS') + '</td></tr>';
    }
    html += '<tr data-buscar="' + textoSeguro((p.titulo + ' ' + p.vista).toLowerCase()) + '"><td>' + textoSeguro(p.titulo) + '</td>';
    d.roles.forEach(function (r) {
      var c = p.roles[r];
      if (!c.editable) { html += '<td class="pm-celda text-muted">—</td>'; return; }
      html += '<td class="pm-celda"><span class="' + (c.cambio ? 'pm-cambio ' : '') + 'd-inline-block p-1"><input type="checkbox" data-rol="' + textoSeguro(r) +
              '" data-vista="' + textoSeguro(p.vista) + '"' + (c.actual ? ' checked' : '') + ' aria-label="' + textoSeguro(Permisos_NombreRol(r) + ': ' + p.titulo) + '"></span></td>';
    });
    html += '</tr>';
  });
  $('#pm_cuerpo').html(html);
  Permisos_Filtrar();
}

function Permisos_Filtrar() {
  var t = $.trim($('#pm_buscar').val() || '').toLowerCase();
  $('#pm_cuerpo tr[data-buscar]').each(function () { $(this).toggle(!t || this.getAttribute('data-buscar').indexOf(t) !== -1); });
  $('#pm_cuerpo tr.pm-seccion').toggle(!t);
}

function Permisos_Buscar(vista) {
  return PM.datos.pantallas.filter(function (p) { return p.vista === vista; })[0];
}

function Permisos_Cambiar() {
  var cb = $(this), rol = cb.data('rol'), vista = cb.data('vista'), permitido = cb.is(':checked');
  var p = Permisos_Buscar(vista), c = p.roles[rol];
  var guardar = function () {
    cb.prop('disabled', true);
    $.post(PM_URL, { accion: 'guardar', rol: rol, vista: vista, permitido: permitido ? 1 : 0 }, null, 'json').done(function (r) {
      c.actual = permitido;
      c.cambio = r.cambio;
      cb.parent().toggleClass('pm-cambio', r.cambio);
    }).fail(function () { cb.prop('checked', !permitido); }).always(function () { cb.prop('disabled', false); });
  };
  // Dar una pantalla de la dirección a otro rol abre datos delicados: se pide confirmación
  if (permitido && p.direccion && !c.fabrica) {
    return Swal.fire({ title: '¿Dar esta pantalla a ' + Permisos_NombreRol(rol) + '?', html: '<b>' + textoSeguro(p.titulo) + '</b> es una pantalla de la dirección. Todos los usuarios ' +
                       textoSeguro(Permisos_NombreRol(rol).toLowerCase()) + ' podrán verla y usarla.', icon: 'warning', showCancelButton: true,
                       confirmButtonText: 'Sí, darla', cancelButtonText: 'Cancelar' }).then(function (x) { if (x.isConfirmed) { guardar(); } else { cb.prop('checked', false); } });
  }
  guardar();
}

function Permisos_Restablecer(rol) {
  Swal.fire({ title: rol ? '¿Volver ' + Permisos_NombreRol(rol) + ' a fábrica?' : '¿Volver todos los roles a fábrica?', text: 'Se borran los cambios hechos en esta pantalla.',
              icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, volver', cancelButtonText: 'Cancelar' }).then(function (x) {
    if (!x.isConfirmed) { return; }
    $.post(PM_URL, { accion: 'restablecer', rol: rol }, null, 'json').done(function () {
      Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Permisos de fábrica restablecidos' });
      Permisos_Cargar();
    });
  });
}
