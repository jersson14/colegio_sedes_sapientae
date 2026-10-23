-- =====================================================================
-- Migración 004 – Fase 4: métodos de pago, cuentas del colegio y recibos correlativos
-- Aplicar con: mysql --default-character-set=utf8mb4 colegio2 < 004_metodos_pago_recibos.sql
-- =====================================================================

-- ---------- 1. Catálogos ----------
CREATE TABLE IF NOT EXISTS metodos_pago (
  id_metodo           INT AUTO_INCREMENT PRIMARY KEY,
  nombre              VARCHAR(60) NOT NULL UNIQUE,
  tipo                ENUM('EFECTIVO','TARJETA','TRANSFERENCIA','DEPOSITO','BILLETERA','CHEQUE','OTRO') NOT NULL,
  requiere_referencia TINYINT(1)   NOT NULL DEFAULT 0,   -- n.° de operación / voucher / últimos dígitos
  requiere_cuenta     TINYINT(1)   NOT NULL DEFAULT 1,   -- a qué caja o banco entra el dinero
  comision_pct        DECIMAL(5,2) NOT NULL DEFAULT 0,   -- comisión del banco/POS (informativa)
  estado              ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  created_at          DATETIME NULL,
  updated_at          DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cuentas_financieras (
  id_cuenta            INT AUTO_INCREMENT PRIMARY KEY,
  tipo                 ENUM('CAJA','BANCO','BILLETERA') NOT NULL,
  nombre               VARCHAR(100) NOT NULL UNIQUE,     -- "Caja principal", "BCP cuenta corriente"...
  banco                VARCHAR(100) NULL,
  numero_cuenta        VARCHAR(40)  NULL,
  codigo_interbancario VARCHAR(40)  NULL,                -- CCI / CBU / IBAN
  titular              VARCHAR(150) NULL,
  saldo_inicial        DECIMAL(14,2) NOT NULL DEFAULT 0,
  fecha_saldo_inicial  DATE NULL,
  estado               ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  created_at           DATETIME NULL,
  updated_at           DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO metodos_pago (nombre, tipo, requiere_referencia, requiere_cuenta, estado, created_at) VALUES
  ('EFECTIVO', 'EFECTIVO', 0, 1, 'ACTIVO', NOW()),
  ('TARJETA DE DÉBITO', 'TARJETA', 1, 1, 'ACTIVO', NOW()),
  ('TARJETA DE CRÉDITO', 'TARJETA', 1, 1, 'ACTIVO', NOW()),
  ('TRANSFERENCIA BANCARIA', 'TRANSFERENCIA', 1, 1, 'ACTIVO', NOW()),
  ('DEPÓSITO EN CUENTA', 'DEPOSITO', 1, 1, 'ACTIVO', NOW()),
  ('BILLETERA DIGITAL', 'BILLETERA', 1, 1, 'ACTIVO', NOW()),
  ('CHEQUE', 'CHEQUE', 1, 1, 'ACTIVO', NOW()),
  -- Solo para los pagos anteriores a esta versión, que no registraban el método
  ('NO REGISTRADO (HISTÓRICO)', 'OTRO', 0, 0, 'INACTIVO', NOW())
ON DUPLICATE KEY UPDATE tipo = VALUES(tipo);

INSERT INTO cuentas_financieras (tipo, nombre, estado, fecha_saldo_inicial, created_at)
VALUES ('CAJA', 'CAJA PRINCIPAL', 'ACTIVO', CURDATE(), NOW())
ON DUPLICATE KEY UPDATE tipo = VALUES(tipo);

-- ---------- 2. Recibos ----------
ALTER TABLE empresa ADD COLUMN serie_recibo VARCHAR(5) NOT NULL DEFAULT 'R001' AFTER permite_doc_extranjero;

-- Último número usado por serie (se incrementa dentro de la transacción del pago: sin saltos ni repetidos)
CREATE TABLE IF NOT EXISTS correlativos (
  serie  VARCHAR(5) PRIMARY KEY,
  ultimo INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagos (
  id_pago             INT AUTO_INCREMENT PRIMARY KEY,
  serie               VARCHAR(5)  NOT NULL,
  numero              INT         NOT NULL,
  id_matricula        INT         NOT NULL,
  fecha               DATETIME    NOT NULL,
  total               DECIMAL(14,2) NOT NULL DEFAULT 0,
  observacion         VARCHAR(255) NULL,
  voucher             VARCHAR(255) NULL,
  usuario_id          INT         NULL,
  estado              ENUM('VALIDO','ANULADO') NOT NULL DEFAULT 'VALIDO',
  motivo_anulacion    VARCHAR(255) NULL,
  fecha_anulacion     DATETIME    NULL,
  usuario_anulacion   INT         NULL,
  UNIQUE KEY uk_recibo (serie, numero),
  INDEX idx_pagos_matricula (id_matricula),
  INDEX idx_pagos_fecha (fecha),
  CONSTRAINT fk_pagos_matricula FOREIGN KEY (id_matricula) REFERENCES matricula(id_matricula) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forma(s) de pago de cada recibo (un recibo puede pagarse en parte en efectivo y en parte con tarjeta)
CREATE TABLE IF NOT EXISTS pagos_metodos (
  id_pago_metodo INT AUTO_INCREMENT PRIMARY KEY,
  id_pago        INT NOT NULL,
  metodo_pago_id INT NOT NULL,
  cuenta_id      INT NULL,
  monto          DECIMAL(14,2) NOT NULL,
  comision       DECIMAL(14,2) NOT NULL DEFAULT 0,
  referencia     VARCHAR(60) NULL,
  created_at     DATETIME NOT NULL,
  INDEX idx_pm_cuenta (cuenta_id),
  CONSTRAINT fk_pm_pago   FOREIGN KEY (id_pago)        REFERENCES pagos(id_pago) ON DELETE CASCADE,
  CONSTRAINT fk_pm_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo),
  CONSTRAINT fk_pm_cuenta FOREIGN KEY (cuenta_id)      REFERENCES cuentas_financieras(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pago_pensiones
  ADD COLUMN id_pago INT NULL AFTER id_pago_pension,
  ADD CONSTRAINT fk_pago_recibo FOREIGN KEY (id_pago) REFERENCES pagos(id_pago) ON DELETE SET NULL;

-- ---------- 3. Recibos para los pagos anteriores ----------
-- Un recibo por alumno y día con el método "NO REGISTRADO (HISTÓRICO)" (no se inventa el método real).
DROP PROCEDURE IF EXISTS TMP_RECIBOS_HISTORICOS;
DELIMITER $$
CREATE PROCEDURE TMP_RECIBOS_HISTORICOS()
BEGIN
  DECLARE V_FIN INT DEFAULT 0;
  DECLARE V_MATRI, V_ID, V_N, V_METODO INT;
  DECLARE V_FECHA DATE;
  DECLARE V_TOTAL DECIMAL(14,2);
  DECLARE V_SERIE VARCHAR(5);
  DECLARE cur CURSOR FOR
    SELECT id_matri, fecha_pago, SUM(sub_total)
      FROM pago_pensiones
     WHERE estado = 'VALIDO' AND id_pago IS NULL AND (sub_total > 0 OR mora_exonerada = 1)
     GROUP BY id_matri, fecha_pago
     ORDER BY fecha_pago, id_matri;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET V_FIN = 1;

  SELECT serie_recibo INTO V_SERIE FROM empresa ORDER BY empresa_id LIMIT 1;
  SELECT id_metodo INTO V_METODO FROM metodos_pago WHERE nombre = 'NO REGISTRADO (HISTÓRICO)';
  INSERT IGNORE INTO correlativos(serie, ultimo) VALUES (V_SERIE, 0);
  OPEN cur;
  bucle: LOOP
    FETCH cur INTO V_MATRI, V_FECHA, V_TOTAL;
    IF V_FIN = 1 THEN LEAVE bucle; END IF;
    UPDATE correlativos SET ultimo = ultimo + 1 WHERE serie = V_SERIE;
    SELECT ultimo INTO V_N FROM correlativos WHERE serie = V_SERIE;
    INSERT INTO pagos(serie, numero, id_matricula, fecha, total, observacion, usuario_id, estado)
    VALUES (V_SERIE, V_N, V_MATRI, V_FECHA, V_TOTAL, 'Recibo generado para pagos anteriores al registro de método de pago', NULL, 'VALIDO');
    SET V_ID = LAST_INSERT_ID();
    UPDATE pago_pensiones SET id_pago = V_ID
     WHERE id_matri = V_MATRI AND fecha_pago = V_FECHA AND estado = 'VALIDO' AND id_pago IS NULL
       AND (sub_total > 0 OR mora_exonerada = 1);
    IF V_TOTAL > 0 THEN
      INSERT INTO pagos_metodos(id_pago, metodo_pago_id, cuenta_id, monto, referencia, created_at)
      VALUES (V_ID, V_METODO, NULL, V_TOTAL, NULL, NOW());
    END IF;
  END LOOP;
  CLOSE cur;
END$$
DELIMITER ;
CALL TMP_RECIBOS_HISTORICOS();
DROP PROCEDURE TMP_RECIBOS_HISTORICOS;

-- ---------- 4. Procedimientos de recibo ----------
-- Crea la cabecera con el siguiente número de la serie. Debe llamarse dentro de una transacción.
DROP PROCEDURE IF EXISTS SP_CREAR_RECIBO;
DELIMITER $$
CREATE PROCEDURE SP_CREAR_RECIBO(IN P_MATRI INT, IN P_OBS VARCHAR(255), IN P_USU INT)
BEGIN
  DECLARE V_SERIE VARCHAR(5);
  DECLARE V_N INT;
  SELECT serie_recibo INTO V_SERIE FROM empresa ORDER BY empresa_id LIMIT 1;
  INSERT IGNORE INTO correlativos(serie, ultimo) VALUES (V_SERIE, 0);
  -- El UPDATE bloquea la fila de la serie hasta el COMMIT: dos cajas no obtienen el mismo número
  UPDATE correlativos SET ultimo = ultimo + 1 WHERE serie = V_SERIE;
  SELECT ultimo INTO V_N FROM correlativos WHERE serie = V_SERIE;
  INSERT INTO pagos(serie, numero, id_matricula, fecha, total, observacion, usuario_id, estado)
  VALUES (V_SERIE, V_N, P_MATRI, NOW(), 0, NULLIF(TRIM(P_OBS), ''), P_USU, 'VALIDO');
  SELECT LAST_INSERT_ID() AS id_pago, V_SERIE AS serie, V_N AS numero,
         CONCAT(V_SERIE, '-', LPAD(V_N, 6, '0')) AS recibo;
END$$
DELIMITER ;

-- Agrega una forma de pago al recibo. Devuelve codigo (1 ok, 0 error) y mensaje.
DROP PROCEDURE IF EXISTS SP_AGREGAR_METODO_RECIBO;
DELIMITER $$
CREATE PROCEDURE SP_AGREGAR_METODO_RECIBO(IN P_PAGO INT, IN P_METODO INT, IN P_CUENTA INT, IN P_MONTO DECIMAL(14,2), IN P_REF VARCHAR(60))
proc: BEGIN
  DECLARE V_NOMBRE VARCHAR(60);
  DECLARE V_REQ_REF, V_REQ_CTA TINYINT;
  DECLARE V_COMISION DECIMAL(5,2);
  DECLARE V_CTA INT;
  SELECT nombre, requiere_referencia, requiere_cuenta, comision_pct INTO V_NOMBRE, V_REQ_REF, V_REQ_CTA, V_COMISION
    FROM metodos_pago WHERE id_metodo = P_METODO AND estado = 'ACTIVO';
  IF V_NOMBRE IS NULL THEN
    SELECT 0 AS codigo, 'Forma de pago no válida' AS mensaje; LEAVE proc;
  END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN
    SELECT 0 AS codigo, CONCAT('Monto no válido en ', V_NOMBRE) AS mensaje; LEAVE proc;
  END IF;
  IF V_REQ_REF = 1 AND TRIM(IFNULL(P_REF, '')) = '' THEN
    SELECT 0 AS codigo, CONCAT(V_NOMBRE, ': indique el n.° de operación o referencia') AS mensaje; LEAVE proc;
  END IF;
  IF P_CUENTA IS NOT NULL THEN
    SELECT id_cuenta INTO V_CTA FROM cuentas_financieras WHERE id_cuenta = P_CUENTA AND estado = 'ACTIVO';
  END IF;
  IF V_REQ_CTA = 1 AND V_CTA IS NULL THEN
    SELECT 0 AS codigo, CONCAT(V_NOMBRE, ': seleccione la caja o cuenta donde ingresa el dinero') AS mensaje; LEAVE proc;
  END IF;
  INSERT INTO pagos_metodos(id_pago, metodo_pago_id, cuenta_id, monto, comision, referencia, created_at)
  VALUES (P_PAGO, P_METODO, V_CTA, P_MONTO, ROUND(P_MONTO * V_COMISION / 100, 2), NULLIF(TRIM(P_REF), ''), NOW());
  SELECT 1 AS codigo, 'ok' AS mensaje;
END$$
DELIMITER ;

-- Calcula el total del recibo y verifica que las formas de pago sumen lo mismo.
DROP PROCEDURE IF EXISTS SP_CERRAR_RECIBO;
DELIMITER $$
CREATE PROCEDURE SP_CERRAR_RECIBO(IN P_PAGO INT)
BEGIN
  DECLARE V_TOTAL, V_METODOS DECIMAL(14,2);
  SELECT IFNULL(SUM(sub_total), 0) INTO V_TOTAL FROM pago_pensiones WHERE id_pago = P_PAGO AND estado = 'VALIDO';
  SELECT IFNULL(SUM(monto), 0) INTO V_METODOS FROM pagos_metodos WHERE id_pago = P_PAGO;
  IF V_TOTAL <> V_METODOS THEN
    SELECT 0 AS codigo, CONCAT('Las formas de pago suman ', V_METODOS, ' y el total a pagar es ', V_TOTAL) AS mensaje, V_TOTAL AS total;
  ELSE
    UPDATE pagos SET total = V_TOTAL WHERE id_pago = P_PAGO;
    SELECT 1 AS codigo, 'ok' AS mensaje, V_TOTAL AS total;
  END IF;
END$$
DELIMITER ;

-- Si el recibo tiene una sola forma de pago, la ajusta al total actual (al corregir montos).
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
    SET O_OK = 1;
  END IF;
END$$
DELIMITER ;

-- Anula el recibo completo: sus conceptos, sus ingresos y el recibo. Devuelve codigo y mensaje.
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
  UPDATE pagos SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = NOW(), usuario_anulacion = P_USU
   WHERE id_pago = P_PAGO;
  SELECT 1 AS codigo, 'Recibo anulado' AS mensaje;
END$$
DELIMITER ;

-- Anular un concepto ahora anula el recibo al que pertenece (si no tiene recibo, solo el concepto)
DROP PROCEDURE IF EXISTS SP_ANULAR_PAGO_PENSION;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_PAGO_PENSION(IN P_ID INT, IN P_MOTIVO VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_EXISTE INT;
  DECLARE V_PAGO INT;
  IF TRIM(IFNULL(P_MOTIVO, '')) = '' THEN
    SELECT 0 AS codigo, 'Indique el motivo de la anulación' AS mensaje; LEAVE proc;
  END IF;
  SELECT COUNT(*), MAX(id_pago) INTO V_EXISTE, V_PAGO FROM pago_pensiones WHERE id_pago_pension = P_ID AND estado = 'VALIDO';
  IF V_EXISTE = 0 THEN
    SELECT 2 AS codigo, 'El pago no existe o ya fue anulado' AS mensaje; LEAVE proc;
  END IF;
  IF V_PAGO IS NOT NULL THEN
    CALL SP_ANULAR_RECIBO(V_PAGO, P_MOTIVO, P_USU);
    LEAVE proc;
  END IF;
  UPDATE pago_pensiones
     SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = NOW(), id_usuario_anulacion = P_USU, updated_at = NOW()
   WHERE id_pago_pension = P_ID;
  UPDATE ingresos SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = CURDATE(), updated = NOW()
   WHERE id_pago_pension = P_ID AND estado = 'VALIDO';
  SELECT 1 AS codigo, 'Pago anulado' AS mensaje;
END$$
DELIMITER ;

-- ---------- 5. Cada concepto de pago se enlaza a su recibo ----------
DROP PROCEDURE IF EXISTS `SP_REGISTRAR_PAGO_PENSION`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_PAGO_PENSION`(
  IN P_MATRI INT, IN P_CONCEPTO VARCHAR(30), IN P_PENSION INT, IN P_MONTO DECIMAL(14,2),
  IN P_EXONERAR TINYINT, IN P_MOTIVO VARCHAR(255), IN P_USU INT, IN P_PAGO INT)
proc: BEGIN
  DECLARE V_NIVEL, V_NIVEL_PEN, V_EXISTE, V_ID INT;
  DECLARE V_PRECIO, V_MORA, V_PAG_P, V_PAG_M, V_SALDO_P, V_SALDO_M, V_PAY_P, V_PAY_M DECIMAL(14,2);
  DECLARE V_EXO, V_VENCIDA TINYINT;

  SELECT a.id_nivel_academico INTO V_NIVEL
    FROM matricula m INNER JOIN aulas a ON a.Id_aula = m.id_aula
   WHERE m.id_matricula = P_MATRI;
  IF V_NIVEL IS NULL THEN
    SELECT 0 AS codigo, 'La matrícula no existe' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;
  IF P_MONTO IS NULL OR P_MONTO < 0 THEN
    SELECT 0 AS codigo, 'El monto no es válido' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;

  
  IF P_CONCEPTO <> 'PENSION' THEN
    IF P_CONCEPTO NOT IN ('ADMISION', 'ALUMNO NUEVO', 'MATRICULA') THEN
      SELECT 0 AS codigo, 'Concepto no válido' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
      LEAVE proc;
    END IF;
    IF P_MONTO <= 0 THEN
      SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
      LEAVE proc;
    END IF;
    SELECT COUNT(*) INTO V_EXISTE FROM pago_pensiones
     WHERE id_matri = P_MATRI AND concepto = P_CONCEPTO AND estado = 'VALIDO' AND sub_total > 0;
    IF V_EXISTE > 0 THEN
      SELECT 2 AS codigo, CONCAT('El concepto ', P_CONCEPTO, ' ya fue pagado') AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
      LEAVE proc;
    END IF;
    INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
    VALUES (P_PAGO, P_MATRI, P_CONCEPTO, NULL, NOW(), P_MONTO, 'VALIDO', P_USU, NOW(), NULL);
    SET V_ID = LAST_INSERT_ID();
    INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
    VALUES (V_ID, 1, P_USU, 1, P_MONTO, P_CONCEPTO, 'VALIDO', CURDATE());
    SELECT 1 AS codigo, 'Pago registrado' AS mensaje, P_MONTO AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;

  
  SELECT id_nivel_academico INTO V_NIVEL_PEN FROM pensiones WHERE id_pensiones = P_PENSION;
  IF V_NIVEL_PEN IS NULL OR V_NIVEL_PEN <> V_NIVEL THEN
    SELECT 0 AS codigo, 'La pensión no corresponde al nivel de la matrícula' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;

  CALL SP_CALCULAR_SALDO_PENSION(P_MATRI, P_PENSION, V_PRECIO, V_MORA, V_PAG_P, V_PAG_M, V_EXO, V_VENCIDA, V_SALDO_P, V_SALDO_M);
  IF V_SALDO_P + V_SALDO_M <= 0 THEN
    SELECT 2 AS codigo, 'La pensión ya está pagada' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;

  IF P_EXONERAR = 1 AND V_SALDO_M > 0 THEN
    IF TRIM(IFNULL(P_MOTIVO, '')) = '' THEN
      SELECT 0 AS codigo, 'Indique el motivo para exonerar la mora' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
      LEAVE proc;
    END IF;
    INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, mora_exonerada, motivo_edicion, estado, id_usuario, created_at, updated_at)
    VALUES (P_PAGO, P_MATRI, 'MORA', P_PENSION, NOW(), 0, 1, CONCAT('MORA EXONERADA: ', P_MOTIVO), 'VALIDO', P_USU, NOW(), NULL);
    SET V_SALDO_M = 0;
  END IF;

  IF V_SALDO_P + V_SALDO_M <= 0 THEN
    SELECT 1 AS codigo, 'Mora exonerada' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;
  IF P_MONTO <= 0 THEN
    SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;
  IF P_MONTO > V_SALDO_P + V_SALDO_M THEN
    SELECT 3 AS codigo, CONCAT('El monto excede el saldo pendiente (', V_SALDO_P + V_SALDO_M, ')') AS mensaje,
           0.00 AS pagado_pension, 0.00 AS pagado_mora;
    LEAVE proc;
  END IF;

  SET V_PAY_M = LEAST(P_MONTO, V_SALDO_M);
  SET V_PAY_P = LEAST(P_MONTO - V_PAY_M, V_SALDO_P);

  IF V_PAY_M > 0 THEN
    INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
    VALUES (P_PAGO, P_MATRI, 'MORA', P_PENSION, NOW(), V_PAY_M, 'VALIDO', P_USU, NOW(), NULL);
    SET V_ID = LAST_INSERT_ID();
    INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
    VALUES (V_ID, 1, P_USU, 1, V_PAY_M, 'MORA', 'VALIDO', CURDATE());
  END IF;
  IF V_PAY_P > 0 THEN
    INSERT INTO pago_pensiones(id_pago, id_matri, concepto, id_pension, fecha_pago, sub_total, estado, id_usuario, created_at, updated_at)
    VALUES (P_PAGO, P_MATRI, 'PENSION', P_PENSION, NOW(), V_PAY_P, 'VALIDO', P_USU, NOW(), NULL);
    SET V_ID = LAST_INSERT_ID();
    INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
    VALUES (V_ID, 1, P_USU, 1, V_PAY_P, 'PENSION', 'VALIDO', CURDATE());
  END IF;

  SELECT 1 AS codigo, 'Pago registrado' AS mensaje, V_PAY_P AS pagado_pension, V_PAY_M AS pagado_mora;
END$$
DELIMITER ;

-- ---------- 6. Modificar un pago: ajusta también el recibo (si tiene una sola forma de pago) ----------
DROP PROCEDURE IF EXISTS SP_MODIFICAR_PAGO_PENSION;
DELIMITER $$
CREATE PROCEDURE SP_MODIFICAR_PAGO_PENSION(IN P_ID INT, IN P_MONTO DECIMAL(14,2), IN P_DESCRIP VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_CONCEPTO VARCHAR(30);
  DECLARE V_MATRI, V_PENSION, V_PAGO, V_METODOS INT;
  DECLARE V_TOPE, V_OTROS DECIMAL(14,2);
  DECLARE V_OK TINYINT;

  SELECT concepto, id_matri, id_pension, id_pago INTO V_CONCEPTO, V_MATRI, V_PENSION, V_PAGO
    FROM pago_pensiones WHERE id_pago_pension = P_ID AND estado = 'VALIDO';
  IF V_CONCEPTO IS NULL THEN
    SELECT 2 AS codigo, 'El pago no existe o está anulado' AS mensaje; LEAVE proc;
  END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN
    SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje; LEAVE proc;
  END IF;
  IF TRIM(IFNULL(P_DESCRIP, '')) = '' THEN
    SELECT 0 AS codigo, 'Indique el motivo de la modificación' AS mensaje; LEAVE proc;
  END IF;
  IF V_PAGO IS NOT NULL THEN
    SELECT COUNT(*) INTO V_METODOS FROM pagos_metodos WHERE id_pago = V_PAGO;
    IF V_METODOS > 1 THEN
      SELECT 0 AS codigo, 'El recibo se pagó con varias formas de pago: anúlelo y regístrelo de nuevo' AS mensaje; LEAVE proc;
    END IF;
  END IF;
  -- Una pensión (o su mora) no puede quedar pagada por encima de su precio
  IF V_CONCEPTO IN ('PENSION', 'MORA') THEN
    SELECT IF(V_CONCEPTO = 'PENSION', precio, IFNULL(mora, 0)) INTO V_TOPE FROM pensiones WHERE id_pensiones = V_PENSION;
    SELECT IFNULL(SUM(sub_total), 0) INTO V_OTROS FROM pago_pensiones
     WHERE id_matri = V_MATRI AND id_pension = V_PENSION AND concepto = V_CONCEPTO
       AND estado = 'VALIDO' AND id_pago_pension <> P_ID;
    IF V_OTROS + P_MONTO > V_TOPE THEN
      SELECT 3 AS codigo, CONCAT('El monto supera lo pendiente (máximo ', V_TOPE - V_OTROS, ')') AS mensaje; LEAVE proc;
    END IF;
  END IF;
  UPDATE pago_pensiones SET sub_total = P_MONTO, motivo_edicion = P_DESCRIP, updated_at = NOW()
   WHERE id_pago_pension = P_ID;
  UPDATE ingresos SET monto = P_MONTO, updated = NOW()
   WHERE id_pago_pension = P_ID AND estado = 'VALIDO';
  IF V_PAGO IS NOT NULL THEN
    CALL SP_AJUSTAR_RECIBO(V_PAGO, V_OK);
  END IF;
  SELECT 1 AS codigo, 'Pago modificado' AS mensaje;
END$$
DELIMITER ;

-- ---------- 7. Matrícula: genera su recibo con la forma de pago indicada ----------
-- Devuelve: 1 ok, 2 ya matriculado ese año, 3 el usuario ya existe, 4 falta usuario, 5 forma de pago no válida,
-- 0 alumno no existe.
DROP PROCEDURE IF EXISTS SP_REGISTRAR_MATRICULA;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_MATRICULA(
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

  -- Recibo de la matrícula (solo si hay algo que cobrar)
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

-- Al modificar los montos de la matrícula se ajusta también su recibo
DROP PROCEDURE IF EXISTS SP_SINCRONIZAR_CONCEPTO_MATRICULA;
DELIMITER $$
CREATE PROCEDURE SP_SINCRONIZAR_CONCEPTO_MATRICULA(IN P_MATRI INT, IN P_CONCEPTO VARCHAR(30), IN P_MONTO DECIMAL(14,2), IN P_USU INT)
BEGIN
  DECLARE V_ID, V_PAGO INT;
  DECLARE V_OK TINYINT;
  SELECT MIN(id_pago_pension) INTO V_ID FROM pago_pensiones
   WHERE id_matri = P_MATRI AND concepto = P_CONCEPTO AND estado = 'VALIDO';
  IF V_ID IS NOT NULL THEN
    UPDATE pago_pensiones SET sub_total = IFNULL(P_MONTO, 0), updated_at = NOW() WHERE id_pago_pension = V_ID;
    IF IFNULL(P_MONTO, 0) > 0 THEN
      IF EXISTS (SELECT 1 FROM ingresos WHERE id_pago_pension = V_ID AND estado = 'VALIDO') THEN
        UPDATE ingresos SET monto = P_MONTO, updated = NOW() WHERE id_pago_pension = V_ID AND estado = 'VALIDO';
      ELSE
        INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, created_at)
        VALUES (V_ID, 1, P_USU, 1, P_MONTO, P_CONCEPTO, 'VALIDO', CURDATE());
      END IF;
    ELSE
      UPDATE ingresos SET estado = 'ANULADO', motivo_anulacion = 'MONTO DE MATRÍCULA CAMBIADO A 0',
             fecha_anulacion = CURDATE(), updated = NOW()
       WHERE id_pago_pension = V_ID AND estado = 'VALIDO';
    END IF;
    SELECT id_pago INTO V_PAGO FROM pago_pensiones WHERE id_pago_pension = V_ID;
    IF V_PAGO IS NOT NULL THEN
      CALL SP_AJUSTAR_RECIBO(V_PAGO, V_OK);
    END IF;
  END IF;
END$$
DELIMITER ;

-- ---------- 8. Listado de pagos de una matrícula: con recibo y formas de pago ----------
DROP PROCEDURE IF EXISTS SP_LISTAR_PAGOS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PAGOS(IN `ID` INT)
BEGIN
  SELECT pensiones.id_nivel_academico, pensiones.mes,
         pago_pensiones.id_pago_pension, pago_pensiones.id_matri, pago_pensiones.concepto, pago_pensiones.id_pension,
         pago_pensiones.fecha_pago, DATE_FORMAT(pago_pensiones.fecha_pago, "%d-%m-%Y") AS fecha_formateada2,
         pago_pensiones.sub_total, pago_pensiones.created_at, pago_pensiones.motivo_edicion, pago_pensiones.mora_exonerada,
         matricula.id_alumno, alumnos.Id_alumno, alumnos.alum_dni, alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat,
         CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
         pago_pensiones.id_pago,
         IFNULL(CONCAT(pagos.serie, '-', LPAD(pagos.numero, 6, '0')), '') AS recibo,
         IFNULL((SELECT GROUP_CONCAT(mp.nombre ORDER BY pm.id_pago_metodo SEPARATOR ' + ')
                   FROM pagos_metodos pm INNER JOIN metodos_pago mp ON mp.id_metodo = pm.metodo_pago_id
                  WHERE pm.id_pago = pago_pensiones.id_pago), '') AS metodos,
         (SELECT COUNT(*) FROM pagos_metodos pm WHERE pm.id_pago = pago_pensiones.id_pago) AS n_metodos
    FROM pago_pensiones
    LEFT JOIN pensiones ON pensiones.id_pensiones = pago_pensiones.id_pension
    LEFT JOIN pagos ON pagos.id_pago = pago_pensiones.id_pago
   INNER JOIN matricula ON pago_pensiones.id_matri = matricula.id_matricula
   INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
   WHERE pago_pensiones.id_matri = ID AND pago_pensiones.estado = 'VALIDO'
   ORDER BY pago_pensiones.fecha_pago, pago_pensiones.id_pago_pension;
END$$
DELIMITER ;
