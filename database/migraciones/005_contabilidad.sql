-- =====================================================================
-- Migración 005 – Fase 5: contabilidad (rol CONTADOR, movimientos de caja y bancos,
-- depósitos/transferencias, cierre de caja, conciliación, egresos e ingresos con cuenta)
-- Aplicar con: mysql --default-character-set=utf8mb4 colegio2 < 005_contabilidad.sql
-- =====================================================================

-- ---------- 1. Rol CONTADOR ----------
INSERT INTO roles (tipo_rol, descripcion, estado, created_at, updated_at)
SELECT 'CONTADOR', 'GESTIONA COBRANZAS, INGRESOS, EGRESOS, CAJAS, BANCOS Y REPORTES FINANCIEROS', 'ACTIVO', NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM roles WHERE tipo_rol = 'CONTADOR');

-- ---------- 2. Categorías contables por grupo (plan de cuentas simplificado) ----------
ALTER TABLE indicadores
  ADD COLUMN codigo   VARCHAR(10) NULL AFTER id_indicadores,
  ADD COLUMN padre_id INT NULL AFTER tipo_indicador,
  ADD COLUMN es_grupo TINYINT(1) NOT NULL DEFAULT 0 AFTER padre_id,
  ADD CONSTRAINT fk_indicador_padre FOREIGN KEY (padre_id) REFERENCES indicadores(id_indicadores);

INSERT INTO indicadores (codigo, tipo_indicador, nombre, descripcion, es_grupo, estado, created_at) VALUES
  ('4.1', 'INGRESOS', 'INGRESOS ACADÉMICOS', 'Pensiones, matrícula, admisión y moras', 1, 'ACTIVO', CURDATE()),
  ('4.2', 'INGRESOS', 'OTROS INGRESOS', 'Uniformes, eventos, certificados y otros', 1, 'ACTIVO', CURDATE()),
  ('5.1', 'GASTOS', 'REMUNERACIONES', 'Sueldos y honorarios del personal', 1, 'ACTIVO', CURDATE()),
  ('5.2', 'GASTOS', 'SERVICIOS BÁSICOS', 'Luz, agua, internet, telefonía', 1, 'ACTIVO', CURDATE()),
  ('5.3', 'GASTOS', 'ALQUILER Y MANTENIMIENTO', 'Local, reparaciones, limpieza', 1, 'ACTIVO', CURDATE()),
  ('5.4', 'GASTOS', 'MATERIALES Y SUMINISTROS', 'Útiles, material didáctico, oficina', 1, 'ACTIVO', CURDATE()),
  ('5.5', 'GASTOS', 'IMPUESTOS Y COMISIONES', 'Tributos y comisiones bancarias', 1, 'ACTIVO', CURDATE()),
  ('5.9', 'GASTOS', 'OTROS EGRESOS', 'Gastos no clasificados', 1, 'ACTIVO', CURDATE());

INSERT INTO indicadores (codigo, tipo_indicador, padre_id, nombre, descripcion, estado, created_at)
SELECT x.codigo, 'GASTOS', g.id_indicadores, x.nombre, x.descripcion, 'ACTIVO', CURDATE()
  FROM (SELECT '5.1.1' codigo, '5.1' grupo, 'REMUNERACIONES DOCENTES' nombre, 'Planilla de docentes' descripcion UNION ALL
        SELECT '5.1.2', '5.1', 'REMUNERACIONES ADMINISTRATIVAS', 'Planilla del personal administrativo' UNION ALL
        SELECT '5.5.1', '5.5', 'COMISIONES BANCARIAS', 'Comisiones de bancos y POS') x
  INNER JOIN indicadores g ON g.codigo = x.grupo AND g.es_grupo = 1;

-- Categorías existentes: pensiones -> académicos; el resto de ingresos -> otros; gastos -> servicios u otros
UPDATE indicadores i INNER JOIN indicadores g ON g.codigo = '4.1' SET i.padre_id = g.id_indicadores WHERE i.id_indicadores = 1;
UPDATE indicadores i INNER JOIN indicadores g ON g.codigo = '4.2' SET i.padre_id = g.id_indicadores
 WHERE i.tipo_indicador = 'INGRESOS' AND i.es_grupo = 0 AND i.padre_id IS NULL AND i.id_indicadores <> 1;
UPDATE indicadores i INNER JOIN indicadores g ON g.codigo = '5.2' SET i.padre_id = g.id_indicadores
 WHERE i.tipo_indicador = 'GASTOS' AND i.es_grupo = 0 AND i.padre_id IS NULL
   AND (i.nombre LIKE '%LUZ%' OR i.nombre LIKE '%AGUA%' OR i.nombre LIKE '%INTERNET%' OR i.nombre LIKE '%TELEF%');
UPDATE indicadores i INNER JOIN indicadores g ON g.codigo = '5.9' SET i.padre_id = g.id_indicadores
 WHERE i.tipo_indicador = 'GASTOS' AND i.es_grupo = 0 AND i.padre_id IS NULL;

-- ---------- 3. Libro de movimientos de cajas y cuentas ----------
CREATE TABLE IF NOT EXISTS movimientos_cuenta (
  id_movimiento      INT AUTO_INCREMENT PRIMARY KEY,
  cuenta_id          INT NOT NULL,
  fecha              DATETIME NOT NULL,
  tipo               ENUM('ENTRADA','SALIDA') NOT NULL,
  monto              DECIMAL(14,2) NOT NULL,
  origen             ENUM('PAGO','INGRESO','EGRESO','TRANSFERENCIA','AJUSTE','PLANILLA') NOT NULL,
  origen_id          INT NOT NULL,
  descripcion        VARCHAR(255) NULL,
  referencia         VARCHAR(60) NULL,
  usuario_id         INT NULL,
  estado             ENUM('VALIDO','ANULADO') NOT NULL DEFAULT 'VALIDO',
  conciliado         TINYINT(1) NOT NULL DEFAULT 0,
  fecha_conciliacion DATETIME NULL,
  created_at         DATETIME NOT NULL,
  INDEX idx_mov_cuenta_fecha (cuenta_id, fecha),
  INDEX idx_mov_origen (origen, origen_id),
  CONSTRAINT fk_mov_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Depósitos (caja -> banco), transferencias y retiros entre cuentas del colegio
CREATE TABLE IF NOT EXISTS transferencias (
  id_transferencia INT AUTO_INCREMENT PRIMARY KEY,
  tipo             ENUM('DEPOSITO','TRANSFERENCIA','RETIRO') NOT NULL,
  cuenta_origen    INT NOT NULL,
  cuenta_destino   INT NOT NULL,
  monto            DECIMAL(14,2) NOT NULL,
  fecha            DATETIME NOT NULL,
  referencia       VARCHAR(60) NULL,
  observacion      VARCHAR(255) NULL,
  usuario_id       INT NULL,
  estado           ENUM('VALIDO','ANULADO') NOT NULL DEFAULT 'VALIDO',
  motivo_anulacion VARCHAR(255) NULL,
  fecha_anulacion  DATETIME NULL,
  CONSTRAINT fk_tr_origen  FOREIGN KEY (cuenta_origen)  REFERENCES cuentas_financieras(id_cuenta),
  CONSTRAINT fk_tr_destino FOREIGN KEY (cuenta_destino) REFERENCES cuentas_financieras(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cierre de caja: lo esperado según el sistema contra lo contado
CREATE TABLE IF NOT EXISTS cierres_caja (
  id_cierre      INT AUTO_INCREMENT PRIMARY KEY,
  cuenta_id      INT NOT NULL,
  fecha          DATE NOT NULL,
  saldo_inicial  DECIMAL(14,2) NOT NULL,
  entradas       DECIMAL(14,2) NOT NULL,
  salidas        DECIMAL(14,2) NOT NULL,
  saldo_esperado DECIMAL(14,2) NOT NULL,
  saldo_contado  DECIMAL(14,2) NOT NULL,
  diferencia     DECIMAL(14,2) NOT NULL,
  ajustado       TINYINT(1) NOT NULL DEFAULT 0,
  observacion    VARCHAR(255) NULL,
  usuario_id     INT NULL,
  created_at     DATETIME NOT NULL,
  UNIQUE KEY uk_cierre (cuenta_id, fecha),
  CONSTRAINT fk_cierre_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 4. Egresos e ingresos manuales: forma de pago, cuenta y comprobante ----------
ALTER TABLE egresos
  ADD COLUMN metodo_pago_id  INT NULL AFTER observacion,
  ADD COLUMN cuenta_id       INT NULL AFTER metodo_pago_id,
  ADD COLUMN referencia      VARCHAR(60)  NULL AFTER cuenta_id,
  ADD COLUMN beneficiario    VARCHAR(150) NULL AFTER referencia,
  ADD COLUMN nro_comprobante VARCHAR(30)  NULL AFTER beneficiario,
  ADD COLUMN adjunto         VARCHAR(255) NULL AFTER nro_comprobante,
  ADD CONSTRAINT fk_egreso_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo),
  ADD CONSTRAINT fk_egreso_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta);

ALTER TABLE ingresos
  ADD COLUMN metodo_pago_id INT NULL AFTER observacion,
  ADD COLUMN cuenta_id      INT NULL AFTER metodo_pago_id,
  ADD COLUMN referencia     VARCHAR(60) NULL AFTER cuenta_id,
  ADD CONSTRAINT fk_ingreso_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo),
  ADD CONSTRAINT fk_ingreso_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta);

