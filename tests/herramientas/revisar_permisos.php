<?php
/**
 * Revisa controller/_permisos.php contra lo que cada rol realmente usa (Fase 9).
 *
 * Para cada rol: pantallas de su menú en view/index.php → archivos JS propios de cada pantalla + su script en línea
 * → controladores y reportes MPDF que aparecen en ellos, más las funciones de los JS globales (cargados por el index)
 * que esas pantallas llaman. A diferencia del generador anterior, una función solo se resuelve en los archivos
 * que la pantalla carga, no en cualquier archivo con una función del mismo nombre.
 *
 * Uso: php tests/herramientas/revisar_permisos.php [--json]
 * Imprime los permisos que sobran (concedidos pero no usados por ese rol) y los que faltan.
 */
$R = realpath(__DIR__ . '/../..');
chdir($R);
$leer = function ($f) { return is_file($f) ? file_get_contents($f) : ''; };
$re_ctrl = '#(?:\.\./)*((?:controller|view/MPDF/REPORTE)/[\w/\-ñÑáéíóú]+\.php)#u';
$controles = function ($texto) use ($re_ctrl) { preg_match_all($re_ctrl, $texto, $m); return array_unique($m[1]); };

// ---------- JS globales (los carga el index para todos los roles) ----------
$index = $leer('view/index.php');
// Scripts que el index carga solo para el administrador (en la misma línea de su if): no son de todos los roles
$solo_admin = [];
foreach (preg_split('/\R/', $index) as $linea) {
    if (preg_match('/==\s*"ADMINISTRADOR"/', $linea) && preg_match_all('#<script src="\.\./js/([\w\-ñ]+\.js)#u', $linea, $sa)) {
        $solo_admin = array_merge($solo_admin, $sa[1]);
    }
}
preg_match_all('#<script src="\.\./js/([\w\-ñ]+\.js)#u', $index, $m);
$globales = array_diff(array_unique($m[1]), $solo_admin);
$funciones_globales = [];   // nombre => cuerpo
$re_func = '/function\s+([A-Za-z_][\wñÑ]*)\s*\(/u';
foreach ($globales as $js) {
    $t = $leer("js/$js");
    preg_match_all($re_func, $t, $mm, PREG_OFFSET_CAPTURE);
    foreach ($mm[1] as $i => $f) {
        $ini = $mm[0][$i][1];
        $fin = isset($mm[0][$i + 1]) ? $mm[0][$i + 1][1] : strlen($t);
        $funciones_globales[$f[0]] = ($funciones_globales[$f[0]] ?? '') . substr($t, $ini, $fin - $ini);
    }
}
// Funciones definidas en los <script> del propio index (Panel_Contable, etc.): también son globales, y su
// definición no es una llamada (se quita del texto común con llaves balanceadas)
$definiciones_index = [];
if (preg_match_all('/function\s+([A-Za-z_][\wñÑ]*)\s*\([^)]*\)\s*\{/u', $index, $mm, PREG_OFFSET_CAPTURE)) {
    foreach ($mm[0] as $i => $ini) {
        $p = $ini[1] + strlen($ini[0]);
        $nivel = 1;
        while ($nivel > 0 && $p < strlen($index)) {
            $ch = $index[$p++];
            if ($ch === '{') { $nivel++; } elseif ($ch === '}') { $nivel--; }
        }
        $cuerpo = substr($index, $ini[1], $p - $ini[1]);
        $funciones_globales[$mm[1][$i][0]] = ($funciones_globales[$mm[1][$i][0]] ?? '') . $cuerpo;
        $definiciones_index[] = $cuerpo;
    }
}

// Controladores que se alcanzan desde un texto llamando funciones globales (cierre transitivo)
$alcance_global = function ($texto) use ($funciones_globales, $controles) {
    $vistos = [];
    $pend = [$texto];
    $res = [];
    while ($pend) {
        $t = array_pop($pend);
        $res = array_merge($res, $controles($t));
        preg_match_all('/\b([A-Za-z_][\wñÑ]*)\s*\(/u', $t, $mm);
        foreach (array_unique($mm[1]) as $f) {
            if (isset($funciones_globales[$f]) && !isset($vistos[$f])) {
                $vistos[$f] = true;
                $pend[] = $funciones_globales[$f];
            }
        }
    }
    return array_unique($res);
};

// ---------- Menú por rol ----------
$roles_vistas = [];
$roles_texto = [];          // texto del index visible para cada rol (menú y panel), para sus llamadas directas
$pila = [];
foreach (explode("\n", $index) as $linea) {
    if (preg_match('/if\s*\((\$_SESSION\[\'S_ROL\'\]\s*==\s*"\w+"(?:\s*\|\|\s*\$_SESSION\[\'S_ROL\'\]\s*==\s*"\w+")*)\)\s*\{/', $linea, $mm)) {
        preg_match_all('/"(\w+)"/', $mm[1], $r);
        $pila[] = $r[1];
        continue;
    }
    if ($pila && preg_match('/^\s*(<\?php)?\s*\}\s*(\?>)?\s*$/', $linea)) {
        array_pop($pila);
        continue;
    }
    // Fuera de los bloques por rol: lo ve cualquier rol (p. ej. el inicio llama listar_empresa() para todos)
    $roles = $pila ? end($pila) : ['*'];
    foreach ($roles as $rol) {
        $roles_texto[$rol] = ($roles_texto[$rol] ?? '') . $linea . "\n";
        if (preg_match_all("/cargar_contenido\('contenido_principal',\s*'([^']+)'\)/", $linea, $v)) {
            foreach ($v[1] as $vista) { $roles_vistas[$rol][$vista] = true; }
        }
    }
}

