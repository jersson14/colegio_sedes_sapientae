-- =====================================================================
-- Fase 8 (bloque B):
--   1. Planilla del personal administrativo con la misma estructura que la docente
--      (contratos, planilla mensual por tipo de personal, pagos y adelantos).
--      Los pagos al personal administrativo van a "5.1.2 REMUNERACIONES ADMINISTRATIVAS".
--   2. Asistencia docente diaria (horas dictadas, faltas, tardanzas). Al generar la planilla,
--      los contratos por horas toman las horas registradas en el mes.
-- Las tablas conservan su nombre (contratos_docente, pagos_docentes…) y ahora sirven a ambos tipos.
-- =====================================================================

-- Literales de vistas y procedimientos con la misma collation que las tablas (si no: "Illegal mix of collations")
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------- 1. Personal en contratos, detalle y pagos ----------
ALTER TABLE contratos_docente
  MODIFY docente_id INT NULL,
  ADD COLUMN personal_id INT NULL AFTER docente_id,
  ADD COLUMN persona_tipo VARCHAR(15) AS (IF(docente_id IS NULL, 'ADMINISTRATIVO', 'DOCENTE')) VIRTUAL,
  ADD COLUMN persona_id INT AS (IFNULL(docente_id, personal_id)) VIRTUAL,
  ADD INDEX idx_contrato_persona (personal_id, estado),
  ADD CONSTRAINT fk_contrato_personal FOREIGN KEY (personal_id) REFERENCES personal_admi(personal_adm_id),
  ADD CONSTRAINT chk_contrato_persona CHECK ((docente_id IS NULL) <> (personal_id IS NULL));

ALTER TABLE planillas
  ADD COLUMN tipo_personal ENUM('DOCENTE','ADMINISTRATIVO') NOT NULL DEFAULT 'DOCENTE' AFTER mes;

ALTER TABLE planilla_detalle
  MODIFY docente_id INT NULL,
  ADD COLUMN personal_id INT NULL AFTER docente_id,
  ADD COLUMN horas_origen ENUM('ASISTENCIA','ESTIMADO','MANUAL') NOT NULL DEFAULT 'ESTIMADO' AFTER horas,
  ADD COLUMN persona_tipo VARCHAR(15) AS (IF(docente_id IS NULL, 'ADMINISTRATIVO', 'DOCENTE')) VIRTUAL,
  ADD COLUMN persona_id INT AS (IFNULL(docente_id, personal_id)) VIRTUAL,
  ADD UNIQUE KEY uk_detalle_personal (id_planilla, personal_id),
  ADD CONSTRAINT fk_det_personal FOREIGN KEY (personal_id) REFERENCES personal_admi(personal_adm_id),
  ADD CONSTRAINT chk_detalle_persona CHECK ((docente_id IS NULL) <> (personal_id IS NULL));

ALTER TABLE pagos_docentes
  MODIFY docente_id INT NULL,
  ADD COLUMN personal_id INT NULL AFTER docente_id,
  ADD COLUMN persona_tipo VARCHAR(15) AS (IF(docente_id IS NULL, 'ADMINISTRATIVO', 'DOCENTE')) VIRTUAL,
  ADD COLUMN persona_id INT AS (IFNULL(docente_id, personal_id)) VIRTUAL,
  ADD INDEX idx_pd_personal (personal_id, tipo, estado),
  ADD CONSTRAINT fk_pd_personal FOREIGN KEY (personal_id) REFERENCES personal_admi(personal_adm_id),
  ADD CONSTRAINT chk_pago_persona CHECK ((docente_id IS NULL) <> (personal_id IS NULL));

-- Docentes y personal administrativo con los mismos campos (lo usan planilla, boletas y reportes)
DROP VIEW IF EXISTS vw_personal_planilla;
CREATE VIEW vw_personal_planilla AS
SELECT 'DOCENTE' AS tipo, Id_docente AS id, docente_dni AS documento, docente_nombre AS nombres, docente_apelli AS apellidos,
       'DOCENTE' AS cargo, docente_estatus AS estado, id_asusuario AS usuario_id
  FROM docentes
UNION ALL
SELECT 'ADMINISTRATIVO', personal_adm_id, personal_adm_dni, personal_adm_nombre, personal_adm_apellido,
       personal_adm_tipo, personal_adm_estatus, id_ausuario
  FROM personal_admi;

