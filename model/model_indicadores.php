<?php
    require_once 'model_conexion.php';

    // Categorías de ingresos y egresos dentro de los grupos del plan de cuentas (Fase 8)
    class Modelo_Indicadores extends conexionBD{

        private function llamar($sql, $params = []){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare($sql);
            $q->execute($params);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
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
        public function Listar_Indicadores(){
            return ['data' => $this->llamar("CALL SP_LISTAR_INDICADORES()")];
        }
        public function Listar_Grupos(){
            return $this->llamar("CALL SP_LISTAR_GRUPOS_INDICADOR()");
        }
        // 1 registrada, 2 ya existe, 3 grupo inválido
        public function Registrar_Indicador($tipo, $nombre, $descrip, $padre){
            return $this->codigo("CALL SP_REGISTRAR_INDICADORES(?,?,?,?)", [$tipo, $nombre, $descrip, $padre]);
        }
        // 1 actualizada, 2 nombre repetido, 3 grupo inválido, 4 no se puede cambiar tipo/grupo
        public function Modificar_Indicador($id, $tipo, $nombre, $descrip, $esta, $padre){
            return $this->codigo("CALL SP_MODIFICAR_INDICADOR(?,?,?,?,?,?)", [$id, $tipo, $nombre, $descrip, $esta, $padre]);
        }
        // 1 eliminada, 2 tiene movimientos, 3 del sistema o grupo con categorías
        public function Eliminar_Indicador($id){
            return $this->codigo("CALL SP_ELIMINAR_INDICADOR(?)", [$id]);
        }
    }
?>
