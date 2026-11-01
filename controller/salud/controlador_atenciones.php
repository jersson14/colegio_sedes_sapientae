<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
// Enfermería y Psicología: accion=listar | buscar (estudiantes para una atención nueva) | historial (de un estudiante).
// Cada rol ve solo su tipo de atención; la dirección elige el tipo. Guardar usa los controladores de siempre.
$rol = rol_actual();
$tipo = $rol === 'ENFERMERA' ? 'ENFERMERIA' : ($rol === 'PSICOLOGA' ? 'PSICOLOGIA' : (($_POST['tipo'] ?? '') === 'PSICOLOGIA' ? 'PSICOLOGIA' : 'ENFERMERIA'));
$pdo = (new conexionBD())->conexionPDO();
$accion = $_POST['accion'] ?? 'listar';

// Estudiantes del año activo: nombre, documento, aula
$ESTUDIANTE_SQL = "SELECT m.id_matricula AS matricula, al.alum_dni AS documento, al.alum_nombre AS nombres,
                          CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, al.alum_sexo AS sexo,
                          a.Grado AS grado, s.seccion_nombre AS seccion, n.Nivel_academico AS nivel
                   FROM matricula m
                   JOIN alumnos al ON al.Id_alumno = m.id_alumno
                   JOIN aulas a ON a.Id_aula = m.id_aula
                   JOIN seccion s ON s.seccion_id = a.id_seccion
                   JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico";

if ($accion === 'buscar') {
    $texto = trim((string) ($_POST['q'] ?? ''));
    if (mb_strlen($texto, 'UTF-8') < 2) {
        responder_json(['ok' => true, 'estudiantes' => []]);
    }
    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $texto) . '%';
    $q = $pdo->prepare($ESTUDIANTE_SQL . " WHERE m.`id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY `año_escolar` DESC LIMIT 1)
                                          AND (al.alum_dni LIKE ? OR CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) LIKE ?
                                               OR CONCAT_WS(' ', al.alum_apepat, al.alum_apemat, al.alum_nombre) LIKE ?)
                                          ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre LIMIT 12");
    $q->execute([$like, $like, $like]);
    responder_json(['ok' => true, 'estudiantes' => $q->fetchAll(PDO::FETCH_ASSOC)]);
}

$CAMPOS = "SELECT x.id_atencion AS id, x.id_matricula AS matricula, x.motivo_consulta AS motivo, x.diagnostico, x.observaciones, x.created_at AS fecha,
                  e.documento, e.nombres, e.apellidos, e.sexo, e.grado, e.seccion, e.nivel,
                  COALESCE(CONCAT_WS(' ', pa.personal_adm_nombre, pa.personal_adm_apellido), u.usu_usuario) AS atendio
           FROM atencion_salud x
           JOIN (" . $ESTUDIANTE_SQL . ") e ON e.matricula = x.id_matricula
           LEFT JOIN usuario u ON u.usu_id = x.id_usuario
           LEFT JOIN personal_admi pa ON pa.id_ausuario = x.id_usuario";

if ($accion === 'historial') {
    $q = $pdo->prepare($CAMPOS . " WHERE x.tipo_atencion = ? AND x.id_matricula = ? ORDER BY x.created_at DESC");
    $q->execute([$tipo, entero_post('matricula', 1, 'matrícula')]);
    responder_json(['ok' => true, 'atenciones' => $q->fetchAll(PDO::FETCH_ASSOC)]);
}

// Listar: rango de fechas (por defecto los últimos 30 días) y texto opcional
$fecha = function ($k, $defecto) {
    $v = (string) ($_POST[$k] ?? '');
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return $d && $d->format('Y-m-d') === $v ? $v : $defecto;
};
$desde = $fecha('desde', date('Y-m-d', strtotime('-30 days')));
$hasta = $fecha('hasta', date('Y-m-d'));
$q = $pdo->prepare($CAMPOS . " WHERE x.tipo_atencion = ? AND DATE(x.created_at) BETWEEN ? AND ? ORDER BY x.created_at DESC LIMIT 500");
$q->execute([$tipo, $desde, $hasta]);
$lista = $q->fetchAll(PDO::FETCH_ASSOC);
$r = $pdo->prepare("SELECT SUM(DATE(created_at) = CURDATE()) AS hoy, SUM(YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)) AS semana,
                           SUM(DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS mes, COUNT(DISTINCT id_matricula) AS estudiantes
                    FROM atencion_salud WHERE tipo_atencion = ? AND YEAR(created_at) = YEAR(CURDATE())");
$r->execute([$tipo]);
$resumen = array_map('intval', $r->fetch(PDO::FETCH_ASSOC) ?: []);
$m = $pdo->prepare("SELECT motivo_consulta AS motivo, COUNT(*) AS veces FROM atencion_salud WHERE tipo_atencion = ? AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY motivo_consulta ORDER BY veces DESC LIMIT 5");
$m->execute([$tipo, $desde, $hasta]);
responder_json(['ok' => true, 'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'resumen' => $resumen,
                'motivos' => $m->fetchAll(PDO::FETCH_ASSOC), 'atenciones' => $lista]);
