<?php
    require_once 'model_conexion.php';

    class Modelo_Empleado extends conexionBD{
        

        public function Listar_Empleado(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_EMPLEADO()";
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
        public function Registrar_Empleado($nro,$nom,$apepa,$apema,$fnac,$movil,$dire,$email){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_EMPLEADO(?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$nro);
            $query ->bindParam(2,$nom);
            $query ->bindParam(3,$apepa);
            $query ->bindParam(4,$apema);
            $query ->bindParam(5,$fnac);
            $query ->bindParam(6,$movil);
            $query ->bindParam(7,$dire);
            $query ->bindParam(8,$email);
            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Empleado($id,$nro,$nom,$apepa,$apema,$fnac,$movil,$dire,$email,$esta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_EMPLEADO(?,?,?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$nro);
            $query ->bindParam(3,$nom);
            $query ->bindParam(4,$apepa);
            $query ->bindParam(5,$apema);
            $query ->bindParam(6,$fnac);
            $query ->bindParam(7,$movil);
            $query ->bindParam(8,$dire);
            $query ->bindParam(9,$email);
            $query ->bindParam(10,$esta);
            $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function listar_total_Empleados(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TOTAL_EMPLEADO()";
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
        public function Modificar_foto_empleado($id,$ruta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_EMPLEADO_FOTO(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$ruta);

            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
    }




?>