<?php
    require_once 'model_conexion.php';

    class Modelo_Componentes extends conexionBD{
        

        public function Listar_componentes(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_COMPONENTES()";
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
        public function Listar_criterios_filtros($aula){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_COMPONENTES_FILTRO(?)";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->bindParam(1,$aula);

            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($resultado as $resp){
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Cargar_Id_Detalle($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_SELECT_ID_DETALLE(?)";
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
        public function Listar_componentes_curso($id){
            $c = conexionBD::conexionPDO();
            $arreglo = array();
            $sql = "CALL SP_LISTA_COMPO_CURSO_EDITAR(?)";
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
    
        public function Modificar_Componente($id_asignatura, $componente, $observacion) {
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_COMPONENTE(?, ?, ?)"; // Procedimiento almacenado para registrar un componente
            $query = $c->prepare($sql);
            $query->bindParam(1, $id_asignatura);
            $query->bindParam(2, $componente);
            $query->bindParam(3, $observacion);
        
            $query->execute();
            $resultado = $query->fetchColumn();
            
            conexionBD::cerrar_conexion(); // Cerramos la conexión después de la ejecución
            return $resultado; // Devuelve el resultado del procedimiento almacenado (1: registrado, 0: ya existe)
        }
    
        // Registrar un nuevo componente
        public function Registrar_Componente($id_asignatura, $componente, $observacion) {
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_COMPONENTE(?, ?, ?)"; // Procedimiento almacenado para registrar un componente
            $query = $c->prepare($sql);
            $query->bindParam(1, $id_asignatura);
            $query->bindParam(2, $componente);
            $query->bindParam(3, $observacion);
        
            $query->execute();
            $resultado = $query->fetchColumn();
            
            conexionBD::cerrar_conexion(); // Cerramos la conexión después de la ejecución
            return $resultado; // Devuelve el resultado del procedimiento almacenado (1: registrado)
        }
    
        // Registrar o Modificar un componente
       
        public function Eliminar_componentes($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_COMPONENTES(?)";
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
        public function Eliminar_componentes_unico($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_COMPONENTES_UNICO(?)";
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