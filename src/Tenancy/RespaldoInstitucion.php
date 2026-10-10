<?php

declare(strict_types=1);

namespace App\Tenancy;

use PDO;

/**
 * Copia de seguridad y restauración de UNA institución (Fase 4, hito 4.10): su base y su almacén de
 * archivos, nada de otro colegio.
 *
 * Respaldo: <destino>/<slug>-<fecha>/ con base.sql (sin DEFINER), archivos.zip y manifiesto.json
 * (sumas SHA-256, versión de migraciones y filas por tabla).
 *
 * Restauración: SIEMPRE en una base nueva. Se comprueban las sumas y, después de cargar, que cada tabla
 * tenga las filas del manifiesto; si algo no cuadra, se borra lo restaurado y no cambia nada. Activarla
 * (que la institución pase a usar la base restaurada) es un paso aparte y explícito; la base y los
 * archivos anteriores no se borran.
 */
final class RespaldoInstitucion
{
    public const VERSION_FORMATO = 1;
    private const MARCA_ZIP = '.respaldo';

    /**
     * @param PDO $servidor conexión con DDL, sin base seleccionada
     * @param \Closure(string): PDO $conectar abre una base con DDL
     */
    public function __construct(
        private readonly PDO $servidor,
        private readonly \Closure $conectar,
        private readonly ClienteMysql $mysql,
    ) {
    }

    /**
     * @param list<array{0: string, 1: string}> $anteriores carpetas de antes de la Fase 4 que también son de
     *        esta institución (solo modo único), [carpeta física, ruta en la BD]: entran con su ruta de la BD
     *        y al restaurar quedan en el almacén.
     * @param \Closure(string): bool $esDeLaAplicacion archivos de esas carpetas que no son de los usuarios
     * @return string la carpeta del respaldo
     */
    public function respaldar(Tenant $tenant, string $almacen, string $destino, array $anteriores = [], ?\Closure $esDeLaAplicacion = null): string
    {
        $carpeta = rtrim($destino, '/\\') . '/' . $tenant->slug . '-' . date('Ymd-His');
        if (!is_dir($carpeta) && !mkdir($carpeta, 0700, true)) {
            throw new \RuntimeException("No se pudo crear $carpeta");
        }
        $pdo = ($this->conectar)($tenant->baseDatos);
        $filas = self::filasPorTabla($pdo);
        $migracion = (string) $pdo->query('SELECT MAX(version) FROM phinxlog')->fetchColumn();

        $this->mysql->volcar($tenant->baseDatos, "$carpeta/base.sql");
        $archivos = self::comprimir($almacen, "$carpeta/archivos.zip", $anteriores, $esDeLaAplicacion ?? static fn (string $n): bool => false);

        $manifiesto = [
            'formato' => self::VERSION_FORMATO,
            'slug' => $tenant->slug,
            'base_datos' => $tenant->baseDatos,
            'creado' => date('c'),
            'migracion' => $migracion,
            'filas' => $filas,
            'archivos_almacen' => $archivos,
            'sha256' => ['base.sql' => hash_file('sha256', "$carpeta/base.sql"), 'archivos.zip' => hash_file('sha256', "$carpeta/archivos.zip")],
        ];
        file_put_contents("$carpeta/manifiesto.json", json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $carpeta;
    }

    /**
     * Restaura en una base nueva y deja los archivos en $almacenNuevo (una carpeta que no debe existir).
     *
     * @return array{base: string, slug: string, filas: int, archivos: int}
     */
    public function restaurar(string $carpeta, string $almacenNuevo): array
    {
        $manifiesto = self::manifiesto($carpeta);
        $base = substr((string) $manifiesto['base_datos'], 0, 46) . '_r' . date('YmdHis');
        if (file_exists($almacenNuevo)) {
            throw new \RuntimeException("$almacenNuevo ya existe.");
        }
        $this->servidor->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        try {
            $this->mysql->cargar($base, "$carpeta/base.sql");
            $filas = self::filasPorTabla(($this->conectar)($base));
            $esperadas = (array) $manifiesto['filas'];
            ksort($esperadas);
            ksort($filas);
            if ($filas !== array_map('intval', $esperadas)) {
                $distintas = array_keys(array_diff_assoc($esperadas, $filas) + array_diff_assoc($filas, $esperadas));
                throw new \RuntimeException('Las filas restauradas no coinciden con el respaldo en: ' . implode(', ', $distintas));
            }
            $archivos = self::descomprimir("$carpeta/archivos.zip", $almacenNuevo);
            if ($archivos !== (int) $manifiesto['archivos_almacen']) {
                throw new \RuntimeException("Se esperaban {$manifiesto['archivos_almacen']} archivos y se restauraron $archivos.");
            }
        } catch (\Throwable $e) {
            $this->servidor->exec("DROP DATABASE IF EXISTS `$base`");
            self::borrarCarpeta($almacenNuevo);
            throw $e;
        }
        return ['base' => $base, 'slug' => (string) $manifiesto['slug'], 'filas' => array_sum($filas), 'archivos' => $archivos];
    }

    /** @return array<string, mixed> el manifiesto, con las sumas ya comprobadas */
    public static function manifiesto(string $carpeta): array
    {
        $manifiesto = json_decode((string) @file_get_contents("$carpeta/manifiesto.json"), true);
        if (!is_array($manifiesto) || ($manifiesto['formato'] ?? null) !== self::VERSION_FORMATO) {
            throw new \RuntimeException("$carpeta no es un respaldo válido (falta o no se lee manifiesto.json).");
        }
        foreach ((array) $manifiesto['sha256'] as $archivo => $suma) {
            if (!is_file("$carpeta/$archivo") || !hash_equals((string) $suma, (string) hash_file('sha256', "$carpeta/$archivo"))) {
                throw new \RuntimeException("$archivo está dañado o fue modificado (la suma SHA-256 no coincide).");
            }
        }
        return $manifiesto;
    }

    /** @return array<string, int> filas exactas por tabla (sin phinxlog, que no es del colegio) */
    private static function filasPorTabla(PDO $pdo): array
    {
        $filas = [];
        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as [$tabla]) {
            if ($tabla !== 'phinxlog') {
                $filas[(string) $tabla] = (int) $pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
            }
        }
        return $filas;
    }

