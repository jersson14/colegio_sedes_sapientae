<?php
setlocale(LC_TIME, 'es_ES.UTF-8'); // Establecer la configuración local para español
$current_year = date('Y');

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$html = '';
$codigo = $mysqli->real_escape_string($_GET['codigo']);
$idpagopen = $mysqli->real_escape_string($_GET['idpagopen']);

	$query="SELECT
	pensiones.id_nivel_academico, 
	pensiones.mes, 
	pago_pensiones.id_pago_pension, 
	pago_pensiones.id_matri, 
	pago_pensiones.concepto, 
	pago_pensiones.id_pension, 
	pago_pensiones.fecha_pago, 
	pago_pensiones.sub_total, 
	pago_pensiones.created_at, 
	matricula.id_alumno, 
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat, 
	CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
	empresa.emp_razon, 
	empresa.emp_logo, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	seccion.seccion_nombre,
  CONCAT_WS(' - ',Grado,seccion_nombre) AS grado
FROM
	pago_pensiones
	LEFT JOIN
	pensiones
	ON 
		pensiones.id_pensiones = pago_pensiones.id_pension
	INNER JOIN
	matricula
	ON 
		pago_pensiones.id_matri = matricula.id_matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
	INNER JOIN
	empresa
	ON 
		usuario.empresa_id = empresa.empresa_id
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	WHERE
		pago_pensiones.id_matri = '$codigo' and pago_pensiones.id_pago_pension='$idpagopen'";
//CONVERSIÓN DE FECHA




$resultado = $mysqli ->query($query);
while($row1 =$resultado->fetch_assoc()){
  
// Definir el contenido HTML para la primera página
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
         <img style="border: 1.5px solid black; padding: 10px;border-radius: 25px;" width="auto" align="center" src="../../../'.$row1['emp_logo'].'">
        </td>
    </tr>
</table>
<br>

<h2 style="text-align: center;margin: 0;text-decoration: underline;">BOLETA DE PAGO</h2>
<div style="text-align:center">
<br><b>DNI: </b><b>'.utf8_encode($row1['alum_dni']).'</b>
<br><b>Estudiante: </b>'.utf8_encode($row1['Estudiante']).'
<br><b>Nivel académico: </b>'.utf8_decode($row1['Nivel_academico']).'
<br><b>Grado - Sección: </b>'.$row1['grado'].'<hr>

<table width="100%" style="margin: 0;border-bottom:1px solid;border-left:0px;border-right:0px;border-top:0px;">
<thead>
    <tr style="background-color: #CCCDCF;">
        <th style="margin: 0;border-bottom:0px solid;border-left:0px;border-right:0px;border-top:0px;font-size:15px">Concepto</th>
        <th   style="margin: 0;border-bottom:0px solid;border-left:0px;border-right:0px;border-top:0px;font-size:15px">Mes</th>
        <th  style="margin: 0;border-bottom:0px solid;border-left:0px;border-right:0px;border-top:0px;font-size:15px">Fecha de pago</th>
        <th style="margin: 0;border-bottom:0px solid;border-left:0px;border-right:0px;border-top:0px;font-size:15px">Total pagado</th>

    </tr>
</thead>

';

$query2= "SELECT
	pensiones.id_nivel_academico, 
	pensiones.mes, 
	pago_pensiones.id_pago_pension, 
	pago_pensiones.id_matri, 
	pago_pensiones.concepto, 
	pago_pensiones.id_pension, 
	pago_pensiones.fecha_pago, 
	pago_pensiones.sub_total, 
	pago_pensiones.created_at, 
	matricula.id_alumno, 
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat, 
	CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
	empresa.emp_razon, 
	empresa.emp_logo,
    empresa.emp_telefono,
    empresa.emp_email,
    empresa.emp_direccion
FROM
	pago_pensiones
	LEFT JOIN
	pensiones
	ON 
		pensiones.id_pensiones = pago_pensiones.id_pension
	INNER JOIN
	matricula
	ON 
		pago_pensiones.id_matri = matricula.id_matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
	INNER JOIN
	empresa
	ON 
		usuario.empresa_id = empresa.empresa_id
WHERE
	pago_pensiones.id_matri = '$codigo' and pago_pensiones.id_pago_pension='$idpagopen'";
$resultado2=$mysqli->query($query2);
while($row2=$resultado2->fetch_assoc()){
    date_default_timezone_set('America/Lima'); // Configura la zona horaria a Lima/Perú
    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES.utf8', 'es_ES', 'spanish'); // Configura el locale para español
    
    // Supongamos que 'fecha_pago' está en formato 'YYYY-MM-DD'
    $fecha_consejo_uni = $row1['fecha_pago'];
    
    // Convertir la fecha a formato de timestamp
    $timestamp_fecha_pago = strtotime($fecha_consejo_uni);
    
    // Formatear la fecha en el formato requerido: "11 de agosto del 2024"
    $fecha_formateada = strftime('%d de %B del %Y', $timestamp_fecha_pago);
    
    // Convertir a minúsculas
    $fecha_formateada_minusculas = mb_strtolower($fecha_formateada, 'UTF-8');
    $html.="
    <tr>
    <td  style='text-align:center;font-size:14px'>".utf8_encode($row2['concepto'])."</td>
    <td  style='text-align:center;font-size:14px'>".utf8_encode($row2['mes'])."</td>
    <td  style='text-align:center;font-size:14px'>".$fecha_formateada_minusculas."</td>
    <td  style='text-align:center;font-size:14px'>S/. ".utf8_encode($row2['sub_total'])."</td>

   
";

$html.="</tr><tbody>
</tbody>
</table>

<br>
<div style='text-align:center'>
<b>!Gracias por su preferencia¡</b><br>

<br><b>Tel&eacute;fono: </b>".utf8_encode($row2['emp_telefono'])."
<br><b>Email: </b>".utf8_encode($row2['emp_email'])."<br>
<b style='text-align: center;'>Dirección: </b>".utf8_encode($row2['emp_direccion'])."<br>
   <br> <b style='text-align: center;'>Abancay-Apurímac-Perú</b></div>
</div>
<br><br>
<p style='text-align:center'>(Esta boleta de pago puede ser remplazada por una original en el colegio)</p>
";
}
}


$mpdf = new \Mpdf\Mpdf(
    ['mode' => 'UTF-8','format' => [130,210]]
);
$mpdf->WriteHTML($html);
$mpdf->Output();
?>