<?php
// Fase 0.2: los reportes también exigen sesión y rol (antes se abrían por URL sin login).
require_once __DIR__ . '/../../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ESTUDIANTE');
require_once __DIR__ . '/../../../core/pertenencia.php';
setlocale(LC_TIME, 'es_ES.UTF-8'); // Establecer la configuración local para español
$current_year = date('Y');

require_once __DIR__ . '/../../../model/model_conexion.php';

use App\Reportes\Datos;
use App\Reportes\Fecha;
use App\Reportes\Pdf;

$datos = new Datos((new conexionBD())->conexionPDO());
$html = '';
$codigo = (string) ($_GET['codigo'] ?? '');
$idpagopen = (string) ($_GET['idpagopen'] ?? '');
// IDOR: el estudiante solo imprime boletas de sus propios pagos.
exigir_pago_propio($codigo, $idpagopen);

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
	LEFT JOIN usuario ON matricula.usu_id = usuario.usu_id /* matrícula sin cuenta: igual se reporta */
	INNER JOIN empresa ON empresa.empresa_id = COALESCE(usuario.empresa_id, 1)
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
		pago_pensiones.id_matri = ? and pago_pensiones.id_pago_pension = ?";
//CONVERSIÓN DE FECHA




$filas1 = $datos->filas($query, [$codigo, $idpagopen]);
if ($filas1 === []) {
    Pdf::sinDatos();
}
foreach ($filas1 as $row1) {
  
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
         <img style="border: 1.5px solid black; padding: 10px;border-radius: 25px;" width="auto" align="center" src="' . Pdf::imagen((string) $row1['emp_logo']) . '">
        </td>
    </tr>
</table>
<br>

<h2 style="text-align: center;margin: 0;text-decoration: underline;">BOLETA DE PAGO</h2>
<div style="text-align:center">
<br><b>DNI: </b><b>'.$row1['alum_dni'].'</b>
<br><b>Estudiante: </b>'.$row1['Estudiante'].'
<br><b>Nivel académico: </b>'.$row1['Nivel_academico'].'
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
	LEFT JOIN usuario ON matricula.usu_id = usuario.usu_id /* matrícula sin cuenta: igual se reporta */
	INNER JOIN empresa ON empresa.empresa_id = COALESCE(usuario.empresa_id, 1)
WHERE
	pago_pensiones.id_matri = ? and pago_pensiones.id_pago_pension = ?";
foreach ($datos->filas($query2, [$codigo, $idpagopen]) as $row2) {
    date_default_timezone_set('America/Lima'); // Configura la zona horaria a Lima/Perú
    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES.utf8', 'es_ES', 'spanish'); // Configura el locale para español
    
    // Supongamos que 'fecha_pago' está en formato 'YYYY-MM-DD'
    $fecha_consejo_uni = $row1['fecha_pago'];
    
    // Convertir la fecha a formato de timestamp
    $timestamp_fecha_pago = strtotime($fecha_consejo_uni);
    
    // Formatear la fecha en el formato requerido: "11 de agosto del 2024"
    $fecha_formateada = Fecha::larga($fecha_consejo_uni);
    
    // Convertir a minúsculas
    $fecha_formateada_minusculas = mb_strtolower($fecha_formateada, 'UTF-8');
    $html.="
    <tr>
    <td  style='text-align:center;font-size:14px'>".$row2['concepto']."</td>
    <td  style='text-align:center;font-size:14px'>".$row2['mes']."</td>
    <td  style='text-align:center;font-size:14px'>".$fecha_formateada_minusculas."</td>
    <td  style='text-align:center;font-size:14px'>S/. ".$row2['sub_total']."</td>

   
";

$html.="</tr><tbody>
</tbody>
</table>

<br>
<div style='text-align:center'>
<b>!Gracias por su preferencia¡</b><br>

<br><b>Tel&eacute;fono: </b>".$row2['emp_telefono']."
<br><b>Email: </b>".$row2['emp_email']."<br>
<b style='text-align: center;'>Dirección: </b>".$row2['emp_direccion']."<br>
   <br> <b style='text-align: center;'>Abancay-Apurímac-Perú</b></div>
</div>
<br><br>
<p style='text-align:center'>(Esta boleta de pago puede ser remplazada por una original en el colegio)</p>
";
}
}


Pdf::enviar($html, ['mode' => 'UTF-8', 'format' => [130, 210]]);
?>