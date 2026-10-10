<?php

/**
 * Suite de aislamiento entre instituciones (Fase 4, hito 4.9): el tenant A nunca ve datos del B.
 * Puerta de salida de la fase: sin esta prueba en verde no se da de alta un segundo colegio.
 *
 * Necesita un servidor en modo múltiple (MODO_TENANT=multiple, TENANT_DOMINIO=prueba.test) y dos
 * bases con los MISMOS datos de prueba: los mismos usuarios e ids en las dos, que es el caso difícil
 * (una sesión de A con el usu_id 9 tiene sentido en B). La prueba registra los tenants en la maestra
 * y marca un alumno de B para distinguir las bases.
 *
 * Uso:  BASE_URL=http://127.0.0.1:8098/ DB_HOST=… DB_PORT=… DB_USER=… DB_PASS=…
 *       MAESTRO_DB_NAME=… DB_NAME_A=… DB_NAME_B=… php tests/E2E/aislamiento.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const DOMINIO = 'prueba.test';
const LOGIN = 'controller/usuario/controlador_iniciar_sesion.php';
const LISTAR = 'controller/alumnos/controlador_listar_alumnos.php';
const MARCA = 'AISLAMIENTO B';

// Solo el puerto de BASE_URL: cada petición va al nombre de su institución, resuelto a 127.0.0.1.
$puerto = (int) (parse_url(getenv('BASE_URL') ?: 'http://127.0.0.1:8098/', PHP_URL_PORT) ?: 80);
$entorno = static fn (string $clave): string => (string) (getenv($clave) ?: '');

// --- Preparación: tenants en la maestra y un dato que solo existe en B -------------------------
$pdo = static fn (string $bd): PDO => new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8', $entorno('DB_HOST') ?: '127.0.0.1', (int) ($entorno('DB_PORT') ?: 3306), $bd),
    $entorno('DB_USER'),
    $entorno('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$maestro = $pdo($entorno('MAESTRO_DB_NAME'));
$maestro->exec("DELETE FROM tenants WHERE slug IN ('colegio-a', 'colegio-b', 'suspendido')");
$alta = $maestro->prepare('INSERT INTO tenants (slug, razon_social, base_datos, estado) VALUES (?, ?, ?, ?)');
$alta->execute(['colegio-a', 'Colegio A', $entorno('DB_NAME_A'), 'ACTIVO']);
$alta->execute(['colegio-b', 'Colegio B', $entorno('DB_NAME_B'), 'ACTIVO']);
// El suspendido apunta a la base de A: si llegara a entrar, vería datos reales.
$alta->execute(['suspendido', 'Suspendido', $entorno('DB_NAME_A') . '_x', 'SUSPENDIDO']);
$pdo($entorno('DB_NAME_B'))->exec("UPDATE alumnos SET alum_nombre = '" . MARCA . "' WHERE alum_dni = '70000001'");

// --- Cliente HTTP --------------------------------------------------------------------------------

/**
 * Petición a http://<host>:<puerto>/<ruta> con el host resuelto a 127.0.0.1: las cookies quedan
 * guardadas a nombre de cada institución, como en un navegador.
 *
 * @param array<string, string>|null $post
 * @param list<string> $cabeceras
 * @return array{codigo: int, cuerpo: string}
 */
function pedir(string $host, string $ruta, string $galleta, ?array $post = null, array $cabeceras = []): array
{
    global $puerto;
    $c = curl_init("http://$host:$puerto/$ruta");
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_RESOLVE => ["$host:$puerto:127.0.0.1"],
        CURLOPT_HTTPHEADER => $cabeceras,
        CURLOPT_COOKIEFILE => $galleta,
        CURLOPT_COOKIEJAR => $galleta,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post !== null) {
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $cuerpo = (string) curl_exec($c);
    $codigo = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
    return ['codigo' => $codigo, 'cuerpo' => $cuerpo];
}

/** Recuento de comprobaciones (estático: comprobar() se llama desde funciones). */
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
        echo '::error title=Aislamiento::' . str_replace("\n", ' ', "$descripcion $detalle") . "\n";
    }
}

/**
 * La galleta de sesión copiada a otro host (lo que haría quien la roba o la reenvía). Formato
 * Netscape: el dominio es la primera columna, con «#HttpOnly_» delante si la cookie lo es.
 */
function copiarGalleta(string $origen, string $destino, string $hostDestino): void
{
    $copia = [];
    foreach (file($origen, FILE_IGNORE_NEW_LINES) ?: [] as $linea) {
        $columnas = explode("\t", $linea);
        if (count($columnas) === 7) {
            $columnas[0] = (str_starts_with($columnas[0], '#HttpOnly_') ? '#HttpOnly_' : '') . $hostDestino;
        }
        $copia[] = implode("\t", $columnas);
    }
    file_put_contents($destino, implode("\n", $copia) . "\n");
}

/** Inicia sesión como administrador y devuelve el token CSRF del panel. */
function entrar(string $host, string $galleta): string
{
    pedir($host, '', $galleta);
    $r = pedir($host, LOGIN, $galleta, ['u' => 'usuario9', 'c' => 'Prueba.2026']);
    comprobar(trim($r['cuerpo']) === '1', "$host: el administrador inicia sesión", "({$r['codigo']}: {$r['cuerpo']})");
    $panel = pedir($host, 'view/index.php', $galleta)['cuerpo'];
    return preg_match('/name="csrf-token" content="([^"]+)"/', $panel, $m) === 1 ? $m[1] : '';
}

