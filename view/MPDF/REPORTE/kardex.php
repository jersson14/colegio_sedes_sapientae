<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
ob_start();
setlocale(LC_TIME, 'es_ES.UTF-8');
// Zona horaria: la fija ../conexion.php según la configuración regional

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
$codigo = (int) ($_GET['codigo'] ?? 0);
reporte_exigir_matricula_propia($mysqli, $codigo);

$query = "SELECT
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
    CONCAT_WS(' - ',aulas.Grado,seccion.seccion_nombre) AS grado, 
    año_escolar.año_escolar, 
    empresa.emp_email, 
    empresa.emp_telefono, 
    empresa.emp_direccion
FROM
    pago_pensiones
    LEFT JOIN pensiones ON pensiones.id_pensiones = pago_pensiones.id_pension
    INNER JOIN matricula ON pago_pensiones.id_matri = matricula.id_matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    -- los datos del colegio no dependen de que el estudiante tenga usuario (antes, sin usuario el kardex salía vacío)
    CROSS JOIN (SELECT * FROM empresa ORDER BY empresa_id LIMIT 1) empresa
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN año_escolar ON matricula.id_año = año_escolar.Id_año_escolar
WHERE
    matricula.id_matricula = '$codigo' AND pago_pensiones.estado = 'VALIDO'";

$stmt = $mysqli->prepare($query);
$stmt->execute();
$resultado = $stmt->get_result();

$html = '';

if ($row1 = $resultado->fetch_assoc()) {
    $html .= pdf_estilos(10) . '
    <style>
        table { margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background-color: ' . pdf_identidad()['claro'] . '; color: ' . pdf_identidad()['color'] . '; }
        .cab td { border: none; }
        h3 { color: ' . pdf_identidad()['color'] . '; font-size: 12px; margin: 10px 0 4px; }
    </style>
    ' . pdf_cabecera('KARDEX DE PAGOS', 'Año escolar ' . $row1['año_escolar']) . '
    <table>
        <tr><th>' . nombre_documento() . ':</th><td>'.$row1['alum_dni'].'</td></tr>
        <tr><th>Estudiante:</th><td>' . pdf_esc(pdf_titulo($row1['Estudiante'])) . '</td></tr>
        <tr><th>Nivel académico:</th><td>' . pdf_esc(pdf_titulo($row1['Nivel_academico'])) . '</td></tr>
        <tr><th>Grado y sección:</th><td>' . pdf_esc(pdf_titulo(str_replace(' - UNICO', '', $row1['grado']))) . '</td></tr>
    </table>
    <h3>Pagos</h3>
    <table>
        <tr>
            <th>Concepto</th>
            <th>Mes</th>
            <th>Fecha de Pago</th>
            <th>Subtotal</th>
        </tr>';

    // Inicializar la suma total
    $total = 0;

    // Repetir la ejecución para obtener los pagos
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    while ($row2 = $resultado->fetch_assoc()) {
        // Crear el objeto DateTime con la zona horaria configurada
        $fecha_pago = new DateTime($row2['fecha_pago'], new DateTimeZone(config_regional()['zona_horaria']));
    
        // Configurar el locale para español
        $locale = 'es_ES';
        
        // Crear un formateador de fechas
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE, config_regional()['zona_horaria'], IntlDateFormatter::GREGORIAN, 'd \'de\' MMMM \'de\' y');
    
        // Formatear la fecha en español
        $fecha_formateada = $formatter->format($fecha_pago);
    
        $html .= '
        <tr>
            <td>' . pdf_esc(pdf_titulo($row2['concepto'])) . '</td>
            <td>' . pdf_esc(pdf_titulo($row2['mes'])) . '</td>
            <td>'.$fecha_formateada.'</td>
            <td>' . formato_moneda($row2['sub_total']) . '</td>
        </tr>';
    
        // Sumar el subtotal al total
        $total += $row2['sub_total'];
    }
    
    
    // Agregar una fila para el total
    $html .= '
        <tr>
            <td colspan="3" style="text-align: right;"><b>Total:</b></td>
            <td><b>' . formato_moneda($total) . '</b></td>
        </tr>
    </table>
    </div>';
}

// Limpiar el buffer de salida antes de crear el PDF
ob_end_clean();

try {
    $mpdf = pdf_nuevo('A4');
    
    // Definir el pie de página
    $mpdf->SetHTMLFooter('<p style="text-align: center; font-size: 8.5px; color: #666;">Pagos registrados en el sistema. Los comprobantes originales se entregan en el colegio.</p>');

    $mpdf->WriteHTML($html);
    $mpdf->Output('Kardex_de_Pagos.pdf', 'I');
} catch (\Mpdf\MpdfException $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}
