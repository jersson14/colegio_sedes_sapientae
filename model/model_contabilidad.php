<?php
    require_once 'model_conexion.php';

    // Contabilidad (Fase 5): saldos y movimientos de cajas/cuentas, depósitos, cierre de caja y conciliación
    class Modelo_Contabilidad extends conexionBD{

        private function llamar($sql, $params = [], $una_fila = false){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare($sql);
            $q->execute($params);
            $r = $una_fila ? $q->fetch(PDO::FETCH_ASSOC) : $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        public function Saldos(){
            return $this->llamar("CALL SP_LISTAR_SALDOS_CUENTAS()");
        }
        // Movimientos del período con saldo acumulado (los anulados se muestran pero no suman)
        public function Movimientos($cuenta, $desde, $hasta){
            $filas = $this->llamar("CALL SP_LISTAR_MOVIMIENTOS(?,?,?)", [$cuenta, $desde, $hasta]);
            $saldo = $filas ? (float) $filas[0]['saldo_anterior'] : null;
            if ($saldo === null) {
                // Sin movimientos en el período: el saldo anterior se pide aparte
                $c = conexionBD::conexionPDO();
                $q = $c->prepare("CALL SP_SALDO_CUENTA(?, ?, @s)");
                $q->execute([$cuenta, $desde]);
                $saldo = (float) $c->query("SELECT @s")->fetchColumn();
                conexionBD::cerrar_conexion();
            }
            $inicial = $saldo;
            $entradas = $salidas = 0;
            foreach ($filas as &$f) {
                if ($f['estado'] === 'VALIDO') {
                    if ($f['tipo'] === 'ENTRADA') { $saldo += $f['monto']; $entradas += $f['monto']; }
                    else { $saldo -= $f['monto']; $salidas += $f['monto']; }
                }
                $f['saldo'] = round($saldo, 2);
            }
            return ['saldo_anterior' => round($inicial, 2), 'entradas' => round($entradas, 2), 'salidas' => round($salidas, 2),
                    'saldo_final' => round($saldo, 2), 'data' => $filas];
        }
        public function Registrar_Transferencia($tipo, $origen, $destino, $monto, $fecha, $ref, $obs, $usu){
            return $this->llamar("CALL SP_REGISTRAR_TRANSFERENCIA(?,?,?,?,?,?,?,?)", [$tipo, $origen, $destino, $monto, $fecha, $ref, $obs, $usu], true);
        }
        public function Anular_Transferencia($id, $motivo, $usu){
            return $this->llamar("CALL SP_ANULAR_TRANSFERENCIA(?,?,?)", [$id, $motivo, $usu], true);
        }
        public function Listar_Transferencias($desde, $hasta){
            return $this->llamar("CALL SP_LISTAR_TRANSFERENCIAS(?,?)", [$desde, $hasta]);
        }
        public function Resumen_Caja($cuenta, $fecha){
            return $this->llamar("CALL SP_RESUMEN_CAJA(?,?)", [$cuenta, $fecha], true);
        }
        public function Cerrar_Caja($cuenta, $fecha, $contado, $obs, $ajustar, $usu){
            return $this->llamar("CALL SP_CERRAR_CAJA(?,?,?,?,?,?)", [$cuenta, $fecha, $contado, $obs, $ajustar, $usu], true);
        }
        public function Listar_Cierres($cuenta){
            return $this->llamar("CALL SP_LISTAR_CIERRES(?)", [$cuenta]);
        }
        public function Conciliar($id, $valor){
            return $this->llamar("CALL SP_CONCILIAR_MOVIMIENTO(?,?)", [$id, $valor], true);
        }
        public function Panel(){
            $p = $this->llamar("CALL SP_PANEL_CONTABLE()", [], true);
            $p['cuentas'] = array_values(array_filter($this->Saldos(), function ($c) { return $c['estado'] === 'ACTIVO'; }));
            return $p;
        }
    }
?>
