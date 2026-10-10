<?php

declare(strict_types=1);

namespace App\Comercial;

use App\Superadmin\Auditoria;
use App\Tenancy\RespaldoInstitucion;
use PDO;

/**
 * Baja de una institución (Fase 4B.8): exportación final, retención pactada y borrado verificable.
 *
 *   1. exportarFinal(): el zip que se entrega a la institución (ExportacionDatos), con su SHA-256 en la
 *      auditoría. Sin él no se borra nada.
 *   2. Cancelada en el panel → retencion_hasta = hoy + RETENCION_DIAS_BAJA (los datos siguen intactos).
 *   3. purgar(): vencida la retención, borra su base, su almacén de archivos y sus respaldos, COMPRUEBA que
 *      ya no existen y deja una constancia (JSON con su propio SHA-256 en la auditoría). La fila del registro
 *      se conserva (borrado_en) para que el subdominio no se reutilice por error y quede el historial.
 */
final class BajaInstitucion
{
    /**
     * @param PDO $servidor conexión con DDL, sin base seleccionada
     * @param string $almacenes carpeta con los almacenes de las instituciones (ALMACEN_DIR)
     * @param string $respaldos carpeta de los respaldos (RESPALDO_DIR)
     */
    public function __construct(
        private readonly PDO $maestro,
        private readonly PDO $servidor,
        private readonly Auditoria $auditoria,
        private readonly string $almacenes,
        private readonly string $respaldos,
    ) {
    }

    /** @return array{archivo: string, sha256: string} */
    public function exportarFinal(string $slug, PDO $base, string $actor): array
    {
        $t = $this->tenant($slug);
        $carpeta = $this->respaldos . '/bajas';
        if (!is_dir($carpeta) && !mkdir($carpeta, 0700, true)) {
            throw new \RuntimeException("No se pudo crear $carpeta");
        }
        $archivo = "$carpeta/{$slug}-exportacion-final-" . date('Ymd-His') . '.zip';
        $resumen = (new ExportacionDatos())->generar($base, (string) $t['razon_social'], "{$this->almacenes}/$slug", $archivo);
        $suma = (string) hash_file('sha256', $archivo);
        $this->auditoria->registrar(
            $actor,
            'EXPORTACION_FINAL',
            $slug,
            basename($archivo) . " sha256=$suma ({$resumen['tablas']} tablas, {$resumen['filas']} filas, {$resumen['archivos']} archivos)",
            ''
        );
        return ['archivo' => $archivo, 'sha256' => $suma];
    }

    /**
     * Lo que purgar() borraría y por qué no se puede todavía (si es el caso).
     *
     * @return array{base: string, almacen: ?string, respaldos: list<string>, impedimentos: list<string>, exportacion: ?string}
     */
    public function plan(string $slug, \DateTimeImmutable $hoy): array
    {
        $t = $this->tenant($slug);
        $impedimentos = [];
        if ($t['borrado_en'] !== null) {
            $impedimentos[] = "ya se borró el {$t['borrado_en']}";
        }
        if ($t['estado'] !== 'CANCELADO') {
            $impedimentos[] = "está {$t['estado']}: solo se borra una institución CANCELADA";
        } elseif ($t['retencion_hasta'] === null || (string) $t['retencion_hasta'] >= $hoy->format('Y-m-d')) {
            $impedimentos[] = 'la retención pactada no ha vencido' . ($t['retencion_hasta'] !== null ? " (hasta el {$t['retencion_hasta']})" : '');
        }
        $exportacion = $this->exportacionFinal($slug);
        if ($exportacion === null) {
            $impedimentos[] = 'no hay exportación final entregada (tools/baja_tenant.php exportar)';
        }
        $almacen = "{$this->almacenes}/$slug";
        return [
            'base' => (string) $t['base_datos'],
            'almacen' => is_dir($almacen) ? $almacen : null,
            'respaldos' => $this->respaldosDe($slug),
            'impedimentos' => $impedimentos,
            'exportacion' => $exportacion,
        ];
    }

