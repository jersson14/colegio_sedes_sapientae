<?php

/**
 * Empaquetado comercial (Fase 4B: 4B.2 límites, 4B.3 estados, 4B.7 exportación) contra el servidor en modo
 * múltiple, sobre colegio-a. Mismo entorno que tests/E2E/aislamiento.php (después de ella). Deja colegio-a
 * como estaba: ACTIVO y sin plan.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const HOST_A = 'colegio-a.prueba.test';
$puerto = (int) (parse_url(getenv('BASE_URL') ?: 'http://127.0.0.1:8098/', PHP_URL_PORT) ?: 80);
$entorno = static fn (string $c): string => (string) (getenv($c) ?: '');
$conectar = static fn (string $bd): PDO => new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8', $entorno('DB_HOST') ?: '127.0.0.1', (int) ($entorno('DB_PORT') ?: 3306), $bd),
    $entorno('DB_USER'),
    $entorno('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$maestro = $conectar($entorno('MAESTRO_DB_NAME'));

/**
 * @param array<string, mixed>|null $post
 * @param list<string> $cabeceras
 * @return array{codigo: int, cuerpo: string, tipo: string}
 */
function comercial_pedir(string $ruta, string $galleta, ?array $post = null, array $cabeceras = []): array
{
    global $puerto;
    $c = curl_init('http://' . HOST_A . ":$puerto/$ruta");
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_RESOLVE => [HOST_A . ":$puerto:127.0.0.1"],
        CURLOPT_COOKIEFILE => $galleta, CURLOPT_COOKIEJAR => $galleta, CURLOPT_HTTPHEADER => $cabeceras, CURLOPT_TIMEOUT => 60]);
    if ($post !== null) {
        // Con un CURLFile va como multipart; si no, como formulario normal.
        $multipart = array_filter($post, static fn ($v): bool => $v instanceof CURLFile) !== [];
        curl_setopt($c, CURLOPT_POSTFIELDS, $multipart ? $post : http_build_query($post));
    }
    $cuerpo = (string) curl_exec($c);
    return ['codigo' => (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE), 'cuerpo' => $cuerpo, 'tipo' => (string) curl_getinfo($c, CURLINFO_CONTENT_TYPE)];
}

final class RecuentoComercial
{
    public static int $ok = 0;
    public static int $fallos = 0;
}

function comercial_comprobar(bool $condicion, string $descripcion, string $detalle = ''): void
{
    if ($condicion) {
        RecuentoComercial::$ok++;
        echo "  ok    $descripcion\n";
        return;
    }
    RecuentoComercial::$fallos++;
    echo "  FALLO $descripcion $detalle\n";
    if (getenv('GITHUB_ACTIONS')) {
        echo '::error title=Comercial::' . str_replace("\n", ' ', "$descripcion $detalle") . "\n";
    }
}

/** Inicia sesión y devuelve el token CSRF del panel. */
function comercial_entrar(string $usuario, string $galleta): string
{
    @unlink($galleta);
    comercial_pedir('', $galleta);
    comercial_pedir('controller/usuario/controlador_iniciar_sesion.php', $galleta, ['u' => $usuario, 'c' => 'Prueba.2026']);
    $panel = comercial_pedir('view/index.php', $galleta)['cuerpo'];
    return preg_match('/name="csrf-token" content="([^"]+)"/', $panel, $m) === 1 ? $m[1] : '';
}

