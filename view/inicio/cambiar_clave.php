<?php
// Pantalla obligatoria de cambio de contraseña (contraseña temporal puesta por el administrador).
// La incluye view/index.php en lugar del sistema mientras $_SESSION['S_DEBE_CAMBIAR'] esté activo.
if (!function_exists('tema_actual')) { http_response_code(404); exit; }
$TEMA = tema_actual();
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
  <title>Cambie su contraseña · <?php echo $e($TEMA['nombre']); ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700&display=fallback">
  <link rel="stylesheet" href="../plantilla/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plantilla/dist/css/adminlte.min.css">
  <link rel="icon" href="../<?php echo $e($TEMA['logo'] ?: 'img/icono.jpeg'); ?>">
  <?php echo tema_css_variables($TEMA); ?>
  <style>
    body { min-height: 100vh; display: grid; place-items: center; background: var(--tema-fondo); font-family: 'Source Sans Pro', sans-serif; padding: 24px 16px; }
    .cc-caja { width: 100%; max-width: 440px; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px -12px rgba(20,20,30,.25); overflow: hidden; }
    .cc-cab { background: var(--tema-primario); color: var(--tema-primario-texto); padding: 22px 26px; display: flex; gap: 14px; align-items: center; }
    .cc-cab img { width: 54px; height: 54px; object-fit: contain; background: #fff; border-radius: 10px; padding: 4px; }
    .cc-cab b { display: block; font-size: 1.05rem; }
    .cc-cuerpo { padding: 24px 26px 26px; }
    .cc-cuerpo h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 6px; }
    .cc-cuerpo p { color: #5f636a; }
    .cc-cuerpo label { font-weight: 600; font-size: .92rem; }
    .cc-cuerpo .form-control { height: 46px; border-radius: 10px; }
    .cc-btn { width: 100%; height: 48px; border: 0; border-radius: 10px; font-weight: 700; background: var(--tema-primario); color: var(--tema-primario-texto); }
    .cc-btn:hover { background: var(--tema-primario-oscuro); }
    .cc-reglas { font-size: .85rem; color: #5f636a; margin: -4px 0 16px; padding-left: 18px; }
    .cc-salir { display: block; text-align: center; margin-top: 14px; font-size: .9rem; color: #5f636a; }
  </style>
</head>
<body>
  <main class="cc-caja">
    <div class="cc-cab">
      <img src="../<?php echo $e($TEMA['logo'] ?: 'img/logo.jpeg'); ?>" alt="Escudo">
      <div><b><?php echo $e($TEMA['nombre']); ?></b><span>Hola, <?php echo $e($_SESSION['S_NOMBRE'] ?? ''); ?></span></div>
    </div>
    <div class="cc-cuerpo">
      <h1>Cree su contraseña</h1>
      <p>Ingresó con una contraseña temporal. Para proteger su cuenta, elija una contraseña propia antes de continuar.</p>
      <div class="form-group">
        <label for="cc_actual">Contraseña temporal</label>
        <input type="password" id="cc_actual" class="form-control" autocomplete="current-password">
      </div>
      <div class="form-group">
        <label for="cc_nueva">Nueva contraseña</label>
        <input type="password" id="cc_nueva" class="form-control" autocomplete="new-password">
      </div>
      <ul class="cc-reglas"><li>Al menos 8 caracteres.</li><li>Distinta de su usuario y de la temporal.</li></ul>
      <div class="form-group">
        <label for="cc_confirmar">Repita la nueva contraseña</label>
        <input type="password" id="cc_confirmar" class="form-control" autocomplete="new-password">
      </div>
      <button type="button" class="cc-btn" id="cc_guardar"><i class="fas fa-key mr-2"></i>Guardar y entrar</button>
      <a class="cc-salir" href="../controller/usuario/controlador_cerrar_sesion.php">Salir</a>
    </div>
  </main>
  <script src="../plantilla/plugins/jquery/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/cuenta.js?rev=<?php echo time(); ?>"></script>
  <script>
    $.ajaxSetup({ headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content } });
    Cuenta.formulario('#cc_actual', '#cc_nueva', '#cc_confirmar', '#cc_guardar', function () { location.href = 'index.php'; });
    $('#cc_actual').trigger('focus');
  </script>
</body>
</html>
