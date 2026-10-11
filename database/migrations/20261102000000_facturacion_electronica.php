<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Facturación electrónica con Nubefact (módulo opcional): cada institución emite, con su propia cuenta de
 * Nubefact, la boleta o factura de un ingreso (pensión, matrícula o ingreso diverso).
 *
 * - series_comprobantes: el último número usado de cada serie (B001, F001…). La numeración es local para
 *   reservarla antes de llamar a Nubefact; SP_FIJAR_CORRELATIVO la continúa si ya se emitía antes.
 * - comprobantes_electronicos: lo emitido y su estado:
 *     PENDIENTE  número reservado, sin respuesta de Nubefact (corte de red): se consulta
 *     ERROR      Nubefact lo rechazó (datos): se corrige y se reintenta con el mismo número
 *     EMITIDO    Nubefact lo generó; la SUNAT aún no lo acepta (las boletas van en el resumen diario)
 *     ACEPTADO   aceptado por la SUNAT
 *     ANULADO    comunicado de baja enviado
 * - La configuración (ruta, token cifrado, series, tipo de IGV) va en la tabla configuracion, claves facturacion.*.
 */
final class FacturacionElectronica extends AbstractMigration
{
    private const PROCEDIMIENTOS = ['SP_FIJAR_CORRELATIVO', 'SP_RESERVAR_COMPROBANTE', 'SP_RESULTADO_COMPROBANTE', 'SP_CORREGIR_COMPROBANTE', 'SP_ANULAR_COMPROBANTE'];

