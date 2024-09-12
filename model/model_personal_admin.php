<?php
    require_once 'model_conexion.php';

    class Modelo_Personal_Administrativo extends conexionBD{
        

        public function Listar_Personal_Administrativo(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_PERSONAL_ADMIN()";
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
        public function Registrar_Personal($dni,$nombre,$apelli,$tipo,$sexo,$fechanaci,$telf,$telfal,$direc,$ruta,$usu,$contra,$email,$rol){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_PERSONAL(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$dni);
            $query ->bindParam(2,$nombre);
            $query ->bindParam(3,$apelli);
            $query ->bindParam(4,$tipo);
            $query ->bindParam(5,$sexo);
            $query ->bindParam(6,$fechanaci);
            $query ->bindParam(7,$telf);
            $query ->bindParam(8,$telfal);
            $query ->bindParam(9,$direc);
            $query ->bindParam(10,$ruta);
            $query ->bindParam(11,$usu);
            $query ->bindParam(12,$contra);
            $query ->bindParam(13,$email);
            $query ->bindParam(14,$rol);


            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Personal($id,$dni,$nombre,$apelli,$tipo,$sexo,$fechanaci,$telf,$telfal,$direc,$esta,$ruta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_PERSONAL(?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$dni);
            $query ->bindParam(3,$nombre);
            $query ->bindParam(4,$apelli);
            $query ->bindParam(5,$tipo);
            $query ->bindParam(6,$sexo);
            $query ->bindParam(7,$fechanaci);
            $query ->bindParam(8,$telf);
            $query ->bindParam(9,$telfal);
            $query ->bindParam(10,$direc);
            $query ->bindParam(11,$esta);
            $query ->bindParam(12,$ruta);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Eliminar_Personal($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_PERSONAL(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
    
            $resul = $query->execute();
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Roles(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ROL_PERSONAL()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
    }




?>