<?php

/**
 * Panel de superadministrador (Fase 4, hito 4.8), de punta a punta contra el servidor en modo múltiple.
 * Mismo entorno que tests/E2E/aislamiento.php (después de ella: usa colegio-a y colegio-b), más
 * COLEGIO_ENV (para crear la cuenta por consola) y SUPERADMIN_HOST=panel.prueba.test en ese colegio.env.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const PANEL = 'panel.prueba.test';
$puerto = (int) (parse_url(getenv('BASE_URL') ?: 'http://127.0.0.1:8098/', PHP_URL_PORT) ?: 80);
$entorno = static fn (string $c): string => (string) (getenv($c) ?: '');
$maestro = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8', $entorno('DB_HOST') ?: '127.0.0.1', (int) ($entorno('DB_PORT') ?: 3306), $entorno('MAESTRO_DB_NAME')),
    $entorno('DB_USER'),
    $entorno('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

/** @return array{codigo: int, cuerpo: string} */
function pedir(string $host, string $ruta, string $galleta, ?array $post = null): array
{
    global $puerto;
    $c = curl_init("http://$host:$puerto/$ruta");
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_RESOLVE => ["$host:$puerto:127.0.0.1"],
        CURLOPT_COOKIEFILE => $galleta, CURLOPT_COOKIEJAR => $galleta, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60]);
    if ($post !== null) {
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $cuerpo = (string) curl_exec($c);
    return ['codigo' => (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE), 'cuerpo' => $cuerpo];
}

final class Recuento
{
    public static int $ok = 0;
    public static int $fallos = 0;
}

function comprobar(bool $condicion, string $descripcion, string $detalle = ''): void
{
    if ($condicion) {
        Recuento::$ok++;
        echo "  ok    $descripcion\n";
        return;
    }
    Recuento::$fallos++;
    echo "  FALLO $descripcion $detalle\n";
    if (getenv('GITHUB_ACTIONS')) {
        echo '::error title=Superadministrador::' . str_replace("\n", ' ', "$descripcion $detalle") . "\n";
    }
}

function csrf(string $html): string
{
    return preg_match('/name="csrf" value="([^"]+)"/', $html, $m) === 1 ? $m[1] : '';
}

function consola(string ...$argumentos): string
{
    $comando = array_merge([PHP_BINARY, dirname(__DIR__, 2) . '/tools/crear_superadmin.php'], $argumentos);
    $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $t);
    $salida = is_resource($proceso) ? (string) stream_get_contents($t[1]) . (string) stream_get_contents($t[2]) : '';
    if (is_resource($proceso)) {
        proc_close($proceso);
    }
    return $salida;
}

$tmp = sys_get_temp_dir() . '/superadmin_' . getmypid();
@mkdir($tmp);
$usuario = 'e2e-' . getmypid();
$creada = consola("--usuario=$usuario", '--nombre=Prueba E2E');
$clave = preg_match('/Contraseña:\s+(\S+)/', $creada, $m) === 1 ? $m[1] : '';

echo "Panel de superadministrador\n";
comprobar($clave !== '', 'la cuenta se crea por consola y la contraseña se muestra una vez', $creada);

// Solo existe en su host: en el de un colegio, el mismo 404 que una institución inexistente.
$enColegio = pedir('colegio-a.prueba.test', 'superadmin/', "$tmp/x.txt");
comprobar($enColegio['codigo'] === 404 && trim($enColegio['cuerpo']) === 'Institución no encontrada.', 'en el host de un colegio no existe (404)');

$g = "$tmp/panel.txt";
$acceso = pedir(PANEL, 'superadmin/', $g);
comprobar($acceso['codigo'] === 200 && str_contains($acceso['cuerpo'], 'name="clave"'), 'en su host muestra el acceso');
$sinToken = pedir(PANEL, 'superadmin/', $g, ['accion' => 'entrar', 'usuario' => $usuario, 'clave' => $clave]);
comprobar($sinToken['codigo'] === 419, 'sin token CSRF no se procesa nada (419)', "({$sinToken['codigo']})");

