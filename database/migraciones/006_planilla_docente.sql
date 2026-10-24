-- =====================================================================
-- Migración 006 – Fase 6: planilla y pago a docentes
-- Contratos (tiempo completo / parcial / por horas), conceptos, planilla mensual,
-- adelantos y pagos (cada pago genera su egreso y su movimiento de caja/banco).
-- Aplicar con: mysql --default-character-set=utf8mb4 colegio2 < 006_planilla_docente.sql
-- =====================================================================

-- ---------- 1. Contratos ----------
CREATE TABLE IF NOT EXISTS tipos_contrato (
  id_tipo_contrato INT AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(40) NOT NULL UNIQUE,
  calculo          ENUM('MENSUAL','POR_HORA') NOT NULL,
  estado           ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipos_contrato (nombre, calculo) VALUES
  ('TIEMPO COMPLETO', 'MENSUAL'), ('TIEMPO PARCIAL', 'MENSUAL'), ('POR HORAS', 'POR_HORA')
ON DUPLICATE KEY UPDATE calculo = VALUES(calculo);

CREATE TABLE IF NOT EXISTS contratos_docente (
  id_contrato      INT AUTO_INCREMENT PRIMARY KEY,
  docente_id       INT NOT NULL,
  tipo_contrato_id INT NOT NULL,
  fecha_inicio     DATE NOT NULL,
  fecha_fin        DATE NULL,
  sueldo_base      DECIMAL(14,2) NOT NULL DEFAULT 0,   -- tiempo completo / parcial: sueldo mensual
  tarifa_hora      DECIMAL(14,2) NOT NULL DEFAULT 0,   -- por horas
  horas_semanales  DECIMAL(5,2)  NOT NULL DEFAULT 0,
  metodo_pago_id   INT NOT NULL,                       -- cómo se le paga: efectivo, transferencia...
  banco_docente    VARCHAR(100) NULL,
  cuenta_docente   VARCHAR(40)  NULL,
  observacion      VARCHAR(255) NULL,
  estado           ENUM('ACTIVO','FINALIZADO') NOT NULL DEFAULT 'ACTIVO',
  usuario_id       INT NULL,
  created_at       DATETIME NOT NULL,
  updated_at       DATETIME NULL,
  INDEX idx_contrato_docente (docente_id, estado),
  CONSTRAINT fk_contrato_docente FOREIGN KEY (docente_id) REFERENCES docentes(Id_docente),
  CONSTRAINT fk_contrato_tipo    FOREIGN KEY (tipo_contrato_id) REFERENCES tipos_contrato(id_tipo_contrato),
  CONSTRAINT fk_contrato_metodo  FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 2. Conceptos de planilla ----------
CREATE TABLE IF NOT EXISTS conceptos_planilla (
  id_concepto   INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(80) NOT NULL UNIQUE,
  tipo          ENUM('INGRESO','DESCUENTO') NOT NULL,
  forma         ENUM('MONTO','PORCENTAJE') NOT NULL,      -- porcentaje sobre el sueldo/base del mes
  valor_defecto DECIMAL(14,2) NOT NULL DEFAULT 0,         -- > 0: se aplica solo al generar la planilla
  estado        ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO conceptos_planilla (nombre, tipo, forma, valor_defecto) VALUES
  ('BONO', 'INGRESO', 'MONTO', 0),
  ('HORAS EXTRA', 'INGRESO', 'MONTO', 0),
  ('MOVILIDAD', 'INGRESO', 'MONTO', 0),
  ('TARDANZAS', 'DESCUENTO', 'MONTO', 0),
  ('FALTAS', 'DESCUENTO', 'MONTO', 0),
  -- Aporte de ley (pensión/seguro): el porcentaje depende del país; se configura en la pantalla
  ('APORTE DE LEY', 'DESCUENTO', 'PORCENTAJE', 0)
ON DUPLICATE KEY UPDATE tipo = VALUES(tipo);

-- ---------- 3. Planilla mensual ----------
CREATE TABLE IF NOT EXISTS planillas (
  id_planilla      INT AUTO_INCREMENT PRIMARY KEY,
  anio             SMALLINT NOT NULL,
  mes              TINYINT  NOT NULL,
  estado           ENUM('BORRADOR','APROBADA','PAGADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
  total_bruto      DECIMAL(14,2) NOT NULL DEFAULT 0,       -- base + ingresos
  total_descuentos DECIMAL(14,2) NOT NULL DEFAULT 0,       -- descuentos + adelantos
  total_neto       DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_pagado     DECIMAL(14,2) NOT NULL DEFAULT 0,
  observacion      VARCHAR(255) NULL,
  usuario_id       INT NULL,
  aprobado_por     INT NULL,
  fecha_aprobacion DATETIME NULL,
  motivo_anulacion VARCHAR(255) NULL,
  created_at       DATETIME NOT NULL,
  INDEX idx_planilla_periodo (anio, mes, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planilla_detalle (
  id_detalle     INT AUTO_INCREMENT PRIMARY KEY,
  id_planilla    INT NOT NULL,
  contrato_id    INT NOT NULL,
  docente_id     INT NOT NULL,
  tipo_contrato  VARCHAR(40) NOT NULL,                    -- copia: el contrato puede cambiar después
  calculo        ENUM('MENSUAL','POR_HORA') NOT NULL,
  tarifa_hora    DECIMAL(14,2) NOT NULL DEFAULT 0,
  horas          DECIMAL(7,2)  NOT NULL DEFAULT 0,
  base           DECIMAL(14,2) NOT NULL DEFAULT 0,
  ingresos       DECIMAL(14,2) NOT NULL DEFAULT 0,
  descuentos     DECIMAL(14,2) NOT NULL DEFAULT 0,
  adelantos      DECIMAL(14,2) NOT NULL DEFAULT 0,
  neto           DECIMAL(14,2) NOT NULL DEFAULT 0,
  pagado         DECIMAL(14,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uk_detalle (id_planilla, docente_id),
  CONSTRAINT fk_det_planilla FOREIGN KEY (id_planilla) REFERENCES planillas(id_planilla),
  CONSTRAINT fk_det_contrato FOREIGN KEY (contrato_id) REFERENCES contratos_docente(id_contrato),
  CONSTRAINT fk_det_docente  FOREIGN KEY (docente_id) REFERENCES docentes(Id_docente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planilla_detalle_conceptos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  id_detalle  INT NOT NULL,
  concepto_id INT NOT NULL,
  valor       DECIMAL(14,2) NOT NULL,                    -- monto o porcentaje según el concepto
  monto       DECIMAL(14,2) NOT NULL,                    -- monto calculado
  CONSTRAINT fk_pdc_detalle  FOREIGN KEY (id_detalle) REFERENCES planilla_detalle(id_detalle) ON DELETE CASCADE,
  CONSTRAINT fk_pdc_concepto FOREIGN KEY (concepto_id) REFERENCES conceptos_planilla(id_concepto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 4. Pagos y adelantos a docentes ----------
CREATE TABLE IF NOT EXISTS pagos_docentes (
  id_pago_docente      INT AUTO_INCREMENT PRIMARY KEY,
  tipo                 ENUM('PLANILLA','ADELANTO') NOT NULL,
  id_detalle           INT NULL,                         -- PLANILLA: detalle que se paga
  id_detalle_descuento INT NULL,                         -- ADELANTO: detalle de la planilla que lo descuenta
  docente_id           INT NOT NULL,
  fecha                DATETIME NOT NULL,
  monto                DECIMAL(14,2) NOT NULL,
  metodo_pago_id       INT NOT NULL,
  cuenta_id            INT NULL,
  referencia           VARCHAR(60) NULL,
  observacion          VARCHAR(255) NULL,
  egreso_id            INT NULL,
  usuario_id           INT NULL,
  estado               ENUM('VALIDO','ANULADO') NOT NULL DEFAULT 'VALIDO',
  motivo_anulacion     VARCHAR(255) NULL,
  INDEX idx_pd_docente (docente_id, tipo, estado),
  CONSTRAINT fk_pd_detalle  FOREIGN KEY (id_detalle) REFERENCES planilla_detalle(id_detalle),
  CONSTRAINT fk_pd_docente  FOREIGN KEY (docente_id) REFERENCES docentes(Id_docente),
  CONSTRAINT fk_pd_metodo   FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo),
  CONSTRAINT fk_pd_cuenta   FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El egreso de un pago de planilla se anula desde Planilla, no desde Egresos
ALTER TABLE egresos ADD COLUMN pago_docente_id INT NULL AFTER adjunto;

-- ---------- 5. Egreso de remuneraciones (lo usan pagos y adelantos) ----------
-- Valida forma de pago y efectivo disponible, registra el egreso en "REMUNERACIONES DOCENTES" y su movimiento.
DROP PROCEDURE IF EXISTS SP_EGRESO_REMUNERACION;
DELIMITER $$
CREATE PROCEDURE SP_EGRESO_REMUNERACION(IN P_MONTO DECIMAL(14,2), IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60),
                                        IN P_BENEF VARCHAR(150), IN P_OBS VARCHAR(255), IN P_USU INT,
                                        OUT O_ID INT, OUT O_MENSAJE VARCHAR(255))
proc: BEGIN
  DECLARE V_IND, V_CTA INT;
  DECLARE V_OK TINYINT;
  DECLARE V_SALDO DECIMAL(14,2);
  SET O_ID = NULL;
  SELECT id_indicadores INTO V_IND FROM indicadores WHERE codigo = '5.1.1' LIMIT 1;
  CALL SP_VALIDAR_FORMA_PAGO(P_METODO, P_CUENTA, P_REF, O_MENSAJE, V_CTA);
  IF O_MENSAJE IS NOT NULL THEN LEAVE proc; END IF;
  IF V_CTA IS NOT NULL THEN
    CALL SP_VALIDAR_SALIDA(V_CTA, P_MONTO, 'EGRESO', 0, V_OK, V_SALDO);
    IF V_OK = 0 THEN
      SET O_MENSAJE = CONCAT('Saldo insuficiente en la caja (saldo actual ', V_SALDO, ')');
      LEAVE proc;
    END IF;
  END IF;
  INSERT INTO egresos (id_indicador, id_user, cantidad, monto, observacion, metodo_pago_id, cuenta_id, referencia, beneficiario,
                       estado, created_at, updated)
  VALUES (V_IND, P_USU, 1, P_MONTO, P_OBS, P_METODO, V_CTA, NULLIF(TRIM(P_REF), ''), P_BENEF, 'VALIDO', P_FECHA, NULL);
  SET O_ID = LAST_INSERT_ID();
  CALL SP_SINCRONIZAR_MOVIMIENTO('EGRESO', O_ID, V_CTA, 'SALIDA', P_MONTO, TIMESTAMP(P_FECHA, CURTIME()),
                                 CONCAT('Remuneración: ', P_BENEF, ' - ', P_OBS), P_REF, P_USU);
END$$
DELIMITER ;

-- ---------- 6. Contratos ----------
DROP PROCEDURE IF EXISTS SP_LISTAR_CONTRATOS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_CONTRATOS()
BEGIN
  SELECT d.Id_docente, d.docente_dni, CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente, d.docente_estatus,
         c.id_contrato, c.tipo_contrato_id, t.nombre AS tipo_contrato, t.calculo, c.fecha_inicio,
         DATE_FORMAT(c.fecha_inicio, '%d-%m-%Y') AS fecha_inicio_txt, c.sueldo_base, c.tarifa_hora, c.horas_semanales,
         c.metodo_pago_id, mp.nombre AS metodo, c.banco_docente, c.cuenta_docente, c.observacion,
         (SELECT IFNULL(SUM(p.monto), 0) FROM pagos_docentes p
           WHERE p.docente_id = d.Id_docente AND p.tipo = 'ADELANTO' AND p.estado = 'VALIDO' AND p.id_detalle_descuento IS NULL) AS adelantos_pendientes
    FROM docentes d
    LEFT JOIN contratos_docente c ON c.docente_id = d.Id_docente AND c.estado = 'ACTIVO'
    LEFT JOIN tipos_contrato t ON t.id_tipo_contrato = c.tipo_contrato_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = c.metodo_pago_id
   WHERE d.docente_estatus = 'ACTIVO' OR c.id_contrato IS NOT NULL
   ORDER BY d.docente_apelli, d.docente_nombre;
END$$
DELIMITER ;

-- Un contrato nuevo reemplaza al activo (que queda FINALIZADO el día anterior al inicio del nuevo)
DROP PROCEDURE IF EXISTS SP_GUARDAR_CONTRATO;
DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_CONTRATO(IN P_ID INT, IN P_DOCENTE INT, IN P_TIPO INT, IN P_INICIO DATE, IN P_SUELDO DECIMAL(14,2),
                                     IN P_TARIFA DECIMAL(14,2), IN P_HORAS DECIMAL(5,2), IN P_METODO INT, IN P_BANCO VARCHAR(100),
                                     IN P_CUENTA VARCHAR(40), IN P_OBS VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_CALCULO VARCHAR(10);
  DECLARE V_DOC, V_MET, V_ACTUAL INT;
  DECLARE V_INICIO_ACTUAL DATE;
  SELECT Id_docente INTO V_DOC FROM docentes WHERE Id_docente = P_DOCENTE AND docente_estatus = 'ACTIVO';
  IF V_DOC IS NULL THEN SELECT 0 AS codigo, 'El docente no existe o está inactivo' AS mensaje; LEAVE proc; END IF;
  SELECT calculo INTO V_CALCULO FROM tipos_contrato WHERE id_tipo_contrato = P_TIPO AND estado = 'ACTIVO';
  IF V_CALCULO IS NULL THEN SELECT 0 AS codigo, 'Tipo de contrato no válido' AS mensaje; LEAVE proc; END IF;
  SELECT id_metodo INTO V_MET FROM metodos_pago WHERE id_metodo = P_METODO AND estado = 'ACTIVO';
  IF V_MET IS NULL THEN SELECT 0 AS codigo, 'Forma de pago no válida' AS mensaje; LEAVE proc; END IF;
  IF P_INICIO IS NULL THEN SELECT 0 AS codigo, 'Indique la fecha de inicio' AS mensaje; LEAVE proc; END IF;
  IF V_CALCULO = 'MENSUAL' AND IFNULL(P_SUELDO, 0) <= 0 THEN SELECT 0 AS codigo, 'Indique el sueldo mensual' AS mensaje; LEAVE proc; END IF;
  IF V_CALCULO = 'POR_HORA' AND IFNULL(P_TARIFA, 0) <= 0 THEN SELECT 0 AS codigo, 'Indique la tarifa por hora' AS mensaje; LEAVE proc; END IF;

  IF P_ID IS NULL THEN
    SELECT id_contrato, fecha_inicio INTO V_ACTUAL, V_INICIO_ACTUAL
      FROM contratos_docente WHERE docente_id = P_DOCENTE AND estado = 'ACTIVO' LIMIT 1;
    IF V_ACTUAL IS NOT NULL THEN
      IF P_INICIO <= V_INICIO_ACTUAL THEN
        SELECT 0 AS codigo, 'El docente ya tiene un contrato activo desde esa fecha: edítelo o use una fecha de inicio posterior' AS mensaje; LEAVE proc;
      END IF;
      UPDATE contratos_docente SET estado = 'FINALIZADO', fecha_fin = DATE_SUB(P_INICIO, INTERVAL 1 DAY), updated_at = NOW()
       WHERE id_contrato = V_ACTUAL;
    END IF;
    INSERT INTO contratos_docente(docente_id, tipo_contrato_id, fecha_inicio, sueldo_base, tarifa_hora, horas_semanales, metodo_pago_id,
                                  banco_docente, cuenta_docente, observacion, estado, usuario_id, created_at)
    VALUES (P_DOCENTE, P_TIPO, P_INICIO, IF(V_CALCULO = 'MENSUAL', P_SUELDO, 0), IF(V_CALCULO = 'POR_HORA', P_TARIFA, 0), IFNULL(P_HORAS, 0),
            P_METODO, NULLIF(TRIM(P_BANCO), ''), NULLIF(TRIM(P_CUENTA), ''), NULLIF(TRIM(P_OBS), ''), 'ACTIVO', P_USU, NOW());
    SELECT 1 AS codigo, 'Contrato registrado' AS mensaje;
  ELSE
    UPDATE contratos_docente SET tipo_contrato_id = P_TIPO, fecha_inicio = P_INICIO,
           sueldo_base = IF(V_CALCULO = 'MENSUAL', P_SUELDO, 0), tarifa_hora = IF(V_CALCULO = 'POR_HORA', P_TARIFA, 0),
           horas_semanales = IFNULL(P_HORAS, 0), metodo_pago_id = P_METODO, banco_docente = NULLIF(TRIM(P_BANCO), ''),
           cuenta_docente = NULLIF(TRIM(P_CUENTA), ''), observacion = NULLIF(TRIM(P_OBS), ''), updated_at = NOW()
     WHERE id_contrato = P_ID AND docente_id = P_DOCENTE AND estado = 'ACTIVO';
    SELECT IF(ROW_COUNT() > 0, 1, 2) AS codigo, IF(ROW_COUNT() > 0, 'Contrato actualizado', 'El contrato no existe o ya finalizó') AS mensaje;
  END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_FINALIZAR_CONTRATO;
DELIMITER $$
CREATE PROCEDURE SP_FINALIZAR_CONTRATO(IN P_ID INT, IN P_FIN DATE)
BEGIN
  UPDATE contratos_docente SET estado = 'FINALIZADO', fecha_fin = P_FIN, updated_at = NOW()
   WHERE id_contrato = P_ID AND estado = 'ACTIVO' AND P_FIN >= fecha_inicio;
  SELECT IF(ROW_COUNT() > 0, 1, 0) AS codigo,
         IF(ROW_COUNT() > 0, 'Contrato finalizado', 'No se pudo finalizar (la fecha de fin debe ser posterior al inicio)') AS mensaje;
END$$
DELIMITER ;

-- ---------- 7. Planilla: generar, recalcular, editar, aprobar, anular ----------
DROP PROCEDURE IF EXISTS SP_RECALCULAR_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_RECALCULAR_PLANILLA(IN P_PLA INT)
BEGIN
  -- Conceptos en porcentaje se calculan sobre la base del mes
  UPDATE planilla_detalle_conceptos c
   INNER JOIN conceptos_planilla k ON k.id_concepto = c.concepto_id
   INNER JOIN planilla_detalle d ON d.id_detalle = c.id_detalle
     SET c.monto = IF(k.forma = 'PORCENTAJE', ROUND(d.base * c.valor / 100, 2), c.valor)
   WHERE d.id_planilla = P_PLA;
  UPDATE planilla_detalle d SET
      d.ingresos = (SELECT IFNULL(SUM(c.monto), 0) FROM planilla_detalle_conceptos c INNER JOIN conceptos_planilla k ON k.id_concepto = c.concepto_id
                     WHERE c.id_detalle = d.id_detalle AND k.tipo = 'INGRESO'),
      d.descuentos = (SELECT IFNULL(SUM(c.monto), 0) FROM planilla_detalle_conceptos c INNER JOIN conceptos_planilla k ON k.id_concepto = c.concepto_id
                       WHERE c.id_detalle = d.id_detalle AND k.tipo = 'DESCUENTO'),
      d.adelantos = (SELECT IFNULL(SUM(p.monto), 0) FROM pagos_docentes p
                      WHERE p.tipo = 'ADELANTO' AND p.estado = 'VALIDO' AND p.id_detalle_descuento = d.id_detalle),
      d.pagado = (SELECT IFNULL(SUM(p.monto), 0) FROM pagos_docentes p
                   WHERE p.tipo = 'PLANILLA' AND p.estado = 'VALIDO' AND p.id_detalle = d.id_detalle)
   WHERE d.id_planilla = P_PLA;
  UPDATE planilla_detalle SET neto = base + ingresos - descuentos - adelantos WHERE id_planilla = P_PLA;
  UPDATE planillas p SET
      total_bruto = (SELECT IFNULL(SUM(base + ingresos), 0) FROM planilla_detalle WHERE id_planilla = P_PLA),
      total_descuentos = (SELECT IFNULL(SUM(descuentos + adelantos), 0) FROM planilla_detalle WHERE id_planilla = P_PLA),
      total_neto = (SELECT IFNULL(SUM(neto), 0) FROM planilla_detalle WHERE id_planilla = P_PLA),
      total_pagado = (SELECT IFNULL(SUM(pagado), 0) FROM planilla_detalle WHERE id_planilla = P_PLA)
   WHERE p.id_planilla = P_PLA;
END$$
DELIMITER ;

-- Crea la planilla del mes con todos los contratos vigentes; descuenta los adelantos pendientes.
-- Devuelve 1 ok, 2 ya existe la planilla del mes, 3 no hay contratos vigentes, 0 dato inválido.
DROP PROCEDURE IF EXISTS SP_GENERAR_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_GENERAR_PLANILLA(IN P_ANIO INT, IN P_MES INT, IN P_USU INT)
proc: BEGIN
  DECLARE V_INI, V_FIN DATE;
  DECLARE V_EXISTE, V_CONTRATOS, V_ID INT;
  IF P_MES NOT BETWEEN 1 AND 12 OR P_ANIO NOT BETWEEN 2000 AND 2100 THEN
    SELECT 0 AS codigo, 'Período no válido' AS mensaje, 0 AS id; LEAVE proc;
  END IF;
  SELECT COUNT(*) INTO V_EXISTE FROM planillas WHERE anio = P_ANIO AND mes = P_MES AND estado <> 'ANULADA';
  IF V_EXISTE > 0 THEN SELECT 2 AS codigo, 'Ya existe la planilla de ese mes' AS mensaje, 0 AS id; LEAVE proc; END IF;
  SET V_INI = DATE_ADD(MAKEDATE(P_ANIO, 1), INTERVAL P_MES - 1 MONTH);
  SET V_FIN = LAST_DAY(V_INI);

  -- Contrato vigente en el mes (si cambió en el mes, el más reciente)
  DROP TEMPORARY TABLE IF EXISTS tmp_contratos_mes;
  CREATE TEMPORARY TABLE tmp_contratos_mes AS
    SELECT MAX(c.id_contrato) AS id_contrato
      FROM contratos_docente c INNER JOIN docentes d ON d.Id_docente = c.docente_id
     WHERE c.fecha_inicio <= V_FIN AND (c.fecha_fin IS NULL OR c.fecha_fin >= V_INI) AND d.docente_estatus = 'ACTIVO'
     GROUP BY c.docente_id;
  SELECT COUNT(*) INTO V_CONTRATOS FROM tmp_contratos_mes;
  IF V_CONTRATOS = 0 THEN SELECT 3 AS codigo, 'No hay contratos vigentes en ese mes' AS mensaje, 0 AS id; LEAVE proc; END IF;

  INSERT INTO planillas(anio, mes, estado, usuario_id, created_at) VALUES (P_ANIO, P_MES, 'BORRADOR', P_USU, NOW());
  SET V_ID = LAST_INSERT_ID();
  -- Por horas: se proponen horas semanales x 4 semanas (se ajustan en el borrador)
  INSERT INTO planilla_detalle(id_planilla, contrato_id, docente_id, tipo_contrato, calculo, tarifa_hora, horas, base)
  SELECT V_ID, c.id_contrato, c.docente_id, t.nombre, t.calculo, c.tarifa_hora,
         IF(t.calculo = 'POR_HORA', c.horas_semanales * 4, 0),
         IF(t.calculo = 'POR_HORA', ROUND(c.horas_semanales * 4 * c.tarifa_hora, 2), c.sueldo_base)
    FROM tmp_contratos_mes x
    INNER JOIN contratos_docente c ON c.id_contrato = x.id_contrato
    INNER JOIN tipos_contrato t ON t.id_tipo_contrato = c.tipo_contrato_id;
  DROP TEMPORARY TABLE IF EXISTS tmp_contratos_mes;

  -- Conceptos que se aplican siempre (valor por defecto > 0), p. ej. aporte de ley
  INSERT INTO planilla_detalle_conceptos(id_detalle, concepto_id, valor, monto)
  SELECT d.id_detalle, k.id_concepto, k.valor_defecto, 0
    FROM planilla_detalle d CROSS JOIN conceptos_planilla k
   WHERE d.id_planilla = V_ID AND k.estado = 'ACTIVO' AND k.valor_defecto > 0;
  -- Adelantos pagados hasta fin de mes que aún no se descontaron
  UPDATE pagos_docentes p INNER JOIN planilla_detalle d ON d.docente_id = p.docente_id AND d.id_planilla = V_ID
     SET p.id_detalle_descuento = d.id_detalle
   WHERE p.tipo = 'ADELANTO' AND p.estado = 'VALIDO' AND p.id_detalle_descuento IS NULL AND DATE(p.fecha) <= V_FIN;
  CALL SP_RECALCULAR_PLANILLA(V_ID);
  SELECT 1 AS codigo, CONCAT('Planilla generada con ', V_CONTRATOS, ' docentes') AS mensaje, V_ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_PLANILLAS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PLANILLAS(IN P_ANIO INT)
BEGIN
  SELECT p.id_planilla, p.anio, p.mes,
         ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_nombre,
         p.estado, p.total_bruto, p.total_descuentos, p.total_neto, p.total_pagado, p.motivo_anulacion,
         (SELECT COUNT(*) FROM planilla_detalle d WHERE d.id_planilla = p.id_planilla) AS docentes,
         u.usu_usuario AS generado_por, a.usu_usuario AS aprobado_por, DATE_FORMAT(p.fecha_aprobacion, '%d-%m-%Y %H:%i') AS fecha_aprobacion
    FROM planillas p
    LEFT JOIN usuario u ON u.usu_id = p.usuario_id
    LEFT JOIN usuario a ON a.usu_id = p.aprobado_por
   WHERE p.anio = P_ANIO
   ORDER BY p.mes DESC, p.id_planilla DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_PLANILLA_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PLANILLA_DETALLE(IN P_PLA INT)
BEGIN
  SELECT d.*, CONCAT_WS(' ', o.docente_nombre, o.docente_apelli) AS docente, o.docente_dni,
         c.metodo_pago_id, mp.nombre AS metodo, c.banco_docente, c.cuenta_docente, (d.neto - d.pagado) AS saldo, p.estado AS estado_planilla,
         IFNULL((SELECT GROUP_CONCAT(CONCAT(k.nombre, ' ', FORMAT(x.monto, 2)) SEPARATOR ', ')
                   FROM planilla_detalle_conceptos x INNER JOIN conceptos_planilla k ON k.id_concepto = x.concepto_id
                  WHERE x.id_detalle = d.id_detalle), '') AS conceptos
    FROM planilla_detalle d
    INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN docentes o ON o.Id_docente = d.docente_id
    INNER JOIN contratos_docente c ON c.id_contrato = d.contrato_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = c.metodo_pago_id
   WHERE d.id_planilla = P_PLA
   ORDER BY o.docente_apelli, o.docente_nombre;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_CONCEPTOS_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_CONCEPTOS_DETALLE(IN P_DET INT)
BEGIN
  SELECT x.id, x.concepto_id, k.nombre, k.tipo, k.forma, x.valor, x.monto
    FROM planilla_detalle_conceptos x INNER JOIN conceptos_planilla k ON k.id_concepto = x.concepto_id
   WHERE x.id_detalle = P_DET ORDER BY k.tipo DESC, k.nombre;
END$$
DELIMITER ;

-- Ajustes del borrador: horas (por horas) o base (mensual, p. ej. proporcional si entró a mitad de mes)
DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_ACTUALIZAR_DETALLE(IN P_DET INT, IN P_HORAS DECIMAL(7,2), IN P_BASE DECIMAL(14,2))
proc: BEGIN
  DECLARE V_PLA INT;
  DECLARE V_CALCULO VARCHAR(10);
  SELECT d.id_planilla, d.calculo INTO V_PLA, V_CALCULO FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla
   WHERE d.id_detalle = P_DET AND p.estado = 'BORRADOR';
  IF V_PLA IS NULL THEN SELECT 2 AS codigo, 'Solo se modifica una planilla en BORRADOR' AS mensaje; LEAVE proc; END IF;
  IF V_CALCULO = 'POR_HORA' THEN
    IF P_HORAS IS NULL OR P_HORAS < 0 THEN SELECT 0 AS codigo, 'Horas no válidas' AS mensaje; LEAVE proc; END IF;
    UPDATE planilla_detalle SET horas = P_HORAS, base = ROUND(P_HORAS * tarifa_hora, 2) WHERE id_detalle = P_DET;
  ELSE
    IF P_BASE IS NULL OR P_BASE < 0 THEN SELECT 0 AS codigo, 'Sueldo base no válido' AS mensaje; LEAVE proc; END IF;
    UPDATE planilla_detalle SET base = P_BASE WHERE id_detalle = P_DET;
  END IF;
  CALL SP_RECALCULAR_PLANILLA(V_PLA);
  SELECT 1 AS codigo, 'Actualizado' AS mensaje;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_AGREGAR_CONCEPTO_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_AGREGAR_CONCEPTO_DETALLE(IN P_DET INT, IN P_CONCEPTO INT, IN P_VALOR DECIMAL(14,2))
proc: BEGIN
  DECLARE V_PLA INT;
  DECLARE V_FORMA VARCHAR(12);
  SELECT d.id_planilla INTO V_PLA FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla
   WHERE d.id_detalle = P_DET AND p.estado = 'BORRADOR';
  IF V_PLA IS NULL THEN SELECT 2 AS codigo, 'Solo se modifica una planilla en BORRADOR' AS mensaje; LEAVE proc; END IF;
  SELECT forma INTO V_FORMA FROM conceptos_planilla WHERE id_concepto = P_CONCEPTO AND estado = 'ACTIVO';
  IF V_FORMA IS NULL THEN SELECT 0 AS codigo, 'Concepto no válido' AS mensaje; LEAVE proc; END IF;
  IF P_VALOR IS NULL OR P_VALOR <= 0 OR (V_FORMA = 'PORCENTAJE' AND P_VALOR > 100) THEN
    SELECT 0 AS codigo, 'Valor no válido' AS mensaje; LEAVE proc;
  END IF;
  INSERT INTO planilla_detalle_conceptos(id_detalle, concepto_id, valor, monto) VALUES (P_DET, P_CONCEPTO, P_VALOR, 0);
  CALL SP_RECALCULAR_PLANILLA(V_PLA);
  SELECT 1 AS codigo, 'Concepto agregado' AS mensaje;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_QUITAR_CONCEPTO_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_QUITAR_CONCEPTO_DETALLE(IN P_ID INT)
proc: BEGIN
  DECLARE V_PLA INT;
  SELECT d.id_planilla INTO V_PLA FROM planilla_detalle_conceptos x
   INNER JOIN planilla_detalle d ON d.id_detalle = x.id_detalle INNER JOIN planillas p ON p.id_planilla = d.id_planilla
   WHERE x.id = P_ID AND p.estado = 'BORRADOR';
  IF V_PLA IS NULL THEN SELECT 2 AS codigo, 'Solo se modifica una planilla en BORRADOR' AS mensaje; LEAVE proc; END IF;
  DELETE FROM planilla_detalle_conceptos WHERE id = P_ID;
  CALL SP_RECALCULAR_PLANILLA(V_PLA);
  SELECT 1 AS codigo, 'Concepto quitado' AS mensaje;
END$$
DELIMITER ;

-- La aprueba el ADMINISTRADOR (permiso en controller/_permisos.php). No se aprueba con netos negativos.
DROP PROCEDURE IF EXISTS SP_APROBAR_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_APROBAR_PLANILLA(IN P_PLA INT, IN P_USU INT)
proc: BEGIN
  DECLARE V_ESTADO VARCHAR(10);
  DECLARE V_NEG INT;
  SELECT estado INTO V_ESTADO FROM planillas WHERE id_planilla = P_PLA;
  IF V_ESTADO IS NULL OR V_ESTADO <> 'BORRADOR' THEN SELECT 2 AS codigo, 'Solo se aprueba una planilla en BORRADOR' AS mensaje; LEAVE proc; END IF;
  CALL SP_RECALCULAR_PLANILLA(P_PLA);
  SELECT COUNT(*) INTO V_NEG FROM planilla_detalle WHERE id_planilla = P_PLA AND neto < 0;
  IF V_NEG > 0 THEN
    SELECT 3 AS codigo, CONCAT(V_NEG, ' docente(s) con neto negativo (descuentos o adelantos mayores al sueldo): revise antes de aprobar') AS mensaje;
    LEAVE proc;
  END IF;
  UPDATE planillas SET estado = 'APROBADA', aprobado_por = P_USU, fecha_aprobacion = NOW() WHERE id_planilla = P_PLA;
  SELECT 1 AS codigo, 'Planilla aprobada' AS mensaje;
END$$
DELIMITER ;

-- Volver a borrador una planilla aprobada que aún no tiene pagos (para corregirla)
DROP PROCEDURE IF EXISTS SP_REABRIR_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_REABRIR_PLANILLA(IN P_PLA INT)
proc: BEGIN
  DECLARE V_PAGOS INT;
  SELECT COUNT(*) INTO V_PAGOS FROM pagos_docentes p INNER JOIN planilla_detalle d ON d.id_detalle = p.id_detalle
   WHERE d.id_planilla = P_PLA AND p.tipo = 'PLANILLA' AND p.estado = 'VALIDO';
  IF V_PAGOS > 0 THEN SELECT 2 AS codigo, 'La planilla ya tiene pagos: anúlelos primero' AS mensaje; LEAVE proc; END IF;
  UPDATE planillas SET estado = 'BORRADOR', aprobado_por = NULL, fecha_aprobacion = NULL WHERE id_planilla = P_PLA AND estado = 'APROBADA';
  SELECT IF(ROW_COUNT() > 0, 1, 2) AS codigo, IF(ROW_COUNT() > 0, 'Planilla en borrador', 'Solo se reabre una planilla APROBADA') AS mensaje;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_ANULAR_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_PLANILLA(IN P_PLA INT, IN P_MOTIVO VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_PAGOS INT;
  IF TRIM(IFNULL(P_MOTIVO, '')) = '' THEN SELECT 0 AS codigo, 'Indique el motivo de la anulación' AS mensaje; LEAVE proc; END IF;
  SELECT COUNT(*) INTO V_PAGOS FROM pagos_docentes p INNER JOIN planilla_detalle d ON d.id_detalle = p.id_detalle
   WHERE d.id_planilla = P_PLA AND p.tipo = 'PLANILLA' AND p.estado = 'VALIDO';
  IF V_PAGOS > 0 THEN SELECT 2 AS codigo, 'La planilla tiene pagos: anúlelos primero' AS mensaje; LEAVE proc; END IF;
  -- Los adelantos que descontaba quedan pendientes para la próxima planilla
  UPDATE pagos_docentes p INNER JOIN planilla_detalle d ON d.id_detalle = p.id_detalle_descuento
     SET p.id_detalle_descuento = NULL
   WHERE d.id_planilla = P_PLA AND p.tipo = 'ADELANTO';
  UPDATE planillas SET estado = 'ANULADA', motivo_anulacion = P_MOTIVO WHERE id_planilla = P_PLA AND estado IN ('BORRADOR', 'APROBADA');
  SELECT IF(ROW_COUNT() > 0, 1, 2) AS codigo, IF(ROW_COUNT() > 0, 'Planilla anulada', 'La planilla no se puede anular') AS mensaje;
END$$
DELIMITER ;

-- ---------- 8. Pagos y adelantos ----------
-- Paga (total o parcialmente) el neto de un docente de una planilla APROBADA.
DROP PROCEDURE IF EXISTS SP_PAGAR_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_PAGAR_DETALLE(IN P_DET INT, IN P_MONTO DECIMAL(14,2), IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT,
                                  IN P_REF VARCHAR(60), IN P_USU INT)
proc: BEGIN
  DECLARE V_PLA, V_DOC, V_EG, V_PD, V_PEND INT;
  DECLARE V_ESTADO VARCHAR(10);
  DECLARE V_SALDO DECIMAL(14,2);
  DECLARE V_NOMBRE VARCHAR(150);
  DECLARE V_PERIODO VARCHAR(30);
  DECLARE V_MENSAJE VARCHAR(255);
  SELECT d.id_planilla, d.docente_id, d.neto - d.pagado, p.estado,
         CONCAT('PLANILLA ', ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'), ' ', p.anio)
    INTO V_PLA, V_DOC, V_SALDO, V_ESTADO, V_PERIODO
    FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla WHERE d.id_detalle = P_DET;
  IF V_PLA IS NULL THEN SELECT 0 AS codigo, 'Registro no encontrado' AS mensaje; LEAVE proc; END IF;
  IF V_ESTADO <> 'APROBADA' THEN SELECT 2 AS codigo, 'Solo se paga una planilla APROBADA' AS mensaje; LEAVE proc; END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje; LEAVE proc; END IF;
  IF P_MONTO > V_SALDO THEN SELECT 3 AS codigo, CONCAT('El monto excede lo pendiente (', V_SALDO, ')') AS mensaje; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje; LEAVE proc; END IF;
  SELECT CONCAT_WS(' ', docente_nombre, docente_apelli) INTO V_NOMBRE FROM docentes WHERE Id_docente = V_DOC;

  CALL SP_EGRESO_REMUNERACION(P_MONTO, P_FECHA, P_METODO, P_CUENTA, P_REF, V_NOMBRE, V_PERIODO, P_USU, V_EG, V_MENSAJE);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje; LEAVE proc; END IF;
  INSERT INTO pagos_docentes(tipo, id_detalle, docente_id, fecha, monto, metodo_pago_id, cuenta_id, referencia, observacion, egreso_id, usuario_id, estado)
  SELECT 'PLANILLA', P_DET, V_DOC, TIMESTAMP(P_FECHA, CURTIME()), P_MONTO, P_METODO, cuenta_id, referencia, V_PERIODO, V_EG, P_USU, 'VALIDO'
    FROM egresos WHERE id_egresos = V_EG;
  SET V_PD = LAST_INSERT_ID();
  UPDATE egresos SET pago_docente_id = V_PD WHERE id_egresos = V_EG;
  CALL SP_RECALCULAR_PLANILLA(V_PLA);
  SELECT COUNT(*) INTO V_PEND FROM planilla_detalle WHERE id_planilla = V_PLA AND pagado < neto;
  IF V_PEND = 0 THEN UPDATE planillas SET estado = 'PAGADA' WHERE id_planilla = V_PLA; END IF;
  SELECT 1 AS codigo, 'Pago registrado' AS mensaje, V_PD AS id;
END$$
DELIMITER ;

-- Adelanto de sueldo: egreso inmediato; se descuenta en la próxima planilla (o en la del mes si está en borrador)
DROP PROCEDURE IF EXISTS SP_REGISTRAR_ADELANTO;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_ADELANTO(IN P_DOC INT, IN P_MONTO DECIMAL(14,2), IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT,
                                       IN P_REF VARCHAR(60), IN P_OBS VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_CONTRATO, V_EG, V_PD, V_DET, V_PLA INT;
  DECLARE V_NOMBRE VARCHAR(150);
  DECLARE V_MENSAJE VARCHAR(255);
  SELECT id_contrato INTO V_CONTRATO FROM contratos_docente WHERE docente_id = P_DOC AND estado = 'ACTIVO' LIMIT 1;
  IF V_CONTRATO IS NULL THEN SELECT 0 AS codigo, 'El docente no tiene contrato activo' AS mensaje; LEAVE proc; END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje; LEAVE proc; END IF;
  SELECT CONCAT_WS(' ', docente_nombre, docente_apelli) INTO V_NOMBRE FROM docentes WHERE Id_docente = P_DOC;
  CALL SP_EGRESO_REMUNERACION(P_MONTO, P_FECHA, P_METODO, P_CUENTA, P_REF, V_NOMBRE,
                              CONCAT('ADELANTO', IF(TRIM(IFNULL(P_OBS, '')) <> '', CONCAT(': ', P_OBS), '')), P_USU, V_EG, V_MENSAJE);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje; LEAVE proc; END IF;
  -- Si la planilla de ese mes está en borrador, se descuenta ahí mismo
  SELECT d.id_detalle, d.id_planilla INTO V_DET, V_PLA
    FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla
   WHERE d.docente_id = P_DOC AND p.estado = 'BORRADOR' AND p.anio = YEAR(P_FECHA) AND p.mes = MONTH(P_FECHA) LIMIT 1;
  INSERT INTO pagos_docentes(tipo, id_detalle_descuento, docente_id, fecha, monto, metodo_pago_id, cuenta_id, referencia, observacion, egreso_id, usuario_id, estado)
  SELECT 'ADELANTO', V_DET, P_DOC, TIMESTAMP(P_FECHA, CURTIME()), P_MONTO, P_METODO, cuenta_id, referencia, NULLIF(TRIM(P_OBS), ''), V_EG, P_USU, 'VALIDO'
    FROM egresos WHERE id_egresos = V_EG;
  SET V_PD = LAST_INSERT_ID();
  UPDATE egresos SET pago_docente_id = V_PD WHERE id_egresos = V_EG;
  IF V_PLA IS NOT NULL THEN CALL SP_RECALCULAR_PLANILLA(V_PLA); END IF;
  SELECT 1 AS codigo, IF(V_PLA IS NULL, 'Adelanto registrado: se descontará en la próxima planilla',
                                        'Adelanto registrado y descontado en la planilla del mes (borrador)') AS mensaje, V_PD AS id;
END$$
DELIMITER ;

-- Anula un pago de planilla o un adelanto (y su egreso y movimiento)
DROP PROCEDURE IF EXISTS SP_ANULAR_PAGO_DOCENTE;
DELIMITER $$
CREATE PROCEDURE SP_ANULAR_PAGO_DOCENTE(IN P_ID INT, IN P_MOTIVO VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_TIPO VARCHAR(10);
  DECLARE V_DET, V_DESC, V_EG, V_PLA INT;
  DECLARE V_ESTADO_DESC VARCHAR(10);
  IF TRIM(IFNULL(P_MOTIVO, '')) = '' THEN SELECT 0 AS codigo, 'Indique el motivo de la anulación' AS mensaje; LEAVE proc; END IF;
  SELECT tipo, id_detalle, id_detalle_descuento, egreso_id INTO V_TIPO, V_DET, V_DESC, V_EG
    FROM pagos_docentes WHERE id_pago_docente = P_ID AND estado = 'VALIDO';
  IF V_TIPO IS NULL THEN SELECT 2 AS codigo, 'El pago no existe o ya fue anulado' AS mensaje; LEAVE proc; END IF;
  IF V_TIPO = 'ADELANTO' AND V_DESC IS NOT NULL THEN
    SELECT p.estado INTO V_ESTADO_DESC FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla WHERE d.id_detalle = V_DESC;
    IF V_ESTADO_DESC <> 'BORRADOR' THEN
      SELECT 3 AS codigo, 'El adelanto ya se descontó en una planilla aprobada: no se puede anular' AS mensaje; LEAVE proc;
    END IF;
  END IF;
  UPDATE pagos_docentes SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO WHERE id_pago_docente = P_ID;
  UPDATE egresos SET estado = 'ANULADO', motivo_anulacion = P_MOTIVO, fecha_anulacion = CURDATE(), updated = NOW() WHERE id_egresos = V_EG;
  UPDATE movimientos_cuenta SET estado = 'ANULADO' WHERE origen = 'EGRESO' AND origen_id = V_EG;
  SELECT id_planilla INTO V_PLA FROM planilla_detalle WHERE id_detalle = IFNULL(V_DET, V_DESC);
  IF V_PLA IS NOT NULL THEN
    CALL SP_RECALCULAR_PLANILLA(V_PLA);
    UPDATE planillas SET estado = 'APROBADA' WHERE id_planilla = V_PLA AND estado = 'PAGADA';
  END IF;
  SELECT 1 AS codigo, 'Pago anulado' AS mensaje;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_PAGOS_DOCENTES;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PAGOS_DOCENTES(IN P_DET INT, IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  -- P_DET > 0: pagos de ese detalle de planilla; si no, adelantos del período
  SELECT p.id_pago_docente, p.tipo, p.id_detalle, p.id_detalle_descuento, CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente,
         DATE_FORMAT(p.fecha, '%d-%m-%Y') AS fecha_txt, p.monto, mp.nombre AS metodo, c.nombre AS cuenta, p.referencia, p.observacion,
         p.estado, p.motivo_anulacion, u.usu_usuario,
         IF(p.tipo = 'ADELANTO', IF(p.id_detalle_descuento IS NULL, 'PENDIENTE DE DESCUENTO',
            (SELECT CONCAT('DESCONTADO EN ', ELT(pl.mes, 'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'), ' ', pl.anio)
               FROM planilla_detalle x INNER JOIN planillas pl ON pl.id_planilla = x.id_planilla WHERE x.id_detalle = p.id_detalle_descuento)), '') AS situacion
    FROM pagos_docentes p
    INNER JOIN docentes d ON d.Id_docente = p.docente_id
    INNER JOIN metodos_pago mp ON mp.id_metodo = p.metodo_pago_id
    LEFT JOIN cuentas_financieras c ON c.id_cuenta = p.cuenta_id
    LEFT JOIN usuario u ON u.usu_id = p.usuario_id
   WHERE (P_DET > 0 AND p.id_detalle = P_DET)
      OR (IFNULL(P_DET, 0) = 0 AND p.tipo = 'ADELANTO' AND DATE(p.fecha) BETWEEN P_DESDE AND P_HASTA)
   ORDER BY p.fecha DESC;
END$$
DELIMITER ;

-- Boletas del docente que inició sesión (planillas aprobadas o pagadas)
DROP PROCEDURE IF EXISTS SP_MIS_BOLETAS;
DELIMITER $$
CREATE PROCEDURE SP_MIS_BOLETAS(IN P_USU INT)
BEGIN
  SELECT d.id_detalle, p.anio, p.mes,
         ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_nombre,
         d.tipo_contrato, d.base, d.ingresos, d.descuentos, d.adelantos, d.neto, d.pagado, p.estado
    FROM planilla_detalle d
    INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN docentes o ON o.Id_docente = d.docente_id
   WHERE o.id_asusuario = P_USU AND p.estado IN ('APROBADA', 'PAGADA')
   ORDER BY p.anio DESC, p.mes DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_GUARDAR_CONCEPTO_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_CONCEPTO_PLANILLA(IN P_ID INT, IN P_NOMBRE VARCHAR(80), IN P_TIPO VARCHAR(10), IN P_FORMA VARCHAR(12),
                                              IN P_VALOR DECIMAL(14,2), IN P_ESTADO VARCHAR(10))
BEGIN
  IF P_ID IS NULL THEN
    INSERT INTO conceptos_planilla(nombre, tipo, forma, valor_defecto, estado) VALUES (P_NOMBRE, P_TIPO, P_FORMA, P_VALOR, P_ESTADO);
  ELSE
    UPDATE conceptos_planilla SET nombre = P_NOMBRE, tipo = P_TIPO, forma = P_FORMA, valor_defecto = P_VALOR, estado = P_ESTADO
     WHERE id_concepto = P_ID;
  END IF;
  SELECT 1 AS codigo, 'Concepto guardado' AS mensaje;
END$$
DELIMITER ;

-- ---------- 9. Los egresos de planilla se modifican/anulan solo desde Planilla ----------

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_EGRESOS`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_EGRESOS`(IN `ID` INT, IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(14,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT,
                                      IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60),
                                      IN P_BENEF VARCHAR(150), IN P_COMPROB VARCHAR(30))
proc: BEGIN
  DECLARE V_IND, V_CTA, V_EXISTE INT;
  DECLARE V_MENSAJE VARCHAR(255);
  DECLARE V_OK TINYINT;
  DECLARE V_SALDO DECIMAL(14,2);
  DECLARE V_NOMBRE VARCHAR(255);
  SELECT COUNT(*) INTO V_EXISTE FROM egresos WHERE id_egresos = ID AND estado = 'VALIDO';
  IF EXISTS (SELECT 1 FROM egresos WHERE id_egresos = ID AND pago_docente_id IS NOT NULL) THEN
    SELECT 2 AS codigo, 'Es un pago de planilla: se modifica o anula desde Planilla docente' AS mensaje, ID AS id; LEAVE proc;
  END IF;
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

DROP PROCEDURE IF EXISTS `SP_ANULAR_EGRESOS`;
DELIMITER $$
CREATE PROCEDURE `SP_ANULAR_EGRESOS`(IN `ID` INT, IN `OBSERVA` VARCHAR(255), IN `USU` INT)
BEGIN
  UPDATE egresos SET motivo_anulacion = OBSERVA, fecha_anulacion = CURDATE(), estado = 'ANULADO', updated = NOW()
   WHERE id_egresos = ID AND estado = 'VALIDO' AND pago_docente_id IS NULL;
  UPDATE movimientos_cuenta m INNER JOIN egresos e ON e.id_egresos = m.origen_id
     SET m.estado = 'ANULADO'
   WHERE m.origen = 'EGRESO' AND m.origen_id = ID AND e.estado = 'ANULADO';
END$$
DELIMITER ;
