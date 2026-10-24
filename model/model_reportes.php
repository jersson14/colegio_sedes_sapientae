<?php
    require_once __DIR__ . '/model_conexion.php';

    // Reportes financieros (Fase 7). Los totales, la utilidad y el margen se calculan aquí
    // para que la pantalla, el Excel y el PDF muestren exactamente lo mismo.
    class Modelo_Reportes extends conexionBD{

        const MESES = [1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        const CONCEPTOS = ['MATRICULA' => 'Matrícula', 'ADMISION' => 'Admisión', 'ALUMNO NUEVO' => 'Alumno nuevo', 'PENSION' => 'Pensiones', 'MORA' => 'Mora'];

        private function llamar($sql, $params = []){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare($sql);
            $q->execute($params);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        private static function numeros(array $filas, array $campos){
            foreach ($filas as &$f) {
                foreach ($campos as $c) { $f[$c] = round((float) $f[$c], 2); }
            }
            return $filas;
        }
        private static function margen($utilidad, $ingresos){
            return $ingresos > 0 ? round($utilidad / $ingresos * 100, 1) : null;
        }

        // Estado de resultados: grupos › categorías › conceptos de cobranza
        public function Resultados($desde, $hasta){
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_RESULTADOS(?,?)", [$desde, $hasta]), ['total']);
            $r = ['desde' => $desde, 'hasta' => $hasta, 'INGRESO' => ['grupos' => [], 'total' => 0], 'EGRESO' => ['grupos' => [], 'total' => 0]];
            foreach ($filas as $f) {
                $lado = &$r[$f['tipo']];
                $kg = $f['grupo_codigo'] . '|' . $f['grupo'];
                if (!isset($lado['grupos'][$kg])) {
                    $lado['grupos'][$kg] = ['codigo' => $f['grupo_codigo'], 'nombre' => $f['grupo'], 'total' => 0, 'categorias' => []];
                }
                $g = &$lado['grupos'][$kg];
                $kc = (int) $f['id_indicador'];
                if (!isset($g['categorias'][$kc])) {
                    $g['categorias'][$kc] = ['nombre' => $f['categoria'], 'total' => 0, 'cantidad' => 0, 'detalle' => []];
                }
                $c = &$g['categorias'][$kc];
                $c['total'] += $f['total'];
                $c['cantidad'] += (int) $f['cantidad'];
                if ($f['tipo'] === 'INGRESO') {
                    $c['detalle'][] = ['concepto' => $f['concepto'] === null ? 'Ingresos registrados manualmente' : self::CONCEPTOS[$f['concepto']],
                                       'cantidad' => (int) $f['cantidad'], 'total' => $f['total']];
                }
                $g['total'] += $f['total'];
                $lado['total'] += $f['total'];
                unset($lado, $g, $c);
            }
            foreach (['INGRESO', 'EGRESO'] as $t) {
                $r[$t]['total'] = round($r[$t]['total'], 2);
                $r[$t]['grupos'] = array_values(array_map(function ($g) use ($r, $t) {
                    $g['total'] = round($g['total'], 2);
                    $g['categorias'] = array_values(array_map(function ($c) use ($r, $t) {
                        $c['total'] = round($c['total'], 2);
                        // Una categoría con un solo concepto no necesita desglose
                        if (count($c['detalle']) < 2) { $c['detalle'] = []; }
                        $c['porcentaje'] = $r[$t]['total'] > 0 ? round($c['total'] / $r[$t]['total'] * 100, 1) : 0;
                        return $c;
                    }, $g['categorias']));
                    return $g;
                }, $r[$t]['grupos']));
            }
            $r['utilidad'] = round($r['INGRESO']['total'] - $r['EGRESO']['total'], 2);
            $r['margen'] = self::margen($r['utilidad'], $r['INGRESO']['total']);
            return $r;
        }

        // Comparativo mensual del año contra el anterior
        public function Mensual($anio){
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_MENSUAL(?)", [$anio]), ['ingresos', 'egresos', 'ingresos_anterior', 'egresos_anterior']);
            $t = ['ingresos' => 0, 'egresos' => 0, 'ingresos_anterior' => 0, 'egresos_anterior' => 0];
            foreach ($filas as &$f) {
                $f['mes_nombre'] = self::MESES[(int) $f['mes']];
                $f['utilidad'] = round($f['ingresos'] - $f['egresos'], 2);
                $f['utilidad_anterior'] = round($f['ingresos_anterior'] - $f['egresos_anterior'], 2);
                foreach ($t as $k => $v) { $t[$k] += $f[$k]; }
            }
            $t = array_map(function ($v) { return round($v, 2); }, $t);
            $t['utilidad'] = round($t['ingresos'] - $t['egresos'], 2);
            $t['utilidad_anterior'] = round($t['ingresos_anterior'] - $t['egresos_anterior'], 2);
            $t['margen'] = self::margen($t['utilidad'], $t['ingresos']);
            $t['variacion_ingresos'] = $t['ingresos_anterior'] > 0 ? round(($t['ingresos'] - $t['ingresos_anterior']) / $t['ingresos_anterior'] * 100, 1) : null;
            return ['anio' => (int) $anio, 'data' => $filas, 'totales' => $t];
        }

        // Flujo de caja por cuenta
        public function Flujo($desde, $hasta){
            $campos = ['saldo_anterior', 'cobros', 'transferencias_entrada', 'ajustes_entrada', 'pagos', 'transferencias_salida', 'ajustes_salida'];
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_FLUJO_CAJA(?,?)", [$desde, $hasta]), $campos);
            $t = array_fill_keys(array_merge($campos, ['entradas', 'salidas', 'saldo_final']), 0);
            $sin_cuenta = null;
            $cuentas = [];
            foreach ($filas as $f) {
                $f['entradas'] = round($f['cobros'] + $f['transferencias_entrada'] + $f['ajustes_entrada'], 2);
                $f['salidas'] = round($f['pagos'] + $f['transferencias_salida'] + $f['ajustes_salida'], 2);
                $f['saldo_final'] = round($f['saldo_anterior'] + $f['entradas'] - $f['salidas'], 2);
                if ($f['id_cuenta'] === null) { $sin_cuenta = $f; continue; }
                // Las cuentas inactivas solo aparecen si tuvieron saldo o movimientos
                if ($f['estado'] !== 'ACTIVO' && !$f['saldo_anterior'] && !$f['entradas'] && !$f['salidas']) { continue; }
                foreach ($t as $k => $v) { $t[$k] += $f[$k]; }
                $cuentas[] = $f;
            }
            $t = array_map(function ($v) { return round($v, 2); }, $t);
            if ($sin_cuenta && !$sin_cuenta['entradas'] && !$sin_cuenta['salidas']) { $sin_cuenta = null; }
            return ['desde' => $desde, 'hasta' => $hasta, 'data' => $cuentas, 'totales' => $t, 'sin_cuenta' => $sin_cuenta];
        }

        // Cobros y pagos por forma de pago y cuenta
        public function Por_Metodo($desde, $hasta){
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_POR_METODO(?,?)", [$desde, $hasta]), ['total', 'comision']);
            $r = ['INGRESO' => ['data' => [], 'total' => 0, 'comision' => 0], 'EGRESO' => ['data' => [], 'total' => 0, 'comision' => 0]];
            foreach ($filas as $f) {
                $r[$f['tipo']]['data'][] = $f;
                $r[$f['tipo']]['total'] += $f['total'];
                $r[$f['tipo']]['comision'] += $f['comision'];
            }
            foreach ($r as &$lado) {
                $lado['total'] = round($lado['total'], 2);
                $lado['comision'] = round($lado['comision'], 2);
                foreach ($lado['data'] as &$f) {
                    $f['porcentaje'] = $lado['total'] > 0 ? round($f['total'] / $lado['total'] * 100, 1) : 0;
                }
                unset($f);
            }
            unset($lado);
            return ['desde' => $desde, 'hasta' => $hasta] + $r;
        }

        // Morosidad: detalle por pensión, resumen por alumno y por aula
        public function Morosidad($anio, $nivel = 0, $aula = 0){
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_MOROSIDAD(?,?,?)", [$anio, $nivel, $aula]),
                                   ['precio', 'pagado_pension', 'saldo_pension', 'saldo_mora', 'deuda']);
            $alumnos = $aulas = [];
            $t = ['pension' => 0, 'mora' => 0, 'deuda' => 0];
            foreach ($filas as $f) {
                $f['dias_atraso'] = (int) $f['dias_atraso'];
                $m = $f['id_matricula'];
                if (!isset($alumnos[$m])) {
                    $alumnos[$m] = ['id_matricula' => (int) $m, 'documento' => $f['documento'], 'alumno' => $f['alumno'], 'nivel' => $f['nivel'],
                                    'grado' => $f['grado'], 'seccion' => $f['seccion'], 'telefonos' => $f['telefonos'],
                                    'pensiones' => 0, 'meses' => [], 'dias_atraso' => 0, 'pension' => 0, 'mora' => 0, 'deuda' => 0];
                }
                $a = &$alumnos[$m];
                $a['pensiones']++;
                $a['meses'][] = $f['mes'];
                $a['dias_atraso'] = max($a['dias_atraso'], $f['dias_atraso']);
                $a['pension'] += $f['saldo_pension'];
                $a['mora'] += $f['saldo_mora'];
                $a['deuda'] += $f['deuda'];
                unset($a);
                $ka = $f['nivel'] . ' › ' . $f['grado'] . ($f['seccion'] && $f['seccion'] !== 'UNICO' ? ' ' . $f['seccion'] : '');
                if (!isset($aulas[$ka])) { $aulas[$ka] = ['aula' => $ka, 'alumnos' => [], 'deuda' => 0]; }
                $aulas[$ka]['alumnos'][$m] = true;
                $aulas[$ka]['deuda'] += $f['deuda'];
                $t['pension'] += $f['saldo_pension'];
                $t['mora'] += $f['saldo_mora'];
                $t['deuda'] += $f['deuda'];
            }
            $alumnos = array_values(array_map(function ($a) {
                foreach (['pension', 'mora', 'deuda'] as $k) { $a[$k] = round($a[$k], 2); }
                $a['meses'] = implode(', ', $a['meses']);
                return $a;
            }, $alumnos));
            usort($alumnos, function ($x, $y) { return $y['deuda'] <=> $x['deuda']; });
            $aulas = array_values(array_map(function ($a) {
                return ['aula' => $a['aula'], 'alumnos' => count($a['alumnos']), 'deuda' => round($a['deuda'], 2)];
            }, $aulas));
            return ['alumnos' => $alumnos, 'aulas' => $aulas, 'detalle' => $filas,
                    'totales' => array_map(function ($v) { return round($v, 2); }, $t) + ['alumnos' => count($alumnos)],
                    'avisos' => $this->llamar("CALL SP_PENSIONES_FUERA_DE_ANIO(?)", [$anio])];
        }

        // Proyección de pensiones del año escolar
        public function Proyeccion($anio){
            $campos = ['esperado', 'cobrado', 'vencido', 'mora_pendiente', 'por_vencer'];
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_PROYECCION(?)", [$anio]), $campos);
            $t = array_fill_keys($campos, 0);
            foreach ($filas as &$f) {
                $f['avance'] = $f['esperado'] > 0 ? round($f['cobrado'] / $f['esperado'] * 100, 1) : 0;
                foreach ($campos as $k) { $t[$k] += $f[$k]; }
            }
            $t = array_map(function ($v) { return round($v, 2); }, $t);
            $t['avance'] = $t['esperado'] > 0 ? round($t['cobrado'] / $t['esperado'] * 100, 1) : 0;
            return ['data' => $filas, 'totales' => $t];
        }

        // Costo de planilla docente por mes y tipo de contrato
        public function Planilla($anio){
            $campos = ['horas', 'base', 'ingresos', 'bruto', 'descuentos', 'adelantos', 'neto', 'pagado'];
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_PLANILLA(?)", [$anio]), $campos);
            $tipos = [];
            $t = array_fill_keys($campos, 0);
            foreach ($filas as &$f) {
                $f['mes_nombre'] = self::MESES[(int) $f['mes']];
                $k = $f['tipo_contrato'];
                if (!isset($tipos[$k])) { $tipos[$k] = ['tipo_contrato' => $k, 'docentes' => 0, 'meses' => [], 'bruto' => 0, 'neto' => 0]; }
                $tipos[$k]['meses'][$f['mes']] = true;
                $tipos[$k]['docentes'] = max($tipos[$k]['docentes'], (int) $f['docentes']);
                $tipos[$k]['bruto'] += $f['bruto'];
                $tipos[$k]['neto'] += $f['neto'];
                foreach ($campos as $c) { $t[$c] += $f[$c]; }
            }
            $t = array_map(function ($v) { return round($v, 2); }, $t);
            $tipos = array_values(array_map(function ($x) use ($t) {
                return ['tipo_contrato' => $x['tipo_contrato'], 'docentes' => $x['docentes'], 'meses' => count($x['meses']),
                        'bruto' => round($x['bruto'], 2), 'neto' => round($x['neto'], 2),
                        'promedio_mes' => round($x['bruto'] / count($x['meses']), 2),
                        'porcentaje' => $t['bruto'] > 0 ? round($x['bruto'] / $t['bruto'] * 100, 1) : 0];
            }, $tipos));
            return ['anio' => (int) $anio, 'data' => $filas, 'tipos' => $tipos, 'totales' => $t];
        }

        // Cierres de caja con sus diferencias
        public function Cierres($desde, $hasta){
            $filas = self::numeros($this->llamar("CALL SP_REPORTE_CIERRES(?,?)", [$desde, $hasta]),
                                   ['saldo_inicial', 'entradas', 'salidas', 'saldo_esperado', 'saldo_contado', 'diferencia']);
            $t = ['cierres' => count($filas), 'con_diferencia' => 0, 'faltantes' => 0, 'sobrantes' => 0];
            foreach ($filas as $f) {
                if ($f['diferencia'] != 0) { $t['con_diferencia']++; }
                if ($f['diferencia'] < 0) { $t['faltantes'] += -$f['diferencia']; }
                if ($f['diferencia'] > 0) { $t['sobrantes'] += $f['diferencia']; }
            }
            $t['faltantes'] = round($t['faltantes'], 2);
            $t['sobrantes'] = round($t['sobrantes'], 2);
            return ['data' => $filas, 'totales' => $t];
        }

        // Datos para los filtros: años escolares, niveles y aulas
        public function Filtros(){
            return [
                'anios' => $this->llamar("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, estado, fecha_inicio, fecha_fin
                                            FROM `año_escolar` ORDER BY fecha_inicio DESC"),
                'niveles' => $this->llamar("SELECT Id_nivel AS id, Nivel_academico AS nombre FROM nivel_academico WHERE estado = 'ACTIVO' ORDER BY Id_nivel"),
                'aulas' => $this->llamar("SELECT a.Id_aula AS id, a.id_nivel_academico AS nivel, CONCAT(a.Grado, IF(s.seccion_nombre IS NULL OR s.seccion_nombre = 'UNICO', '', CONCAT(' ', s.seccion_nombre))) AS nombre
                                            FROM aulas a LEFT JOIN seccion s ON s.seccion_id = a.id_seccion WHERE a.estado = 'ACTIVO' ORDER BY a.id_nivel_academico, a.Id_aula"),
            ];
        }
        public function Nombre_Anio($id){
            $r = $this->llamar("SELECT `año_escolar` AS anio FROM `año_escolar` WHERE `Id_año_escolar` = ?", [$id]);
            return $r ? $r[0]['anio'] : null;
        }
    }
?>
