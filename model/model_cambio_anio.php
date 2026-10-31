<?php
require_once 'model_conexion.php';
require_once __DIR__ . '/../utilitario/libreta.php';

/**
 * Cambio de año escolar asistido (Fase 13, paso 13).
 * Regla de promoción: solo pasa de grado automáticamente quien APROBÓ TODOS sus cursos (nota final del año según la
 * libreta, utilitario/libreta.php). Con algún curso desaprobado o sin notas, la dirección decide caso por caso
 * (repite, promover tras la recuperación, no continúa o decidir luego).
 */
class Modelo_Cambio_Anio extends conexionBD {
    private $c;
    const ORDINALES = ['PRIMER' => 1, 'PRIMERO' => 1, 'SEGUNDO' => 2, 'TERCER' => 3, 'TERCERO' => 3, 'CUARTO' => 4, 'QUINTO' => 5,
                       'SEXTO' => 6, 'SEPTIMO' => 7, 'SÉPTIMO' => 7, 'OCTAVO' => 8, 'NOVENO' => 9, 'DECIMO' => 10, 'DÉCIMO' => 10];

    private function uno($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetch(PDO::FETCH_ASSOC) ?: null; }
    private function todos($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetchAll(PDO::FETCH_ASSOC); }
    private function valor($sql, $p = []) { $q = $this->c->prepare($sql); $q->execute($p); return $q->fetchColumn(); }

    public function Anios() {
        $this->c = $this->conexionPDO();
        return $this->todos("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, estado, fecha_inicio, fecha_fin FROM `año_escolar` ORDER BY `año_escolar`");
    }

    /** Número de grado a partir del nombre del aula: "3 AÑOS" -> 3, "PRIMER GRADO A" -> 1, "2DO GRADO" -> 2. */
    public static function Orden_Grado($grado) {
        $g = strtoupper(trim(html_entity_decode((string) $grado, ENT_QUOTES, 'UTF-8')));
        if (preg_match('/^(\d{1,2})\s*(AÑOS|ANOS|AÑO)\b/u', $g, $m)) { return (int) $m[1]; }
        if (preg_match('/^(\d{1,2})\s*(ER|RO|DO|TO|MO|VO|NO|º|°)?\b/u', $g, $m)) { return (int) $m[1]; }
        $primera = preg_split('/\s+/', $g)[0] ?? '';
        return self::ORDINALES[$primera] ?? null;
    }

