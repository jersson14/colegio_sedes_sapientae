<?php

/**
 * Baja de una institución (Fase 4B.8) con las herramientas reales, sobre una institución creada para la
 * prueba: no se borra hasta que está CANCELADA, con la retención vencida, exportación final y confirmación;
 * entonces desaparecen su base, sus archivos y sus respaldos (y nada de otra institución), y queda constancia.
 *
 * Necesita COLEGIO_ENV (modo múltiple), MAESTRO_DB_NAME y DB_* como las demás pruebas, y mysqldump/mysql.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__, 2);
$entorno = static fn (string $c): string => (string) (getenv($c) ?: '');
$servidor = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8', $entorno('DB_HOST') ?: '127.0.0.1', (int) ($entorno('DB_PORT') ?: 3306)),
    $entorno('DB_USER'),
    $entorno('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$maestro = $entorno('MAESTRO_DB_NAME');

/** @return array{salida: string, codigo: int} */
function baja_herramienta(string ...$argumentos): array
{
    $proceso = proc_open(array_merge([PHP_BINARY], $argumentos), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $t, dirname(__DIR__, 2));
    $salida = is_resource($proceso) ? (string) stream_get_contents($t[1]) . (string) stream_get_contents($t[2]) : '';
    return ['salida' => $salida, 'codigo' => is_resource($proceso) ? proc_close($proceso) : -1];
}

final class RecuentoBaja
{
    public static int $ok = 0;
    public static int $fallos = 0;
}

function baja_comprobar(bool $condicion, string $descripcion, string $detalle = ''): void
{
    if ($condicion) {
        RecuentoBaja::$ok++;
        echo "  ok    $descripcion\n";
        return;
    }
    RecuentoBaja::$fallos++;
    echo "  FALLO $descripcion $detalle\n";
    if (getenv('GITHUB_ACTIONS')) {
        echo '::error title=Baja::' . str_replace("\n", ' ', "$descripcion $detalle") . "\n";
    }
}

/** Lo que diga colegio.env (ALMACEN_DIR, RESPALDO_DIR), con los mismos valores por defecto que las herramientas. */
function baja_config(string $clave, string $porDefecto): string
{
    foreach (file((string) getenv('COLEGIO_ENV'), FILE_IGNORE_NEW_LINES) ?: [] as $linea) {
        if (str_starts_with(trim($linea), "$clave=")) {
            return rtrim(trim(substr(trim($linea), strlen($clave) + 1)), '/\\');
        }
    }
    return $porDefecto;
}

$slug = 'baja-' . getmypid();
$base = 'sge_' . str_replace('-', '_', $slug);
$almacen = baja_config('ALMACEN_DIR', "$raiz/storage/tenants") . "/$slug";
$respaldos = baja_config('RESPALDO_DIR', dirname((string) getenv('COLEGIO_ENV')) . '/respaldos');
$existeBase = static function () use ($servidor, $base): bool {
    $q = $servidor->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $q->execute([$base]);
    return (int) $q->fetchColumn() === 1;
};
$tenant = static fn (string $col): string => (string) $servidor->query("SELECT $col FROM `$maestro`.tenants WHERE slug = " . $servidor->quote($slug))->fetchColumn();

echo "Baja de una institución ($slug)\n";
$alta = baja_herramienta(
    'tools/alta_tenant.php',
    "--slug=$slug",
    '--razon=Colegio que se va',
    '--email=baja@example.com',
    '--admin-dni=12345678',
    '--admin-nombres=Ana',
    '--admin-apellidos=Pérez',
    '--estado=ACTIVO'
);
@mkdir("$almacen/controller/alumnos/fotos", 0755, true);
file_put_contents("$almacen/controller/alumnos/fotos/IMG_baja.png", 'x');
baja_herramienta('tools/respaldo_tenant.php', 'respaldar', "--tenant=$slug");
$senuelo = "$respaldos/$slug-norte-20260101-000000";
@mkdir($senuelo, 0700, true);
file_put_contents("$senuelo/manifiesto.json", '{}');
baja_comprobar($existeBase() && is_dir($almacen) && glob("$respaldos/$slug-2*") !== [], 'la institución tiene base, archivos y un respaldo', $alta['salida']);

$activa = baja_herramienta('tools/baja_tenant.php', 'purgar', "--tenant=$slug", "--confirmar=$slug");
baja_comprobar(
    $activa['codigo'] !== 0 && str_contains($activa['salida'], 'solo se borra una institución CANCELADA') && $existeBase(),
    'activa no se borra aunque se confirme',
    $activa['salida']
);

