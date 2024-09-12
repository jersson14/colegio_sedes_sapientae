<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>STDW | DIRESA</title>
  <link rel="stylesheet" href="plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plantilla/plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="plantilla/dist/css/adminlte.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="icon" href="img/dire.jpg" type="image/jpg">

</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
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
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
      <a href="index.php" class="navbar-brand">
      <i class="fas fa-file-signature" style="color:green"></i>        
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
            <a href="seguimiento.php" class="nav-link"><i class="fa fa-search"></i><b> Seguimiento Trámite</b></a>
          </li>
            </ul>
        </div>
    </div>
  </nav>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container">
                <div class="row mb-2">
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
            <div class="card">
            <div class="card-header">
            <hr>
            <p style="font-size:13.5px;text-align:justify"><b>La Dirección Regional de Salud Apurímac pone a su disposición la MESA DE PARTES VIRTUAL, para la recepción de documentos y solicitudes durante el Estado de Emergencia. Los documentos serán atendidos y recepcionados de Lunes a Viernes en el horario de 8:00am a 5:00pm. Es obligatorio registrar su correo electrónico, para dar respuesta a su solicitud.</b></p>
            <hr>
                <div class="row">
                <div class="col-12">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><b>DATOS DEL REMITENTE</b></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                        </button>
                    </div>

                </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 form-group" style="color:red">
                                <h7><b>Campos Obligatorios (*)</b></h7>
                            </div>
                            <div class="col-6 form-group">
                                <label for="" style="font-size:small;">N° DNI(*):</label>
                                <input type="text" class="form-control" id="txt_dni" max="8" size="8" maxlenght="8" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="" style="font-size:small;">Nombre(*):</label>
                                <input type="text" class="form-control" id="txt_nom" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="" style="font-size:small;">Apellido Paterno(*):</label>
                                <input type="text" class="form-control" id="txt_apepat" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="" style="font-size:small;">Apellidos Materno(*):</label>
                                <input type="text" class="form-control" id="txt_apemat" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="" style="font-size:small;">Celular(*):</label>
                                <input type="text" class="form-control" id="txt_celular" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="" style="font-size:small;">Email(*):</label>
                                <input type="text" class="form-control" id="txt_email">
                            </div>
                            <div class="col-12">
                                <label for="" style="font-size:small;">Dirección(*):</label>
                                <input type="text" class="form-control" id="txt_dire">
                            </div>
                            <div class="col-12"><br>
                                <label for="" style="font-size:small;">En Representación</label>
                            </div>
                            <div class="col-12 row">
                                <!--radio-->
                                <div class="col-4 form-group clearfix">
                                    <div class="icheck-success d-inline">
                                        <input type="radio" checked value="A Nombre Propio" id="rad_presentacion1" name="r1" >
                                        <label for="rad_presentacion1" style="font-weight:normal; font-size:small">
                                            <b>A Nombre Propio</b>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-4 form-group clearfix">
                                    <div class="icheck-success d-inline">
                                        <input type="radio" id="rad_presentacion2" name="r1" value="A Otra Persona Natural">
                                        <label for="rad_presentacion2" style="font-weight:normal; font-size:small">
                                            <b>A Otra Persona Natural</b>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-4 form-group clearfix">
                                    <div class="icheck-success d-inline">
                                        <input type="radio" id="rad_presentacion3" name="r1" value="Persona Jurídica">
                                        <label for="rad_presentacion3" style="font-weight:normal; font-size:small">
                                            <b>Persona Jurídica</b>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 row" id="div_juridico" style="display:none">
                                <div class="row">
                                <div class="col-4 form-group" >
                                    <label for="" style="font-size:small;">RUC(*):</label>
                                    <input type="text" class="form-control" id="txt_ruc" onkeypress="return soloNumeros(event)">
                                </div>
                                <div class="col-8 form-group" >
                                    <label for="" style="font-size:small;">Razón Social(*):</label>
                                    <input type="text" class="form-control" id="txt_razon">
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="text-align:justify">
               
                <p>*NOTA: Enviar los documentos en un solo archivo en formato pdf o jpg, deberá optimizar los documentos antes de enviarlos. El tamaño máximo de los archivos no debe superar los 30MB.</p>
                <hr>
                <p>*El Administrado garantizará la autenticidad e integridad de los documentos presentados por la Mesa de Partes Virtual, caso contrario será pasible de una denuncia penal por falsedad genérica y falsedad ideológica, según corresponda.</p>
                <hr>
                <p>*De requerir mayor información respecto al uso de este mecanismo, sírvase comunicarse a nuestra central de consultas al (083) 321117.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-danger">
                    <div class="card-header">
                    <h3 class="card-title"><b>DATOS DEL DOCUMENTO</b></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                        </button>
                    </div>
                       
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 form-group" style="color:red">
                                <h7><b>Campos Obligatorios (*)</b></h7>
                            </div>
                           
                            <div class="col-12 form-group">
                                <label for="" >Tipo Documento(*):</label>
                                <select class="js-example-basic-single" id="select_tipo" style="width:100%"></select>
                            </div>
                            <div class="col-12 form-group" style="color:red">
                            <label for="">Requisitos: "OJO los documentos como requisitos deben estar en un solo archivo"</label>
                                <textarea style="color:red" class="form-control" id="txt_requisitos" readonly rows="3" style="resize:none"></textarea>
                            </div>
                            <div class="col-12 form-group">
                                <label for="" >N° Documento(*):</label>
                                <input type="text" class="form-control" id="txt_ndocumento" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-12 form-group">
                                <label for="" >Asunto(*):</label>
                                <textarea class="form-control" id="txt_asunto" rows="3" style="resize:none"></textarea>
                            </div>
                            <div class="col-12 form-group" style="text-align:center">
                            <label for="" style="font-size:13px;color:red">Para realizar la firma digital debe ingresar al siguiente manual para realizar los pasos por el APP ReFirma PDF de la RENIEC</label>
                            <a class='btn btn-primary btn-lg' href='manual_usuario.pdf' target='_blank'><i class='fas fa-file-pdf'></i> VER MANUAL DE USUARIO REFIRMA PDF V1.6</a>
                            </div>
                            <div class="col-8 form-group">
                                <label for="" >Adjuntar Documento(*):</label>
                                <input type="file" clas="form-control" id="txt_archivo">
                                <label for="" style="font-size:11px;color:red">El documento debe estar debidamente firmado, en formato PDF y con un tamaño máximo de 30 MB.</label>
                            </div>
                            <div class="col-4 form-group">
                                <label for="" >N° Folios(*):</label>
                                <input type="text" class="form-control" id="txt_folio" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-12">
                                <div class="form-group clearfix">
                                    <div class="icheck-success d-inline">
                                        <input type="checkbox"  id="checkboxSuccess1" onclick="Validar_Informacion()">
                                        <label for="checkboxSuccess1" style="align:justify">
                                            Declaro bajo penalidad de pejurio, que toda información proporcionada es correscta y veridica.
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12" style="text-align:center">
                                <button class="btn btn-success btn-lg" onclick="Registrar_Tramite()" id="btn_registro"><i class="fas fa-save"></i><b> REGISTRAR TRÁMITE</b></button>
                            </div>
                        </div>
                    </div>
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

