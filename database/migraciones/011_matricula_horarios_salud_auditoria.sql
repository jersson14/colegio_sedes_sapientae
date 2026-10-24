-- =====================================================================
-- Fase 8 (bloque D) — generada por database/generadores/generar_011.php
--   1. Becas y descuentos por matrícula (porcentaje o monto) desde una fecha
--   2. Vacantes por aula (capacidad opcional)
--   3. Horarios: no se cruzan el aula ni el docente
--   4. Atenciones de salud: cada procedimiento modifica solo las de su tipo
--   5. Auditoría de tablas financieras y de configuración (quién, qué, cuándo, antes/después)
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------- 1. Becas y descuentos ----------
ALTER TABLE matricula
  ADD COLUMN beca_tipo ENUM('NINGUNA','PORCENTAJE','MONTO') NOT NULL DEFAULT 'NINGUNA',
  ADD COLUMN beca_valor DECIMAL(14,2) NOT NULL DEFAULT 0,
  ADD COLUMN beca_desde DATE NULL,                 -- aplica a las pensiones que vencen desde esta fecha
  ADD COLUMN beca_motivo VARCHAR(255) NULL;

-- Precio de una pensión para una matrícula: precio del nivel menos su beca (si la pensión vence desde beca_desde)
DROP FUNCTION IF EXISTS FN_PRECIO_PENSION;
DELIMITER $$
CREATE FUNCTION FN_PRECIO_PENSION(P_MATRI INT, P_PENSION INT) RETURNS DECIMAL(14,2)
READS SQL DATA
BEGIN
  DECLARE V_PRECIO DECIMAL(14,2);
  DECLARE V_VENC, V_DESDE DATE;
  DECLARE V_TIPO VARCHAR(12);
  DECLARE V_VALOR DECIMAL(14,2);
  SELECT precio, fecha_vencimiento INTO V_PRECIO, V_VENC FROM pensiones WHERE id_pensiones = P_PENSION;
  SELECT beca_tipo, beca_valor, beca_desde INTO V_TIPO, V_VALOR, V_DESDE FROM matricula WHERE id_matricula = P_MATRI;
  IF V_PRECIO IS NULL THEN RETURN NULL; END IF;
  IF V_TIPO IS NULL OR V_TIPO = 'NINGUNA' OR (V_DESDE IS NOT NULL AND V_VENC < V_DESDE) THEN RETURN V_PRECIO; END IF;
  IF V_TIPO = 'PORCENTAJE' THEN
    RETURN GREATEST(ROUND(V_PRECIO * (100 - LEAST(V_VALOR, 100)) / 100, 2), 0);
  END IF;
  RETURN GREATEST(V_PRECIO - V_VALOR, 0);
END$$
DELIMITER ;

-- Guarda la beca de una matrícula. 1 ok, 0 dato inválido
DROP PROCEDURE IF EXISTS SP_GUARDAR_BECA;
DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_BECA(IN P_MATRI INT, IN P_TIPO VARCHAR(12), IN P_VALOR DECIMAL(14,2), IN P_DESDE DATE, IN P_MOTIVO VARCHAR(255))
proc: BEGIN
  IF NOT EXISTS (SELECT 1 FROM matricula WHERE id_matricula = P_MATRI) THEN SELECT 0 AS codigo, 'La matrícula no existe' AS mensaje; LEAVE proc; END IF;
  IF P_TIPO NOT IN ('NINGUNA', 'PORCENTAJE', 'MONTO') THEN SELECT 0 AS codigo, 'Tipo de beca no válido' AS mensaje; LEAVE proc; END IF;
  IF P_TIPO <> 'NINGUNA' AND (P_VALOR IS NULL OR P_VALOR <= 0 OR (P_TIPO = 'PORCENTAJE' AND P_VALOR > 100)) THEN
    SELECT 0 AS codigo, 'Indique un porcentaje entre 0 y 100 o un monto mayor a 0' AS mensaje; LEAVE proc;
  END IF;
  IF P_TIPO <> 'NINGUNA' AND TRIM(IFNULL(P_MOTIVO, '')) = '' THEN SELECT 0 AS codigo, 'Indique el motivo de la beca o descuento' AS mensaje; LEAVE proc; END IF;
  UPDATE matricula SET beca_tipo = P_TIPO, beca_valor = IF(P_TIPO = 'NINGUNA', 0, P_VALOR),
         beca_desde = IF(P_TIPO = 'NINGUNA', NULL, P_DESDE), beca_motivo = IF(P_TIPO = 'NINGUNA', NULL, TRIM(P_MOTIVO)), updated_at = NOW()
   WHERE id_matricula = P_MATRI;
  SELECT 1 AS codigo, IF(P_TIPO = 'NINGUNA', 'Beca retirada', 'Beca guardada') AS mensaje;
END$$
DELIMITER ;

-- Selector de pensiones al cobrar: precio con beca
DROP PROCEDURE IF EXISTS SP_CARGAR_SELECT_PENSION_MATRICULA;
DELIMITER $$
CREATE PROCEDURE SP_CARGAR_SELECT_PENSION_MATRICULA(IN P_MATRI INT)
BEGIN
  SELECT x.id_pensiones,
         CONCAT(x.mes, ' ', YEAR(x.fecha_vencimiento),
                IF(x.pagado >= x.precio, ' (PAGADO)', IF(x.pagado > 0, ' (PARCIAL)', ''))) AS texto,
         x.fecha_vencimiento, x.precio, x.pagado
    FROM (SELECT pe.id_pensiones, pe.mes, pe.fecha_vencimiento, FN_PRECIO_PENSION(m.id_matricula, pe.id_pensiones) AS precio,
                 IFNULL(pg.pagado, 0) AS pagado
            FROM matricula m
           INNER JOIN aulas a ON a.Id_aula = m.id_aula
           INNER JOIN pensiones pe ON pe.id_nivel_academico = a.id_nivel_academico AND pe.id_anio = m.`id_año`
            LEFT JOIN (SELECT id_pension, SUM(sub_total) AS pagado
                         FROM pago_pensiones
                        WHERE id_matri = P_MATRI AND concepto = 'PENSION' AND estado = 'VALIDO'
                        GROUP BY id_pension) pg ON pg.id_pension = pe.id_pensiones
           WHERE m.id_matricula = P_MATRI) x
   ORDER BY x.fecha_vencimiento;
END$$
DELIMITER ;

