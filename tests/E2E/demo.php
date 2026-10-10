<?php

/**
 * Demo comercial y conversión en cliente (Fase 4B.6), con las herramientas y por HTTP, en modo múltiple.
 * Mismo entorno que tests/E2E/baja.php, más BASE_URL (servidor en modo múltiple).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$puerto = (int) (parse_url(getenv('BASE_URL') ?: 'http://127.0.0.1:8098/', PHP_URL_PORT) ?: 80);
$entorno = static fn (string $c): string => (string) (getenv($c) ?: '');
$servidor = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8', $entorno('DB_HOST') ?: '127.0.0.1', (int) ($entorno('DB_PORT') ?: 3306)),
    $entorno('DB_USER'),
    $entorno('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$maestro = $entorno('MAESTRO_DB_NAME');

/** @return array{salida: string, codigo: int} */
function demo_herramienta(string ...$argumentos): array
{
    $proceso = proc_open(array_merge([PHP_BINARY], $argumentos), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $t, dirname(__DIR__, 2));
    $salida = is_resource($proceso) ? (string) stream_get_contents($t[1]) . (string) stream_get_contents($t[2]) : '';
    return ['salida' => $salida, 'codigo' => is_resource($proceso) ? proc_close($proceso) : -1];
}

/** @return array{codigo: int, cuerpo: string} */
function demo_pedir(string $host, string $ruta, string $galleta, ?array $post = null, array $cabeceras = []): array
{
    global $puerto;
    $c = curl_init("http://$host:$puerto/$ruta");
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_RESOLVE => ["$host:$puerto:127.0.0.1"],
        CURLOPT_COOKIEFILE => $galleta, CURLOPT_COOKIEJAR => $galleta, CURLOPT_HTTPHEADER => $cabeceras, CURLOPT_TIMEOUT => 60]);
    if ($post !== null) {
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    return ['cuerpo' => (string) curl_exec($c), 'codigo' => (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE)];
}

final class RecuentoDemo
{
    public static int $ok = 0;
    public static int $fallos = 0;
}

function demo_comprobar(bool $condicion, string $descripcion, string $detalle = ''): void
{
    if ($condicion) {
        RecuentoDemo::$ok++;
        echo "  ok    $descripcion\n";
        return;
    }
    RecuentoDemo::$fallos++;
    echo "  FALLO $descripcion $detalle\n";
    if (getenv('GITHUB_ACTIONS')) {
        echo '::error title=Demo::' . str_replace("\n", ' ', "$descripcion $detalle") . "\n";
    }
}

/** Entra y devuelve el token CSRF del panel ('' si no entró). */
function demo_entrar(string $host, string $usuario, string $clave, string $galleta): string
{
    @unlink($galleta);
    demo_pedir($host, '', $galleta);
    $r = demo_pedir($host, 'controller/usuario/controlador_iniciar_sesion.php', $galleta, ['u' => $usuario, 'c' => $clave]);
    if (trim($r['cuerpo']) !== '1') {
        return '';
    }
    return preg_match('/name="csrf-token" content="([^"]+)"/', demo_pedir($host, 'view/index.php', $galleta)['cuerpo'], $m) === 1 ? $m[1] : '';
}

$tmp = sys_get_temp_dir() . '/demo_' . getmypid();
@mkdir($tmp);
$slug = 'demo-' . getmypid();
$host = "$slug.prueba.test";
$tenant = static fn (string $col): string => (string) $servidor->query("SELECT $col FROM `$maestro`.tenants WHERE slug = " . $servidor->quote($slug))->fetchColumn();
$existe = static function (string $base) use ($servidor): bool {
    $q = $servidor->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $q->execute([$base]);
    return (int) $q->fetchColumn() === 1;
};

echo "Demo y conversión en cliente ($slug)\n";
$alta = demo_herramienta(
    'tools/alta_tenant.php',
    "--slug=$slug",
    '--razon=Colegio Interesado',
    '--email=interesado@example.com',
    '--admin-dni=12345678',
    '--admin-nombres=Ana',
    '--admin-apellidos=Pérez',
    '--demo'
);
$claveDemo = preg_match('/Contraseña:\s+(\S+)/', $alta['salida'], $m) === 1 ? $m[1] : '';
$baseDemo = $tenant('base_datos');
demo_comprobar($claveDemo !== '' && $tenant('demo') === '1', 'la demo se crea marcada como demo', $alta['salida']);

