<?php

/**
 * Genera database/seeders/datos_prueba.sql: el dataset de piloto, ANONIMIZADO, para pruebas.
 *
 * Conserva ids y relaciones (los procedimientos ven datos coherentes) y sustituye todo dato
 * personal por uno ficticio. Al final verifica que NINGÚN valor personal original aparece en
 * el archivo generado; si aparece, no escribe nada.
 *
 * Uso: DB_HOST=127.0.0.1 DB_PORT=3307 DB_NAME=colegio_sedes DB_USER=root DB_PASS= php tools/generar_datos_prueba.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const CLAVE_PRUEBA = 'Prueba.2026';
$salida = dirname(__DIR__) . '/database/seeders/datos_prueba.sql';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '3306', getenv('DB_NAME') ?: 'colegio_sedes'),
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Nombres ficticios poco comunes: el control anti-fuga es estricto y no admite coincidencias.
$nombres = ['AURELIO', 'BENITA', 'CIRILO', 'DOROTEA', 'EUSEBIO', 'FILOMENA', 'GUMERSINDO', 'HIGINIA', 'ISIDORO',
    'JACINTA', 'LEOCADIO', 'MACARIA', 'NICASIO', 'OTILIA', 'PRUDENCIO', 'QUITERIA', 'RUPERTO', 'SATURNINA',
    'TELESFORO', 'URBANA'];
$apellidos = ['PRUEBA', 'EJEMPLO', 'FICTICIO', 'DEMO', 'MUESTRA', 'ENSAYO', 'MODELO', 'PATRON', 'SIMULADO', 'PILOTO'];

$personales = []; // valores originales que no deben aparecer en la salida
$guardar = static function (mixed $v) use (&$personales): void {
    if (is_string($v) && mb_strlen(trim($v)) >= 4) {
        $personales[trim($v)] = true;
    }
};
$nombre = static fn (int $i): string => $nombres[$i % count($nombres)];
$apellido = static fn (int $i): string => $apellidos[$i % count($apellidos)];
$dni = static fn (int $base, int $i): string => (string) ($base + $i);
$cel = static fn (int $i): string => (string) (900000000 + $i);
$nacimiento = static fn (?string $f): ?string => $f ? substr($f, 0, 4) . '-01-01' : null;

/**
 * Reglas por tabla: columna => función(fila, índice) que devuelve el valor ficticio.
 * Las columnas no listadas se copian tal cual (ids, claves foráneas, estados, fechas de registro…).
 *
 * @var array<string, array<string, callable>> $reglas
 */
