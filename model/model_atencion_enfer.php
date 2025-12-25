<?php
    require_once 'model_conexion.php';

    class Modelo_Atencion_Enfer extends conexionBD{
        

        public function Listar_Atencion_Enfermeria(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ATENCION_ENFERME()";
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
        public function Listar_atenciones_filtros_enfermeria($grado,$fechaini,$fechafin){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ATENCIONES_ENFERMERIA_FILTRO(?,?,?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$grado);
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
    
        public function Registrar_Atencion_Enferme($estu,$motivo,$diagno,$observa,$idusu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_ATENCION_ENFERME(?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$estu);
            $query ->bindParam(2,$motivo);
            $query ->bindParam(3,$diagno);
            $query ->bindParam(4,$observa);
            $query ->bindParam(5,$idusu);

            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();

        }
        public function Modificar_Atencion_Enfermeria($id,$estu,$motivo,$diagno,$observa,$idusu){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_ATENCION_ENFERMERIA(?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$estu);
            $query ->bindParam(3,$motivo);
            $query ->bindParam(4,$diagno);
            $query ->bindParam(5,$observa);
            $query ->bindParam(6,$idusu);
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