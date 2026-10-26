<?php
require_once 'model_conexion.php';

/**
 * Registro mensual de asistencia (Fase 11): planilla por aula y mes como la hoja de asistencia en papel.
 * Códigos: P presente, T tardanza, F falta, J falta justificada (en la base: PRESENTE, TARDE, AUSENTE, JUSTIFICADO).
 */
class Modelo_Registro_Asistencia extends conexionBD {
    const CODIGOS = ['P' => 'PRESENTE', 'T' => 'TARDE', 'F' => 'AUSENTE', 'J' => 'JUSTIFICADO'];

    public function Anios() {
        $c = conexionBD::conexionPDO();
        return $c->query("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, estado, fecha_inicio, fecha_fin
                          FROM `año_escolar` ORDER BY `año_escolar` DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Anio($id) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, estado, fecha_inicio, fecha_fin
                          FROM `año_escolar` WHERE `Id_año_escolar` = ?");
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aulas con estudiantes matriculados en el año; el docente solo ve las aulas donde enseña ese año. */
    public function Aulas($anio, $usu_docente = null) {
        $c = conexionBD::conexionPDO();
        $sql = "SELECT a.Id_aula AS id, a.Grado AS grado, s.seccion_nombre AS seccion, n.Nivel_academico AS nivel,
                       COUNT(DISTINCT m.id_matricula) AS alumnos
                FROM aulas a
                JOIN seccion s ON s.seccion_id = a.id_seccion
                JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                JOIN matricula m ON m.id_aula = a.Id_aula AND m.`id_año` = ?";
        $p = [$anio];
        if ($usu_docente !== null) {
            $sql .= " WHERE a.Id_aula IN (SELECT asg.Id_grado FROM detalle_asignatura_docente dd
                        JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                        JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                        JOIN docentes d ON d.Id_docente = ad.Id_docente
                        WHERE d.id_asusuario = ? AND ad.`id_año` = ?)";
            $p[] = $usu_docente;
            $p[] = $anio;
        }
        $sql .= " GROUP BY a.Id_aula ORDER BY n.Id_nivel, a.Id_aula";
        $q = $c->prepare($sql);
        $q->execute($p);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Aula_Permitida($anio, $aula, $usu_docente = null) {
        foreach ($this->Aulas($anio, $usu_docente) as $a) {
            if ((int) $a['id'] === (int) $aula) {
                return $a;
            }
        }
        return null;
    }

    /** Estudiantes matriculados en el aula y año, ordenados por apellidos. */
    public function Estudiantes($anio, $aula) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT m.id_matricula AS matricula, al.alum_dni AS documento,
                                 CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, al.alum_nombre AS nombres
                          FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno
                          WHERE m.id_aula = ? AND m.`id_año` = ?
                          ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre");
        $q->execute([$aula, $anio]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Marcas del mes: [id_matricula][dia] = código (P/T/F/J). */
    public function Marcas($anio, $aula, $desde, $hasta) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT a.id_matricula, DAY(a.fecha) AS dia, a.estado
                          FROM asistencia a JOIN matricula m ON m.id_matricula = a.id_matricula
                          WHERE m.id_aula = ? AND m.`id_año` = ? AND a.fecha BETWEEN ? AND ?");
        $q->execute([$aula, $anio, $desde, $hasta]);
        $codigo = array_flip(self::CODIGOS);
        $marcas = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $marcas[$r['id_matricula']][(int) $r['dia']] = $codigo[$r['estado']] ?? '';
        }
        return $marcas;
    }

    /** Observaciones del mes: [id_matricula][dia] = texto (solo las que tienen texto). */
    public function Observaciones($anio, $aula, $desde, $hasta) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT a.id_matricula, DAY(a.fecha) AS dia, a.observacion
                          FROM asistencia a JOIN matricula m ON m.id_matricula = a.id_matricula
                          WHERE m.id_aula = ? AND m.`id_año` = ? AND a.fecha BETWEEN ? AND ? AND TRIM(IFNULL(a.observacion, '')) <> ''");
        $q->execute([$aula, $anio, $desde, $hasta]);
        $obs = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $obs[$r['id_matricula']][(int) $r['dia']] = $r['observacion'];
        }
        return $obs;
    }

    /**
     * Guarda los cambios de la planilla en una transacción. $cambios: [['matricula'=>, 'fecha'=>'Y-m-d', 'codigo'=>'P|T|F|J|']].
     * Código vacío borra la marca del día. Solo se aceptan matrículas del aula y año indicados.
     * 'obs' (opcional) reemplaza la observación del día; si no viene, la observación guardada no cambia.
     */
    public function Guardar($anio, $aula, array $cambios) {
        $c = conexionBD::conexionPDO();
        $validas = $c->prepare("SELECT id_matricula FROM matricula WHERE id_aula = ? AND `id_año` = ?");
        $validas->execute([$aula, $anio]);
        $validas = array_flip($validas->fetchAll(PDO::FETCH_COLUMN));
        $guardar = $c->prepare("INSERT INTO asistencia(id_matricula, mes, fecha, estado, observacion, created_at, updated_at)
                                VALUES (?, MONTH(?), ?, ?, '', NOW(), NULL)
                                ON DUPLICATE KEY UPDATE estado = VALUES(estado), updated_at = NOW()");
        $guardar_obs = $c->prepare("INSERT INTO asistencia(id_matricula, mes, fecha, estado, observacion, created_at, updated_at)
                                    VALUES (?, MONTH(?), ?, ?, ?, NOW(), NULL)
                                    ON DUPLICATE KEY UPDATE estado = VALUES(estado), observacion = VALUES(observacion), updated_at = NOW()");
        $borrar = $c->prepare("DELETE FROM asistencia WHERE id_matricula = ? AND fecha = ?");
        $c->beginTransaction();
        try {
            $n = 0;
            foreach ($cambios as $x) {
                if (!isset($validas[$x['matricula']])) {
                    throw new InvalidArgumentException('Estudiante que no pertenece al aula');
                }
                if ($x['codigo'] === '') {
                    $borrar->execute([$x['matricula'], $x['fecha']]);
                } elseif (isset($x['obs'])) {
                    $guardar_obs->execute([$x['matricula'], $x['fecha'], $x['fecha'], self::CODIGOS[$x['codigo']], $x['obs']]);
                } else {
                    $guardar->execute([$x['matricula'], $x['fecha'], $x['fecha'], self::CODIGOS[$x['codigo']]]);
                }
                $n++;
            }
            $c->commit();
            return $n;
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
    }

    /** Nombre del docente tutor (el primero con cursos en el aula ese año) para el encabezado del reporte. */
    public function Docentes_Aula($anio, $aula) {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare("SELECT DISTINCT CONCAT_WS(' ', d.docente_nombre, d.docente_apelli)
                          FROM detalle_asignatura_docente dd
                          JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                          JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                          JOIN docentes d ON d.Id_docente = ad.Id_docente
                          WHERE asg.Id_grado = ? AND ad.`id_año` = ? ORDER BY 1");
        $q->execute([$aula, $anio]);
        return $q->fetchAll(PDO::FETCH_COLUMN);
    }
}
