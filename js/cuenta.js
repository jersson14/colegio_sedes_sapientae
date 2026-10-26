// Cuenta del usuario (Fase 13): cambiar su propia contraseña. La usan la pantalla obligatoria del primer ingreso
// (view/inicio/cambiar_clave.php) y la ventana "Cambiar mi contraseña" del menú del usuario.
window.Cuenta = (function ($) {
  function formulario(actual, nueva, confirmar, boton, alTerminar) {
    var enviar = function () {
      var a = $(actual).val(), n = $(nueva).val(), c = $(confirmar).val();
      if (!a || !n || !c) { return Swal.fire('Faltan datos', 'Complete los tres campos.', 'warning'); }
      if (n.length < 8) { return Swal.fire('Contraseña muy corta', 'La nueva contraseña debe tener al menos 8 caracteres.', 'warning'); }
      if (n !== c) { return Swal.fire('No coinciden', 'La nueva contraseña y su confirmación deben ser iguales.', 'warning'); }
      var b = $(boton).prop('disabled', true);
      $.post('../controller/usuario/controlador_cambiar_mi_clave.php', { actual: a, nueva: n, confirmar: c }, null, 'json').done(function () {
        Swal.fire({ icon: 'success', title: 'Contraseña actualizada', timer: 1800, showConfirmButton: false }).then(alTerminar);
      }).fail(function (x) {
        var r = x.responseJSON || {};
        // dentro del sistema el aviso general ya muestra los 400/403; en la pantalla del primer ingreso, este
        if (window.__avisoGlobal || (x.status !== 400 && x.status !== 403)) { return; }
        Swal.fire('No se pudo cambiar', r.mensaje || 'Revise los datos.', 'warning');
      }).always(function () { b.prop('disabled', false); });
    };
    $(boton).off('click.cuenta').on('click.cuenta', enviar);
    $(actual + ', ' + nueva + ', ' + confirmar).off('keydown.cuenta').on('keydown.cuenta', function (e) { if (e.key === 'Enter') { enviar(); } });
  }

  // Ventana "Cambiar mi contraseña" del menú del usuario
  function abrir() {
    $('#modal_mi_clave input').val('');
    $('#modal_mi_clave').modal('show');
    formulario('#mc_actual', '#mc_nueva', '#mc_confirmar', '#mc_guardar', function () { $('#modal_mi_clave').modal('hide'); });
  }

  return { formulario: formulario, abrir: abrir };
})(window.jQuery);
