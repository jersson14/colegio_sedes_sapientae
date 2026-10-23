<?php
    require_once 'model_conexion.php';

    // Métodos de pago y cajas/cuentas del colegio (Fase 4)
    class Modelo_Formas_Pago extends conexionBD{

        const METODO_HISTORICO = 'NO REGISTRADO (HISTÓRICO)';

        public function Listar_Metodos(){
            $c = conexionBD::conexionPDO();
            $arreglo = $c->query("SELECT m.*, (SELECT COUNT(*) FROM pagos_metodos pm WHERE pm.metodo_pago_id = m.id_metodo) AS usos
                                    FROM metodos_pago m ORDER BY m.estado, m.id_metodo")->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        public function Listar_Cuentas(){
            $c = conexionBD::conexionPDO();
            $arreglo = $c->query("SELECT cu.*, (SELECT COUNT(*) FROM pagos_metodos pm WHERE pm.cuenta_id = cu.id_cuenta) AS usos,
                                         (SELECT IFNULL(SUM(pm.monto), 0) FROM pagos_metodos pm INNER JOIN pagos p ON p.id_pago = pm.id_pago
                                           WHERE pm.cuenta_id = cu.id_cuenta AND p.estado = 'VALIDO') AS cobrado
                                    FROM cuentas_financieras cu ORDER BY cu.estado, cu.tipo, cu.nombre")->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        /** $id null = nuevo. Devuelve ['ok'=>bool,'mensaje'=>...]. */
        public function Guardar_Metodo($id, $nombre, $tipo, $req_ref, $req_cta, $comision, $estado){
            $c = conexionBD::conexionPDO();
            try {
                if ($id) {
                    $q = $c->prepare("SELECT nombre FROM metodos_pago WHERE id_metodo = ?");
                    $q->execute([$id]);
                    if ($q->fetchColumn() === self::METODO_HISTORICO) {
                        conexionBD::cerrar_conexion();
                        return ['ok' => false, 'mensaje' => 'El método histórico no se puede modificar'];
                    }
                    $q = $c->prepare("UPDATE metodos_pago SET nombre = ?, tipo = ?, requiere_referencia = ?, requiere_cuenta = ?,
                                             comision_pct = ?, estado = ?, updated_at = NOW() WHERE id_metodo = ?");
                    $q->execute([$nombre, $tipo, $req_ref, $req_cta, $comision, $estado, $id]);
                } else {
                    $q = $c->prepare("INSERT INTO metodos_pago(nombre, tipo, requiere_referencia, requiere_cuenta, comision_pct, estado, created_at)
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $q->execute([$nombre, $tipo, $req_ref, $req_cta, $comision, $estado]);
                }
            } catch (PDOException $e) {
                conexionBD::cerrar_conexion();
                if ($e->getCode() == 23000) {
                    return ['ok' => false, 'mensaje' => 'Ya existe un método de pago con ese nombre'];
                }
                error_log('Guardar_Metodo: ' . $e->getMessage());
                return ['ok' => false, 'mensaje' => 'No se pudo guardar'];
            }
            conexionBD::cerrar_conexion();
            return ['ok' => true, 'mensaje' => 'Método de pago guardado'];
        }
        public function Guardar_Cuenta($id, $tipo, $nombre, $banco, $numero, $cci, $titular, $saldo, $fecha_saldo, $estado){
            $c = conexionBD::conexionPDO();
            try {
                if ($id) {
                    $q = $c->prepare("UPDATE cuentas_financieras SET tipo = ?, nombre = ?, banco = ?, numero_cuenta = ?, codigo_interbancario = ?,
                                             titular = ?, saldo_inicial = ?, fecha_saldo_inicial = ?, estado = ?, updated_at = NOW()
                                       WHERE id_cuenta = ?");
                    $q->execute([$tipo, $nombre, $banco, $numero, $cci, $titular, $saldo, $fecha_saldo, $estado, $id]);
                } else {
                    $q = $c->prepare("INSERT INTO cuentas_financieras(tipo, nombre, banco, numero_cuenta, codigo_interbancario, titular,
                                             saldo_inicial, fecha_saldo_inicial, estado, created_at)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $q->execute([$tipo, $nombre, $banco, $numero, $cci, $titular, $saldo, $fecha_saldo, $estado]);
                }
            } catch (PDOException $e) {
                conexionBD::cerrar_conexion();
                if ($e->getCode() == 23000) {
                    return ['ok' => false, 'mensaje' => 'Ya existe una caja o cuenta con ese nombre'];
                }
                error_log('Guardar_Cuenta: ' . $e->getMessage());
                return ['ok' => false, 'mensaje' => 'No se pudo guardar'];
            }
            conexionBD::cerrar_conexion();
            return ['ok' => true, 'mensaje' => 'Caja / cuenta guardada'];
        }
    }
?>