-- Estado de pensiones (morosidad, proyección, panel): precio con beca
DROP VIEW IF EXISTS vw_estado_pensiones;
CREATE VIEW vw_estado_pensiones AS
SELECT b.*,
       GREATEST(b.precio - b.pagado_pension, 0) AS saldo_pension,
       CASE WHEN b.exonerada = 1 OR b.vencida = 0 OR (b.precio - b.pagado_pension <= 0 AND b.pagado_mora = 0) THEN 0
            ELSE GREATEST(b.mora - b.pagado_mora, 0) END AS saldo_mora
  FROM (SELECT c.*, (c.a_tiempo < c.precio AND CURDATE() > c.fecha_vencimiento) AS vencida
          FROM (SELECT m.id_matricula, m.`id_año` AS id_anio, m.id_alumno, a.Id_aula AS id_aula, a.id_nivel_academico AS id_nivel,
                       pe.id_pensiones AS id_pension, pe.mes, FIELD(pe.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO',
                       'AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_num,
                       pe.fecha_vencimiento, FN_PRECIO_PENSION(m.id_matricula, pe.id_pensiones) AS precio, IFNULL(pe.mora, 0) AS mora,
                       IFNULL(pg.pagado_pension, 0) AS pagado_pension, IFNULL(pg.pagado_mora, 0) AS pagado_mora,
                       IFNULL(pg.exonerada, 0) AS exonerada, IFNULL(pg.a_tiempo, 0) AS a_tiempo
                  FROM matricula m
                 INNER JOIN aulas a ON a.Id_aula = m.id_aula
                 INNER JOIN pensiones pe ON pe.id_nivel_academico = a.id_nivel_academico AND pe.id_anio = m.`id_año`
                  LEFT JOIN (SELECT pp.id_matri, pp.id_pension,
                                    SUM(CASE WHEN pp.concepto = 'PENSION' THEN pp.sub_total ELSE 0 END) AS pagado_pension,
                                    SUM(CASE WHEN pp.concepto = 'MORA' THEN pp.sub_total ELSE 0 END) AS pagado_mora,
                                    MAX(CASE WHEN pp.concepto = 'MORA' THEN pp.mora_exonerada ELSE 0 END) AS exonerada,
                                    SUM(CASE WHEN pp.concepto = 'PENSION' AND DATE(pp.fecha_pago) <= pe2.fecha_vencimiento THEN pp.sub_total ELSE 0 END) AS a_tiempo
                               FROM pago_pensiones pp
                              INNER JOIN pensiones pe2 ON pe2.id_pensiones = pp.id_pension
                              WHERE pp.estado = 'VALIDO'
                              GROUP BY pp.id_matri, pp.id_pension) pg
                         ON pg.id_matri = m.id_matricula AND pg.id_pension = pe.id_pensiones) c) b;

-- ---------- 2. Vacantes por aula ----------
ALTER TABLE aulas ADD COLUMN capacidad INT NULL AFTER descripcion;   -- NULL = sin límite

-- Vacantes que quedan en el aula ese año (NULL si no tiene límite); P_EXCLUIR: matrícula que no cuenta (al editarla)
DROP FUNCTION IF EXISTS FN_VACANTES_AULA;
DELIMITER $$
CREATE FUNCTION FN_VACANTES_AULA(P_AULA INT, P_ANIO INT, P_EXCLUIR INT) RETURNS INT
READS SQL DATA
BEGIN
  DECLARE V_CAP INT;
  SELECT capacidad INTO V_CAP FROM aulas WHERE Id_aula = P_AULA;
  IF V_CAP IS NULL THEN RETURN NULL; END IF;
  RETURN GREATEST(V_CAP - (SELECT COUNT(*) FROM matricula WHERE id_aula = P_AULA AND `id_año` = P_ANIO AND id_matricula <> IFNULL(P_EXCLUIR, 0)), 0);
END$$
DELIMITER ;

-- 1 ok, 2 ya existe ese grado/sección/nivel, 0 capacidad inválida
DROP PROCEDURE IF EXISTS SP_REGISTRAR_AULAS;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_AULAS`(IN P_GRADO VARCHAR(255), IN P_SECCION INT, IN P_NIVEL INT, IN P_DESCRIP VARCHAR(255), IN P_CAPACIDAD INT)
proc: BEGIN
  IF P_CAPACIDAD IS NOT NULL AND P_CAPACIDAD <= 0 THEN SELECT 0; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM aulas WHERE Grado = P_GRADO AND id_nivel_academico = P_NIVEL AND id_seccion = P_SECCION) THEN SELECT 2; LEAVE proc; END IF;
  INSERT INTO aulas(Grado, id_nivel_academico, id_seccion, descripcion, capacidad, estado, created_at, updated)
  VALUES (P_GRADO, P_NIVEL, P_SECCION, P_DESCRIP, P_CAPACIDAD, 'ACTIVO', NOW(), NOW());
  SELECT 1;
END$$
DELIMITER ;

-- 1 ok, 2 ya existe, 3 la capacidad es menor que los matriculados del año activo, 0 inválido
DROP PROCEDURE IF EXISTS SP_MODIFICAR_AULAS;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_AULAS`(IN P_ID INT, IN P_GRADO VARCHAR(255), IN P_SECCION INT, IN P_NIVEL INT, IN P_DESCRIP VARCHAR(255),
                                      IN P_ESTADO VARCHAR(20), IN P_CAPACIDAD INT)
proc: BEGIN
  IF P_CAPACIDAD IS NOT NULL AND P_CAPACIDAD <= 0 THEN SELECT 0; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM aulas WHERE Grado = P_GRADO AND id_nivel_academico = P_NIVEL AND id_seccion = P_SECCION AND Id_aula <> P_ID) THEN
    SELECT 2; LEAVE proc;
  END IF;
  IF P_CAPACIDAD IS NOT NULL AND P_CAPACIDAD < (SELECT COUNT(*) FROM matricula WHERE id_aula = P_ID
        AND `id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1)) THEN
    SELECT 3; LEAVE proc;
  END IF;
  UPDATE aulas SET Grado = P_GRADO, id_nivel_academico = P_NIVEL, id_seccion = P_SECCION, descripcion = P_DESCRIP,
         estado = P_ESTADO, capacidad = P_CAPACIDAD, updated = NOW()
   WHERE Id_aula = P_ID;
  SELECT 1;
END$$
DELIMITER ;

-- ---------- 3. Horarios sin cruces ----------
-- Revisa un bloque (hora del aula + curso del docente + día) sin guardarlo.
-- O_COD: 1 libre, 2 ya registrado igual, 3 el aula ya tiene otro curso en esa hora, 4 el docente dicta en otra aula a esa hora, 0 inválido
DROP PROCEDURE IF EXISTS SP_VALIDAR_HORARIO_INTERNO;
DELIMITER $$
CREATE PROCEDURE SP_VALIDAR_HORARIO_INTERNO(IN P_HORA INT, IN P_CURSO INT, IN P_DIA VARCHAR(20), OUT O_COD INT, OUT O_MSG VARCHAR(255))
proc: BEGIN
  DECLARE V_AULA, V_DOC, V_ANIO INT;
  DECLARE V_INI, V_FIN TIME;
  DECLARE V_TEXTO VARCHAR(255);
  SELECT id_aula, hora_inicio, hora_fin INTO V_AULA, V_INI, V_FIN FROM horas_aula WHERE id_hora = P_HORA;
  SELECT ad.Id_docente, ad.`id_año` INTO V_DOC, V_ANIO
    FROM detalle_asignatura_docente dad INNER JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente
   WHERE dad.Id_detalle_asig_docente = P_CURSO;
  IF V_AULA IS NULL OR V_DOC IS NULL OR P_DIA NOT IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES') THEN
    SET O_COD = 0, O_MSG = 'Hora, curso o día no válidos'; LEAVE proc;
  END IF;
  IF EXISTS (SELECT 1 FROM horarios WHERE id_hora_aula = P_HORA AND id_detalle_asig_docente = P_CURSO AND dia = P_DIA) THEN
    SET O_COD = 2, O_MSG = 'Ya registrado'; LEAVE proc;
  END IF;
  SELECT CONCAT(s.nombre_asig, ' el ', P_DIA, ' a las ', TIME_FORMAT(V_INI, '%H:%i')) INTO V_TEXTO
    FROM horarios h INNER JOIN detalle_asignatura_docente d ON d.Id_detalle_asig_docente = h.id_detalle_asig_docente
   INNER JOIN asignaturas s ON s.Id_asignatura = d.Id_asignatura
   WHERE h.id_hora_aula = P_HORA AND h.dia = P_DIA AND h.estado = 'ACTIVO' LIMIT 1;
  IF V_TEXTO IS NOT NULL THEN
    SET O_COD = 3, O_MSG = CONCAT('El aula ya tiene ', V_TEXTO); LEAVE proc;
  END IF;
  SELECT CONCAT(CONCAT_WS(' ', o.docente_nombre, o.docente_apelli), ' ya dicta ', s.nombre_asig, ' en ', au.Grado, ' el ', P_DIA, ' de ',
                TIME_FORMAT(ha.hora_inicio, '%H:%i'), ' a ', TIME_FORMAT(ha.hora_fin, '%H:%i')) INTO V_TEXTO
    FROM horarios h
   INNER JOIN horas_aula ha ON ha.id_hora = h.id_hora_aula
   INNER JOIN aulas au ON au.Id_aula = ha.id_aula
   INNER JOIN detalle_asignatura_docente d ON d.Id_detalle_asig_docente = h.id_detalle_asig_docente
   INNER JOIN asignatura_docente ad ON ad.Id_asigdocente = d.Id_asig_docente
   INNER JOIN asignaturas s ON s.Id_asignatura = d.Id_asignatura
   INNER JOIN docentes o ON o.Id_docente = ad.Id_docente
   WHERE ad.Id_docente = V_DOC AND ad.`id_año` = V_ANIO AND h.dia = P_DIA AND h.estado = 'ACTIVO'
     AND ha.hora_inicio < V_FIN AND ha.hora_fin > V_INI
   LIMIT 1;
  IF V_TEXTO IS NOT NULL THEN
    SET O_COD = 4, O_MSG = V_TEXTO; LEAVE proc;
  END IF;
  SET O_COD = 1, O_MSG = 'Libre';
END$$
DELIMITER ;

-- Registra un bloque si está libre. Devuelve codigo (como SP_VALIDAR_HORARIO_INTERNO) y mensaje.
-- Dentro de una transacción con varias filas también detecta cruces entre ellas.
DROP PROCEDURE IF EXISTS SP_REGISTRAR_HORARIO_AULA;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_HORARIO_AULA`(IN P_HORA INT, IN P_CURSO INT, IN P_DIA VARCHAR(20))
BEGIN
  DECLARE V_COD INT;
  DECLARE V_MSG VARCHAR(255);
  CALL SP_VALIDAR_HORARIO_INTERNO(P_HORA, P_CURSO, P_DIA, V_COD, V_MSG);
  IF V_COD = 1 THEN
    INSERT INTO horarios (id_hora_aula, id_detalle_asig_docente, dia, estado, created_at, updated_at)
    VALUES (P_HORA, P_CURSO, P_DIA, 'ACTIVO', NOW(), NOW());
  END IF;
  SELECT V_COD AS codigo, IF(V_COD = 1, 'Registrado', V_MSG) AS mensaje;
END$$
DELIMITER ;

-- ---------- 4. Atenciones de salud: cada tipo modifica solo lo suyo ----------
DROP PROCEDURE IF EXISTS SP_MODIFICAR_ATENCION_ENFERMERIA;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_ATENCION_ENFERMERIA`(IN P_ID INT, IN P_MATRI INT, IN P_MOTIVO VARCHAR(255), IN P_DIAGNO VARCHAR(255), IN P_OBSERVA VARCHAR(255), IN P_USU INT)
BEGIN
  UPDATE atencion_salud SET id_matricula = P_MATRI, id_usuario = P_USU, motivo_consulta = P_MOTIVO, diagnostico = P_DIAGNO,
         observaciones = P_OBSERVA, updated_at = NOW()
   WHERE id_atencion = P_ID AND tipo_atencion = 'ENFERMERIA';
  SELECT ROW_COUNT() > 0;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_MODIFICAR_ATENCION_PSICOLOGICA;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_ATENCION_PSICOLOGICA`(IN P_ID INT, IN P_MATRI INT, IN P_MOTIVO VARCHAR(255), IN P_DIAGNO VARCHAR(255), IN P_OBSERVA VARCHAR(255), IN P_USU INT)
BEGIN
  UPDATE atencion_salud SET id_matricula = P_MATRI, id_usuario = P_USU, motivo_consulta = P_MOTIVO, diagnostico = P_DIAGNO,
         observaciones = P_OBSERVA, updated_at = NOW()
   WHERE id_atencion = P_ID AND tipo_atencion = 'PSICOLOGIA';
  SELECT ROW_COUNT() > 0;
END$$
DELIMITER ;

-- ---------- 5. Auditoría ----------
-- La aplicación identifica al usuario en cada conexión (SET @app_usuario, @app_ip en model/model_conexion.php);
-- los cambios hechos directamente en la BD quedan con usuario vacío.
CREATE TABLE IF NOT EXISTS auditoria (
  id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
  tabla        VARCHAR(40) NOT NULL,
  registro_id  VARCHAR(40) NULL,
  accion       ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  antes        LONGTEXT NULL,
  despues      LONGTEXT NULL,
  usuario_id   INT NULL,
  ip           VARCHAR(45) NULL,
  fecha        DATETIME NOT NULL,
  KEY idx_aud_tabla (tabla, registro_id),
  KEY idx_aud_fecha (fecha),
  KEY idx_aud_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS SP_LISTAR_AUDITORIA;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_AUDITORIA(IN P_TABLA VARCHAR(40), IN P_DESDE DATE, IN P_HASTA DATE, IN P_USUARIO INT, IN P_REGISTRO VARCHAR(40))
BEGIN
  SELECT a.id_auditoria, a.tabla, a.registro_id, a.accion, a.antes, a.despues, a.usuario_id, IFNULL(u.usu_usuario, '(base de datos)') AS usuario,
         a.ip, DATE_FORMAT(a.fecha, '%d-%m-%Y %H:%i:%s') AS fecha_txt
    FROM auditoria a LEFT JOIN usuario u ON u.usu_id = a.usuario_id
   WHERE DATE(a.fecha) BETWEEN P_DESDE AND P_HASTA
     AND (P_TABLA = '' OR a.tabla = P_TABLA)
     AND (P_USUARIO = 0 OR a.usuario_id = P_USUARIO)
     AND (P_REGISTRO = '' OR a.registro_id = P_REGISTRO)
   ORDER BY a.id_auditoria DESC
   LIMIT 1000;
END$$
DELIMITER ;

-- Saldo de una pensión: el precio ya descuenta la beca de la matrícula

DROP PROCEDURE IF EXISTS `SP_CALCULAR_SALDO_PENSION`;
DELIMITER $$
CREATE PROCEDURE `SP_CALCULAR_SALDO_PENSION`(
  IN P_MATRI INT, IN P_PENSION INT,
  OUT O_PRECIO DECIMAL(14,2), OUT O_MORA DECIMAL(14,2),
  OUT O_PAGADO_P DECIMAL(14,2), OUT O_PAGADO_M DECIMAL(14,2),
  OUT O_EXONERADA TINYINT, OUT O_VENCIDA TINYINT,
  OUT O_SALDO_P DECIMAL(14,2), OUT O_SALDO_M DECIMAL(14,2))
BEGIN
  DECLARE V_VENC DATE;
  DECLARE V_A_TIEMPO DECIMAL(14,2);

  SELECT FN_PRECIO_PENSION(P_MATRI, P_PENSION), IFNULL(mora, 0), fecha_vencimiento INTO O_PRECIO, O_MORA, V_VENC
    FROM pensiones WHERE id_pensiones = P_PENSION;

  SELECT IFNULL(SUM(CASE WHEN concepto = 'PENSION' THEN sub_total END), 0),
         IFNULL(SUM(CASE WHEN concepto = 'MORA' THEN sub_total END), 0),
         IFNULL(MAX(CASE WHEN concepto = 'MORA' THEN mora_exonerada END), 0),
         IFNULL(SUM(CASE WHEN concepto = 'PENSION' AND DATE(fecha_pago) <= V_VENC THEN sub_total END), 0)
    INTO O_PAGADO_P, O_PAGADO_M, O_EXONERADA, V_A_TIEMPO
    FROM pago_pensiones
   WHERE id_matri = P_MATRI AND id_pension = P_PENSION AND estado = 'VALIDO';

  SET O_SALDO_P = GREATEST(O_PRECIO - O_PAGADO_P, 0);
  SET O_VENCIDA = (V_A_TIEMPO < O_PRECIO AND CURDATE() > V_VENC);
  IF O_EXONERADA = 1 OR O_VENCIDA = 0 OR (O_SALDO_P = 0 AND O_PAGADO_M = 0) THEN
    SET O_SALDO_M = 0;
  ELSE
    SET O_SALDO_M = GREATEST(O_MORA - O_PAGADO_M, 0);
  END IF;
END$$
DELIMITER ;

-- Corregir un pago: el tope de la pensión es el precio con beca

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_PAGO_PENSION`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_PAGO_PENSION`(IN P_ID INT, IN P_MONTO DECIMAL(14,2), IN P_DESCRIP VARCHAR(255), IN P_USU INT)
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
  
  IF V_CONCEPTO IN ('PENSION', 'MORA') THEN
    SELECT IF(V_CONCEPTO = 'PENSION', FN_PRECIO_PENSION(V_MATRI, V_PENSION), IFNULL(mora, 0)) INTO V_TOPE FROM pensiones WHERE id_pensiones = V_PENSION;
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

-- Matrícula: además valida las vacantes del aula (código 6)

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
  -- 6: el aula no tiene vacantes en ese año escolar
  IF FN_VACANTES_AULA(AULA, AÑO, 0) = 0 THEN SELECT 6; LEAVE proc; END IF;

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

-- Modificar matrícula: el aula nueva debe tener vacantes (código 3)

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_MATRICULA`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_MATRICULA`(
  IN `ID` INT, IN `IDESTU` INT, IN `AÑO` INT, IN `AULA` INT,
  IN `ADMIN` DECIMAL(14,2), IN `NUEVO` DECIMAL(14,2), IN `MATRI` DECIMAL(14,2),
  IN `PROCEDEN` VARCHAR(100), IN `PROVI` VARCHAR(50), IN `DEPAR` VARCHAR(50), IN `USUREG` INT)
proc: BEGIN
  DECLARE V_CANT INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  SELECT COUNT(*) INTO V_CANT FROM matricula
   WHERE id_alumno = IDESTU AND `id_año` = AÑO AND id_matricula <> ID;
  IF V_CANT > 0 THEN SELECT 2; LEAVE proc; END IF;
  -- 3: el aula nueva no tiene vacantes (la propia matrícula no cuenta)
  IF FN_VACANTES_AULA(AULA, AÑO, ID) = 0 THEN SELECT 3; LEAVE proc; END IF;

  START TRANSACTION;
  UPDATE matricula SET
      id_alumno = IDESTU, `id_año` = AÑO, id_aula = AULA,
      pago_admi = ADMIN, pago_alu_nuevo = NUEVO, pago_matricula = MATRI,
      procedencia_colegio = PROCEDEN, provincia = PROVI, departamento = DEPAR,
      updated_at = NOW()
   WHERE id_matricula = ID;
  CALL SP_SINCRONIZAR_CONCEPTO_MATRICULA(ID, 'ADMISION', ADMIN, USUREG);
  CALL SP_SINCRONIZAR_CONCEPTO_MATRICULA(ID, 'ALUMNO NUEVO', NUEVO, USUREG);
  CALL SP_SINCRONIZAR_CONCEPTO_MATRICULA(ID, 'MATRICULA', MATRI, USUREG);
  COMMIT;
  SELECT 1;
END$$
DELIMITER ;

-- Aulas con su capacidad y matriculados del año escolar activo

DROP PROCEDURE IF EXISTS `SP_LISTAR_AULAS`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_AULAS`()
SELECT
	aulas.Id_aula, 
	aulas.Grado, 
	aulas.id_nivel_academico, 
	aulas.id_seccion, 
	aulas.descripcion, 
	aulas.capacidad,
	(SELECT COUNT(*) FROM matricula m WHERE m.id_aula = aulas.Id_aula
	    AND m.`id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1)) AS matriculados,
	aulas.created_at,
		aulas.estado,
	date_format(aulas.created_at, "%d-%m-%Y") as fecha_formateada,  
	aulas.updated, 
	seccion.seccion_nombre, 
	nivel_academico.Nivel_academico