    public function up(): void
    {
        $this->execute("CREATE TABLE series_comprobantes (
            serie CHAR(4) NOT NULL PRIMARY KEY,
            ultimo INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $this->execute("CREATE TABLE comprobantes_electronicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_ingreso INT NOT NULL,
            tipo TINYINT NOT NULL COMMENT '1 factura, 2 boleta',
            serie CHAR(4) NOT NULL,
            numero INT NOT NULL,
            cliente_tipo_doc CHAR(1) NOT NULL COMMENT '1 DNI, 6 RUC, - varios',
            cliente_doc VARCHAR(15) NOT NULL,
            cliente_nombre VARCHAR(200) NOT NULL,
            cliente_direccion VARCHAR(250) NOT NULL DEFAULT '',
            cliente_email VARCHAR(250) NOT NULL DEFAULT '',
            descripcion VARCHAR(250) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            estado ENUM('PENDIENTE','ERROR','EMITIDO','ACEPTADO','ANULADO') NOT NULL DEFAULT 'PENDIENTE',
            enlace VARCHAR(500) NULL,
            enlace_pdf VARCHAR(500) NULL,
            enlace_xml VARCHAR(500) NULL,
            codigo_hash VARCHAR(100) NULL,
            sunat_descripcion VARCHAR(500) NULL,
            error VARCHAR(500) NULL,
            motivo_anulacion VARCHAR(100) NULL,
            usu_id INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_comprobante_numero (serie, numero),
            KEY ix_comprobante_ingreso (id_ingreso, estado),
            CONSTRAINT fk_comprobante_ingreso FOREIGN KEY (id_ingreso) REFERENCES ingresos (id_ingreso)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        foreach (self::PROCEDIMIENTOS as $sp) {
            $this->execute("DROP PROCEDURE IF EXISTS $sp");
        }
        // 1 fijado; 0 si el número no es mayor que el último usado (la numeración nunca retrocede).
        $this->execute("CREATE PROCEDURE SP_FIJAR_CORRELATIVO(IN SERIE CHAR(4), IN ULTIMO INT)
            BEGIN
                INSERT INTO series_comprobantes (serie, ultimo) VALUES (SERIE, 0) ON DUPLICATE KEY UPDATE serie = serie;
                UPDATE series_comprobantes s SET s.ultimo = ULTIMO WHERE s.serie = SERIE AND s.ultimo < ULTIMO;
                SELECT ROW_COUNT() > 0;
            END");
        // Devuelve (codigo, id): 1 reservado; 2 el ingreso ya tiene un comprobante vigente;
        // 0 el ingreso no existe, está anulado o es de monto 0.
        $this->execute("CREATE PROCEDURE SP_RESERVAR_COMPROBANTE(IN INGRESO INT, IN TIPO TINYINT, IN SERIE CHAR(4), IN DOC_TIPO CHAR(1),
                IN DOC VARCHAR(15), IN NOMBRE VARCHAR(200), IN DIRECCION VARCHAR(250), IN EMAIL VARCHAR(250), IN DESCRIPCION VARCHAR(250),
                IN USU INT)
            BEGIN
                DECLARE TOTAL_INGRESO DECIMAL(10,2);
                SET TOTAL_INGRESO = (SELECT i.monto FROM ingresos i WHERE i.id_ingreso = INGRESO AND i.estado = 'VALIDO' AND i.monto > 0);
                IF TOTAL_INGRESO IS NULL THEN
                    SELECT 0 AS codigo, 0 AS id;
                ELSEIF EXISTS (SELECT 1 FROM comprobantes_electronicos WHERE id_ingreso = INGRESO AND estado <> 'ANULADO') THEN
                    SELECT 2 AS codigo, 0 AS id;
                ELSE
                    INSERT INTO series_comprobantes (serie, ultimo) VALUES (SERIE, 0) ON DUPLICATE KEY UPDATE serie = serie;
                    UPDATE series_comprobantes s SET s.ultimo = LAST_INSERT_ID(s.ultimo + 1) WHERE s.serie = SERIE;
                    INSERT INTO comprobantes_electronicos (id_ingreso, tipo, serie, numero, cliente_tipo_doc, cliente_doc, cliente_nombre,
                            cliente_direccion, cliente_email, descripcion, total, usu_id)
                        VALUES (INGRESO, TIPO, SERIE, LAST_INSERT_ID(), DOC_TIPO, DOC, NOMBRE, IFNULL(DIRECCION, ''), IFNULL(EMAIL, ''),
                            DESCRIPCION, TOTAL_INGRESO, USU);
                    SELECT 1 AS codigo, LAST_INSERT_ID() AS id;
                END IF;
            END");
        $this->execute("CREATE PROCEDURE SP_RESULTADO_COMPROBANTE(IN ID INT, IN ESTADO VARCHAR(10), IN ENLACE VARCHAR(500), IN PDF VARCHAR(500),
                IN XML VARCHAR(500), IN HASH VARCHAR(100), IN SUNAT VARCHAR(500), IN ERROR VARCHAR(500))
            BEGIN
                UPDATE comprobantes_electronicos c
                   SET c.estado = ESTADO, c.enlace = IFNULL(ENLACE, c.enlace), c.enlace_pdf = IFNULL(PDF, c.enlace_pdf),
                       c.enlace_xml = IFNULL(XML, c.enlace_xml), c.codigo_hash = IFNULL(HASH, c.codigo_hash),
                       c.sunat_descripcion = IFNULL(SUNAT, c.sunat_descripcion), c.error = ERROR
                 WHERE c.id = ID AND c.estado <> 'ANULADO';
                SELECT ROW_COUNT() > 0;
            END");
        // Solo uno rechazado por Nubefact se corrige (mismo número): 1 corregido; 0 no procede.
        $this->execute("CREATE PROCEDURE SP_CORREGIR_COMPROBANTE(IN ID INT, IN DOC_TIPO CHAR(1), IN DOC VARCHAR(15), IN NOMBRE VARCHAR(200),
                IN DIRECCION VARCHAR(250), IN EMAIL VARCHAR(250))
            BEGIN
                UPDATE comprobantes_electronicos c
                   SET c.cliente_tipo_doc = DOC_TIPO, c.cliente_doc = DOC, c.cliente_nombre = NOMBRE,
                       c.cliente_direccion = IFNULL(DIRECCION, ''), c.cliente_email = IFNULL(EMAIL, ''), c.estado = 'PENDIENTE', c.error = NULL
                 WHERE c.id = ID AND c.estado = 'ERROR';
                SELECT ROW_COUNT() > 0;
            END");
        // Tras la baja aceptada por Nubefact: 1 anulado; 0 si no estaba emitido o aceptado.
        $this->execute("CREATE PROCEDURE SP_ANULAR_COMPROBANTE(IN ID INT, IN MOTIVO VARCHAR(100))
            BEGIN
                UPDATE comprobantes_electronicos c SET c.estado = 'ANULADO', c.motivo_anulacion = MOTIVO
                 WHERE c.id = ID AND c.estado IN ('EMITIDO', 'ACEPTADO');
                SELECT ROW_COUNT() > 0;
            END");
    }

    public function down(): void
    {
        foreach (self::PROCEDIMIENTOS as $sp) {
            $this->execute("DROP PROCEDURE IF EXISTS $sp");
        }
        $this->execute('DROP TABLE IF EXISTS comprobantes_electronicos');
        $this->execute('DROP TABLE IF EXISTS series_comprobantes');
    }
}
