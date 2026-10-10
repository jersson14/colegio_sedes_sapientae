<?php
// Fase 0.2: los reportes también exigen sesión y rol (antes se abrían por URL sin login).
require_once __DIR__ . '/../../../core/guard.php';
exigir_rol('ADMINISTRADOR');
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../../../model/model_conexion.php';

use App\Reportes\Datos;
use App\Reportes\Fecha;
use App\Reportes\Pdf;

$datos = new Datos((new conexionBD())->conexionPDO());
$codigo = (string) ($_GET['codigo'] ?? '');
$fecha = (string) ($_GET['fecha'] ?? '');

// Consulta SQL principal
$query = "SELECT
    pago_pensiones.*, 
    pensiones.mes, 
    alumnos.alum_dni,
    CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
    nivel_academico.Nivel_academico,
    CONCAT_WS(' - ', aulas.Grado, seccion.seccion_nombre) AS grado,
    empresa.emp_razon, 
    empresa.emp_logo,
    empresa.emp_email,
    empresa.emp_telefono,
    empresa.emp_direccion,
    año_escolar.año_escolar
FROM
    pago_pensiones
    LEFT JOIN pensiones ON pensiones.id_pensiones = pago_pensiones.id_pension
    INNER JOIN matricula ON pago_pensiones.id_matri = matricula.id_matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    LEFT JOIN usuario ON matricula.usu_id = usuario.usu_id /* matrícula sin cuenta: igual se reporta */
    INNER JOIN empresa ON empresa.empresa_id = COALESCE(usuario.empresa_id, 1)
    INNER JOIN año_escolar ON matricula.id_año = año_escolar.Id_año_escolar
WHERE
    matricula.id_matricula = ? AND DATE(pago_pensiones.fecha_pago) = ?";

$filas = $datos->filas($query, [$codigo, $fecha]);
if ($filas === []) {
    Pdf::sinDatos();
}

$html = '';

if ($filas !== []) {
    $first_row = $filas[0]; // Obtenemos el primer registro para la información general
    
    // Guardamos la información de la empresa
    $emp_logo = $first_row['emp_logo'];
    $emp_telefono = $first_row['emp_telefono'];
    $emp_email = $first_row['emp_email'];
    $emp_direccion = $first_row['emp_direccion'];

    // Generar el HTML de la boleta
    $html = '
    <style>
        body { font-family: Arial, sans-serif; }
        .boleta { width: 100%; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { max-width: 150px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
    <div class="boleta">
        <div class="header">
            <img src="' . Pdf::imagen((string) $emp_logo) . '" class="logo">
            <h2><u>RECIBO DE PAGO</u></h2>
        </div>
        <hr>
        <p><strong>DNI:</strong> '.$first_row['alum_dni'].'</p>
        <p><strong>Estudiante:</strong> '.$first_row['Estudiante'].'</p>
        <p><strong>Nivel académico:</strong> '.$first_row['Nivel_academico'].'</p>
        <p><strong>Grado - Sección:</strong> '.$first_row['grado'].'</p>
        <p><strong>Año escolar:</strong> '.$first_row['año_escolar'].'</p>
        <hr>

        <table>
            <tr>
                <th>Concepto</th>
                <th>Mes</th>
                <th>Fecha de pago</th>
                <th>Total pagado</th>
            </tr>';

    $total = 0;
    foreach ($filas as $row) {
        // Antes strftime() sin el locale disponible: «25 de december del 2025».
        $fecha_formateada = Fecha::larga((string) $row['fecha_pago']);
        
        $html .= '
            <tr>
                <td>'.$row['concepto'].'</td>
                <td>'.$row['mes'].'</td>
                <td>'.$fecha_formateada.'</td>
                <td>S/. '.number_format($row['sub_total'], 2).'</td>
            </tr>';
        
        $total += $row['sub_total'];
    }

    $html .= '
            <tr>
                <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                <td><strong>S/. '.number_format($total, 2).'</strong></td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; text-align: center;">
            <p><strong>¡Gracias por su preferencia!</strong></p>
            <p><strong>Teléfono:</strong> '.$emp_telefono.'</p>
            <p><strong>Email:</strong> '.$emp_email.'</p>
            <p><strong>Dirección:</strong> '.$emp_direccion.'</p>
            <p><strong>Abancay-Apurímac-Perú</strong></p>
        </div>
        
        <p style="text-align: center; font-style: italic; margin-top: 20px;">
            (Esta boleta de pago puede ser reemplazada por una original en el colegio)
        </p>
    </div>';
}

// Generar el PDF
Pdf::enviar($html, ['mode' => 'UTF-8', 'format' => [130, 300]], 'boleta_de_pago.pdf');