$hash = password_hash(CLAVE_PRUEBA, PASSWORD_DEFAULT);
$reglas = [
    'alumnos' => [
        'alum_dni' => fn ($r, $i) => $dni(70000000, $i), 'alum_nombre' => fn ($r, $i) => $nombre($i),
        'alum_apepat' => fn ($r, $i) => $apellido($i), 'alum_apemat' => fn ($r, $i) => $apellido($i + 3),
        'alum_movil' => fn ($r, $i) => $cel($i), 'alum_direccion' => fn ($r, $i) => "CALLE FICTICIA $i",
        'alum_fechanacimiento' => fn ($r) => $nacimiento($r['alum_fechanacimiento']),
        'alum_fotoperfil' => fn () => 'controller/alumnos/fotos/VACIO.png',
    ],
    'docentes' => [
        'docente_dni' => fn ($r, $i) => $dni(60000000, $i), 'docente_nombre' => fn ($r, $i) => $nombre($i + 5),
        'docente_apelli' => fn ($r, $i) => $apellido($i) . ' ' . $apellido($i + 1),
        'docente_movil' => fn ($r, $i) => $cel(100 + $i), 'docente_nro_alterno' => fn ($r, $i) => $cel(200 + $i),
        'docente_direccion' => fn ($r, $i) => "AV. EJEMPLO $i", 'docente_fechanacimiento' => fn ($r) => $nacimiento($r['docente_fechanacimiento']),
        'docente_fotoperfil' => fn () => 'controller/docentes/fotos/VACIO.png',
    ],
    'padres' => [
        'Dni_papa' => fn ($r, $i) => $dni(50000000, $i), 'Datos_papa' => fn ($r, $i) => $nombre($i + 2) . ' ' . $apellido($i),
        'Celular_papa' => fn ($r, $i) => $cel(300 + $i), 'Dni_mama' => fn ($r, $i) => $dni(51000000, $i),
        'Datos_mama' => fn ($r, $i) => $nombre($i + 7) . ' ' . $apellido($i + 4), 'Celular_mama' => fn ($r, $i) => $cel(400 + $i),
    ],
    'personal_admi' => [
        'personal_adm_dni' => fn ($r, $i) => $dni(40000000, $i), 'personal_adm_nombre' => fn ($r, $i) => $nombre($i + 9),
        'personal_adm_apellido' => fn ($r, $i) => $apellido($i + 2), 'personal_adm_movil' => fn ($r, $i) => $cel(500 + $i),
        'personal_adm_nro_alterno' => fn ($r, $i) => $cel(600 + $i), 'personal_adm_direccion' => fn ($r, $i) => "JR. MODELO $i",
        'personal_adm_fechanacimiento' => fn ($r) => $nacimiento($r['personal_adm_fechanacimiento']),
        'personal_adm_fotoperfil' => fn () => 'controller/personal_administrativo/fotos/VACIO.png',
    ],
    'auxiliar' => [
        'auxiliar_dni' => fn ($r, $i) => $dni(30000000, $i), 'auxiliar_nombre' => fn ($r, $i) => $nombre($i + 11),
        'auxiliar_apepat' => fn ($r, $i) => $apellido($i), 'auxiliar_apemat' => fn ($r, $i) => $apellido($i + 5),
        'auxiliar_movil' => fn ($r, $i) => $cel(700 + $i), 'auxiliar_nro_alterno' => fn ($r, $i) => $cel(800 + $i),
        'auxiliar_direccion' => fn ($r, $i) => "PSJE. DEMO $i", 'auxiliar_fechanacimiento' => fn ($r) => $nacimiento($r['auxiliar_fechanacimiento']),
        'auxiliar_fotoperfil' => fn () => '',
    ],
    'usuario' => [
        'usu_usuario' => fn ($r, $i) => 'usuario' . $r['usu_id'], 'usu_contra' => fn () => $hash,
        'usu_email' => fn ($r) => 'usuario' . $r['usu_id'] . '@example.com',
        // Un docente sin cursos queda INACTIVO para probar ese caso del login (E2E).
        'usu_estatus' => fn ($r) => (int) $r['usu_id'] === 33 ? 'INACTIVO' : $r['usu_estatus'],
    ],
    'atencion_salud' => [
        'motivo_consulta' => fn ($r, $i) => "MOTIVO DE PRUEBA $i", 'diagnostico' => fn ($r, $i) => "DIAGNOSTICO DE PRUEBA $i",
        'observaciones' => fn () => 'OBSERVACION DE PRUEBA',
    ],
    'solicitudes_informacion' => [
        'nombre_completo' => fn ($r, $i) => $nombre($i) . ' ' . $apellido($i), 'email' => fn ($r, $i) => "solicitud$i@example.com",
        'telefono' => fn ($r, $i) => $cel(900 + $i), 'mensaje' => fn () => 'Mensaje de prueba.',
        'ip_registro' => fn () => '192.0.2.1', 'observaciones' => fn ($r) => $r['observaciones'] === null ? null : 'Observación de prueba.',
    ],
    'matricula' => ['procedencia_colegio' => fn () => 'COLEGIO DE PROCEDENCIA DE PRUEBA'],
    // Datos de contacto del colegio: el teléfono puede ser el móvil de una persona.
    'empresa' => [
        'emp_razon' => fn () => 'COLEGIO DE PRUEBA', 'emp_email' => fn () => 'contacto@example.com',
        'emp_cod' => fn () => '0000', 'emp_telefono' => fn () => '900000000',
        'emp_direccion' => fn () => 'CALLE FICTICIA 1', 'emp_logo' => fn () => '',
    ],
    'ingresos' => ['observacion' => fn () => 'INGRESO DE PRUEBA', 'motivo_anulacion' => fn ($r) => $r['motivo_anulacion'] ? 'ANULACION DE PRUEBA' : $r['motivo_anulacion']],
    'egresos' => ['observacion' => fn () => 'EGRESO DE PRUEBA', 'motivo_anulacion' => fn ($r) => $r['motivo_anulacion'] ? 'ANULACION DE PRUEBA' : $r['motivo_anulacion']],
    'asistencia' => ['observacion' => fn ($r) => $r['observacion'] ? 'OBSERVACION DE PRUEBA' : $r['observacion']],
    'pago_pensiones' => ['motivo_edicion' => fn ($r) => $r['motivo_edicion'] ? 'EDICION DE PRUEBA' : $r['motivo_edicion']],
];
// Columnas de las que se recogen los valores originales para el control anti-fuga.
$columnasPersonales = $reglas;
unset($columnasPersonales['ingresos'], $columnasPersonales['egresos'], $columnasPersonales['asistencia'], $columnasPersonales['pago_pensiones']);
unset($columnasPersonales['usuario']['usu_estatus']); // un estado, no un dato personal

