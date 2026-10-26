<?php
require_once 'model_conexion.php';
require_once 'model_contabilidad.php';

/**
 * Panel de inicio del administrador: el estado del colegio hoy en una sola consulta al servidor.
 */
class Modelo_Panel extends conexionBD {

    public function Admin() {
        $c = conexionBD::conexionPDO();
        $uno = function ($sql, $p = []) use ($c) {
            $q = $c->prepare($sql);
            $q->execute($p);
            return $q->fetch(PDO::FETCH_ASSOC) ?: [];
        };
        $todos = function ($sql, $p = []) use ($c) {
            $q = $c->prepare($sql);
            $q->execute($p);
            return $q->fetchAll(PDO::FETCH_ASSOC);
        };

        $anio = $uno("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, fecha_inicio, fecha_fin
                      FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1");
        $id_anio = $anio['id'] ?? 0;
        $periodos = $todos("SELECT periodos AS nombre, fecha_inicio AS inicio, fecha_fin AS fin FROM periodos
                            WHERE `id_año_escolar` = ? ORDER BY fecha_inicio", [$id_anio]);

        $comunidad = $uno("SELECT
              (SELECT COUNT(*) FROM matricula WHERE `id_año` = ?) AS estudiantes,
              (SELECT COUNT(*) FROM matricula m JOIN alumnos a ON a.Id_alumno = m.id_alumno WHERE m.`id_año` = ? AND a.tipo_alum = 'NUEVO') AS nuevos,
              (SELECT COUNT(*) FROM docentes WHERE docente_estatus = 'ACTIVO') AS docentes,
              (SELECT COUNT(*) FROM docentes WHERE docente_estatus <> 'ACTIVO') AS docentes_inactivos,
              (SELECT COUNT(*) FROM personal_admi WHERE personal_adm_estatus = 'ACTIVO') AS administrativos,
              (SELECT COUNT(*) FROM usuario WHERE usu_estatus = 'ACTIVO') AS usuarios,
              (SELECT COUNT(*) FROM usuario WHERE usu_estatus <> 'ACTIVO') AS usuarios_inactivos,
              (SELECT COUNT(*) FROM aulas a WHERE EXISTS (SELECT 1 FROM matricula m WHERE m.id_aula = a.Id_aula AND m.`id_año` = ?)) AS aulas", [$id_anio, $id_anio, $id_anio]);

        // Asistencia del día de clases (hoy, o el viernes anterior en fin de semana): marcas por estado y aulas sin registro
        $hoy = date('Y-m-d');
        $dia = (int) date('N') <= 5 ? $hoy : date('Y-m-d', strtotime('last friday'));
        $en_anio = $anio && $dia >= $anio['fecha_inicio'] && $dia <= $anio['fecha_fin'];
        $asistencia = ['dia' => $dia, 'es_hoy' => $dia === $hoy, 'en_anio' => (bool) $en_anio,
                       'P' => 0, 'T' => 0, 'F' => 0, 'J' => 0, 'registrados' => 0, 'aulas_pendientes' => []];
        if ($en_anio) {
            foreach ($todos("SELECT x.estado, COUNT(*) n FROM asistencia x JOIN matricula m ON m.id_matricula = x.id_matricula
                             WHERE x.fecha = ? AND m.`id_año` = ? GROUP BY x.estado", [$dia, $id_anio]) as $r) {
                $k = ['PRESENTE' => 'P', 'TARDE' => 'T', 'AUSENTE' => 'F', 'JUSTIFICADO' => 'J'][$r['estado']] ?? null;
                if ($k) { $asistencia[$k] = (int) $r['n']; $asistencia['registrados'] += (int) $r['n']; }
            }
            $asistencia['aulas_pendientes'] = $todos("SELECT a.Id_aula AS id, CONCAT(n.Nivel_academico, ' · ', a.Grado, IF(s.seccion_nombre <> 'UNICO', CONCAT(' ', s.seccion_nombre), '')) AS aula
                    FROM aulas a JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico JOIN seccion s ON s.seccion_id = a.id_seccion
                    WHERE EXISTS (SELECT 1 FROM matricula m WHERE m.id_aula = a.Id_aula AND m.`id_año` = ?)
                      AND NOT EXISTS (SELECT 1 FROM asistencia x JOIN matricula m ON m.id_matricula = x.id_matricula
                                      WHERE m.id_aula = a.Id_aula AND m.`id_año` = ? AND x.fecha = ?)
                    ORDER BY n.Id_nivel, a.Id_aula", [$id_anio, $id_anio, $dia]);
        }

        $pendientes = $uno("SELECT
              (SELECT COUNT(*) FROM detalle_tarea d JOIN matricula m ON m.id_matricula = d.id_matriculado WHERE d.estado = 'ENVIADO' AND m.`id_año` = ?) AS tareas_por_calificar,
              (SELECT COUNT(*) FROM atencion_salud WHERE tipo_atencion = 'ENFERMERIA' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS enfermeria_mes,
              (SELECT COUNT(*) FROM atencion_salud WHERE tipo_atencion = 'PSICOLOGIA' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS psicologia_mes", [$id_anio]);

        $comunicados = $todos("SELECT c.id_comunicado AS id, c.tipo, c.titulo, c.estado, c.created_at AS fecha,
                                      CONCAT(IFNULL(a.Grado, ''), IF(s.seccion_nombre IS NOT NULL AND s.seccion_nombre <> 'UNICO', CONCAT(' ', s.seccion_nombre), '')) AS grado
                               FROM comunicados c LEFT JOIN aulas a ON a.Id_aula = c.id_aula LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                               ORDER BY c.created_at DESC, c.id_comunicado DESC LIMIT 5");

        $empresa = $uno("SELECT empresa_id, emp_razon, emp_email, emp_cod, emp_telefono, emp_direccion, emp_logo, lema FROM empresa ORDER BY empresa_id LIMIT 1");

        return [
            'hoy' => $hoy,
            'anio' => $anio ?: null,
            'periodos' => $periodos,
            'comunidad' => array_map('intval', $comunidad),
            'asistencia' => $asistencia,
            'caja' => (new Modelo_Contabilidad())->Panel(),
            'pendientes' => array_map('intval', $pendientes),
            'comunicados' => $comunicados,
            'empresa' => $empresa,
        ];
    }
}