-- Cobros ya registrados con caja/cuenta (Fase 4) pasan al libro de movimientos
INSERT INTO movimientos_cuenta (cuenta_id, fecha, tipo, monto, origen, origen_id, descripcion, referencia, usuario_id, estado, created_at)
SELECT pm.cuenta_id, p.fecha, 'ENTRADA', pm.monto, 'PAGO', pm.id_pago_metodo,
       CONCAT('Recibo ', p.serie, '-', LPAD(p.numero, 6, '0')), pm.referencia, p.usuario_id, p.estado, NOW()
  FROM pagos_metodos pm INNER JOIN pagos p ON p.id_pago = pm.id_pago
 WHERE pm.cuenta_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM movimientos_cuenta m WHERE m.origen = 'PAGO' AND m.origen_id = pm.id_pago_metodo);

-- ---------- 5. Saldos y movimientos ----------
-- Saldo de una cuenta hasta una fecha: saldo inicial + movimientos válidos desde la fecha del saldo inicial
DROP PROCEDURE IF EXISTS SP_SALDO_CUENTA;
DELIMITER $$
CREATE PROCEDURE SP_SALDO_CUENTA(IN P_CUENTA INT, IN P_HASTA DATETIME, OUT O_SALDO DECIMAL(14,2))
BEGIN
  SELECT c.saldo_inicial + IFNULL((SELECT SUM(IF(m.tipo = 'ENTRADA', m.monto, -m.monto)) FROM movimientos_cuenta m
                                    WHERE m.cuenta_id = c.id_cuenta AND m.estado = 'VALIDO' AND m.fecha < P_HASTA
                                      AND DATE(m.fecha) >= IFNULL(c.fecha_saldo_inicial, '1000-01-01')), 0)
    INTO O_SALDO
    FROM cuentas_financieras c WHERE c.id_cuenta = P_CUENTA;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_SALDOS_CUENTAS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_SALDOS_CUENTAS()
BEGIN
  SELECT c.id_cuenta, c.tipo, c.nombre, c.banco, c.numero_cuenta, c.saldo_inicial, c.fecha_saldo_inicial, c.estado,
         c.saldo_inicial + IFNULL(SUM(IF(m.tipo = 'ENTRADA', m.monto, -m.monto)), 0) AS saldo,
         IFNULL(SUM(IF(m.tipo = 'ENTRADA' AND DATE(m.fecha) = CURDATE(), m.monto, 0)), 0) AS entradas_hoy,
         IFNULL(SUM(IF(m.tipo = 'SALIDA' AND DATE(m.fecha) = CURDATE(), m.monto, 0)), 0) AS salidas_hoy,
         IFNULL(SUM(IF(m.conciliado = 0 AND m.id_movimiento IS NOT NULL, 1, 0)), 0) AS sin_conciliar,
         (SELECT MAX(ci.fecha) FROM cierres_caja ci WHERE ci.cuenta_id = c.id_cuenta) AS ultimo_cierre
    FROM cuentas_financieras c
    LEFT JOIN movimientos_cuenta m ON m.cuenta_id = c.id_cuenta AND m.estado = 'VALIDO'
                                  AND DATE(m.fecha) >= IFNULL(c.fecha_saldo_inicial, '1000-01-01')
   GROUP BY c.id_cuenta
   ORDER BY c.estado, FIELD(c.tipo, 'CAJA', 'BANCO', 'BILLETERA'), c.nombre;
END$$
DELIMITER ;

-- Movimientos de una cuenta entre dos fechas, con el saldo anterior al período (el acumulado lo calcula PHP)
DROP PROCEDURE IF EXISTS SP_LISTAR_MOVIMIENTOS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_MOVIMIENTOS(IN P_CUENTA INT, IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  DECLARE V_SALDO DECIMAL(14,2);
  CALL SP_SALDO_CUENTA(P_CUENTA, P_DESDE, V_SALDO);
  SELECT m.id_movimiento, m.fecha, DATE_FORMAT(m.fecha, '%d-%m-%Y %H:%i') AS fecha_txt, m.tipo, m.monto, m.origen, m.origen_id,
         m.descripcion, m.referencia, m.estado, m.conciliado, u.usu_usuario, V_SALDO AS saldo_anterior
    FROM movimientos_cuenta m LEFT JOIN usuario u ON u.usu_id = m.usuario_id
   WHERE m.cuenta_id = P_CUENTA AND DATE(m.fecha) BETWEEN P_DESDE AND P_HASTA
   ORDER BY m.fecha, m.id_movimiento;
END$$
DELIMITER ;

-- Crea, actualiza o anula el movimiento de una operación (pago, ingreso, egreso...). Sin cuenta: lo anula.
DROP PROCEDURE IF EXISTS SP_SINCRONIZAR_MOVIMIENTO;
DELIMITER $$
CREATE PROCEDURE SP_SINCRONIZAR_MOVIMIENTO(IN P_ORIGEN VARCHAR(20), IN P_ORIGEN_ID INT, IN P_CUENTA INT, IN P_TIPO VARCHAR(10),
                                           IN P_MONTO DECIMAL(14,2), IN P_FECHA DATETIME, IN P_DESC VARCHAR(255),
                                           IN P_REF VARCHAR(60), IN P_USU INT)
BEGIN
  DECLARE V_ID INT;
  SELECT MAX(id_movimiento) INTO V_ID FROM movimientos_cuenta
   WHERE origen = P_ORIGEN AND origen_id = P_ORIGEN_ID AND estado = 'VALIDO';
  IF P_CUENTA IS NULL OR IFNULL(P_MONTO, 0) <= 0 THEN
    UPDATE movimientos_cuenta SET estado = 'ANULADO' WHERE id_movimiento = V_ID;
  ELSEIF V_ID IS NULL THEN
    INSERT INTO movimientos_cuenta(cuenta_id, fecha, tipo, monto, origen, origen_id, descripcion, referencia, usuario_id, estado, created_at)
    VALUES (P_CUENTA, P_FECHA, P_TIPO, P_MONTO, P_ORIGEN, P_ORIGEN_ID, P_DESC, NULLIF(TRIM(P_REF), ''), P_USU, 'VALIDO', NOW());
  ELSE
    UPDATE movimientos_cuenta SET cuenta_id = P_CUENTA, fecha = P_FECHA, tipo = P_TIPO, monto = P_MONTO,
           descripcion = P_DESC, referencia = NULLIF(TRIM(P_REF), ''), conciliado = IF(cuenta_id = P_CUENTA AND monto = P_MONTO, conciliado, 0)
     WHERE id_movimiento = V_ID;
  END IF;
END$$
DELIMITER ;

-- ¿Alcanza el efectivo? Solo se controla en cajas (en bancos puede haber sobregiro o depósitos no registrados).
-- P_EXCLUIR_*: al modificar, el movimiento anterior de la misma operación no cuenta.
DROP PROCEDURE IF EXISTS SP_VALIDAR_SALIDA;
DELIMITER $$
CREATE PROCEDURE SP_VALIDAR_SALIDA(IN P_CUENTA INT, IN P_MONTO DECIMAL(14,2), IN P_EXCLUIR_ORIGEN VARCHAR(20), IN P_EXCLUIR_ID INT,
                                   OUT O_OK TINYINT, OUT O_SALDO DECIMAL(14,2))
BEGIN
  DECLARE V_TIPO VARCHAR(10);
  DECLARE V_PREVIO DECIMAL(14,2);
  SELECT tipo INTO V_TIPO FROM cuentas_financieras WHERE id_cuenta = P_CUENTA;
  CALL SP_SALDO_CUENTA(P_CUENTA, '9999-12-31', O_SALDO);
  SELECT IFNULL(SUM(IF(tipo = 'SALIDA', monto, -monto)), 0) INTO V_PREVIO FROM movimientos_cuenta
   WHERE origen = P_EXCLUIR_ORIGEN AND origen_id = P_EXCLUIR_ID AND cuenta_id = P_CUENTA AND estado = 'VALIDO';
  SET O_SALDO = O_SALDO + V_PREVIO;
  SET O_OK = (V_TIPO <> 'CAJA' OR O_SALDO - P_MONTO >= 0);
END$$
DELIMITER ;

-- Valida método + cuenta + referencia. O_CUENTA queda NULL si el método no usa cuenta.
DROP PROCEDURE IF EXISTS SP_VALIDAR_FORMA_PAGO;
DELIMITER $$
CREATE PROCEDURE SP_VALIDAR_FORMA_PAGO(IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60),
                                       OUT O_MENSAJE VARCHAR(255), OUT O_CUENTA INT)
