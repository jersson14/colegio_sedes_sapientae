<?php
// Fase 5.8: récord académico de un alumno en un programa de estudios (institutos).
require_once __DIR__ . '/../../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../../model/model_conexion.php';
require_once __DIR__ . '/../../../core/institucion.php';

use App\Institucion\Evaluacion\RecordAcademico;
use App\Reportes\Datos;
use App\Reportes\Fecha;
use App\Reportes\Pdf;

$pdo = (new conexionBD())->conexionPDO();
$datos = new Datos($pdo);
$alumno = (int) ($_GET['alumno'] ?? 0);
$programa = (int) ($_GET['programa'] ?? 0);

$encabezado = $datos->fila(
    "SELECT a.alum_dni, CONCAT_WS(' ', a.alum_apepat, a.alum_apemat, a.alum_nombre) AS alumno, p.codigo, p.nombre AS programa,
            (SELECT emp_razon FROM empresa ORDER BY empresa_id LIMIT 1) AS emp_razon,
            (SELECT emp_cod FROM empresa ORDER BY empresa_id LIMIT 1) AS emp_cod,
            (SELECT emp_logo FROM empresa ORDER BY empresa_id LIMIT 1) AS emp_logo
       FROM alumnos a, programas_estudio p WHERE a.Id_alumno = ? AND p.id_programa = ?",
    [$alumno, $programa]
);
if ($encabezado === null) {
    Pdf::sinDatos();
}
$configuracion = institucion_config();
$record = new RecordAcademico($pdo);
$resumen = $record->de($alumno, $programa, $configuracion);
$detalle = $record->detalle($alumno, $programa);
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$romanos = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];

$filas = '';
foreach ($detalle['unidades'] as $u) {
    $nota = $u['nota_recuperacion'] ?? $u['nota_final'];
    $filas .= '<tr><td align="center">' . $romanos[(int) $u['periodo_academico']] . '</td><td>' . $e($u['codigo']) . '</td><td>' . $e($u['nombre'])
        . '</td><td align="center">' . $e($u['creditos']) . '</td><td align="center">' . ($nota !== null ? $e($nota) . ($u['nota_recuperacion'] !== null ? ' (R)' : '') : '—')
        . '</td><td>' . $e($u['estado'] ?? 'NO CURSADA') . '</td><td>' . $e($u['periodo'] ?? '') . '</td></tr>';
}
$html = '<style>body{font-family:sans-serif;font-size:10pt} table.datos{border-collapse:collapse;width:100%} table.datos th,table.datos td{border:1px solid #444;padding:4px}
    table.datos th{background:#e8e8e8} h1{font-size:15pt;text-align:center;margin:4px 0} .sub{text-align:center;margin:0}</style>
    <table width="100%"><tr><td width="20%"><img src="' . $e(Pdf::imagen((string) $encabezado['emp_logo'])) . '" height="60"></td>
    <td><h1>RÉCORD ACADÉMICO</h1><p class="sub"><b>' . $e($encabezado['emp_razon']) . '</b>' . ($encabezado['emp_cod'] !== '' ? ' · Código modular ' . $e($encabezado['emp_cod']) : '') . '</p></td></tr></table>
    <p><b>Estudiante:</b> ' . $e($encabezado['alumno']) . ' &nbsp; <b>DNI:</b> ' . $e($encabezado['alum_dni']) . '<br>
    <b>Programa de estudios:</b> ' . $e($encabezado['codigo']) . ' · ' . $e($encabezado['programa']) . '</p>
    <table class="datos"><thead><tr><th>Periodo</th><th>Código</th><th>Unidad didáctica</th><th>Créditos</th><th>Nota</th><th>Situación</th><th>Cursada en</th></tr></thead>
    <tbody>' . $filas . '</tbody></table>
    <p><b>' . $e($resumen['estrategia']) . ':</b> ' . ($resumen['promedio'] !== null ? $e(number_format((float) $resumen['promedio'], 2)) : '—')
    . ' &nbsp; <b>Créditos aprobados:</b> ' . $e($resumen['creditos_aprobados']) . ' &nbsp; <b>Unidades aprobadas:</b> ' . $e($resumen['unidades_aprobadas'])
    . ' &nbsp; <b>Nota mínima aprobatoria:</b> ' . $configuracion->notaMinima() . '</p>'
    . ($detalle['completo'] ? '<p><b>Ha aprobado todas las unidades didácticas del programa de estudios.</b></p>' : '')
    . '<p style="font-size:8pt">(R) nota obtenida en la evaluación de recuperación. Documento informativo emitido el ' . $e(Fecha::larga(date('Y-m-d'))) . '.</p>';

Pdf::enviar($html, Pdf::A4, 'record-academico-' . $encabezado['alum_dni'] . '.pdf', null, 'Récord académico');
