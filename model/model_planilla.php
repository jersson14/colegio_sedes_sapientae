<?php
    require_once 'model_conexion.php';

    // Planilla y pago a docentes (Fase 6)
    class Modelo_Planilla extends conexionBD{

        private function llamar($sql, $params = [], $una_fila = false){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare($sql);
            $q->execute($params);
            $r = $una_fila ? $q->fetch(PDO::FETCH_ASSOC) : $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        public function Contratos(){ return $this->llamar("CALL SP_LISTAR_CONTRATOS()"); }
        public function Tipos_Contrato(){
            return $this->llamar("SELECT id_tipo_contrato, nombre, calculo FROM tipos_contrato WHERE estado = 'ACTIVO' ORDER BY id_tipo_contrato");
        }
        public function Guardar_Contrato($id, $docente, $tipo, $inicio, $sueldo, $tarifa, $horas, $metodo, $banco, $cuenta, $obs, $usu){
            return $this->llamar("CALL SP_GUARDAR_CONTRATO(?,?,?,?,?,?,?,?,?,?,?,?)",
                [$id, $docente, $tipo, $inicio, $sueldo, $tarifa, $horas, $metodo, $banco, $cuenta, $obs, $usu], true);
        }
        public function Finalizar_Contrato($id, $fin){ return $this->llamar("CALL SP_FINALIZAR_CONTRATO(?,?)", [$id, $fin], true); }
        public function Conceptos(){
            return $this->llamar("SELECT id_concepto, nombre, tipo, forma, valor_defecto, estado FROM conceptos_planilla ORDER BY estado, tipo DESC, nombre");
        }
        public function Guardar_Concepto($id, $nombre, $tipo, $forma, $valor, $estado){
            try {
                return $this->llamar("CALL SP_GUARDAR_CONCEPTO_PLANILLA(?,?,?,?,?,?)", [$id, $nombre, $tipo, $forma, $valor, $estado], true);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { return ['codigo' => 0, 'mensaje' => 'Ya existe un concepto con ese nombre']; }
                throw $e;
            }
        }
        public function Planillas($anio){ return $this->llamar("CALL SP_LISTAR_PLANILLAS(?)", [$anio]); }
        public function Generar($anio, $mes, $usu){ return $this->llamar("CALL SP_GENERAR_PLANILLA(?,?,?)", [$anio, $mes, $usu], true); }
        public function Detalle($pla){ return $this->llamar("CALL SP_LISTAR_PLANILLA_DETALLE(?)", [$pla]); }
        public function Conceptos_Detalle($det){ return $this->llamar("CALL SP_LISTAR_CONCEPTOS_DETALLE(?)", [$det]); }
        public function Pagos($det, $desde = null, $hasta = null){ return $this->llamar("CALL SP_LISTAR_PAGOS_DOCENTES(?,?,?)", [$det, $desde, $hasta]); }
        public function Actualizar_Detalle($det, $horas, $base){ return $this->llamar("CALL SP_ACTUALIZAR_DETALLE(?,?,?)", [$det, $horas, $base], true); }
        public function Agregar_Concepto($det, $concepto, $valor){ return $this->llamar("CALL SP_AGREGAR_CONCEPTO_DETALLE(?,?,?)", [$det, $concepto, $valor], true); }
        public function Quitar_Concepto($id){ return $this->llamar("CALL SP_QUITAR_CONCEPTO_DETALLE(?)", [$id], true); }
        public function Aprobar($pla, $usu){ return $this->llamar("CALL SP_APROBAR_PLANILLA(?,?)", [$pla, $usu], true); }
        public function Reabrir($pla){ return $this->llamar("CALL SP_REABRIR_PLANILLA(?)", [$pla], true); }
        public function Anular($pla, $motivo, $usu){ return $this->llamar("CALL SP_ANULAR_PLANILLA(?,?,?)", [$pla, $motivo, $usu], true); }
        public function Pagar($det, $monto, $fecha, $metodo, $cuenta, $ref, $usu){
            return $this->llamar("CALL SP_PAGAR_DETALLE(?,?,?,?,?,?,?)", [$det, $monto, $fecha, $metodo, $cuenta, $ref, $usu], true);
        }
        public function Adelanto($doc, $monto, $fecha, $metodo, $cuenta, $ref, $obs, $usu){
            return $this->llamar("CALL SP_REGISTRAR_ADELANTO(?,?,?,?,?,?,?,?)", [$doc, $monto, $fecha, $metodo, $cuenta, $ref, $obs, $usu], true);
        }
        public function Anular_Pago($id, $motivo, $usu){ return $this->llamar("CALL SP_ANULAR_PAGO_DOCENTE(?,?,?)", [$id, $motivo, $usu], true); }
        public function Mis_Boletas($usu){ return $this->llamar("CALL SP_MIS_BOLETAS(?)", [$usu]); }
    }
?>
