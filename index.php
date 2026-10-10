<?php
  require 'core/sesion.php';
  require 'core/marca.php'; // Fase 4.7: nombre y logo de la institución del host
  if(sesion_activa()){
    header('Location: view/index.php');
    exit;
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión · <?= marca_html(marca()->nombre) ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plantilla/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="plantilla/dist/css/adminlte.min.css">
  <link rel="icon" href="<?= marca_html(marca()->icono) ?>">

  <style>
    .input-group-text {
      cursor: pointer;
    }
  </style>

  <?= marca_estilo() /* Fase 4.7: color de la institución */ ?>
</head>
<?php $fondo = marca()->fondoAcceso; ?><body class="hold-transition login-page" style="<?= $fondo !== null ? "background-image: url('" . marca_html($fondo) . "'); background-size: 100% 100%;" : 'background: linear-gradient(135deg, var(--color-institucion, #1f4e79) 0%, #6c9bc8 100%);' ?>">
<div class="login-box">
  <div class="login-logo">
    <a href="index.php"><b style="font-family:'arial black'; font-size:28px; color:black"></b></a>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
    <img src="<?= marca_html(marca()->logoAcceso) ?>" alt="<?= marca_html(marca()->nombre) ?>" style="width: 100%; height: auto;">		      	
      <p class="login-box-msg" style="font-family:Arial black; font-size:15px; color:black"><b>DATOS DEL USUARIO</b></p>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Ingrese su usuario" id="txt_usuario">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Ingrese su contraseña" id="txt_contra">
          <div class="input-group-append">
            <div class="input-group-text" onclick="togglePassword()" title="Mostrar/Ocultar contraseña">
              <span class="fas fa-eye" id="toggleIcon"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-6">
            <div class="icheck-primary" style="color:black">
              <input type="checkbox" id="remember">
              <label for="remember">
                Recuerdame
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-6">
            <button class="btn btn-primary btn-block" id="entrar" onclick="Iniciar_Sesion()"><i class='fas fa-share-square ml-1 mr-1'></i>&nbsp;<b>Iniciar Sesión</b></button>
          </div>
          <!-- /.col -->
        </div>
        <br>
      

    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plantilla/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plantilla/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="plantilla/dist/js/adminlte.min.js"></script>
<script src="js/console_usuario.js?rev=<?php echo time();?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js" integrity="sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2" crossorigin="anonymous"></script>

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
      // Borra contraseñas guardadas por versiones anteriores del "recuérdame".
      localStorage.removeItem('pass');
      if(localStorage.checkbox && localStorage.checkbox !=""){
        rmcheck.setAttribute("checked","checked");
        usuarioInput.value = localStorage.usuario;
      }else{
        rmcheck.removeAttribute("checked");
        usuarioInput.value = "";
        passInput.value    = "";
      }
      
</script>
<script>
  txt_usuario.focus();
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