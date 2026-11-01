// Material de las tareas (docente y administrador): documentos, videos subidos y enlaces de video.
// Lo usan view_tareas.php (dirección) y docente/view_tareas_docente.php. Los límites vienen del servidor en window.LIMITES_TAREA.
window.MaterialTarea = (function () {
  var DOCUMENTOS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
  var VIDEOS = ['mp4', 'webm', 'm4v', 'mov'];

  function limites() {
    return window.LIMITES_TAREA || { documento_mb: 20, video_mb: 100, enlaces_max: 10 };
  }
  function tamano(bytes) {
    return bytes < 1048576 ? Math.max(1, Math.round(bytes / 1024)) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
  }
  function extension(nombre) {
    return String(nombre).split('.').pop().toLowerCase();
  }

  // Revisa todos los archivos elegidos; si alguno no sirve limpia el campo y avisa
  function validar(input) {
    var l = limites();
    for (var i = 0; i < input.files.length; i++) {
      var f = input.files[i];
      var ext = extension(f.name);
      var video = VIDEOS.indexOf(ext) !== -1;
      var problema = '';
      if (!video && DOCUMENTOS.indexOf(ext) === -1) {
        problema = 'Tipo de archivo no permitido: ' + textoSeguro(f.name) + '<br><small>Documentos: ' + DOCUMENTOS.join(', ') + '. Videos: ' + VIDEOS.join(', ') + '.</small>';
      } else if (f.size > (video ? l.video_mb : l.documento_mb) * 1048576) {
        problema = 'El archivo ' + textoSeguro(f.name) + ' pesa ' + (f.size / 1048576).toFixed(1) + ' MB y el máximo para '
          + (video ? 'videos' : 'documentos') + ' es ' + (video ? l.video_mb : l.documento_mb) + ' MB.'
          + (video ? '<br><small>Para videos largos suba el video a YouTube (como "No listado") o a Google Drive y pegue el enlace.</small>' : '');
      }
      if (problema) {
        input.value = '';
        $(input).trigger('change');
        Swal.fire('Archivo no permitido', problema, 'warning');
        return false;
      }
    }
    return true;
  }

  // Lista los archivos elegidos (con icono de video) en el <ul> indicado
  function listar(input, lista) {
    lista.innerHTML = '';
    for (var i = 0; i < input.files.length; i++) {
      var f = input.files[i];
      var li = document.createElement('li');
      li.innerHTML = '<i class="fas ' + (VIDEOS.indexOf(extension(f.name)) !== -1 ? 'fa-film text-danger' : 'fa-file') + '"></i> '
        + textoSeguro(f.name) + ' <small class="text-muted">(' + tamano(f.size) + ')</small>';
      lista.appendChild(li);
    }
  }

  // Conecta un campo de archivos con su lista
  function conectar(inputId, listaId) {
    var input = document.getElementById(inputId);
    if (!input) { return; }
    $(input).off('change.material').on('change.material', function () {
      if (input.files.length && !validar(input)) { return; }
      listar(input, document.getElementById(listaId));
    });
  }

  // Enlaces escritos (uno por línea); avisa si hay más de los permitidos o alguno no parece un enlace
  function enlaces(textareaId) {
    var lineas = String(document.getElementById(textareaId).value || '').split(/\r?\n/)
      .map(function (s) { return s.trim(); }).filter(function (s) { return s !== ''; });
    if (lineas.length > limites().enlaces_max) {
      Swal.fire('Demasiados enlaces', 'Puede agregar hasta ' + limites().enlaces_max + ' enlaces de video por tarea', 'warning');
      return null;
    }
    for (var i = 0; i < lineas.length; i++) {
      if (/\s/.test(lineas[i]) || lineas[i].indexOf('.') === -1) {
        Swal.fire('Enlace no válido', 'Escriba un enlace por línea, por ejemplo https://youtu.be/xxxx<br><b>' + textoSeguro(lineas[i]) + '</b>', 'warning');
        return null;
      }
    }
    return lineas.join('\n');
  }

  // Edición: muestra los archivos actuales (con opción de quitarlos) y llena los enlaces guardados
  function cargarActual(carpeta, contenedorId, textareaId) {
    var cont = document.getElementById(contenedorId);
    cont.innerHTML = '<small class="text-muted">Cargando material actual...</small>';
    document.getElementById(textareaId).value = '';
    if (!carpeta) { cont.innerHTML = ''; return; }
    $.getJSON('../controller/tareas/controlador_archivos_tarea.php', { carpeta: carpeta, formato: 'json' }).done(function (r) {
      document.getElementById(textareaId).value = (r.enlaces || []).join('\n');
      if (!r.archivos || !r.archivos.length) {
        cont.innerHTML = '<small class="text-muted">La tarea no tiene archivos subidos.</small>';
        return;
      }
      var html = '<label class="mb-1">Archivos actuales <small class="text-muted">(marque los que quiere quitar)</small>:</label><ul class="list-unstyled mb-2">';
      r.archivos.forEach(function (f) {
        html += '<li><label class="font-weight-normal mb-0"><input type="checkbox" class="quitar-archivo mr-1" value="' + textoSeguro(f.nombre) + '"> '
          + '<i class="fas ' + (f.video ? 'fa-film text-danger' : 'fa-file') + '"></i> ' + textoSeguro(f.nombre)
          + ' <small class="text-muted">(' + tamano(f.tamano) + ')</small> <span class="badge badge-danger d-none">se quitará</span></label></li>';
      });
      cont.innerHTML = html + '</ul>';
      $(cont).find('.quitar-archivo').on('change', function () {
        $(this).siblings('.badge').toggleClass('d-none', !this.checked);
      });
    }).fail(function () {
      cont.innerHTML = '<small class="text-danger">No se pudo cargar el material actual.</small>';
    });
  }

  // Nombres de los archivos actuales marcados para quitar (JSON)
  function quitar(contenedorId) {
    return JSON.stringify($('#' + contenedorId + ' .quitar-archivo:checked').map(function () { return this.value; }).get());
  }

  // Muestra el avance de la subida (útil con videos) y se usa como opción "xhr" de $.ajax
  function xhrConProgreso() {
    var xhr = new window.XMLHttpRequest();
    xhr.upload.addEventListener('progress', function (e) {
      if (!e.lengthComputable || e.total < 2 * 1048576) { return; }
      var p = Math.round(e.loaded * 100 / e.total);
      if (!Swal.isVisible() || !document.getElementById('progreso_material')) {
        Swal.fire({
          title: 'Subiendo material...',
          html: '<div class="progress"><div id="progreso_material" class="progress-bar bg-success" style="width:0%">0%</div></div>'
            + '<small class="text-muted">No cierre esta ventana hasta que termine.</small>',
          allowOutsideClick: false, showConfirmButton: false
        });
      }
      var barra = document.getElementById('progreso_material');
      barra.style.width = p + '%';
      barra.textContent = p < 100 ? p + '%' : 'Guardando...';
    });
    return xhr;
  }

  return { validar: validar, conectar: conectar, enlaces: enlaces, cargarActual: cargarActual, quitar: quitar, xhrConProgreso: xhrConProgreso };
})();