$tmp = sys_get_temp_dir() . '/aislamiento_' . getmypid();
@mkdir($tmp);
$hostA = 'colegio-a.' . DOMINIO;
$hostB = 'colegio-b.' . DOMINIO;
$galletaA = "$tmp/a.txt";
$galletaB = "$tmp/b.txt";

echo "Aislamiento entre instituciones\n";
$tokenA = entrar($hostA, $galletaA);
$tokenB = entrar($hostB, $galletaB);

$alumnosA = pedir($hostA, LISTAR, $galletaA, [], ["X-CSRF-Token: $tokenA"]);
$alumnosB = pedir($hostB, LISTAR, $galletaB, [], ["X-CSRF-Token: $tokenB"]);
comprobar($alumnosA['codigo'] === 200 && str_contains($alumnosA['cuerpo'], '70000001'), 'A lista sus alumnos');
comprobar(!str_contains($alumnosA['cuerpo'], MARCA), 'A no ve el alumno que solo existe en la base de B');
comprobar(str_contains($alumnosB['cuerpo'], MARCA), 'B ve su propio dato (la conexión abrió la base de B)');

// La sesión de A presentada en B: mismos usu_id y rol en las dos bases, pero no es de B.
copiarGalleta($galletaA, "$tmp/a_en_b.txt", $hostB);
$robada = pedir($hostB, LISTAR, "$tmp/a_en_b.txt", [], ["X-CSRF-Token: $tokenA"]);
comprobar($robada['codigo'] === 401, 'la sesión de A no sirve en B (401)', "({$robada['codigo']})");
comprobar(!str_contains($robada['cuerpo'], '70000001'), 'y no devuelve ningún dato');
$reporte = pedir($hostB, 'view/MPDF/REPORTE/kardex.php?codigo=38', "$tmp/a_en_b.txt");
comprobar($reporte['codigo'] === 401, 'tampoco abre reportes PDF de B (401)', "({$reporte['codigo']})");
$panel = pedir($hostB, 'view/index.php', "$tmp/a_en_b.txt");
comprobar(!str_contains($panel['cuerpo'], 'csrf-token'), 'ni el panel de B');

// Quien presentó la galleta en otro colegio no cierra la sesión legítima de A.
$sigue = pedir($hostA, LISTAR, $galletaA, [], ["X-CSRF-Token: $tokenA"]);
comprobar($sigue['codigo'] === 200 && str_contains($sigue['cuerpo'], '70000001'), 'la sesión de A sigue activa en A');

// Hosts sin institución: la misma respuesta para el inexistente, el suspendido, la IP y el dominio base.
$respuestas = [];
foreach (['nadie.' . DOMINIO, 'suspendido.' . DOMINIO, '127.0.0.1', DOMINIO, 'colegio-a.otro.' . DOMINIO] as $host) {
    $r = pedir($host, 'index.php', "$tmp/vacia.txt");
    $respuestas[$host] = $r['codigo'] . ' ' . trim($r['cuerpo']);
    comprobar($r['codigo'] === 404, "«{$host}» no corresponde a ninguna institución (404)", "({$r['codigo']})");
}
comprobar(count(array_unique($respuestas)) === 1, 'el suspendido responde igual que el inexistente (no revela que existe)');
$login = pedir('suspendido.' . DOMINIO, LOGIN, "$tmp/vacia.txt", ['u' => 'usuario9', 'c' => 'Prueba.2026']);
comprobar($login['codigo'] === 404 && trim($login['cuerpo']) !== '1', 'en el suspendido no se puede iniciar sesión');

// Límite de intentos por institución: bloquear a «usuario9» en B no lo bloquea en A.
for ($i = 0; $i < 5; $i++) {
    pedir($hostB, LOGIN, "$tmp/fallos.txt", ['u' => 'usuario9', 'c' => 'mala']);
}
$bloqueado = pedir($hostB, LOGIN, "$tmp/fallos.txt", ['u' => 'usuario9', 'c' => 'Prueba.2026']);
comprobar($bloqueado['codigo'] === 429, 'cinco fallos bloquean a usuario9 en B (429)', "({$bloqueado['codigo']})");
$libre = pedir($hostA, LOGIN, "$tmp/otro.txt", ['u' => 'usuario9', 'c' => 'Prueba.2026']);
comprobar(trim($libre['cuerpo']) === '1', 'usuario9 de A sigue pudiendo entrar', "({$libre['codigo']}: {$libre['cuerpo']})");

// El suspendido apunta a una base que no existe: no se deja en la maestra (tools/migrar_tenants.php lo recorrería).
$maestro->exec("DELETE FROM tenants WHERE slug = 'suspendido'");
array_map('unlink', glob("$tmp/*") ?: []);
@rmdir($tmp);
echo "\n" . Recuento::$ok . ' comprobaciones correctas, ' . Recuento::$fallos . " fallos\n";
exit(Recuento::$fallos === 0 ? 0 : 1);
