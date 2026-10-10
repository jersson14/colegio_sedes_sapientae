<?php

/**
 * Caracterización de los reportes PDF (Fase 3, módulo reportes): graba o verifica el HTML que cada
 * reporte entrega a mPDF, con los datos de prueba y la fecha congelada.
 *
 * Uso:  COLEGIO_ENV=<prueba.env> php tools/caracterizar_reportes.php grabar|verificar
 *
 * El PDF en sí no se compara (lleva fechas de generación y compresión): el HTML es lo que decide qué
 * datos y con qué formato aparecen. Las fechas de «hoy» del lado PHP (date()) se normalizan.
 */

declare(strict_types=1);

const ARCHIVO = __DIR__ . '/../tests/Caracterizacion/reportes.json';

/** [reporte, rol, usu_id, parámetros GET] */
const CASOS = [
    ['boleta_pago.php', 'ADMINISTRADOR', '9', ['codigo' => '38', 'idpagopen' => '133']],
    ['cedula.php', 'ADMINISTRADOR', '9', ['codigo' => '34']],
    ['cedula.php', 'ADMINISTRADOR', '9', ['codigo' => '40']],
    ['horario.php', 'ADMINISTRADOR', '9', ['codigo' => '5']],
    ['horario.php', 'ADMINISTRADOR', '9', ['codigo' => '18']],
    ['kardex.php', 'ADMINISTRADOR', '9', ['codigo' => '38']],
    ['kardex.php', 'ADMINISTRADOR', '9', ['codigo' => '34']],
    ['notas_general.php', 'ADMINISTRADOR', '9', ['id_matricula' => '34']],
    ['notas_por_bimestre.php', 'ADMINISTRADOR', '9', ['id_matricula' => '34', 'id_bimestre' => '44']],
    // Periodo de otro año: hoy produce avisos de PHP y un PDF sin datos.
    ['notas_por_bimestre.php', 'ADMINISTRADOR', '9', ['id_matricula' => '34', 'id_bimestre' => '12']],
    ['pago.php', 'ADMINISTRADOR', '9', ['codigo' => '38', 'fecha' => '2025-12-25']],
    ['kardex.php', 'ESTUDIANTE', '62', ['codigo' => '40']],
    // Matrícula 31: sin cuenta de alumno (antes los reportes salían en blanco).
    ['kardex.php', 'ADMINISTRADOR', '9', ['codigo' => '31']],
    ['cedula.php', 'ADMINISTRADOR', '9', ['codigo' => '31']],
];

$modo = $argv[1] ?? '';
if (!in_array($modo, ['grabar', 'verificar'], true)) {
    fwrite(STDERR, "Uso: php tools/caracterizar_reportes.php grabar|verificar\n");
    exit(2);
}
if ((getenv('COLEGIO_ENV') ?: '') === '') {
    fwrite(STDERR, "Define COLEGIO_ENV con la configuración de prueba (fecha congelada).\n");
    exit(2);
}

/**
 * Lo que depende de la máquina y no del reporte: las fechas del reloj de PHP y los finales de línea
 * (en Windows git deja los .php en CRLF y el HTML escrito en ellos lleva \r\n; en el CI, \n).
 */
function normalizar(string $texto): string
{
    $hoy = [date('d/m/Y'), date('d-m-Y'), date('Y-m-d')];
    return str_replace([...$hoy, "\r\n", '\r\n'], ['«HOY»', '«HOY»', '«HOY»', "\n", '\n'], $texto);
}

function ejecutar(array $caso): array
{
    [$reporte, $rol, $usuario, $parametros] = $caso;
    $comando = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../tests/Caracterizacion/reporte_html.php')
        . ' ' . escapeshellarg($reporte) . ' ' . escapeshellarg($rol) . ' ' . escapeshellarg($usuario)
        . ' ' . escapeshellarg(http_build_query($parametros)) . ' 2>&1';
    $salida = (string) shell_exec($comando);
    $partes = explode("\n@@REPORTE@@", $salida, 2);
    $registro = isset($partes[1]) ? json_decode($partes[1], true) : null;
    return [
        'reporte' => $reporte, 'rol' => $rol, 'parametros' => $parametros,
        'registro' => is_array($registro) ? json_decode(normalizar((string) json_encode($registro, JSON_UNESCAPED_UNICODE)), true) : null,
        // Lo que se imprimió fuera del PDF (avisos, errores): debe estar vacío.
        'salida' => trim(normalizar($partes[0])),
    ];
}

$resultados = array_map('ejecutar', CASOS);

if ($modo === 'grabar') {
    file_put_contents(ARCHIVO, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    $sinPdf = count(array_filter($resultados, static fn (array $r): bool => $r['registro'] === null));
    echo count($resultados) . " reportes grabados ($sinPdf sin PDF).\n";
    exit(0);
}

$grabados = json_decode((string) @file_get_contents(ARCHIVO), true);
if (!is_array($grabados)) {
    fwrite(STDERR, "No hay grabación: ejecuta primero «grabar».\n");
    exit(2);
}
/** Primera diferencia legible entre la grabación y el resultado (campo y línea). */
function primeraDiferencia(array $antes, array $ahora): string
{
    if ($antes['salida'] !== $ahora['salida']) {
        return 'salida: «' . mb_substr($ahora['salida'], 0, 200) . '»';
    }
    $a = explode("\n", implode('', $antes['registro']['html'] ?? []));
    $b = explode("\n", implode('', $ahora['registro']['html'] ?? []));
    foreach ($b as $n => $linea) {
        if (($a[$n] ?? null) !== $linea) {
            return "html línea $n: grabado «" . trim(mb_substr($a[$n] ?? '(nada)', 0, 120)) . '» ahora «' . trim(mb_substr($linea, 0, 120)) . '»';
        }
    }
    return count($a) !== count($b) ? 'html: ' . count($a) . ' líneas grabadas, ' . count($b) . ' ahora' : 'config, pie o archivo';
}

$cambios = 0;
foreach ($resultados as $i => $r) {
    if (($grabados[$i] ?? null) !== $r) {
        $cambios++;
        $detalle = is_array($grabados[$i] ?? null) ? primeraDiferencia($grabados[$i], $r) : 'caso nuevo';
        echo "CAMBIO  {$r['reporte']} " . json_encode($r['parametros']) . "\n        $detalle\n";
        if (getenv('GITHUB_ACTIONS')) {
            echo "::error title=Reporte PDF::{$r['reporte']} " . json_encode($r['parametros']) . ' ' . str_replace("\n", ' ', $detalle) . "\n";
        }
    }
}
echo $cambios === 0 ? count($resultados) . " reportes idénticos a la grabación.\n" : "$cambios reportes cambiaron.\n";
exit($cambios === 0 ? 0 : 1);
