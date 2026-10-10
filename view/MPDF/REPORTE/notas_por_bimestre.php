<?php
// Fase 0.2: los reportes también exigen sesión y rol (antes se abrían por URL sin login).
require_once __DIR__ . '/../../../core/guard.php';
exigir_rol('ADMINISTRADOR');
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../../../model/model_conexion.php';

use App\Reportes\Datos;
use App\Reportes\Pdf;

$datos = new Datos((new conexionBD())->conexionPDO());
$id_matricula = (string) ($_GET['id_matricula'] ?? '');
$id_bimestre = (string) ($_GET['id_bimestre'] ?? '');

// Consulta para obtener los datos del estudiante y la institución
$query_datos = "SELECT
	matricula.id_alumno, 
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante, 
	empresa.emp_razon, 
	empresa.emp_logo, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	seccion.seccion_nombre, 
	CONCAT_WS(' - ', aulas.Grado, seccion.seccion_nombre) AS grado, 
	`año_escolar`.`año_escolar`, 
	empresa.emp_razon,
    empresa.emp_cod,  
	empresa.emp_email, 
	empresa.emp_telefono, 
	empresa.emp_direccion, 
	periodos.id_periodo, 
	periodos.periodos
FROM
	matricula
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
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	periodos
	ON 
		`año_escolar`.`Id_año_escolar` = periodos.`id_año_escolar`
WHERE
	matricula.id_matricula = ? AND periodos.id_periodo = ?";

$datos_estudiante = $datos->fila($query_datos, [$id_matricula, $id_bimestre]);
if ($datos_estudiante === null) {
    Pdf::sinDatos(); // periodo de otro año o matrícula inexistente (antes: avisos de PHP y un PDF vacío)
}

// Consulta para obtener las notas y competencias
$query_notas = "SELECT
    asignaturas.nombre_asig, 
    criterios.competencias, 
    notas.nota, 
    notas.conclusiones, 
    periodos.periodos
FROM
    criterios
    INNER JOIN
    detalle_asignatura_docente
    ON 
        criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
    INNER JOIN
    asignaturas
    ON 
        detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
    INNER JOIN
    notas
    ON 
        criterios.id_criterio = notas.id_criterio
    INNER JOIN
    periodos
    ON 
        notas.id_bimestre = periodos.id_periodo
WHERE
    notas.id_matricula = ? AND
    notas.id_bimestre = ?
ORDER BY
    asignaturas.nombre_asig ASC, 
    criterios.id_criterio ASC";

$filas_notas = $datos->filas($query_notas, [$id_matricula, $id_bimestre]);

// Consulta para obtener las notas de los padres
$query_notas_padres = "SELECT
    criterio,
    nota
FROM
    notas_padre
WHERE 
    id_matricula = ? AND id_bimestre = ?";

$filas_notas_padres = $datos->filas($query_notas_padres, [$id_matricula, $id_bimestre]);

// Consulta para obtener la asistencia
$query_asistencia = "SELECT
    SUM(CASE WHEN asistencia.estado = 'PRESENTE' THEN 1 ELSE 0 END) AS total_asistencia_presente,
    SUM(CASE WHEN asistencia.estado = 'TARDE' THEN 1 ELSE 0 END) AS total_asistencia_tarde,
    SUM(CASE WHEN asistencia.estado = 'AUSENTE' THEN 1 ELSE 0 END) AS total_asistencia_ausente,
    SUM(CASE WHEN asistencia.estado = 'JUSTIFICADO' THEN 1 ELSE 0 END) AS total_asistencia_justificado
FROM
    asistencia
WHERE
    id_matricula = ?
    AND fecha BETWEEN (SELECT fecha_inicio FROM periodos WHERE id_periodo = ?)
                  AND (SELECT fecha_fin FROM periodos WHERE id_periodo = ?)";

$asistencia = $datos->fila($query_asistencia, [$id_matricula, $id_bimestre, $id_bimestre]);

