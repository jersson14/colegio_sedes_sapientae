<?php
    require_once 'model_conexion.php';

    class Modelo_Alumnos extends conexionBD{
        

        public function Listar_Alumnos(){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_LISTAR_ALUMNOS()";
            $arreglo = array();
            $query  = $c->prepare($sql);
            $query->execute();
            $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
            $query->closeCursor();
            // Acceso al sistema del estudiante (usuario creado en su matrícula): ACTIVO, INACTIVO o SIN USUARIO
            $acceso = $c->query("SELECT m.id_alumno, IF(SUM(u.usu_estatus = 'ACTIVO') > 0, 'ACTIVO', 'INACTIVO')
                                 FROM matricula m JOIN usuario u ON u.usu_id = m.usu_id GROUP BY m.id_alumno")->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach($resultado as $resp){
                $resp['acceso'] = $acceso[$resp['Id_alumno']] ?? 'SIN USUARIO';
                $arreglo["data"][]=$resp;
            }
            return $arreglo;
            conexionBD::cerrar_conexion();
        }
        public function Registrar_Alumnos($dni,$nombre,$apepa,$apema,$sexo,$fechanaci,$telf,$direc,$ruta,$dnipa,$nompa,$celpa,$dnima,$nomma,$celma){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_REGISTRAR_ALUMNOS(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$dni);
            $query ->bindParam(2,$nombre);
            $query ->bindParam(3,$apepa);
            $query ->bindParam(4,$apema);
            $query ->bindParam(5,$sexo);
            $query ->bindParam(6,$fechanaci);
            $query ->bindParam(7,$telf);
            $query ->bindParam(8,$direc);
            $query ->bindParam(9,$ruta);
            $query ->bindParam(10,$dnipa);
            $query ->bindParam(11,$nompa);
            $query ->bindParam(12,$celpa);
            $query ->bindParam(13,$dnima);
            $query ->bindParam(14,$nomma);
            $query ->bindParam(15,$celma);

            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        public function Modificar_alumno($id,$dni,$nombre,$apepa,$apema,$sexo,$fechanaci,$telf,$direc,$ruta,$idpa,$dnipa,$nompa,$celpa,$dnima,$nomma,$celma){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_ALUMNOS(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $query  = $c->prepare($sql);
            $query ->bindParam(1,$id);
            $query ->bindParam(2,$dni);
            $query ->bindParam(3,$nombre);
            $query ->bindParam(4,$apepa);
            $query ->bindParam(5,$apema);
            $query ->bindParam(6,$sexo);
            $query ->bindParam(7,$fechanaci);
            $query ->bindParam(8,$telf);
            $query ->bindParam(9,$direc);
            $query ->bindParam(10,$ruta);
            $query ->bindParam(11,$idpa);
            $query ->bindParam(12,$dnipa);
            $query ->bindParam(13,$nompa);
            $query ->bindParam(14,$celpa);
            $query ->bindParam(15,$dnima);
            $query ->bindParam(16,$nomma);
            $query ->bindParam(17,$celma);
            $resultado = $query->execute();
            if($row = $query->fetchColumn()){
                return $row;
            }
            conexionBD::cerrar_conexion();
        }
        /** Activa o desactiva el acceso al sistema del estudiante (sus usuarios de matrícula). Devuelve las filas cambiadas. */
        public function Cambiar_Acceso($id_alumno, $estado){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("UPDATE usuario u JOIN matricula m ON m.usu_id = u.usu_id
                              SET u.usu_estatus = ?, u.updated_at = NOW() WHERE m.id_alumno = ?");
            $q->execute([$estado, $id_alumno]);
            return $q->rowCount();
        }
        public function Eliminar_Alumno($id){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_ELIMINAR_ALUMNO(?)";
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
        public function Modificar_foto_estudiante($id,$ruta){
            $c = conexionBD::conexionPDO();
            $sql = "CALL SP_MODIFICAR_ESTUDIANTE_FOTO(?,?)";
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

        /**
         * Padre o madre ya registrado con ese documento (por un hermano). Devuelve su nombre y celular, el otro
         * padre de esa familia y los hijos registrados, para ofrecer sus datos al registrar a otro estudiante.
         */
        public function Buscar_Padre($dni, $excluir_alumno = 0){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("SELECT p.*, CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS hijo
                              FROM padres p JOIN alumnos al ON al.Id_alumno = p.id_alu
                              WHERE (p.Dni_papa = ? OR p.Dni_mama = ?) AND p.id_alu <> ?
                              ORDER BY p.id_papas DESC");
            $q->execute([$dni, $dni, $excluir_alumno]);
            $filas = $q->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            if (!$filas) { return null; }
            $es_padre = $filas[0]['Dni_papa'] === $dni;
            [$yo, $el] = $es_padre ? ['papa', 'mama'] : ['mama', 'papa'];
            // Primer dato no vacío, del registro más reciente al más antiguo (un hermano pudo quedar sin celular)
            $dato = function ($campo, $filtro = null) use ($filas) {
                foreach ($filas as $f) {
                    if ($filtro && !$filtro($f)) { continue; }
                    if (trim((string) $f[$campo]) !== '') { return $f[$campo]; }
                }
                return '';
            };
            $dni_otro = $dato('Dni_' . $el);
            $mismo_otro = function ($f) use ($el, $dni_otro) { return $dni_otro === '' || $f['Dni_' . $el] === $dni_otro; };
            $otro = ['rol' => $es_padre ? 'MADRE' : 'PADRE', 'dni' => $dni_otro, 'nombre' => $dato('Datos_' . $el, $mismo_otro), 'celular' => $dato('Celular_' . $el, $mismo_otro)];
            return [
                'rol' => $es_padre ? 'PADRE' : 'MADRE',
                'nombre' => $dato('Datos_' . $yo),
                'celular' => $dato('Celular_' . $yo),
                'otro' => trim((string) $otro['nombre']) !== '' ? $otro : null,
                'hijos' => array_values(array_unique(array_column($filas, 'hijo'))),
            ];
        }
    }




?>