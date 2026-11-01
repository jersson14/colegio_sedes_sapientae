<?php
/**
 * Genera la base de demostración colegio2_demo: un colegio completo del año escolar 2026 con data simulada hasta HOY
 * (inicial, primaria y secundaria; docentes, horarios, estudiantes, matrículas, pensiones y pagos, asistencia, notas,
 * tareas, exámenes, material, comunicados, atenciones de salud, gastos y movimientos de caja y banco).
 *
 * Uso:  php scripts/demo/generar_demo.php            (borra y vuelve a crear colegio2_demo; nunca toca colegio2)
 *
 * - La estructura (tablas, procedimientos, triggers, vistas) se copia de colegio2, más los catálogos del sistema
 *   (roles, países, monedas, métodos de pago, indicadores, datos del colegio...) y el usuario administrador JERSSON123.
 * - Matrículas, pagos, tareas, exámenes y gastos se registran con los MISMOS procedimientos que usa el sistema, con la
 *   fecha simulada de cada operación (SET timestamp), así cuadran recibos, moras, ingresos y saldos de las cuentas.
 * - Semilla fija: cada ejecución produce la misma data.
 * - Todos los usuarios de la demo tienen la contraseña Colegio2026 (ver docs/demo.md).
 */
if (PHP_SAPI !== 'cli') { exit('Solo desde la consola'); }
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');
mt_srand(2026);

const ORIGEN = 'colegio2';
const DEMO = 'colegio2_demo';
const CLAVE = 'Colegio2026';
const HOY = '2026-11-01';
$MYSQL_BIN = 'C:/xampp/mysql/bin';
$CN = ['host' => '127.0.0.1', 'port' => 3307, 'user' => 'root', 'pass' => ''];

function paso($t) { echo '[' . date('H:i:s') . "] $t\n"; }
function azar(array $a) { return $a[mt_rand(0, count($a) - 1)]; }
function prob($p) { return mt_rand() / mt_getrandmax() < $p; }
function entre($a, $b) { return mt_rand($a, $b); }

