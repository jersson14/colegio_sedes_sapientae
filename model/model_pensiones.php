<?php
    require_once 'model_conexion.php';

    // Pensiones por año escolar y nivel (Fase 8): registrar, generar el cronograma y ajustar vencimientos
    class Modelo_Pensiones extends conexionBD{

        private function llamar($sql, $params = [], $una_fila = false){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare($sql);
            $q->execute($params);
            $r = $una_fila ? $q->fetch(PDO::FETCH_ASSOC) : $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $r;
        }
        private function codigo($sql, $params){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare($sql);
            $q->execute($params);
            $r = $q->fetchColumn();
            conexionBD::cerrar_conexion();
            return (int) $r;
        }
        public function Listar_Pensiones($anio, $nivel){
            return $this->llamar("CALL SP_LISTAR_PENSIONES(?,?)", [$anio, $nivel]);
        }
        public function Anios_Escolares(){
            return $this->llamar("SELECT `Id_año_escolar` AS id, `año_escolar` AS anio, estado, fecha_inicio, fecha_fin
                                    FROM `año_escolar` ORDER BY fecha_inicio DESC");
        }
        // 1 registrada, 2 ya existe, 3 vencimiento fuera del año escolar, 0 inválido
        public function Registrar_Pensiones($anio, $nivel, $mes, $fecha, $precio, $mora){
            return $this->codigo("CALL SP_REGISTRAR_PENSIONES(?,?,?,?,?,?)", [$anio, $nivel, $mes, $fecha, $precio, $mora]);
        }
        // 1 actualizada, 2 ya existe, 3 vencimiento fuera del año, 4 tiene pagos (no cambia año/nivel/mes), 0 inválido
        public function Modificar_Pensiones($id, $anio, $nivel, $mes, $fecha, $precio, $mora){
            return $this->codigo("CALL SP_MODIFICAR_PENSIONES(?,?,?,?,?,?,?)", [$id, $anio, $nivel, $mes, $fecha, $precio, $mora]);
        }
        // 1 eliminada, 2 tiene pagos registrados (no se elimina)
        public function Eliminar_Pensiones($id){
            return $this->codigo("CALL SP_ELIMINAR_PENSION(?)", [$id]);
        }
        public function Generar_Pensiones($anio, $nivel, $desde, $hasta, $dia, $precio, $mora){
            return $this->llamar("CALL SP_GENERAR_PENSIONES(?,?,?,?,?,?,?)", [$anio, $nivel, $desde, $hasta, $dia, $precio, $mora], true);
        }
        public function Ajustar_Vencimientos($anio, $aplicar, $usu){
            return $this->llamar("CALL SP_AJUSTAR_VENCIMIENTOS(?,?,?)", [$anio, $aplicar, $usu]);
        }
    }
?>