$servidor->exec("UPDATE `$maestro`.tenants SET estado = 'CANCELADO', cancelado_en = NOW(), retencion_hasta = CURDATE() + INTERVAL 30 DAY WHERE slug = " . $servidor->quote($slug));
$enRetencion = baja_herramienta('tools/baja_tenant.php', 'purgar', "--tenant=$slug", "--confirmar=$slug");
baja_comprobar(
    $enRetencion['codigo'] !== 0 && str_contains($enRetencion['salida'], 'retención pactada no ha vencido') && str_contains($enRetencion['salida'], 'exportación final'),
    'cancelada, dentro de la retención y sin exportación final, tampoco',
    $enRetencion['salida']
);

$exportacion = baja_herramienta('tools/baja_tenant.php', 'exportar', "--tenant=$slug");
$zip = preg_match('/Exportación final: (\S+)/', $exportacion['salida'], $m) === 1 ? $m[1] : '';
baja_comprobar(
    $zip !== '' && is_file($zip) && str_contains($exportacion['salida'], 'SHA-256: ' . hash_file('sha256', $zip)),
    'la exportación final se genera con su SHA-256',
    $exportacion['salida']
);

$servidor->exec("UPDATE `$maestro`.tenants SET retencion_hasta = CURDATE() - INTERVAL 1 DAY WHERE slug = " . $servidor->quote($slug));
$aviso = baja_herramienta('tools/baja_tenant.php', 'purgar', "--tenant=$slug");
baja_comprobar($aviso['codigo'] === 0 && str_contains($aviso['salida'], 'Se puede borrar') && $existeBase(), 'sin --confirmar solo informa');
$mala = baja_herramienta('tools/baja_tenant.php', 'purgar', "--tenant=$slug", '--confirmar=otro');
baja_comprobar($mala['codigo'] !== 0 && $existeBase(), 'con una confirmación que no es el slug no borra');

$borrado = baja_herramienta('tools/baja_tenant.php', 'purgar', "--tenant=$slug", "--confirmar=$slug");
$constancia = preg_match('/Constancia: (\S+)/', $borrado['salida'], $m) === 1 ? $m[1] : '';
baja_comprobar(
    $borrado['codigo'] === 0 && !$existeBase() && !is_dir($almacen) && glob("$respaldos/$slug-2*") === [],
    'vencida la retención y con exportación: base, archivos y respaldos desaparecen',
    $borrado['salida']
);
baja_comprobar(is_dir($senuelo), 'el respaldo de otra institución con un nombre parecido sigue intacto');
$datos = $constancia !== '' && is_file($constancia) ? (array) json_decode((string) file_get_contents($constancia), true) : [];
baja_comprobar(
    ($datos['verificacion']['base_datos_eliminada'] ?? false) === true && ($datos['exportacion_final_entregada'] ?? null) !== null,
    'la constancia registra la verificación y la exportación entregada'
);
$auditada = (string) $servidor->query("SELECT detalle FROM `$maestro`.auditoria WHERE accion = 'BORRADO' AND tenant = " . $servidor->quote($slug) . ' ORDER BY id DESC LIMIT 1')->fetchColumn();
baja_comprobar($constancia !== '' && str_contains($auditada, 'sha256=' . hash_file('sha256', $constancia)), 'la auditoría guarda el SHA-256 de la constancia');
baja_comprobar($tenant('borrado_en') !== '' && $tenant('estado') === 'CANCELADO', 'el registro se conserva como borrado (el subdominio no se reutiliza por error)');
$otraVez = baja_herramienta('tools/baja_tenant.php', 'purgar', "--tenant=$slug", "--confirmar=$slug");
baja_comprobar($otraVez['codigo'] !== 0 && str_contains($otraVez['salida'], 'ya se borró'), 'un segundo borrado se rechaza');

// Limpieza de lo que la prueba dejó fuera de la institución.
foreach (array_merge([$zip, $constancia], glob("$senuelo/*") ?: []) as $archivo) {
    if ($archivo !== '' && is_file($archivo)) {
        unlink($archivo);
    }
}
@rmdir($senuelo);
echo "\n" . RecuentoBaja::$ok . ' comprobaciones correctas, ' . RecuentoBaja::$fallos . " fallos\n";
exit(RecuentoBaja::$fallos === 0 ? 0 : 1);