    /** Aulas activas con su nivel, sección y número de grado (las que no tienen grado reconocible quedan sin orden). */
    private function aulas() {
        $aulas = $this->todos("SELECT a.Id_aula AS id, a.Grado AS grado, a.id_nivel_academico AS id_nivel, n.Nivel_academico AS nivel,
                                      a.id_seccion, s.seccion_nombre AS seccion, a.capacidad
                               FROM aulas a JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                               LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                               WHERE IFNULL(a.estado, 'ACTIVO') = 'ACTIVO' ORDER BY n.Id_nivel, a.Id_aula");
        // "TODOS" es el nivel comodín del sistema (aulas para todos los niveles): no forma parte del paso de grado
        foreach ($aulas as &$a) { $a['orden'] = strtoupper($a['nivel']) === 'TODOS' ? null : self::Orden_Grado($a['grado']); }
        unset($a);
        return $aulas;
    }

    /**
     * Aula del grado siguiente: mismo nivel y grado + 1 (misma sección si existe); después del último grado de un nivel,
     * el primero del nivel siguiente. null = egresa (último grado del último nivel) o no se puede deducir.
     */
    public static function Aula_Siguiente(array $aula, array $aulas) {
        if ($aula['orden'] === null) { return ['tipo' => 'REVISAR', 'aula' => null]; }
        $elegir = function ($candidatas) use ($aula) {
            if (!$candidatas) { return null; }
            foreach ($candidatas as $x) { if ((int) $x['id_seccion'] === (int) $aula['id_seccion']) { return $x; } }
            return reset($candidatas);
        };
        $mismo = array_filter($aulas, function ($x) use ($aula) { return $x['id_nivel'] == $aula['id_nivel'] && $x['orden'] === $aula['orden'] + 1; });
        if ($s = $elegir($mismo)) { return ['tipo' => 'AULA', 'aula' => $s]; }
        // Siguiente nivel con grados reconocibles: su primer grado
        $niveles = array_values(array_unique(array_map(function ($x) { return (int) $x['id_nivel']; },
            array_filter($aulas, function ($x) { return $x['orden'] !== null; }))));
        sort($niveles);
        $pos = array_search((int) $aula['id_nivel'], $niveles, true);
        if ($pos !== false && isset($niveles[$pos + 1])) {
            $sig = $niveles[$pos + 1];
            $grados = array_filter($aulas, function ($x) use ($sig) { return (int) $x['id_nivel'] === $sig && $x['orden'] !== null; });
            $min = min(array_column($grados, 'orden'));
            if ($s = $elegir(array_filter($grados, function ($x) use ($min) { return $x['orden'] === $min; }))) { return ['tipo' => 'AULA', 'aula' => $s]; }
        }
        return ['tipo' => 'EGRESA', 'aula' => null];
    }

    /** Lo que ya tiene el año destino (para no duplicar copias). */
    public function Destino($destino) {
        $this->c = $this->conexionPDO();
        return [
            'periodos' => (int) $this->valor("SELECT COUNT(*) FROM periodos WHERE `id_año_escolar` = ?", [$destino]),
            'pensiones' => (int) $this->valor("SELECT COUNT(*) FROM pensiones WHERE id_anio = ?", [$destino]),
            'cursos' => (int) $this->valor("SELECT COUNT(*) FROM asignatura_docente WHERE `id_año` = ?", [$destino]),
            'horas' => (int) $this->valor("SELECT COUNT(*) FROM horas_aula WHERE `id_año_academico` = ?", [$destino]),
            'matriculas' => (int) $this->valor("SELECT COUNT(*) FROM matricula WHERE `id_año` = ?", [$destino]),
        ];
    }

    /** Propuesta de promoción: por aula del año que termina, sus estudiantes con el resultado y la decisión sugerida. */
    public function Propuesta($origen, $destino) {
        $this->c = $this->conexionPDO();
        $aulas = $this->aulas();
        $por_id = [];
        foreach ($aulas as $a) { $por_id[$a['id']] = $a; }
        $con_alumnos = $this->todos("SELECT DISTINCT id_aula FROM matricula WHERE `id_año` = ? ORDER BY id_aula", [$origen]);
        $ya = array_flip(array_column($this->todos("SELECT id_alumno FROM matricula WHERE `id_año` = ?", [$destino]), 'id_alumno'));
        $res = [];
        foreach ($con_alumnos as $f) {
            $aula = $por_id[$f['id_aula']] ?? null;
            if (!$aula) { continue; }
            $sig = self::Aula_Siguiente($aula, $aulas);
            $libreta = libreta_aula($origen, $aula['id'], null);
            $ids = $this->todos("SELECT m.id_matricula, m.id_alumno FROM matricula m WHERE m.id_aula = ? AND m.`id_año` = ?", [$aula['id'], $origen]);
            $alumno_de = array_column($ids, 'id_alumno', 'id_matricula');
            $cursos = array_column($libreta['cursos'] ?? [], 'nombre', 'id');
            $estudiantes = [];
            foreach ($libreta['estudiantes'] ?? [] as $e) {
                $desap = [];
                foreach ($e['cursos'] as $id => $c) { if ($c['aprobado'] === false) { $desap[] = $cursos[$id] ?? ''; } }
                $aprobo_todo = $e['con_notas'] > 0 && !$desap;
                $resultado = isset($ya[$alumno_de[$e['matricula']] ?? 0]) ? 'YA_MATRICULADO' : ($aprobo_todo ? 'APROBO_TODO' : ($e['con_notas'] ? 'DESAPROBADOS' : 'SIN_NOTAS'));
                $sugerida = $resultado === 'APROBO_TODO' ? ($sig['tipo'] === 'EGRESA' ? 'EGRESA' : ($sig['tipo'] === 'AULA' ? 'PROMOVER' : 'LUEGO')) : 'LUEGO';
                if ($resultado === 'YA_MATRICULADO') { $sugerida = 'NADA'; }
                $estudiantes[] = ['matricula' => (int) $e['matricula'], 'documento' => $e['documento'], 'nombre' => trim($e['apellidos'] . ', ' . $e['nombres']),
                                  'promedio' => $e['promedio'], 'resultado' => $resultado, 'desaprobados' => $desap, 'sugerida' => $sugerida];
            }
            usort($estudiantes, function ($x, $y) { return strcmp($x['nombre'], $y['nombre']); });
            $res[] = ['aula' => $aula, 'siguiente' => $sig['tipo'], 'aula_siguiente' => $sig['aula'] ? (int) $sig['aula']['id'] : null, 'estudiantes' => $estudiantes];
        }
        return ['aulas' => $res, 'todas_las_aulas' => $aulas];
    }

    /**
     * Ejecuta el cambio de año en una transacción.
     * $decisiones: [matricula => ['accion' => PROMOVER|REPETIR|EGRESA|NO_CONTINUA|LUEGO, 'aula' => id destino para PROMOVER]]
     * $copiar: ['periodos' => bool, 'pensiones' => bool, 'cursos' => bool, 'horarios' => bool]; $activar: activa el año nuevo.
     */
    public function Ejecutar($origen, $destino, array $decisiones, array $copiar, $activar, $usureg) {
        $this->c = $this->conexionPDO();
        $o = $this->uno("SELECT `año_escolar` AS anio FROM `año_escolar` WHERE `Id_año_escolar` = ?", [$origen]);
        $d = $this->uno("SELECT `año_escolar` AS anio FROM `año_escolar` WHERE `Id_año_escolar` = ?", [$destino]);
        if (!$o || !$d || $origen == $destino) { throw new InvalidArgumentException('Elija dos años escolares distintos'); }
        $salto = (int) $d['anio'] - (int) $o['anio'];
        $ya = $this->Destino($destino);
        $r = ['matriculas' => 0, 'repiten' => 0, 'promovidos' => 0, 'egresan' => 0, 'pendientes' => 0, 'omitidos' => [],
              'periodos' => 0, 'pensiones' => 0, 'cursos' => 0, 'criterios' => 0, 'horas' => 0, 'horarios' => 0, 'activado' => false];
        $this->c->beginTransaction();
        try {
            if (!empty($copiar['periodos']) && !$ya['periodos']) {
                $r['periodos'] = $this->c->exec(sprintf("INSERT INTO periodos(`id_año_escolar`, tipo_perido, periodos, fecha_inicio, fecha_fin, estado, created_at)
                    SELECT %d, tipo_perido, periodos, DATE_ADD(fecha_inicio, INTERVAL %d YEAR), DATE_ADD(fecha_fin, INTERVAL %d YEAR), 'EN CURSO', CURDATE()
                    FROM periodos WHERE `id_año_escolar` = %d", $destino, $salto, $salto, $origen));
            }
            if (!empty($copiar['pensiones']) && !$ya['pensiones']) {
                $r['pensiones'] = $this->c->exec(sprintf("INSERT INTO pensiones(id_anio, id_nivel_academico, mes, fecha_vencimiento, precio, mora, created_at)
                    SELECT %d, id_nivel_academico, mes, DATE_ADD(fecha_vencimiento, INTERVAL %d YEAR), precio, mora, CURDATE()
                    FROM pensiones WHERE id_anio = %d", $destino, $salto, $origen));
            }
            $mapa_detalle = [];
            if ((!empty($copiar['cursos']) || !empty($copiar['horarios'])) && !$ya['cursos']) {
                // Cursos de cada docente (y sus criterios de evaluación): el mismo reparto para empezar, editable después
                foreach ($this->todos("SELECT * FROM asignatura_docente WHERE `id_año` = ?", [$origen]) as $ad) {
                    $q = $this->c->prepare("INSERT INTO asignatura_docente(Id_docente, `id_año`, Total_cursos, created_at) VALUES (?, ?, ?, CURDATE())");
                    $q->execute([$ad['Id_docente'], $destino, $ad['Total_cursos']]);
                    $nuevo_ad = $this->c->lastInsertId();
                    $r['cursos']++;
                    foreach ($this->todos("SELECT * FROM detalle_asignatura_docente WHERE Id_asig_docente = ?", [$ad['Id_asigdocente']]) as $dd) {
                        $q = $this->c->prepare("INSERT INTO detalle_asignatura_docente(Id_asig_docente, Id_asignatura, created_at) VALUES (?, ?, CURDATE())");
                        $q->execute([$nuevo_ad, $dd['Id_asignatura']]);
                        $mapa_detalle[$dd['Id_detalle_asig_docente']] = $this->c->lastInsertId();
                        $q = $this->c->prepare("INSERT INTO criterios(id_detalle_asignatura, competencias, `descripción_observa`, estado, created_at)
                                                SELECT ?, competencias, `descripción_observa`, estado, CURDATE() FROM criterios WHERE id_detalle_asignatura = ?");
                        $q->execute([$mapa_detalle[$dd['Id_detalle_asig_docente']], $dd['Id_detalle_asig_docente']]);
                        $r['criterios'] += $q->rowCount();
                    }
                }
            }
            if (!empty($copiar['horarios']) && !$ya['horas'] && $mapa_detalle) {
                foreach ($this->todos("SELECT * FROM horas_aula WHERE `id_año_academico` = ?", [$origen]) as $h) {
                    $q = $this->c->prepare("INSERT INTO horas_aula(`id_año_academico`, id_aula, turno, hora_inicio, hora_fin, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
                    $q->execute([$destino, $h['id_aula'], $h['turno'], $h['hora_inicio'], $h['hora_fin'], $h['estado']]);
                    $nueva_hora = $this->c->lastInsertId();
                    $r['horas']++;
                    foreach ($this->todos("SELECT * FROM horarios WHERE id_hora_aula = ?", [$h['id_hora']]) as $x) {
                        if (!isset($mapa_detalle[$x['id_detalle_asig_docente']])) { continue; }
                        $q = $this->c->prepare("INSERT INTO horarios(id_hora_aula, id_detalle_asig_docente, dia, estado, created_at) VALUES (?, ?, ?, ?, CURDATE())");
                        $q->execute([$nueva_hora, $mapa_detalle[$x['id_detalle_asig_docente']], $x['dia'], $x['estado']]);
                        $r['horarios']++;
                    }
                }
            }

            // Matrículas del año nuevo (sin cobro: la matrícula se cobra después en "Cobrar pensión", como siempre)
            $origen_de = [];
            foreach ($this->todos("SELECT m.id_matricula, m.id_alumno, m.id_aula, m.usu_id, CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS nombre
                                   FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno WHERE m.`id_año` = ?", [$origen]) as $m) {
                $origen_de[$m['id_matricula']] = $m;
            }
            $ins = $this->c->prepare("INSERT INTO matricula(id_alumno, `id_año`, id_aula, pago_admi, pago_alu_nuevo, pago_matricula, procedencia_colegio, provincia, departamento, usu_id, created_at, updated_at)
                                      VALUES (?, ?, ?, 0, 0, 0, '', '', '', ?, NOW(), NOW())");
            $concepto = $this->c->prepare("INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
                                           VALUES (NULL, ?, ?, NULL, NOW(), 0, 'VALIDO', ?, NOW(), NOW())");
            foreach ($decisiones as $id_mat => $dec) {
                $m = $origen_de[$id_mat] ?? null;
                if (!$m) { continue; }
                $accion = $dec['accion'] ?? 'LUEGO';
                if ($accion === 'EGRESA' || $accion === 'NO_CONTINUA') { $r['egresan']++; continue; }
                if ($accion !== 'PROMOVER' && $accion !== 'REPETIR') { $r['pendientes']++; continue; }
                $aula = $accion === 'REPETIR' ? (int) $m['id_aula'] : (int) ($dec['aula'] ?? 0);
                if (!$aula) { $r['omitidos'][] = $m['nombre'] . ': sin aula de destino'; continue; }
                if ($this->valor("SELECT COUNT(*) FROM matricula WHERE id_alumno = ? AND `id_año` = ?", [$m['id_alumno'], $destino])) {
                    $r['omitidos'][] = $m['nombre'] . ': ya estaba matriculado en el año nuevo'; continue;
                }
                $vac = $this->valor("SELECT FN_VACANTES_AULA(?, ?, 0)", [$aula, $destino]);
                if ($vac !== null && (int) $vac <= 0) { $r['omitidos'][] = $m['nombre'] . ': el aula de destino no tiene vacantes'; continue; }
                $ins->execute([$m['id_alumno'], $destino, $aula, $m['usu_id']]);
                $nueva = $this->c->lastInsertId();
                foreach (['ADMISION', 'ALUMNO NUEVO', 'MATRICULA'] as $cpt) { $concepto->execute([$nueva, $cpt, $usureg]); }
                $q = $this->c->prepare("UPDATE alumnos SET tipo_alum = 'ANTIGUO', alum_estatus = 'SI' WHERE Id_alumno = ?");
                $q->execute([$m['id_alumno']]);
                $r['matriculas']++;
                $r[$accion === 'REPETIR' ? 'repiten' : 'promovidos']++;
            }

            if ($activar) {
                // Cierra el año que termina: sus periodos quedan finalizados y el año nuevo pasa a ser el activo
                $q = $this->c->prepare("UPDATE periodos SET estado = 'FINALIZADO' WHERE `id_año_escolar` = ?");
                $q->execute([$origen]);
                $q = $this->c->prepare("UPDATE `año_escolar` SET estado = IF(`Id_año_escolar` = ?, 'ACTIVO', 'INACTIVO'), updated_at = NOW()");
                $q->execute([$destino]);
                $r['activado'] = true;
            }
            $this->c->commit();
        } catch (Throwable $e) {
            $this->c->rollBack();
            throw $e;
        }
        return $r;
    }
}
