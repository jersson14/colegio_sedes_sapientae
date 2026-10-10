<?php
// Fase 0.2: los reportes también exigen sesión y rol (antes se abrían por URL sin login).
require_once __DIR__ . '/../../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ESTUDIANTE', 'AUXILIAR');
require_once __DIR__ . '/../../../core/pertenencia.php';
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../../../model/model_conexion.php';

use App\Reportes\Datos;
use App\Reportes\Pdf;

$datos = new Datos((new conexionBD())->conexionPDO());
$codigo = (string) ($_GET['codigo'] ?? '');
// IDOR: codigo = aula; el estudiante solo ve el horario de su aula.
exigir_aula_propia($codigo);

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
	aulas.Id_aula = ?";

$filas_aula = $datos->filas($query_pagos, [$codigo]);

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
    horas_aula.id_aula = ?
GROUP BY
    horas_aula.hora_inicio, horas_aula.hora_fin
ORDER BY
    horas_aula.hora_inicio;
";

$filas_horario = $datos->filas($query_horario, [$codigo]);

// Generar HTML para el PDF
$html = '';

if ($row1 = $filas_aula[0] ?? null) {
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
        <img src="' . Pdf::imagen((string) $row1['emp_logo']) . '" alt="Logo">
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
// Definir la función para determinar el estilo de la celda
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

foreach ($filas_horario as $row_horario) {
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

Pdf::enviar($html, Pdf::A4, 'Horario_aula.pdf', titulo: 'HORARIO');
?>
