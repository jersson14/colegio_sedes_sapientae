<?php
declare(strict_types=1);

/**
 * Pertenencia del dato (IDOR), Fase 0 — complemento de exigir_rol().
 *
 * El rol dice QUÉ puede hacer un usuario; esto comprueba SOBRE QUÉ registros.
 * Hoy cubre al ESTUDIANTE: solo ve y modifica lo suyo. Los demás roles
 * conservan el comportamiento anterior (pendiente: DOCENTE → solo sus aulas).
 *
 * Enlace de identidad: matricula.usu_id = $_SESSION['S_ID'] del estudiante.
 *
 * Requiere core/guard.php (sesión cargada y responder_error()).
 */

require_once __DIR__ . '/../model/model_conexion.php';

function es_estudiante(): bool
{
    return ($_SESSION['S_ROL'] ?? '') === 'ESTUDIANTE';
}

/** Para un ESTUDIANTE el id de usuario sale de la sesión, nunca del cliente. */
function id_usuario_propio(?string $recibido): ?string
{
    return es_estudiante() ? (string)$_SESSION['S_ID'] : $recibido;
}

/** Igual, para endpoints que identifican al usuario por su DNI. */
function dni_propio(string $recibido, string ...$roles): string
{
    return in_array($_SESSION['S_ROL'] ?? '', $roles, true) ? (string)($_SESSION['S_DNI'] ?? '') : $recibido;
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

/** La matrícula debe ser del estudiante en sesión. */
function exigir_matricula_propia(string $idMatricula): void
{
    if (es_estudiante() && !pertenencia_existe(
        'SELECT 1 FROM matricula WHERE id_matricula = ? AND usu_id = ?',
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

/** El estudiante debe estar (o haber estado) matriculado en el aula. */
function exigir_aula_propia(string $idAula): void
{
    if (es_estudiante() && !pertenencia_existe(
        'SELECT 1 FROM matricula WHERE id_aula = ? AND usu_id = ?',
        [$idAula, $_SESSION['S_ID']]
    )) {
        denegar_ajeno();
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
 * Carpeta de tarea visible para el estudiante: su propio envío, o el
 * enunciado de una tarea que le fue asignada (tiene detalle_tarea).
 */
function exigir_carpeta_tarea_visible(string $nombreCarpeta): void
{
    if (!es_estudiante()) {
        return;
    }
    $sufijo = '%/' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $nombreCarpeta);
    if (!pertenencia_existe(
        'SELECT 1 FROM detalle_tarea dt
           JOIN matricula m ON m.id_matricula = dt.id_matriculado
           JOIN tareas t    ON t.id_tarea = dt.id_tarea
          WHERE m.usu_id = ? AND (dt.archivo_evnio_tarea LIKE ? OR t.archivo_tarea LIKE ?)',
        [$_SESSION['S_ID'], $sufijo, $sufijo]
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