/** Condiciones de colegio-a directamente en la maestra (lo que hace el panel de superadministrador). */
function comercial_condiciones_a(PDO $maestro, string $estado, ?int $maxAlumnos, ?int $maxMb, ?string $suspendidoHace = null): void
{
    $maestro->exec("DELETE s FROM suscripciones s JOIN tenants t ON t.id = s.tenant_id WHERE t.slug = 'colegio-a'");
    $maestro->prepare("INSERT INTO planes (codigo, nombre, max_alumnos, max_almacenamiento_mb) VALUES ('E2E_COMERCIAL', 'Plan E2E', ?, ?)
        ON DUPLICATE KEY UPDATE max_alumnos = VALUES(max_alumnos), max_almacenamiento_mb = VALUES(max_almacenamiento_mb), activo = 1")
        ->execute([$maxAlumnos, $maxMb]);
    $maestro->exec("INSERT INTO suscripciones (tenant_id, plan_id, inicio) SELECT t.id, p.id, CURDATE() FROM tenants t, planes p
        WHERE t.slug = 'colegio-a' AND p.codigo = 'E2E_COMERCIAL'");
    $maestro->prepare("UPDATE tenants SET estado = ?, prueba_hasta = IF(? = 'PRUEBA', '2030-01-31', NULL),
        suspendido_desde = IF(? IS NULL, NULL, NOW() - INTERVAL ? DAY) WHERE slug = 'colegio-a'")
        ->execute([$estado, $estado, $suspendidoHace, (int) $suspendidoHace]);
}

$tmp = sys_get_temp_dir() . '/comercial_' . getmypid();
@mkdir($tmp);
$g = "$tmp/admin.txt";
$alta = 'controller/alumnos/controlador_registrar_alumno.php';
$listar = 'controller/alumnos/controlador_listar_alumnos.php';
$baseA = (string) $maestro->query("SELECT base_datos FROM tenants WHERE slug = 'colegio-a'")->fetchColumn();
$activos = (int) $conectar($baseA)->query("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'")->fetchColumn();

echo "Empaquetado comercial (colegio-a)\n";

// 4B.2: límites del plan, aplicados en el servidor.
comercial_condiciones_a($maestro, 'ACTIVO', $activos, null);
$token = comercial_entrar('usuario9', $g);
$h = ["X-CSRF-Token: $token"];
$lleno = comercial_pedir($alta, $g, ['dni' => '79999999'], $h);
$motivo = (string) (json_decode($lleno['cuerpo'], true)['error'] ?? '');
comercial_comprobar($lleno['codigo'] === 402 && str_contains($motivo, "límite de $activos alumnos"), "con $activos alumnos activos y un plan de $activos, el alta se frena (402)", "({$lleno['codigo']}: {$lleno['cuerpo']})");
comercial_comprobar(comercial_pedir($listar, $g, [], $h)['codigo'] === 200, 'consultar sigue funcionando');
comercial_condiciones_a($maestro, 'ACTIVO', $activos + 1, null);
comercial_comprobar(comercial_pedir($alta, $g, ['dni' => '79999999'], $h)['codigo'] !== 402, 'con un cupo libre el alta llega al controlador');

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
file_put_contents("$tmp/logo.png", $png);
comercial_condiciones_a($maestro, 'ACTIVO', null, 0);
$sinEspacio = comercial_pedir(
    'controller/empresa/controlador_empresa_modificar_foto.php',
    $g,
    ['id' => '1', 'nombrefoto' => 'logo.png', 'fotoactual' => '', 'foto' => new CURLFile("$tmp/logo.png", 'image/png', 'logo.png')],
    $h
);
comercial_comprobar($sinEspacio['codigo'] === 402 && str_contains((string) (json_decode($sinEspacio['cuerpo'], true)['error'] ?? ''), 'espacio'), 'sin espacio en el plan, una subida se rechaza (402)', "({$sinEspacio['codigo']})");

// 4B.3: MOROSO consulta e imprime, pero no da altas.
comercial_condiciones_a($maestro, 'MOROSO', null, null);
$moroso = comercial_pedir($alta, $g, ['dni' => '79999999'], $h);
comercial_comprobar($moroso['codigo'] === 402 && str_contains((string) (json_decode($moroso['cuerpo'], true)['error'] ?? ''), 'pago pendiente'), 'MOROSO: el alta se frena con el motivo', "({$moroso['codigo']})");
comercial_comprobar(comercial_pedir($listar, $g, [], $h)['codigo'] === 200, 'MOROSO: consulta');
$kardex = comercial_pedir('view/MPDF/REPORTE/kardex.php?codigo=38', $g);
comercial_comprobar($kardex['codigo'] === 200 && str_starts_with($kardex['cuerpo'], '%PDF'), 'MOROSO: imprime reportes', "({$kardex['codigo']})");
comercial_comprobar(str_contains(comercial_pedir('view/index.php', $g)['cuerpo'], 'id="aviso_moroso"'), 'MOROSO: el panel avisa del pago pendiente');

// PRUEBA: aviso con la fecha de fin.
comercial_condiciones_a($maestro, 'PRUEBA', null, null);
$prueba = comercial_pedir('view/index.php', $g)['cuerpo'];
comercial_comprobar(str_contains($prueba, 'id="aviso_prueba"') && str_contains($prueba, '31/01/2030'), 'PRUEBA: el panel avisa hasta cuándo');

// SUSPENDIDO: solo el administrador, solo para exportar, durante la ventana.
comercial_condiciones_a($maestro, 'SUSPENDIDO', null, null, '1');
$token = comercial_entrar('usuario9', $g);
$h = ["X-CSRF-Token: $token"];
$suspendido = comercial_pedir('view/index.php', $g)['cuerpo'];
comercial_comprobar(str_contains($suspendido, 'id="suspendido"') && str_contains($suspendido, 'id="descargar_datos"'), 'SUSPENDIDO: el administrador entra a una página con la descarga de sus datos');
// Por GET: la página de suspensión no lleva token CSRF (un POST sin él daría 419 antes de llegar aquí).
comercial_comprobar(comercial_pedir($listar, $g)['codigo'] === 403, 'SUSPENDIDO: nada más responde (403)');
comercial_comprobar(comercial_pedir('view/MPDF/REPORTE/kardex.php?codigo=38', $g)['codigo'] === 403, 'SUSPENDIDO: tampoco los reportes');
$exportacion = comercial_pedir('controller/exportacion/controlador_exportar_datos.php', $g);
file_put_contents("$tmp/datos.zip", $exportacion['cuerpo']);
$zip = new ZipArchive();
$abierto = $zip->open("$tmp/datos.zip") === true;
$usuarios = $abierto ? (string) $zip->getFromName('datos/usuario.csv') : '';
$alumnosCsv = $abierto ? (string) $zip->getFromName('datos/alumnos.csv') : '';
comercial_comprobar(
    $exportacion['codigo'] === 200 && str_contains($exportacion['tipo'], 'zip') && $abierto && $zip->locateName('LEEME.txt') !== false,
    'SUSPENDIDO: descarga sus datos en un zip',
    "({$exportacion['codigo']} {$exportacion['tipo']})"
);
comercial_comprobar(substr_count($alumnosCsv, "\n") === 1 + (int) $conectar($baseA)->query('SELECT COUNT(*) FROM alumnos')->fetchColumn(), 'el CSV de alumnos tiene todas las filas');
comercial_comprobar(str_contains($usuarios, 'usu_usuario') && !str_contains($usuarios, 'usu_contra') && !str_contains($usuarios, '$2y$'), 'sin las contraseñas (ni sus hashes)');
if ($abierto) {
    $zip->close();
}
$auditada = (int) $maestro->query("SELECT COUNT(*) FROM auditoria WHERE accion = 'EXPORTACION' AND tenant = 'colegio-a' AND fecha > NOW() - INTERVAL 5 MINUTE")->fetchColumn();
comercial_comprobar($auditada >= 1, 'la exportación queda en la auditoría');
comercial_entrar('usuario10', "$tmp/docente.txt");
$docente = comercial_pedir('view/index.php', "$tmp/docente.txt")['cuerpo'];
comercial_comprobar(str_contains($docente, 'id="suspendido"') && !str_contains($docente, 'id="descargar_datos"'), 'un docente ve el aviso, sin la descarga');
comercial_comprobar(comercial_pedir('controller/exportacion/controlador_exportar_datos.php', "$tmp/docente.txt")['codigo'] === 403, 'y no puede exportar (403)');

comercial_condiciones_a($maestro, 'SUSPENDIDO', null, null, '40');
comercial_comprobar(comercial_pedir('index.php', "$tmp/fuera.txt")['codigo'] === 404, 'pasada la ventana de exportación, la institución ya no responde (404)');

// Se deja como estaba.
$maestro->exec("DELETE s FROM suscripciones s JOIN tenants t ON t.id = s.tenant_id WHERE t.slug = 'colegio-a'");
$maestro->exec("UPDATE tenants SET estado = 'ACTIVO', prueba_hasta = NULL, suspendido_desde = NULL WHERE slug = 'colegio-a'");
$maestro->exec("DELETE FROM planes WHERE codigo = 'E2E_COMERCIAL'");
comercial_comprobar(comercial_pedir('index.php', "$tmp/final.txt")['codigo'] === 200, 'colegio-a vuelve a estar activo');

array_map('unlink', glob("$tmp/*") ?: []);
@rmdir($tmp);
echo "\n" . RecuentoComercial::$ok . ' comprobaciones correctas, ' . RecuentoComercial::$fallos . " fallos\n";
exit(RecuentoComercial::$fallos === 0 ? 0 : 1);