$mala = pedir(PANEL, 'superadmin/', $g, ['csrf' => csrf($acceso['cuerpo']), 'accion' => 'entrar', 'usuario' => $usuario, 'clave' => 'mala']);
comprobar(str_contains($mala['cuerpo'], 'incorrectos') && !str_contains($mala['cuerpo'], 'tabla_instituciones'), 'con una contraseña mala no entra');

// Una sesión de administrador de un colegio no vale aquí (otra cookie, otra tabla de cuentas).
$deColegio = "$tmp/colegio.txt";
pedir('colegio-a.prueba.test', '', $deColegio);
pedir('colegio-a.prueba.test', 'controller/usuario/controlador_iniciar_sesion.php', $deColegio, ['u' => 'usuario9', 'c' => 'Prueba.2026']);
$galletaColegio = (string) file_get_contents($deColegio);
file_put_contents("$tmp/colegio_en_panel.txt", str_replace('colegio-a.prueba.test', PANEL, $galletaColegio));
comprobar(!str_contains(pedir(PANEL, 'superadmin/', "$tmp/colegio_en_panel.txt")['cuerpo'], 'tabla_instituciones'), 'la sesión del administrador de un colegio no abre el panel');

$panel = pedir(PANEL, 'superadmin/', $g, ['csrf' => csrf($mala['cuerpo']), 'accion' => 'entrar', 'usuario' => $usuario, 'clave' => $clave]);
comprobar(
    str_contains($panel['cuerpo'], 'tabla_instituciones') && str_contains($panel['cuerpo'], 'data-slug="colegio-a"') && str_contains($panel['cuerpo'], 'data-slug="colegio-b"'),
    'con su cuenta entra y ve las instituciones'
);
$baseA = (string) $maestro->query("SELECT base_datos FROM tenants WHERE slug = 'colegio-a'")->fetchColumn();
$activosA = (int) $maestro->query("SELECT COUNT(*) FROM `$baseA`.alumnos WHERE alum_estatus = 'SI'")->fetchColumn();
comprobar(preg_match('#data-slug="colegio-a".*?<td>' . $activosA . '</td>#s', $panel['cuerpo']) === 1, "con sus cifras ($activosA alumnos activos en colegio-a, leídos de su base)");

// Suspender corta el acceso al colegio; reactivar lo devuelve. Los datos no se tocan.
$token = csrf($panel['cuerpo']);
pedir(PANEL, 'superadmin/', $g, ['csrf' => $token, 'accion' => 'estado', 'slug' => 'colegio-b', 'estado' => 'SUSPENDIDO']);
comprobar(pedir('colegio-b.prueba.test', 'index.php', "$tmp/b.txt")['codigo'] === 404, 'al suspender colegio-b, su dirección responde 404');
$reactivado = pedir(PANEL, 'superadmin/', $g, ['csrf' => $token, 'accion' => 'estado', 'slug' => 'colegio-b', 'estado' => 'ACTIVO']);
comprobar(pedir('colegio-b.prueba.test', 'index.php', "$tmp/b2.txt")['codigo'] === 200, 'al reactivarlo vuelve a abrir');
$inexistente = pedir(PANEL, 'superadmin/', $g, ['csrf' => $token, 'accion' => 'estado', 'slug' => 'no-existe', 'estado' => 'ACTIVO']);
comprobar(str_contains($inexistente['cuerpo'], 'No existe la institución'), 'un colegio inexistente se informa, no se crea');
comprobar(substr_count($reactivado['cuerpo'], '<td>ESTADO</td>') >= 2 && str_contains($reactivado['cuerpo'], 'SUSPENDIDO → ACTIVO')
    && str_contains($reactivado['cuerpo'], "<td>$usuario</td>"), 'la auditoría registra quién cambió qué');

