<?php
    require_once 'model_conexion.php';

    class Modelo_Egresos extends conexionBD{
        

        public function Listar_Egresos_diversos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EGRESOS_DIVERSOS()";
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
        public function Listar_egresos_diversos_filtro($indi,$fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EGRESOS_DIVERSOS_FILTRO(?,?,?)";
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
        public function Cargar_Select_Indicadores_Egresos(){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_CARGAR_SELECT_INDICADORES_GASTOS()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        // Devuelve ['codigo'=>1|0|3, 'mensaje'=>..., 'id'=>...] (3 = saldo de caja insuficiente)
        public function Registrar_Egreso($indi,$cantidad,$monto,$obse,$usu,$fecha,$metodo,$cuenta,$ref,$benef,$comprob,$adjunto){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("CALL SP_REGISTRAR_EGRESOS(?,?,?,?,?,?,?,?,?,?,?,?)");
            $query->execute([$indi,$cantidad,$monto,$obse,$usu,$fecha,$metodo,$cuenta,$ref,$benef,$comprob,$adjunto]);
            $r = $query->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        public function Modificar_Egresos($id,$indi,$cantidad,$monto,$obser,$usu,$fecha,$metodo,$cuenta,$ref,$benef,$comprob){
            $c = conexionBD::conexionPDO();
            $query = $c->prepare("CALL SP_MODIFICAR_EGRESOS(?,?,?,?,?,?,?,?,?,?,?,?)");
            $query->execute([$id,$indi,$cantidad,$monto,$obser,$usu,$fecha,$metodo,$cuenta,$ref,$benef,$comprob]);
            $r = $query->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        public function Anular_Egreso($id,$obser,$usu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ANULAR_EGRESOS(?,?,?)";
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
        public function Listar_diferencia(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_DIFERENCIA()";
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
        public function Listar_diferencia_filtro($fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_DIFERENCIA_FILTRO(?,?)";
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
    }




?>