<script src="js/console_tramite_externo.js?rev=<?php echo time();?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


</body>
</html>
<script>
    $(document).ready(function () {
        $('.js-example-basic-single').select2();

        Cargar_Select_Tipo();
        $("#select_tipo").change(function(){
        var id=$("#select_tipo").val();
        Traerrequisitotipodoc(id);
        });

     
        $("#rad_presentacion1").on('click', function(){
            document.getElementById('div_juridico').style.display="none";
        });
        $("#rad_presentacion2").on('click', function(){
            document.getElementById('div_juridico').style.display="none";
        });
        $("#rad_presentacion3").on('click', function(){
            document.getElementById('div_juridico').style.display="block";
        });
    });
    
    Validar_Informacion();
    function Validar_Informacion(){

        if(document.getElementById('checkboxSuccess1').checked==false){
            $("#btn_registro").addClass("disabled");
        }else{
            $("#btn_registro").removeClass("disabled");
        }
    }

    $('input[type="file"]').on('change', function(){
        var ext = $( this ).val().split('.').pop();
        console.log($( this ).val());
        if($(this).val() !=''){
        if(ext == "PDF" || ext =="pdf"){
            if($(this)[0].files[0].size > 31457280){//----- 30 MB
            //if($(this)[0].files[0].size> 1048576){ ------- 1 MB
            //if($(this)[0].files[0].size> 10485760){ ------- 10 MB
                Swal.fire("El archivo seleccionado es demasiado pesado",
                "<label style='color:#9B0000;'>Seleccionar un archivo mas liviano</label>","waning");
                $("#txt_archivo").val("");
                return;
                //$("#btn_subir").prop("disabled",true);
            }else{
                //$("#btn_subir").attr("disabled",false);
            }
            $("#txtformato").val(ext);
        }
        else{
            $("#txt_archivo").val("");
            Swal.fire("Mensaje de Error","Extensión no permitida: " + ext,
            "error");
        }
        }
    });

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
var input=  document.getElementById('txt_dni');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
     this.value = this.value.slice(0,8); 
})
var input=  document.getElementById('txt_celular');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})
var input=  document.getElementById('txt_folio');
input.addEventListener('input',function(){
  if (this.value.length > 3) 
     this.value = this.value.slice(0,3); 
})
</script>
