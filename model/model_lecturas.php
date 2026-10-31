<?php
require_once 'model_conexion.php';

/**
 * Comunicados con lectura (Fase 13, paso 14): quién abrió cada comunicado y a qué familias falta avisar.
 * Las familias leen con la cuenta del estudiante; la lectura cuenta desde la primera vez que lo abren.
 */
class Modelo_Lecturas extends conexionBD {
    private $c;

    private function uno($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetch(PDO::FETCH_ASSOC) ?: null; }
    private function todos($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetchAll(PDO::FETCH_ASSOC); }

    /** Aula del estudiante en el año activo (o null). */
    private function aula_estudiante($usu) {
        $r = $this->uno("SELECT m.id_aula FROM matricula m WHERE m.usu_id = ? AND m.`id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE `año_escolar` = FN_ANIO_ESCOLAR_ACTUAL() ORDER BY estado = 'ACTIVO' DESC LIMIT 1)
                         ORDER BY m.id_matricula DESC LIMIT 1", [$usu]);
        return $r ? (int) $r['id_aula'] : null;
    }

    /**
     * Abre un comunicado: devuelve su contenido y registra la lectura. El estudiante solo abre los generales y los de
     * su aula; un comunicado oculto no lo abre nadie más que la dirección. null si no corresponde.
     */
    public function Ver($id, $usu, $rol) {
        $this->c = $this->conexionPDO();
        $com = $this->uno("SELECT c.id_comunicado AS id, c.tipo, c.id_aula, c.titulo, c.descripcion, c.imagen, c.estado, c.created_at AS fecha,
                                  CONCAT(IFNULL(n.Nivel_academico, ''), ' ', IFNULL(a.Grado, '')) AS grado
                           FROM comunicados c LEFT JOIN aulas a ON a.Id_aula = c.id_aula LEFT JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                           WHERE c.id_comunicado = ?", [$id]);
        if (!$com) { return null; }
        if ($rol !== 'ADMINISTRADOR') {
            if ($com['estado'] !== 'ACTIVO') { return null; }
            if ($rol === 'ESTUDIANTE' && $com['tipo'] !== 'GENERAL' && (int) $com['id_aula'] !== $this->aula_estudiante($usu)) { return null; }
        }
        $q = $this->c->prepare("INSERT IGNORE INTO comunicado_lecturas(id_comunicado, usu_id, leido_at) VALUES (?, ?, NOW())");
        $q->execute([$id, $usu]);
        unset($com['id_aula']);
        return $com;
    }

    /**
     * Familias a las que va dirigido un comunicado (estudiantes del año activo: todos, o los de su aula) con la fecha
     * en que lo leyeron y el celular de la madre o del padre para volver a avisar.
     */
    public function Lectura($id) {
        $this->c = $this->conexionPDO();
        $com = $this->uno("SELECT id_comunicado AS id, tipo, id_aula, titulo, created_at AS fecha FROM comunicados WHERE id_comunicado = ?", [$id]);
        if (!$com) { return null; }
        $filtro = $com['tipo'] === 'GENERAL' ? '' : 'AND m.id_aula = ?';
        $p = [$id];
        if ($filtro) { $p[] = $com['id_aula']; }
        $familias = $this->todos("SELECT m.id_matricula AS matricula, CONCAT_WS(' ', al.alum_apepat, al.alum_apemat, al.alum_nombre) AS estudiante,
                                         CONCAT(n.Nivel_academico, ' · ', a.Grado) AS aula, m.usu_id IS NOT NULL AS con_usuario, l.leido_at,
                                         (SELECT NULLIF(TRIM(p.Celular_mama), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS cel_madre,
                                         (SELECT NULLIF(TRIM(p.Celular_papa), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS cel_padre,
                                         (SELECT NULLIF(TRIM(p.Datos_mama), '') FROM padres p WHERE p.id_alu = al.Id_alumno ORDER BY p.id_papas DESC LIMIT 1) AS madre
                                  FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno
                                  JOIN aulas a ON a.Id_aula = m.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                                  LEFT JOIN comunicado_lecturas l ON l.id_comunicado = ? AND l.usu_id = m.usu_id
                                  WHERE m.`id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE `año_escolar` = FN_ANIO_ESCOLAR_ACTUAL() ORDER BY estado = 'ACTIVO' DESC LIMIT 1) $filtro
                                  ORDER BY l.leido_at IS NOT NULL, n.Id_nivel, a.Id_aula, al.alum_apepat, al.alum_apemat", $p);
        unset($com['id_aula']);
        $leidos = count(array_filter($familias, function ($f) { return $f['leido_at']; }));
        return ['comunicado' => $com, 'familias' => $familias, 'leidos' => $leidos, 'total' => count($familias)];
    }
}
