<?php
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$codigo = $mysqli->real_escape_string($_GET['codigo']);

// Obtener el código de matrícula desde la URL

// Consulta para obtener los datos de matrícula y pagos
$query_pagos = "SELECT
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
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	padres
	ON 
		alumnos.Id_alumno = padres.id_alu
WHERE
	matricula.id_matricula = '$codigo' AND
	concepto IN ('ADMISION','ALUMNO NUEVO','MATRICULA')";

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
        <img src="../../../'. $row1['emp_logo'].'" alt="Logo">
        <h2><u>CÉDULA DE MATRICULA</u></h2>
    </div>
    <h3>Datos de Matricula</h3>
    <table>
        <tr><th>DNI:</th><td>' . $row1['alum_dni'] . '</td></tr>
        <tr><th>Estudiante:</th><td>' . $row1['Estudiante'] . '</td></tr>
        <tr><th>Nivel Académico:</th><td>' . $row1['Nivel_academico'] . '</td></tr>
        <tr><th>Grado - Sección:</th><td>' . $row1['grado'] . '</td></tr>
        <tr><th>Año Escolar (Matriculado):</th><td>' . $row1['año_escolar'] . '</td></tr>
        <tr><th>Procedencia de colegio:</th><td>' . $row1['procedencia_colegio'] . '</td></tr>
        <tr><th>Provincia:</th><td>' . $row1['provincia'] . '</td></tr>
        <tr><th>Departamento:</th><td>' . $row1['departamento'] . '</td></tr>

    </table>
    <h3>Datos de los Padres</h3>
    <table>
        <tr><th>DNI Papá:</th><td>' . $row1['Dni_papa'] . '</td></tr>
        <tr><th>Datos Papá:</th><td>' . $row1['Datos_papa'] . '</td></tr>
        <tr><th>Celular Papá:</th><td>' . $row1['Celular_papa'] . '</td></tr>
        <tr><th>DNI Mamá:</th><td>' . $row1['Dni_mama'] . '</td></tr>
        <tr><th>Datos Mamá:</th><td>' . $row1['Datos_mama'] . '</td></tr>
        <tr><th>Celular Mamá:</th><td>' . $row1['Celular_mama'] . '</td></tr>
    </table>';

    $html .= '<h3>Detalle de Pagos</h3>
    <table>
        <tr>
            <th>Concepto</th>
            <th>Fecha de Pago</th>
            <th>Subtotal</th>
        </tr>';

    $total = 0;

    $stmt_pagos->execute();
    $resultado_pagos = $stmt_pagos->get_result();
    while ($row2 = $resultado_pagos->fetch_assoc()) {
        $fecha_pago = new DateTime($row2['fecha_pago'], new DateTimeZone('America/Lima'));
        $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'America/Lima', IntlDateFormatter::GREGORIAN, 'd \'de\' MMMM \'de\' y');
        $fecha_formateada = $formatter->format($fecha_pago);

        $html .= '
        <tr>
            <td>' . $row2['concepto'] . '</td>
            <td>' . $fecha_formateada . '</td>
            <td>S/. ' . number_format($row2['sub_total'], 2) . '</td>
        </tr>';

        $total += $row2['sub_total'];
    }

    $html .= '
        <tr>
            <td colspan="2" style="text-align: right;"><b>Total:</b></td>
            <td><b>S/. ' . number_format($total, 2) . '</b></td>
        </tr>
    </table>';
}

// Agregar el horario del alumno
function estiloAsignatura($asignatura) {
    return ($asignatura == 'RECREO') ? 'background-color: yellow;' : '';
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

    $mpdf->SetTitle('Cédula de matricula');
    $mpdf->WriteHTML($html);
    $mpdf->Output('Cedula_matricula.pdf', 'I'); // Salida en el navegador
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
?>