// ---------- Lo que usa cada rol ----------
$usa = [];   // ruta => [rol => [motivos]]
$comunes = $alcance_global(str_replace($definiciones_index, '', $roles_texto['*'] ?? ''));
foreach ($roles_vistas as $rol => $vistas) {
    // Llamadas directas del index: las de su bloque y las comunes a todos (panel de inicio, etc.)
    foreach ($alcance_global($roles_texto[$rol] ?? '') as $c) { $usa[$c][$rol]['index'] = true; }
    foreach ($comunes as $c) { $usa[$c][$rol]['index (todos)'] = true; }
    foreach (array_keys($vistas) as $vista) {
        $tv = $leer("view/$vista");
        $texto = $tv;
        preg_match_all('#<script src="\.\./js/([\w\-ñ]+\.js)#u', $tv, $mm);
        // Todo script que la pantalla carga cuenta completo (aunque el index ya lo cargue: la pantalla lo vuelve a ejecutar)
        foreach ($mm[1] as $js) { $texto .= "\n" . $leer("js/$js"); }
        foreach ($alcance_global($texto) as $c) { $usa[$c][$rol][$vista] = true; }
    }
}

$permisos_publicos = (require "controller/_permisos.php")["publico"] ?? [];
// ---------- Mapa de pantallas (Fase 13, paso 15): --generar escribe controller/_pantallas.php ----------
// Para dar o quitar pantallas completas a un rol desde Configuración → Permisos por rol: qué controladores usa cada
// pantalla, su nombre y sección en el menú, las pantallas de fábrica de cada rol y los controladores de su inicio.
if (in_array('--generar', $argv, true)) {
    $pantallas = [];
    $seccion = '';
    $lineas = explode("\n", $index);
    foreach ($lineas as $n => $linea) {
        if (preg_match('/menu-seccion">([^<]+)</u', $linea, $s)) { $seccion = trim($s[1]); }
        if (!preg_match_all("/cargar_contenido\('contenido_principal',\s*'([^']+)'\)/", $linea, $v)) { continue; }
        foreach ($v[1] as $vista) {
            if (isset($pantallas[$vista])) { continue; }
            $titulo = '';
            for ($k = $n; $k < min($n + 5, count($lineas)); $k++) {
                if (preg_match('#<p>\s*([^<]+?)\s*(<i|</p>)#u', $lineas[$k], $p)) { $titulo = trim($p[1]); break; }
            }
            $tv = $leer("view/$vista");
            $texto = $tv;
            preg_match_all('#<script src="\.\./js/([\w\-ñ]+\.js)#u', $tv, $mm);
            foreach ($mm[1] as $js) { $texto .= "\n" . $leer("js/$js"); }
            $ctrl = array_values(array_filter($alcance_global($texto), function ($c) use ($permisos_publicos) { return !in_array($c, $permisos_publicos, true); }));
            sort($ctrl);
            $pantallas[$vista] = ['titulo' => $titulo ?: basename($vista, '.php'), 'seccion' => $seccion ?: 'Otras', 'controladores' => $ctrl];
        }
    }
    $roles_mapa = [];
    foreach ($roles_vistas as $rol => $vistas) {
        $indice = [];
        foreach ($usa as $c => $por_rol) {
            foreach (array_keys($por_rol[$rol] ?? []) as $motivo) { if (strpos($motivo, 'index') === 0) { $indice[] = $c; break; } }
        }
        sort($indice);
        $roles_mapa[$rol] = ['vistas' => array_keys($vistas), 'indice' => array_values(array_unique($indice))];
    }
    $salida = "<?php\n// GENERADO por tests/herramientas/revisar_permisos.php --generar (no editar a mano).\n"
        . "// Pantallas del menú con los controladores que usan, y las pantallas e inicio de fábrica de cada rol.\nreturn "
        . var_export(['pantallas' => $pantallas, 'roles' => $roles_mapa], true) . ";\n";
    file_put_contents('controller/_pantallas.php', $salida);
    echo 'controller/_pantallas.php: ' . count($pantallas) . " pantallas\n";
    exit;
}

// ---------- Comparar con _permisos.php ----------
$permisos = require 'controller/_permisos.php';
$publicos = $permisos['publico'] ?? [];
$concedidos = $permisos['roles'];
$sobran = [];
$faltan = [];
foreach ($concedidos as $ruta => $roles) {
    foreach ($roles as $rol) {
        if (empty($usa[$ruta][$rol])) { $sobran[] = [$ruta, $rol]; }
    }
}
foreach ($usa as $ruta => $roles) {
    if (in_array($ruta, $publicos, true)) { continue; }
    foreach ($roles as $rol => $motivos) {
        if ($rol === 'ADMINISTRADOR') { continue; }
        if (!in_array($rol, $concedidos[$ruta] ?? [], true)) {
            $faltan[] = [$ruta, $rol, implode(', ', array_keys($motivos))];
        }
    }
}
if (in_array('--json', $argv, true)) {
    echo json_encode(['sobran' => $sobran, 'faltan' => $faltan, 'vistas' => array_map('array_keys', $roles_vistas), 'usa' => $usa], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
echo "Pantallas por rol: " . implode(', ', array_map(function ($r) use ($roles_vistas) { return "$r " . count($roles_vistas[$r]); }, array_keys($roles_vistas))) . "\n\n";
echo "== Permisos concedidos que el rol no usa (" . count($sobran) . ")\n";
foreach ($sobran as [$ruta, $rol]) { echo "  $rol  $ruta\n"; }
echo "\n== Permisos que faltan (" . count($faltan) . ")\n";
foreach ($faltan as [$ruta, $rol, $motivo]) { echo "  $rol  $ruta   <- $motivo\n"; }
