<?php
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';

// ID de matrícula, puedes cambiarlo a un parámetro dinámico si lo necesitas
$id_matricula = $mysqli->real_escape_string($_GET['id_matricula']);

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
    año_escolar.año_escolar, 
    empresa.emp_razon, 
    empresa.emp_cod, 
    empresa.emp_email, 
    empresa.emp_telefono, 
    empresa.emp_direccion
FROM
    matricula
INNER JOIN
    alumnos ON matricula.id_alumno = alumnos.Id_alumno
INNER JOIN
    usuario ON matricula.usu_id = usuario.usu_id
INNER JOIN
    empresa ON usuario.empresa_id = empresa.empresa_id
INNER JOIN
    aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN
    nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN
    seccion ON aulas.id_seccion = seccion.seccion_id
INNER JOIN
    año_escolar ON matricula.id_año = año_escolar.Id_año_escolar
WHERE
    matricula.id_matricula = '$id_matricula'";

$stmt_datos = $mysqli->prepare($query_datos);
$stmt_datos->execute();
$resultado_datos = $stmt_datos->get_result();
$datos_estudiante = $resultado_datos->fetch_assoc();

// Consulta para obtener las notas y competencias
$query_notas = "    SELECT 
    a.nombre_asig, 
    c.competencias,
    COALESCE(MAX(CASE WHEN p.periodos = 'I BIMESTRE' THEN n.nota END), 0) AS Bimestre_1,
    COALESCE(MAX(CASE WHEN p.periodos = 'II BIMESTRE' THEN n.nota END), 0) AS Bimestre_2,
    COALESCE(MAX(CASE WHEN p.periodos = 'III BIMESTRE' THEN n.nota END), 0) AS Bimestre_3,
    COALESCE(MAX(CASE WHEN p.periodos = 'IV BIMESTRE' THEN n.nota END), 0) AS Bimestre_4,
    n.conclusiones AS conclusiones
FROM
    asignaturas a
INNER JOIN
    detalle_asignatura_docente dad ON a.Id_asignatura = dad.Id_asignatura
INNER JOIN
    criterios c ON dad.Id_detalle_asig_docente = c.id_detalle_asignatura
LEFT JOIN
    notas n ON c.id_criterio = n.id_criterio
LEFT JOIN
    periodos p ON n.id_bimestre = p.id_periodo
WHERE
    n.id_matricula = '$id_matricula'
GROUP BY
    a.nombre_asig, 
    c.competencias
HAVING
    MAX(n.nota) IS NOT NULL
ORDER BY
    a.nombre_asig ASC,
    c.competencias ASC";

$stmt_notas = $mysqli->prepare($query_notas);
$stmt_notas->execute();
$resultado_notas = $stmt_notas->get_result();

// Consulta para obtener las notas de los padres
$query_notas_padres = "SELECT
    n.criterio,
    COALESCE(MAX(CASE WHEN p.periodos = 'I BIMESTRE' THEN n.nota END), 0) AS Bimestre_1,
    COALESCE(MAX(CASE WHEN p.periodos = 'II BIMESTRE' THEN n.nota END), 0) AS Bimestre_2,
    COALESCE(MAX(CASE WHEN p.periodos = 'III BIMESTRE' THEN n.nota END), 0) AS Bimestre_3,
    COALESCE(MAX(CASE WHEN p.periodos = 'IV BIMESTRE' THEN n.nota END), 0) AS Bimestre_4
FROM
    notas_padre n
INNER JOIN
    periodos p ON n.id_bimestre = p.id_periodo
WHERE
    n.id_matricula ='$id_matricula'
GROUP BY
    n.criterio
ORDER BY
    n.criterio";

$stmt_notas_padres = $mysqli->prepare($query_notas_padres);
$stmt_notas_padres->execute();
$resultado_notas_padres = $stmt_notas_padres->get_result();

// Consulta para obtener la asistencia
$query_asistencia = "SELECT
    periodos.periodos, 
    periodos.fecha_inicio, 
    periodos.fecha_fin,
    SUM(CASE WHEN asistencia.estado = 'PRESENTE' THEN 1 ELSE 0 END) AS total_asistencia_presente,
    SUM(CASE WHEN asistencia.estado = 'TARDE' THEN 1 ELSE 0 END) AS total_asistencia_tarde,
    SUM(CASE WHEN asistencia.estado = 'AUSENTE' THEN 1 ELSE 0 END) AS total_asistencia_ausente,
    SUM(CASE WHEN asistencia.estado = 'JUSTIFICADO' THEN 1 ELSE 0 END) AS total_asistencia_justificado
FROM
    periodos
LEFT JOIN
    asistencia ON asistencia.fecha BETWEEN periodos.fecha_inicio AND periodos.fecha_fin
    AND asistencia.id_matricula = '$id_matricula' 
GROUP BY
    periodos.periodos, 
    periodos.fecha_inicio, 
    periodos.fecha_fin
ORDER BY
    periodos.fecha_inicio";

