<?php

declare(strict_types=1);

/**
 * Pertenencia del dato (IDOR), Fase 0 — complemento de exigir_rol().
 *
 * El rol dice QUÉ puede hacer un usuario; esto comprueba SOBRE QUÉ registros.
 *   ESTUDIANTE → solo lo suyo:   matricula.usu_id = S_ID
 *   DOCENTE    → solo sus cursos y las aulas donde los dicta:
 *                usuario.usu_id = docentes.id_asusuario
 *                → asignatura_docente → detalle_asignatura_docente (Id_detalle_asig_docente)
 *                → tareas / examen / criterios .id_detalle_asignatura
 *                → asignaturas.Id_grado = aula
 *   ADMINISTRADOR y AUXILIAR conservan el comportamiento anterior.
 *
 * Requiere core/guard.php (sesión cargada y responder_error()).
 */

require_once __DIR__ . '/../model/model_conexion.php';

// Cursos (Id_detalle_asig_docente) del docente en sesión. Parámetro: S_ID.
const SQL_CURSOS_DOCENTE = 'SELECT dad.Id_detalle_asig_docente
      FROM detalle_asignatura_docente dad
      JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente
      JOIN docentes d            ON d.Id_docente = ad.Id_docente
     WHERE d.id_asusuario = ?';

// Aulas donde dicta el docente en sesión. Parámetro: S_ID.
const SQL_AULAS_DOCENTE = 'SELECT a.Id_grado
      FROM detalle_asignatura_docente dad
      JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente
      JOIN docentes d            ON d.Id_docente = ad.Id_docente
      JOIN asignaturas a         ON a.Id_asignatura = dad.Id_asignatura
     WHERE d.id_asusuario = ?';

function es_estudiante(): bool
{
    return ($_SESSION['S_ROL'] ?? '') === 'ESTUDIANTE';
}

function es_docente(): bool
{
    return ($_SESSION['S_ROL'] ?? '') === 'DOCENTE';
}

/**
 * Endpoints que la interfaz llama siempre con el id del propio usuario
 * (txtprincipalid): salvo el administrador, el id sale de la sesión.
 */
function id_usuario_propio(?string $recibido): ?string
{
    return ($_SESSION['S_ROL'] ?? '') !== 'ADMINISTRADOR' ? (string)$_SESSION['S_ID'] : $recibido;
}

/** Igual, para endpoints que identifican al usuario por su DNI. */
function dni_propio(string $recibido, string ...$roles): string
{
    return in_array($_SESSION['S_ROL'] ?? '', $roles, true) ? (string)($_SESSION['S_DNI'] ?? '') : $recibido;
}

/** Para un DOCENTE, su Id_docente (tabla docentes) sale de la sesión. */
function id_docente_propio(?string $recibido): ?string
{
    if (!es_docente()) {
        return $recibido;
    }
    $q = pertenencia_pdo()->prepare('SELECT Id_docente FROM docentes WHERE id_asusuario = ?');
    $q->execute([$_SESSION['S_ID']]);
    $id = $q->fetchColumn();
    $q->closeCursor();
    return $id === false ? '0' : (string)$id;
}

function pertenencia_pdo(): PDO
{
    static $pdo = null;
    return $pdo ??= (new conexionBD())->conexionPDO();
}

function pertenencia_existe(string $sql, array $params): bool
{
    $q = pertenencia_pdo()->prepare($sql);
    $q->execute($params);
    $ok = $q->fetchColumn() !== false;
    $q->closeCursor();
    return $ok;
}

function denegar_ajeno(): never
{
    responder_error(403, 'El registro solicitado no te pertenece');
}

