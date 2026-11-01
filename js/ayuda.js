// Ayuda contextual (Fase 13, paso 16): botón "?" junto al título de las pantallas complejas.
// Muestra la sección de docs/guia_rapida.md que explica esa pantalla.
var AYUDA_SECCIONES = {
  'alumnos/view_alumnos.php': ['Importar estudiantes desde Excel', 'Estudiantes y docentes: activar o desactivar', 'Ficha del estudiante'],
  'matricula/view_matricula.php': ['Matricular a un estudiante'],
  'boletas/view_boletas.php': ['Libretas de notas'],
  'asistencia/view_registro_asistencia.php': ['Tomar asistencia: Registro de asistencia'],
  'tareas/view_registro_tareas.php': ['Revisar tareas: Registro de tareas'],
  'tareas/view_tareas_estudiante.php': ['Envío de tareas: cuándo se puede reenviar'],
  'reportes/view_reporte_estudiantes.php': ['Reporte de estudiantes a medida', 'Filtros y columnas en todas las listas'],
  'comunicado/view_comunicado.php': ['Comunicados: quién los leyó'],
  'avisos/view_avisos.php': ['Avisos a las familias'],
  'aula_virtual/view_aula_virtual.php': ['Aula virtual'],
  'horario/view_calendario.php': ['Calendario de horarios'],
  'configuracion/view_cambio_anio.php': ['Cambio de año escolar (solo administrador)'],
  'configuracion/view_permisos.php': ['Permisos por rol (solo administrador)'],
  'configuracion/view_parametros.php': ['Parámetros del sistema (solo administrador)'],
  'configuracion/view_respaldos.php': ['Respaldos (solo administrador)']
};

// Lo llama cargar_contenido después de cargar cada pantalla
function Ayuda_Colocar(contenedor, vista) {
  var secciones = AYUDA_SECCIONES[vista];
  var titulo = $(contenedor).find('.pagina-titulo').first();
  if (!secciones || !titulo.length || titulo.find('.ayuda-boton').length) { return; }
  $('<button type="button" class="btn btn-link ayuda-boton" title="Ayuda de esta pantalla" aria-label="Ayuda de esta pantalla"><i class="fas fa-question-circle"></i></button>')
    .on('click', function () { Ayuda_Abrir(secciones, titulo.clone().children().remove().end().text()); })
    .appendTo(titulo);
}

function Ayuda_Abrir(secciones, titulo) {
  $.post('../controller/ayuda/controlador_ayuda.php', { secciones: secciones.join('|') }, null, 'json').done(function (r) {
    Swal.fire({ title: 'Ayuda: ' + $.trim(titulo), html: '<div class="ayuda-texto">' + r.html + '</div>', width: 720, confirmButtonText: 'Entendido' });
  });
}