FROM
	aulas
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel$$
DELIMITER ;

-- Aulas con su capacidad y matriculados del año escolar activo

DROP PROCEDURE IF EXISTS `SP_LISTAR_AULAS_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_AULAS_FILTRO`(IN `ID` INT)
SELECT
	aulas.Id_aula, 
	aulas.Grado, 
	aulas.id_nivel_academico, 
	aulas.id_seccion, 
	aulas.descripcion, 
	aulas.capacidad,
	(SELECT COUNT(*) FROM matricula m WHERE m.id_aula = aulas.Id_aula
	    AND m.`id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1)) AS matriculados,
	aulas.created_at,
		aulas.estado,
	date_format(aulas.created_at, "%d-%m-%Y") as fecha_formateada,  
	aulas.updated, 
	seccion.seccion_nombre, 
	nivel_academico.Nivel_academico
FROM
	aulas
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
  WHERE aulas.id_nivel_academico=ID$$
DELIMITER ;

-- ---------- Triggers de auditoría ----------

DROP TRIGGER IF EXISTS `trg_aud_pagos_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_ins` AFTER INSERT ON `pagos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_pago', NEW.`id_pago`, 'serie', NEW.`serie`, 'numero', NEW.`numero`, 'id_matricula', NEW.`id_matricula`, 'fecha', NEW.`fecha`, 'total', NEW.`total`, 'observacion', NEW.`observacion`, 'voucher', NEW.`voucher`, 'usuario_id', NEW.`usuario_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'usuario_anulacion', NEW.`usuario_anulacion`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos', NEW.`id_pago`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_upd` AFTER UPDATE ON `pagos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago', OLD.`id_pago`, 'serie', OLD.`serie`, 'numero', OLD.`numero`, 'id_matricula', OLD.`id_matricula`, 'fecha', OLD.`fecha`, 'total', OLD.`total`, 'observacion', OLD.`observacion`, 'voucher', OLD.`voucher`, 'usuario_id', OLD.`usuario_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'usuario_anulacion', OLD.`usuario_anulacion`);
  SET V_DESPUES = JSON_OBJECT('id_pago', NEW.`id_pago`, 'serie', NEW.`serie`, 'numero', NEW.`numero`, 'id_matricula', NEW.`id_matricula`, 'fecha', NEW.`fecha`, 'total', NEW.`total`, 'observacion', NEW.`observacion`, 'voucher', NEW.`voucher`, 'usuario_id', NEW.`usuario_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'usuario_anulacion', NEW.`usuario_anulacion`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos', NEW.`id_pago`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_del` AFTER DELETE ON `pagos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago', OLD.`id_pago`, 'serie', OLD.`serie`, 'numero', OLD.`numero`, 'id_matricula', OLD.`id_matricula`, 'fecha', OLD.`fecha`, 'total', OLD.`total`, 'observacion', OLD.`observacion`, 'voucher', OLD.`voucher`, 'usuario_id', OLD.`usuario_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'usuario_anulacion', OLD.`usuario_anulacion`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos', OLD.`id_pago`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_metodos_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_metodos_ins` AFTER INSERT ON `pagos_metodos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_pago_metodo', NEW.`id_pago_metodo`, 'id_pago', NEW.`id_pago`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'monto', NEW.`monto`, 'comision', NEW.`comision`, 'referencia', NEW.`referencia`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos_metodos', NEW.`id_pago_metodo`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_metodos_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_metodos_upd` AFTER UPDATE ON `pagos_metodos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago_metodo', OLD.`id_pago_metodo`, 'id_pago', OLD.`id_pago`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'monto', OLD.`monto`, 'comision', OLD.`comision`, 'referencia', OLD.`referencia`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_pago_metodo', NEW.`id_pago_metodo`, 'id_pago', NEW.`id_pago`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'monto', NEW.`monto`, 'comision', NEW.`comision`, 'referencia', NEW.`referencia`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos_metodos', NEW.`id_pago_metodo`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_metodos_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_metodos_del` AFTER DELETE ON `pagos_metodos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago_metodo', OLD.`id_pago_metodo`, 'id_pago', OLD.`id_pago`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'monto', OLD.`monto`, 'comision', OLD.`comision`, 'referencia', OLD.`referencia`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos_metodos', OLD.`id_pago_metodo`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pago_pensiones_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pago_pensiones_ins` AFTER INSERT ON `pago_pensiones` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_pago_pension', NEW.`id_pago_pension`, 'id_pago', NEW.`id_pago`, 'id_matri', NEW.`id_matri`, 'concepto', NEW.`concepto`, 'id_pension', NEW.`id_pension`, 'fecha_pago', NEW.`fecha_pago`, 'sub_total', NEW.`sub_total`, 'mora_exonerada', NEW.`mora_exonerada`, 'motivo_edicion', NEW.`motivo_edicion`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'id_usuario', NEW.`id_usuario`, 'id_usuario_anulacion', NEW.`id_usuario_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pago_pensiones', NEW.`id_pago_pension`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pago_pensiones_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pago_pensiones_upd` AFTER UPDATE ON `pago_pensiones` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago_pension', OLD.`id_pago_pension`, 'id_pago', OLD.`id_pago`, 'id_matri', OLD.`id_matri`, 'concepto', OLD.`concepto`, 'id_pension', OLD.`id_pension`, 'fecha_pago', OLD.`fecha_pago`, 'sub_total', OLD.`sub_total`, 'mora_exonerada', OLD.`mora_exonerada`, 'motivo_edicion', OLD.`motivo_edicion`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'id_usuario', OLD.`id_usuario`, 'id_usuario_anulacion', OLD.`id_usuario_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_pago_pension', NEW.`id_pago_pension`, 'id_pago', NEW.`id_pago`, 'id_matri', NEW.`id_matri`, 'concepto', NEW.`concepto`, 'id_pension', NEW.`id_pension`, 'fecha_pago', NEW.`fecha_pago`, 'sub_total', NEW.`sub_total`, 'mora_exonerada', NEW.`mora_exonerada`, 'motivo_edicion', NEW.`motivo_edicion`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'id_usuario', NEW.`id_usuario`, 'id_usuario_anulacion', NEW.`id_usuario_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pago_pensiones', NEW.`id_pago_pension`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pago_pensiones_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pago_pensiones_del` AFTER DELETE ON `pago_pensiones` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago_pension', OLD.`id_pago_pension`, 'id_pago', OLD.`id_pago`, 'id_matri', OLD.`id_matri`, 'concepto', OLD.`concepto`, 'id_pension', OLD.`id_pension`, 'fecha_pago', OLD.`fecha_pago`, 'sub_total', OLD.`sub_total`, 'mora_exonerada', OLD.`mora_exonerada`, 'motivo_edicion', OLD.`motivo_edicion`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'id_usuario', OLD.`id_usuario`, 'id_usuario_anulacion', OLD.`id_usuario_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pago_pensiones', OLD.`id_pago_pension`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_ingresos_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_ingresos_ins` AFTER INSERT ON `ingresos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_ingreso', NEW.`id_ingreso`, 'id_pago_pension', NEW.`id_pago_pension`, 'id_indicador', NEW.`id_indicador`, 'id_user', NEW.`id_user`, 'cantidad', NEW.`cantidad`, 'monto', NEW.`monto`, 'observacion', NEW.`observacion`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'referencia', NEW.`referencia`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('ingresos', NEW.`id_ingreso`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_ingresos_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_ingresos_upd` AFTER UPDATE ON `ingresos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_ingreso', OLD.`id_ingreso`, 'id_pago_pension', OLD.`id_pago_pension`, 'id_indicador', OLD.`id_indicador`, 'id_user', OLD.`id_user`, 'cantidad', OLD.`cantidad`, 'monto', OLD.`monto`, 'observacion', OLD.`observacion`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'referencia', OLD.`referencia`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_ingreso', NEW.`id_ingreso`, 'id_pago_pension', NEW.`id_pago_pension`, 'id_indicador', NEW.`id_indicador`, 'id_user', NEW.`id_user`, 'cantidad', NEW.`cantidad`, 'monto', NEW.`monto`, 'observacion', NEW.`observacion`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'referencia', NEW.`referencia`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('ingresos', NEW.`id_ingreso`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_ingresos_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_ingresos_del` AFTER DELETE ON `ingresos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_ingreso', OLD.`id_ingreso`, 'id_pago_pension', OLD.`id_pago_pension`, 'id_indicador', OLD.`id_indicador`, 'id_user', OLD.`id_user`, 'cantidad', OLD.`cantidad`, 'monto', OLD.`monto`, 'observacion', OLD.`observacion`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'referencia', OLD.`referencia`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('ingresos', OLD.`id_ingreso`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_egresos_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_egresos_ins` AFTER INSERT ON `egresos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_egresos', NEW.`id_egresos`, 'id_indicador', NEW.`id_indicador`, 'id_user', NEW.`id_user`, 'cantidad', NEW.`cantidad`, 'monto', NEW.`monto`, 'observacion', NEW.`observacion`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'referencia', NEW.`referencia`, 'beneficiario', NEW.`beneficiario`, 'nro_comprobante', NEW.`nro_comprobante`, 'adjunto', NEW.`adjunto`, 'pago_docente_id', NEW.`pago_docente_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('egresos', NEW.`id_egresos`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_egresos_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_egresos_upd` AFTER UPDATE ON `egresos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_egresos', OLD.`id_egresos`, 'id_indicador', OLD.`id_indicador`, 'id_user', OLD.`id_user`, 'cantidad', OLD.`cantidad`, 'monto', OLD.`monto`, 'observacion', OLD.`observacion`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'referencia', OLD.`referencia`, 'beneficiario', OLD.`beneficiario`, 'nro_comprobante', OLD.`nro_comprobante`, 'adjunto', OLD.`adjunto`, 'pago_docente_id', OLD.`pago_docente_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_egresos', NEW.`id_egresos`, 'id_indicador', NEW.`id_indicador`, 'id_user', NEW.`id_user`, 'cantidad', NEW.`cantidad`, 'monto', NEW.`monto`, 'observacion', NEW.`observacion`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'referencia', NEW.`referencia`, 'beneficiario', NEW.`beneficiario`, 'nro_comprobante', NEW.`nro_comprobante`, 'adjunto', NEW.`adjunto`, 'pago_docente_id', NEW.`pago_docente_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('egresos', NEW.`id_egresos`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_egresos_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_egresos_del` AFTER DELETE ON `egresos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_egresos', OLD.`id_egresos`, 'id_indicador', OLD.`id_indicador`, 'id_user', OLD.`id_user`, 'cantidad', OLD.`cantidad`, 'monto', OLD.`monto`, 'observacion', OLD.`observacion`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'referencia', OLD.`referencia`, 'beneficiario', OLD.`beneficiario`, 'nro_comprobante', OLD.`nro_comprobante`, 'adjunto', OLD.`adjunto`, 'pago_docente_id', OLD.`pago_docente_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('egresos', OLD.`id_egresos`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pensiones_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pensiones_ins` AFTER INSERT ON `pensiones` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_pensiones', NEW.`id_pensiones`, 'id_anio', NEW.`id_anio`, 'id_nivel_academico', NEW.`id_nivel_academico`, 'mes', NEW.`mes`, 'fecha_vencimiento', NEW.`fecha_vencimiento`, 'precio', NEW.`precio`, 'mora', NEW.`mora`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pensiones', NEW.`id_pensiones`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pensiones_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pensiones_upd` AFTER UPDATE ON `pensiones` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pensiones', OLD.`id_pensiones`, 'id_anio', OLD.`id_anio`, 'id_nivel_academico', OLD.`id_nivel_academico`, 'mes', OLD.`mes`, 'fecha_vencimiento', OLD.`fecha_vencimiento`, 'precio', OLD.`precio`, 'mora', OLD.`mora`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_pensiones', NEW.`id_pensiones`, 'id_anio', NEW.`id_anio`, 'id_nivel_academico', NEW.`id_nivel_academico`, 'mes', NEW.`mes`, 'fecha_vencimiento', NEW.`fecha_vencimiento`, 'precio', NEW.`precio`, 'mora', NEW.`mora`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pensiones', NEW.`id_pensiones`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pensiones_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pensiones_del` AFTER DELETE ON `pensiones` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pensiones', OLD.`id_pensiones`, 'id_anio', OLD.`id_anio`, 'id_nivel_academico', OLD.`id_nivel_academico`, 'mes', OLD.`mes`, 'fecha_vencimiento', OLD.`fecha_vencimiento`, 'precio', OLD.`precio`, 'mora', OLD.`mora`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pensiones', OLD.`id_pensiones`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_matricula_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_matricula_ins` AFTER INSERT ON `matricula` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_matricula', NEW.`id_matricula`, 'id_alumno', NEW.`id_alumno`, 'id_año', NEW.`id_año`, 'id_aula', NEW.`id_aula`, 'pago_admi', NEW.`pago_admi`, 'pago_alu_nuevo', NEW.`pago_alu_nuevo`, 'pago_matricula', NEW.`pago_matricula`, 'procedencia_colegio', NEW.`procedencia_colegio`, 'provincia', NEW.`provincia`, 'departamento', NEW.`departamento`, 'usu_id', NEW.`usu_id`, 'created_at', NEW.`created_at`, 'beca_tipo', NEW.`beca_tipo`, 'beca_valor', NEW.`beca_valor`, 'beca_desde', NEW.`beca_desde`, 'beca_motivo', NEW.`beca_motivo`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('matricula', NEW.`id_matricula`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_matricula_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_matricula_upd` AFTER UPDATE ON `matricula` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_matricula', OLD.`id_matricula`, 'id_alumno', OLD.`id_alumno`, 'id_año', OLD.`id_año`, 'id_aula', OLD.`id_aula`, 'pago_admi', OLD.`pago_admi`, 'pago_alu_nuevo', OLD.`pago_alu_nuevo`, 'pago_matricula', OLD.`pago_matricula`, 'procedencia_colegio', OLD.`procedencia_colegio`, 'provincia', OLD.`provincia`, 'departamento', OLD.`departamento`, 'usu_id', OLD.`usu_id`, 'created_at', OLD.`created_at`, 'beca_tipo', OLD.`beca_tipo`, 'beca_valor', OLD.`beca_valor`, 'beca_desde', OLD.`beca_desde`, 'beca_motivo', OLD.`beca_motivo`);
  SET V_DESPUES = JSON_OBJECT('id_matricula', NEW.`id_matricula`, 'id_alumno', NEW.`id_alumno`, 'id_año', NEW.`id_año`, 'id_aula', NEW.`id_aula`, 'pago_admi', NEW.`pago_admi`, 'pago_alu_nuevo', NEW.`pago_alu_nuevo`, 'pago_matricula', NEW.`pago_matricula`, 'procedencia_colegio', NEW.`procedencia_colegio`, 'provincia', NEW.`provincia`, 'departamento', NEW.`departamento`, 'usu_id', NEW.`usu_id`, 'created_at', NEW.`created_at`, 'beca_tipo', NEW.`beca_tipo`, 'beca_valor', NEW.`beca_valor`, 'beca_desde', NEW.`beca_desde`, 'beca_motivo', NEW.`beca_motivo`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('matricula', NEW.`id_matricula`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_matricula_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_matricula_del` AFTER DELETE ON `matricula` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_matricula', OLD.`id_matricula`, 'id_alumno', OLD.`id_alumno`, 'id_año', OLD.`id_año`, 'id_aula', OLD.`id_aula`, 'pago_admi', OLD.`pago_admi`, 'pago_alu_nuevo', OLD.`pago_alu_nuevo`, 'pago_matricula', OLD.`pago_matricula`, 'procedencia_colegio', OLD.`procedencia_colegio`, 'provincia', OLD.`provincia`, 'departamento', OLD.`departamento`, 'usu_id', OLD.`usu_id`, 'created_at', OLD.`created_at`, 'beca_tipo', OLD.`beca_tipo`, 'beca_valor', OLD.`beca_valor`, 'beca_desde', OLD.`beca_desde`, 'beca_motivo', OLD.`beca_motivo`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('matricula', OLD.`id_matricula`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_cuentas_financieras_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_cuentas_financieras_ins` AFTER INSERT ON `cuentas_financieras` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_cuenta', NEW.`id_cuenta`, 'tipo', NEW.`tipo`, 'nombre', NEW.`nombre`, 'banco', NEW.`banco`, 'numero_cuenta', NEW.`numero_cuenta`, 'codigo_interbancario', NEW.`codigo_interbancario`, 'titular', NEW.`titular`, 'saldo_inicial', NEW.`saldo_inicial`, 'fecha_saldo_inicial', NEW.`fecha_saldo_inicial`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('cuentas_financieras', NEW.`id_cuenta`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_cuentas_financieras_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_cuentas_financieras_upd` AFTER UPDATE ON `cuentas_financieras` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_cuenta', OLD.`id_cuenta`, 'tipo', OLD.`tipo`, 'nombre', OLD.`nombre`, 'banco', OLD.`banco`, 'numero_cuenta', OLD.`numero_cuenta`, 'codigo_interbancario', OLD.`codigo_interbancario`, 'titular', OLD.`titular`, 'saldo_inicial', OLD.`saldo_inicial`, 'fecha_saldo_inicial', OLD.`fecha_saldo_inicial`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_cuenta', NEW.`id_cuenta`, 'tipo', NEW.`tipo`, 'nombre', NEW.`nombre`, 'banco', NEW.`banco`, 'numero_cuenta', NEW.`numero_cuenta`, 'codigo_interbancario', NEW.`codigo_interbancario`, 'titular', NEW.`titular`, 'saldo_inicial', NEW.`saldo_inicial`, 'fecha_saldo_inicial', NEW.`fecha_saldo_inicial`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('cuentas_financieras', NEW.`id_cuenta`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_cuentas_financieras_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_cuentas_financieras_del` AFTER DELETE ON `cuentas_financieras` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_cuenta', OLD.`id_cuenta`, 'tipo', OLD.`tipo`, 'nombre', OLD.`nombre`, 'banco', OLD.`banco`, 'numero_cuenta', OLD.`numero_cuenta`, 'codigo_interbancario', OLD.`codigo_interbancario`, 'titular', OLD.`titular`, 'saldo_inicial', OLD.`saldo_inicial`, 'fecha_saldo_inicial', OLD.`fecha_saldo_inicial`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('cuentas_financieras', OLD.`id_cuenta`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_transferencias_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_transferencias_ins` AFTER INSERT ON `transferencias` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_transferencia', NEW.`id_transferencia`, 'tipo', NEW.`tipo`, 'cuenta_origen', NEW.`cuenta_origen`, 'cuenta_destino', NEW.`cuenta_destino`, 'monto', NEW.`monto`, 'fecha', NEW.`fecha`, 'referencia', NEW.`referencia`, 'observacion', NEW.`observacion`, 'usuario_id', NEW.`usuario_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('transferencias', NEW.`id_transferencia`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_transferencias_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_transferencias_upd` AFTER UPDATE ON `transferencias` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_transferencia', OLD.`id_transferencia`, 'tipo', OLD.`tipo`, 'cuenta_origen', OLD.`cuenta_origen`, 'cuenta_destino', OLD.`cuenta_destino`, 'monto', OLD.`monto`, 'fecha', OLD.`fecha`, 'referencia', OLD.`referencia`, 'observacion', OLD.`observacion`, 'usuario_id', OLD.`usuario_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`);
  SET V_DESPUES = JSON_OBJECT('id_transferencia', NEW.`id_transferencia`, 'tipo', NEW.`tipo`, 'cuenta_origen', NEW.`cuenta_origen`, 'cuenta_destino', NEW.`cuenta_destino`, 'monto', NEW.`monto`, 'fecha', NEW.`fecha`, 'referencia', NEW.`referencia`, 'observacion', NEW.`observacion`, 'usuario_id', NEW.`usuario_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fecha_anulacion', NEW.`fecha_anulacion`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('transferencias', NEW.`id_transferencia`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_transferencias_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_transferencias_del` AFTER DELETE ON `transferencias` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_transferencia', OLD.`id_transferencia`, 'tipo', OLD.`tipo`, 'cuenta_origen', OLD.`cuenta_origen`, 'cuenta_destino', OLD.`cuenta_destino`, 'monto', OLD.`monto`, 'fecha', OLD.`fecha`, 'referencia', OLD.`referencia`, 'observacion', OLD.`observacion`, 'usuario_id', OLD.`usuario_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fecha_anulacion', OLD.`fecha_anulacion`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('transferencias', OLD.`id_transferencia`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_cierres_caja_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_cierres_caja_ins` AFTER INSERT ON `cierres_caja` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_cierre', NEW.`id_cierre`, 'cuenta_id', NEW.`cuenta_id`, 'fecha', NEW.`fecha`, 'saldo_inicial', NEW.`saldo_inicial`, 'entradas', NEW.`entradas`, 'salidas', NEW.`salidas`, 'saldo_esperado', NEW.`saldo_esperado`, 'saldo_contado', NEW.`saldo_contado`, 'diferencia', NEW.`diferencia`, 'ajustado', NEW.`ajustado`, 'observacion', NEW.`observacion`, 'usuario_id', NEW.`usuario_id`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('cierres_caja', NEW.`id_cierre`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_cierres_caja_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_cierres_caja_upd` AFTER UPDATE ON `cierres_caja` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_cierre', OLD.`id_cierre`, 'cuenta_id', OLD.`cuenta_id`, 'fecha', OLD.`fecha`, 'saldo_inicial', OLD.`saldo_inicial`, 'entradas', OLD.`entradas`, 'salidas', OLD.`salidas`, 'saldo_esperado', OLD.`saldo_esperado`, 'saldo_contado', OLD.`saldo_contado`, 'diferencia', OLD.`diferencia`, 'ajustado', OLD.`ajustado`, 'observacion', OLD.`observacion`, 'usuario_id', OLD.`usuario_id`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_cierre', NEW.`id_cierre`, 'cuenta_id', NEW.`cuenta_id`, 'fecha', NEW.`fecha`, 'saldo_inicial', NEW.`saldo_inicial`, 'entradas', NEW.`entradas`, 'salidas', NEW.`salidas`, 'saldo_esperado', NEW.`saldo_esperado`, 'saldo_contado', NEW.`saldo_contado`, 'diferencia', NEW.`diferencia`, 'ajustado', NEW.`ajustado`, 'observacion', NEW.`observacion`, 'usuario_id', NEW.`usuario_id`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('cierres_caja', NEW.`id_cierre`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_cierres_caja_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_cierres_caja_del` AFTER DELETE ON `cierres_caja` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_cierre', OLD.`id_cierre`, 'cuenta_id', OLD.`cuenta_id`, 'fecha', OLD.`fecha`, 'saldo_inicial', OLD.`saldo_inicial`, 'entradas', OLD.`entradas`, 'salidas', OLD.`salidas`, 'saldo_esperado', OLD.`saldo_esperado`, 'saldo_contado', OLD.`saldo_contado`, 'diferencia', OLD.`diferencia`, 'ajustado', OLD.`ajustado`, 'observacion', OLD.`observacion`, 'usuario_id', OLD.`usuario_id`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('cierres_caja', OLD.`id_cierre`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_metodos_pago_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_metodos_pago_ins` AFTER INSERT ON `metodos_pago` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_metodo', NEW.`id_metodo`, 'nombre', NEW.`nombre`, 'tipo', NEW.`tipo`, 'requiere_referencia', NEW.`requiere_referencia`, 'requiere_cuenta', NEW.`requiere_cuenta`, 'comision_pct', NEW.`comision_pct`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('metodos_pago', NEW.`id_metodo`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_metodos_pago_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_metodos_pago_upd` AFTER UPDATE ON `metodos_pago` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_metodo', OLD.`id_metodo`, 'nombre', OLD.`nombre`, 'tipo', OLD.`tipo`, 'requiere_referencia', OLD.`requiere_referencia`, 'requiere_cuenta', OLD.`requiere_cuenta`, 'comision_pct', OLD.`comision_pct`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_metodo', NEW.`id_metodo`, 'nombre', NEW.`nombre`, 'tipo', NEW.`tipo`, 'requiere_referencia', NEW.`requiere_referencia`, 'requiere_cuenta', NEW.`requiere_cuenta`, 'comision_pct', NEW.`comision_pct`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('metodos_pago', NEW.`id_metodo`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_metodos_pago_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_metodos_pago_del` AFTER DELETE ON `metodos_pago` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_metodo', OLD.`id_metodo`, 'nombre', OLD.`nombre`, 'tipo', OLD.`tipo`, 'requiere_referencia', OLD.`requiere_referencia`, 'requiere_cuenta', OLD.`requiere_cuenta`, 'comision_pct', OLD.`comision_pct`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('metodos_pago', OLD.`id_metodo`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_planillas_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_planillas_ins` AFTER INSERT ON `planillas` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_planilla', NEW.`id_planilla`, 'anio', NEW.`anio`, 'mes', NEW.`mes`, 'tipo_personal', NEW.`tipo_personal`, 'estado', NEW.`estado`, 'total_bruto', NEW.`total_bruto`, 'total_descuentos', NEW.`total_descuentos`, 'total_neto', NEW.`total_neto`, 'total_pagado', NEW.`total_pagado`, 'observacion', NEW.`observacion`, 'usuario_id', NEW.`usuario_id`, 'aprobado_por', NEW.`aprobado_por`, 'fecha_aprobacion', NEW.`fecha_aprobacion`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('planillas', NEW.`id_planilla`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_planillas_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_planillas_upd` AFTER UPDATE ON `planillas` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_planilla', OLD.`id_planilla`, 'anio', OLD.`anio`, 'mes', OLD.`mes`, 'tipo_personal', OLD.`tipo_personal`, 'estado', OLD.`estado`, 'total_bruto', OLD.`total_bruto`, 'total_descuentos', OLD.`total_descuentos`, 'total_neto', OLD.`total_neto`, 'total_pagado', OLD.`total_pagado`, 'observacion', OLD.`observacion`, 'usuario_id', OLD.`usuario_id`, 'aprobado_por', OLD.`aprobado_por`, 'fecha_aprobacion', OLD.`fecha_aprobacion`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_planilla', NEW.`id_planilla`, 'anio', NEW.`anio`, 'mes', NEW.`mes`, 'tipo_personal', NEW.`tipo_personal`, 'estado', NEW.`estado`, 'total_bruto', NEW.`total_bruto`, 'total_descuentos', NEW.`total_descuentos`, 'total_neto', NEW.`total_neto`, 'total_pagado', NEW.`total_pagado`, 'observacion', NEW.`observacion`, 'usuario_id', NEW.`usuario_id`, 'aprobado_por', NEW.`aprobado_por`, 'fecha_aprobacion', NEW.`fecha_aprobacion`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('planillas', NEW.`id_planilla`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_planillas_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_planillas_del` AFTER DELETE ON `planillas` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_planilla', OLD.`id_planilla`, 'anio', OLD.`anio`, 'mes', OLD.`mes`, 'tipo_personal', OLD.`tipo_personal`, 'estado', OLD.`estado`, 'total_bruto', OLD.`total_bruto`, 'total_descuentos', OLD.`total_descuentos`, 'total_neto', OLD.`total_neto`, 'total_pagado', OLD.`total_pagado`, 'observacion', OLD.`observacion`, 'usuario_id', OLD.`usuario_id`, 'aprobado_por', OLD.`aprobado_por`, 'fecha_aprobacion', OLD.`fecha_aprobacion`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('planillas', OLD.`id_planilla`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_planilla_detalle_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_planilla_detalle_ins` AFTER INSERT ON `planilla_detalle` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_detalle', NEW.`id_detalle`, 'id_planilla', NEW.`id_planilla`, 'contrato_id', NEW.`contrato_id`, 'docente_id', NEW.`docente_id`, 'personal_id', NEW.`personal_id`, 'tipo_contrato', NEW.`tipo_contrato`, 'calculo', NEW.`calculo`, 'tarifa_hora', NEW.`tarifa_hora`, 'horas', NEW.`horas`, 'horas_origen', NEW.`horas_origen`, 'base', NEW.`base`, 'ingresos', NEW.`ingresos`, 'descuentos', NEW.`descuentos`, 'adelantos', NEW.`adelantos`, 'neto', NEW.`neto`, 'pagado', NEW.`pagado`, 'persona_tipo', NEW.`persona_tipo`, 'persona_id', NEW.`persona_id`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('planilla_detalle', NEW.`id_detalle`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_planilla_detalle_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_planilla_detalle_upd` AFTER UPDATE ON `planilla_detalle` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_detalle', OLD.`id_detalle`, 'id_planilla', OLD.`id_planilla`, 'contrato_id', OLD.`contrato_id`, 'docente_id', OLD.`docente_id`, 'personal_id', OLD.`personal_id`, 'tipo_contrato', OLD.`tipo_contrato`, 'calculo', OLD.`calculo`, 'tarifa_hora', OLD.`tarifa_hora`, 'horas', OLD.`horas`, 'horas_origen', OLD.`horas_origen`, 'base', OLD.`base`, 'ingresos', OLD.`ingresos`, 'descuentos', OLD.`descuentos`, 'adelantos', OLD.`adelantos`, 'neto', OLD.`neto`, 'pagado', OLD.`pagado`, 'persona_tipo', OLD.`persona_tipo`, 'persona_id', OLD.`persona_id`);
  SET V_DESPUES = JSON_OBJECT('id_detalle', NEW.`id_detalle`, 'id_planilla', NEW.`id_planilla`, 'contrato_id', NEW.`contrato_id`, 'docente_id', NEW.`docente_id`, 'personal_id', NEW.`personal_id`, 'tipo_contrato', NEW.`tipo_contrato`, 'calculo', NEW.`calculo`, 'tarifa_hora', NEW.`tarifa_hora`, 'horas', NEW.`horas`, 'horas_origen', NEW.`horas_origen`, 'base', NEW.`base`, 'ingresos', NEW.`ingresos`, 'descuentos', NEW.`descuentos`, 'adelantos', NEW.`adelantos`, 'neto', NEW.`neto`, 'pagado', NEW.`pagado`, 'persona_tipo', NEW.`persona_tipo`, 'persona_id', NEW.`persona_id`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('planilla_detalle', NEW.`id_detalle`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_planilla_detalle_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_planilla_detalle_del` AFTER DELETE ON `planilla_detalle` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_detalle', OLD.`id_detalle`, 'id_planilla', OLD.`id_planilla`, 'contrato_id', OLD.`contrato_id`, 'docente_id', OLD.`docente_id`, 'personal_id', OLD.`personal_id`, 'tipo_contrato', OLD.`tipo_contrato`, 'calculo', OLD.`calculo`, 'tarifa_hora', OLD.`tarifa_hora`, 'horas', OLD.`horas`, 'horas_origen', OLD.`horas_origen`, 'base', OLD.`base`, 'ingresos', OLD.`ingresos`, 'descuentos', OLD.`descuentos`, 'adelantos', OLD.`adelantos`, 'neto', OLD.`neto`, 'pagado', OLD.`pagado`, 'persona_tipo', OLD.`persona_tipo`, 'persona_id', OLD.`persona_id`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('planilla_detalle', OLD.`id_detalle`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_docentes_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_docentes_ins` AFTER INSERT ON `pagos_docentes` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_pago_docente', NEW.`id_pago_docente`, 'tipo', NEW.`tipo`, 'id_detalle', NEW.`id_detalle`, 'id_detalle_descuento', NEW.`id_detalle_descuento`, 'docente_id', NEW.`docente_id`, 'personal_id', NEW.`personal_id`, 'fecha', NEW.`fecha`, 'monto', NEW.`monto`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'referencia', NEW.`referencia`, 'observacion', NEW.`observacion`, 'egreso_id', NEW.`egreso_id`, 'usuario_id', NEW.`usuario_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'persona_tipo', NEW.`persona_tipo`, 'persona_id', NEW.`persona_id`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos_docentes', NEW.`id_pago_docente`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_docentes_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_docentes_upd` AFTER UPDATE ON `pagos_docentes` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago_docente', OLD.`id_pago_docente`, 'tipo', OLD.`tipo`, 'id_detalle', OLD.`id_detalle`, 'id_detalle_descuento', OLD.`id_detalle_descuento`, 'docente_id', OLD.`docente_id`, 'personal_id', OLD.`personal_id`, 'fecha', OLD.`fecha`, 'monto', OLD.`monto`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'referencia', OLD.`referencia`, 'observacion', OLD.`observacion`, 'egreso_id', OLD.`egreso_id`, 'usuario_id', OLD.`usuario_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'persona_tipo', OLD.`persona_tipo`, 'persona_id', OLD.`persona_id`);
  SET V_DESPUES = JSON_OBJECT('id_pago_docente', NEW.`id_pago_docente`, 'tipo', NEW.`tipo`, 'id_detalle', NEW.`id_detalle`, 'id_detalle_descuento', NEW.`id_detalle_descuento`, 'docente_id', NEW.`docente_id`, 'personal_id', NEW.`personal_id`, 'fecha', NEW.`fecha`, 'monto', NEW.`monto`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'cuenta_id', NEW.`cuenta_id`, 'referencia', NEW.`referencia`, 'observacion', NEW.`observacion`, 'egreso_id', NEW.`egreso_id`, 'usuario_id', NEW.`usuario_id`, 'estado', NEW.`estado`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'persona_tipo', NEW.`persona_tipo`, 'persona_id', NEW.`persona_id`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos_docentes', NEW.`id_pago_docente`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_pagos_docentes_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_pagos_docentes_del` AFTER DELETE ON `pagos_docentes` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_pago_docente', OLD.`id_pago_docente`, 'tipo', OLD.`tipo`, 'id_detalle', OLD.`id_detalle`, 'id_detalle_descuento', OLD.`id_detalle_descuento`, 'docente_id', OLD.`docente_id`, 'personal_id', OLD.`personal_id`, 'fecha', OLD.`fecha`, 'monto', OLD.`monto`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'cuenta_id', OLD.`cuenta_id`, 'referencia', OLD.`referencia`, 'observacion', OLD.`observacion`, 'egreso_id', OLD.`egreso_id`, 'usuario_id', OLD.`usuario_id`, 'estado', OLD.`estado`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'persona_tipo', OLD.`persona_tipo`, 'persona_id', OLD.`persona_id`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('pagos_docentes', OLD.`id_pago_docente`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_contratos_docente_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_contratos_docente_ins` AFTER INSERT ON `contratos_docente` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_contrato', NEW.`id_contrato`, 'docente_id', NEW.`docente_id`, 'personal_id', NEW.`personal_id`, 'tipo_contrato_id', NEW.`tipo_contrato_id`, 'fecha_inicio', NEW.`fecha_inicio`, 'fecha_fin', NEW.`fecha_fin`, 'sueldo_base', NEW.`sueldo_base`, 'tarifa_hora', NEW.`tarifa_hora`, 'horas_semanales', NEW.`horas_semanales`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'banco_docente', NEW.`banco_docente`, 'cuenta_docente', NEW.`cuenta_docente`, 'observacion', NEW.`observacion`, 'estado', NEW.`estado`, 'usuario_id', NEW.`usuario_id`, 'created_at', NEW.`created_at`, 'persona_tipo', NEW.`persona_tipo`, 'persona_id', NEW.`persona_id`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('contratos_docente', NEW.`id_contrato`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_contratos_docente_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_contratos_docente_upd` AFTER UPDATE ON `contratos_docente` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_contrato', OLD.`id_contrato`, 'docente_id', OLD.`docente_id`, 'personal_id', OLD.`personal_id`, 'tipo_contrato_id', OLD.`tipo_contrato_id`, 'fecha_inicio', OLD.`fecha_inicio`, 'fecha_fin', OLD.`fecha_fin`, 'sueldo_base', OLD.`sueldo_base`, 'tarifa_hora', OLD.`tarifa_hora`, 'horas_semanales', OLD.`horas_semanales`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'banco_docente', OLD.`banco_docente`, 'cuenta_docente', OLD.`cuenta_docente`, 'observacion', OLD.`observacion`, 'estado', OLD.`estado`, 'usuario_id', OLD.`usuario_id`, 'created_at', OLD.`created_at`, 'persona_tipo', OLD.`persona_tipo`, 'persona_id', OLD.`persona_id`);
  SET V_DESPUES = JSON_OBJECT('id_contrato', NEW.`id_contrato`, 'docente_id', NEW.`docente_id`, 'personal_id', NEW.`personal_id`, 'tipo_contrato_id', NEW.`tipo_contrato_id`, 'fecha_inicio', NEW.`fecha_inicio`, 'fecha_fin', NEW.`fecha_fin`, 'sueldo_base', NEW.`sueldo_base`, 'tarifa_hora', NEW.`tarifa_hora`, 'horas_semanales', NEW.`horas_semanales`, 'metodo_pago_id', NEW.`metodo_pago_id`, 'banco_docente', NEW.`banco_docente`, 'cuenta_docente', NEW.`cuenta_docente`, 'observacion', NEW.`observacion`, 'estado', NEW.`estado`, 'usuario_id', NEW.`usuario_id`, 'created_at', NEW.`created_at`, 'persona_tipo', NEW.`persona_tipo`, 'persona_id', NEW.`persona_id`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('contratos_docente', NEW.`id_contrato`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_contratos_docente_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_contratos_docente_del` AFTER DELETE ON `contratos_docente` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_contrato', OLD.`id_contrato`, 'docente_id', OLD.`docente_id`, 'personal_id', OLD.`personal_id`, 'tipo_contrato_id', OLD.`tipo_contrato_id`, 'fecha_inicio', OLD.`fecha_inicio`, 'fecha_fin', OLD.`fecha_fin`, 'sueldo_base', OLD.`sueldo_base`, 'tarifa_hora', OLD.`tarifa_hora`, 'horas_semanales', OLD.`horas_semanales`, 'metodo_pago_id', OLD.`metodo_pago_id`, 'banco_docente', OLD.`banco_docente`, 'cuenta_docente', OLD.`cuenta_docente`, 'observacion', OLD.`observacion`, 'estado', OLD.`estado`, 'usuario_id', OLD.`usuario_id`, 'created_at', OLD.`created_at`, 'persona_tipo', OLD.`persona_tipo`, 'persona_id', OLD.`persona_id`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('contratos_docente', OLD.`id_contrato`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_conceptos_planilla_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_conceptos_planilla_ins` AFTER INSERT ON `conceptos_planilla` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_concepto', NEW.`id_concepto`, 'nombre', NEW.`nombre`, 'tipo', NEW.`tipo`, 'forma', NEW.`forma`, 'valor_defecto', NEW.`valor_defecto`, 'estado', NEW.`estado`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('conceptos_planilla', NEW.`id_concepto`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_conceptos_planilla_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_conceptos_planilla_upd` AFTER UPDATE ON `conceptos_planilla` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_concepto', OLD.`id_concepto`, 'nombre', OLD.`nombre`, 'tipo', OLD.`tipo`, 'forma', OLD.`forma`, 'valor_defecto', OLD.`valor_defecto`, 'estado', OLD.`estado`);
  SET V_DESPUES = JSON_OBJECT('id_concepto', NEW.`id_concepto`, 'nombre', NEW.`nombre`, 'tipo', NEW.`tipo`, 'forma', NEW.`forma`, 'valor_defecto', NEW.`valor_defecto`, 'estado', NEW.`estado`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('conceptos_planilla', NEW.`id_concepto`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_conceptos_planilla_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_conceptos_planilla_del` AFTER DELETE ON `conceptos_planilla` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_concepto', OLD.`id_concepto`, 'nombre', OLD.`nombre`, 'tipo', OLD.`tipo`, 'forma', OLD.`forma`, 'valor_defecto', OLD.`valor_defecto`, 'estado', OLD.`estado`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('conceptos_planilla', OLD.`id_concepto`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_indicadores_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_indicadores_ins` AFTER INSERT ON `indicadores` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_indicadores', NEW.`id_indicadores`, 'codigo', NEW.`codigo`, 'tipo_indicador', NEW.`tipo_indicador`, 'padre_id', NEW.`padre_id`, 'es_grupo', NEW.`es_grupo`, 'nombre', NEW.`nombre`, 'descripcion', NEW.`descripcion`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('indicadores', NEW.`id_indicadores`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_indicadores_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_indicadores_upd` AFTER UPDATE ON `indicadores` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_indicadores', OLD.`id_indicadores`, 'codigo', OLD.`codigo`, 'tipo_indicador', OLD.`tipo_indicador`, 'padre_id', OLD.`padre_id`, 'es_grupo', OLD.`es_grupo`, 'nombre', OLD.`nombre`, 'descripcion', OLD.`descripcion`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_indicadores', NEW.`id_indicadores`, 'codigo', NEW.`codigo`, 'tipo_indicador', NEW.`tipo_indicador`, 'padre_id', NEW.`padre_id`, 'es_grupo', NEW.`es_grupo`, 'nombre', NEW.`nombre`, 'descripcion', NEW.`descripcion`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('indicadores', NEW.`id_indicadores`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_indicadores_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_indicadores_del` AFTER DELETE ON `indicadores` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_indicadores', OLD.`id_indicadores`, 'codigo', OLD.`codigo`, 'tipo_indicador', OLD.`tipo_indicador`, 'padre_id', OLD.`padre_id`, 'es_grupo', OLD.`es_grupo`, 'nombre', OLD.`nombre`, 'descripcion', OLD.`descripcion`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('indicadores', OLD.`id_indicadores`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_empresa_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_empresa_ins` AFTER INSERT ON `empresa` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('empresa_id', NEW.`empresa_id`, 'emp_razon', NEW.`emp_razon`, 'emp_email', NEW.`emp_email`, 'emp_cod', NEW.`emp_cod`, 'emp_telefono', NEW.`emp_telefono`, 'emp_direccion', NEW.`emp_direccion`, 'emp_logo', NEW.`emp_logo`, 'pais_id', NEW.`pais_id`, 'moneda_id', NEW.`moneda_id`, 'zona_horaria', NEW.`zona_horaria`, 'identificacion_tributaria', NEW.`identificacion_tributaria`, 'permite_doc_extranjero', NEW.`permite_doc_extranjero`, 'serie_recibo', NEW.`serie_recibo`, 'created_at', NEW.`created_at`, 'nota_min', NEW.`nota_min`, 'nota_max', NEW.`nota_max`, 'nota_aprobatoria', NEW.`nota_aprobatoria`, 'nota_decimales', NEW.`nota_decimales`, 'notas_literales', NEW.`notas_literales`, 'literales_aprobatorios', NEW.`literales_aprobatorios`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('empresa', NEW.`empresa_id`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_empresa_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_empresa_upd` AFTER UPDATE ON `empresa` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('empresa_id', OLD.`empresa_id`, 'emp_razon', OLD.`emp_razon`, 'emp_email', OLD.`emp_email`, 'emp_cod', OLD.`emp_cod`, 'emp_telefono', OLD.`emp_telefono`, 'emp_direccion', OLD.`emp_direccion`, 'emp_logo', OLD.`emp_logo`, 'pais_id', OLD.`pais_id`, 'moneda_id', OLD.`moneda_id`, 'zona_horaria', OLD.`zona_horaria`, 'identificacion_tributaria', OLD.`identificacion_tributaria`, 'permite_doc_extranjero', OLD.`permite_doc_extranjero`, 'serie_recibo', OLD.`serie_recibo`, 'created_at', OLD.`created_at`, 'nota_min', OLD.`nota_min`, 'nota_max', OLD.`nota_max`, 'nota_aprobatoria', OLD.`nota_aprobatoria`, 'nota_decimales', OLD.`nota_decimales`, 'notas_literales', OLD.`notas_literales`, 'literales_aprobatorios', OLD.`literales_aprobatorios`);
  SET V_DESPUES = JSON_OBJECT('empresa_id', NEW.`empresa_id`, 'emp_razon', NEW.`emp_razon`, 'emp_email', NEW.`emp_email`, 'emp_cod', NEW.`emp_cod`, 'emp_telefono', NEW.`emp_telefono`, 'emp_direccion', NEW.`emp_direccion`, 'emp_logo', NEW.`emp_logo`, 'pais_id', NEW.`pais_id`, 'moneda_id', NEW.`moneda_id`, 'zona_horaria', NEW.`zona_horaria`, 'identificacion_tributaria', NEW.`identificacion_tributaria`, 'permite_doc_extranjero', NEW.`permite_doc_extranjero`, 'serie_recibo', NEW.`serie_recibo`, 'created_at', NEW.`created_at`, 'nota_min', NEW.`nota_min`, 'nota_max', NEW.`nota_max`, 'nota_aprobatoria', NEW.`nota_aprobatoria`, 'nota_decimales', NEW.`nota_decimales`, 'notas_literales', NEW.`notas_literales`, 'literales_aprobatorios', NEW.`literales_aprobatorios`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('empresa', NEW.`empresa_id`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_empresa_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_empresa_del` AFTER DELETE ON `empresa` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('empresa_id', OLD.`empresa_id`, 'emp_razon', OLD.`emp_razon`, 'emp_email', OLD.`emp_email`, 'emp_cod', OLD.`emp_cod`, 'emp_telefono', OLD.`emp_telefono`, 'emp_direccion', OLD.`emp_direccion`, 'emp_logo', OLD.`emp_logo`, 'pais_id', OLD.`pais_id`, 'moneda_id', OLD.`moneda_id`, 'zona_horaria', OLD.`zona_horaria`, 'identificacion_tributaria', OLD.`identificacion_tributaria`, 'permite_doc_extranjero', OLD.`permite_doc_extranjero`, 'serie_recibo', OLD.`serie_recibo`, 'created_at', OLD.`created_at`, 'nota_min', OLD.`nota_min`, 'nota_max', OLD.`nota_max`, 'nota_aprobatoria', OLD.`nota_aprobatoria`, 'nota_decimales', OLD.`nota_decimales`, 'notas_literales', OLD.`notas_literales`, 'literales_aprobatorios', OLD.`literales_aprobatorios`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('empresa', OLD.`empresa_id`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_usuario_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_usuario_ins` AFTER INSERT ON `usuario` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('usu_id', NEW.`usu_id`, 'usu_usuario', NEW.`usu_usuario`, 'usu_email', NEW.`usu_email`, 'usu_estatus', NEW.`usu_estatus`, 'rol_id', NEW.`rol_id`, 'empresa_id', NEW.`empresa_id`, 'created_at', NEW.`created_at`, 'contrasena', MD5(NEW.usu_contra));
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('usuario', NEW.`usu_id`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_usuario_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_usuario_upd` AFTER UPDATE ON `usuario` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('usu_id', OLD.`usu_id`, 'usu_usuario', OLD.`usu_usuario`, 'usu_email', OLD.`usu_email`, 'usu_estatus', OLD.`usu_estatus`, 'rol_id', OLD.`rol_id`, 'empresa_id', OLD.`empresa_id`, 'created_at', OLD.`created_at`, 'contrasena', MD5(OLD.usu_contra));
  SET V_DESPUES = JSON_OBJECT('usu_id', NEW.`usu_id`, 'usu_usuario', NEW.`usu_usuario`, 'usu_email', NEW.`usu_email`, 'usu_estatus', NEW.`usu_estatus`, 'rol_id', NEW.`rol_id`, 'empresa_id', NEW.`empresa_id`, 'created_at', NEW.`created_at`, 'contrasena', MD5(NEW.usu_contra));
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('usuario', NEW.`usu_id`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_usuario_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_usuario_del` AFTER DELETE ON `usuario` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('usu_id', OLD.`usu_id`, 'usu_usuario', OLD.`usu_usuario`, 'usu_email', OLD.`usu_email`, 'usu_estatus', OLD.`usu_estatus`, 'rol_id', OLD.`rol_id`, 'empresa_id', OLD.`empresa_id`, 'created_at', OLD.`created_at`, 'contrasena', MD5(OLD.usu_contra));
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('usuario', OLD.`usu_id`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;
