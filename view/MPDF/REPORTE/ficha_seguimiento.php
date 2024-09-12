<?php
require_once  __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$codigo = $_GET['codigo'];
$query ="SELECT
empresa.empresa_id, 
empresa.emp_razon, 
empresa.emp_email, 
empresa.emp_cod, 
empresa.emp_telefono, 
empresa.emp_direccion, 
empresa.emp_logo
FROM
empresa";
date_default_timezone_set('America/Lima');
$html="";
$resultado = $mysqli->query($query);
$query2="SELECT
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
$resultado2 = $mysqli->query($query2);
$razon      = "";
$telefono   = "";
$email      = "";
$codigo     = "";
$logo       = "";
while($row2 = $resultado->fetch_assoc()){
    $razon      = $row2['emp_razon'];
    $cod_cel    = $row2['emp_cod'];
    $telefono    = $row2['emp_telefono'];
    $email      = utf8_encode($row2['emp_email']);
    $logo       = $row2['emp_logo'];
}
while($row = $resultado2->fetch_assoc()){
  
    $html.='
    <style>
        @page{
            margin: 10mm;
            margin-header: 0mm;
            margin-footer: 0mm;
            odd-footer-name: html_myFooter1;
        }
    </style>
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
    <td align="left"><b>Remitente: </b><span>'.utf8_encode($row['REMITENTE']).'</span></td>
    <td align="left"><b>Área Orig.: </b><span>'.utf8_encode($row['origen']).'</span></td>
    <td align="left"><b>N° Documento: </b>'.utf8_encode($row['documento_id']).'</td>
    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Área Dest.: </b><span>'.utf8_encode($row['destino']).'</span></td>
    <td align="left"><b>N° Expediente: </b>'.utf8_encode($row['doc_nrodocumento']).'</td>
    <td align="left"><b>N° Folios: </b>'.utf8_encode($row['doc_folio']).'</td>
    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Tipo Documento: </b>'.utf8_encode($row['tipodo_descripcion']).'</td>
    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Fecha de Registro: </b><span>'.date('d/m/Y - H:i:s', strtotime($row['doc_fecharegistro'])).'</span></td>
    </tr>
    </thead>
    </table>
    
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Asunto: </b><span>'.utf8_encode($row['doc_asunto']).'</span></td>

    </tr>
    </thead>
    </table>
    <table style="  font-size: 12px;width:100%;border-collapse:collapse; border-color:#FF0080; margin: 0;" >
    <thead align="left">
    <tr>
    <td align="left"><b>Observaciones: </b><span>'.utf8_encode($row['doc_observaciones']).'</span></td>

    </tr>
    </thead>
    </table>
    <div style="float:right;widht:100%; text-align:center">
        <img src="../../../cuadro_seguimiento.jpg" alt="Girl in a jacket" width="100%" height="95%">
    </div>

    ';
}
$mpdf = new \Mpdf\Mpdf(
    ['mode' => 'UTF-8','format' => [190, 236]]
);
$mpdf->WriteHTML($html);
$mpdf->Output();

?>