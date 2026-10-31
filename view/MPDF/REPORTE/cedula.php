<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
// Zona horaria: la fija ../conexion.php según la configuración regional

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
$codigo = (int) ($_GET['codigo'] ?? 0);

// Consulta principal para obtener datos de matrícula (sin filtro de concepto)
$query_matricula = "SELECT
	matricula.id_matricula,
	matricula.id_alumno, 
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat, 
	CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante, 
	empresa.emp_razon, 
	empresa.emp_logo, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	seccion.seccion_nombre, 
	CONCAT_WS(' - ', aulas.Grado, seccion.seccion_nombre) AS grado, 
	`año_escolar`.`año_escolar`, 
	empresa.emp_email, 
	empresa.emp_telefono, 
	empresa.emp_direccion, 
	matricula.procedencia_colegio, 
	matricula.provincia, 
	matricula.departamento, 
	matricula.usu_id, 
	matricula.created_at, 
	matricula.updated_at, 
	padres.Dni_papa, 
	padres.Datos_papa, 
	padres.Celular_papa, 
	padres.Dni_mama, 
	padres.Datos_mama, 
	padres.Celular_mama
FROM
	matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
	-- los datos del colegio no dependen de que el estudiante tenga usuario (antes, sin usuario no salía la cédula)
	CROSS JOIN
	(SELECT * FROM empresa ORDER BY empresa_id LIMIT 1) empresa
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
	padres
	ON 
		alumnos.Id_alumno = padres.id_alu
WHERE
	matricula.id_matricula = '$codigo'";

$stmt_matricula = $mysqli->prepare($query_matricula);
$stmt_matricula->execute();
$resultado_matricula = $stmt_matricula->get_result();

// Consulta separada para obtener los pagos
$query_pagos = "SELECT
	pago_pensiones.id_pago_pension, 
	pago_pensiones.concepto, 
	pago_pensiones.fecha_pago, 
	pago_pensiones.sub_total
FROM
	pago_pensiones
WHERE
	pago_pensiones.id_matri = '$codigo' AND
	pago_pensiones.concepto IN ('ADMISION','ALUMNO NUEVO','MATRICULA') AND pago_pensiones.estado = 'VALIDO'
ORDER BY
	pago_pensiones.fecha_pago ASC";

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
    horas_aula.id_aula = (SELECT id_aula FROM matricula WHERE id_matricula = '$codigo' )
GROUP BY
    horas_aula.hora_inicio, horas_aula.hora_fin
ORDER BY
    horas_aula.hora_inicio";

$stmt_horario = $mysqli->prepare($query_horario);
$stmt_horario->execute();
$resultado_horario = $stmt_horario->get_result();

// Generar HTML para el PDF
$html = '';

