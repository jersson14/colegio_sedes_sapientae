<?php

declare(strict_types=1);

namespace App\Auditoria;

use PDO;

/**
 * Auditoría de una institución (H-15): registra las escrituras, los inicios y cierres de sesión, y permite
 * consultarlos. Escribe por SP_REGISTRAR_AUDITORIA (la aplicación no tiene INSERT).
 */
final class RegistroAuditoria
{
    /** Campos que nunca se guardan tal cual: contraseñas, tokens y claves. */
    private const SENSIBLES = '/(contra|clave|pass|token|csrf|secret|api_?key)/i';

    private const MAX_VALOR = 200;
    private const MAX_DATOS = 4000;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(?int $usuId, string $usuario, string $rol, string $modulo, string $accion, int $estado, string $ip, ?string $datos): void
    {
        $consulta = $this->pdo->prepare('CALL SP_REGISTRAR_AUDITORIA(?, ?, ?, ?, ?, ?, ?, ?)');
        $consulta->execute([$usuId, $usuario, $rol, $modulo, $accion, $estado, $ip, $datos]);
        $consulta->closeCursor();
    }

    /**
     * Lo enviado en la petición, apto para guardar: sin contraseñas ni tokens, valores largos recortados y de
     * los archivos solo el nombre. null si no hay nada.
     *
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $archivos $_FILES
     */
    public static function datosSeguros(array $post, array $archivos = []): ?string
    {
        $limpio = self::limpiar($post);
        foreach ($archivos as $campo => $archivo) {
            $nombres = is_array($archivo) ? ($archivo['name'] ?? '') : '';
            $limpio['archivo:' . $campo] = is_array($nombres) ? array_values(array_map('strval', $nombres)) : (string) $nombres;
        }
        if ($limpio === []) {
            return null;
        }
        $json = (string) json_encode($limpio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return mb_strlen($json) > self::MAX_DATOS ? mb_substr($json, 0, self::MAX_DATOS) . '…' : $json;
    }

    /**
     * @param array<array-key, mixed> $datos
     * @return array<array-key, mixed>
     */
    private static function limpiar(array $datos): array
    {
        $limpio = [];
        foreach ($datos as $clave => $valor) {
            if (preg_match(self::SENSIBLES, (string) $clave) === 1) {
                $limpio[$clave] = '***';
            } elseif (is_array($valor)) {
                $limpio[$clave] = self::limpiar($valor);
            } else {
                $texto = (string) $valor;
                $limpio[$clave] = mb_strlen($texto) > self::MAX_VALOR ? mb_substr($texto, 0, self::MAX_VALOR) . '…' : $texto;
            }
        }
        return $limpio;
    }

    /**
     * Lecturas que el código heredado hace por POST (los listados de DataTables, los selects): no se auditan
     * cuando salen bien, para que la auditoría muestre lo que cambia datos. Si se deniegan, sí.
     */
    public static function esLecturaRutinaria(string $accion, int $estado): bool
    {
        return $estado < 400 && preg_match('/^(listar|cargar|total|traer|obtener|ver)(_|[a-z])/', $accion) === 1;
    }

    /**
     * Módulo y acción a partir del archivo que atiende la petición:
     * controller/alumnos/controlador_registrar_alumno.php → [alumnos, registrar_alumno].
     *
     * @return array{0: string, 1: string}
     */
    public static function moduloYAccion(string $archivo): array
    {
        $ruta = str_replace('\\', '/', $archivo);
        $accion = preg_replace('/^controlador_/', '', basename($ruta, '.php')) ?? '';
        if (preg_match('#/controller/([^/]+)/[^/]+$#', $ruta, $m) === 1) {
            return [$m[1], $accion];
        }
        if (str_contains($ruta, '/MPDF/REPORTE/')) {
            return ['reportes', $accion];
        }
        return ['otros', $accion];
    }

    /**
     * Búsqueda para la pantalla de auditoría, lo más reciente primero.
     *
     * @param array{desde?: string, hasta?: string, usuario?: string, modulo?: string} $filtros fechas en Y-m-d
     * @return list<array<string, mixed>>
     */
    public function buscar(array $filtros, int $limite = 500): array
    {
        $condiciones = [];
        $parametros = [];
        if (($filtros['desde'] ?? '') !== '') {
            $condiciones[] = 'fecha >= ?';
            $parametros[] = $filtros['desde'] . ' 00:00:00';
        }
        if (($filtros['hasta'] ?? '') !== '') {
            $condiciones[] = 'fecha <= ?';
            $parametros[] = $filtros['hasta'] . ' 23:59:59';
        }
        if (($filtros['usuario'] ?? '') !== '') {
            $condiciones[] = 'usuario LIKE ?';
            $parametros[] = '%' . addcslashes($filtros['usuario'], '%_\\') . '%';
        }
        if (($filtros['modulo'] ?? '') !== '') {
            $condiciones[] = 'modulo = ?';
            $parametros[] = $filtros['modulo'];
        }
        $consulta = $this->pdo->prepare(
            'SELECT id, fecha, usu_id, usuario, rol, modulo, accion, estado, ip, datos FROM auditoria_acciones'
            . ($condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones))
            . ' ORDER BY id DESC LIMIT ' . max(1, min($limite, 2000))
        );
        $consulta->execute($parametros);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<string> */
    public function modulos(): array
    {
        return array_map('strval', $this->pdo->query('SELECT DISTINCT modulo FROM auditoria_acciones ORDER BY modulo')->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Borra lo más antiguo que $dias (mínimo 30, lo impone el procedimiento). */
    public function purgar(int $dias): int
    {
        $consulta = $this->pdo->prepare('CALL SP_PURGAR_AUDITORIA(?)');
        $consulta->execute([$dias]);
        $borradas = (int) $consulta->fetchColumn();
        $consulta->closeCursor();
        return $borradas;
    }
}
