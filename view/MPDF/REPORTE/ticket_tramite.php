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
CONCAT_WS(' ',documento.doc_nombreremitente,documento.doc_apepatremitente,documento.doc_apematremitente)AS remitente,
documento.doc_nrodocumento, 
tipo_documento.tipodo_descripcion,
documento.doc_fecharegistro,
documento.doc_dniremitente
FROM
documento
INNER JOIN
tipo_documento
ON 
    documento.tipodocumento_id = tipo_documento.tipodocumento_id
    WHERE documento.documento_id  = '".$codigo."'";
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
    <table>
        <tr>
            <td align="center">
             <img width="100%" align="center" src="../../../view/'.$logo.'">
            </td>
        </tr>
    </table>
    <span style="font-size:12px"><b><br>Número de Expediente:
    </b> '.$row['documento_id'].'
    </span><br>
    <span style="font-size:12px"><b><br>Fecha - Hora:
    </b> '.date('d/m/Y - H:i:s', strtotime($row['doc_fecharegistro'])).'
    </span><br>
    <span style="font-size:12px"><b><br>Tipo Documento:
    </b> '.$row['tipodo_descripcion'].'
    </span><br>
    <span style="font-size:12px"><b><br>DNI:
    </b> '.$row['doc_dniremitente'].'
    </span><br>
    <span style="font-size:12px"><b><br>Remitente:<br>
    </b> '.utf8_encode($row['remitente']).'
    </span><br><br>
        <table width="100%" cellpadding="8">
            <tr>
                <td class="barcodecell" align="center">
                    <barcode code="'.$row['documento_id'].'" type="QR"  class="barcode" size="1.3" disableborder="1"/>
                </td>
            </tr>
        </table>
    <htmlpagefooter name="myFooter1">
        <table width="100%">
            <tr>
                <td width="50%">
                '.$telefono.'
                </td>
                <td width="50%">
                '.utf8_encode($email).'
                </td>
            </tr>
        </table>
    </htmlpagefooter>
    ';
}
$mpdf = new \Mpdf\Mpdf(
    ['mode' => 'UTF-8','format' => [80,130]]
);
$mpdf->WriteHTML($html);
$mpdf->Output();
?>