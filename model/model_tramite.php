<?php
    require_once 'model_conexion.php';

    class Modelo_Tramite extends conexionBD{
        
        public function Listar_Tramite(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAMITE()";
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
        public function Registrar_Tramite($dni,$nom,$apt,$apm,$cel,$ema,$dir,$vpresentacion,$ruc,$raz,$arp,$ard,$tip
        ,$ndo,$asu,$ruta,$fol,$idusu,$acc,$obs,$tre){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_TRAMITE(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$dni);
            $query ->bindParam(2,$nom);
            $query ->bindParam(3,$apt);
            $query ->bindParam(4,$apm);
            $query ->bindParam(5,$cel);
            $query ->bindParam(6,$ema);
            $query ->bindParam(7,$dir);
            $query ->bindParam(8,$vpresentacion);
            $query ->bindParam(9,$ruc);
            $query ->bindParam(10,$raz);
            $query ->bindParam(11,$arp);
            $query ->bindParam(12,$ard);
            $query ->bindParam(13,$tip);
            $query ->bindParam(14,$ndo);
            $query ->bindParam(15,$asu);
            $query ->bindParam(16,$ruta);
            $query ->bindParam(17,$fol);
            $query ->bindParam(18,$idusu);
            $query ->bindParam(19,$acc);
            $query ->bindParam(20,$obs);
            $query ->bindParam(21,$tre);

            $query ->execute();
            if($row=$query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Registrar_Tramite_ul($dni,$nom,$apt,$apm,$cel,$ema,$dir,$vpresentacion,$ruc,$raz,$arp,$ard,$tip
        ,$ndo,$asu,$ruta,$fol,$idusu,$acc,$obs,$tre){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_TRAMITE_UL(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$dni);
            $query ->bindParam(2,$nom);
            $query ->bindParam(3,$apt);
            $query ->bindParam(4,$apm);
            $query ->bindParam(5,$cel);
            $query ->bindParam(6,$ema);
            $query ->bindParam(7,$dir);
            $query ->bindParam(8,$vpresentacion);
            $query ->bindParam(9,$ruc);
            $query ->bindParam(10,$raz);
            $query ->bindParam(11,$arp);
            $query ->bindParam(12,$ard);
            $query ->bindParam(13,$tip);
            $query ->bindParam(14,$ndo);
            $query ->bindParam(15,$asu);
            $query ->bindParam(16,$ruta);
            $query ->bindParam(17,$fol);
            $query ->bindParam(18,$idusu);
            $query ->bindParam(19,$acc);
            $query ->bindParam(20,$obs);
            $query ->bindParam(21,$tre);

            $query ->execute();
            if($row=$query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Registrar_Tramite_Externo($dni,$nom,$apt,$apm,$cel,$ema,$dir,$vpresentacion,$ruc,$raz,$tip,$ndo,$asu,$ruta,$fol){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_TRAMITE_EXTERNO(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$dni);
            $query ->bindParam(2,$nom);
            $query ->bindParam(3,$apt);
            $query ->bindParam(4,$apm);
            $query ->bindParam(5,$cel);
            $query ->bindParam(6,$ema);
            $query ->bindParam(7,$dir);
            $query ->bindParam(8,$vpresentacion);
            $query ->bindParam(9,$ruc);
            $query ->bindParam(10,$raz);
            $query ->bindParam(11,$tip);
            $query ->bindParam(12,$ndo);
            $query ->bindParam(13,$asu);
            $query ->bindParam(14,$ruta);
            $query ->bindParam(15,$fol);


            $query ->execute();
            if($row=$query->fetchColumn()){
                return $row;
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
        public function TraerRequisitos($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_TRAE_REQUISITO(?)";
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
    public function Cargar_Select_DNI(){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_CARGAR_SELECT_DNI()";
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
    public function TraerRequisitosDNI($id){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_LISTAR_TRAE_REQUISITO_DNI(?)";
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
    public function listar_total_docpendientes(){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_LISTAR_TOTAL_DOC_PENDIENTE()";
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
    public function listar_total_docaceptado(){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_TOTAL_DOCUMENTOS_ACEPTADOS()";
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
    public function listar_total_docfinalizado(){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_TOTAL_DOCUMENTOS_FINALIZADO()";
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
    
    public function Listar_Tramite_Estado($estados){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_LISTAR_TRAMITE_ESTADO(?)";
        $arreglo = array();
        $query  = $c->prepare($sql);
        $query->bindParam(1,$estados);
        $query->execute();
        $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach($resultado as $resp){
            $arreglo["data"][]=$resp;
        }
        return $arreglo;
        conexionBD::cerrar_conexion();
    }
    public function Rechazar_Tramite($id2,$desc2,$loc){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_RECHAZAR_TRAMITE(?,?,?)";
        $arreglo = array();
        $query  = $c->prepare($sql);
        $query ->bindParam(1,$id2);
        $query ->bindParam(2,$desc2);
        $query ->bindParam(3,$loc);

        $resul = $query->execute();
        if($resul){
            return 1;
        }else{
            return 0;
        }
        conexionBD::cerrar_conexion();
    }
    ///Eliminar tramite
    public function Eliminar_Tramite($id2,$orig2,$desc2,$idusu2){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_ELIMINAR_TRAMITE(?,?,?,?)";
        $arreglo = array();
        $query  = $c->prepare($sql);
        $query ->bindParam(1,$id);
        $query ->bindParam(2,$id);
        $query ->bindParam(3,$id);
        $query ->bindParam(4,$id);

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
        $sql = "CALL SP_LISTAR_TRAMITE_AREA_FECHAS(?,?,?)";
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
    public function Listar_Tramite_Fecha_Estado($fechainicio,$fechafin,$estado){
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_LISTAR_TRAMITE_AREA_ESTADO(?,?,?)";
        $arreglo = array();
        $query  = $c->prepare($sql);
        $query->bindParam(1,$fechainicio);
        $query->bindParam(2,$fechafin);
        $query->bindParam(3,$estado);

        $query->execute();
        $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach($resultado as $resp){
            $arreglo["data"][]=$resp;
        }
        return $arreglo;
        conexionBD::cerrar_conexion();
    }
    public function Listar_Tramite_Fecha_Tipodoc($fechainicio,$fechafin,$tipodoc)
    {
        $c = conexionBD::conexionPDO();
        $sql = "CALL SP_LISTAR_TRAMITE_AREA_TIPO_DOC(?,?,?)";
        $arreglo = array();
        $query  = $c->prepare($sql);
        $query->bindParam(1,$fechainicio);
        $query->bindParam(2,$fechafin);
        $query->bindParam(3,$tipodoc);

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