<?php
    require_once 'model_conexion.php';

    class Modelo_Docentes extends conexionBD{
        

        public function Listar_Docentes(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_DOCENTES()";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                unset($resp["usu_contra"]); // la clave (aunque cifrada) no se envía al navegador
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Registrar_Docentes($dni,$nombre,$apelli,$espe,$sexo,$fechanaci,$telf,$telfal,$direc,$ruta,$usu,$contra,$email){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_DOCENTES(?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$dni);
            $query ->bindParam(2,$nombre);
            $query ->bindParam(3,$apelli);
            $query ->bindParam(4,$espe);
            $query ->bindParam(5,$sexo);
            $query ->bindParam(6,$fechanaci);
            $query ->bindParam(7,$telf);
            $query ->bindParam(8,$telfal);
            $query ->bindParam(9,$direc);
            $query ->bindParam(10,$ruta);
            $query ->bindParam(11,$usu);
            $query ->bindParam(12,$contra);
            $query ->bindParam(13,$email);


            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_Docentes($id,$dni,$nombre,$apelli,$espe,$sexo,$fechanaci,$telf,$telfal,$direc,$esta,$ruta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_DOCENTES(?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$dni);
            $query ->bindParam(3,$nombre);
            $query ->bindParam(4,$apelli);
            $query ->bindParam(5,$espe);
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
        /** Activa o desactiva al docente y su usuario (inactivo no puede ingresar al sistema). */
        public function Cambiar_Estado($usu_id, $estado){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("UPDATE docentes d JOIN usuario u ON u.usu_id = d.id_asusuario
                              SET d.docente_estatus = ?, d.updated_at = NOW(), u.usu_estatus = ?, u.updated_at = NOW()
                              WHERE d.id_asusuario = ?");
            $q->execute([$estado, $estado, $usu_id]);
            return $q->rowCount() > 0 ? 1 : 0;
        }
        public function Eliminar_Docente($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_DOCENTE(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
    
            // Con matrícula, cursos, contratos o pagos la base no deja borrar (claves foráneas): se responde 0
            try {
                $resul = $query->execute();
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { return 0; }
                throw $e;
            }
            if($resul){
                return 1;
            }else{
                return 0;
            }
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Select_Especialidad(){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_CARGAR_SELECT_ESPECIALIDAD()";
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Modificar_foto_docente($id,$ruta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_DOCENTE_FOTO(?,?)";
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