BEGIN
  DECLARE V_NOMBRE VARCHAR(60);
  DECLARE V_REQ_REF, V_REQ_CTA TINYINT;
  SET O_MENSAJE = NULL;
  SELECT nombre, requiere_referencia, requiere_cuenta INTO V_NOMBRE, V_REQ_REF, V_REQ_CTA
    FROM metodos_pago WHERE id_metodo = P_METODO AND estado = 'ACTIVO';
  IF P_CUENTA IS NOT NULL THEN
    SELECT MAX(id_cuenta) INTO O_CUENTA FROM cuentas_financieras WHERE id_cuenta = P_CUENTA AND estado = 'ACTIVO';
  END IF;
  IF V_NOMBRE IS NULL THEN
    SET O_MENSAJE = 'Forma de pago no válida';
  ELSEIF V_REQ_REF = 1 AND TRIM(IFNULL(P_REF, '')) = '' THEN
    SET O_MENSAJE = CONCAT(V_NOMBRE, ': indique el n.° de operación o referencia');
  ELSEIF V_REQ_CTA = 1 AND O_CUENTA IS NULL THEN
    SET O_MENSAJE = CONCAT(V_NOMBRE, ': seleccione la caja o cuenta');
  END IF;
END$$
DELIMITER ;

-- ---------- 6. Recibos: cada forma de pago es una entrada en su caja/cuenta ----------
DROP PROCEDURE IF EXISTS SP_AGREGAR_METODO_RECIBO;
DELIMITER $$
CREATE PROCEDURE SP_AGREGAR_METODO_RECIBO(IN P_PAGO INT, IN P_METODO INT, IN P_CUENTA INT, IN P_MONTO DECIMAL(14,2), IN P_REF VARCHAR(60))
proc: BEGIN
  DECLARE V_MENSAJE VARCHAR(255);
  DECLARE V_CTA, V_ID INT;
  DECLARE V_COMISION DECIMAL(5,2);
  DECLARE V_RECIBO VARCHAR(20);
  DECLARE V_USU INT;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN
    SELECT 0 AS codigo, 'Monto no válido en la forma de pago' AS mensaje; LEAVE proc;
  END IF;
  CALL SP_VALIDAR_FORMA_PAGO(P_METODO, P_CUENTA, P_REF, V_MENSAJE, V_CTA);
  IF V_MENSAJE IS NOT NULL THEN
    SELECT 0 AS codigo, V_MENSAJE AS mensaje; LEAVE proc;
  END IF;
  SELECT comision_pct INTO V_COMISION FROM metodos_pago WHERE id_metodo = P_METODO;
  INSERT INTO pagos_metodos(id_pago, metodo_pago_id, cuenta_id, monto, comision, referencia, created_at)
  VALUES (P_PAGO, P_METODO, V_CTA, P_MONTO, ROUND(P_MONTO * V_COMISION / 100, 2), NULLIF(TRIM(P_REF), ''), NOW());
  SET V_ID = LAST_INSERT_ID();
  SELECT CONCAT(serie, '-', LPAD(numero, 6, '0')), usuario_id INTO V_RECIBO, V_USU FROM pagos WHERE id_pago = P_PAGO;
  CALL SP_SINCRONIZAR_MOVIMIENTO('PAGO', V_ID, V_CTA, 'ENTRADA', P_MONTO, NOW(), CONCAT('Recibo ', V_RECIBO), P_REF, V_USU);
  SELECT 1 AS codigo, 'ok' AS mensaje;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_AJUSTAR_RECIBO;
