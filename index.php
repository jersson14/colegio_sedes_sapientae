<?php
  session_start();
  if(isset($_SESSION['S_ID'])){
    header('Location: view/index.php');
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar Sesión</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plantilla/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="plantilla/dist/css/adminlte.min.css">
  <link rel="icon" href="img/icono.jpeg" type="image/jpeg">

</head>
<body class="hold-transition login-page" style="background-image: url('img/fondo.jpeg'); background-size: 25px 50px; background-size: 100% 100%;"  >
<div class="login-box">
  <div class="login-logo">
    <a href="index.php"><b style="font-family:'arial black'; font-size:28px; color:black"></b></a>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
    <img src="img/logo1.png" alt="" width="100%" height="100%">		      	
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
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const rmcheck       = document.getElementById('remember'),
        usuarioInput  = document.getElementById('txt_usuario'),
        passInput     = document.getElementById('txt_contra');
      if(localStorage.checkbox && localStorage.checkbox !=""){
        rmcheck.setAttribute("checked","checked");
        usuarioInput.value = localStorage.usuario;
        passInput.value    = localStorage.pass;
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
