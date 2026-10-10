<?php

declare(strict_types=1);

namespace App\Comercial;

use PDO;

/**
 * Exportación completa de los datos de una institución (Fase 4B.7; portabilidad, Ley 29733): un zip con
 * un CSV por tabla (UTF-8 con BOM, para que Excel lo abra bien) y sus archivos, legible sin este sistema.
 *
 * No incluye los hashes de las contraseñas: no le sirven a nadie fuera del sistema y son un riesgo si el
 * zip se pierde. Tampoco phinxlog (es del sistema, no de la institución).
 */
final class ExportacionDatos
{
    /** Columnas que nunca salen. */
    public const EXCLUIDAS = ['usuario' => ['usu_contra']];

    /**
     * @param list<array{0: string, 1: string}> $anteriores carpetas de antes de la Fase 4 [física, ruta en la BD] (modo único)
     * @param \Closure(string): bool $esDeLaAplicacion archivos de esas carpetas que no son de los usuarios
     * @return array{tablas: int, filas: int, archivos: int}
     */
    public function generar(PDO $pdo, string $nombre, string $almacen, string $zip, array $anteriores = [], ?\Closure $esDeLaAplicacion = null): array
    {
        $archivo = new \ZipArchive();
        if ($archivo->open($zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear la exportación.');
        }
        $tablas = 0;
        $filas = 0;
        $resumen = [];
        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as [$tabla]) {
            $tabla = (string) $tabla;
            if ($tabla === 'phinxlog') {
                continue;
            }
            [$csv, $n] = self::csv($pdo, $tabla);
            $archivo->addFromString("datos/$tabla.csv", $csv);
            $tablas++;
            $filas += $n;
            $resumen[$tabla] = $n;
        }
        $archivos = 0;
        $fuentes = [[$almacen, '']];
        foreach ($anteriores as [$fisica, $rutaBd]) {
            $fuentes[] = [$fisica, $rutaBd . '/'];
        }
        foreach ($fuentes as [$carpeta, $prefijo]) {
            if (!is_dir($carpeta)) {
                continue;
            }
            $base = (string) realpath($carpeta);
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS)) as $f) {
                /** @var \SplFileInfo $f */
                $entrada = 'archivos/' . $prefijo . str_replace('\\', '/', substr((string) $f->getRealPath(), strlen($base) + 1));
                if ($f->isFile() && !($esDeLaAplicacion !== null && $prefijo !== '' && $esDeLaAplicacion($f->getFilename()))
                    && $archivo->locateName($entrada) === false) {
                    $archivo->addFile($f->getPathname(), $entrada);
                    $archivos++;
                }
            }
        }
        $archivo->addFromString('LEEME.txt', "Exportación de datos de: $nombre\nGenerada: " . date('Y-m-d H:i:s P') . "\n\n"
            . "datos/     un archivo CSV por tabla (UTF-8, separado por comas, primera fila = nombres de columna)\n"
            . "archivos/  fotos, logos y documentos, con la misma ruta que figura en los datos\n\n"
            . "No incluye las contraseñas de los usuarios. manifiesto.json resume el contenido.\n");
        $archivo->addFromString('manifiesto.json', (string) json_encode(
            ['institucion' => $nombre, 'generada' => date('c'), 'filas_por_tabla' => $resumen, 'archivos' => $archivos],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
        ));
        $archivo->close();
        return ['tablas' => $tablas, 'filas' => $filas, 'archivos' => $archivos];
    }

    /** @return array{0: string, 1: int} el CSV y su número de filas */
    private static function csv(PDO $pdo, string $tabla): array
    {
        $consulta = $pdo->query("SELECT * FROM `$tabla`");
        $excluidas = self::EXCLUIDAS[$tabla] ?? [];
        $salida = fopen('php://temp', 'w+');
        if ($salida === false) {
            throw new \RuntimeException('Sin memoria temporal para la exportación.');
        }
        fwrite($salida, "\xEF\xBB\xBF");
        $n = 0;
        $cabecera = null;
        while (($fila = $consulta->fetch(PDO::FETCH_ASSOC)) !== false) {
            foreach ($excluidas as $columna) {
                unset($fila[$columna]);
            }
            if ($cabecera === null) {
                $cabecera = array_keys($fila);
                fputcsv($salida, $cabecera, ',', '"', '');
            }
            fputcsv($salida, array_map(static fn ($v): string => $v === null ? '' : (string) $v, $fila), ',', '"', '');
            $n++;
        }
        if ($cabecera === null) {
            // Tabla vacía: al menos los nombres de columna.
            $columnas = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
            fputcsv($salida, array_values(array_diff(array_map('strval', $columnas), $excluidas)), ',', '"', '');
        }
        rewind($salida);
        $csv = (string) stream_get_contents($salida);
        fclose($salida);
        return [$csv, $n];
    }
}