DELIMITER $$
CREATE PROCEDURE SP_AJUSTAR_RECIBO(IN P_PAGO INT, OUT O_OK TINYINT)
BEGIN
  DECLARE V_TOTAL DECIMAL(14,2);
  DECLARE V_N INT;
  SELECT COUNT(*) INTO V_N FROM pagos_metodos WHERE id_pago = P_PAGO;
  SELECT IFNULL(SUM(sub_total), 0) INTO V_TOTAL FROM pago_pensiones WHERE id_pago = P_PAGO AND estado = 'VALIDO';
  IF V_N > 1 THEN
    SET O_OK = 0;
  ELSE
    UPDATE pagos_metodos SET monto = V_TOTAL WHERE id_pago = P_PAGO;
    UPDATE pagos SET total = V_TOTAL WHERE id_pago = P_PAGO;
    UPDATE movimientos_cuenta m INNER JOIN pagos_metodos pm ON m.origen = 'PAGO' AND m.origen_id = pm.id_pago_metodo
       SET m.monto = V_TOTAL, m.conciliado = 0
     WHERE pm.id_pago = P_PAGO AND m.estado = 'VALIDO';
    SET O_OK = 1;
  END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_ANULAR_RECIBO;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_RECIBO(IN P_PAGO INT, IN P_MOTIVO VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_EXISTE INT;
  IF TRIM(IFNULL(P_MOTIVO, '')) = '' THEN
    SELECT 0 AS codigo, 'Indique el motivo de la anulación' AS mensaje; LEAVE proc;
  END IF;
  SELECT COUNT(*) INTO V_EXISTE FROM pagos WHERE id_pago = P_PAGO AND estado = 'VALIDO';
  IF V_EXISTE = 0 THEN
    SELECT 2 AS codigo, 'El recibo no existe o ya fue anulado' AS mensaje; LEAVE proc;
  END IF;
  UPDATE ingresos i INNER JOIN pago_pensiones p ON p.id_pago_pension = i.id_pago_pension
     SET i.estado = 'ANULADO', i.motivo_anulacion = P_MOTIVO, i.fecha_anulacion = CURDATE(), i.updated = NOW()
   WHERE p.id_pago = P_PAGO AND i.estado = 'VALIDO';
  UPDATE pago_pensiones
     SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = NOW(), id_usuario_anulacion = P_USU, updated_at = NOW()
   WHERE id_pago = P_PAGO AND estado = 'VALIDO';
  UPDATE movimientos_cuenta m INNER JOIN pagos_metodos pm ON m.origen = 'PAGO' AND m.origen_id = pm.id_pago_metodo
     SET m.estado = 'ANULADO'
   WHERE pm.id_pago = P_PAGO;
  UPDATE pagos SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = NOW(), usuario_anulacion = P_USU
   WHERE id_pago = P_PAGO;
  SELECT 1 AS codigo, 'Recibo anulado' AS mensaje;
END$$
DELIMITER ;

-- ---------- 7. Egresos ----------
-- Devuelven codigo (1 ok, 0 dato inválido, 2 no existe/anulado, 3 saldo de caja insuficiente), mensaje e id.
DROP PROCEDURE IF EXISTS SP_REGISTRAR_EGRESOS;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_EGRESOS(IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(14,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT,
                                      IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60),
                                      IN P_BENEF VARCHAR(150), IN P_COMPROB VARCHAR(30), IN P_ADJUNTO VARCHAR(255))
proc: BEGIN
  DECLARE V_IND, V_CTA, V_ID INT;
  DECLARE V_MENSAJE VARCHAR(255);
  DECLARE V_OK TINYINT;
  DECLARE V_SALDO DECIMAL(14,2);
  DECLARE V_NOMBRE VARCHAR(255);
  SELECT id_indicadores, nombre INTO V_IND, V_NOMBRE FROM indicadores
   WHERE id_indicadores = INDI AND tipo_indicador = 'GASTOS' AND es_grupo = 0 AND estado = 'ACTIVO';
  IF V_IND IS NULL THEN SELECT 0 AS codigo, 'Seleccione una categoría de egreso válida' AS mensaje, 0 AS id; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje, 0 AS id; LEAVE proc; END IF;
  CALL SP_VALIDAR_FORMA_PAGO(P_METODO, P_CUENTA, P_REF, V_MENSAJE, V_CTA);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje, 0 AS id; LEAVE proc; END IF;
  IF V_CTA IS NOT NULL THEN
    CALL SP_VALIDAR_SALIDA(V_CTA, MONTO, 'EGRESO', 0, V_OK, V_SALDO);
    IF V_OK = 0 THEN
      SELECT 3 AS codigo, CONCAT('Saldo insuficiente en la caja (saldo actual ', V_SALDO, ')') AS mensaje, 0 AS id; LEAVE proc;
    END IF;
  END IF;
  INSERT INTO egresos (id_indicador, id_user, cantidad, monto, observacion, metodo_pago_id, cuenta_id, referencia, beneficiario,
                       nro_comprobante, adjunto, estado, created_at, updated)
  VALUES (INDI, USU, CANTIDAD, MONTO, OBSERVA, P_METODO, V_CTA, NULLIF(TRIM(P_REF), ''), NULLIF(TRIM(P_BENEF), ''),
          NULLIF(TRIM(P_COMPROB), ''), P_ADJUNTO, 'VALIDO', P_FECHA, NULL);
  SET V_ID = LAST_INSERT_ID();
  CALL SP_SINCRONIZAR_MOVIMIENTO('EGRESO', V_ID, V_CTA, 'SALIDA', MONTO, TIMESTAMP(P_FECHA, CURTIME()),
                                 CONCAT('Egreso: ', V_NOMBRE, IF(TRIM(IFNULL(P_BENEF, '')) <> '', CONCAT(' - ', P_BENEF), '')), P_REF, USU);
  SELECT 1 AS codigo, 'Egreso registrado' AS mensaje, V_ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_MODIFICAR_EGRESOS;
DELIMITER $$
CREATE PROCEDURE SP_MODIFICAR_EGRESOS(IN `ID` INT, IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(14,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT,
                                      IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60),
                                      IN P_BENEF VARCHAR(150), IN P_COMPROB VARCHAR(30))
proc: BEGIN
  DECLARE V_IND, V_CTA, V_EXISTE INT;
  DECLARE V_MENSAJE VARCHAR(255);
  DECLARE V_OK TINYINT;
  DECLARE V_SALDO DECIMAL(14,2);
  DECLARE V_NOMBRE VARCHAR(255);
  SELECT COUNT(*) INTO V_EXISTE FROM egresos WHERE id_egresos = ID AND estado = 'VALIDO';
  IF V_EXISTE = 0 THEN SELECT 2 AS codigo, 'El egreso no existe o está anulado' AS mensaje, ID AS id; LEAVE proc; END IF;
  SELECT id_indicadores, nombre INTO V_IND, V_NOMBRE FROM indicadores
   WHERE id_indicadores = INDI AND tipo_indicador = 'GASTOS' AND es_grupo = 0 AND estado = 'ACTIVO';
  IF V_IND IS NULL THEN SELECT 0 AS codigo, 'Seleccione una categoría de egreso válida' AS mensaje, ID AS id; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje, ID AS id; LEAVE proc; END IF;
  CALL SP_VALIDAR_FORMA_PAGO(P_METODO, P_CUENTA, P_REF, V_MENSAJE, V_CTA);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje, ID AS id; LEAVE proc; END IF;
  IF V_CTA IS NOT NULL THEN
    CALL SP_VALIDAR_SALIDA(V_CTA, MONTO, 'EGRESO', ID, V_OK, V_SALDO);
    IF V_OK = 0 THEN
      SELECT 3 AS codigo, CONCAT('Saldo insuficiente en la caja (saldo disponible ', V_SALDO, ')') AS mensaje, ID AS id; LEAVE proc;
    END IF;
  END IF;
  UPDATE egresos SET id_indicador = INDI, id_user = USU, cantidad = CANTIDAD, monto = MONTO, observacion = OBSERVA,
         metodo_pago_id = P_METODO, cuenta_id = V_CTA, referencia = NULLIF(TRIM(P_REF), ''), beneficiario = NULLIF(TRIM(P_BENEF), ''),
         nro_comprobante = NULLIF(TRIM(P_COMPROB), ''), created_at = P_FECHA, updated = NOW()
   WHERE id_egresos = ID;
  CALL SP_SINCRONIZAR_MOVIMIENTO('EGRESO', ID, V_CTA, 'SALIDA', MONTO, TIMESTAMP(P_FECHA, CURTIME()),
                                 CONCAT('Egreso: ', V_NOMBRE, IF(TRIM(IFNULL(P_BENEF, '')) <> '', CONCAT(' - ', P_BENEF), '')), P_REF, USU);
  SELECT 1 AS codigo, 'Egreso modificado' AS mensaje, ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_ANULAR_EGRESOS;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_EGRESOS(IN `ID` INT, IN `OBSERVA` VARCHAR(255), IN `USU` INT)
BEGIN
  UPDATE egresos SET motivo_anulacion = OBSERVA, fecha_anulacion = CURDATE(), estado = 'ANULADO', updated = NOW()
   WHERE id_egresos = ID AND estado = 'VALIDO';
  UPDATE movimientos_cuenta SET estado = 'ANULADO' WHERE origen = 'EGRESO' AND origen_id = ID;
END$$
DELIMITER ;

-- ---------- 8. Ingresos manuales (no de pensiones) ----------
DROP PROCEDURE IF EXISTS SP_REGISTRAR_INGRESOS;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_INGRESOS(IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(14,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT,
                                       IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60))
proc: BEGIN
  DECLARE V_IND, V_CTA, V_ID INT;
  DECLARE V_MENSAJE VARCHAR(255);
  DECLARE V_NOMBRE VARCHAR(255);
  SELECT id_indicadores, nombre INTO V_IND, V_NOMBRE FROM indicadores
   WHERE id_indicadores = INDI AND tipo_indicador = 'INGRESOS' AND es_grupo = 0 AND estado = 'ACTIVO' AND id_indicadores <> 1;
  IF V_IND IS NULL THEN SELECT 0 AS codigo, 'Seleccione una categoría de ingreso válida' AS mensaje, 0 AS id; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje, 0 AS id; LEAVE proc; END IF;
  CALL SP_VALIDAR_FORMA_PAGO(P_METODO, P_CUENTA, P_REF, V_MENSAJE, V_CTA);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje, 0 AS id; LEAVE proc; END IF;
  INSERT INTO ingresos (id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, metodo_pago_id, cuenta_id, referencia,
                        estado, created_at, updated)
  VALUES (NULL, INDI, USU, CANTIDAD, MONTO, OBSERVA, P_METODO, V_CTA, NULLIF(TRIM(P_REF), ''), 'VALIDO', P_FECHA, NULL);
  SET V_ID = LAST_INSERT_ID();
  CALL SP_SINCRONIZAR_MOVIMIENTO('INGRESO', V_ID, V_CTA, 'ENTRADA', MONTO, TIMESTAMP(P_FECHA, CURTIME()), CONCAT('Ingreso: ', V_NOMBRE), P_REF, USU);
  SELECT 1 AS codigo, 'Ingreso registrado' AS mensaje, V_ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_MODIFICAR_INGRESOS;
DELIMITER $$
CREATE PROCEDURE SP_MODIFICAR_INGRESOS(IN `ID` INT, IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(14,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT,
                                       IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60))
proc: BEGIN
  DECLARE V_IND, V_CTA, V_EXISTE INT;
  DECLARE V_MENSAJE VARCHAR(255);
  DECLARE V_NOMBRE VARCHAR(255);
  -- Los ingresos de pensiones/matrícula se modifican desde Pago de pensiones
  SELECT COUNT(*) INTO V_EXISTE FROM ingresos WHERE id_ingreso = ID AND estado = 'VALIDO' AND id_pago_pension IS NULL;
  IF V_EXISTE = 0 THEN SELECT 2 AS codigo, 'El ingreso no existe, está anulado o es de pensiones' AS mensaje, ID AS id; LEAVE proc; END IF;
  SELECT id_indicadores, nombre INTO V_IND, V_NOMBRE FROM indicadores
   WHERE id_indicadores = INDI AND tipo_indicador = 'INGRESOS' AND es_grupo = 0 AND estado = 'ACTIVO' AND id_indicadores <> 1;
  IF V_IND IS NULL THEN SELECT 0 AS codigo, 'Seleccione una categoría de ingreso válida' AS mensaje, ID AS id; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje, ID AS id; LEAVE proc; END IF;
  CALL SP_VALIDAR_FORMA_PAGO(P_METODO, P_CUENTA, P_REF, V_MENSAJE, V_CTA);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje, ID AS id; LEAVE proc; END IF;
  UPDATE ingresos SET id_indicador = INDI, id_user = USU, cantidad = CANTIDAD, monto = MONTO, observacion = OBSERVA,
         metodo_pago_id = P_METODO, cuenta_id = V_CTA, referencia = NULLIF(TRIM(P_REF), ''), created_at = P_FECHA, updated = NOW()
   WHERE id_ingreso = ID;
  CALL SP_SINCRONIZAR_MOVIMIENTO('INGRESO', ID, V_CTA, 'ENTRADA', MONTO, TIMESTAMP(P_FECHA, CURTIME()), CONCAT('Ingreso: ', V_NOMBRE), P_REF, USU);
  SELECT 1 AS codigo, 'Ingreso modificado' AS mensaje, ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_ANULAR_INGRESOS;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_INGRESOS(IN `ID` INT, IN `OBSERVA` VARCHAR(255), IN `USU` INT)
BEGIN
  UPDATE ingresos SET motivo_anulacion = OBSERVA, fecha_anulacion = CURDATE(), estado = 'ANULADO', updated = NOW()
   WHERE id_ingreso = ID AND id_pago_pension IS NULL AND estado = 'VALIDO';
  UPDATE movimientos_cuenta m INNER JOIN ingresos i ON i.id_ingreso = m.origen_id
     SET m.estado = 'ANULADO'
   WHERE m.origen = 'INGRESO' AND m.origen_id = ID AND i.estado = 'ANULADO';
END$$
DELIMITER ;

-- ---------- 9. Listados de ingresos/egresos y categorías ----------
-- Filtro: categoría (0 = todas) Y rango de fechas (antes era categoría O fechas)
DROP PROCEDURE IF EXISTS SP_LISTAR_EGRESOS_DIVERSOS_FILTRO;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_EGRESOS_DIVERSOS_FILTRO(IN `INDI` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)
BEGIN
  SELECT e.id_egresos, e.id_indicador, e.id_user, e.cantidad, e.monto, e.observacion, e.estado, e.motivo_anulacion,
         e.fecha_anulacion, e.created_at, e.updated, i.nombre, DATE_FORMAT(e.created_at, "%d-%m-%Y") AS fecha_formateada,
         e.metodo_pago_id, e.cuenta_id, e.referencia, e.beneficiario, e.nro_comprobante, e.adjunto,
         IFNULL(mp.nombre, '') AS metodo, IFNULL(c.nombre, '') AS cuenta, IFNULL(g.nombre, '') AS grupo
    FROM egresos e
    INNER JOIN indicadores i ON e.id_indicador = i.id_indicadores
    LEFT JOIN indicadores g ON g.id_indicadores = i.padre_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = e.metodo_pago_id
    LEFT JOIN cuentas_financieras c ON c.id_cuenta = e.cuenta_id
   WHERE (IFNULL(INDI, 0) = 0 OR e.id_indicador = INDI)
     AND e.created_at BETWEEN IFNULL(FECHAINI, '1000-01-01') AND IFNULL(FECHAFIN, '9999-12-31')
   ORDER BY e.created_at DESC, e.id_egresos DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_EGRESOS_DIVERSOS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_EGRESOS_DIVERSOS()
BEGIN
  CALL SP_LISTAR_EGRESOS_DIVERSOS_FILTRO(0, CURDATE(), CURDATE());
END$$
DELIMITER ;

-- Ingresos "diversos" = los registrados a mano (antes mostraba también los de pensiones del día)
DROP PROCEDURE IF EXISTS SP_LISTAR_INGRESOS_DIVERSOS_FILTRO;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_INGRESOS_DIVERSOS_FILTRO(IN `INDI` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)
BEGIN
  SELECT x.id_ingreso, x.id_pago_pension, x.id_indicador, x.id_user, x.cantidad, x.monto, x.observacion, x.estado,
         x.motivo_anulacion, x.fecha_anulacion, x.created_at, x.updated, i.nombre, DATE_FORMAT(x.created_at, "%d-%m-%Y") AS fecha_formateada,
         x.metodo_pago_id, x.cuenta_id, x.referencia, IFNULL(mp.nombre, '') AS metodo, IFNULL(c.nombre, '') AS cuenta,
         IFNULL(g.nombre, '') AS grupo
    FROM ingresos x
    INNER JOIN indicadores i ON x.id_indicador = i.id_indicadores
    LEFT JOIN indicadores g ON g.id_indicadores = i.padre_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = x.metodo_pago_id
    LEFT JOIN cuentas_financieras c ON c.id_cuenta = x.cuenta_id
   WHERE x.id_pago_pension IS NULL AND x.id_indicador <> 1
     AND (IFNULL(INDI, 0) = 0 OR x.id_indicador = INDI)
     AND x.created_at BETWEEN IFNULL(FECHAINI, '1000-01-01') AND IFNULL(FECHAFIN, '9999-12-31')
   ORDER BY x.created_at DESC, x.id_ingreso DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_INGRESOS_DIVERSOS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_INGRESOS_DIVERSOS()
BEGIN
  CALL SP_LISTAR_INGRESOS_DIVERSOS_FILTRO(0, CURDATE(), CURDATE());
END$$
DELIMITER ;

-- Selectores de categoría: solo categorías activas (no los grupos), con su grupo en el texto
DROP PROCEDURE IF EXISTS SP_CARGAR_SELECT_INDICADORES_GASTOS;
DELIMITER $$
CREATE PROCEDURE SP_CARGAR_SELECT_INDICADORES_GASTOS()
BEGIN
  SELECT i.id_indicadores, IF(g.nombre IS NULL, i.nombre, CONCAT(g.nombre, ' › ', i.nombre)) AS nombre
    FROM indicadores i LEFT JOIN indicadores g ON g.id_indicadores = i.padre_id
   WHERE i.tipo_indicador = 'GASTOS' AND i.es_grupo = 0 AND i.estado = 'ACTIVO'
   ORDER BY IFNULL(g.codigo, '9'), i.nombre;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_CARGAR_SELECT_INDICADORES;
DELIMITER $$
CREATE PROCEDURE SP_CARGAR_SELECT_INDICADORES()
BEGIN
  SELECT i.id_indicadores, IF(g.nombre IS NULL, i.nombre, CONCAT(g.nombre, ' › ', i.nombre)) AS nombre
    FROM indicadores i LEFT JOIN indicadores g ON g.id_indicadores = i.padre_id
   WHERE i.tipo_indicador = 'INGRESOS' AND i.es_grupo = 0 AND i.estado = 'ACTIVO' AND i.id_indicadores <> 1
   ORDER BY IFNULL(g.codigo, '9'), i.nombre;
END$$
DELIMITER ;

-- ---------- 10. Depósitos y transferencias entre cuentas ----------
DROP PROCEDURE IF EXISTS SP_REGISTRAR_TRANSFERENCIA;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_TRANSFERENCIA(IN P_TIPO VARCHAR(20), IN P_ORIGEN INT, IN P_DESTINO INT, IN P_MONTO DECIMAL(14,2),
                                            IN P_FECHA DATE, IN P_REF VARCHAR(60), IN P_OBS VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_ORIGEN, V_DESTINO VARCHAR(100);
  DECLARE V_ID INT;
  DECLARE V_OK TINYINT;
  DECLARE V_SALDO DECIMAL(14,2);
  DECLARE V_FECHA DATETIME;
  IF P_TIPO NOT IN ('DEPOSITO', 'TRANSFERENCIA', 'RETIRO') THEN SELECT 0 AS codigo, 'Tipo no válido' AS mensaje, 0 AS id; LEAVE proc; END IF;
  IF P_ORIGEN = P_DESTINO THEN SELECT 0 AS codigo, 'La cuenta de origen y la de destino deben ser distintas' AS mensaje, 0 AS id; LEAVE proc; END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje, 0 AS id; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje, 0 AS id; LEAVE proc; END IF;
  SELECT nombre INTO V_ORIGEN FROM cuentas_financieras WHERE id_cuenta = P_ORIGEN AND estado = 'ACTIVO';
  SELECT nombre INTO V_DESTINO FROM cuentas_financieras WHERE id_cuenta = P_DESTINO AND estado = 'ACTIVO';
  IF V_ORIGEN IS NULL OR V_DESTINO IS NULL THEN SELECT 0 AS codigo, 'Seleccione cuentas activas' AS mensaje, 0 AS id; LEAVE proc; END IF;
  CALL SP_VALIDAR_SALIDA(P_ORIGEN, P_MONTO, 'TRANSFERENCIA', 0, V_OK, V_SALDO);
  IF V_OK = 0 THEN SELECT 3 AS codigo, CONCAT('Saldo insuficiente en ', V_ORIGEN, ' (saldo actual ', V_SALDO, ')') AS mensaje, 0 AS id; LEAVE proc; END IF;
  SET V_FECHA = TIMESTAMP(P_FECHA, CURTIME());
  INSERT INTO transferencias(tipo, cuenta_origen, cuenta_destino, monto, fecha, referencia, observacion, usuario_id, estado)
  VALUES (P_TIPO, P_ORIGEN, P_DESTINO, P_MONTO, V_FECHA, NULLIF(TRIM(P_REF), ''), NULLIF(TRIM(P_OBS), ''), P_USU, 'VALIDO');
  SET V_ID = LAST_INSERT_ID();
  INSERT INTO movimientos_cuenta(cuenta_id, fecha, tipo, monto, origen, origen_id, descripcion, referencia, usuario_id, estado, created_at)
  VALUES (P_ORIGEN, V_FECHA, 'SALIDA', P_MONTO, 'TRANSFERENCIA', V_ID, CONCAT(P_TIPO, ' hacia ', V_DESTINO), NULLIF(TRIM(P_REF), ''), P_USU, 'VALIDO', NOW()),
         (P_DESTINO, V_FECHA, 'ENTRADA', P_MONTO, 'TRANSFERENCIA', V_ID, CONCAT(P_TIPO, ' desde ', V_ORIGEN), NULLIF(TRIM(P_REF), ''), P_USU, 'VALIDO', NOW());
  SELECT 1 AS codigo, 'Registrado' AS mensaje, V_ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_ANULAR_TRANSFERENCIA;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_TRANSFERENCIA(IN P_ID INT, IN P_MOTIVO VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_EXISTE INT;
  IF TRIM(IFNULL(P_MOTIVO, '')) = '' THEN SELECT 0 AS codigo, 'Indique el motivo de la anulación' AS mensaje; LEAVE proc; END IF;
  SELECT COUNT(*) INTO V_EXISTE FROM transferencias WHERE id_transferencia = P_ID AND estado = 'VALIDO';
  IF V_EXISTE = 0 THEN SELECT 2 AS codigo, 'No existe o ya fue anulado' AS mensaje; LEAVE proc; END IF;
  UPDATE transferencias SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = NOW() WHERE id_transferencia = P_ID;
  UPDATE movimientos_cuenta SET estado = 'ANULADO' WHERE origen = 'TRANSFERENCIA' AND origen_id = P_ID;
  SELECT 1 AS codigo, 'Anulado' AS mensaje;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_TRANSFERENCIAS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_TRANSFERENCIAS(IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  SELECT t.id_transferencia, t.tipo, o.nombre AS origen, d.nombre AS destino, t.monto, DATE_FORMAT(t.fecha, '%d-%m-%Y %H:%i') AS fecha_txt,
         t.referencia, t.observacion, t.estado, t.motivo_anulacion, u.usu_usuario
    FROM transferencias t
    INNER JOIN cuentas_financieras o ON o.id_cuenta = t.cuenta_origen
    INNER JOIN cuentas_financieras d ON d.id_cuenta = t.cuenta_destino
    LEFT JOIN usuario u ON u.usu_id = t.usuario_id
   WHERE DATE(t.fecha) BETWEEN P_DESDE AND P_HASTA
   ORDER BY t.fecha DESC;
END$$
DELIMITER ;

-- ---------- 11. Cierre de caja y conciliación ----------
DROP PROCEDURE IF EXISTS SP_RESUMEN_CAJA;
DELIMITER $$
CREATE PROCEDURE SP_RESUMEN_CAJA(IN P_CUENTA INT, IN P_FECHA DATE)
BEGIN
  DECLARE V_INICIO DECIMAL(14,2);
  CALL SP_SALDO_CUENTA(P_CUENTA, P_FECHA, V_INICIO);
  SELECT V_INICIO AS saldo_inicial,
         IFNULL(SUM(IF(m.tipo = 'ENTRADA', m.monto, 0)), 0) AS entradas,
         IFNULL(SUM(IF(m.tipo = 'SALIDA', m.monto, 0)), 0) AS salidas,
         V_INICIO + IFNULL(SUM(IF(m.tipo = 'ENTRADA', m.monto, -m.monto)), 0) AS saldo_esperado,
         COUNT(m.id_movimiento) AS movimientos,
         (SELECT COUNT(*) FROM cierres_caja c WHERE c.cuenta_id = P_CUENTA AND c.fecha = P_FECHA) AS cerrado
    FROM cuentas_financieras cu
    LEFT JOIN movimientos_cuenta m ON m.cuenta_id = cu.id_cuenta AND m.estado = 'VALIDO' AND DATE(m.fecha) = P_FECHA
                                  AND DATE(m.fecha) >= IFNULL(cu.fecha_saldo_inicial, '1000-01-01')
   WHERE cu.id_cuenta = P_CUENTA;
END$$
DELIMITER ;

-- Registra el cierre del día. Con P_AJUSTAR = 1 la diferencia se registra como movimiento de AJUSTE
-- (sobrante: entrada; faltante: salida) para que el saldo del sistema coincida con lo contado.
DROP PROCEDURE IF EXISTS SP_CERRAR_CAJA;
DELIMITER $$
CREATE PROCEDURE SP_CERRAR_CAJA(IN P_CUENTA INT, IN P_FECHA DATE, IN P_CONTADO DECIMAL(14,2), IN P_OBS VARCHAR(255),
                                IN P_AJUSTAR TINYINT, IN P_USU INT)
proc: BEGIN
  DECLARE V_INICIO, V_ENTRADAS, V_SALIDAS, V_ESPERADO, V_DIF DECIMAL(14,2);
  DECLARE V_EXISTE, V_ID INT;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje; LEAVE proc; END IF;
  IF P_CONTADO IS NULL OR P_CONTADO < 0 THEN SELECT 0 AS codigo, 'Ingrese el monto contado' AS mensaje; LEAVE proc; END IF;
  SELECT COUNT(*) INTO V_EXISTE FROM cierres_caja WHERE cuenta_id = P_CUENTA AND fecha = P_FECHA;
  IF V_EXISTE > 0 THEN SELECT 2 AS codigo, 'Esa caja ya fue cerrada en esa fecha' AS mensaje; LEAVE proc; END IF;
  CALL SP_SALDO_CUENTA(P_CUENTA, P_FECHA, V_INICIO);
  SELECT IFNULL(SUM(IF(tipo = 'ENTRADA', monto, 0)), 0), IFNULL(SUM(IF(tipo = 'SALIDA', monto, 0)), 0)
    INTO V_ENTRADAS, V_SALIDAS
    FROM movimientos_cuenta WHERE cuenta_id = P_CUENTA AND estado = 'VALIDO' AND DATE(fecha) = P_FECHA;
  SET V_ESPERADO = V_INICIO + V_ENTRADAS - V_SALIDAS;
  SET V_DIF = P_CONTADO - V_ESPERADO;
  IF V_DIF <> 0 AND TRIM(IFNULL(P_OBS, '')) = '' THEN
    SELECT 0 AS codigo, CONCAT('Hay una diferencia de ', V_DIF, ': explique el motivo en la observación') AS mensaje; LEAVE proc;
  END IF;
  INSERT INTO cierres_caja(cuenta_id, fecha, saldo_inicial, entradas, salidas, saldo_esperado, saldo_contado, diferencia, ajustado,
                           observacion, usuario_id, created_at)
  VALUES (P_CUENTA, P_FECHA, V_INICIO, V_ENTRADAS, V_SALIDAS, V_ESPERADO, P_CONTADO, V_DIF, IF(P_AJUSTAR = 1 AND V_DIF <> 0, 1, 0),
          NULLIF(TRIM(P_OBS), ''), P_USU, NOW());
  SET V_ID = LAST_INSERT_ID();
  IF P_AJUSTAR = 1 AND V_DIF <> 0 THEN
    INSERT INTO movimientos_cuenta(cuenta_id, fecha, tipo, monto, origen, origen_id, descripcion, usuario_id, estado, created_at)
    VALUES (P_CUENTA, TIMESTAMP(P_FECHA, '23:59:59'), IF(V_DIF > 0, 'ENTRADA', 'SALIDA'), ABS(V_DIF), 'AJUSTE', V_ID,
            CONCAT(IF(V_DIF > 0, 'Sobrante', 'Faltante'), ' en cierre de caja: ', P_OBS), P_USU, 'VALIDO', NOW());
  END IF;
  SELECT 1 AS codigo, 'Caja cerrada' AS mensaje, V_ESPERADO AS esperado, V_DIF AS diferencia;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_CIERRES;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_CIERRES(IN P_CUENTA INT)
BEGIN
  SELECT c.id_cierre, DATE_FORMAT(c.fecha, '%d-%m-%Y') AS fecha_txt, c.saldo_inicial, c.entradas, c.salidas, c.saldo_esperado,
         c.saldo_contado, c.diferencia, c.ajustado, c.observacion, u.usu_usuario
    FROM cierres_caja c LEFT JOIN usuario u ON u.usu_id = c.usuario_id
   WHERE c.cuenta_id = P_CUENTA
   ORDER BY c.fecha DESC LIMIT 60;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_CONCILIAR_MOVIMIENTO;
DELIMITER $$
CREATE PROCEDURE SP_CONCILIAR_MOVIMIENTO(IN P_ID INT, IN P_VALOR TINYINT)
BEGIN
  UPDATE movimientos_cuenta SET conciliado = P_VALOR, fecha_conciliacion = IF(P_VALOR = 1, NOW(), NULL)
   WHERE id_movimiento = P_ID AND estado = 'VALIDO';
  SELECT ROW_COUNT() AS cambiados;
END$$
DELIMITER ;

-- ---------- 12. Panel del contador ----------
DROP PROCEDURE IF EXISTS SP_PANEL_CONTABLE;
DELIMITER $$
CREATE PROCEDURE SP_PANEL_CONTABLE()
BEGIN
  SELECT
    (SELECT IFNULL(SUM(monto), 0) FROM ingresos WHERE estado = 'VALIDO' AND created_at = CURDATE()) AS ingresos_hoy,
    (SELECT IFNULL(SUM(monto), 0) FROM egresos WHERE estado = 'VALIDO' AND created_at = CURDATE()) AS egresos_hoy,
    (SELECT IFNULL(SUM(monto), 0) FROM ingresos WHERE estado = 'VALIDO' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS ingresos_mes,
    (SELECT IFNULL(SUM(monto), 0) FROM egresos WHERE estado = 'VALIDO' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS egresos_mes,
    (SELECT COUNT(*) FROM pagos WHERE estado = 'VALIDO' AND DATE(fecha) = CURDATE()) AS recibos_hoy;
END$$
DELIMITER ;

-- ---------- 13. Ajustes a procedimientos existentes ----------

DROP PROCEDURE IF EXISTS `SP_REGISTRAR_MATRICULA`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_MATRICULA`(
  IN `IDESTU` INT, IN `AÑO` INT, IN `AULA` INT,
  IN `ADMIN` DECIMAL(14,2), IN `NUEVO` DECIMAL(14,2), IN `MATRI` DECIMAL(14,2),
  IN `PROCEDEN` VARCHAR(100), IN `PROVI` VARCHAR(50), IN `DEPAR` VARCHAR(50),
  IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `USUREG` INT,
  IN `P_METODO` INT, IN `P_CUENTA` INT, IN `P_REF` VARCHAR(60))
proc: BEGIN
  DECLARE V_ALUMNO, V_CANT, V_USU, V_MATRI, V_ID, V_EXISTE, V_RECIBO, V_N, V_CTA INT;
  DECLARE V_TOTAL DECIMAL(14,2);
  DECLARE V_SERIE VARCHAR(5);
  DECLARE V_REQ_REF, V_REQ_CTA TINYINT;
  DECLARE V_COMISION DECIMAL(5,2);
  DECLARE V_METODO_OK INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  SELECT COUNT(*) INTO V_ALUMNO FROM alumnos WHERE Id_alumno = IDESTU;
  IF V_ALUMNO = 0 THEN SELECT 0; LEAVE proc; END IF;

  SELECT COUNT(*) INTO V_CANT FROM matricula WHERE id_alumno = IDESTU AND `id_año` = AÑO;
  IF V_CANT > 0 THEN SELECT 2; LEAVE proc; END IF;

  SELECT MAX(usu_id) INTO V_USU FROM matricula WHERE id_alumno = IDESTU AND usu_id IS NOT NULL;
  IF V_USU IS NULL THEN
    IF TRIM(IFNULL(USU, '')) = '' THEN SELECT 4; LEAVE proc; END IF;
    SELECT COUNT(*) INTO V_EXISTE FROM usuario WHERE usu_usuario = USU;
    IF V_EXISTE > 0 THEN SELECT 3; LEAVE proc; END IF;
  END IF;

  SET V_TOTAL = IFNULL(ADMIN, 0) + IFNULL(NUEVO, 0) + IFNULL(MATRI, 0);
  IF V_TOTAL > 0 THEN
    SELECT COUNT(*), MAX(requiere_referencia), MAX(requiere_cuenta), MAX(comision_pct)
      INTO V_METODO_OK, V_REQ_REF, V_REQ_CTA, V_COMISION
      FROM metodos_pago WHERE id_metodo = P_METODO AND estado = 'ACTIVO';
    IF P_CUENTA IS NOT NULL THEN
      SELECT MAX(id_cuenta) INTO V_CTA FROM cuentas_financieras WHERE id_cuenta = P_CUENTA AND estado = 'ACTIVO';
    END IF;
    IF V_METODO_OK = 0 OR (V_REQ_REF = 1 AND TRIM(IFNULL(P_REF, '')) = '') OR (V_REQ_CTA = 1 AND V_CTA IS NULL) THEN
      SELECT 5; LEAVE proc;
    END IF;
  END IF;

  START TRANSACTION;
  IF V_USU IS NULL THEN
    INSERT INTO usuario(usu_usuario, usu_contra, usu_email, usu_estatus, rol_id, empresa_id, created_at, updated_at)
    VALUES (USU, CONTRA, EMAIL, 'ACTIVO', 1, 1, NOW(), NOW());
    SET V_USU = LAST_INSERT_ID();
  ELSE
    UPDATE usuario SET usu_estatus = 'ACTIVO' WHERE usu_id = V_USU;
  END IF;

  UPDATE alumnos SET alum_estatus = 'SI', tipo_alum = 'ANTIGUO' WHERE Id_alumno = IDESTU;

  INSERT INTO matricula(id_alumno, `id_año`, id_aula, pago_admi, pago_alu_nuevo, pago_matricula,
                        procedencia_colegio, provincia, departamento, usu_id, created_at, updated_at)
  VALUES (IDESTU, AÑO, AULA, ADMIN, NUEVO, MATRI, PROCEDEN, PROVI, DEPAR, V_USU, NOW(), NOW());
  SET V_MATRI = LAST_INSERT_ID();

  
  IF V_TOTAL > 0 THEN
    SELECT serie_recibo INTO V_SERIE FROM empresa ORDER BY empresa_id LIMIT 1;
    INSERT IGNORE INTO correlativos(serie, ultimo) VALUES (V_SERIE, 0);
    UPDATE correlativos SET ultimo = ultimo + 1 WHERE serie = V_SERIE;
    SELECT ultimo INTO V_N FROM correlativos WHERE serie = V_SERIE;
    INSERT INTO pagos(serie, numero, id_matricula, fecha, total, observacion, usuario_id, estado)
    VALUES (V_SERIE, V_N, V_MATRI, NOW(), V_TOTAL, 'Matrícula', USUREG, 'VALIDO');
    SET V_RECIBO = LAST_INSERT_ID();
    INSERT INTO pagos_metodos(id_pago, metodo_pago_id, cuenta_id, monto, comision, referencia, created_at)
    VALUES (V_RECIBO, P_METODO, V_CTA, V_TOTAL, ROUND(V_TOTAL * V_COMISION / 100, 2), NULLIF(TRIM(P_REF), ''), NOW());
    CALL SP_SINCRONIZAR_MOVIMIENTO('PAGO', LAST_INSERT_ID(), V_CTA, 'ENTRADA', V_TOTAL, NOW(), CONCAT('Recibo ', V_SERIE, '-', LPAD(V_N, 6, '0')), P_REF, USUREG);
  END IF;

  INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
  VALUES (IF(IFNULL(ADMIN, 0) > 0, V_RECIBO, NULL), V_MATRI, 'ADMISION', NULL, NOW(), IFNULL(ADMIN, 0), 'VALIDO', USUREG, NOW(), NOW());
  SET V_ID = LAST_INSERT_ID();
  IF IFNULL(ADMIN, 0) > 0 THEN
    INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
    VALUES (V_ID, 1, USUREG, 1, ADMIN, 'ADMISION', 'VALIDO', CURDATE());
  END IF;

  INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
  VALUES (IF(IFNULL(NUEVO, 0) > 0, V_RECIBO, NULL), V_MATRI, 'ALUMNO NUEVO', NULL, NOW(), IFNULL(NUEVO, 0), 'VALIDO', USUREG, NOW(), NOW());
  SET V_ID = LAST_INSERT_ID();
  IF IFNULL(NUEVO, 0) > 0 THEN
    INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
    VALUES (V_ID, 1, USUREG, 1, NUEVO, 'ALUMNO NUEVO', 'VALIDO', CURDATE());
  END IF;

  INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
  VALUES (IF(IFNULL(MATRI, 0) > 0, V_RECIBO, NULL), V_MATRI, 'MATRICULA', NULL, NOW(), IFNULL(MATRI, 0), 'VALIDO', USUREG, NOW(), NOW());
  SET V_ID = LAST_INSERT_ID();
  IF IFNULL(MATRI, 0) > 0 THEN
    INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
    VALUES (V_ID, 1, USUREG, 1, MATRI, 'MATRICULA', 'VALIDO', CURDATE());
  END IF;

  COMMIT;
  SELECT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_REGISTRAR_PERSONAL`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_PERSONAL`(IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `TIPO` VARCHAR(30), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `ROL` INT)
BEGIN
DECLARE EDAD INT;
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM personal_admi where personal_admi.personal_adm_dni=DNI);
IF @CANTIDAD = 0 THEN
INSERT INTO usuario(usu_usuario,usu_contra,usu_email,usu_estatus,rol_id,empresa_id,created_at,updated_at)VALUE(USU,CONTRA,EMAIL,'ACTIVO',ROL,'1',NOW(),'');

INSERT INTO personal_admi(personal_adm_dni,personal_adm_nombre,personal_adm_apellido,personal_adm_tipo,personal_adm_sexo,personal_adm_fechanacimiento,personal_adm_movil,personal_adm_nro_alterno,personal_adm_direccion,personal_adm_estatus,personal_adm_fotoperfil,id_ausuario,created_at,updated_at)VALUE(DNI,NOMBRES,APELLI,TIPO,SEXO,FECHANAC,CEL,CELALT,DIREC,'ACTIVO',FOTO,(SELECT MAX(`usu_id`) from `usuario`),NOW(),'');

SELECT 1;
ELSE
SELECT 2;

END IF;

END$$
DELIMITER ;