// Generar HTML para el PDF
$html = '
 <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 150px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; } /* Cambia el tamaño de fuente aquí */
        th, td { border: 1px solid black; padding: 8px; text-align: left; line-height: 1.5; } /* Aumentar el espaciado */
        th { background-color: #f2f2f2; }
        .curso { font-weight: bold; }
        .nota { text-align: center; }
    </style>
<div class="header">
    <img src="' . Pdf::imagen((string) $datos_estudiante['emp_logo']) . '" alt="Logo">
    <h3><u>INFORME DE PROGRESO DEL APRENDIZAJE DEL ESTUDIANTE '.$datos_estudiante['año_escolar'].'</u></h3>
</div>
<table>
    <tr>
        <th>DRE:</th>
        <td><b>APURÍMAC</b></td>
        <th>UGEL:</th>
        <td><b>UGEL Abancay</b></td>
    </tr>
    <tr>
        <th>Institución Educativa:</th>
        <td>' . $datos_estudiante['emp_razon'] . '</td>
        <th>Código Modular:</th>
        <td>' . $datos_estudiante['emp_cod'] . '</td>
    </tr>
    <tr>
        <th>DNI:</th>
        <td>' . $datos_estudiante['alum_dni'] . '</td>
        <th>Estudiante:</th>
        <td>' . $datos_estudiante['Estudiante'] . '</td>
    </tr>
    <tr>
        <th>Grado:</th>
        <td>' . $datos_estudiante['Grado'] . '</td>
        <th>Sección:</th>
        <td>' . $datos_estudiante['seccion_nombre'] . '</td>
    </tr>
    <tr>
        <th>Nivel:</th>
        <td>' . $datos_estudiante['Nivel_academico'] . '</td>
        <th>Año Escolar:</th>
        <td>' . $datos_estudiante['año_escolar'] . '</td>
    </tr>
</table>

<h3>Competencias y Calificaciones</h3>
<table>
    <tr>
        <td colspan="4" style="text-align:center; font-weight:bold;">
            Periodo: ' .  $datos_estudiante['periodos']. '
        </td>
    </tr>
    <tr>
        <th style="text-align:center;">Área</th>
        <th style="text-align:center;">Competencia</th>
        <th style="text-align:center;">Nota</th>
        <th style="text-align:center;">Conclusión Descriptiva</th>
    </tr>';

    $current_area = '';
    $area_rows = 0;
    $result_array = $filas_notas;
    
    foreach ($result_array as $index => $row) {
        if ($current_area != $row['nombre_asig']) {
            // Si no es la primera área y hemos terminado con la anterior, cerramos su rowspan
            if ($current_area != '' && $area_rows > 0) {
                $html = str_replace("<!--ROWSPAN_{$current_area}-->", " rowspan=\"$area_rows\"", $html);
            }
            
            // Iniciamos una nueva área
            $html .= "<tr>\n<td <!--ROWSPAN_{$row['nombre_asig']}-->><strong>{$row['nombre_asig']}</strong></td>\n";
            $current_area = $row['nombre_asig'];
            $area_rows = 1;
        } else {
            $html .= "<tr>\n";
            $area_rows++;
        }
    
        $html .= "
            <td class=\"competencia\">{$row['competencias']}</td>
            <td class=\"nota\">{$row['nota']}</td>
            <td>{$row['conclusiones']}</td>
        </tr>\n";
    
        // Si es el último elemento, cerramos el rowspan de la última área
        if ($index == count($result_array) - 1) {
            $html = str_replace("<!--ROWSPAN_{$current_area}-->", " rowspan=\"$area_rows\"", $html);
        }
    }
    
$html .= '
</table>

<h3>Evaluación de Padres de Familia</h3>
<table>
    <tr>
        <th style="text-align:center;">Criterio</th>
        <th style="text-align:center;">Nota</th>
    </tr>';

foreach ($filas_notas_padres as $row) {
    $html .= '
    <tr>
        <td>' . $row['criterio'] . '</td>
        <td class="nota">' . $row['nota'] . '</td>
    </tr>';
}

$html .= '
</table>

<h3>Asistencia y Puntualidad</h3>
<table>
    <tr>
        <th style="text-align:center;">Presentes</th>
        <th style="text-align:center;">Tardanzas</th>
        <th style="text-align:center;">Faltas</th>
        <th style="text-align:center;">Justificadas</th>
    </tr>
    <tr>
        <td class="nota">' . $asistencia['total_asistencia_presente'] . '</td>
        <td class="nota">' . $asistencia['total_asistencia_tarde'] . '</td>
        <td class="nota">' . $asistencia['total_asistencia_ausente'] . '</td>
        <td class="nota">' . $asistencia['total_asistencia_justificado'] . '</td>
    </tr>
</table>

<!-- Sección de firma del director -->
<div style="margin-top: 150px; text-align: center;">
    <p>______________________________________________</p>
    <p><strong>FIRMA Y SELLO DEL DIRECTOR</strong></p>
</div>';

Pdf::enviar($html, ['format' => 'A4']);
?>