// ---------------------------------------------------------------- 1. Base nueva con la estructura de colegio2
paso('Creando ' . DEMO . ' con la estructura de ' . ORIGEN);
$cli = "\"$MYSQL_BIN/mysql.exe\" --default-character-set=utf8mb4 -u{$CN['user']} -P{$CN['port']} -h{$CN['host']}";
$dump = "\"$MYSQL_BIN/mysqldump.exe\" --default-character-set=utf8mb4 -u{$CN['user']} -P{$CN['port']} -h{$CN['host']}";
$esquema = sys_get_temp_dir() . '/demo_esquema.sql';
passthru("$dump --no-data --routines --triggers --skip-add-locks " . ORIGEN . " > \"$esquema\"", $e1);
$pdo0 = new PDO("mysql:host={$CN['host']};port={$CN['port']}", $CN['user'], $CN['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo0->exec('DROP DATABASE IF EXISTS ' . DEMO);
$col = $pdo0->query("SELECT default_character_set_name, default_collation_name FROM information_schema.schemata WHERE schema_name = '" . ORIGEN . "'")->fetch(PDO::FETCH_NUM);
$pdo0->exec('CREATE DATABASE ' . DEMO . " CHARACTER SET {$col[0]} COLLATE {$col[1]}");   // misma colación que la original (la usan los procedimientos)
passthru("$cli " . DEMO . " < \"$esquema\"", $e2);
if ($e1 || $e2) { exit("No se pudo copiar la estructura\n"); }
@unlink($esquema);

$db = new PDO("mysql:host={$CN['host']};port={$CN['port']};dbname=" . DEMO . ';charset=utf8mb4', $CN['user'], $CN['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec("SET time_zone = '-05:00'");

// Catálogos del sistema (se copian tal cual; sin revisar llaves mientras se copian, el orden no importa)
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['roles', 'paises', 'monedas', 'tipos_documento_identidad', 'metodos_pago', 'indicadores', 'conceptos_planilla',
          'tipos_contrato', 'avisos_config', 'empresa', 'parametros', 'nivel_academico', 'seccion', 'especialidad', 'permisos_pantalla'] as $t) {
    $db->exec("INSERT INTO `$t` SELECT * FROM " . ORIGEN . ".`$t`");
}
$db->exec('SET FOREIGN_KEY_CHECKS = 1');
// El administrador real (con su contraseña de siempre) y su ficha
$db->exec("INSERT INTO usuario SELECT * FROM " . ORIGEN . ".usuario WHERE usu_usuario = 'JERSSON123'");
$db->exec("INSERT INTO docentes SELECT * FROM " . ORIGEN . ".docentes WHERE id_asusuario = (SELECT usu_id FROM usuario WHERE usu_usuario = 'JERSSON123')");
$ADMIN = (int) $db->query("SELECT usu_id FROM usuario WHERE usu_usuario = 'JERSSON123'")->fetchColumn();
$db->exec("SET @app_usuario = $ADMIN, @app_ip = 'demo'");

// Fecha simulada de cada operación: NOW() y CURDATE() de los procedimientos y triggers devuelven esta fecha
function fecha($f) { global $db; $db->exec('SET timestamp = ' . (int) strtotime($f)); }
function fecha_hoy() { global $db; $db->exec('SET timestamp = DEFAULT'); }
function llamar($sql, array $p = []) {
    global $db;
    $q = $db->prepare($sql);
    $q->execute($p);
    $r = $q->columnCount() ? $q->fetch(PDO::FETCH_ASSOC) : null;
    $q->closeCursor();
    return $r;
}
function insertar($tabla, array $fila) {
    global $db;
    $c = array_keys($fila);
    $db->prepare("INSERT INTO `$tabla` (`" . implode('`,`', $c) . "`) VALUES (" . rtrim(str_repeat('?,', count($c)), ',') . ")")->execute(array_values($fila));
    return (int) $db->lastInsertId();
}
/** Inserción masiva por bloques (asistencia, notas). */
function insertar_lote($tabla, array $columnas, array $filas) {
    global $db;
    foreach (array_chunk($filas, 500) as $b) {
        $marca = '(' . rtrim(str_repeat('?,', count($columnas)), ',') . ')';
        $db->prepare("INSERT INTO `$tabla` (`" . implode('`,`', $columnas) . "`) VALUES " . implode(',', array_fill(0, count($b), $marca)))
           ->execute(array_merge(...array_map('array_values', $b)));
    }
}

$HASH = password_hash(htmlspecialchars(CLAVE, ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT, ['cost' => 10]);
function usuario($nombre, $rol, $email, $creado) {
    global $HASH;
    return insertar('usuario', ['usu_usuario' => $nombre, 'usu_contra' => $HASH, 'debe_cambiar_clave' => 0, 'usu_email' => $email,
        'usu_estatus' => 'ACTIVO', 'rol_id' => $rol, 'empresa_id' => 1, 'created_at' => $creado, 'updated_at' => $creado]);
}

// ---------------------------------------------------------------- 2. Nombres simulados
$NOM_M = ['Luis', 'Carlos', 'José', 'Jorge', 'Miguel', 'Diego', 'Mateo', 'Sebastián', 'Santiago', 'Thiago', 'Gael', 'Adrián', 'Joaquín',
          'Rodrigo', 'Fernando', 'Renzo', 'Piero', 'Bruno', 'Alonso', 'Fabricio', 'Gonzalo', 'Iker', 'Leonardo', 'Daniel', 'Gabriel',
          'Martín', 'Nicolás', 'Samuel', 'Emiliano', 'Franco', 'Aarón', 'Dylan', 'Josué', 'Kevin', 'Marcelo', 'Ángel', 'César'];
$NOM_F = ['María', 'Valentina', 'Camila', 'Luciana', 'Sofía', 'Valeria', 'Ximena', 'Mía', 'Isabella', 'Fernanda', 'Alessandra', 'Daniela',
          'Antonella', 'Nicole', 'Ariana', 'Kiara', 'Abigail', 'Romina', 'Micaela', 'Rafaela', 'Zoe', 'Emily', 'Allison', 'Brianna',
          'Gabriela', 'Lucía', 'Andrea', 'Rosa', 'Milagros', 'Fiorella', 'Jimena', 'Naomi', 'Renata', 'Victoria', 'Paula', 'Danna'];
$APE = ['Quispe', 'Flores', 'Sánchez', 'Rodríguez', 'García', 'Rojas', 'Mamani', 'Huamán', 'Vargas', 'Chávez', 'Ramos', 'Torres',
        'Mendoza', 'Castillo', 'Díaz', 'Espinoza', 'López', 'Gutiérrez', 'Pérez', 'Ramírez', 'Cruz', 'Salazar', 'Vásquez', 'Romero',
        'Fernández', 'Gonzales', 'Herrera', 'Medina', 'Aguilar', 'Silva', 'Paredes', 'Cáceres', 'Ccori', 'Condori', 'Huarcaya',
        'Palomino', 'Valdivia', 'Zevallos', 'Alarcón', 'Benites', 'Cárdenas', 'Delgado', 'Morales', 'Núñez', 'Ortiz', 'Ponce', 'Ríos',
        'Soto', 'Tapia', 'Villanueva', 'Yupanqui', 'Ccahuana', 'Loayza', 'Arias', 'Bustamante'];
$CALLES = ['Av. Los Incas', 'Jr. Lima', 'Av. Grau', 'Calle Los Pinos', 'Jr. Ayacucho', 'Av. Sol', 'Jr. Bolognesi', 'Calle San Martín',
           'Av. La Cultura', 'Jr. Puno', 'Av. Tacna', 'Calle Las Flores', 'Jr. Arequipa', 'Av. Huáscar', 'Jr. Junín', 'Av. Pardo'];
$COLEGIOS = ['I.E. SAN JOSÉ', 'I.E. NUESTRA SEÑORA DE FÁTIMA', 'COLEGIO SANTA ROSA', 'I.E. JOSÉ CARLOS MARIÁTEGUI', 'COLEGIO SAN MARTÍN', 'I.E. MICAELA BASTIDAS'];
$dnis = [];
function dni($desde = 60000000, $hasta = 79999999) {
    global $dnis;
    do { $d = (string) mt_rand($desde, $hasta); } while (isset($dnis[$d]));
    $dnis[$d] = 1;
    return $d;
}
function celular() { return '9' . mt_rand(10000000, 99999999); }
function direccion() { global $CALLES; return azar($CALLES) . ' N° ' . mt_rand(100, 1999); }
function sin_tildes($t) { return strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'ñ' => 'n', 'Ñ' => 'N']); }
function may($t) { return mb_strtoupper($t, 'UTF-8'); }

// ---------------------------------------------------------------- 3. Año escolar, periodos, aulas y cursos
paso('Año escolar 2026, bimestres, grados y cursos');
fecha('2026-01-05 09:00');
$ANIO = insertar('año_escolar', ['año_escolar' => '2026', 'estado' => 'ACTIVO', 'fecha_inicio' => '2026-03-09', 'fecha_fin' => '2026-12-11',
                                 'created_at' => '2026-01-05 09:00:00']);
$BIM = [];
foreach ([['I BIMESTRE', '2026-03-09', '2026-05-15', 'FINALIZADO'], ['II BIMESTRE', '2026-05-18', '2026-07-24', 'FINALIZADO'],
          ['III BIMESTRE', '2026-08-10', '2026-10-16', 'FINALIZADO'], ['IV BIMESTRE', '2026-10-19', '2026-12-11', 'EN CURSO']] as $b) {
    $BIM[] = ['id' => insertar('periodos', ['id_año_escolar' => $ANIO, 'tipo_perido' => 'BIMESTRE', 'periodos' => $b[0], 'fecha_inicio' => $b[1],
              'fecha_fin' => $b[2], 'estado' => $b[3], 'created_at' => '2026-01-05']), 'ini' => $b[1], 'fin' => $b[2], 'cerrado' => $b[3] === 'FINALIZADO'];
}
$UNICO = (int) $db->query("SELECT seccion_id FROM seccion WHERE seccion_nombre = 'UNICO'")->fetchColumn();
$NIVEL = [];
foreach ($db->query("SELECT Id_nivel, Nivel_academico FROM nivel_academico") as $n) { $NIVEL[$n['Nivel_academico']] = (int) $n['Id_nivel']; }

// Cursos de cada nivel: [nombre, horas por semana, especialidad, competencias]
$CURSOS = [
  'INICIAL' => [['COMUNICACIÓN', 6, 'EDUCACIÓN INICIAL', ['Se comunica oralmente en su lengua materna', 'Lee diversos tipos de textos', 'Crea proyectos desde los lenguajes artísticos']],
                ['MATEMÁTICA', 6, 'EDUCACIÓN INICIAL', ['Resuelve problemas de cantidad', 'Resuelve problemas de forma, movimiento y localización']],
                ['PERSONAL SOCIAL', 4, 'EDUCACIÓN INICIAL', ['Construye su identidad', 'Convive y participa democráticamente']],
                ['CIENCIA Y TECNOLOGÍA', 4, 'EDUCACIÓN INICIAL', ['Indaga mediante métodos científicos para construir sus conocimientos']],
                ['PSICOMOTRICIDAD', 3, 'EDUCACIÓN FÍSICA', ['Se desenvuelve de manera autónoma a través de su motricidad']],
                ['INGLÉS', 2, 'INGLÉS', ['Se comunica oralmente en inglés como lengua extranjera']]],
  'PRIMARIA' => [['MATEMÁTICA', 6, 'EDUCACIÓN PRIMARIA', ['Resuelve problemas de cantidad', 'Resuelve problemas de regularidad, equivalencia y cambio', 'Resuelve problemas de forma, movimiento y localización']],
                 ['COMUNICACIÓN', 6, 'EDUCACIÓN PRIMARIA', ['Se comunica oralmente', 'Lee diversos tipos de textos escritos', 'Escribe diversos tipos de textos']],
                 ['PERSONAL SOCIAL', 3, 'EDUCACIÓN PRIMARIA', ['Construye su identidad', 'Construye interpretaciones históricas']],
                 ['CIENCIA Y TECNOLOGÍA', 4, 'EDUCACIÓN PRIMARIA', ['Indaga mediante métodos científicos', 'Explica el mundo físico basándose en conocimientos científicos']],
                 ['EDUCACIÓN FÍSICA', 3, 'EDUCACIÓN FÍSICA', ['Se desenvuelve de manera autónoma a través de su motricidad', 'Asume una vida saludable']],
                 ['ARTE Y CULTURA', 2, 'EDUCACIÓN PRIMARIA', ['Aprecia de manera crítica manifestaciones artístico-culturales', 'Crea proyectos desde los lenguajes artísticos']],
                 ['INGLÉS', 2, 'INGLÉS', ['Se comunica oralmente en inglés', 'Lee y escribe textos en inglés']],
                 ['EDUCACIÓN RELIGIOSA', 2, 'EDUCACIÓN PRIMARIA', ['Construye su identidad como persona humana, amada por Dios']],
                 ['COMPUTACIÓN', 2, 'COMPUTACIÓN', ['Se desenvuelve en entornos virtuales generados por las TIC']]],
  'SECUNDARIA' => [['MATEMÁTICA', 6, 'MATEMATICO', ['Resuelve problemas de cantidad', 'Resuelve problemas de regularidad, equivalencia y cambio', 'Resuelve problemas de gestión de datos e incertidumbre']],
                   ['COMUNICACIÓN', 5, 'COMUNICACION', ['Se comunica oralmente', 'Lee diversos tipos de textos escritos', 'Escribe diversos tipos de textos']],
                   ['INGLÉS', 3, 'INGLÉS', ['Se comunica oralmente en inglés', 'Lee y escribe textos en inglés']],
                   ['CIENCIAS SOCIALES', 3, 'CIENCIAS SOCIALES', ['Construye interpretaciones históricas', 'Gestiona responsablemente el espacio y el ambiente']],
                   ['DESARROLLO PERSONAL, CIUDADANÍA Y CÍVICA', 3, 'CIENCIAS SOCIALES', ['Construye su identidad', 'Convive y participa democráticamente']],
                   ['CIENCIA Y TECNOLOGÍA', 5, 'QUIMICO', ['Indaga mediante métodos científicos', 'Explica el mundo físico', 'Diseña y construye soluciones tecnológicas']],
                   ['EDUCACIÓN FÍSICA', 2, 'EDUCACIÓN FÍSICA', ['Se desenvuelve de manera autónoma a través de su motricidad', 'Asume una vida saludable']],
                   ['ARTE Y CULTURA', 2, 'DANZA', ['Aprecia de manera crítica manifestaciones artístico-culturales', 'Crea proyectos desde los lenguajes artísticos']],
                   ['EDUCACIÓN PARA EL TRABAJO', 3, 'COMPUTACIÓN', ['Gestiona proyectos de emprendimiento económico o social']],
                   ['EDUCACIÓN RELIGIOSA', 2, 'RELIGIÓN', ['Construye su identidad como persona humana, amada por Dios']]],
];
$GRADOS = ['INICIAL' => ['3 AÑOS', '4 AÑOS', '5 AÑOS'],
           'PRIMARIA' => ['PRIMER GRADO', 'SEGUNDO GRADO', 'TERCER GRADO', 'CUARTO GRADO', 'QUINTO GRADO', 'SEXTO GRADO'],
           'SECUNDARIA' => ['PRIMER AÑO', 'SEGUNDO AÑO', 'TERCER AÑO', 'CUARTO AÑO', 'QUINTO AÑO']];
// Horas de clase de cada nivel (el recreo queda entre bloques)
$BLOQUES = ['INICIAL' => [['08:00', '08:45'], ['08:45', '09:30'], ['09:30', '10:15'], ['10:45', '11:30'], ['11:30', '12:15']],
            'PRIMARIA' => [['07:45', '08:30'], ['08:30', '09:15'], ['09:15', '10:00'], ['10:30', '11:15'], ['11:15', '12:00'], ['12:00', '12:45']],
            'SECUNDARIA' => [['07:30', '08:15'], ['08:15', '09:00'], ['09:00', '09:45'], ['09:45', '10:30'], ['11:00', '11:45'], ['11:45', '12:30'], ['12:30', '13:15']]];
$EDAD_BASE = ['INICIAL' => 3, 'PRIMARIA' => 6, 'SECUNDARIA' => 12];

// Especialidades que faltan en el catálogo
$ESP = [];
foreach ($db->query("SELECT Id_especilidad, Especialidad FROM especialidad") as $e) { $ESP[$e['Especialidad']] = (int) $e['Id_especilidad']; }
foreach (['EDUCACIÓN INICIAL', 'EDUCACIÓN PRIMARIA', 'EDUCACIÓN FÍSICA', 'INGLÉS', 'CIENCIAS SOCIALES', 'COMPUTACIÓN', 'RELIGIÓN'] as $e) {
    if (!isset($ESP[$e])) { $ESP[$e] = insertar('especialidad', ['Especialidad' => $e, 'Descripcion' => 'DOCENTES DE ' . $e, 'Estado' => 'ACTIVO', 'created_at' => '2026-01-05 09:00:00']); }
}

$AULAS = [];   // id => [nivel, grado, orden, cursos => [nombre => id_asignatura]]
foreach ($GRADOS as $nivel => $grados) {
    foreach ($grados as $i => $g) {
        $id = insertar('aulas', ['Grado' => $g, 'id_nivel_academico' => $NIVEL[$nivel], 'id_seccion' => $UNICO, 'descripcion' => 'AULA DE ' . $g . ' DE ' . $nivel,
                                 'capacidad' => 25, 'estado' => 'ACTIVO', 'created_at' => '2026-01-05 09:00:00']);
        $AULAS[$id] = ['nivel' => $nivel, 'grado' => $g, 'orden' => $i, 'cursos' => []];
        foreach ($CURSOS[$nivel] as $c) {
            $AULAS[$id]['cursos'][$c[0]] = insertar('asignaturas', ['nombre_asig' => $c[0], 'Id_grado' => $id, 'observaciones' => '', 'estado' => 'CON DOCENTE', 'created_at' => '2026-01-05']);
        }
    }
}

// ---------------------------------------------------------------- 4. Personal y docentes
paso('Personal y docentes');
function persona($sexo) { global $NOM_M, $NOM_F, $APE; return [azar($sexo === 'MASCULINO' ? $NOM_M : $NOM_F), azar($APE), azar($APE)]; }
$usados = [];
function nombre_usuario($nombre, $ape) {
    global $usados;
    $base = may(sin_tildes(mb_substr($nombre, 0, 1) . str_replace(' ', '', $ape)));
    $u = $base; $n = 2;
    while (isset($usados[$u])) { $u = $base . $n++; }
    $usados[$u] = 1;
    return $u;
}
$PERSONAL = [];
foreach ([['DIRECCION', 9, 'ADMINISTRADOR', 'FEMENINO'], ['CONTADOR', 11, 'CONTADOR', 'MASCULINO'], ['AUXILIAR', 3, 'AUXILIAR', 'FEMENINO'],
          ['ENFERMERA', 4, 'ENFERMERA', 'FEMENINO'], ['PSICOLOGA', 5, 'PSICOLOGA', 'FEMENINO']] as $p) {
    [$n, $a1, $a2] = persona($p[3]);
    $usados[$p[0]] = 1;
    $u = usuario($p[0], $p[1], strtolower($p[0]) . '@colegio-demo.pe', '2026-01-05 09:00:00');
    $PERSONAL[$p[2]] = ['usu' => $u, 'nombre' => "$n $a1 $a2"];
    if ($p[2] === 'ADMINISTRADOR') {   // la dirección inicia sesión como "docente" sin cursos (igual que el administrador real)
        insertar('docentes', ['docente_dni' => dni(40000000, 49999999), 'docente_nombre' => may($n), 'docente_apelli' => may("$a1 $a2"), 'especialidad_id' => $ESP['EDUCACIÓN PRIMARIA'],
            'docente_sexo' => $p[3], 'docente_fechanacimiento' => entre(1970, 1982) . '-0' . entre(1, 9) . '-1' . entre(0, 9), 'docente_movil' => celular(),
            'docente_nro_alterno' => '', 'docente_direccion' => may(direccion()), 'docente_estatus' => 'ACTIVO', 'docente_fotoperfil' => 'controller/docentes/fotos/',
            'id_asusuario' => $u, 'created_at' => '2026-01-05']);
    } else {
        insertar('personal_admi', ['personal_adm_dni' => dni(40000000, 49999999), 'personal_adm_nombre' => may($n), 'personal_adm_apellido' => may("$a1 $a2"),
            'personal_adm_tipo' => $p[2], 'personal_adm_sexo' => $p[3], 'personal_adm_fechanacimiento' => entre(1975, 1995) . '-0' . entre(1, 9) . '-2' . entre(0, 8),
            'personal_adm_movil' => celular(), 'personal_adm_nro_alterno' => '', 'personal_adm_direccion' => may(direccion()), 'personal_adm_estatus' => 'ACTIVO',
            'personal_adm_fotoperfil' => 'controller/personal_administrativo/fotos/', 'id_ausuario' => $u, 'created_at' => '2026-01-05 09:00:00']);
    }
}
$CONTADOR = $PERSONAL['CONTADOR']['usu'];

// Plantel: tutoras de inicial y primaria (todos los cursos de su aula salvo los de especialistas) y especialistas
$ids_aula = array_keys($AULAS);
$aulas_de = function ($nivel, $grados = null) use ($AULAS) {
    return array_keys(array_filter($AULAS, function ($a) use ($nivel, $grados) { return $a['nivel'] === $nivel && ($grados === null || in_array($a['orden'], $grados, true)); }));
};
$PLANTEL = [];
foreach (array_merge($aulas_de('INICIAL'), $aulas_de('PRIMARIA')) as $aula) {
    $esp = $AULAS[$aula]['nivel'] === 'INICIAL' ? 'EDUCACIÓN INICIAL' : 'EDUCACIÓN PRIMARIA';
    $cursos = array_diff(array_keys($AULAS[$aula]['cursos']), ['PSICOMOTRICIDAD', 'INGLÉS', 'EDUCACIÓN FÍSICA', 'COMPUTACIÓN']);
    $PLANTEL[] = ['esp' => $esp, 'sexo' => prob(.85) ? 'FEMENINO' : 'MASCULINO', 'cursos' => array_map(function ($c) use ($aula) { return [$aula, $c]; }, $cursos)];
}
$especialista = function ($esp, $sexo, array $pares) use (&$PLANTEL) { $PLANTEL[] = ['esp' => $esp, 'sexo' => $sexo, 'cursos' => $pares]; };
$todos = function ($curso, array $aulas) { return array_map(function ($a) use ($curso) { return [$a, $curso]; }, $aulas); };
$especialista('EDUCACIÓN FÍSICA', 'MASCULINO', array_merge($todos('PSICOMOTRICIDAD', $aulas_de('INICIAL')), $todos('EDUCACIÓN FÍSICA', $aulas_de('PRIMARIA'))));
$especialista('EDUCACIÓN FÍSICA', 'FEMENINO', $todos('EDUCACIÓN FÍSICA', $aulas_de('SECUNDARIA')));
$especialista('INGLÉS', 'FEMENINO', array_merge($todos('INGLÉS', $aulas_de('INICIAL')), $todos('INGLÉS', $aulas_de('PRIMARIA'))));
$especialista('INGLÉS', 'MASCULINO', $todos('INGLÉS', $aulas_de('SECUNDARIA')));
$especialista('COMPUTACIÓN', 'MASCULINO', array_merge($todos('COMPUTACIÓN', $aulas_de('PRIMARIA')), $todos('EDUCACIÓN PARA EL TRABAJO', $aulas_de('SECUNDARIA'))));
$especialista('MATEMATICO', 'MASCULINO', $todos('MATEMÁTICA', $aulas_de('SECUNDARIA', [0, 1, 2])));
$especialista('MATEMATICO', 'FEMENINO', $todos('MATEMÁTICA', $aulas_de('SECUNDARIA', [3, 4])));
$especialista('COMUNICACION', 'FEMENINO', $todos('COMUNICACIÓN', $aulas_de('SECUNDARIA', [0, 1, 2])));
$especialista('COMUNICACION', 'MASCULINO', $todos('COMUNICACIÓN', $aulas_de('SECUNDARIA', [3, 4])));
$especialista('CIENCIAS SOCIALES', 'MASCULINO', array_merge($todos('CIENCIAS SOCIALES', $aulas_de('SECUNDARIA')), $todos('DESARROLLO PERSONAL, CIUDADANÍA Y CÍVICA', $aulas_de('SECUNDARIA', [0, 1]))));
$especialista('QUIMICO', 'FEMENINO', $todos('CIENCIA Y TECNOLOGÍA', $aulas_de('SECUNDARIA')));
$especialista('DANZA', 'FEMENINO', array_merge($todos('ARTE Y CULTURA', $aulas_de('SECUNDARIA')), $todos('DESARROLLO PERSONAL, CIUDADANÍA Y CÍVICA', $aulas_de('SECUNDARIA', [2, 3, 4]))));
$especialista('RELIGIÓN', 'MASCULINO', $todos('EDUCACIÓN RELIGIOSA', $aulas_de('SECUNDARIA')));

$DOCENTES = [];   // [usu, nombre, id_docente, cursos => [id_detalle]]
$DETALLE = [];    // id_detalle => [aula, curso, docente(index), usu]
foreach ($PLANTEL as $k => $p) {
    [$n, $a1, $a2] = persona($p['sexo']);
    $login = nombre_usuario($n, $a1);
    $u = usuario($login, 2, strtolower(sin_tildes($login)) . '@colegio-demo.pe', '2026-01-08 10:00:00');
    $idd = insertar('docentes', ['docente_dni' => dni(40000000, 49999999), 'docente_nombre' => may($n), 'docente_apelli' => may("$a1 $a2"), 'especialidad_id' => $ESP[$p['esp']],
        'docente_sexo' => $p['sexo'], 'docente_fechanacimiento' => entre(1972, 1996) . '-' . str_pad(entre(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(entre(1, 28), 2, '0', STR_PAD_LEFT),
        'docente_movil' => celular(), 'docente_nro_alterno' => '', 'docente_direccion' => may(direccion()), 'docente_estatus' => 'ACTIVO',
        'docente_fotoperfil' => 'controller/docentes/fotos/', 'id_asusuario' => $u, 'created_at' => '2026-01-08']);
    $ad = insertar('asignatura_docente', ['Id_docente' => $idd, 'id_año' => $ANIO, 'Total_cursos' => count($p['cursos']), 'created_at' => '2026-01-12']);
    $DOCENTES[$k] = ['usu' => $u, 'login' => $login, 'nombre' => "$n $a1 $a2", 'id' => $idd, 'cursos' => []];
    foreach ($p['cursos'] as [$aula, $curso]) {
        $det = insertar('detalle_asignatura_docente', ['Id_asig_docente' => $ad, 'Id_asignatura' => $AULAS[$aula]['cursos'][$curso], 'created_at' => '2026-01-12']);
        $DETALLE[$det] = ['aula' => $aula, 'curso' => $curso, 'doc' => $k, 'usu' => $u];
        $DOCENTES[$k]['cursos'][] = $det;
    }
}
$DET_DE = [];   // aula => curso => id_detalle
foreach ($DETALLE as $id => $d) { $DET_DE[$d['aula']][$d['curso']] = $id; }

// ---------------------------------------------------------------- 5. Horario semanal sin cruces de docentes
paso('Horarios');
$ocupado = [];   // usu docente => dia => [[ini, fin]]
$cruza = function ($usu, $dia, $ini, $fin) use (&$ocupado) {
    foreach ($ocupado[$usu][$dia] ?? [] as [$a, $b]) { if ($ini < $b && $a < $fin) { return true; } }
    return false;
};
$DIAS = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];
$HORARIO = [];   // id_detalle => [[dia, ini, fin]]
// Primero los especialistas (enseñan en muchas aulas), luego el resto
$orden_aulas = $ids_aula;
foreach ($orden_aulas as $aula) {
    $nivel = $AULAS[$aula]['nivel'];
    $horas = [];
    foreach ($BLOQUES[$nivel] as [$ini, $fin]) {
        $horas[] = ['id' => insertar('horas_aula', ['id_año_academico' => $ANIO, 'id_aula' => $aula, 'turno' => 'MAÑANA', 'hora_inicio' => $ini, 'hora_fin' => $fin,
                    'estado' => 'ACTIVO', 'created_at' => '2026-01-15']), 'ini' => $ini, 'fin' => $fin];
    }
    $pend = [];
    foreach ($CURSOS[$nivel] as $c) { $pend[$c[0]] = $c[1]; }
    $huecos = [];
    foreach ($DIAS as $dia) { foreach ($horas as $h) { $huecos[] = [$dia, $h]; } }
    shuffle($huecos);
    // Los cursos de especialistas (más aulas) se ubican primero
    uksort($pend, function ($a, $b) use ($DET_DE, $DOCENTES, $DETALLE, $aula) {
        return count($DOCENTES[$DETALLE[$DET_DE[$aula][$b]]['doc']]['cursos']) <=> count($DOCENTES[$DETALLE[$DET_DE[$aula][$a]]['doc']]['cursos']);
    });
    $usados_dia = [];
    foreach ($pend as $curso => $n) {
        $det = $DET_DE[$aula][$curso];
        $usu = $DETALLE[$det]['usu'];
        foreach ($huecos as $i => [$dia, $h]) {
            if ($n === 0) { break; }
            if (($usados_dia[$curso][$dia] ?? 0) >= 2 || $cruza($usu, $dia, $h['ini'], $h['fin'])) { continue; }
            insertar('horarios', ['id_hora_aula' => $h['id'], 'id_detalle_asig_docente' => $det, 'dia' => $dia, 'estado' => 'ACTIVO', 'created_at' => '2026-01-20']);
            $ocupado[$usu][$dia][] = [$h['ini'], $h['fin']];
            $HORARIO[$det][] = [$dia, $h['ini'], $h['fin']];
            $usados_dia[$curso][$dia] = ($usados_dia[$curso][$dia] ?? 0) + 1;
            unset($huecos[$i]);
            $n--;
        }
    }
}

// Criterios de evaluación de cada curso
$CRITERIOS = [];   // id_detalle => [id_criterio]
foreach ($DETALLE as $det => $d) {
    foreach ($CURSOS[$AULAS[$d['aula']]['nivel']] as $c) {
        if ($c[0] !== $d['curso']) { continue; }
        foreach ($c[3] as $comp) {
            $CRITERIOS[$det][] = insertar('criterios', ['id_detalle_asignatura' => $det, 'competencias' => may($comp), 'descripción_observa' => '', 'estado' => 'ACTIVO', 'created_at' => '2026-02-20']);
        }
    }
}

// ---------------------------------------------------------------- 6. Cuentas, pensiones
paso('Cuentas y cronograma de pensiones');
$CAJA = insertar('cuentas_financieras', ['tipo' => 'CAJA', 'nombre' => 'CAJA PRINCIPAL', 'saldo_inicial' => 1500, 'fecha_saldo_inicial' => '2026-01-02', 'estado' => 'ACTIVO', 'created_at' => '2026-01-02 08:00:00']);
$BANCO = insertar('cuentas_financieras', ['tipo' => 'BANCO', 'nombre' => 'CUENTA CORRIENTE BCP', 'banco' => 'BCP', 'numero_cuenta' => '285-' . mt_rand(1000000, 9999999) . '-0-' . mt_rand(10, 99),
    'codigo_interbancario' => '002285' . mt_rand(100000000, 999999999) . '55', 'titular' => 'SEDES SAPIENTIAE S.A.C.', 'saldo_inicial' => 45000, 'fecha_saldo_inicial' => '2026-01-02', 'estado' => 'ACTIVO', 'created_at' => '2026-01-02 08:00:00']);
$YAPE = insertar('cuentas_financieras', ['tipo' => 'BILLETERA', 'nombre' => 'YAPE DEL COLEGIO', 'banco' => 'BCP', 'numero_cuenta' => celular(), 'titular' => 'SEDES SAPIENTIAE S.A.C.',
    'saldo_inicial' => 0, 'fecha_saldo_inicial' => '2026-01-02', 'estado' => 'ACTIVO', 'created_at' => '2026-01-02 08:00:00']);
$PRECIO = ['INICIAL' => 280, 'PRIMARIA' => 320, 'SECUNDARIA' => 360];
$MATRICULA = ['INICIAL' => 250, 'PRIMARIA' => 300, 'SECUNDARIA' => 350];
fecha('2026-01-20 10:00');
foreach ($PRECIO as $nivel => $precio) {
    llamar('CALL SP_GENERAR_PENSIONES(?,?,?,?,?,?,?)', [$ANIO, $NIVEL[$nivel], 3, 12, 0, $precio, 20]);
}
$PENSIONES = [];   // nivel => [mes(3..12) => [id, vence, precio]]
$MESES = ['MARZO' => 3, 'ABRIL' => 4, 'MAYO' => 5, 'JUNIO' => 6, 'JULIO' => 7, 'AGOSTO' => 8, 'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12];
foreach ($db->query("SELECT p.id_pensiones, p.mes, p.fecha_vencimiento, p.precio, n.Nivel_academico FROM pensiones p JOIN nivel_academico n ON n.Id_nivel = p.id_nivel_academico") as $p) {
    $PENSIONES[$p['Nivel_academico']][$MESES[$p['mes']]] = ['id' => (int) $p['id_pensiones'], 'vence' => $p['fecha_vencimiento'], 'precio' => (float) $p['precio']];
}

/** Forma de pago al azar: [metodo, cuenta, referencia]. */
function forma_pago() {
    global $CAJA, $BANCO, $YAPE;
    $r = mt_rand(1, 100);
    if ($r <= 45) { return [1, $CAJA, '']; }
    if ($r <= 72) { return [4, $BANCO, 'OP-' . mt_rand(100000, 999999)]; }
    if ($r <= 90) { return [6, $YAPE, 'YAPE-' . mt_rand(1000000, 9999999)]; }
    return [5, $BANCO, 'DEP-' . mt_rand(100000, 999999)];
}
function hora_caja($dia) { return $dia . ' ' . str_pad(entre(8, 16), 2, '0', STR_PAD_LEFT) . ':' . str_pad(entre(0, 59), 2, '0', STR_PAD_LEFT); }

// ---------------------------------------------------------------- 7. Estudiantes, padres y matrículas
paso('Estudiantes y matrículas (con su recibo)');
$EST = [];   // id_matricula => [aula, perfil, habilidad, asistencia, usu, nivel]
$total_est = 0;
foreach ($AULAS as $aula => $a) {
    $n = entre(15, 20);
    for ($i = 0; $i < $n; $i++) {
        $sexo = prob(.5) ? 'MASCULINO' : 'FEMENINO';
        [$nom, $ap1, $ap2] = persona($sexo);
        if (prob(.35)) { $nom .= ' ' . azar($sexo === 'MASCULINO' ? $NOM_M : $NOM_F); }
        $edad = $EDAD_BASE[$a['nivel']] + $a['orden'];
        $nac = (2026 - $edad - (prob(.25) ? 1 : 0)) . '-' . str_pad(entre(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(entre(1, 28), 2, '0', STR_PAD_LEFT);
        if ($nac > (2026 - $edad) . '-03-31') { $nac = (2026 - $edad) . '-0' . entre(1, 3) . '-1' . entre(0, 9); }
        $dni = dni(70000000, 79999999);
        $papa = persona('MASCULINO'); $mama = persona('FEMENINO');
        $dir = direccion();
        $fmat = '2026-' . azar(['01', '02', '02', '02', '03']) . '-' . str_pad(entre(5, 27), 2, '0', STR_PAD_LEFT);
        if ($fmat > '2026-03-06') { $fmat = '2026-03-0' . entre(2, 6); }
        $hora = hora_caja($fmat);
        fecha($hora);
        llamar('CALL SP_REGISTRAR_ALUMNOS(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$dni, may($nom), may($ap1), may($ap2), $sexo, $nac, '', may($dir), 'controller/alumnos/fotos/',
            dni(10000000, 49999999), may($papa[0] . ' ' . $ap1 . ' ' . $papa[2]), celular(), dni(10000000, 49999999), may($mama[0] . ' ' . $ap2 . ' ' . $mama[2]), celular()]);
        $id_alu = (int) $db->query("SELECT Id_alumno FROM alumnos WHERE alum_dni = '$dni'")->fetchColumn();
        $nuevo = prob(.18) ? 150 : 0;
        [$met, $cta, $ref] = forma_pago();
        $r = llamar('CALL SP_REGISTRAR_MATRICULA(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$id_alu, $ANIO, $aula, 0, $nuevo, $MATRICULA[$a['nivel']],
            $nuevo ? azar($COLEGIOS) : 'SEDES SAPIENTIAE', 'CUSCO', 'CUSCO', $dni, $HASH, strtolower(sin_tildes($nom === '' ? 'alumno' : explode(' ', $nom)[0])) . $dni . '@gmail.com',
            $CONTADOR, $met, $cta, $ref]);
        if ((int) reset($r) !== 1) { echo "  matrícula no registrada ($dni): " . json_encode($r) . "\n"; continue; }
        $mat = (int) $db->query("SELECT id_matricula FROM matricula WHERE id_alumno = $id_alu")->fetchColumn();
        $usu = (int) $db->query("SELECT usu_id FROM matricula WHERE id_matricula = $mat")->fetchColumn();
        if ($nuevo) { $db->exec("UPDATE alumnos SET tipo_alum = 'NUEVO' WHERE Id_alumno = $id_alu"); }
        $p = mt_rand(1, 100);
        $EST[$mat] = ['aula' => $aula, 'nivel' => $a['nivel'], 'usu' => $usu, 'sexo' => $sexo, 'nombre' => "$nom $ap1",
                      'perfil' => $p <= 55 ? 'PUNTUAL' : ($p <= 85 ? 'REGULAR' : 'MOROSO'),
                      'hab' => max(6, min(19.5, 14 + (mt_rand(-100, 100) / 100) * 4 + (mt_rand(-100, 100) / 100) * 1.5)),
                      'asis' => prob(.12) ? .86 : (prob(.5) ? .95 : .98), 'deja' => entre(5, 9)];
        $total_est++;
    }
}
$db->exec("UPDATE usuario SET debe_cambiar_clave = 0");
paso("  $total_est estudiantes matriculados");

// ---------------------------------------------------------------- 8. Pagos de pensiones (con moras) día a día
paso('Pagos de pensiones');
function saldo($mat, $pen) {
    global $db;
    $db->exec("CALL SP_CALCULAR_SALDO_PENSION($mat, $pen, @p, @m, @pp, @pm, @e, @v, @sp, @sm)");
    return $db->query("SELECT @sp AS p, @sm AS m")->fetch();
}
$eventos = [];   // [fecha, matricula, [meses]]
foreach ($EST as $mat => $e) {
    foreach ($PENSIONES[$e['nivel']] as $mes => $pen) {
        $vence = $pen['vence'];
        if ($e['perfil'] === 'MOROSO' && $mes >= $e['deja']) { if (prob(.75)) { continue; } }
        if ($e['perfil'] === 'PUNTUAL') {
            $dia = date('Y-m-d', strtotime($vence) - entre(0, 18) * 86400);
            if ($mes === 11 && prob(.25)) { $dia = '2026-10-' . entre(26, 31); }   // algunos adelantan noviembre
        } elseif ($e['perfil'] === 'REGULAR') {
            $dia = date('Y-m-d', strtotime($vence) + (prob(.55) ? entre(1, 25) : -entre(0, 6)) * 86400);
        } else {
            $dia = date('Y-m-d', strtotime($vence) + entre(5, 45) * 86400);
        }
        if (date('N', strtotime($dia)) == 7) { $dia = date('Y-m-d', strtotime($dia) + 86400); }
        if ($dia >= HOY || $dia < '2026-03-01') { continue; }
        $eventos[] = [$dia, $mat, $mes];
    }
}
usort($eventos, function ($a, $b) { return $a[0] <=> $b[0]; });
// Junta los meses que un estudiante paga el mismo día en un solo recibo
$recibos = [];
foreach ($eventos as [$dia, $mat, $mes]) { $recibos["$dia|$mat"][] = $mes; }
$n_rec = 0; $n_mora = 0;
/** Cobra en un recibo las pensiones de los meses indicados (con su mora, que a veces se exonera). Devuelve el id del recibo o 0. */
function cobrar($mat, array $meses, $cuando) {
    global $db, $EST, $PENSIONES, $CONTADOR, $n_mora;
    $e = $EST[$mat];
    fecha($cuando);
    $db->beginTransaction();
    $rec = llamar('CALL SP_CREAR_RECIBO(?,?,?)', [$mat, '', $CONTADOR]);
    $total = 0; $ok = true;
    sort($meses);
    foreach ($meses as $mes) {
        $pen = $PENSIONES[$e['nivel']][$mes]['id'];
        $s = saldo($mat, $pen);
        $monto = round((float) $s['p'] + (float) $s['m'], 2);
        if ($monto <= 0) { continue; }
        $exonera = (float) $s['m'] > 0 && prob(.2);
        if ($exonera) { $monto = (float) $s['p']; }
        if ((float) $s['m'] > 0 && !$exonera) { $n_mora++; }
        $r = llamar('CALL SP_REGISTRAR_PAGO_PENSION(?,?,?,?,?,?,?,?)', [$mat, 'PENSION', $pen, $monto, $exonera ? 1 : 0, $exonera ? azar(['PRIMER RETRASO DEL AÑO', 'SITUACIÓN ECONÓMICA COMUNICADA', 'HERMANOS EN EL COLEGIO']) : '', $CONTADOR, $rec['id_pago']]);
        if ((int) $r['codigo'] !== 1) { $ok = false; break; }
        $total += $monto;
    }
    if (!$ok || $total <= 0) { $db->rollBack(); return 0; }
    [$met, $cta, $ref] = forma_pago();
    $r1 = llamar('CALL SP_AGREGAR_METODO_RECIBO(?,?,?,?,?)', [$rec['id_pago'], $met, $cta, $total, $ref]);
    $r2 = llamar('CALL SP_CERRAR_RECIBO(?)', [$rec['id_pago']]);
    if ((int) $r1['codigo'] !== 1 || (int) $r2['codigo'] !== 1) { $db->rollBack(); echo '  recibo rechazado: ' . json_encode([$r1, $r2]) . "\n"; return 0; }
    $db->commit();
    return (int) $rec['id_pago'];
}
foreach ($recibos as $clave => $meses) {
    [$dia, $mat] = explode('|', $clave);
    if (cobrar((int) $mat, $meses, hora_caja($dia))) { $n_rec++; }
}
paso("  $n_rec recibos de pensión ($n_mora con mora)");

// Dos recibos anulados por error de digitación y vueltos a emitir (quedan para ver la anulación)
fecha_hoy();
foreach ($db->query("SELECT id_pago, id_matricula, fecha FROM pagos WHERE observacion IS NULL AND estado = 'VALIDO' ORDER BY RAND(7) LIMIT 2")->fetchAll() as $p) {
    $meses = $db->query("SELECT DISTINCT p.mes FROM pago_pensiones pp JOIN pensiones p ON p.id_pensiones = pp.id_pension WHERE pp.id_pago = {$p['id_pago']}")->fetchAll(PDO::FETCH_COLUMN);
    $cuando = date('Y-m-d H:i', strtotime($p['fecha']) + 1200);
    fecha($cuando);
    llamar('CALL SP_ANULAR_RECIBO(?,?,?)', [$p['id_pago'], 'MONTO DIGITADO POR ERROR, SE EMITIÓ NUEVO RECIBO', $CONTADOR]);
    cobrar((int) $p['id_matricula'], array_map(function ($m) use ($MESES) { return $MESES[$m]; }, $meses), date('Y-m-d H:i', strtotime($cuando) + 300));
}

// ---------------------------------------------------------------- 9. Calendario escolar y asistencia
paso('Asistencia diaria');
$FERIADOS = ['2026-04-02', '2026-04-03', '2026-05-01', '2026-06-29', '2026-07-23', '2026-07-28', '2026-07-29', '2026-08-06', '2026-10-08'];
$DIAS_CLASE = [];
for ($t = strtotime('2026-03-09'); $t < strtotime(HOY); $t += 86400) {
    $d = date('Y-m-d', $t);
    if (date('N', $t) >= 6 || in_array($d, $FERIADOS, true) || ($d >= '2026-07-27' && $d <= '2026-08-07')) { continue; }   // vacaciones de medio año
    $DIAS_CLASE[] = $d;
}
$filas = [];
foreach ($EST as $mat => $e) {
    foreach ($DIAS_CLASE as $d) {
        $r = mt_rand() / mt_getrandmax();
        if ($r < $e['asis']) { $estado = prob(.95) ? 'PRESENTE' : 'TARDE'; }
        else { $estado = prob(.55) ? 'AUSENTE' : 'JUSTIFICADO'; }
        $obs = $estado === 'JUSTIFICADO' ? azar(['CITA MÉDICA', 'ENFERMEDAD (PRESENTÓ CONSTANCIA)', 'VIAJE FAMILIAR', 'TRÁMITE DOCUMENTARIO']) : '';
        $filas[] = [$mat, (int) substr($d, 5, 2), $d, $estado, $obs, $d, null];
    }
}
insertar_lote('asistencia', ['id_matricula', 'mes', 'fecha', 'estado', 'observacion', 'created_at', 'updated_at'], $filas);
paso('  ' . count($filas) . ' marcas de asistencia');

// Asistencia del personal docente
$filas = [];
foreach ($DOCENTES as $d) {
    $horas_dia = [];
    foreach ($d['cursos'] as $det) { foreach ($HORARIO[$det] ?? [] as [$dia]) { $horas_dia[$dia] = ($horas_dia[$dia] ?? 0) + .75; } }
    foreach ($DIAS_CLASE as $f) {
        $dia = $DIAS[date('N', strtotime($f)) - 1];
        if (empty($horas_dia[$dia])) { continue; }
        $r = mt_rand(1, 1000);
        $estado = $r <= 955 ? 'PRESENTE' : ($r <= 985 ? 'TARDANZA' : ($r <= 993 ? 'JUSTIFICADA' : 'FALTA'));
        $filas[] = [$d['id'], $f, $estado, $horas_dia[$dia], $estado === 'FALTA' || $estado === 'JUSTIFICADA' ? 0 : $horas_dia[$dia], $estado === 'TARDANZA' ? entre(5, 20) : 0,
                    $estado === 'JUSTIFICADA' ? 'LICENCIA POR SALUD' : '', $PERSONAL['AUXILIAR']['usu'], "$f 13:30:00"];
    }
}
insertar_lote('asistencia_docente', ['docente_id', 'fecha', 'estado', 'horas_programadas', 'horas_dictadas', 'minutos_tardanza', 'observacion', 'usuario_id', 'created_at'], $filas);

// ---------------------------------------------------------------- 10. Notas por bimestre
paso('Notas de los bimestres I, II y III (y avance del IV)');
$LIT = ['AD', 'A', 'B', 'C'];
function nota_de($hab, $nivel) {
    $n = max(5, min(20, (int) round($hab + (mt_rand(-100, 100) / 100) * 2.5)));
    if ($nivel === 'SECUNDARIA') { return str_pad((string) $n, 2, '0', STR_PAD_LEFT); }
    return $n >= 18 ? 'AD' : ($n >= 14 ? 'A' : ($n >= 11 ? 'B' : 'C'));
}
$POR_AULA = [];
foreach ($EST as $mat => $e) { $POR_AULA[$e['aula']][] = $mat; }
$filas = [];
foreach ($DETALLE as $det => $d) {
    $nivel = $AULAS[$d['aula']]['nivel'];
    foreach ($BIM as $bi => $b) {
        $parcial = !$b['cerrado'];
        if ($parcial && prob(.6)) { continue; }   // en el IV bimestre solo algunos cursos ya tienen notas
        foreach ($CRITERIOS[$det] as $ci => $crit) {
            if ($parcial && $ci > 0) { break; }
            foreach ($POR_AULA[$d['aula']] ?? [] as $mat) {
                $nota = nota_de($EST[$mat]['hab'] + ($bi - 1) * .3, $nivel);
                $conc = in_array($nota, ['C'], true) || (is_numeric($nota) && (int) $nota < 11) ? 'REQUIERE REFORZAR EN CASA Y ASISTIR A RECUPERACIÓN' : '';
                $filas[] = [$mat, $b['id'], $crit, $nota, $conc, min($b['fin'], '2026-10-30')];
            }
        }
    }
}
insertar_lote('notas', ['id_matricula', 'id_bimestre', 'id_criterio', 'nota', 'conclusiones', 'creared_at'], $filas);
paso('  ' . count($filas) . ' notas');

// ---------------------------------------------------------------- 11. Tareas (con entregas y calificaciones) y exámenes
paso('Tareas y exámenes');
$TEMAS = ['MATEMÁTICA' => ['Fracciones equivalentes', 'Problemas con las cuatro operaciones', 'Ecuaciones de primer grado', 'Perímetro y área', 'Tablas y gráficos de barras', 'Porcentajes', 'Proporcionalidad'],
          'COMUNICACIÓN' => ['Comprensión lectora: el cuento', 'Redacción de una carta', 'Los sustantivos y adjetivos', 'Exposición oral', 'Textos instructivos', 'Uso de tildes'],
          'CIENCIA Y TECNOLOGÍA' => ['El ciclo del agua', 'Las plantas y sus partes', 'La célula', 'Estados de la materia', 'El sistema solar', 'Mezclas y soluciones'],
          'PERSONAL SOCIAL' => ['Mi familia y mi comunidad', 'Regiones naturales del Perú', 'Los derechos del niño', 'Símbolos patrios'],
          'CIENCIAS SOCIALES' => ['El Tahuantinsuyo', 'La independencia del Perú', 'Recursos naturales', 'El mapa político del Perú'],
          'INGLÉS' => ['My family', 'Daily routines', 'Present simple', 'Food and drinks', 'Describing places'],
          'DEFAULT' => ['Trabajo práctico', 'Investigación en casa', 'Ficha de aplicación', 'Proyecto del bimestre', 'Organizador visual']];
$VIDEOS = ['https://www.youtube.com/watch?v=GrlKeY4Uk5k', 'https://www.youtube.com/watch?v=n7a4Rwd5tVw', 'https://www.youtube.com/watch?v=ovKPCfHWs3A', 'https://www.youtube.com/watch?v=3S3mq5H3Ffk'];
$n_tareas = 0;
foreach ($DETALLE as $det => $d) {
    $nivel = $AULAS[$d['aula']]['nivel'];
    if (in_array($d['curso'], ['EDUCACIÓN FÍSICA', 'PSICOMOTRICIDAD'], true)) { continue; }
    $cada = $nivel === 'INICIAL' ? 28 : ($nivel === 'PRIMARIA' ? 18 : 14);
    $temas = $TEMAS[$d['curso']] ?? $TEMAS['DEFAULT'];
    $t = strtotime('2026-03-16') + entre(0, 6) * 86400;
    $k = 0;
    while ($t < strtotime('2026-10-30')) {
        $pub = date('Y-m-d', $t);
        if (date('N', $t) >= 6) { $t += 86400 * 2; continue; }
        $vence = date('Y-m-d', $t + entre(5, 9) * 86400) . ' 23:59:00';
        $tema = may($temas[$k % count($temas)] . ($k >= count($temas) ? ' II' : ''));
        fecha($pub . ' ' . str_pad(entre(14, 20), 2, '0', STR_PAD_LEFT) . ':' . str_pad(entre(0, 59), 2, '0', STR_PAD_LEFT));
        $r = llamar('CALL SP_REGISTRAR_TAREA(?,?,?,?,?)', [$det, $tema, $vence, may('Desarrollar la actividad en el cuaderno o en hojas y enviar una foto clara o el archivo. ' .
            azar(['Revisar la página del libro indicada en clase.', 'Pueden apoyarse en el video del enlace.', 'Trabajo individual.', 'Usar letra legible y ordenada.'])), null]);
        if ((int) reset($r) === 1) {
            $id = $db->query("SELECT id_tarea FROM tareas ORDER BY doc_ncorrelativo DESC LIMIT 1")->fetchColumn();
            if (prob(.35)) { $db->prepare("UPDATE tareas SET enlaces_video = ? WHERE id_tarea = ?")->execute([json_encode([['url' => azar($VIDEOS)]], JSON_UNESCAPED_SLASHES), $id]); }
            $vencida = $vence < HOY . ' 00:00:00';
            if ($vencida && $vence < '2026-10-16') { $db->exec("UPDATE tareas SET estado = 'FINALIZADO' WHERE id_tarea = '$id'"); }
            foreach ($db->query("SELECT id_detalle_tarea, id_matriculado FROM detalle_tarea WHERE id_tarea = '$id'")->fetchAll() as $dt) {
                $e = $EST[(int) $dt['id_matriculado']] ?? null;
                if (!$e) { continue; }
                $entrega = prob(min(.97, .55 + ($e['hab'] - 8) * .04));
                $limite = strtotime($vence);
                $f_envio = $limite - entre(1, 96) * 3600;
                if (prob(.07)) { $f_envio = $limite + entre(2, 48) * 3600; }
                if (!$entrega || $f_envio > strtotime(HOY) - 3600 || $f_envio < $t) { continue; }
                $calificada = $f_envio < strtotime(HOY) - 5 * 86400 && prob(.92);
                $nota = nota_de($e['hab'], $nivel);
                $db->prepare("UPDATE detalle_tarea SET estado = ?, fecha_envio = ?, calificacion = ?, observacion = ?, updated_at = ? WHERE id_detalle_tarea = ?")->execute([
                    $calificada ? 'CALIFICADO' : 'ENVIADO', date('Y-m-d H:i:s', $f_envio), $calificada ? $nota : null,
                    $calificada && prob(.3) ? azar(['MUY BIEN, SIGUE ASÍ', 'BUEN TRABAJO', 'REVISA LA ORTOGRAFÍA', 'FALTÓ COMPLETAR LA ÚLTIMA PARTE', 'EXCELENTE PRESENTACIÓN']) : '',
                    date('Y-m-d H:i:s', $f_envio), $dt['id_detalle_tarea']]);
            }
            $n_tareas++;
        }
        $k++;
        $t += ($cada + entre(-3, 3)) * 86400;
    }
    // Un examen por bimestre
    foreach ($BIM as $bi => $b) {
        $dia = date('Y-m-d', strtotime($b['fin']) - entre(3, 10) * 86400);
        if (date('N', strtotime($dia)) >= 6) { $dia = date('Y-m-d', strtotime($dia) - 2 * 86400); }
        fecha(date('Y-m-d', strtotime($dia) - 7 * 86400) . ' 15:00');
        $r = llamar('CALL SP_REGISTRAR_EXAMENES(?,?,?,?)', [$det, may('Evaluación del ' . ['I', 'II', 'III', 'IV'][$bi] . ' bimestre'), $dia . ' 08:00:00', may('Estudiar los temas trabajados en el bimestre')]);
        if ($dia < HOY) { $db->exec("UPDATE examen SET estado = 'REALIZADO' WHERE id_detalle_asignatura = $det AND fecha_examen = '$dia 08:00:00'"); }
    }
}
paso("  $n_tareas tareas");

// ---------------------------------------------------------------- 12. Material de apoyo del aula virtual
paso('Material de apoyo');
$MAT = [['Separata de repaso', 'Material para repasar en casa los temas de la unidad.', ['https://es.khanacademy.org/']],
        ['Video explicativo de la clase', 'Mírenlo antes de la próxima clase; tomen apuntes de las ideas principales.', [azar($VIDEOS)]],
        ['Lecturas recomendadas', 'Lecturas cortas para reforzar la comprensión. Elijan una y comenten en clase.', ['https://www.bibliotecadigital.minedu.gob.pe/']],
        ['Plan de trabajo del bimestre', 'Fechas de evaluaciones, tareas y proyectos del bimestre.', []],
        ['Página de ejercicios interactivos', 'Practiquen con los ejercicios; pueden repetirlos las veces que quieran.', ['https://phet.colorado.edu/es/']]];
foreach ($DETALLE as $det => $d) {
    $n = entre(1, 4);
    $orden = array_rand($MAT, $n);
    foreach ((array) $orden as $i) {
        $f = date('Y-m-d H:i:s', strtotime('2026-03-12') + entre(0, 230) * 86400 + entre(14, 20) * 3600);
        if ($f >= HOY) { continue; }
        insertar('material_curso', ['id_detalle_asig_docente' => $det, 'titulo' => $MAT[$i][0], 'descripcion' => $MAT[$i][1], 'carpeta' => null,
            'enlaces' => $MAT[$i][2] ? json_encode(array_map(function ($u) { return ['url' => $u]; }, $MAT[$i][2]), JSON_UNESCAPED_SLASHES) : null,
            'usu_id' => $d['usu'], 'created_at' => $f]);
    }
}

// ---------------------------------------------------------------- 13. Comunicados
paso('Comunicados, atenciones de salud y solicitudes');
$COM = [['2026-02-16', 'INICIO DEL AÑO ESCOLAR 2026', 'Estimadas familias: les damos la bienvenida al año escolar 2026. Las clases inician el lunes 9 de marzo. El horario de ingreso es 7:30 a. m. para secundaria, 7:45 a. m. para primaria y 8:00 a. m. para inicial.'],
        ['2026-03-20', 'REUNIÓN DE PADRES DE FAMILIA', 'Se convoca a la primera reunión de padres de familia el sábado 28 de marzo a las 9:00 a. m. en el auditorio. Se presentará a los tutores y el plan anual de trabajo.'],
        ['2026-05-04', 'CELEBRACIÓN DEL DÍA DE LA MADRE', 'Invitamos a todas las mamás a la actuación por el Día de la Madre el viernes 8 de mayo a las 10:00 a. m. ¡Las esperamos!'],
        ['2026-05-20', 'ENTREGA DE LIBRETAS DEL I BIMESTRE', 'Las libretas del I bimestre ya están disponibles en la plataforma, en la opción Mis notas. Los tutores atenderán consultas del 25 al 29 de mayo.'],
        ['2026-07-15', 'VACACIONES DE MEDIO AÑO', 'Las vacaciones de medio año serán del 27 de julio al 7 de agosto. Las clases se reanudan el lunes 10 de agosto.'],
        ['2026-08-24', 'SIMULACRO DE SISMO', 'El jueves 27 de agosto a las 10:00 a. m. se realizará el simulacro escolar de sismo. Pedimos puntualidad.'],
        ['2026-09-21', 'ANIVERSARIO DEL COLEGIO', 'Del 28 de septiembre al 2 de octubre celebraremos el aniversario con actividades deportivas, culturales y la gran kermés familiar.'],
        ['2026-10-20', 'ENTREGA DE LIBRETAS DEL III BIMESTRE', 'Las libretas del III bimestre ya pueden verse en la plataforma. Recuerden ponerse al día con las pensiones para recoger documentos de fin de año.']];
$usuarios_familia = array_column($EST, 'usu');
foreach ($COM as $c) {
    $id = insertar('comunicados', ['tipo' => 'GENERAL', 'id_aula' => null, 'titulo' => $c[1], 'descripcion' => $c[2], 'imagen' => '', 'estado' => 'ACTIVO',
                                   'id_usuario' => $PERSONAL['ADMINISTRADOR']['usu'], 'created_at' => $c[0]]);
    $lec = [];
    foreach ($usuarios_familia as $u) {
        if (prob($c[0] < '2026-10-01' ? .82 : .55)) { $lec[] = [$id, $u, date('Y-m-d H:i:s', strtotime($c[0]) + entre(1, 240) * 3600)]; }
    }
    insertar_lote('comunicado_lecturas', ['id_comunicado', 'usu_id', 'leido_at'], $lec);
}

// Atenciones de enfermería y psicología
$ENF = [['DOLOR DE CABEZA', 'CEFALEA TENSIONAL', 'SE LE DIO REPOSO Y AGUA. MEJORÓ.'], ['DOLOR ESTOMACAL', 'MALESTAR GÁSTRICO', 'SE COMUNICÓ A LA MAMÁ.'],
        ['CAÍDA EN EL RECREO', 'RASPÓN EN LA RODILLA', 'LIMPIEZA Y CURACIÓN DE LA HERIDA.'], ['FIEBRE', 'TEMPERATURA 38.2 °C', 'SE LLAMÓ AL APODERADO PARA QUE LO RECOJA.'],
        ['MAREO', 'BAJA DE PRESIÓN', 'SE LE DIO UN REFRIGERIO Y DESCANSO.'], ['GOLPE EN EDUCACIÓN FÍSICA', 'CONTUSIÓN LEVE EN EL BRAZO', 'COMPRESA FRÍA. SIN COMPLICACIONES.']];
$PSI = [['DIFICULTAD PARA CONCENTRARSE', 'POSIBLE FALTA DE ATENCIÓN', 'SE CITA A LOS PADRES PARA ENTREVISTA.'], ['CONFLICTO CON COMPAÑEROS', 'PROBLEMAS DE CONVIVENCIA', 'SE TRABAJÓ RESOLUCIÓN DE CONFLICTOS.'],
        ['ANSIEDAD ANTE LOS EXÁMENES', 'ANSIEDAD LEVE', 'TÉCNICAS DE RESPIRACIÓN Y SEGUIMIENTO.'], ['BAJO RENDIMIENTO', 'DESMOTIVACIÓN ESCOLAR', 'SE COORDINA CON EL TUTOR UN PLAN DE APOYO.'],
        ['ADAPTACIÓN AL COLEGIO', 'ESTUDIANTE NUEVO EN PROCESO DE ADAPTACIÓN', 'SEGUIMIENTO QUINCENAL.']];
$mats = array_keys($EST);
foreach ([['ENFERMERIA', $ENF, 70, 'ENFERMERA'], ['PSICOLOGIA', $PSI, 35, 'PSICOLOGA']] as [$tipo, $lista, $n, $rol]) {
    for ($i = 0; $i < $n; $i++) {
        $x = azar($lista);
        $f = azar($DIAS_CLASE) . ' ' . str_pad(entre(8, 13), 2, '0', STR_PAD_LEFT) . ':' . str_pad(entre(0, 59), 2, '0', STR_PAD_LEFT) . ':00';
        insertar('atencion_salud', ['id_matricula' => azar($mats), 'id_usuario' => $PERSONAL[$rol]['usu'], 'tipo_atencion' => $tipo, 'motivo_consulta' => $x[0],
                                    'diagnostico' => $x[1], 'observaciones' => $x[2], 'created_at' => $f]);
    }
}

// Solicitudes de información de la página pública
foreach ([['inicial', 'PENDIENTE'], ['primaria', 'PENDIENTE'], ['secundaria', 'CONTACTADO'], ['primaria', 'ATENDIDO'], ['inicial', 'ATENDIDO'], ['secundaria', 'PENDIENTE']] as $i => [$nivel, $estado]) {
    [$n, $a1, $a2] = persona(prob(.7) ? 'FEMENINO' : 'MASCULINO');
    insertar('solicitudes_informacion', ['nombre_completo' => "$n $a1 $a2", 'email' => strtolower(sin_tildes($n . '.' . $a1)) . '@gmail.com', 'telefono' => celular(),
        'nivel_interes' => $nivel, 'mensaje' => 'Buenas tardes, quisiera información sobre vacantes y costos para el próximo año.', 'fecha_registro' => '2026-' . (9 + intdiv($i, 2)) . '-1' . $i . ' 1' . $i . ':20:00',
        'estado' => $estado, 'ip_registro' => '190.237.' . entre(1, 254) . '.' . entre(1, 254)]);
}

// ---------------------------------------------------------------- 14. Gastos, otros ingresos y depósitos (en orden de fechas)
paso('Gastos, otros ingresos y depósitos al banco');
$IND = [];
foreach ($db->query("SELECT id_indicadores, codigo FROM indicadores") as $i) { $IND[$i['codigo']] = (int) $i['id_indicadores']; }
$grupo = function ($cod) use ($IND) { return $IND[$cod]; };
foreach ([['5.2.3', 'INTERNET Y TELEFONÍA', '5.2'], ['5.3.1', 'MANTENIMIENTO Y LIMPIEZA', '5.3'], ['5.4.1', 'MATERIAL DIDÁCTICO Y DE OFICINA', '5.4'], ['4.2.2', 'ACTIVIDADES Y EVENTOS', '4.2']] as [$cod, $nom, $padre]) {
    $IND[$cod] = insertar('indicadores', ['codigo' => $cod, 'tipo_indicador' => substr($cod, 0, 1) === '4' ? 'INGRESOS' : 'GASTOS', 'padre_id' => $IND[$padre], 'es_grupo' => 0,
                                          'nombre' => $nom, 'descripcion' => '', 'estado' => 'ACTIVO', 'created_at' => '2026-01-05']);
}
$mov = [];   // [fecha, tipo, datos]
for ($m = 1; $m <= 10; $m++) {
    $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
    $fin = date('Y-m-t', strtotime("2026-$mm-01"));
    if ($m >= 3) {
        foreach ($DOCENTES as $d) { $mov[] = [$fin, 'egreso', [$IND['5.1.1'], 1, azar([2200, 2350, 2500, 2650]), 'REMUNERACIÓN ' . may(strftime_es($m)), 4, $BANCO, 'TRF-' . mt_rand(100000, 999999), may($d['nombre']), '']]; }
    }
    foreach (['ADMINISTRADOR' => 3800, 'CONTADOR' => 2600, 'AUXILIAR' => 1600, 'ENFERMERA' => 1900, 'PSICOLOGA' => 2100] as $rol => $sueldo) {
        $mov[] = [$fin, 'egreso', [$IND['5.1.2'], 1, $sueldo, 'REMUNERACIÓN ' . may(strftime_es($m)), 4, $BANCO, 'TRF-' . mt_rand(100000, 999999), may($PERSONAL[$rol]['nombre']), '']];
    }
    $mov[] = ["2026-$mm-1" . entre(2, 8), 'egreso', [$IND['5.2.1'], 1, $m >= 3 ? entre(520, 690) : entre(180, 260), 'RECIBO DE LUZ', 1, $CAJA, '', 'ELECTRO SUR ESTE S.A.A.', 'S' . entre(100, 999) . '-' . mt_rand(100000, 999999)]];
    $mov[] = ["2026-$mm-1" . entre(2, 8), 'egreso', [$IND['5.2.2'], 1, $m >= 3 ? entre(210, 290) : entre(90, 130), 'RECIBO DE AGUA', 1, $CAJA, '', 'SEDACUSCO S.A.', 'S' . entre(100, 999) . '-' . mt_rand(100000, 999999)]];
    $mov[] = ["2026-$mm-05", 'egreso', [$IND['5.2.3'], 1, 249, 'INTERNET FIBRA ÓPTICA', 4, $BANCO, 'DEB-' . mt_rand(100000, 999999), 'MOVISTAR', 'F' . entre(100, 999) . '-' . mt_rand(10000, 99999)]];
    if ($m >= 2) { $mov[] = ["2026-$mm-2" . entre(0, 6), 'egreso', [$IND['5.3.1'], 1, entre(300, 900), 'ÚTILES DE LIMPIEZA Y MANTENIMIENTO', 1, $CAJA, '', 'DISTRIBUIDORA EL SOL', 'B' . entre(1, 9) . '-' . mt_rand(1000, 9999)]]; }
    if (in_array($m, [2, 3, 8], true)) { $mov[] = ["2026-$mm-1" . entre(0, 9), 'egreso', [$IND['5.4.1'], 1, entre(1500, 3800), 'MATERIAL DIDÁCTICO Y DE OFICINA', 4, $BANCO, 'TRF-' . mt_rand(100000, 999999), 'LIBRERÍA CENTRAL S.A.C.', 'F001-' . mt_rand(1000, 9999)]]; }
    if ($m >= 3) {   // depósito del efectivo y del Yape al banco cada fin de mes
        $mov[] = [$fin, 'deposito', [$CAJA, 1200]];
        $mov[] = [$fin, 'deposito', [$YAPE, 0]];
    }
}
for ($i = 0; $i < 45; $i++) { $mov[] = ['2026-03-0' . entre(2, 9), 'ingreso', [$IND['4.2.1'], entre(1, 3), 'VENTA DE UNIFORME ESCOLAR']]; }
for ($i = 0; $i < 12; $i++) { $mov[] = ['2026-10-0' . entre(1, 2), 'ingreso', [$IND['4.2.2'], 1, 'KERMÉS DE ANIVERSARIO - VENTA DE TICKETS']]; }
usort($mov, function ($a, $b) { return $a[0] <=> $b[0]; });
function strftime_es($m) { return ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre'][$m] . ' 2026'; }
function saldo_cuenta($c, $hasta) { global $db; $db->exec("CALL SP_SALDO_CUENTA($c, '$hasta', @s)"); return (float) $db->query("SELECT @s")->fetchColumn(); }
$rech = 0;
foreach ($mov as [$f, $tipo, $x]) {
    if ($f >= HOY) { continue; }
    fecha($f . ' 17:' . str_pad(entre(0, 59), 2, '0', STR_PAD_LEFT));
    if ($tipo === 'egreso') {
        $r = llamar('CALL SP_REGISTRAR_EGRESOS(?,?,?,?,?,?,?,?,?,?,?,?)', [$x[0], $x[1], $x[2], $x[3], $CONTADOR, $f, $x[4], $x[5], $x[6], $x[7], $x[8], null]);
        if ((int) ($r['codigo'] ?? 0) !== 1) { $rech++; }
    } elseif ($tipo === 'ingreso') {
        [$met, $cta, $ref] = forma_pago();
        $monto = $x[0] === $IND['4.2.1'] ? $x[1] * 95 : entre(80, 260);
        llamar('CALL SP_REGISTRAR_INGRESOS(?,?,?,?,?,?,?,?,?)', [$x[0], $x[1], $monto, $x[2], $CONTADOR, $f, $met, $cta, $ref]);
    } else {
        $monto = round(saldo_cuenta($x[0], date('Y-m-d H:i:s', strtotime($f . ' 23:59:59'))) - $x[1], 2);
        if ($monto > 100) {
            llamar('CALL SP_REGISTRAR_TRANSFERENCIA(?,?,?,?,?,?,?,?)', [$x[0] === $CAJA ? 'DEPOSITO' : 'TRANSFERENCIA', $x[0], $BANCO, $monto, $f,
                'OP-' . mt_rand(100000, 999999), $x[0] === $CAJA ? 'DEPÓSITO DEL EFECTIVO DEL MES' : 'TRASLADO DEL YAPE AL BANCO', $CONTADOR]);
        }
    }
}
if ($rech) { paso("  $rech gastos rechazados por saldo insuficiente"); }

// ---------------------------------------------------------------- 15. Limpieza final
fecha_hoy();
$db->exec("DELETE FROM auditoria WHERE tabla IN ('asistencia', 'notas', 'detalle_tarea', 'comunicado_lecturas')");
$db->exec("DELETE FROM login_intentos");
paso('Listo. Resumen:');
foreach (['alumnos', 'matricula', 'docentes', 'horarios', 'pagos', 'pago_pensiones', 'asistencia', 'notas', 'tareas', 'detalle_tarea', 'examen', 'material_curso',
          'comunicados', 'atencion_salud', 'egresos', 'ingresos', 'transferencias'] as $t) {
    printf("  %-18s %6d\n", $t, $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn());
}
