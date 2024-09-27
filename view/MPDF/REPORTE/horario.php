<?php
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$codigo = $mysqli->real_escape_string($_GET['codigo']);

// Obtener el código de matrícula desde la URL

// Consulta para obtener los datos de matrícula y pagos
$query_pagos = "SELECT DISTINCT
	horas_aula.`id_año_academico`, 
	horas_aula.id_aula, 
	horas_aula.turno, 
	horas_aula.estado, 
	`año_escolar`.`año_escolar`, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	horarios.estado AS HORARIO, 
	seccion.seccion_nombre, 
	CONCAT_WS(' - ',Grado,seccion_nombre) AS GRADO, 
	empresa.emp_logo
FROM
	horas_aula
	INNER JOIN
	`año_escolar`
	ON 
		horas_aula.`id_año_academico` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	aulas
	ON 
		horas_aula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	horarios
	ON 
		horas_aula.id_hora = horarios.id_hora_aula
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id,
	empresa
WHERE
	aulas.Id_aula = '$codigo'";

$stmt_pagos = $mysqli->prepare($query_pagos);

$stmt_pagos->execute();
$resultado_pagos = $stmt_pagos->get_result();

// Consulta para obtener el horario del alumno
$query_horario = "SELECT
    CONCAT_WS(' - ', hora_inicio, hora_fin) AS hora,
    MAX(CASE WHEN horarios.dia = 'Lunes' THEN asignaturas.nombre_asig ELSE '' END) AS Lunes,
    MAX(CASE WHEN horarios.dia = 'Martes' THEN asignaturas.nombre_asig ELSE '' END) AS Martes,
    MAX(CASE WHEN horarios.dia = 'Miércoles' THEN asignaturas.nombre_asig ELSE '' END) AS Miercoles,
    MAX(CASE WHEN horarios.dia = 'Jueves' THEN asignaturas.nombre_asig ELSE '' END) AS Jueves,
    MAX(CASE WHEN horarios.dia = 'Viernes' THEN asignaturas.nombre_asig ELSE '' END) AS Viernes
FROM
    horarios
INNER JOIN
    horas_aula ON horarios.id_hora_aula = horas_aula.id_hora
INNER JOIN
    detalle_asignatura_docente ON horarios.id_detalle_asig_docente = detalle_asignatura_docente.Id_detalle_asig_docente
INNER JOIN
    asignaturas ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
INNER JOIN
    aulas ON horas_aula.id_aula = aulas.Id_aula
INNER JOIN
    año_escolar ON horas_aula.id_año_academico = año_escolar.Id_año_escolar
WHERE
    horas_aula.id_aula = '$codigo'
GROUP BY
    horas_aula.hora_inicio, horas_aula.hora_fin
ORDER BY
    horas_aula.hora_inicio";

$stmt_horario = $mysqli->prepare($query_horario);
$stmt_horario->execute();
$resultado_horario = $stmt_horario->get_result();

// Generar HTML para el PDF
$html = '';

if ($row1 = $resultado_pagos->fetch_assoc()) {
    $html .= '
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 150px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
    <div class="header">
        <img src="../../../' . $row1['emp_logo'] . '" alt="Logo">
        <h2><u>HORARIOS POR AULA</u></h2>
    </div>
    <h3>Datos de Horaio</h3>
    <table>
        <tr><th>Aula o Grado:</th><td>' . $row1['Grado'] . '</td></tr>
        <tr><th>Sección:</th><td>' . $row1['seccion_nombre'] . '</td></tr>
        <tr><th>Nivel Académico:</th><td>' . $row1['Nivel_academico'] . '</td></tr>
        <tr><th>Año académico:</th><td>' . $row1['año_escolar'] . '</td></tr>


    </table>';

 
}

// Agregar el horario del alumno
$html .= '<h3>Horario</h3>
<table>
    <tr>
        <th>Hora</th>
        <th>Lunes</th>
        <th>Martes</th>
        <th>Miércoles</th>
        <th>Jueves</th>
        <th>Viernes</th>
    </tr>';

while ($row_horario = $resultado_horario->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . $row_horario['hora'] . '</td>
        <td>' . $row_horario['Lunes'] . '</td>
        <td>' . $row_horario['Martes'] . '</td>
        <td>' . $row_horario['Miercoles'] . '</td>
        <td>' . $row_horario['Jueves'] . '</td>
        <td>' . $row_horario['Viernes'] . '</td>
    </tr>';
}

$html .= '</table>';

// Limpiar el buffer de salida antes de crear el PDF
ob_end_clean();

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9,
    ]);

    $mpdf->SetTitle('HORARIO');
    $mpdf->WriteHTML($html);
    $mpdf->Output('Horario_aula.pdf', 'I'); // Salida en el navegador
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
?>