$token = demo_entrar($host, 'admin', $claveDemo, "$tmp/demo.txt");
$alumnos = demo_pedir($host, 'controller/alumnos/controlador_listar_alumnos.php', "$tmp/demo.txt", [], ["X-CSRF-Token: $token"])['cuerpo'];
$acceso = demo_pedir($host, 'index.php', "$tmp/x.txt")['cuerpo'];
demo_comprobar(
    $token !== '' && str_contains($alumnos, '70000001') && str_contains($acceso, 'COLEGIO INTERESADO'),
    'el colegio entra con su administrador y ve los datos de ejemplo con su nombre'
);
demo_comprobar(
    demo_entrar($host, 'usuario10', 'Prueba.2026', "$tmp/x.txt") === '' && demo_entrar($host, 'usuario9', 'Prueba.2026', "$tmp/x.txt") === '',
    'las cuentas del ejemplo no entran con la contraseña publicada en el repositorio'
);

$noDemo = demo_herramienta(
    'tools/convertir_demo.php',
    '--tenant=colegio-a',
    '--razon=X',
    '--email=x@example.com',
    '--admin-dni=12345678',
    '--admin-nombres=A',
    '--admin-apellidos=B'
);
demo_comprobar($noDemo['codigo'] !== 0 && str_contains($noDemo['salida'], 'no es una demo'), 'un colegio real nunca se «convierte» (no se reemplazan sus datos)', $noDemo['salida']);

$conversion = demo_herramienta(
    'tools/convertir_demo.php',
    "--tenant=$slug",
    '--razon=Colegio Interesado SAC',
    '--email=direccion@example.com',
    '--admin-dni=87654321',
    '--admin-nombres=Rosa',
    '--admin-apellidos=Quispe'
);
$claveCliente = preg_match('/Contraseña:\s+(\S+)/', $conversion['salida'], $m) === 1 ? $m[1] : '';
$baseCliente = $tenant('base_datos');
demo_comprobar(
    $claveCliente !== '' && $tenant('demo') === '0' && $tenant('estado') === 'ACTIVO' && $baseCliente !== $baseDemo,
    'la conversión deja el colegio como cliente, en una base nueva',
    $conversion['salida']
);
demo_comprobar(!$existe($baseDemo) && $existe($baseCliente), 'la base de la demo (datos ficticios) ya no existe');
$token = demo_entrar($host, 'admin', $claveCliente, "$tmp/cliente.txt");
$alumnos = demo_pedir($host, 'controller/alumnos/controlador_listar_alumnos.php', "$tmp/cliente.txt", [], ["X-CSRF-Token: $token"])['cuerpo'];
demo_comprobar($token !== '' && !str_contains($alumnos, '70000001'), 'el administrador real entra en la misma dirección, sin datos de ejemplo');
demo_comprobar(demo_entrar($host, 'admin', $claveDemo, "$tmp/x.txt") === '', 'la contraseña de la demo ya no sirve');
$otraVez = demo_herramienta(
    'tools/convertir_demo.php',
    "--tenant=$slug",
    '--razon=X',
    '--email=x@example.com',
    '--admin-dni=12345678',
    '--admin-nombres=A',
    '--admin-apellidos=B'
);
demo_comprobar($otraVez['codigo'] !== 0 && $tenant('base_datos') === $baseCliente, 'convertirlo otra vez se rechaza (ya tiene datos reales)');

// Limpieza (un DELETE de varias tablas con alias necesita la base seleccionada).
$servidor->exec("USE `$maestro`");
foreach (['facturas', 'consumos', 'suscripciones'] as $dependiente) {
    $servidor->exec("DELETE x FROM $dependiente x JOIN tenants t ON t.id = x.tenant_id WHERE t.slug = " . $servidor->quote($slug));
}
$servidor->exec('DELETE FROM tenants WHERE slug = ' . $servidor->quote($slug));
$servidor->exec("DROP DATABASE IF EXISTS `$baseCliente`");
$servidor->exec("DROP DATABASE IF EXISTS `$baseDemo`");
array_map('unlink', glob("$tmp/*") ?: []);
@rmdir($tmp);
echo "\n" . RecuentoDemo::$ok . ' comprobaciones correctas, ' . RecuentoDemo::$fallos . " fallos\n";
exit(RecuentoDemo::$fallos === 0 ? 0 : 1);
