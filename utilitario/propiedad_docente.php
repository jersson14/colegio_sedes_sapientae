<?php
/**
 * El docente solo trabaja con SUS cursos (Fase 11): notas, exámenes y tareas.
 * Administrador y auxiliar no tienen esta restricción. Requiere controller/_seguridad.php.
 */
require_once __DIR__ . '/../model/model_conexion.php';

function propiedad_pdo() {
    static $pdo = null;
    return $pdo ?: ($pdo = (new conexionBD())->conexionPDO());
}

/** El curso (detalle_asignatura_docente) está asignado al docente con ese usuario. */
function curso_es_del_docente($curso, $usu_id) {
    $q = propiedad_pdo()->prepare("SELECT 1 FROM detalle_asignatura_docente dd
                                   JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                                   JOIN docentes d ON d.Id_docente = ad.Id_docente
                                   WHERE dd.Id_detalle_asig_docente = ? AND d.id_asusuario = ? LIMIT 1");
    $q->execute([$curso, $usu_id]);
    return (bool) $q->fetchColumn();
}

/** Corta con 403 si el usuario es DOCENTE y el curso no es suyo. */
function exigir_curso_propio($curso, $ruta) {
    if (rol_actual() === 'DOCENTE' && !curso_es_del_docente($curso, usuario_actual_id())) {
        registrar_acceso_denegado($ruta, 'CURSO_AJENO');
        denegar(403, 'Ese curso no está asignado a usted');
    }
}

/** Curso de un examen ('' si no existe). */
function curso_del_examen($id_examen) {
    $q = propiedad_pdo()->prepare("SELECT id_detalle_asignatura FROM examen WHERE id_examen = ?");
    $q->execute([$id_examen]);
    return (string) $q->fetchColumn();
}

/**
 * Notas: cada registro {id_matri, cri} debe ser de un criterio de un curso del docente
 * y de un estudiante matriculado en el aula de ese curso (en el año escolar de la asignación).
 */
function exigir_notas_propias(array $registros, $ruta) {
    if (rol_actual() !== 'DOCENTE') {
        return;
    }
    $q = propiedad_pdo()->prepare("SELECT 1 FROM criterios c
                                   JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = c.id_detalle_asignatura
                                   JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                                   JOIN docentes d ON d.Id_docente = ad.Id_docente
                                   JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                   JOIN matricula m ON m.id_aula = asg.Id_grado AND m.`id_año` = ad.`id_año`
                                   WHERE c.id_criterio = ? AND m.id_matricula = ? AND d.id_asusuario = ? LIMIT 1");
    $vistos = [];
    foreach ($registros as $r) {
        $clave = ($r['cri'] ?? '') . '|' . ($r['id_matri'] ?? '');
        if (isset($vistos[$clave])) {
            continue;
        }
        $q->execute([$r['cri'] ?? 0, $r['id_matri'] ?? 0, usuario_actual_id()]);
        if (!$q->fetchColumn()) {
            registrar_acceso_denegado($ruta, 'NOTA_CURSO_AJENO');
            denegar(403, 'Solo puede registrar notas de sus cursos y de los estudiantes de esas aulas');
        }
        $vistos[$clave] = true;
    }
}
