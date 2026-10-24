<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>MIS BOLETAS DE PAGO</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">MIS BOLETAS</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-striped table-bordered table-sm">
          <thead style="background-color:#0A5D86;color:#FFFFFF;">
            <tr><th>Mes</th><th>Contrato</th><th class="text-right">Sueldo / horas</th><th class="text-right">Ingresos</th><th class="text-right">Descuentos</th><th class="text-right">Adelantos</th><th class="text-right">Neto</th><th class="text-right">Pagado</th><th>Estado</th><th></th></tr>
          </thead>
          <tbody id="tbody_mis_boletas"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $.ajax({ url: "../controller/planilla/controlador_mis_boletas.php", type: 'POST' }).done(function(resp) {
      let filas = "";
      resp.data.forEach(function(b) {
        filas += "<tr><td><b>" + b.mes_nombre + " " + b.anio + "</b></td><td>" + $('<div>').text(b.tipo_contrato).html() + "</td><td class='text-right'>" + formatoMoneda(b.base)
               + "</td><td class='text-right'>" + formatoMoneda(b.ingresos) + "</td><td class='text-right'>" + formatoMoneda(b.descuentos) + "</td><td class='text-right'>"
               + formatoMoneda(b.adelantos) + "</td><td class='text-right'><b>" + formatoMoneda(b.neto) + "</b></td><td class='text-right'>" + formatoMoneda(b.pagado)
               + "</td><td>" + b.estado + "</td><td><a class='btn btn-success btn-sm' target='_blank' href='../view/MPDF/REPORTE/boleta_docente.php?id=" + b.id_detalle
               + "'><i class='fas fa-print'></i> Boleta</a></td></tr>";
      });
      $("#tbody_mis_boletas").html(filas || "<tr><td colspan='10' class='text-center text-muted'>Aún no tiene boletas de pago</td></tr>");
    });
  });
</script>
