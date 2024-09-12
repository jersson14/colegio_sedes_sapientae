
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>STDW / DIRESA</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plantilla/plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="icon" href="img/dire.jpg" type="image/jpg">

  <link rel="stylesheet" href="plantilla/dist/css/adminlte.min.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand-md navbar-light navbar-white"style="background-color:#3E445E">
    <div class="container" >
      
      <img src="view/diresa2.png" alt="" width="20%">
      <div align="right">
        <img src="view/gore.png" alt="" width="30%">
      </div>
      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <!-- SEARCH FORM -->
        </div>

      </div>

      <!-- Right navbar links -->

    </div>
  </nav>
  <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
      
    <a href="index.php" class="navbar-brand">
      <i class="fa fa-search" style="color:green"></i>
         
        <span class="brand-text font-weight-light"><b>TRÁMITE VIRTUAL</b></span>
      </a>

      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a href="index.php" class="nav-link"><i class="fa fa-user"></i><b> Login</b></a>
          </li>
          <li class="nav-item">
            <a href="registrar.php" class="nav-link"><i class="fa fa-plus"></i><b> Nuevo Trámite</b></a>
          </li>
        </ul>

        <!-- SEARCH FORM -->
        </div>

      </div>

      <!-- Right navbar links -->

    </div>
  </nav>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <div class="col-3">
    <select type="text" class="form-control" id="txt_id"  hidden>
    </select>
  <input type="text" class="form-control" id="txt_fecha_inicio" hidden>
  <input type="text" class="form-control" id="txt_fecha_fin" hidden>

  </div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container">
        <div class="row mb-12">
          <div class="col-sm-12" style="text-align:center;color:#3E445E">
            <h4 class="m-0"><b>DIRECCIÓN REGIONAL DE SALUD APURÍMAC</b></h4>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container">
        <div class="row">
          
          <!-- /.col-md-6 -->
          <div class="col-lg-12">
            <div class="card">
            <div class="card-header">
              <hr>
              <p style="font-size:13.5px; text-align:justify"><b>La Dirección Regional de Salud Apurímac pone a su disposición el SEGUIMIENTO DE DOCUMENTOS, para realizar la busqueda y rastreo de los diferentes documentos que se registraron por la misma plataforma. El horario de atención sera de Lunes a Viernes en el horario de 8:00am a 5:00pm.</b></p>
              <hr>
              <div class="card-header bg-primary">
                <h5 class="card-title m-0"><b>Buscador de Trámite</b></h5>
              </div>
              <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <label for="">Número de Documento(*):</label>
                        <input type="text" class="form-control" id="txt_numero">
                    </div>
                    <div class="col-6">
                        <label for="">Número de DNI(*):</label>
                        <input type="text" class="form-control" id="txt_dni" onkeypress="return soloNumeros(event)">
                    </div>
                    <div class="col-12">
                        <label for="">&nbsp;</label><br>
                        <button class="btn btn-success" style="width:100%;font-size:18px" onclick="Traer_Datos_Seguimiento2()"><i class="fa fa-search"></i> Buscar Documento</button>
                    </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-12" id="div_buscador" style="display:none">
            <div class="card">
              <div class="card-header bg-primary">
                <h5 class="card-title m-0" id="lbl_titulo"><b>Seguimiento</b></h5>
              </div>
              <div class="card-body">
                <div class="row">
                <div class="col-md-12" id="div_seguimiento">
            <!-- The time line -->
            
                </div>

              <!-- /.timeline-label -->
              <!-- timeline item -->
                </div>
                </div>
              </div>
            </div>
            </div>

          </div>
          <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
    <em>Versión 1.0.0</em>
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; 2023 <a href="https://www.diresaapurimac.gob.pe/web/" target="_blank"><em>DIRESA APURÍMAC</em></a></strong>
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="plantilla/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plantilla/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="plantilla/dist/js/adminlte.min.js"></script>

<!-- AdminLTE for demo purposes -->
<script src="js/console_usuario.js?rev=<?php echo time();?>"></script>
<script src="js/console_horario.js?rev=<?php echo time();?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
   $(document).ready(function () {
    traerdatos();

    var input=  document.getElementById('txt_numero');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
     this.value = this.value.slice(0,8); 
})
var input=  document.getElementById('txt_dni');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
     this.value = this.value.slice(0,8); 
})
function sololetras(e) {
        key=e.keyCode || e.which;
 
        teclado=String.fromCharCode(key).toLowerCase();
 
        letras="qwertyuiopasdfghjklñzxcvbnm ";
 
        especiales="8-37-38-46-164";
 
        teclado_especial=false;
 
        for(var i in especiales){
            if(key==especiales[i]){
                teclado_especial=true;
                break;
            }
        }
 
        if(letras.indexOf(teclado)==-1 && !teclado_especial){
            return false;
        }
    }


  function soloNumeros(e){
      tecla = (document.all) ? e.keyCode : e.which;
      if (tecla==8){
          return true;
      }
      // Patron de entrada, en este caso solo acepta numeros
      patron =/[0-9]/;
      tecla_final = String.fromCharCode(tecla);
      return patron.test(tecla_final);
  }

</script>

</body>
</html>
