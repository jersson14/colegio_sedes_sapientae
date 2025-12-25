<?php
    require_once 'model_conexion.php';

    class Modelo_aula_Horas extends conexionBD{
        

        public function Listar_aula_horas(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_AULAS_HORAS()";
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
        public function Listar_aula_horas_filtro($año,$grado){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_AULAS_HORAS_FILTRO(?,?)";
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
        public function Listar_componentes_horas_aulas($id,$año) {
            $c = conexionBD::conexionPDO();
            $arreglo = [];
        
            $sql = "CALL SP_CARGAR_HORA_ID_AULA(?,?)";
            $query = $c->prepare($sql);
            $query->bindParam(1, $id, PDO::PARAM_INT);
            $query->bindParam(2, $año, PDO::PARAM_INT);

            $query->execute();
        
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $arreglo[] = $row;
            }
        
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        
        public function Cargar_Id_aula($id,$año){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_CARGAR_HORA_ID_AULA(?,?)";
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
        public function Registrar_Aulas_horas($año, $aula, $turno, $inicio, $fin) {
            // Establecer la conexión a la base de datos
            $c = conexionBD::conexionPDO();
            
            // Definir la llamada al procedimiento almacenado
            $sql = "CALL SP_REGISTRAR_AULA_HORA(?, ?, ?, ?, ?)";
            $query = $c->prepare($sql);
            
            // Vincular los parámetros al procedimiento almacenado
            $query->bindParam(1, $año);
            $query->bindParam(2, $aula);
            $query->bindParam(3, $turno);
            $query->bindParam(4, $inicio);
            $query->bindParam(5, $fin);
            
            // Ejecutar la consulta
            $query->execute();
            
            // Obtener el resultado del procedimiento almacenado
            $resultado = $query->fetchColumn();
            
            // Cerrar la conexión a la base de datos
            conexionBD::cerrar_conexion();
            
            // Devolver el resultado del procedimiento almacenado (1: registrado, 2: ya existe, 0: error)
            return $resultado;
        }
        
        public function Modificar_Hora_Aula($año, $aula, $turno, $inicio, $fin) {
            // Establecer la conexión a la base de datos
            $c = conexionBD::conexionPDO();
            
            // Definir la llamada al procedimiento almacenado
            $sql = "CALL SP_REGISTRAR_AULA_HORA(?, ?, ?, ?, ?)";
            $query = $c->prepare($sql);
            
            // Vincular los parámetros al procedimiento almacenado
            $query->bindParam(1, $año);
            $query->bindParam(2, $aula);
            $query->bindParam(3, $turno);
            $query->bindParam(4, $inicio);
            $query->bindParam(5, $fin);
            
            // Ejecutar la consulta
            $query->execute();
            
            // Obtener el resultado del procedimiento almacenado
            $resultado = $query->fetchColumn();
            
            // Cerrar la conexión a la base de datos
            conexionBD::cerrar_conexion();
            
            // Devolver el resultado del procedimiento almacenado (1: registrado, 2: ya existe, 0: error)
            return $resultado;
        }
        // Registrar o Modificar un componente
       
        public function Eliminar_horas_aula($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_HORAS_AULA(?)";
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
        public function Eliminar_horas_unico($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_HORAS_UNICO(?)";
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