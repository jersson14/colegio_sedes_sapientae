
<?php
require_once  __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$codigo = $_GET['codigo'];
$html="";
$consulta="SELECT
documento.documento_id, 
documento.doc_dniremitente, 
CONCAT_WS(' ',documento.doc_nombreremitente,documento.doc_apepatremitente,documento.doc_apematremitente) AS REMITENTE, 
documento.doc_nombreremitente, 
documento.doc_apepatremitente, 
documento.doc_apematremitente, 
documento.tipodocumento_id, 
tipo_documento.tipodo_descripcion, 
documento.doc_estatus, 
documento.doc_nrodocumento, 
documento.doc_celularremitente, 
documento.doc_emailremitente, 
documento.doc_direccionremitente, 
documento.doc_representacion, 
documento.doc_ruc, 
documento.doc_empresa, 
documento.doc_folio, 
documento.doc_archivo, 
documento.doc_asunto, 
documento.doc_fecharegistro,
documento.area_origen, 
documento.area_destino, 
documento.area_id,
documento.dias_pasados,
dias_respuesta,
acciones,
doc_observaciones,
dias_respuesta,
origen.area_nombre as origen, 
destino.area_nombre as destino
FROM
documento
INNER JOIN
tipo_documento
ON 
    documento.tipodocumento_id = tipo_documento.tipodocumento_id
INNER JOIN
area AS origen
ON 
    documento.area_origen = origen.area_cod
INNER JOIN
area AS destino
ON 
    documento.area_destino = destino.area_cod
    WHERE documento_id = '".$codigo."'";

  $resultado=$mysqli->query($consulta);
  
  while($filas=$resultado->fetch_assoc()){
    $fecha = setlocale(LC_TIME, "spanish");					    
        $fecharegistro = date("d-m-Y - h:i:sa", strtotime($filas['doc_fecharegistro']));
    
    $html.='
    <div style="font-family:arial">
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead>
    <tr>
    <td align="left"> <img src="../../../minsa.png" alt="Girl in a jacket" width="150" height="40" align="left"></td>
    <td align="right"> <img src="../../diresa.png" alt="Girl in a jacket" width="150" height="40" align="left"></td>
    </tr>
    </thead>
    </table><br>
    <div margin: 0;><h2  style="text-align:center;float:center;width:100%; margin: 0;font-family:Cooper Black; color:black;font-size:17px">
        <u>HOJA DE ENVÍO DE TRÁMITE GENERAL</u></h2>
    </div><br>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Remitente: </b><span>'.utf8_encode($filas['REMITENTE']).'</span></td>
    <td align="left"><b>Área Orig.: </b><span>'.utf8_encode($filas['origen']).'</span></td>
    <td align="left"><b>N° Documento: </b>'.utf8_encode($filas['documento_id']).'</td>
    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Área Dest.: </b><span>'.utf8_encode($filas['destino']).'</span></td>
    <td align="left"><b>N° Expediente: </b>'.utf8_encode($filas['doc_nrodocumento']).'</td>
    <td align="left"><b>N° Folios: </b>'.utf8_encode($filas['doc_folio']).'</td>

    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Tipo Documento: </b>'.utf8_encode($filas['tipodo_descripcion']).'</td>
    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Fecha de Registro: </b><span>'.$fecharegistro.'</span></td>
    </tr>
    </thead>
    </table>
    
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Asunto: </b><span>'.utf8_encode($filas['doc_asunto']).'</span></td>

    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Observaciones: </b><span>'.utf8_encode($filas['doc_observaciones']).'</span></td>
    </tr>
    </thead>
    </table>
    </div>
    <div style="border-radius:5px;border: 1px black solid;"><h4 style="color:white;background-color:#0677A8;text-align:center;font-family:arial"><b>SEGUIMIENTO DE DOCUMENTO</b></h4></div>
<table style="font-size: 12px; width:100%;font-family:arial;height: 300px " border="1">
<thead style="">
        <tr  bgcolor="#0A8BC3" >

            <th height="25" style="color:white">DESTINO</th>
            <th height="25" style="color:white">ACCIONES</th>
            <th height="25" style="color:white">FECHA</th>
            <th height="25" style="color:white">DESCRIPCIÓN</th>
            <th height="25" style="color:white">ESTADO</th>



        </tr>
</thead>
<tbody>';
$consultamovi= "SELECT 
movimiento.movimiento_id, 
movimiento.documento_id,
area.area_cod, 
area.area_nombre,
movimiento.mov_fecharegistro, 
movimiento.mov_descripcion, 
movimiento.mov_estatus,
movimiento.mov_acciones
FROM
movimiento
INNER JOIN
area
ON 
    movimiento.area_origen_id = area.area_cod

    WHERE movimiento.documento_id = '".$codigo."'";
    
    $resultadomovi=$mysqli->query($consultamovi);
    while($filamovi=$resultadomovi->fetch_assoc()){
        $fechamovi = setlocale(LC_TIME, "spanish");					    
        $fecharegistromovi = date("d-m-Y - h:i:sa", strtotime($filas['doc_fecharegistro']));
        $html.='<tr>
                <td height="40" style="text-align:center">'.utf8_encode($filamovi['area_nombre']).'</td>
                <td height="40" style="text-align:center">'.utf8_encode($filamovi['mov_acciones']).'</td>
                <td height="40" style="text-align:center">'.$fecharegistromovi.'</td>
                <td height="40" style="text-align:center">'.utf8_encode($filamovi['mov_descripcion']).'</td>
                <td height="40" style="text-align:center">'.$filamovi['mov_estatus'].'</td>

    ';

}
$html.='
</tr>
</tbody>
</table>
</div>
<div style="float:right;widht:100%; text-align:center">
        <img src="../../../acciones.jpg" alt="Girl in a jacket" width="100%" height="20%">
    </div>
';
  }
$mpdf = new \Mpdf\Mpdf(['mode' => ' utf-8', 'format' => 'letter']);

$mpdf->WriteHTML($html);
$mpdf->Output();