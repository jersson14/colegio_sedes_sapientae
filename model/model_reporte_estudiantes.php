<?php
require_once 'model_conexion.php';

/**
 * Reporte de estudiantes a medida (Fase 11): el usuario elige los campos y los filtros.
 * Los campos salen de este catálogo (nunca del navegador), así la consulta es siempre segura.
 */
class Modelo_Reporte_Estudiantes extends conexionBD {

    /** clave => [grupo, título, expresión SQL, tipo (texto|numero|moneda|porcentaje)] */
    public static function Campos() {
        $asis = "(SELECT %s FROM asistencia x WHERE x.id_matricula = m.id_matricula)";
        $deuda = "(SELECT %s FROM vw_estado_pensiones v WHERE v.id_matricula = m.id_matricula AND v.vencida = 1 AND v.saldo_pension + v.saldo_mora > 0)";
        return [
            'documento'     => ['Estudiante', 'Documento', 'al.alum_dni', 'texto'],
            'apellidos'     => ['Estudiante', 'Apellidos', "CONCAT_WS(' ', al.alum_apepat, al.alum_apemat)", 'texto'],
            'nombres'       => ['Estudiante', 'Nombres', 'al.alum_nombre', 'texto'],
            'sexo'          => ['Estudiante', 'Sexo', 'al.alum_sexo', 'texto'],
            'nacimiento'    => ['Estudiante', 'Fecha de nacimiento', "DATE_FORMAT(al.alum_fechanacimiento, '%d/%m/%Y')", 'texto'],
            'edad'          => ['Estudiante', 'Edad', 'TIMESTAMPDIFF(YEAR, al.alum_fechanacimiento, CURDATE())', 'numero'],
            'telefono'      => ['Estudiante', 'Teléfono del estudiante', 'al.alum_movil', 'texto'],
            'direccion'     => ['Estudiante', 'Dirección', 'al.alum_direccion', 'texto'],
            'usuario'       => ['Estudiante', 'Usuario del sistema', 'u.usu_usuario', 'texto'],
            'papa'          => ['Padres', 'Padre', 'pa.Datos_papa', 'texto'],
            'dni_papa'      => ['Padres', 'Documento del padre', 'pa.Dni_papa', 'texto'],
            'cel_papa'      => ['Padres', 'Celular del padre', 'pa.Celular_papa', 'texto'],
            'mama'          => ['Padres', 'Madre', 'pa.Datos_mama', 'texto'],
            'dni_mama'      => ['Padres', 'Documento de la madre', 'pa.Dni_mama', 'texto'],
            'cel_mama'      => ['Padres', 'Celular de la madre', 'pa.Celular_mama', 'texto'],
            'nivel'         => ['Matrícula', 'Nivel', 'n.Nivel_academico', 'texto'],
            'grado'         => ['Matrícula', 'Grado', 'a.Grado', 'texto'],
            'seccion'       => ['Matrícula', 'Sección', 's.seccion_nombre', 'texto'],
            'tipo'          => ['Matrícula', 'Nuevo / antiguo', 'al.tipo_alum', 'texto'],
            'fecha_matricula' => ['Matrícula', 'Fecha de matrícula', "DATE_FORMAT(m.created_at, '%d/%m/%Y')", 'texto'],
            'procedencia'   => ['Matrícula', 'Colegio de procedencia', 'm.procedencia_colegio', 'texto'],
            'beca'          => ['Matrícula', 'Beca', "CASE m.beca_tipo WHEN 'PORCENTAJE' THEN CONCAT(m.beca_valor + 0, '%') WHEN 'MONTO' THEN CONCAT('Monto ', m.beca_valor) ELSE '' END", 'texto'],
            'asistencia'    => ['Seguimiento', '% asistencia', sprintf($asis, "ROUND(SUM(x.estado <> 'AUSENTE') * 100 / NULLIF(COUNT(*), 0))"), 'porcentaje'],
            'faltas'        => ['Seguimiento', 'Faltas', sprintf($asis, "SUM(x.estado = 'AUSENTE')"), 'numero'],
            'tardanzas'     => ['Seguimiento', 'Tardanzas', sprintf($asis, "SUM(x.estado = 'TARDE')"), 'numero'],
            'tareas'        => ['Seguimiento', 'Tareas entregadas', "(SELECT CONCAT(SUM(d.estado IN ('ENVIADO','CALIFICADO')), ' de ', COUNT(*)) FROM detalle_tarea d WHERE d.id_matriculado = m.id_matricula)", 'texto'],
            'pensiones_vencidas' => ['Pensiones', 'Pensiones vencidas', sprintf($deuda, 'COUNT(*)'), 'numero'],
            'deuda'         => ['Pensiones', 'Deuda vencida', sprintf($deuda, 'IFNULL(SUM(v.saldo_pension + v.saldo_mora), 0)'), 'moneda'],
        ];
    }

    public function Niveles() {
        $c = conexionBD::conexionPDO();
        return $c->query("SELECT Id_nivel AS id, Nivel_academico AS nombre FROM nivel_academico ORDER BY Id_nivel")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * $campos: claves del catálogo, en el orden elegido. $f: anio (obligatorio), nivel, aula, sexo, tipo, con_deuda, buscar.
     */
    public function Generar(array $campos, array $f) {
        $catalogo = self::Campos();
        $select = [];
        foreach ($campos as $k) {
            $select[] = $catalogo[$k][2] . ' AS `' . $k . '`';
        }
        $where = ['m.`id_año` = ?'];
        $p = [$f['anio']];
        if (!empty($f['nivel'])) { $where[] = 'a.id_nivel_academico = ?'; $p[] = $f['nivel']; }
        if (!empty($f['aula'])) { $where[] = 'a.Id_aula = ?'; $p[] = $f['aula']; }
        if (!empty($f['sexo'])) { $where[] = 'al.alum_sexo = ?'; $p[] = $f['sexo']; }
        if (!empty($f['tipo'])) { $where[] = 'al.tipo_alum = ?'; $p[] = $f['tipo']; }
        if (!empty($f['buscar'])) {
            $where[] = "(CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) LIKE ? OR al.alum_dni LIKE ?)";
            $p[] = '%' . $f['buscar'] . '%';
            $p[] = '%' . $f['buscar'] . '%';
        }
        if (!empty($f['con_deuda'])) {
            $where[] = 'EXISTS (SELECT 1 FROM vw_estado_pensiones v WHERE v.id_matricula = m.id_matricula AND v.vencida = 1 AND v.saldo_pension + v.saldo_mora > 0)';
        }
        $sql = 'SELECT ' . implode(', ', $select) . '
                FROM matricula m
                JOIN alumnos al ON al.Id_alumno = m.id_alumno
                JOIN aulas a ON a.Id_aula = m.id_aula
                JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                LEFT JOIN usuario u ON u.usu_id = m.usu_id
                LEFT JOIN (SELECT id_alu, MAX(Datos_papa) Datos_papa, MAX(Dni_papa) Dni_papa, MAX(Celular_papa) Celular_papa,
                                  MAX(Datos_mama) Datos_mama, MAX(Dni_mama) Dni_mama, MAX(Celular_mama) Celular_mama
                             FROM padres GROUP BY id_alu) pa ON pa.id_alu = al.Id_alumno
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY n.Id_nivel, a.Id_aula, al.alum_apepat, al.alum_apemat, al.alum_nombre';
        $c = conexionBD::conexionPDO();
        $q = $c->prepare($sql);
        $q->execute($p);
        return $q->fetchAll(PDO::FETCH_NUM);
    }
}
