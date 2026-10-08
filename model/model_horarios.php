<?php
    require_once 'model_conexion.php';

    class Modelo_Horarios extends conexionBD{
        
        public function Listar_Horarios(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_HORARIOS()";
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
        public function Listar_horarios_filtro($año,$grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_HORARIOS_FILTRO(?,?)";
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
        public function Listar_componentes_horas_aulas($id) {
            $c = conexionBD::conexionPDO();
        
            $sql = "CALL SP_CARGAR_HORA_ID_AULA(?)";
            $arreglo = array();
            $query = $c->prepare($sql);
            $query->bindParam(1, $id, PDO::PARAM_INT);
            $query->execute();
        
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $arreglo[] = $row;
            }
        
            conexionBD::cerrar_conexion();
            return $arreglo;
        }

        public function Listar_pagos_por_id_estudiante_todo($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE_TODO(?)";
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
        public function Listar_pagos_por_id_estudiante($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE(?)";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->bindParam(1, $id);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        
        public function Listar_pagos_por_id_estudiante_año($id,$año){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE_AÑO(?,?)";
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
        public function Cargar_Id_aula_horarios($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_HORARIOS_ID_AULA(?)";
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
        public function Listar_compo_cursos($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTAR_COMPONENTES_CURSO(?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Listar_componentes_horarios($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTA_HORARIOS_EDITAR(?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        
        }
        public function Componente_Existe($id_asignatura) {
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_COMPROBAR_COMPONENTE(?)"; // Procedimiento almacenado para verificar existencia
            $query = $c->prepare($sql);
            $query->bindParam(1, $id_asignatura);
            $query->execute();
            $resultado = $query->fetchColumn();
            
            conexionBD::cerrar_conexion();
            return $resultado; // Devuelve 1 si existe, 0 si no existe
        }
        public function Cargar_horas($id,$año){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_HORAS(?,?)";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$año);

            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Id_Detalle($id,$año){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ID_DETALLE_HORARIO(?,?)";
            $arreglo = array();

            $query  = $c->prepare($sql);
            $query->bindParam(1,$id);
            $query->bindParam(2,$año);

            $query->execute();
            $resultado = $query->fetchAll();
            foreach($resultado as $resp){
                $arreglo[]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        // Registro y borrado de horarios: src/Services/GestionarHorarios.php.
        
        
        // Registrar o Modificar un componente
       
        public function Eliminar_horario_unico($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_HORARIO_UNICO(?)";
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
    }




?>