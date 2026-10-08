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

        // Cobro, edición y anulación de pagos: src/Services/GestionarPensiones.php.
    }
?>