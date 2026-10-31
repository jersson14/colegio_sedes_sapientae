<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
// Zona horaria: la fija ../conexion.php según la configuración regional

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
$codigo = $mysqli->real_escape_string($_GET['codigo']);
reporte_exigir_aula_propia($mysqli, $codigo);

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
    CONCAT_WS(' - ', horas_aula.hora_inicio, horas_aula.hora_fin) AS hora,
    MAX(CASE WHEN horarios.dia = 'Lunes' THEN 
        CASE 
            WHEN asignaturas.nombre_asig = 'RECREO' THEN asignaturas.nombre_asig 
            ELSE CONCAT_WS(' - ', asignaturas.nombre_asig, CONCAT('(', docentes.docente_nombre, ' ', docentes.docente_apelli, ')')) 
        END 
    ELSE '' END) AS Lunes,
    MAX(CASE WHEN horarios.dia = 'Martes' THEN 
        CASE 
            WHEN asignaturas.nombre_asig = 'RECREO' THEN asignaturas.nombre_asig 
            ELSE CONCAT_WS(' - ', asignaturas.nombre_asig, CONCAT('(', docentes.docente_nombre, ' ', docentes.docente_apelli, ')')) 
        END 
    ELSE '' END) AS Martes,
    MAX(CASE WHEN horarios.dia = 'Miércoles' THEN 
        CASE 
            WHEN asignaturas.nombre_asig = 'RECREO' THEN asignaturas.nombre_asig 
            ELSE CONCAT_WS(' - ', asignaturas.nombre_asig, CONCAT('(', docentes.docente_nombre, ' ', docentes.docente_apelli, ')')) 
        END 
    ELSE '' END) AS Miercoles,
    MAX(CASE WHEN horarios.dia = 'Jueves' THEN 
        CASE 
            WHEN asignaturas.nombre_asig = 'RECREO' THEN asignaturas.nombre_asig 
            ELSE CONCAT_WS(' - ', asignaturas.nombre_asig, CONCAT('(', docentes.docente_nombre, ' ', docentes.docente_apelli, ')')) 
        END 
    ELSE '' END) AS Jueves,
    MAX(CASE WHEN horarios.dia = 'Viernes' THEN 
        CASE 
            WHEN asignaturas.nombre_asig = 'RECREO' THEN asignaturas.nombre_asig 
            ELSE CONCAT_WS(' - ', asignaturas.nombre_asig, CONCAT('(', docentes.docente_nombre, ' ', docentes.docente_apelli, ')')) 
        END 
    ELSE '' END) AS Viernes
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
INNER JOIN
    asignatura_docente ON detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
INNER JOIN 
    docentes ON asignatura_docente.Id_docente = docentes.Id_docente
WHERE
    horas_aula.id_aula = '$codigo'
GROUP BY
    horas_aula.hora_inicio, horas_aula.hora_fin
ORDER BY
    horas_aula.hora_inicio;
";

$stmt_horario = $mysqli->prepare($query_horario);
$stmt_horario->execute();
$resultado_horario = $stmt_horario->get_result();

// Generar HTML para el PDF
$html = '';

if ($row1 = $resultado_pagos->fetch_assoc()) {
    $grado_txt = pdf_titulo($row1['Nivel_academico']) . ' · ' . pdf_titulo($row1['Grado']) . ($row1['seccion_nombre'] && $row1['seccion_nombre'] !== 'UNICO' ? ' "' . $row1['seccion_nombre'] . '"' : '');
    $html .= pdf_estilos(10) . '
    <style>
        th, td { border: 1px solid #ccc; padding: 5px; }
        th { background-color: ' . pdf_identidad()['color'] . '; color: ' . pdf_identidad()['texto_color'] . '; }
        .cab td { border: none; }
        h3 { color: ' . pdf_identidad()['color'] . '; font-size: 12px; margin: 10px 0 4px; }
    </style>
    ' . pdf_cabecera('HORARIO DE CLASES', $grado_txt . ' · ' . $row1['año_escolar']) . '
    ';

 
}

// Agregar el horario del alumno
// Definir la función para determinar el estilo de la celda
function estiloAsignatura($asignatura) {
    return ($asignatura == 'RECREO') ? 'font-style: italic; background-color: ' . pdf_identidad()['claro'] . ';' : '';
}

// Agregar el horario del alumno
$html .= '<table style="text-align: center;">
    <tr>
        <th class="c">Hora</th>
        <th class="c">Lunes</th>
        <th class="c">Martes</th>
        <th class="c">Miércoles</th>
        <th class="c">Jueves</th>
        <th class="c">Viernes</th>
    </tr>';

while ($row_horario = $resultado_horario->fetch_assoc()) {
    $html .= '
    <tr>
        <td style="font-size: 11px;margin: 0 auto; text-align: center;">' . $row_horario['hora'] . '</td>
        <td style="font-size: 11px;margin: 0 auto; text-align: center;' . estiloAsignatura($row_horario['Lunes']) . '">' . $row_horario['Lunes'] . '</td>
        <td style="font-size: 11px;margin: 0 auto; text-align: center;' . estiloAsignatura($row_horario['Martes']) . '">' . $row_horario['Martes'] . '</td>
        <td style="font-size: 11px;margin: 0 auto; text-align: center;' . estiloAsignatura($row_horario['Miercoles']) . '">' . $row_horario['Miercoles'] . '</td>
        <td style="font-size: 11px;margin: 0 auto; text-align: center;' . estiloAsignatura($row_horario['Jueves']) . '">' . $row_horario['Jueves'] . '</td>
        <td style="font-size: 11px;margin: 0 auto; text-align: center;' . estiloAsignatura($row_horario['Viernes']) . '">' . $row_horario['Viernes'] . '</td>
    </tr>';
}

$html .= '</table>';

// Limpiar el buffer de salida antes de crear el PDF
ob_end_clean();

try {
    $mpdf = pdf_nuevo('A4');
    $mpdf->SetTitle('Horario de clases');
    $mpdf->WriteHTML($html);
    $mpdf->Output('Horario_aula.pdf', 'I'); // Salida en el navegador
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
?>
