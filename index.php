<?php
  require_once __DIR__ . '/utilitario/sesion.php';
  iniciar_sesion_segura();
  if(isset($_SESSION['S_ID'])){
    header('Location: view/index.php');
    exit;
  }
?>
<!DOCTYPE html>
<html lang="es">
<?php
  // Identidad del colegio (Configuración > Apariencia): nombre, lema, escudo y colores
  require_once __DIR__ . '/utilitario/tema.php';
  $TEMA = tema_actual();
  $e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
  $escudo = $TEMA['logo'] ?: 'img/logo1.png';
?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar · <?php echo $e($TEMA['nombre'] ?: 'Sistema escolar'); ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plantilla/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="plantilla/dist/css/adminlte.min.css">
  <link rel="icon" href="<?php echo $e($escudo); ?>">
  <?php echo tema_css_variables($TEMA); ?>
  <style>
    body.ingreso { min-height: 100vh; margin: 0; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); background: var(--tema-fondo); font-family: 'Source Sans Pro', sans-serif; }
    .ing-identidad {
      background: var(--tema-primario); color: var(--tema-primario-texto);
      display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
      padding: 56px 8vw; border-right: 6px solid var(--tema-acento); position: relative; overflow: hidden;
    }
    .ing-identidad::after {
      content: ""; position: absolute; right: -120px; bottom: -120px; width: 380px; height: 380px; border-radius: 50%;
      border: 46px solid color-mix(in srgb, var(--tema-acento) 22%, transparent);
    }
    .ing-escudo { width: 120px; height: 120px; object-fit: contain; background: #fff; border-radius: 18px; padding: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .ing-identidad h1 { font-size: clamp(1.9rem, 3.4vw, 2.9rem); font-weight: 700; margin: 26px 0 8px; line-height: 1.05; letter-spacing: .01em; text-wrap: balance; }
    .ing-identidad p { font-size: 1.15rem; opacity: .88; margin: 0; max-width: 32ch; }
    .ing-identidad .ing-sistema { margin-top: 28px; font-size: .95rem; display: flex; align-items: center; gap: 8px; opacity: .9; }
    .ing-identidad .ing-sistema i { color: var(--tema-acento); }
    .ing-formulario { display: flex; align-items: center; justify-content: center; padding: 40px 24px; }
    .ing-caja { width: 100%; max-width: 380px; }
    .ing-caja h2 { font-size: 1.6rem; font-weight: 700; margin: 0 0 4px; color: #1C1E22; }
    .ing-caja .ing-sub { color: #6B6F76; margin-bottom: 26px; }
    .ing-caja label { font-weight: 600; font-size: .92rem; margin-bottom: 6px; }
    .ing-caja .form-control { height: 46px; border-radius: 10px 0 0 10px; font-size: 1rem; }
    .ing-caja .input-group-text { border-radius: 0 10px 10px 0; background: #fff; cursor: pointer; min-width: 46px; justify-content: center; }
    .ing-caja .form-control:focus { border-color: var(--tema-primario-borde); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--tema-primario) 22%, transparent); }
    .ing-caja .btn-entrar {
      width: 100%; height: 48px; border: 0; border-radius: 10px; font-weight: 700; font-size: 1.05rem;
      background: var(--tema-primario); color: var(--tema-primario-texto); transition: background-color .18s ease;
    }
    .ing-caja .btn-entrar:hover, .ing-caja .btn-entrar:focus { background: var(--tema-primario-oscuro); color: var(--tema-primario-texto); }
    .ing-caja .btn-entrar:focus-visible { outline: 3px solid var(--tema-acento); outline-offset: 2px; }
    .icheck-primary > input:first-child:checked + label::before { background-color: var(--tema-primario); border-color: var(--tema-primario); }
    .ing-ayuda { margin-top: 22px; font-size: .88rem; color: #6B6F76; }
    @media (max-width: 860px) {
      body.ingreso { grid-template-columns: 1fr; }
      .ing-identidad { padding: 34px 24px 30px; border-right: 0; border-bottom: 5px solid var(--tema-acento); align-items: center; text-align: center; }
      .ing-identidad::after { display: none; }
      .ing-escudo { width: 86px; height: 86px; }
      .ing-identidad h1 { margin-top: 16px; }
      .ing-identidad p { margin: 0 auto; }
      .ing-identidad .ing-sistema { display: none; }
      .ing-formulario { padding-top: 28px; align-items: flex-start; }
    }
  </style>
</head>
<body class="ingreso">
  <section class="ing-identidad">
    <img src="<?php echo $e($escudo); ?>" alt="Escudo de <?php echo $e($TEMA['nombre']); ?>" class="ing-escudo">
    <h1><?php echo $e($TEMA['nombre'] ?: 'Sistema escolar'); ?></h1>
    <?php if ($TEMA['lema']) { ?><p><?php echo $e($TEMA['lema']); ?></p><?php } ?>
    <div class="ing-sistema"><i class="fas fa-graduation-cap"></i> Sistema de gestión escolar</div>
  </section>

  <main class="ing-formulario">
    <div class="ing-caja">
      <h2>Ingresar</h2>
      <p class="ing-sub">Use el usuario y la contraseña que le entregó el colegio.</p>
      <div class="form-group">
        <label for="txt_usuario">Usuario</label>
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Su usuario" id="txt_usuario" autocomplete="username">
          <div class="input-group-append"><span class="input-group-text"><span class="fas fa-user"></span></span></div>
        </div>
      </div>
      <div class="form-group">
        <label for="txt_contra">Contraseña</label>
        <div class="input-group">
          <input type="password" class="form-control" placeholder="Su contraseña" id="txt_contra" autocomplete="current-password">
          <div class="input-group-append">
            <button type="button" class="input-group-text" onclick="togglePassword()" title="Mostrar u ocultar la contraseña" aria-label="Mostrar u ocultar la contraseña">
              <span class="fas fa-eye" id="toggleIcon"></span>
            </button>
          </div>
        </div>
      </div>
      <div class="icheck-primary mb-3">
        <input type="checkbox" id="remember">
        <label for="remember">Recordar mi usuario</label>
      </div>
      <button class="btn btn-entrar" id="entrar" onclick="Iniciar_Sesion()"><i class="fas fa-sign-in-alt mr-2"></i>Ingresar</button>
      <p class="ing-ayuda"><i class="fas fa-question-circle"></i> ¿Olvidó su contraseña? Pídala a la secretaría o a la dirección del colegio.</p>
    </div>
  </main>
<!-- jQuery -->
<script src="plantilla/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plantilla/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="plantilla/dist/js/adminlte.min.js"></script>
<script src="js/console_usuario.js?rev=<?php echo time();?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // Función para mostrar/ocultar contraseña
  function togglePassword() {
    const passInput = document.getElementById('txt_contra');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passInput.type === 'password') {
      passInput.type = 'text';
      toggleIcon.classList.remove('fa-eye');
      toggleIcon.classList.add('fa-eye-slash');
    } else {
      passInput.type = 'password';
      toggleIcon.classList.remove('fa-eye-slash');
      toggleIcon.classList.add('fa-eye');
    }
  }
</script>

<script>
  const rmcheck       = document.getElementById('remember'),
        usuarioInput  = document.getElementById('txt_usuario'),
        passInput     = document.getElementById('txt_contra');
      // Solo se recuerda el usuario; se borra la contraseña que versiones anteriores guardaban
      try { localStorage.removeItem('pass'); } catch (e) {}
      if(localStorage.checkbox && localStorage.checkbox !=""){
        rmcheck.setAttribute("checked","checked");
        usuarioInput.value = localStorage.usuario;
      }else{
        rmcheck.removeAttribute("checked");
        usuarioInput.value = "";
      }
      passInput.value = "";
      
</script>
<script>
  (usuarioInput.value ? passInput : usuarioInput).focus();
  var input = document.getElementById("txt_contra");
  input.addEventListener("keyup", function(event) {
  if (event.keyCode === 13) {
   event.preventDefault();
   document.getElementById("entrar").click();
  }
});
</script>
</body>
</html>