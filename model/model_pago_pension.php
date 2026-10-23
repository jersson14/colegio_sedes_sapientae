<?php
    require_once 'model_conexion.php';

    class Modelo_Pago_Pension extends conexionBD{
        

        public function Listar_Pago_pension(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PAGO_PENSION()";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_pagos_filtro($año,$grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PAGOS_FILTRO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$año);
            $query->bindParam(2,$grado);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_pagos_por_id($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PAGO_PENSION_ID(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_pagos_todo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PAGO_PENSION_TODO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_pagos_por_id_año($id,$año){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PAGO_PENSION_ID_AÑO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$año);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Año(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_AÑO()";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Pension($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_PENSION(?)";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function TraerMonto($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAE_MONTO(?)";
            
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function TraerTipo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TIPO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        /**
         * Registra varios conceptos de pago de una matrícula en una sola transacción:
         * si alguno falla (ya pagado, excede saldo, etc.) no se registra ninguno.
         * $lineas: [['concepto'=>..., 'id_pension'=>int|null, 'monto'=>'0.00', 'exonerar'=>0|1, 'motivo'=>''], ...]
         */
        public function Registrar_Pago($id_matri,$lineas,$usu){
            $c = conexionBD::conexionPDO();
            $detalle = [];
            try {
                $c->beginTransaction();
                foreach($lineas as $i => $l){
                    $query = $c->prepare("CALL SP_REGISTRAR_PAGO_PENSION(?,?,?,?,?,?,?)");
                    $query->execute([$id_matri,$l['concepto'],$l['id_pension'],$l['monto'],$l['exonerar'],$l['motivo'],$usu]);
                    $r = $query->fetch(PDO::FETCH_ASSOC);
                    $query->closeCursor();
                    if((int)$r['codigo'] !== 1){
                        $c->rollBack();
                        conexionBD::cerrar_conexion();
                        return ['ok'=>false,'codigo'=>(int)$r['codigo'],'linea'=>$i+1,'mensaje'=>$r['mensaje']];
                    }
                    $detalle[] = $r;
                }
                $c->commit();
            } catch (PDOException $e) {
                if($c->inTransaction()){ $c->rollBack(); }
                error_log('Registrar_Pago: '.$e->getMessage());
                conexionBD::cerrar_conexion();
                return ['ok'=>false,'codigo'=>0,'mensaje'=>'Error al registrar el pago'];
            }
            conexionBD::cerrar_conexion();
            return ['ok'=>true,'codigo'=>1,'mensaje'=>'Pago registrado','detalle'=>$detalle];
        }
        public function Saldo_Pension($id_matri,$id_pension){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("CALL SP_SALDO_PENSION(?,?)");
            $query->execute([$id_matri,$id_pension]);
            $r = $query->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r ?: null;
        }
        public function Cargar_Pension_Matricula($id_matri){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("CALL SP_CARGAR_SELECT_PENSION_MATRICULA(?)");
            $query->execute([$id_matri]);
            $arreglo = $query->fetchAll();
            conexionBD::cerrar_conexion();
            return $arreglo;
        }

        public function Listar_pagos_alu($id) {
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTAR_PAGOS(?)";
            $query = $c->prepare($sql);
            $query->bindParam(1, $id);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            $total_sub_total = 0;
            foreach ($resultado as $resp) {
                $total_sub_total += $resp['sub_total'];
                $arreglo["data"][] = $resp;
            }
            // Añadimos el total de sub_total al arreglo
            $arreglo["total_sub_total"] = $total_sub_total;
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        public function Modificar_Pago_pension($id,$monto,$descrip,$usu){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("CALL SP_MODIFICAR_PAGO_PENSION(?,?,?,?)");
            $query->execute([$id,$monto,$descrip,$usu]);
            $r = $query->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        // Los pagos ya no se borran: quedan ANULADOS con motivo, usuario y fecha (y su ingreso también)
        public function Anular_pago_pension($id,$motivo,$usu){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("CALL SP_ANULAR_PAGO_PENSION(?,?,?)");
            $query->execute([$id,$motivo,$usu]);
            $r = $query->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }

    }
?>