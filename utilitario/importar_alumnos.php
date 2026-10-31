<?php
/**
 * Importar estudiantes desde Excel (Fase 13, paso 14): las mismas reglas que el registro manual
 * (controller/alumnos/controlador_registrar_alumno.php). Primero se revisa el archivo fila por fila; luego se graban
 * solo las filas sin errores. Requiere controller/_seguridad.php.
 */
require_once __DIR__ . '/regional.php';
require_once __DIR__ . '/hoja_calculo.php';
require_once __DIR__ . '/duplicados.php';
require_once __DIR__ . '/../model/model_alumnos.php';

const IMPORTAR_MAX_FILAS = 1000;

/** Columnas de la plantilla: clave => [encabezado, obligatorio, ancho]. */
function importar_columnas() {
    $doc = nombre_documento();
    return [
        'dni'   => [$doc . ' del estudiante', true, 18], 'nombre' => ['Nombres', true, 20], 'apepa' => ['Apellido paterno', true, 18],
        'apema' => ['Apellido materno', false, 18], 'sexo' => ['Sexo (F o M)', true, 12], 'fechanaci' => ['Fecha de nacimiento (dd/mm/aaaa)', true, 22],
        'telf'  => ['Teléfono del estudiante', false, 16], 'direc' => ['Dirección', false, 28],
        'dnipa' => [$doc . ' del padre', false, 16], 'nompa' => ['Nombres y apellidos del padre', false, 28], 'celpa' => ['Celular del padre', false, 16],
        'dnima' => [$doc . ' de la madre', true, 16], 'nomma' => ['Nombres y apellidos de la madre', true, 28], 'celma' => ['Celular de la madre', true, 16],
    ];
}

/** Plantilla .xlsx: hoja de datos (solo encabezados) e instrucciones. */
function importar_plantilla() {
    $cols = importar_columnas();
    $c = config_regional();
    $instr = [
        ['Cómo llenar la plantilla'],
        ['1. Escriba un estudiante por fila en la hoja "Estudiantes", sin cambiar los encabezados.'],
        ['2. Las columnas obligatorias son: ' . implode(', ', array_map(function ($x) { return $x[0]; }, array_filter($cols, function ($x) { return $x[1]; }))) . '.'],
        ['3. ' . $c['doc_nombre'] . ': ' . $c['doc_min'] . ' a ' . $c['doc_max'] . ' caracteres (ej. ' . $c['doc_ejemplo'] . ').'],
        ['4. Sexo: F o M (también FEMENINO o MASCULINO).'],
        ['5. Fecha de nacimiento: dd/mm/aaaa, por ejemplo 15/03/2018.'],
        ['6. Celulares y teléfonos: solo números (pueden llevar +, espacios o guiones).'],
        ['7. Al importar, el sistema revisa todo y muestra los errores antes de grabar. Solo se graban las filas correctas.'],
    ];
    return hoja_crear_xlsx([
        ['nombre' => 'Estudiantes', 'filas' => [array_column($cols, 0)], 'anchos' => array_column($cols, 2)],
        ['nombre' => 'Instrucciones', 'filas' => $instr, 'anchos' => [110], 'sin_encabezado' => true],
    ]);
}

function importar_normalizar_encabezado($t) {
    $t = mb_strtolower(trim((string) $t), 'UTF-8');
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    return preg_replace('/[^a-z0-9]+/', ' ', $t);
}

/** Qué columna del archivo corresponde a cada campo (por el texto del encabezado). */
function importar_mapa(array $encabezados) {
    $alias = [
        'dni' => ['del estudiante', 'documento', 'dni', 'estudiante'], 'nombre' => ['nombres', 'nombre'], 'apepa' => ['apellido paterno', 'paterno'],
        'apema' => ['apellido materno', 'materno'], 'sexo' => ['sexo', 'genero'], 'fechanaci' => ['nacimiento', 'fecha'],
        'telf' => ['telefono del estudiante', 'telefono'], 'direc' => ['direccion'],
        'dnipa' => ['del padre'], 'nompa' => ['apellidos del padre', 'padre'], 'celpa' => ['celular del padre'],
        'dnima' => ['de la madre'], 'nomma' => ['apellidos de la madre', 'madre'], 'celma' => ['celular de la madre'],
    ];
    $mapa = [];
    $usadas = [];
    // primero las coincidencias más específicas (celular/documento del padre antes que "padre")
    foreach (['celpa', 'celma', 'dnipa', 'dnima', 'nompa', 'nomma', 'apepa', 'apema', 'telf', 'fechanaci', 'sexo', 'direc', 'nombre', 'dni'] as $campo) {
        foreach ($encabezados as $i => $h) {
            if (isset($usadas[$i])) { continue; }
            $n = importar_normalizar_encabezado($h);
            foreach ($alias[$campo] as $a) {
                if (strpos($n, $a) !== false && !($campo === 'nombre' && preg_match('/padre|madre/', $n)) && !($campo === 'dni' && preg_match('/padre|madre/', $n))) {
                    $mapa[$campo] = $i; $usadas[$i] = true; break 2;
                }
            }
        }
    }
    return $mapa;
}

