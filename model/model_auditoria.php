<?php
    require_once 'model_conexion.php';

    // Auditoría de tablas financieras y de configuración (Fase 8D): la llenan los triggers de la migración 011
    class Modelo_Auditoria extends conexionBD{

        const TABLAS = [
            'pagos' => 'Recibos', 'pagos_metodos' => 'Formas de pago de recibos', 'pago_pensiones' => 'Pagos de pensión/matrícula',
            'ingresos' => 'Ingresos', 'egresos' => 'Egresos', 'pensiones' => 'Pensiones', 'matricula' => 'Matrículas y becas',
            'cuentas_financieras' => 'Cajas y cuentas', 'transferencias' => 'Depósitos y transferencias', 'cierres_caja' => 'Cierres de caja',
            'metodos_pago' => 'Formas de pago', 'planillas' => 'Planillas', 'planilla_detalle' => 'Detalle de planilla',
            'pagos_docentes' => 'Pagos y adelantos de planilla', 'contratos_docente' => 'Contratos', 'conceptos_planilla' => 'Conceptos de planilla',
            'indicadores' => 'Categorías', 'empresa' => 'Datos del colegio y configuración', 'usuario' => 'Usuarios',
            'notas' => 'Notas', 'notas_padre' => 'Notas de padres', 'asistencia' => 'Asistencia', 'detalle_tarea' => 'Envíos y calificaciones de tareas',
            'alumnos' => 'Estudiantes', 'docentes' => 'Docentes', 'periodos' => 'Periodos (bimestres)', 'año_escolar' => 'Año escolar',
        ];

        public function Listar($tabla, $desde, $hasta, $usuario, $registro){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("CALL SP_LISTAR_AUDITORIA(?,?,?,?,?)");
            $q->execute([$tabla, $desde, $hasta, $usuario, $registro]);
            $filas = $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            foreach ($filas as &$f) {
                $antes = json_decode((string) $f['antes'], true) ?: [];
                $despues = json_decode((string) $f['despues'], true) ?: [];
                $cambios = [];
                foreach (array_unique(array_merge(array_keys($antes), array_keys($despues))) as $campo) {
                    $a = $antes[$campo] ?? null;
                    $d = $despues[$campo] ?? null;
                    if ($f['accion'] === 'UPDATE' && (string) $a === (string) $d) { continue; }
                    if ($f['accion'] !== 'UPDATE' && $a === null && $d === null) { continue; }
                    // La contraseña solo se informa como cambiada
                    if ($campo === 'contrasena') { $a = $a === null ? null : '••••'; $d = $d === null ? null : '•••• (cambiada)'; }
                    $cambios[] = ['campo' => $campo, 'antes' => $a, 'despues' => $d];
                }
                $f['tabla_nombre'] = self::TABLAS[$f['tabla']] ?? $f['tabla'];
                $f['cambios'] = $cambios;
                unset($f['antes'], $f['despues']);
            }
            return $filas;
        }
    }
?>
