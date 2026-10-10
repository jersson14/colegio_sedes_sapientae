<?php

declare(strict_types=1);

namespace App\Tenancy;

/**
 * mysqldump y mysql (el cliente) para copias de seguridad (Fase 4.10). Las credenciales van en un
 * archivo de opciones temporal (permisos 0600, se borra al terminar), nunca en la línea de comandos.
 *
 * Binarios: MYSQL_BIN_DIR en colegio.env (p. ej. C:/xampp/mysql/bin) o los del PATH.
 */
final class ClienteMysql
{
    public function __construct(
        private readonly string $host,
        private readonly int $puerto,
        private readonly string $usuario,
        private readonly string $clave,
        private readonly string $directorioBinarios = '',
    ) {
    }

    /** Con el usuario de migraciones (DDL) de colegio.env. */
    public static function desdeConfig(): self
    {
        require_once __DIR__ . '/../../core/config.php';
        $usuario = (string) config('DB_MIGRACION_USER', '');
        return new self(
            (string) config('DB_HOST', 'localhost'),
            (int) config('DB_PORT', '3306'),
            $usuario !== '' ? $usuario : (string) config('DB_USER', ''),
            $usuario !== '' ? (string) config('DB_MIGRACION_PASS', '') : (string) config('DB_PASS', ''),
            (string) config('MYSQL_BIN_DIR', ''),
        );
    }

    /**
     * Vuelca $base a $archivo: tablas, datos, procedimientos y disparadores, en una transacción
     * consistente. Sin DEFINER (restaurar con un usuario sin SUPER, como el del hosting compartido).
     */
    public function volcar(string $base, string $archivo): void
    {
        $crudo = $archivo . '.crudo';
        $opciones = ['--single-transaction', '--routines', '--triggers', '--no-tablespaces', '--hex-blob',
            '--default-character-set=utf8mb4', '--skip-dump-date'];
        // mysqldump 8 contra MariaDB pide estadísticas de columnas que MariaDB no tiene.
        if (str_contains($this->ejecutar('mysqldump', ['--help']), 'column-statistics')) {
            $opciones[] = '--column-statistics=0';
        }
        $salida = fopen($crudo, 'wb');
        if ($salida === false) {
            throw new \RuntimeException("No se pudo escribir $crudo");
        }
        try {
            $this->ejecutar('mysqldump', [...$opciones, $base], salida: $salida);
        } finally {
            fclose($salida);
        }
        $entrada = fopen($crudo, 'rb');
        $limpio = fopen($archivo, 'wb');
        if ($entrada === false || $limpio === false) {
            throw new \RuntimeException("No se pudo escribir $archivo");
        }
        while (($linea = fgets($entrada)) !== false) {
            fwrite($limpio, (string) preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $linea));
        }
        fclose($entrada);
        fclose($limpio);
        unlink($crudo);
    }

    /** Carga $archivo (un volcado) en $base, que ya debe existir. */
    public function cargar(string $base, string $archivo): void
    {
        $entrada = fopen($archivo, 'rb');
        if ($entrada === false) {
            throw new \RuntimeException("No se pudo leer $archivo");
        }
        try {
            $this->ejecutar('mysql', ['--default-character-set=utf8mb4', $base], entrada: $entrada);
        } finally {
            fclose($entrada);
        }
    }

    /**
     * @param list<string> $argumentos
     * @param resource|null $salida a dónde va la salida estándar (por defecto se devuelve)
     * @param resource|null $entrada de dónde lee la entrada estándar
     */
    private function ejecutar(string $programa, array $argumentos, $salida = null, $entrada = null): string
    {
        $opciones = tempnam(sys_get_temp_dir(), 'cnf');
        if ($opciones === false) {
            throw new \RuntimeException('No se pudo crear el archivo de opciones temporal.');
        }
        chmod($opciones, 0600);
        $comillas = static fn (string $v): string => '"' . addcslashes($v, "\\\"") . '"';
        file_put_contents($opciones, "[client]\nhost=" . $comillas($this->host) . "\nport={$this->puerto}\nuser="
            . $comillas($this->usuario) . "\npassword=" . $comillas($this->clave) . "\n");
        try {
            $binario = $this->directorioBinarios !== '' ? rtrim($this->directorioBinarios, '/\\') . '/' . $programa : $programa;
            $comando = [$binario, '--defaults-extra-file=' . $opciones, ...$argumentos];
            $errores = tmpfile();
            $descriptores = [0 => $entrada ?? ['pipe', 'r'], 1 => $salida ?? ['pipe', 'w'], 2 => $errores];
            $proceso = proc_open($comando, $descriptores, $tuberias);
            if (!is_resource($proceso)) {
                throw new \RuntimeException("No se pudo ejecutar $programa (¿MYSQL_BIN_DIR?).");
            }
            if (isset($tuberias[0])) {
                fclose($tuberias[0]);
            }
            $texto = isset($tuberias[1]) ? (string) stream_get_contents($tuberias[1]) : '';
            if (isset($tuberias[1])) {
                fclose($tuberias[1]);
            }
            $codigo = proc_close($proceso);
            if ($codigo !== 0) {
                rewind($errores);
                throw new \RuntimeException("$programa terminó con error $codigo: " . trim((string) stream_get_contents($errores)));
            }
            return $texto;
        } finally {
            unlink($opciones);
        }
    }
}