/** Revisa todas las filas. Devuelve ['filas' => [...], 'listas' => n, 'con_error' => n, 'faltan_columnas' => [...]]. */
function importar_revisar(array $filas) {
    $cols = importar_columnas();
    if (count($filas) < 2) { throw new InvalidArgumentException('El archivo no tiene estudiantes (solo encabezados o vacío)'); }
    if (count($filas) - 1 > IMPORTAR_MAX_FILAS) { throw new InvalidArgumentException('Máximo ' . IMPORTAR_MAX_FILAS . ' estudiantes por archivo'); }
    $mapa = importar_mapa($filas[0]);
    $faltan = [];
    foreach ($cols as $k => $c) { if ($c[1] && !isset($mapa[$k])) { $faltan[] = $c[0]; } }
    if ($faltan) { return ['filas' => [], 'listas' => 0, 'con_error' => 0, 'faltan_columnas' => $faltan]; }
    $pdo = (new conexionBD())->conexionPDO();
    $existe = $pdo->prepare('SELECT 1 FROM alumnos WHERE alum_dni = ?');
    $vistos = [];
    $res = [];
    $min = date('Y-m-d', strtotime('-100 years'));
    $max = date('Y-m-d', strtotime('-2 years'));
    foreach (array_slice($filas, 1) as $n => $f) {
        $v = [];
        foreach ($cols as $k => $c) { $v[$k] = isset($mapa[$k]) ? trim((string) ($f[$mapa[$k]] ?? '')) : ''; }
        if (!array_filter($v)) { continue; }                                   // fila vacía
        $err = [];
        $aviso = null;
        foreach ($cols as $k => $c) { if ($c[1] && $v[$k] === '') { $err[] = 'Falta: ' . $c[0]; } }
        foreach (['dni' => true, 'dnima' => true, 'dnipa' => false] as $k => $req) {
            $v[$k] = normalizar_documento($v[$k]);
            if ($v[$k] !== '' && !documento_valido($v[$k])) { $err[] = $cols[$k][0] . ' no válido'; }
        }
        if ($v['dni'] !== '') {
            if (isset($vistos[$v['dni']])) { $err[] = 'Documento repetido en el archivo (fila ' . $vistos[$v['dni']] . ')'; }
            $vistos[$v['dni']] = $n + 2;
            $existe->execute([$v['dni']]);
            if ($existe->fetchColumn()) { $err[] = 'Ya existe un estudiante con ese documento'; }
        }
        $s = strtoupper(substr(importar_normalizar_encabezado($v['sexo']), 0, 1));
        if ($v['sexo'] !== '') {
            if (in_array($s, ['F', 'M'], true) || preg_match('/^(mujer|hombre)/i', $v['sexo'])) {
                $v['sexo'] = ($s === 'F' || stripos($v['sexo'], 'mujer') === 0) ? 'FEMENINO' : 'MASCULINO';
            } else { $err[] = 'Sexo: use F o M'; }
        }
        if ($v['fechanaci'] !== '') {
            $fecha = hoja_fecha($v['fechanaci']);
            if (!$fecha) { $err[] = 'Fecha de nacimiento no válida (use dd/mm/aaaa)'; }
            elseif ($fecha < $min || $fecha > $max) { $err[] = 'Fecha de nacimiento fuera de rango (entre 2 y 100 años)'; }
            else { $v['fechanaci'] = $fecha; }
        }
        foreach (['telf', 'celpa', 'celma'] as $k) {
            if ($v[$k] !== '' && !telefono_valido($v[$k])) { $err[] = $cols[$k][0] . ' no válido'; }
        }
        foreach (['nombre', 'apepa', 'apema', 'nompa', 'nomma', 'direc'] as $k) { $v[$k] = mb_strtoupper($v[$k], 'UTF-8'); }
        if (!$err) {
            $dup = posible_duplicado_alumno(htmlspecialchars($v['nombre'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($v['apepa'], ENT_QUOTES, 'UTF-8'),
                                            htmlspecialchars($v['apema'], ENT_QUOTES, 'UTF-8'), $v['fechanaci'], $v['dni']);
            if ($dup) { $aviso = 'Parece la misma persona que el estudiante con documento ' . $dup . ' (se importará igual)'; }
        }
        $res[] = ['fila' => $n + 2, 'datos' => $v, 'errores' => $err, 'aviso' => $aviso];
    }
    $con_error = count(array_filter($res, function ($r) { return $r['errores']; }));
    return ['filas' => $res, 'listas' => count($res) - $con_error, 'con_error' => $con_error, 'faltan_columnas' => []];
}

/** Graba las filas sin errores con el mismo procedimiento del registro manual. Devuelve cuántas se grabaron. */
function importar_grabar(array $revision) {
    $M = new Modelo_Alumnos();
    $n = 0;
    $fallidas = [];
    foreach ($revision['filas'] as $r) {
        if ($r['errores']) { continue; }
        $d = array_map(function ($x) { return htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8'); }, $r['datos']);
        $res = $M->Registrar_Alumnos($d['dni'], $d['nombre'], $d['apepa'], $d['apema'], $d['sexo'], $d['fechanaci'], $d['telf'], $d['direc'],
                                     'controller/alumnos/fotos/', $d['dnipa'], $d['nompa'], $d['celpa'], $d['dnima'], $d['nomma'], $d['celma']);
        if ((string) $res === '1') { $n++; } else { $fallidas[] = $r['fila']; }
    }
    return ['grabadas' => $n, 'fallidas' => $fallidas];
}
