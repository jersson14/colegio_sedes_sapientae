<?php
require_once __DIR__ . '/../../../core/guard.php';
exigir_rol('ADMINISTRADOR');
/*
 * Fase 5.8: certificado modular (con modulo=) o constancia de egreso (sin modulo, todo el programa) de un
 * instituto. Solo si de verdad está aprobado: en otro caso, 422 con el motivo.
 * El título técnico oficial lo regula el MINEDU (formato y registro propios): no se emite aquí.
 */
require_once __DIR__ . '/../../../model/model_conexion.php';

use App\Institucion\Evaluacion\RecordAcademico;
use App\Reportes\Datos;
use App\Reportes\Fecha;
use App\Reportes\Pdf;

$pdo = (new conexionBD())->conexionPDO();
$datos = new Datos($pdo);
$alumno = (int) ($_GET['alumno'] ?? 0);
$programa = (int) ($_GET['programa'] ?? 0);
$modulo = isset($_GET['modulo']) ? (int) $_GET['modulo'] : null;

$encabezado = $datos->fila(
    "SELECT a.alum_dni, CONCAT_WS(' ', a.alum_nombre, a.alum_apepat, a.alum_apemat) AS alumno, p.nombre AS programa,
            (SELECT emp_razon FROM empresa ORDER BY empresa_id LIMIT 1) AS emp_razon,
            (SELECT emp_direccion FROM empresa ORDER BY empresa_id LIMIT 1) AS emp_direccion,
            (SELECT emp_logo FROM empresa ORDER BY empresa_id LIMIT 1) AS emp_logo
       FROM alumnos a, programas_estudio p WHERE a.Id_alumno = ? AND p.id_programa = ?",
    [$alumno, $programa]
);
if ($encabezado === null) {
    Pdf::sinDatos();
}
$detalle = (new RecordAcademico($pdo))->detalle($alumno, $programa);
$elegido = null;
foreach ($detalle['modulos'] as $m) {
    if ($m['id_modulo'] === $modulo) {
        $elegido = $m;
    }
}
$aprobado = $modulo === null ? $detalle['completo'] : ($elegido['completo'] ?? false);
if (!$aprobado) {
    responder_error(422, $modulo === null
        ? 'El estudiante aún no ha aprobado todas las unidades del programa.'
        : 'El estudiante aún no ha aprobado todas las unidades de ese módulo.');
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$unidades = array_filter($detalle['unidades'], static fn (array $u): bool => $u['estado'] === 'APROBADO' && ($modulo === null || (int) $u['id_modulo'] === $modulo));
$creditos = array_sum(array_map(static fn (array $u): float => (float) $u['creditos'], $unidades));
$lista = implode('', array_map(static fn (array $u): string => '<tr><td>' . $e($u['codigo']) . '</td><td>' . $e($u['nombre']) . '</td><td align="center">'
    . $e($u['creditos']) . '</td><td align="center">' . $e($u['nota_recuperacion'] ?? $u['nota_final']) . '</td></tr>', $unidades));
$titulo = $modulo === null ? 'CONSTANCIA DE EGRESO' : 'CERTIFICADO MODULAR';
$que = $modulo === null
    ? 'ha aprobado todas las unidades didácticas del programa de estudios <b>' . $e($encabezado['programa']) . '</b>'
    : 'ha aprobado el módulo formativo <b>' . $e($elegido['nombre'] ?? '') . '</b> del programa de estudios <b>' . $e($encabezado['programa']) . '</b>';

$html = '<style>body{font-family:serif;font-size:12pt} h1{text-align:center;font-size:20pt;letter-spacing:2px} p{text-align:justify;line-height:1.6}
    table.datos{border-collapse:collapse;width:100%;font-size:10pt} table.datos th,table.datos td{border:1px solid #444;padding:4px} table.datos th{background:#eee}</style>
    <div style="text-align:center"><img src="' . $e(Pdf::imagen((string) $encabezado['emp_logo'])) . '" height="80"><br><b>' . $e($encabezado['emp_razon']) . '</b></div>
    <h1>' . $titulo . '</h1>
    <p>La dirección de ' . $e($encabezado['emp_razon']) . ' hace constar que <b>' . $e($encabezado['alumno']) . '</b>, identificado(a) con DNI N.º '
    . $e($encabezado['alum_dni']) . ', ' . $que . ', con un total de <b>' . $e(number_format($creditos, 1)) . ' créditos</b>, según el detalle:</p>
    <table class="datos"><thead><tr><th>Código</th><th>Unidad didáctica</th><th>Créditos</th><th>Nota</th></tr></thead><tbody>' . $lista . '</tbody></table>
    <p>Se expide a solicitud del interesado, ' . $e(trim((string) $encabezado['emp_direccion']) !== '' ? $encabezado['emp_direccion'] . ', ' : '')
    . $e(Fecha::larga(date('Y-m-d'))) . '.</p>
    <br><br><br><table width="100%"><tr><td align="center" width="50%">______________________<br>Dirección</td>
    <td align="center">______________________<br>Secretaría académica</td></tr></table>';

Pdf::enviar($html, Pdf::A4, strtolower(str_replace(' ', '-', $titulo)) . '-' . $encabezado['alum_dni'] . '.pdf', null, $titulo);
