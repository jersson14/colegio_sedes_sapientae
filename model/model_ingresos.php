<?php
    require_once 'model_conexion.php';

    class Modelo_Ingresos extends conexionBD{
        

        public function Listar_Ingresos_diversos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_INGRESOS_DIVERSOS()";
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
        public function Listar_Ingresos_pensiones(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_INGRESOS_PENSIONES()";
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
        public function Listar_ingresos_diversos_filtro($indi,$fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_INGRESOS_DIVERSOS_FILTRO(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$indi);
            $query->bindParam(2,$fechaini);
            $query->bindParam(3,$fechafin);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_ingresos_pensiones_filtro($fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_INGRESOS_PENSIONES_FILTRO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fechaini);
            $query->bindParam(2,$fechafin);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Indicadores(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_INDICADORES()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Registrar_Ingresos($indi,$cantidad,$monto,$obse,$usu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_INGRESOS(?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$indi);
            $query ->bindParam(2,$cantidad);
            $query ->bindParam(3,$monto);
            $query ->bindParam(4,$obse);
            $query ->bindParam(5,$usu);

            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Ingresos($id,$indi,$cantidad,$monto,$obser,$usu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_INGRESOS(?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$indi);
            $query ->bindParam(3,$cantidad);
            $query ->bindParam(4,$monto);
            $query ->bindParam(5,$obser);
            $query ->bindParam(6,$usu);
          
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();;
        }
        public function Anular_Ingresos($id,$obser,$usu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ANULAR_INGRESOS(?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$obser);
            $query ->bindParam(3,$usu);
          
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();;
        }
    }




?>