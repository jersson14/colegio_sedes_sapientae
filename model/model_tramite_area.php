<?php
    require_once 'model_conexion.php';

    class Modelo_TramiteArea extends conexionBD{

        public function Listar_Tramite($idusuario){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_AREA(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$idusuario);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_Tramite_Areas($idareas){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_AREA2(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$idareas);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
         public function Listar_Tramite_Areas_Buscar($id,$estados){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_AREA_BUSCAR(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$estados);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Tipo(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_TIPO()";
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
        public function Registrar_Deri($iddo,$orig,$dest,$desc,$idusu,$ruta,$tipo,$acc){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_TRAMITE_DERIVAR(?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$iddo);
            $query ->bindParam(2,$orig);
            $query ->bindParam(3,$dest);
            $query ->bindParam(4,$desc);
            $query ->bindParam(5,$idusu);
            $query ->bindParam(6,$ruta);
            $query ->bindParam(7,$tipo);
            $query ->bindParam(8,$acc);

            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
       
        public function Listar_Tramite_Seguimiento($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_SEGUIMIENTO(?)";
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
        public function Cargar_Select_DNI_UL($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_DNI_UL(?)";
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
        
        public function Modificar_Estatus_Tramite($id,$estatus){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_TRAMITE_ESTATUS(?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$estatus);
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Listar_Tramite_Fecha_Area($fechainicio,$fechafin,$area){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_AREA_FECHAS_TA(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fechainicio);
            $query->bindParam(2,$fechafin);
            $query->bindParam(3,$area);
    
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_Tramite_Fecha_Estado($fechainicio,$fechafin,$estado,$area){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_AREA_ESTADO_TA(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fechainicio);
            $query->bindParam(2,$fechafin);
            $query->bindParam(3,$estado);
            $query->bindParam(4,$area);
    
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_Tramite_Fecha_TipoDoc($fechainicio,$fechafin,$tipodoc,$area){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE_AREA_TIPO_DOC_TA(?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$fechainicio);
            $query->bindParam(2,$fechafin);
            $query->bindParam(3,$tipodoc);
            $query->bindParam(4,$area);

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