if ($row_matricula = $resultado_matricula->fetch_assoc()) {
    $html .= pdf_estilos(10) . '
    <style>
        table { margin-bottom: 14px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background-color: ' . pdf_identidad()['claro'] . '; color: ' . pdf_identidad()['color'] . '; width: 32%; }
        .cab td { border: none; }
        h3 { color: ' . pdf_identidad()['color'] . '; font-size: 12px; margin: 10px 0 4px; }
        .no-data { text-align: center; font-style: italic; color: #666; }
        .recreo { background-color: ' . pdf_identidad()['claro'] . '; font-style: italic; }
    </style>
    ' . pdf_cabecera('CÉDULA DE MATRÍCULA', 'Año escolar ' . $row_matricula['año_escolar']) . '
    <h3>Datos de la matrícula</h3>
    <table>
        <tr><th>' . nombre_documento() . ':</th><td>' . $row_matricula['alum_dni'] . '</td></tr>
        <tr><th>Estudiante:</th><td>' . pdf_esc(pdf_titulo($row_matricula['Estudiante'])) . '</td></tr>
        <tr><th>Nivel académico:</th><td>' . pdf_esc(pdf_titulo($row_matricula['Nivel_academico'])) . '</td></tr>
        <tr><th>Grado y sección:</th><td>' . pdf_esc(pdf_titulo(str_replace(' - UNICO', '', $row_matricula['grado']))) . '</td></tr>
        <tr><th>Año escolar:</th><td>' . $row_matricula['año_escolar'] . '</td></tr>
        <tr><th>Colegio de procedencia:</th><td>' . pdf_esc(pdf_titulo($row_matricula['procedencia_colegio'])) . '</td></tr>
        <tr><th>Provincia:</th><td>' . $row_matricula['provincia'] . '</td></tr>
        <tr><th>Departamento:</th><td>' . $row_matricula['departamento'] . '</td></tr>
    </table>
    <h3>Datos de los padres</h3>
    <table>
        <tr><th>' . nombre_documento() . ' Papá:</th><td>' . $row_matricula['Dni_papa'] . '</td></tr>
        <tr><th>Padre:</th><td>' . pdf_esc(pdf_titulo($row_matricula['Datos_papa'])) . '</td></tr>
        <tr><th>Celular Papá:</th><td>' . $row_matricula['Celular_papa'] . '</td></tr>
        <tr><th>' . nombre_documento() . ' Mamá:</th><td>' . $row_matricula['Dni_mama'] . '</td></tr>
        <tr><th>Madre:</th><td>' . pdf_esc(pdf_titulo($row_matricula['Datos_mama'])) . '</td></tr>
        <tr><th>Celular Mamá:</th><td>' . $row_matricula['Celular_mama'] . '</td></tr>
    </table>';

    // Sección de pagos
    $html .= '<h3>Pagos de la matrícula</h3>
    <table>
        <tr>
            <th>Concepto</th>
            <th>Fecha de Pago</th>
            <th>Subtotal</th>
        </tr>';

    $total = 0;
    $hay_pagos = false;

    while ($row_pago = $resultado_pagos->fetch_assoc()) {
        $hay_pagos = true;
        $fecha_pago = new DateTime($row_pago['fecha_pago'], new DateTimeZone(config_regional()['zona_horaria']));
        $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, config_regional()['zona_horaria'], IntlDateFormatter::GREGORIAN, 'd \'de\' MMMM \'de\' y');
        $fecha_formateada = $formatter->format($fecha_pago);

        $html .= '
        <tr>
            <td>' . pdf_esc(pdf_titulo($row_pago['concepto'])) . '</td>
            <td>' . $fecha_formateada . '</td>
            <td>' . formato_moneda($row_pago['sub_total']) . '</td>
        </tr>';

        $total += $row_pago['sub_total'];
    }

    if (!$hay_pagos) {
        $html .= '
        <tr>
            <td colspan="3" class="no-data">No se registraron pagos de matrícula</td>
        </tr>';
    } else {
        $html .= '
        <tr>
            <td colspan="2" style="text-align: right;"><b>Total:</b></td>
            <td><b>' . formato_moneda($total) . '</b></td>
        </tr>';
    }

    $html .= '</table>';
} else {
    $html .= '<p style="text-align: center; color: red;">No se encontró información de la matrícula.</p>';
}

// Función para estilo de asignatura
function estiloAsignatura($asignatura) {
    return ($asignatura == 'RECREO') ? 'font-style: italic; background-color: ' . pdf_identidad()['claro'] . ';' : '';
}

// Agregar el horario del alumno
$html .= '<h3>Horario</h3>
<table style="margin: 0 auto; text-align: center;">
    <tr style="color:black;margin: 0 auto; text-align: center;">
        <th style="color:black;margin: 0 auto; text-align: center;">Hora</th>
        <th style="color:black;margin: 0 auto; text-align: center;">Lunes</th>
        <th style="color:black;margin: 0 auto; text-align: center;">Martes</th>
        <th style="color:black;margin: 0 auto; text-align: center;">Miércoles</th>
        <th style="color:black;margin: 0 auto; text-align: center;">Jueves</th>
        <th style="color:black;margin: 0 auto; text-align: center;">Viernes</th>
    </tr>';

$hay_horario = false;
while ($row_horario = $resultado_horario->fetch_assoc()) {
    $hay_horario = true;
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

if (!$hay_horario) {
    $html .= '
    <tr>
        <td colspan="6" class="no-data">No se encontró horario asignado</td>
    </tr>';
}

$html .= '</table>';

// Limpiar el buffer de salida antes de crear el PDF
ob_end_clean();

try {
    $mpdf = pdf_nuevo('A4');

    $mpdf->SetTitle('Cédula de matricula');
    $mpdf->WriteHTML($html);
    $mpdf->Output('Cedula_matricula.pdf', 'I');
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
?>