    /**
     * @param list<array{0: string, 1: string}> $anteriores
     * @param \Closure(string): bool $esDeLaAplicacion
     */
    private static function comprimir(string $almacen, string $zip, array $anteriores, \Closure $esDeLaAplicacion): int
    {
        $archivo = new \ZipArchive();
        if ($archivo->open($zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No se pudo crear $zip");
        }
        $total = 0;
        if (is_dir($almacen)) {
            $raiz = realpath($almacen);
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($almacen, \FilesystemIterator::SKIP_DOTS)) as $f) {
                /** @var \SplFileInfo $f */
                if ($f->isFile()) {
                    $archivo->addFile($f->getPathname(), str_replace('\\', '/', substr((string) $f->getRealPath(), strlen((string) $raiz) + 1)));
                    $total++;
                }
            }
        }
        foreach ($anteriores as [$fisica, $rutaBd]) {
            if (!is_dir($fisica)) {
                continue;
            }
            $base = (string) realpath($fisica);
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fisica, \FilesystemIterator::SKIP_DOTS)) as $f) {
                /** @var \SplFileInfo $f */
                $entrada = $rutaBd . '/' . str_replace('\\', '/', substr((string) $f->getRealPath(), strlen($base) + 1));
                // Lo del almacén manda: si un archivo está en los dos sitios, ya entró.
                if ($f->isFile() && !$esDeLaAplicacion($f->getFilename()) && $archivo->locateName($entrada) === false) {
                    $archivo->addFile($f->getPathname(), $entrada);
                    $total++;
                }
            }
        }
        // Un zip sin entradas no llega a escribirse: la marca hace que exista aunque el colegio no tenga archivos.
        $archivo->addFromString(self::MARCA_ZIP, 'formato ' . self::VERSION_FORMATO);
        $archivo->close();
        return $total;
    }

    private static function descomprimir(string $zip, string $destino): int
    {
        $archivo = new \ZipArchive();
        if ($archivo->open($zip) !== true) {
            throw new \RuntimeException("No se pudo abrir $zip");
        }
        if (!mkdir($destino, 0755, true)) {
            throw new \RuntimeException("No se pudo crear $destino");
        }
        for ($i = 0; $i < $archivo->numFiles; $i++) {
            $nombre = (string) $archivo->getNameIndex($i);
            // Nada fuera de la carpeta destino («..», rutas absolutas).
            if (str_contains($nombre, '..') || str_starts_with($nombre, '/') || preg_match('/^[A-Za-z]:/', $nombre) === 1) {
                throw new \RuntimeException("Entrada no permitida en el respaldo: $nombre");
            }
        }
        $archivo->extractTo($destino);
        $total = $archivo->numFiles - ($archivo->locateName(self::MARCA_ZIP) !== false ? 1 : 0);
        $archivo->close();
        @unlink($destino . '/' . self::MARCA_ZIP);
        return $total;
    }

    public static function borrarCarpeta(string $carpeta): void
    {
        if (!is_dir($carpeta)) {
            return;
        }
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterador as $f) {
            /** @var \SplFileInfo $f */
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($carpeta);
    }
}