/** Estudiante: su matrícula. Docente: matrícula de un aula donde dicta. */
function exigir_matricula_propia(string $idMatricula): void
{
    if (es_estudiante() && !pertenencia_existe(
        'SELECT 1 FROM matricula WHERE id_matricula = ? AND usu_id = ?',
        [$idMatricula, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
    }
    if (es_docente() && !pertenencia_existe(
        'SELECT 1 FROM matricula WHERE id_matricula = ? AND id_aula IN (' . SQL_AULAS_DOCENTE . ')',
        [$idMatricula, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
    }
}

/** El pago debe pertenecer a una matrícula del estudiante en sesión. */
function exigir_pago_propio(string $idMatricula, string $idPago): void
{
    if (es_estudiante() && !pertenencia_existe(
        'SELECT 1 FROM pago_pensiones p JOIN matricula m ON m.id_matricula = p.id_matri
          WHERE p.id_pago_pension = ? AND p.id_matri = ? AND m.usu_id = ?',
        [$idPago, $idMatricula, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
    }
}

/** Estudiante: aula donde está matriculado. Docente: aula donde dicta. */
function exigir_aula_propia(string $idAula): void
{
    if (es_estudiante() && !pertenencia_existe(
        'SELECT 1 FROM matricula WHERE id_aula = ? AND usu_id = ?',
        [$idAula, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
    }
    if (es_docente() && !pertenencia_existe(
        'SELECT 1 FROM (' . SQL_AULAS_DOCENTE . ') x WHERE x.Id_grado = ?',
        [$_SESSION['S_ID'], $idAula]
    )) {
        denegar_ajeno();
    }
}

/** Docente: el curso (Id_detalle_asig_docente) debe ser suyo. */
function exigir_curso_propio(string $idDetalleAsignatura): void
{
    if (es_docente() && !pertenencia_existe(
        'SELECT 1 FROM (' . SQL_CURSOS_DOCENTE . ') x WHERE x.Id_detalle_asig_docente = ?',
        [$_SESSION['S_ID'], $idDetalleAsignatura]
    )) {
        denegar_ajeno();
    }
}

/**
 * Docente: la tarea debe ser de uno de sus cursos. Devuelve la carpeta de
 * la tarea según la BD (para no confiar en la que envía el cliente);
 * para otros roles devuelve $archivoRecibido.
 */
function exigir_tarea_propia(string $idTarea, string $archivoRecibido = ''): string
{
    if (!es_docente()) {
        return $archivoRecibido;
    }
    $q = pertenencia_pdo()->prepare(
        'SELECT archivo_tarea FROM tareas
          WHERE id_tarea = ? AND id_detalle_asignatura IN (' . SQL_CURSOS_DOCENTE . ')'
    );
    $q->execute([$idTarea, $_SESSION['S_ID']]);
    $fila = $q->fetch(PDO::FETCH_NUM);
    $q->closeCursor();
    if ($fila === false) {
        denegar_ajeno();
    }
    return (string)($fila[0] ?? '');
}

/** Docente: el examen debe ser de uno de sus cursos. */
function exigir_examen_propio(string $idExamen): void
{
    if (es_docente() && !pertenencia_existe(
        'SELECT 1 FROM examen WHERE id_examen = ? AND id_detalle_asignatura IN (' . SQL_CURSOS_DOCENTE . ')',
        [$idExamen, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
    }
}

/** Docente: solo califica envíos de tareas de sus cursos. */
function exigir_envio_calificable(string $idDetalleTarea): void
{
    if (es_docente() && !pertenencia_existe(
        'SELECT 1 FROM detalle_tarea dt JOIN tareas t ON t.id_tarea = dt.id_tarea
          WHERE dt.id_detalle_tarea = ? AND t.id_detalle_asignatura IN (' . SQL_CURSOS_DOCENTE . ')',
        [$idDetalleTarea, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
    }
}

/**
 * Docente: cada nota debe ir a un criterio de SU curso y a un alumno
 * matriculado en el aula de ese curso. $registros = [['id_matri','cri',...], ...]
 */
function exigir_notas_propias(array $registros): void
{
    if (!es_docente()) {
        return;
    }
    $q = pertenencia_pdo()->prepare(
        'SELECT 1 FROM criterios c
           JOIN detalle_asignatura_docente dad ON dad.Id_detalle_asig_docente = c.id_detalle_asignatura
           JOIN asignaturas a                  ON a.Id_asignatura = dad.Id_asignatura
           JOIN matricula m                    ON m.id_aula = a.Id_grado
          WHERE c.id_criterio = ? AND m.id_matricula = ?
            AND c.id_detalle_asignatura IN (' . SQL_CURSOS_DOCENTE . ')'
    );
    foreach ($registros as $r) {
        $q->execute([(string)($r['cri'] ?? ''), (string)($r['id_matri'] ?? ''), $_SESSION['S_ID']]);
        $ok = $q->fetchColumn() !== false;
        $q->closeCursor();
        if (!$ok) {
            denegar_ajeno();
        }
    }
}

/**
 * El envío de tarea debe ser del estudiante. Devuelve la carpeta actual
 * guardada en la BD: el cliente no decide qué carpeta se borra.
 * Para otros roles devuelve $archivoRecibido sin cambios.
 */
function exigir_envio_propio(string $idDetalle, string $archivoRecibido): string
{
    if (!es_estudiante()) {
        return $archivoRecibido;
    }
    $q = pertenencia_pdo()->prepare(
        'SELECT dt.archivo_evnio_tarea FROM detalle_tarea dt
           JOIN matricula m ON m.id_matricula = dt.id_matriculado
          WHERE dt.id_detalle_tarea = ? AND m.usu_id = ?'
    );
    $q->execute([$idDetalle, $_SESSION['S_ID']]);
    $fila = $q->fetch(PDO::FETCH_NUM);
    $q->closeCursor();
    if ($fila === false) {
        denegar_ajeno();
    }
    return (string)($fila[0] ?? '');
}

/**
 * Carpeta de tarea visible.
 * Estudiante: su propio envío o el enunciado de una tarea asignada.
 * Docente: enunciados y envíos de tareas de sus cursos.
 */
function exigir_carpeta_tarea_visible(string $nombreCarpeta): void
{
    $sufijo = '%/' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $nombreCarpeta);
    if (es_estudiante() && !pertenencia_existe(
        'SELECT 1 FROM detalle_tarea dt
           JOIN matricula m ON m.id_matricula = dt.id_matriculado
           JOIN tareas t    ON t.id_tarea = dt.id_tarea
          WHERE m.usu_id = ? AND (dt.archivo_evnio_tarea LIKE ? OR t.archivo_tarea LIKE ?)',
        [$_SESSION['S_ID'], $sufijo, $sufijo]
    )) {
        denegar_ajeno();
    }
    if (es_docente() && !pertenencia_existe(
        'SELECT 1 FROM tareas t
           LEFT JOIN detalle_tarea dt ON dt.id_tarea = t.id_tarea AND dt.archivo_evnio_tarea LIKE ?
          WHERE t.id_detalle_asignatura IN (' . SQL_CURSOS_DOCENTE . ')
            AND (t.archivo_tarea LIKE ? OR dt.id_detalle_tarea IS NOT NULL)',
        [$sufijo, $_SESSION['S_ID'], $sufijo]
    )) {
        denegar_ajeno();
    }
}

/** Foto actual del docente según la BD (no la que dice el cliente). */
function foto_actual_docente(string $dni): string
{
    $q = pertenencia_pdo()->prepare('SELECT docente_fotoperfil FROM docentes WHERE docente_dni = ?');
    $q->execute([$dni]);
    $foto = $q->fetchColumn();
    $q->closeCursor();
    return $foto === false ? '' : (string)$foto;
}

/** Foto actual del alumno según la BD (no la que dice el cliente). */
function foto_actual_alumno(string $dni): string
{
    $q = pertenencia_pdo()->prepare('SELECT alum_fotoperfil FROM alumnos WHERE alum_dni = ?');
    $q->execute([$dni]);
    $foto = $q->fetchColumn();
    $q->closeCursor();
    return $foto === false ? '' : (string)$foto;
}