$tablas = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()
    AND table_type = 'BASE TABLE' AND table_name <> 'phinxlog' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

$sql = "-- Datos de PRUEBA generados por tools/generar_datos_prueba.php. Anonimizados: ningún dato personal real.\n"
    . "-- Contraseña de todos los usuarios: " . CLAVE_PRUEBA . "\n"
    . "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n";
$filasTotales = 0;
foreach ($tablas as $tabla) {
    $filas = $pdo->query("SELECT * FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
    $sql .= "\n-- $tabla\nDELETE FROM `$tabla`;\n";
    foreach ($filas as $i => $fila) {
        foreach ($columnasPersonales[$tabla] ?? [] as $col => $_) {
            $guardar($fila[$col] ?? null);
        }
        foreach ($reglas[$tabla] ?? [] as $col => $regla) {
            $fila[$col] = $regla($fila, $i + 1);
        }
        $cols = implode(', ', array_map(fn ($c) => "`$c`", array_keys($fila)));
        $vals = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $fila));
        $sql .= "INSERT INTO `$tabla` ($cols) VALUES ($vals);\n";
        $filasTotales++;
    }
}
$sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

// Control anti-fuga: ningún valor personal original puede aparecer en la salida.
$fugas = array_filter(array_map('strval', array_keys($personales)), fn (string $v) => stripos($sql, $v) !== false);
// Excepciones conocidas: valores originales que coinciden con los ficticios (p. ej. una ruta por defecto).
$fugas = array_filter($fugas, fn ($v) => !in_array($v, ['controller/alumnos/fotos/VACIO.png', 'controller/docentes/fotos/VACIO.png',
    'controller/personal_administrativo/fotos/VACIO.png', 'Observación de prueba.',
    // Centinelas de "sin foto": son prefijo de la ruta por defecto.
    'controller/alumnos/fotos/', 'controller/docentes/fotos/', 'controller/personal_administrativo/fotos/'], true)
    // Fechas de nacimiento: se generalizan a AAAA-01-01 a propósito; una coincidencia solo revela el año.
    && !preg_match('/^\d{4}-01-01$/', $v));
if ($fugas) {
    fwrite(STDERR, 'ABORTADO: ' . count($fugas) . " valores personales aparecerían en la salida, p. ej.: "
        . implode(' | ', getenv('VER_FUGAS') ? $fugas : array_slice(array_map(fn ($v) => mb_substr($v, 0, 3) . '…', $fugas), 0, 5)) . "\n");
    exit(1);
}
file_put_contents($salida, $sql);
echo "$filasTotales filas de " . count($tablas) . ' tablas → ' . basename($salida) . '; ' . count($personales)
    . " valores personales verificados ausentes.\n";