$stmt_asistencia = $mysqli->prepare($query_asistencia);
$stmt_asistencia->execute();
$resultado_asistencia = $stmt_asistencia->get_result();
$asistencia = $resultado_asistencia->fetch_assoc();

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
    <img src="../../../' . $datos_estudiante['emp_logo'] . '" alt="Logo">
    <h3><u>INFORME DE PROGRESO DEL APRENDIZAJE DEL ESTUDIANTE ' . $datos_estudiante['año_escolar'] . '</u></h3>
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
        <th style="text-align:center;">Área</th>
        <th style="text-align:center;">Competencia</th>
        <th style="text-align:center;">I</th>
        <th style="text-align:center;">II</th>
        <th style="text-align:center;">III</th>
        <th style="text-align:center;">IV</th>
        <th style="text-align:center;">Conclusión Descriptiva de la Competencia</th>
    </tr>';

$asignaturas = []; // Array para almacenar asignaturas

// Construcción de las filas de notas
while ($fila = $resultado_notas->fetch_assoc()) {
    $asignaturas[$fila['nombre_asig']][] = $fila; // Agrupar por nombre de asignatura
}

// Ahora genera las filas con asignaturas agrupadas
foreach ($asignaturas as $nombre_asig => $competencias) {
    foreach ($competencias as $index => $fila) {
        if ($index == 0) { // Si es la primera fila, muestra el nombre de la asignatura
            $html .= '
            <tr>
                <td class="curso" rowspan="' . count($competencias) . '">' . $nombre_asig . '</td>
                <td class="competencia">' . $fila['competencias'] . '</td>
                <td class="nota">' . $fila['Bimestre_1'] . '</td>
                <td class="nota">' . $fila['Bimestre_2'] . '</td>
                <td class="nota">' . $fila['Bimestre_3'] . '</td>
                <td class="nota">' . $fila['Bimestre_4'] . '</td>
                <td>' . $fila['conclusiones'] . '</td>
            </tr>';
        } else { // Si no es la primera fila, solo muestra la competencia
            $html .= '
            <tr>
                <td class="competencia">' . $fila['competencias'] . '</td>
                <td class="nota">' . $fila['Bimestre_1'] . '</td>
                <td class="nota">' . $fila['Bimestre_2'] . '</td>
                <td class="nota">' . $fila['Bimestre_3'] . '</td>
                <td class="nota">' . $fila['Bimestre_4'] . '</td>
                <td>' . $fila['conclusiones'] . '</td>
            </tr>';
        }
    }
}


$html .= '</table>';
$html .= '
<h3>Notas de Padres</h3>
<table>
    <tr>
        <th style="text-align:center;">Criterio</th>
        <th style="text-align:center;">I</th>
        <th style="text-align:center;">II</th>
        <th style="text-align:center;">III</th>
        <th style="text-align:center;">IV</th>
    </tr>';

// Construcción de las filas de notas de padres
while ($fila_notas_padres = $resultado_notas_padres->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . $fila_notas_padres['criterio'] . '</td>
        <td class="nota">' . $fila_notas_padres['Bimestre_1'] . '</td>
        <td class="nota">' . $fila_notas_padres['Bimestre_2'] . '</td>
        <td class="nota">' . $fila_notas_padres['Bimestre_3'] . '</td>
        <td class="nota">' . $fila_notas_padres['Bimestre_4'] . '</td>
    </tr>';
}

$html .= '</table>';
// Asistencia
$html .= '<h3>Registro de Asistencia</h3>
<table>
    <tr>
        <th style="text-align:center;">Periodo</th>
        <th style="text-align:center;">Fecha de Inicio</th>
        <th style="text-align:center;">Fecha de Fin</th>
        <th style="text-align:center;">Total Presentes</th>
        <th style="text-align:center;">Total Tardes</th>
        <th style="text-align:center;">Total Ausentes</th>
        <th style="text-align:center;">Total Justificados</th>
    </tr>';

// Construcción de las filas de asistencia
while ($fila_asistencia = $resultado_asistencia->fetch_assoc()) {
    
    $html .= '
    <tr>
        <td>' . $fila_asistencia['periodos'] . '</td>
        <td>' . date('d-m-Y', strtotime($fila_asistencia['fecha_inicio'])) . '</td>
        <td>' . date('d-m-Y', strtotime($fila_asistencia['fecha_fin'])) . '</td>
        <td class="nota">' . $fila_asistencia['total_asistencia_presente'] . '</td>
        <td class="nota">' . $fila_asistencia['total_asistencia_tarde'] . '</td>
        <td class="nota">' . $fila_asistencia['total_asistencia_ausente'] . '</td>
        <td class="nota">' . $fila_asistencia['total_asistencia_justificado'] . '</td>
    </tr>';
}

$html .= '</table>

<!-- Sección de firma del director -->
<div style="margin-top: 150px; text-align: center;">
    <p>______________________________________________</p>
    <p><strong>FIRMA Y SELLO DEL DIRECTOR</strong></p>
</div>';

// Generación del PDF con MPDF
$mpdf = new \Mpdf\Mpdf([
    'format' => [216, 356] // Dimensiones en milímetros para tamaño oficio
]);
$mpdf->WriteHTML($html);
$mpdf->Output('informe_progreso.pdf', 'I'); // 'D' para forzar la descarga
exit();
?>
