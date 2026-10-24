<?php
    require_once 'model_conexion.php';

    // Configuración regional del colegio: país, moneda, zona horaria y documento (Fase 3); escala de notas (Fase 8C)
    class Modelo_Regional extends conexionBD{

        public function Traer_Configuracion(){
            $c = conexionBD::conexionPDO();
            $sql = "SELECT e.empresa_id, e.pais_id, IFNULL(e.moneda_id, p.moneda_id) AS moneda_id,
                           IFNULL(e.zona_horaria, p.zona_horaria) AS zona_horaria,
                           IFNULL(e.identificacion_tributaria, '') AS identificacion_tributaria,
                           e.permite_doc_extranjero,
                           IFNULL(e.nota_min, p.nota_min) AS nota_min, IFNULL(e.nota_max, p.nota_max) AS nota_max,
                           IFNULL(e.nota_aprobatoria, p.nota_aprobatoria) AS nota_aprobatoria,
                           IFNULL(e.nota_decimales, p.nota_decimales) AS nota_decimales,
                           IFNULL(e.notas_literales, p.notas_literales) AS notas_literales,
                           IFNULL(e.literales_aprobatorios, p.literales_aprobatorios) AS literales_aprobatorios
                      FROM empresa e LEFT JOIN paises p ON p.id_pais = e.pais_id
                     ORDER BY e.empresa_id LIMIT 1";
            $fila = $c->query($sql)->fetch(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $fila;
        }
        public function Listar_Paises(){
            $c = conexionBD::conexionPDO();
            $sql = "SELECT p.id_pais, p.codigo_iso, p.nombre, p.locale, p.moneda_id, p.zona_horaria, p.prefijo_tel,
                           p.id_tributario, t.nombre AS doc_nombre, t.ejemplo AS doc_ejemplo,
                           p.nota_min, p.nota_max, p.nota_aprobatoria, p.nota_decimales, p.notas_literales, p.literales_aprobatorios
                      FROM paises p LEFT JOIN tipos_documento_identidad t ON t.pais_id = p.id_pais
                     ORDER BY p.nombre";
            $arreglo = $c->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        public function Listar_Monedas(){
            $c = conexionBD::conexionPDO();
            $sql = "SELECT id_moneda, codigo, nombre, simbolo, decimales, sep_miles, sep_decimal, simbolo_antes
                      FROM monedas WHERE estado = 'ACTIVO' ORDER BY nombre";
            $arreglo = $c->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
        public function Hay_Movimientos(){
            $c = conexionBD::conexionPDO();
            $n = (int) $c->query("SELECT (SELECT COUNT(*) FROM pago_pensiones WHERE estado = 'VALIDO')
                                       + (SELECT COUNT(*) FROM egresos WHERE estado = 'VALIDO')")->fetchColumn();
            conexionBD::cerrar_conexion();
            return $n > 0;
        }
        /**
         * Guarda la configuración y deja en el historial cada campo que cambió.
         * Devuelve ['ok'=>bool, 'mensaje'=>...].
         */
        public function Guardar_Configuracion($pais, $moneda, $zona, $id_trib, $extranjero, $escala, $usu){
            $c = conexionBD::conexionPDO();
            $q = $c->prepare("SELECT COUNT(*) FROM paises WHERE id_pais = ?");
            $q->execute([$pais]);
            $q2 = $c->prepare("SELECT COUNT(*) FROM monedas WHERE id_moneda = ? AND estado = 'ACTIVO'");
            $q2->execute([$moneda]);
            if (!$q->fetchColumn() || !$q2->fetchColumn()) {
                conexionBD::cerrar_conexion();
                return ['ok' => false, 'mensaje' => 'País o moneda no válidos'];
            }
            $campos_escala = "CONCAT(TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM e.nota_min)), ' a ', TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM e.nota_max)),
                                     ', aprueba ', TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM e.nota_aprobatoria)), ', ', e.nota_decimales, ' dec.',
                                     IF(IFNULL(e.notas_literales, '') = '', '', CONCAT(', ', e.notas_literales, ' (aprueban ', e.literales_aprobatorios, ')'))) AS escala_notas";
            $actual = $c->query("SELECT e.empresa_id, p.nombre AS pais, m.codigo AS moneda, e.zona_horaria,
                                        e.identificacion_tributaria, e.permite_doc_extranjero, $campos_escala
                                   FROM empresa e LEFT JOIN paises p ON p.id_pais = e.pais_id
                                   LEFT JOIN monedas m ON m.id_moneda = e.moneda_id
                                  ORDER BY e.empresa_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            try {
                $c->beginTransaction();
                $u = $c->prepare("UPDATE empresa SET pais_id = ?, moneda_id = ?, zona_horaria = ?,
                                         identificacion_tributaria = ?, permite_doc_extranjero = ?,
                                         nota_min = ?, nota_max = ?, nota_aprobatoria = ?, nota_decimales = ?,
                                         notas_literales = ?, literales_aprobatorios = ?, updated_at = NOW()
                                   WHERE empresa_id = ?");
                $u->execute([$pais, $moneda, $zona, $id_trib, $extranjero, $escala['min'], $escala['max'], $escala['aprobatoria'],
                             $escala['decimales'], $escala['literales'], $escala['aprobatorios'], $actual['empresa_id']]);
                $nuevo = $c->query("SELECT p.nombre AS pais, m.codigo AS moneda, e.zona_horaria,
                                           e.identificacion_tributaria, e.permite_doc_extranjero, $campos_escala
                                      FROM empresa e INNER JOIN paises p ON p.id_pais = e.pais_id
                                      INNER JOIN monedas m ON m.id_moneda = e.moneda_id
                                     WHERE e.empresa_id = " . (int) $actual['empresa_id'])->fetch(PDO::FETCH_ASSOC);
                $h = $c->prepare("INSERT INTO historial_configuracion(campo, valor_anterior, valor_nuevo, usuario_id, fecha)
                                  VALUES (?, ?, ?, ?, NOW())");
                foreach ($nuevo as $campo => $valor) {
                    if ((string) $actual[$campo] !== (string) $valor) {
                        $h->execute([$campo, $actual[$campo], $valor, $usu]);
                    }
                }
                $c->commit();
            } catch (PDOException $e) {
                if ($c->inTransaction()) { $c->rollBack(); }
                error_log('Guardar_Configuracion: ' . $e->getMessage());
                conexionBD::cerrar_conexion();
                return ['ok' => false, 'mensaje' => 'No se pudo guardar la configuración'];
            }
            conexionBD::cerrar_conexion();
            return ['ok' => true, 'mensaje' => 'Configuración guardada'];
        }
        public function Listar_Historial(){
            $c = conexionBD::conexionPDO();
            $sql = "SELECT h.campo, h.valor_anterior, h.valor_nuevo, u.usu_usuario,
                           DATE_FORMAT(h.fecha, '%d-%m-%Y %H:%i') AS fecha
                      FROM historial_configuracion h LEFT JOIN usuario u ON u.usu_id = h.usuario_id
                     ORDER BY h.id_historial DESC LIMIT 20";
            $arreglo = $c->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            conexionBD::cerrar_conexion();
            return $arreglo;
        }
    }
?>
