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