    /**
     * Borra y verifica. $confirmacion debe ser el slug (contra borrados por error de dedo).
     *
     * @return string la ruta de la constancia
     */
    public function purgar(string $slug, string $confirmacion, \DateTimeImmutable $hoy, string $actor): string
    {
        if ($confirmacion !== $slug) {
            throw new \InvalidArgumentException('Para borrar hay que confirmar con el slug exacto de la institución.');
        }
        $plan = $this->plan($slug, $hoy);
        if ($plan['impedimentos'] !== []) {
            throw new \DomainException('No se puede borrar: ' . implode('; ', $plan['impedimentos']) . '.');
        }
        $t = $this->tenant($slug);

        $this->servidor->exec("DROP DATABASE IF EXISTS `{$plan['base']}`");
        if ($plan['almacen'] !== null) {
            RespaldoInstitucion::borrarCarpeta($plan['almacen']);
        }
        foreach ($plan['respaldos'] as $respaldo) {
            RespaldoInstitucion::borrarCarpeta($respaldo);
        }

        // Verificación: cada cosa debe haber desaparecido de verdad.
        $existe = $this->servidor->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $existe->execute([$plan['base']]);
        $verificacion = [
            'base_datos_eliminada' => (int) $existe->fetchColumn() === 0,
            'almacen_eliminado' => !is_dir("{$this->almacenes}/$slug"),
            'respaldos_eliminados' => $this->respaldosDe($slug) === [],
        ];
        if (in_array(false, $verificacion, true)) {
            $this->auditoria->registrar($actor, 'BORRADO_INCOMPLETO', $slug, (string) json_encode($verificacion), '');
            throw new \RuntimeException('El borrado no se pudo verificar: ' . json_encode($verificacion));
        }

        $this->maestro->prepare('UPDATE tenants SET borrado_en = NOW() WHERE slug = ?')->execute([$slug]);
        $constancia = [
            'documento' => 'Constancia de borrado de datos',
            'institucion' => (string) $t['razon_social'],
            'slug' => $slug,
            'cancelada_en' => $t['cancelado_en'],
            'retencion_hasta' => $t['retencion_hasta'],
            'borrado_en' => date('c'),
            'responsable' => $actor,
            'exportacion_final_entregada' => $plan['exportacion'],
            'eliminado' => [
                'base_de_datos' => $plan['base'],
                'archivos' => $plan['almacen'] !== null ? 'almacén de la institución' : 'no tenía',
                'respaldos' => count($plan['respaldos']),
            ],
            'verificacion' => $verificacion,
        ];
        $archivo = "{$this->respaldos}/bajas/{$slug}-constancia-borrado-" . date('Ymd-His') . '.json';
        if (!is_dir(dirname($archivo))) {
            mkdir(dirname($archivo), 0700, true);
        }
        file_put_contents($archivo, json_encode($constancia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->auditoria->registrar($actor, 'BORRADO', $slug, basename($archivo) . ' sha256=' . hash_file('sha256', $archivo), '');
        return $archivo;
    }

    /** @return array<string, mixed> */
    private function tenant(string $slug): array
    {
        $consulta = $this->maestro->prepare('SELECT * FROM tenants WHERE slug = ?');
        $consulta->execute([$slug]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($fila)) {
            throw new \DomainException("No existe la institución «{$slug}».");
        }
        return $fila;
    }

    /**
     * Las carpetas de respaldo de ESTA institución: «<slug>-AAAAMMDD-HHMMSS» exacto (con un comodín, borrar
     * «colegio-a» se llevaría también los respaldos de «colegio-a-norte»).
     *
     * @return list<string>
     */
    private function respaldosDe(string $slug): array
    {
        $propias = array_filter(
            glob("{$this->respaldos}/$slug-*") ?: [],
            static fn (string $c): bool => is_dir($c) && preg_match('/^' . preg_quote($slug, '/') . '-\d{8}-\d{6}$/', basename($c)) === 1,
        );
        return array_values($propias);
    }

    /** El detalle de la última exportación final auditada, o null. */
    private function exportacionFinal(string $slug): ?string
    {
        $consulta = $this->maestro->prepare("SELECT CONCAT(fecha, ' ', detalle) FROM auditoria WHERE accion = 'EXPORTACION_FINAL' AND tenant = ? ORDER BY id DESC LIMIT 1");
        $consulta->execute([$slug]);
        $fila = $consulta->fetchColumn();
        return $fila !== false ? (string) $fila : null;
    }
}
