<?php
require_once 'model_conexion.php';

/**
 * Registro de notas de un curso: estudiantes × criterios de un periodo, como el registro auxiliar del docente.
 * Se guarda nota por nota (inserta, corrige o borra); los triggers de auditoría registran cada cambio.
 */
class Modelo_Registro_Notas extends conexionBD {

    /** Criterios activos del curso. */
    public function Criterios($curso) {
        $q = conexionBD::conexionPDO()->prepare("SELECT id_criterio AS id, competencias AS nombre FROM criterios
                                                 WHERE id_detalle_asignatura = ? AND (estado IS NULL OR estado = 'ACTIVO')
                                                 ORDER BY id_criterio");
        $q->execute([$curso]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Aula y año del curso. */
    public function Curso($curso) {
        $q = conexionBD::conexionPDO()->prepare("SELECT asg.Id_grado AS aula, ad.`id_año` AS anio FROM detalle_asignatura_docente dd
                                                 JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                                                 JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                                                 WHERE dd.Id_detalle_asig_docente = ?");
        $q->execute([$curso]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function Periodo($id) {
        $q = conexionBD::conexionPDO()->prepare("SELECT id_periodo AS id, `id_año_escolar` AS anio, periodos AS nombre, fecha_inicio, fecha_fin, estado
                                                 FROM periodos WHERE id_periodo = ?");
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function Periodos($anio) {
        $q = conexionBD::conexionPDO()->prepare("SELECT id_periodo AS id, periodos AS nombre, fecha_inicio, fecha_fin, estado
                                                 FROM periodos WHERE `id_año_escolar` = ? ORDER BY fecha_inicio, id_periodo");
        $q->execute([$anio]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Estudiantes matriculados en el aula del curso, en el año del curso. */
    public function Estudiantes($aula, $anio) {
        $q = conexionBD::conexionPDO()->prepare("SELECT m.id_matricula AS matricula, CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos,
                                                        al.alum_nombre AS nombres
                                                 FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno
                                                 WHERE m.id_aula = ? AND m.`id_año` = ?
                                                 ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre");
        $q->execute([$aula, $anio]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Notas del curso en el periodo: [matricula][criterio] = ['nota', 'conclusion']. */
    public function Notas($curso, $periodo) {
        $q = conexionBD::conexionPDO()->prepare("SELECT n.id_matricula, n.id_criterio, n.nota, n.conclusiones FROM notas n
                                                 JOIN criterios c ON c.id_criterio = n.id_criterio
                                                 WHERE c.id_detalle_asignatura = ? AND n.id_bimestre = ?");
        $q->execute([$curso, $periodo]);
        $res = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $res[$r['id_matricula']][$r['id_criterio']] = ['nota' => $r['nota'], 'conclusion' => (string) $r['conclusiones']];
        }
        return $res;
    }

    /** Aplica los cambios en una transacción: nota vacía borra; si existe la corrige; si no, la inserta. */
    public function Guardar($periodo, array $cambios) {
        $c = conexionBD::conexionPDO();
        $buscar = $c->prepare("SELECT id_nota_bole FROM notas WHERE id_matricula = ? AND id_bimestre = ? AND id_criterio = ? LIMIT 1");
        $insertar = $c->prepare("INSERT INTO notas(id_matricula, id_bimestre, id_criterio, nota, conclusiones, creared_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $corregir = $c->prepare("UPDATE notas SET nota = ?, conclusiones = ?, updated_at = NOW() WHERE id_nota_bole = ?");
        $borrar = $c->prepare("DELETE FROM notas WHERE id_nota_bole = ?");
        $n = 0;
        $c->beginTransaction();
        try {
            foreach ($cambios as $x) {
                $buscar->execute([$x['matricula'], $periodo, $x['criterio']]);
                $id = $buscar->fetchColumn();
                if ($x['nota'] === '') {
                    if ($id) { $borrar->execute([$id]); $n++; }
                } elseif ($id) {
                    $corregir->execute([$x['nota'], $x['conclusion'], $id]); $n++;
                } else {
                    $insertar->execute([$x['matricula'], $periodo, $x['criterio'], $x['nota'], $x['conclusion']]); $n++;
                }
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        return $n;
    }
}