// Alta desde el panel.
$slugNuevo = 'panel-' . getmypid();
$alta = pedir(PANEL, 'superadmin/', $g, ['csrf' => $token, 'accion' => 'alta', 'slug' => $slugNuevo, 'razon' => 'Colegio desde el panel',
    'email' => 'panel@example.com', 'admin_dni' => '12345678', 'admin_nombres' => 'Ana', 'admin_apellidos' => 'Pérez', 'estado' => 'ACTIVO']);
$claveColegio = preg_match('/contraseña inicial: (\S+)/', $alta['cuerpo'], $m) === 1 ? $m[1] : '';
comprobar($claveColegio !== '' && str_contains($alta['cuerpo'], "data-slug=\"$slugNuevo\""), 'da de alta una institución desde el panel', mb_substr(strip_tags($alta['cuerpo']), 0, 200));
pedir("$slugNuevo.prueba.test", '', "$tmp/nuevo.txt");
$entra = pedir("$slugNuevo.prueba.test", 'controller/usuario/controlador_iniciar_sesion.php', "$tmp/nuevo.txt", ['u' => 'admin', 'c' => $claveColegio]);
comprobar(trim($entra['cuerpo']) === '1', 'y su administrador entra con la contraseña inicial');
$repetida = pedir(PANEL, 'superadmin/', $g, ['csrf' => $token, 'accion' => 'alta', 'slug' => $slugNuevo, 'razon' => 'Otra',
    'email' => 'panel@example.com', 'admin_dni' => '12345678', 'admin_nombres' => 'Ana', 'admin_apellidos' => 'Pérez']);
comprobar(str_contains($repetida['cuerpo'], 'Ya existe'), 'un subdominio repetido se rechaza');

// Desactivar la cuenta por consola cierra la sesión abierta.
consola("--desactivar=$usuario");
comprobar(!str_contains(pedir(PANEL, 'superadmin/', $g)['cuerpo'], 'tabla_instituciones'), 'una cuenta desactivada pierde la sesión abierta');

// Límite de intentos: cinco fallos bloquean (aunque luego llegue la contraseña buena).
$cuentaBloqueo = 'bloqueo-' . getmypid();
$claveBloqueo = preg_match('/Contraseña:\s+(\S+)/', consola("--usuario=$cuentaBloqueo", '--nombre=Bloqueo'), $m) === 1 ? $m[1] : '';
$h = "$tmp/bloqueo.txt";
$pagina = pedir(PANEL, 'superadmin/', $h)['cuerpo'];
for ($i = 0; $i < 5; $i++) {
    $pagina = pedir(PANEL, 'superadmin/', $h, ['csrf' => csrf($pagina), 'accion' => 'entrar', 'usuario' => $cuentaBloqueo, 'clave' => 'mala'])['cuerpo'];
}
$bloqueada = pedir(PANEL, 'superadmin/', $h, ['csrf' => csrf($pagina), 'accion' => 'entrar', 'usuario' => $cuentaBloqueo, 'clave' => $claveBloqueo]);
comprobar(str_contains($bloqueada['cuerpo'], 'Demasiados intentos') && !str_contains($bloqueada['cuerpo'], 'tabla_instituciones'), 'cinco fallos bloquean la cuenta un rato');

// Limpieza: la institución de prueba, sus cuentas y su base.
$maestro->prepare('DELETE FROM tenants WHERE slug = ?')->execute([$slugNuevo]);
$maestro->exec('DROP DATABASE IF EXISTS `sge_' . str_replace('-', '_', $slugNuevo) . '`');
$maestro->prepare('DELETE FROM superadmins WHERE usuario IN (?, ?)')->execute([$usuario, $cuentaBloqueo]);
array_map('unlink', glob("$tmp/*") ?: []);
@rmdir($tmp);
echo "\n" . Recuento::$ok . ' comprobaciones correctas, ' . Recuento::$fallos . " fallos\n";
exit(Recuento::$fallos === 0 ? 0 : 1);