-- ---------- 2. Asistencia docente ----------
CREATE TABLE IF NOT EXISTS asistencia_docente (
  id_asistencia     INT AUTO_INCREMENT PRIMARY KEY,
  docente_id        INT NOT NULL,
  fecha             DATE NOT NULL,
  estado            ENUM('PRESENTE','TARDANZA','FALTA','JUSTIFICADA') NOT NULL,
  horas_programadas DECIMAL(5,2) NOT NULL DEFAULT 0,   -- según su horario de ese día
  horas_dictadas    DECIMAL(5,2) NOT NULL DEFAULT 0,   -- las que se pagan a los contratos por horas
  minutos_tardanza  INT NOT NULL DEFAULT 0,
  observacion       VARCHAR(255) NULL,
  usuario_id        INT NULL,
  created_at        DATETIME NOT NULL,
  updated_at        DATETIME NULL,
  UNIQUE KEY uk_asistencia_docente (docente_id, fecha),
  KEY idx_asistencia_fecha (fecha),
  CONSTRAINT fk_asis_docente FOREIGN KEY (docente_id) REFERENCES docentes(Id_docente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Horas que el docente tiene en su horario el día de P_FECHA (año escolar de esa fecha o, si no hay, el activo)
DROP FUNCTION IF EXISTS FN_HORAS_PROGRAMADAS;
DELIMITER $$
CREATE FUNCTION FN_HORAS_PROGRAMADAS(P_DOC INT, P_FECHA DATE) RETURNS DECIMAL(5,2)
READS SQL DATA
BEGIN
  DECLARE V_ANIO INT;
  DECLARE V_HORAS DECIMAL(5,2);
  SELECT `Id_año_escolar` INTO V_ANIO FROM `año_escolar`
   ORDER BY (P_FECHA BETWEEN fecha_inicio AND fecha_fin) DESC, estado = 'ACTIVO' DESC, fecha_inicio DESC LIMIT 1;
  SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, ha.hora_inicio, ha.hora_fin)), 0) / 60 INTO V_HORAS
    FROM horarios h
   INNER JOIN horas_aula ha ON ha.id_hora = h.id_hora_aula
   INNER JOIN detalle_asignatura_docente dad ON dad.Id_detalle_asig_docente = h.id_detalle_asig_docente
   INNER JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente
   WHERE ad.Id_docente = P_DOC AND ad.`id_año` = V_ANIO AND h.estado = 'ACTIVO'
     AND h.dia = ELT(WEEKDAY(P_FECHA) + 1, 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO');
  RETURN IFNULL(V_HORAS, 0);
END$$
DELIMITER ;

-- Docentes activos con lo registrado en P_FECHA (o las horas de su horario para proponer)
DROP PROCEDURE IF EXISTS SP_ASISTENCIA_DOCENTE_DIA;
DELIMITER $$
CREATE PROCEDURE SP_ASISTENCIA_DOCENTE_DIA(IN P_FECHA DATE)
BEGIN
  SELECT d.Id_docente, d.docente_dni, CONCAT_WS(' ', d.docente_apelli, d.docente_nombre) AS docente,
         t.nombre AS tipo_contrato, t.calculo,
         FN_HORAS_PROGRAMADAS(d.Id_docente, P_FECHA) AS horas_horario,
         a.id_asistencia, a.estado, a.horas_programadas, a.horas_dictadas, a.minutos_tardanza, a.observacion
    FROM docentes d
    LEFT JOIN contratos_docente c ON c.docente_id = d.Id_docente AND c.fecha_inicio <= P_FECHA AND (c.fecha_fin IS NULL OR c.fecha_fin >= P_FECHA)
    LEFT JOIN tipos_contrato t ON t.id_tipo_contrato = c.tipo_contrato_id
    LEFT JOIN asistencia_docente a ON a.docente_id = d.Id_docente AND a.fecha = P_FECHA
   WHERE d.docente_estatus = 'ACTIVO'
   ORDER BY d.docente_apelli, d.docente_nombre;
END$$
DELIMITER ;

-- Registra o corrige la asistencia de un docente en un día.
-- 1 guardada, 2 la planilla docente de ese mes ya está aprobada o pagada, 0 dato inválido
DROP PROCEDURE IF EXISTS SP_GUARDAR_ASISTENCIA_DOCENTE;
DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_ASISTENCIA_DOCENTE(IN P_DOC INT, IN P_FECHA DATE, IN P_ESTADO VARCHAR(12), IN P_HORAS DECIMAL(5,2),
                                               IN P_MINUTOS INT, IN P_OBS VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_HORAS DECIMAL(5,2);
  IF NOT EXISTS (SELECT 1 FROM docentes WHERE Id_docente = P_DOC AND docente_estatus = 'ACTIVO') THEN
    SELECT 0 AS codigo, 'El docente no existe o está inactivo' AS mensaje; LEAVE proc;
  END IF;
  IF P_ESTADO NOT IN ('PRESENTE', 'TARDANZA', 'FALTA', 'JUSTIFICADA') THEN SELECT 0 AS codigo, 'Estado no válido' AS mensaje; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje; LEAVE proc; END IF;
  IF P_HORAS IS NULL OR P_HORAS < 0 OR P_HORAS > 16 THEN SELECT 0 AS codigo, 'Horas dictadas no válidas (0 a 16)' AS mensaje; LEAVE proc; END IF;
  IF P_ESTADO = 'TARDANZA' AND IFNULL(P_MINUTOS, 0) <= 0 THEN SELECT 0 AS codigo, 'Indique los minutos de tardanza' AS mensaje; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM planillas WHERE tipo_personal = 'DOCENTE' AND anio = YEAR(P_FECHA) AND mes = MONTH(P_FECHA)
                                        AND estado IN ('APROBADA', 'PAGADA')) THEN
    SELECT 2 AS codigo, 'La planilla docente de ese mes ya está aprobada: no se modifica su asistencia' AS mensaje; LEAVE proc;
  END IF;
  SET V_HORAS = IF(P_ESTADO = 'FALTA', 0, P_HORAS);
  INSERT INTO asistencia_docente(docente_id, fecha, estado, horas_programadas, horas_dictadas, minutos_tardanza, observacion, usuario_id, created_at)
  VALUES (P_DOC, P_FECHA, P_ESTADO, FN_HORAS_PROGRAMADAS(P_DOC, P_FECHA), V_HORAS, IF(P_ESTADO = 'TARDANZA', P_MINUTOS, 0),
          NULLIF(TRIM(P_OBS), ''), P_USU, NOW())
  ON DUPLICATE KEY UPDATE estado = VALUES(estado), horas_dictadas = VALUES(horas_dictadas), minutos_tardanza = VALUES(minutos_tardanza),
                          observacion = VALUES(observacion), usuario_id = VALUES(usuario_id), updated_at = NOW();
  SELECT 1 AS codigo, 'Asistencia guardada' AS mensaje;
END$$
DELIMITER ;

-- Resumen del mes por docente (también lo muestra la planilla)
DROP PROCEDURE IF EXISTS SP_RESUMEN_ASISTENCIA_DOCENTE;
DELIMITER $$
CREATE PROCEDURE SP_RESUMEN_ASISTENCIA_DOCENTE(IN P_ANIO INT, IN P_MES INT)
BEGIN
  SELECT d.Id_docente, CONCAT_WS(' ', d.docente_apelli, d.docente_nombre) AS docente,
         COUNT(a.id_asistencia) AS dias, SUM(a.estado = 'PRESENTE') AS presentes, SUM(a.estado = 'TARDANZA') AS tardanzas,
         SUM(a.estado = 'FALTA') AS faltas, SUM(a.estado = 'JUSTIFICADA') AS justificadas,
         IFNULL(SUM(a.minutos_tardanza), 0) AS minutos_tardanza, IFNULL(SUM(a.horas_programadas), 0) AS horas_programadas,
         IFNULL(SUM(a.horas_dictadas), 0) AS horas_dictadas
    FROM docentes d
    LEFT JOIN asistencia_docente a ON a.docente_id = d.Id_docente AND YEAR(a.fecha) = P_ANIO AND MONTH(a.fecha) = P_MES
   WHERE d.docente_estatus = 'ACTIVO' OR a.id_asistencia IS NOT NULL
   GROUP BY d.Id_docente
   ORDER BY d.docente_apelli, d.docente_nombre;
END$$
DELIMITER ;

-- ---------- 3. Planilla para ambos tipos de personal ----------
-- Egreso de remuneraciones en la categoría que corresponde (5.1.1 docentes, 5.1.2 administrativos)
DROP PROCEDURE IF EXISTS SP_EGRESO_REMUNERACION;
DELIMITER $$
CREATE PROCEDURE SP_EGRESO_REMUNERACION(IN P_MONTO DECIMAL(14,2), IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT, IN P_REF VARCHAR(60),
                                        IN P_BENEF VARCHAR(150), IN P_OBS VARCHAR(255), IN P_USU INT, IN P_CODIGO VARCHAR(10),
                                        OUT O_ID INT, OUT O_MENSAJE VARCHAR(255))
proc: BEGIN
  DECLARE V_IND, V_CTA INT;
  DECLARE V_OK TINYINT;
  DECLARE V_SALDO DECIMAL(14,2);
  SET O_ID = NULL;
  SELECT id_indicadores INTO V_IND FROM indicadores WHERE codigo = P_CODIGO LIMIT 1;
  IF V_IND IS NULL THEN SET O_MENSAJE = CONCAT('No existe la categoría ', P_CODIGO); LEAVE proc; END IF;
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

-- Contratos del tipo de personal (los nombres de columna se mantienen para la pantalla)
DROP PROCEDURE IF EXISTS SP_LISTAR_CONTRATOS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_CONTRATOS(IN P_TIPO VARCHAR(15))
BEGIN
  SELECT w.id AS Id_docente, w.documento AS docente_dni, CONCAT_WS(' ', w.nombres, w.apellidos) AS docente, w.estado AS docente_estatus,
         w.tipo AS tipo_personal, w.cargo,
         c.id_contrato, c.tipo_contrato_id, t.nombre AS tipo_contrato, t.calculo, c.fecha_inicio,
         DATE_FORMAT(c.fecha_inicio, '%d-%m-%Y') AS fecha_inicio_txt, c.sueldo_base, c.tarifa_hora, c.horas_semanales,
         c.metodo_pago_id, mp.nombre AS metodo, c.banco_docente, c.cuenta_docente, c.observacion,
         (SELECT IFNULL(SUM(p.monto), 0) FROM pagos_docentes p
           WHERE p.persona_tipo = w.tipo AND p.persona_id = w.id AND p.tipo = 'ADELANTO' AND p.estado = 'VALIDO' AND p.id_detalle_descuento IS NULL) AS adelantos_pendientes
    FROM vw_personal_planilla w
    LEFT JOIN contratos_docente c ON c.persona_tipo = w.tipo AND c.persona_id = w.id AND c.estado = 'ACTIVO'
    LEFT JOIN tipos_contrato t ON t.id_tipo_contrato = c.tipo_contrato_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = c.metodo_pago_id
   WHERE w.tipo = P_TIPO AND (w.estado = 'ACTIVO' OR c.id_contrato IS NOT NULL)
   ORDER BY w.apellidos, w.nombres;
END$$
DELIMITER ;

-- Un contrato nuevo reemplaza al activo (que queda FINALIZADO el día anterior al inicio del nuevo)
DROP PROCEDURE IF EXISTS SP_GUARDAR_CONTRATO;
DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_CONTRATO(IN P_ID INT, IN P_TIPO_PERSONAL VARCHAR(15), IN P_PERSONA INT, IN P_TIPO INT, IN P_INICIO DATE,
                                     IN P_SUELDO DECIMAL(14,2), IN P_TARIFA DECIMAL(14,2), IN P_HORAS DECIMAL(5,2), IN P_METODO INT,
                                     IN P_BANCO VARCHAR(100), IN P_CUENTA VARCHAR(40), IN P_OBS VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_CALCULO VARCHAR(10);
  DECLARE V_MET, V_ACTUAL INT;
  DECLARE V_INICIO_ACTUAL DATE;
  IF NOT EXISTS (SELECT 1 FROM vw_personal_planilla WHERE tipo = P_TIPO_PERSONAL AND id = P_PERSONA AND estado = 'ACTIVO') THEN
    SELECT 0 AS codigo, 'La persona no existe o está inactiva' AS mensaje; LEAVE proc;
  END IF;
  SELECT calculo INTO V_CALCULO FROM tipos_contrato WHERE id_tipo_contrato = P_TIPO AND estado = 'ACTIVO';
  IF V_CALCULO IS NULL THEN SELECT 0 AS codigo, 'Tipo de contrato no válido' AS mensaje; LEAVE proc; END IF;
  SELECT id_metodo INTO V_MET FROM metodos_pago WHERE id_metodo = P_METODO AND estado = 'ACTIVO';
  IF V_MET IS NULL THEN SELECT 0 AS codigo, 'Forma de pago no válida' AS mensaje; LEAVE proc; END IF;
  IF P_INICIO IS NULL THEN SELECT 0 AS codigo, 'Indique la fecha de inicio' AS mensaje; LEAVE proc; END IF;
  IF V_CALCULO = 'MENSUAL' AND IFNULL(P_SUELDO, 0) <= 0 THEN SELECT 0 AS codigo, 'Indique el sueldo mensual' AS mensaje; LEAVE proc; END IF;
  IF V_CALCULO = 'POR_HORA' AND IFNULL(P_TARIFA, 0) <= 0 THEN SELECT 0 AS codigo, 'Indique la tarifa por hora' AS mensaje; LEAVE proc; END IF;

  IF P_ID IS NULL THEN
    SELECT id_contrato, fecha_inicio INTO V_ACTUAL, V_INICIO_ACTUAL
      FROM contratos_docente WHERE persona_tipo = P_TIPO_PERSONAL AND persona_id = P_PERSONA AND estado = 'ACTIVO' LIMIT 1;
    IF V_ACTUAL IS NOT NULL THEN
      IF P_INICIO <= V_INICIO_ACTUAL THEN
        SELECT 0 AS codigo, 'Ya tiene un contrato activo desde esa fecha: edítelo o use una fecha de inicio posterior' AS mensaje; LEAVE proc;
      END IF;
      UPDATE contratos_docente SET estado = 'FINALIZADO', fecha_fin = DATE_SUB(P_INICIO, INTERVAL 1 DAY), updated_at = NOW()
       WHERE id_contrato = V_ACTUAL;
    END IF;
    INSERT INTO contratos_docente(docente_id, personal_id, tipo_contrato_id, fecha_inicio, sueldo_base, tarifa_hora, horas_semanales, metodo_pago_id,
                                  banco_docente, cuenta_docente, observacion, estado, usuario_id, created_at)
    VALUES (IF(P_TIPO_PERSONAL = 'DOCENTE', P_PERSONA, NULL), IF(P_TIPO_PERSONAL = 'ADMINISTRATIVO', P_PERSONA, NULL),
            P_TIPO, P_INICIO, IF(V_CALCULO = 'MENSUAL', P_SUELDO, 0), IF(V_CALCULO = 'POR_HORA', P_TARIFA, 0), IFNULL(P_HORAS, 0),
            P_METODO, NULLIF(TRIM(P_BANCO), ''), NULLIF(TRIM(P_CUENTA), ''), NULLIF(TRIM(P_OBS), ''), 'ACTIVO', P_USU, NOW());
    SELECT 1 AS codigo, 'Contrato registrado' AS mensaje;
  ELSE
    UPDATE contratos_docente SET tipo_contrato_id = P_TIPO, fecha_inicio = P_INICIO,
           sueldo_base = IF(V_CALCULO = 'MENSUAL', P_SUELDO, 0), tarifa_hora = IF(V_CALCULO = 'POR_HORA', P_TARIFA, 0),
           horas_semanales = IFNULL(P_HORAS, 0), metodo_pago_id = P_METODO, banco_docente = NULLIF(TRIM(P_BANCO), ''),
           cuenta_docente = NULLIF(TRIM(P_CUENTA), ''), observacion = NULLIF(TRIM(P_OBS), ''), updated_at = NOW()
     WHERE id_contrato = P_ID AND persona_tipo = P_TIPO_PERSONAL AND persona_id = P_PERSONA AND estado = 'ACTIVO';
    SELECT IF(ROW_COUNT() > 0, 1, 2) AS codigo, IF(ROW_COUNT() > 0, 'Contrato actualizado', 'El contrato no existe o ya finalizó') AS mensaje;
  END IF;
END$$
DELIMITER ;

-- Crea la planilla del mes del tipo de personal con todos los contratos vigentes; descuenta los adelantos pendientes.
-- Docentes por horas: horas dictadas registradas en la asistencia del mes (si no hay registros, horas semanales x 4).
-- Devuelve 1 ok, 2 ya existe la planilla del mes, 3 no hay contratos vigentes, 0 dato inválido.
DROP PROCEDURE IF EXISTS SP_GENERAR_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_GENERAR_PLANILLA(IN P_ANIO INT, IN P_MES INT, IN P_TIPO VARCHAR(15), IN P_USU INT)
proc: BEGIN
  DECLARE V_INI, V_FIN DATE;
  DECLARE V_EXISTE, V_CONTRATOS, V_ID INT;
  IF P_MES NOT BETWEEN 1 AND 12 OR P_ANIO NOT BETWEEN 2000 AND 2100 OR P_TIPO NOT IN ('DOCENTE', 'ADMINISTRATIVO') THEN
    SELECT 0 AS codigo, 'Período no válido' AS mensaje, 0 AS id; LEAVE proc;
  END IF;
  SELECT COUNT(*) INTO V_EXISTE FROM planillas WHERE anio = P_ANIO AND mes = P_MES AND tipo_personal = P_TIPO AND estado <> 'ANULADA';
  IF V_EXISTE > 0 THEN SELECT 2 AS codigo, 'Ya existe la planilla de ese mes' AS mensaje, 0 AS id; LEAVE proc; END IF;
  SET V_INI = DATE_ADD(MAKEDATE(P_ANIO, 1), INTERVAL P_MES - 1 MONTH);
  SET V_FIN = LAST_DAY(V_INI);

  -- Contrato vigente en el mes (si cambió en el mes, el más reciente)
  DROP TEMPORARY TABLE IF EXISTS tmp_contratos_mes;
  CREATE TEMPORARY TABLE tmp_contratos_mes AS
    SELECT MAX(c.id_contrato) AS id_contrato
      FROM contratos_docente c INNER JOIN vw_personal_planilla w ON w.tipo = c.persona_tipo AND w.id = c.persona_id
     WHERE c.persona_tipo = P_TIPO AND c.fecha_inicio <= V_FIN AND (c.fecha_fin IS NULL OR c.fecha_fin >= V_INI) AND w.estado = 'ACTIVO'
     GROUP BY c.persona_id;
  SELECT COUNT(*) INTO V_CONTRATOS FROM tmp_contratos_mes;
  IF V_CONTRATOS = 0 THEN SELECT 3 AS codigo, 'No hay contratos vigentes en ese mes' AS mensaje, 0 AS id; LEAVE proc; END IF;

  INSERT INTO planillas(anio, mes, tipo_personal, estado, usuario_id, created_at) VALUES (P_ANIO, P_MES, P_TIPO, 'BORRADOR', P_USU, NOW());
  SET V_ID = LAST_INSERT_ID();
  INSERT INTO planilla_detalle(id_planilla, contrato_id, docente_id, personal_id, tipo_contrato, calculo, tarifa_hora, horas, horas_origen, base)
  SELECT V_ID, c.id_contrato, c.docente_id, c.personal_id, t.nombre, t.calculo, c.tarifa_hora,
         IF(t.calculo = 'POR_HORA', IFNULL(a.horas, c.horas_semanales * 4), 0),
         IF(t.calculo = 'POR_HORA' AND a.horas IS NOT NULL, 'ASISTENCIA', 'ESTIMADO'),
         IF(t.calculo = 'POR_HORA', ROUND(IFNULL(a.horas, c.horas_semanales * 4) * c.tarifa_hora, 2), c.sueldo_base)
    FROM tmp_contratos_mes x
    INNER JOIN contratos_docente c ON c.id_contrato = x.id_contrato
    INNER JOIN tipos_contrato t ON t.id_tipo_contrato = c.tipo_contrato_id
    LEFT JOIN (SELECT docente_id, SUM(horas_dictadas) AS horas FROM asistencia_docente
                WHERE fecha BETWEEN V_INI AND V_FIN GROUP BY docente_id) a ON a.docente_id = c.docente_id;
  DROP TEMPORARY TABLE IF EXISTS tmp_contratos_mes;

  -- Conceptos que se aplican siempre (valor por defecto > 0), p. ej. aporte de ley
  INSERT INTO planilla_detalle_conceptos(id_detalle, concepto_id, valor, monto)
  SELECT d.id_detalle, k.id_concepto, k.valor_defecto, 0
    FROM planilla_detalle d CROSS JOIN conceptos_planilla k
   WHERE d.id_planilla = V_ID AND k.estado = 'ACTIVO' AND k.valor_defecto > 0;
  -- Adelantos pagados hasta fin de mes que aún no se descontaron
  UPDATE pagos_docentes p INNER JOIN planilla_detalle d ON d.persona_tipo = p.persona_tipo AND d.persona_id = p.persona_id AND d.id_planilla = V_ID
     SET p.id_detalle_descuento = d.id_detalle
   WHERE p.tipo = 'ADELANTO' AND p.estado = 'VALIDO' AND p.id_detalle_descuento IS NULL AND DATE(p.fecha) <= V_FIN;
  CALL SP_RECALCULAR_PLANILLA(V_ID);
  SELECT 1 AS codigo, CONCAT('Planilla generada con ', V_CONTRATOS, IF(P_TIPO = 'DOCENTE', ' docente(s)', ' persona(s)')) AS mensaje, V_ID AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_PLANILLAS;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PLANILLAS(IN P_ANIO INT, IN P_TIPO VARCHAR(15))
BEGIN
  SELECT p.id_planilla, p.anio, p.mes, p.tipo_personal,
         ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_nombre,
         p.estado, p.total_bruto, p.total_descuentos, p.total_neto, p.total_pagado, p.motivo_anulacion,
         (SELECT COUNT(*) FROM planilla_detalle d WHERE d.id_planilla = p.id_planilla) AS docentes,
         u.usu_usuario AS generado_por, a.usu_usuario AS aprobado_por, DATE_FORMAT(p.fecha_aprobacion, '%d-%m-%Y %H:%i') AS fecha_aprobacion
    FROM planillas p
    LEFT JOIN usuario u ON u.usu_id = p.usuario_id
    LEFT JOIN usuario a ON a.usu_id = p.aprobado_por
   WHERE p.anio = P_ANIO AND p.tipo_personal = P_TIPO
   ORDER BY p.mes DESC, p.id_planilla DESC;
END$$
DELIMITER ;

-- Detalle con la asistencia del mes de cada docente (para revisar horas, faltas y tardanzas)
DROP PROCEDURE IF EXISTS SP_LISTAR_PLANILLA_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PLANILLA_DETALLE(IN P_PLA INT)
BEGIN
  SELECT d.*, CONCAT_WS(' ', w.nombres, w.apellidos) AS docente, w.documento AS docente_dni, w.cargo,
         c.metodo_pago_id, mp.nombre AS metodo, c.banco_docente, c.cuenta_docente, (d.neto - d.pagado) AS saldo, p.estado AS estado_planilla,
         IFNULL((SELECT GROUP_CONCAT(CONCAT(k.nombre, ' ', FORMAT(x.monto, 2)) SEPARATOR ', ')
                   FROM planilla_detalle_conceptos x INNER JOIN conceptos_planilla k ON k.id_concepto = x.concepto_id
                  WHERE x.id_detalle = d.id_detalle), '') AS conceptos,
         asis.dias AS asis_dias, asis.faltas AS asis_faltas, asis.tardanzas AS asis_tardanzas,
         asis.minutos AS asis_minutos, asis.horas AS asis_horas
    FROM planilla_detalle d
    INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN vw_personal_planilla w ON w.tipo = d.persona_tipo AND w.id = d.persona_id
    INNER JOIN contratos_docente c ON c.id_contrato = d.contrato_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = c.metodo_pago_id
    LEFT JOIN (SELECT a.docente_id, COUNT(*) AS dias, SUM(a.estado = 'FALTA') AS faltas, SUM(a.estado = 'TARDANZA') AS tardanzas,
                      SUM(a.minutos_tardanza) AS minutos, SUM(a.horas_dictadas) AS horas
                 FROM asistencia_docente a INNER JOIN planillas pl ON pl.id_planilla = P_PLA
                WHERE YEAR(a.fecha) = pl.anio AND MONTH(a.fecha) = pl.mes
                GROUP BY a.docente_id) asis ON asis.docente_id = d.docente_id
   WHERE d.id_planilla = P_PLA
   ORDER BY w.apellidos, w.nombres;
END$$
DELIMITER ;

-- Horas o base ajustadas a mano en el borrador
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
    UPDATE planilla_detalle SET horas = P_HORAS, horas_origen = 'MANUAL', base = ROUND(P_HORAS * tarifa_hora, 2) WHERE id_detalle = P_DET;
  ELSE
    IF P_BASE IS NULL OR P_BASE < 0 THEN SELECT 0 AS codigo, 'Sueldo base no válido' AS mensaje; LEAVE proc; END IF;
    UPDATE planilla_detalle SET base = P_BASE WHERE id_detalle = P_DET;
  END IF;
  CALL SP_RECALCULAR_PLANILLA(V_PLA);
  SELECT 1 AS codigo, 'Actualizado' AS mensaje;
END$$
DELIMITER ;

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
    SELECT 3 AS codigo, CONCAT(V_NEG, ' persona(s) con neto negativo (descuentos o adelantos mayores al sueldo): revise antes de aprobar') AS mensaje;
    LEAVE proc;
  END IF;
  UPDATE planillas SET estado = 'APROBADA', aprobado_por = P_USU, fecha_aprobacion = NOW() WHERE id_planilla = P_PLA;
  SELECT 1 AS codigo, 'Planilla aprobada' AS mensaje;
END$$
DELIMITER ;

-- Paga (total o parcialmente) el neto de una persona de una planilla APROBADA
DROP PROCEDURE IF EXISTS SP_PAGAR_DETALLE;
DELIMITER $$
CREATE PROCEDURE SP_PAGAR_DETALLE(IN P_DET INT, IN P_MONTO DECIMAL(14,2), IN P_FECHA DATE, IN P_METODO INT, IN P_CUENTA INT,
                                  IN P_REF VARCHAR(60), IN P_USU INT)
proc: BEGIN
  DECLARE V_PLA, V_EG, V_PD, V_PEND INT;
  DECLARE V_ESTADO VARCHAR(10);
  DECLARE V_TIPO VARCHAR(15);
  DECLARE V_SALDO DECIMAL(14,2);
  DECLARE V_NOMBRE VARCHAR(150);
  DECLARE V_PERIODO VARCHAR(40);
  DECLARE V_MENSAJE VARCHAR(255);
  SELECT d.id_planilla, d.neto - d.pagado, p.estado, p.tipo_personal, CONCAT_WS(' ', w.nombres, w.apellidos),
         CONCAT('PLANILLA ', ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'), ' ', p.anio)
    INTO V_PLA, V_SALDO, V_ESTADO, V_TIPO, V_NOMBRE, V_PERIODO
    FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN vw_personal_planilla w ON w.tipo = d.persona_tipo AND w.id = d.persona_id
   WHERE d.id_detalle = P_DET;
  IF V_PLA IS NULL THEN SELECT 0 AS codigo, 'Registro no encontrado' AS mensaje; LEAVE proc; END IF;
  IF V_ESTADO <> 'APROBADA' THEN SELECT 2 AS codigo, 'Solo se paga una planilla APROBADA' AS mensaje; LEAVE proc; END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje; LEAVE proc; END IF;
  IF P_MONTO > V_SALDO THEN SELECT 3 AS codigo, CONCAT('El monto excede lo pendiente (', V_SALDO, ')') AS mensaje; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje; LEAVE proc; END IF;

  CALL SP_EGRESO_REMUNERACION(P_MONTO, P_FECHA, P_METODO, P_CUENTA, P_REF, V_NOMBRE, V_PERIODO, P_USU,
                              IF(V_TIPO = 'DOCENTE', '5.1.1', '5.1.2'), V_EG, V_MENSAJE);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje; LEAVE proc; END IF;
  INSERT INTO pagos_docentes(tipo, id_detalle, docente_id, personal_id, fecha, monto, metodo_pago_id, cuenta_id, referencia, observacion, egreso_id, usuario_id, estado)
  SELECT 'PLANILLA', P_DET, d.docente_id, d.personal_id, TIMESTAMP(P_FECHA, CURTIME()), P_MONTO, P_METODO, e.cuenta_id, e.referencia, V_PERIODO, V_EG, P_USU, 'VALIDO'
    FROM egresos e INNER JOIN planilla_detalle d ON d.id_detalle = P_DET WHERE e.id_egresos = V_EG;
  SET V_PD = LAST_INSERT_ID();
  UPDATE egresos SET pago_docente_id = V_PD WHERE id_egresos = V_EG;
  CALL SP_RECALCULAR_PLANILLA(V_PLA);
  SELECT COUNT(*) INTO V_PEND FROM planilla_detalle WHERE id_planilla = V_PLA AND pagado < neto;
  IF V_PEND = 0 THEN UPDATE planillas SET estado = 'PAGADA' WHERE id_planilla = V_PLA; END IF;
  SELECT 1 AS codigo, 'Pago registrado' AS mensaje, V_PD AS id;
END$$
DELIMITER ;

-- Adelanto de sueldo: egreso inmediato; se descuenta en la próxima planilla de su tipo (o en la del mes si está en borrador)
DROP PROCEDURE IF EXISTS SP_REGISTRAR_ADELANTO;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_ADELANTO(IN P_TIPO VARCHAR(15), IN P_PERSONA INT, IN P_MONTO DECIMAL(14,2), IN P_FECHA DATE, IN P_METODO INT,
                                       IN P_CUENTA INT, IN P_REF VARCHAR(60), IN P_OBS VARCHAR(255), IN P_USU INT)
proc: BEGIN
  DECLARE V_CONTRATO, V_EG, V_PD, V_DET, V_PLA INT;
  DECLARE V_NOMBRE VARCHAR(150);
  DECLARE V_MENSAJE VARCHAR(255);
  SELECT id_contrato INTO V_CONTRATO FROM contratos_docente
   WHERE persona_tipo = P_TIPO AND persona_id = P_PERSONA AND estado = 'ACTIVO' LIMIT 1;
  IF V_CONTRATO IS NULL THEN SELECT 0 AS codigo, 'No tiene contrato activo' AS mensaje; LEAVE proc; END IF;
  IF P_MONTO IS NULL OR P_MONTO <= 0 THEN SELECT 0 AS codigo, 'El monto debe ser mayor a 0' AS mensaje; LEAVE proc; END IF;
  IF P_FECHA IS NULL OR P_FECHA > CURDATE() THEN SELECT 0 AS codigo, 'La fecha no puede ser futura' AS mensaje; LEAVE proc; END IF;
  SELECT CONCAT_WS(' ', nombres, apellidos) INTO V_NOMBRE FROM vw_personal_planilla WHERE tipo = P_TIPO AND id = P_PERSONA;
  CALL SP_EGRESO_REMUNERACION(P_MONTO, P_FECHA, P_METODO, P_CUENTA, P_REF, V_NOMBRE,
                              CONCAT('ADELANTO', IF(TRIM(IFNULL(P_OBS, '')) <> '', CONCAT(': ', P_OBS), '')), P_USU,
                              IF(P_TIPO = 'DOCENTE', '5.1.1', '5.1.2'), V_EG, V_MENSAJE);
  IF V_MENSAJE IS NOT NULL THEN SELECT 0 AS codigo, V_MENSAJE AS mensaje; LEAVE proc; END IF;
  -- Si la planilla de ese mes está en borrador, se descuenta ahí mismo
  SELECT d.id_detalle, d.id_planilla INTO V_DET, V_PLA
    FROM planilla_detalle d INNER JOIN planillas p ON p.id_planilla = d.id_planilla
   WHERE d.persona_tipo = P_TIPO AND d.persona_id = P_PERSONA AND p.estado = 'BORRADOR'
     AND p.anio = YEAR(P_FECHA) AND p.mes = MONTH(P_FECHA) LIMIT 1;
  INSERT INTO pagos_docentes(tipo, id_detalle_descuento, docente_id, personal_id, fecha, monto, metodo_pago_id, cuenta_id, referencia, observacion, egreso_id, usuario_id, estado)
  SELECT 'ADELANTO', V_DET, IF(P_TIPO = 'DOCENTE', P_PERSONA, NULL), IF(P_TIPO = 'ADMINISTRATIVO', P_PERSONA, NULL),
         TIMESTAMP(P_FECHA, CURTIME()), P_MONTO, P_METODO, cuenta_id, referencia, NULLIF(TRIM(P_OBS), ''), V_EG, P_USU, 'VALIDO'
    FROM egresos WHERE id_egresos = V_EG;
  SET V_PD = LAST_INSERT_ID();
  UPDATE egresos SET pago_docente_id = V_PD WHERE id_egresos = V_EG;
  IF V_PLA IS NOT NULL THEN CALL SP_RECALCULAR_PLANILLA(V_PLA); END IF;
  SELECT 1 AS codigo, IF(V_PLA IS NULL, 'Adelanto registrado: se descontará en la próxima planilla',
                                        'Adelanto registrado y descontado en la planilla del mes (borrador)') AS mensaje, V_PD AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_PAGOS_DOCENTES;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PAGOS_DOCENTES(IN P_DET INT, IN P_DESDE DATE, IN P_HASTA DATE, IN P_TIPO VARCHAR(15))
BEGIN
  -- P_DET > 0: pagos de ese detalle de planilla; si no, adelantos del período del tipo de personal
  SELECT p.id_pago_docente, p.tipo, p.id_detalle, p.id_detalle_descuento, CONCAT_WS(' ', w.nombres, w.apellidos) AS docente,
         DATE_FORMAT(p.fecha, '%d-%m-%Y') AS fecha_txt, p.monto, mp.nombre AS metodo, c.nombre AS cuenta, p.referencia, p.observacion,
         p.estado, p.motivo_anulacion, u.usu_usuario,
         IF(p.tipo = 'ADELANTO', IF(p.id_detalle_descuento IS NULL, 'PENDIENTE DE DESCUENTO',
            (SELECT CONCAT('DESCONTADO EN ', ELT(pl.mes, 'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'), ' ', pl.anio)
               FROM planilla_detalle x INNER JOIN planillas pl ON pl.id_planilla = x.id_planilla WHERE x.id_detalle = p.id_detalle_descuento)), '') AS situacion
    FROM pagos_docentes p
    INNER JOIN vw_personal_planilla w ON w.tipo = p.persona_tipo AND w.id = p.persona_id
    INNER JOIN metodos_pago mp ON mp.id_metodo = p.metodo_pago_id
    LEFT JOIN cuentas_financieras c ON c.id_cuenta = p.cuenta_id
    LEFT JOIN usuario u ON u.usu_id = p.usuario_id
   WHERE (P_DET > 0 AND p.id_detalle = P_DET)
      OR (IFNULL(P_DET, 0) = 0 AND p.tipo = 'ADELANTO' AND p.persona_tipo = P_TIPO AND DATE(p.fecha) BETWEEN P_DESDE AND P_HASTA)
   ORDER BY p.fecha DESC;
END$$
DELIMITER ;

-- Boletas de quien inició sesión (docente o personal administrativo), de planillas aprobadas o pagadas
DROP PROCEDURE IF EXISTS SP_MIS_BOLETAS;
DELIMITER $$
CREATE PROCEDURE SP_MIS_BOLETAS(IN P_USU INT)
BEGIN
  SELECT d.id_detalle, p.anio, p.mes, p.tipo_personal,
         ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_nombre,
         d.tipo_contrato, d.base, d.ingresos, d.descuentos, d.adelantos, d.neto, d.pagado, p.estado
    FROM planilla_detalle d
    INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN vw_personal_planilla w ON w.tipo = d.persona_tipo AND w.id = d.persona_id
   WHERE w.usuario_id = P_USU AND p.estado IN ('APROBADA', 'PAGADA')
   ORDER BY p.anio DESC, p.mes DESC;
END$$
DELIMITER ;

-- Reporte de costo de planilla (Fase 7): ahora distingue docentes y personal administrativo
DROP PROCEDURE IF EXISTS SP_REPORTE_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_PLANILLA(IN P_ANIO INT)
BEGIN
  SELECT p.mes, p.estado, CONCAT(IF(p.tipo_personal = 'DOCENTE', 'DOCENTES', 'ADMINISTRATIVOS'), ' · ', d.tipo_contrato) AS tipo_contrato,
         COUNT(*) AS docentes, SUM(d.horas) AS horas,
         SUM(d.base) AS base, SUM(d.ingresos) AS ingresos, SUM(d.base + d.ingresos) AS bruto,
         SUM(d.descuentos) AS descuentos, SUM(d.adelantos) AS adelantos, SUM(d.neto) AS neto, SUM(d.pagado) AS pagado
    FROM planillas p
   INNER JOIN planilla_detalle d ON d.id_planilla = p.id_planilla
   WHERE p.anio = P_ANIO AND p.estado <> 'ANULADA'
   GROUP BY p.mes, p.estado, p.tipo_personal, d.tipo_contrato
   ORDER BY p.mes, p.tipo_personal, d.tipo_contrato;
END$$
DELIMITER ;
