<?php
require_once 'model_conexion.php';

/**
 * Registro de tareas (Fase 11): cuadrícula estudiantes × tareas de un curso, como el registro auxiliar del docente.
 */
class Modelo_Registro_Tareas extends conexionBD {

    /** Cursos (asignatura + aula + docente) del año; el docente solo ve los suyos. */
    public function Cursos($anio, $usu_docente = null) {
        $c = conexionBD::conexionPDO();
        $sql = "SELECT dd.Id_detalle_asig_docente AS id, asg.nombre_asig AS asignatura, a.Grado AS grado, s.seccion_nombre AS seccion,
                       n.Nivel_academico AS nivel, CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente,
                       (SELECT COUNT(*) FROM tareas t WHERE t.id_detalle_asignatura = dd.Id_detalle_asig_docente) AS tareas
                FROM detalle_asignatura_docente dd
                JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                JOIN docentes d ON d.Id_docente = ad.Id_docente
                JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                JOIN aulas a ON a.Id_aula = asg.Id_grado
                JOIN seccion s ON s.seccion_id = a.id_seccion
                JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                WHERE ad.`id_año` = ?" . ($usu_docente !== null ? " AND d.id_asusuario = ?" : '') . "
                ORDER BY n.Id_nivel, a.Id_aula, asg.nombre_asig";
        $q = $c->prepare($sql);
        $q->execute($usu_docente !== null ? [$anio, $usu_docente] : [$anio]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Periodos($anio) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT id_periodo AS id, periodos AS nombre, fecha_inicio, fecha_fin
                          FROM periodos WHERE `id_año_escolar` = ? ORDER BY fecha_inicio");
        $q->execute([$anio]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Estudiantes del aula del curso que aún no tienen la tarea (matriculados después) la reciben como pendiente. */
    public function Completar_Envios($curso) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("INSERT INTO detalle_tarea(id_tarea, id_matriculado, archivo_evnio_tarea, calificacion, observacion, estado, fecha_envio, created_at, updated_at)
                          SELECT t.id_tarea, m.id_matricula, '', NULL, '', 'PENDIENTE', NULL, NOW(), NULL
                          FROM tareas t
                          JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
                          JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                          JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                          JOIN matricula m ON m.id_aula = asg.Id_grado AND m.`id_año` = ad.`id_año`
                          WHERE t.id_detalle_asignatura = ? AND t.estado = 'PENDIENTE'
                            AND NOT EXISTS (SELECT 1 FROM detalle_tarea x WHERE x.id_tarea = t.id_tarea AND x.id_matriculado = m.id_matricula)");
        $q->execute([$curso]);
    }

    /** Tareas del curso (opcionalmente entre dos fechas de entrega). */
    public function Tareas($curso, $desde = null, $hasta = null) {
        $c = conexionBD::conexionPDO();
        $sql = "SELECT id_tarea AS id, tema, fecha_entrega, estado, archivo_tarea AS carpeta FROM tareas WHERE id_detalle_asignatura = ?";
        $p = [$curso];
        if ($desde && $hasta) {
            $sql .= " AND DATE(fecha_entrega) BETWEEN ? AND ?";
            $p[] = $desde;
            $p[] = $hasta;
        }
        $q = $c->prepare($sql . " ORDER BY fecha_entrega, id_tarea");
        $q->execute($p);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Estudiantes del aula del curso con sus envíos: [matricula => [id_tarea => envío]]. */
    public function Estudiantes_Envios($curso, array $ids_tareas) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT m.id_matricula AS matricula, al.alum_dni AS documento,
                                 CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, al.alum_nombre AS nombres
                          FROM detalle_asignatura_docente dd
                          JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                          JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                          JOIN matricula m ON m.id_aula = asg.Id_grado AND m.`id_año` = ad.`id_año`
                          JOIN alumnos al ON al.Id_alumno = m.id_alumno
                          WHERE dd.Id_detalle_asig_docente = ?
                          ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre");
        $q->execute([$curso]);
        $estudiantes = $q->fetchAll(PDO::FETCH_ASSOC);
        $envios = [];
        if ($ids_tareas) {
            $marcas = implode(',', array_fill(0, count($ids_tareas), '?'));
            $q = $c->prepare("SELECT id_detalle_tarea AS id, id_tarea, id_matriculado, estado, calificacion, observacion, fecha_envio,
                                     archivo_evnio_tarea AS carpeta
                              FROM detalle_tarea WHERE id_tarea IN ($marcas)");
            $q->execute($ids_tareas);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $envios[$r['id_matriculado']][$r['id_tarea']] = $r;
            }
        }
        foreach ($estudiantes as &$e) {
            $e['envios'] = (object) ($envios[$e['matricula']] ?? []);
        }
        return $estudiantes;
    }
}
