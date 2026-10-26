<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<?php
// Ficha única del estudiante (Fase 13, paso 6). La abre Ficha_Abrir(id) de js/ficha.js, que luego llama a Ficha_Mostrar.
require_once __DIR__ . '/../../utilitario/tema.php';
$TEMA = tema_actual();
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Ficha del estudiante</h1>
        <p class="pagina-ayuda">Todo lo del estudiante en una sola pantalla.</p>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Ficha del estudiante</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content fc">
  <div class="container-fluid">
    <!-- Solo al imprimir: identidad del colegio -->
    <div class="fc-membrete">
      <img src="../<?php echo $e($TEMA['logo'] ?: 'img/logo.jpeg'); ?>" alt="">
      <div><b><?php echo $e($TEMA['nombre']); ?></b><span>Ficha del estudiante · impresa el <?php echo date('d/m/Y'); ?></span></div>
    </div>
    <div id="ficha_contenido" aria-live="polite">
      <div class="pi-tarjeta fc-cargando"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando la ficha…</div>
    </div>
  </div>
</section>
