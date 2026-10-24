-- =====================================================================
-- Fase 8 (bloque A): integridad de datos en pensiones, usuarios y categorías
--   1. Cada pensión pertenece a un año escolar (antes solo nivel + mes)
--   2. Ajuste de vencimientos al calendario del año escolar (lo decide el usuario)
--   3. Generar el cronograma de pensiones de un nivel en un paso
--   4. Usuarios únicos: se separan los repetidos y se agrega un índice único
--   5. Categorías con grupo y código del plan de cuentas; no se borran si tienen movimientos
-- =====================================================================

-- ---------- 1. Pensiones por año escolar ----------
ALTER TABLE pensiones ADD COLUMN id_anio INT NULL AFTER id_pensiones;

-- a) El año de las matrículas que la pagaron
UPDATE pensiones pe
   SET id_anio = (SELECT m.`id_año` FROM pago_pensiones pp INNER JOIN matricula m ON m.id_matricula = pp.id_matri
                   WHERE pp.id_pension = pe.id_pensiones
                   GROUP BY m.`id_año` ORDER BY COUNT(*) DESC LIMIT 1);
-- b) Sin pagos: el año escolar cuyo calendario contiene el vencimiento
UPDATE pensiones pe
   SET id_anio = (SELECT ae.`Id_año_escolar` FROM `año_escolar` ae
                   WHERE pe.fecha_vencimiento BETWEEN ae.fecha_inicio - INTERVAL 2 MONTH AND ae.fecha_fin + INTERVAL 2 MONTH
                   ORDER BY ae.fecha_inicio DESC LIMIT 1)
 WHERE id_anio IS NULL;
-- c) Resto: el año escolar activo (el colegio usa la tabla como calendario del año en curso)
UPDATE pensiones
   SET id_anio = (SELECT `Id_año_escolar` FROM `año_escolar` ORDER BY estado = 'ACTIVO' DESC, fecha_inicio DESC LIMIT 1)
 WHERE id_anio IS NULL;

ALTER TABLE pensiones
  MODIFY id_anio INT NOT NULL,
  ADD CONSTRAINT fk_pension_anio FOREIGN KEY (id_anio) REFERENCES `año_escolar` (`Id_año_escolar`),
  ADD UNIQUE KEY uk_pension_anio_nivel_mes (id_anio, id_nivel_academico, mes);

-- Historial de vencimientos ajustados (para revisar o revertir)
CREATE TABLE IF NOT EXISTS pensiones_ajustes (
  id_ajuste INT AUTO_INCREMENT PRIMARY KEY,
  id_pension INT NOT NULL,
  fecha_anterior DATE NOT NULL,
  fecha_nueva DATE NOT NULL,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_ajuste_pension (id_pension)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fecha del mes P_MES (1-12) dentro del calendario del año escolar, con el día indicado
-- (0 o mayor que el último día = último día del mes). Los meses anteriores al de inicio caen en el año siguiente.
DROP FUNCTION IF EXISTS FN_FECHA_EN_ANIO_ESCOLAR;
DELIMITER $$
CREATE FUNCTION FN_FECHA_EN_ANIO_ESCOLAR(P_ANIO INT, P_MES INT, P_DIA INT) RETURNS DATE
READS SQL DATA
BEGIN
  DECLARE V_INICIO DATE;
  DECLARE V_PRIMERO DATE;
  SELECT fecha_inicio INTO V_INICIO FROM `año_escolar` WHERE `Id_año_escolar` = P_ANIO;
  IF V_INICIO IS NULL OR P_MES NOT BETWEEN 1 AND 12 THEN RETURN NULL; END IF;
  SET V_PRIMERO = MAKEDATE(YEAR(V_INICIO) + IF(P_MES < MONTH(V_INICIO), 1, 0), 1) + INTERVAL (P_MES - 1) MONTH;
  IF P_DIA IS NULL OR P_DIA <= 0 OR P_DIA > DAY(LAST_DAY(V_PRIMERO)) THEN
    RETURN LAST_DAY(V_PRIMERO);
  END IF;
  RETURN V_PRIMERO + INTERVAL (P_DIA - 1) DAY;
END$$
DELIMITER ;

-- ¿El vencimiento está dentro del calendario del año escolar? (con 2 meses de margen)
DROP FUNCTION IF EXISTS FN_VENCIMIENTO_EN_ANIO;
DELIMITER $$
CREATE FUNCTION FN_VENCIMIENTO_EN_ANIO(P_ANIO INT, P_FECHA DATE) RETURNS TINYINT
READS SQL DATA
BEGIN
  RETURN (SELECT COUNT(*) FROM `año_escolar`
           WHERE `Id_año_escolar` = P_ANIO
             AND P_FECHA BETWEEN fecha_inicio - INTERVAL 2 MONTH AND fecha_fin + INTERVAL 2 MONTH) > 0;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_PENSIONES_FILTRO;
DROP PROCEDURE IF EXISTS SP_LISTAR_PENSIONES;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_PENSIONES(IN P_ANIO INT, IN P_NIVEL INT)
BEGIN
  SELECT pe.id_pensiones, pe.id_anio, ae.`año_escolar` AS anio_escolar, pe.id_nivel_academico, n.Nivel_academico,
         pe.mes, FIELD(pe.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_num,
         pe.fecha_vencimiento, DATE_FORMAT(pe.fecha_vencimiento, '%d-%m-%Y') AS fecha_formateada,
         pe.precio, pe.precio AS PRECIO, pe.mora, pe.mora AS MORA,
         FN_VENCIMIENTO_EN_ANIO(pe.id_anio, pe.fecha_vencimiento) AS fecha_en_anio,
         (SELECT COUNT(*) FROM pago_pensiones pp WHERE pp.id_pension = pe.id_pensiones AND pp.estado = 'VALIDO') AS pagos,
         pe.created_at, pe.updated_at
    FROM pensiones pe
   INNER JOIN nivel_academico n ON n.Id_nivel = pe.id_nivel_academico
   INNER JOIN `año_escolar` ae ON ae.`Id_año_escolar` = pe.id_anio
   WHERE (P_ANIO = 0 OR pe.id_anio = P_ANIO) AND (P_NIVEL = 0 OR pe.id_nivel_academico = P_NIVEL)
   ORDER BY ae.fecha_inicio DESC, n.Id_nivel, mes_num;
END$$
DELIMITER ;

-- Códigos: 1 registrada, 2 ya existe ese año/nivel/mes, 3 vencimiento fuera del año escolar, 0 datos inválidos
DROP PROCEDURE IF EXISTS SP_REGISTRAR_PENSIONES;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_PENSIONES(IN P_ANIO INT, IN P_NIVEL INT, IN P_MES VARCHAR(30), IN P_FECHA_VEN DATE,
                                        IN P_PRECIO DECIMAL(14,2), IN P_MORA DECIMAL(14,2))
proc: BEGIN
  IF FIELD(P_MES, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') = 0
     OR NOT EXISTS (SELECT 1 FROM nivel_academico WHERE Id_nivel = P_NIVEL)
     OR NOT EXISTS (SELECT 1 FROM `año_escolar` WHERE `Id_año_escolar` = P_ANIO) THEN
    SELECT 0; LEAVE proc;
  END IF;
  IF FN_VENCIMIENTO_EN_ANIO(P_ANIO, P_FECHA_VEN) = 0 THEN SELECT 3; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM pensiones WHERE id_anio = P_ANIO AND id_nivel_academico = P_NIVEL AND mes = P_MES) THEN
    SELECT 2; LEAVE proc;
  END IF;
  INSERT INTO pensiones(id_anio, id_nivel_academico, mes, fecha_vencimiento, precio, mora, created_at, updated_at)
  VALUES (P_ANIO, P_NIVEL, P_MES, P_FECHA_VEN, P_PRECIO, P_MORA, NOW(), NULL);
  SELECT 1;
END$$
DELIMITER ;

-- Códigos: 1 actualizada, 2 ya existe, 3 vencimiento fuera del año, 4 tiene pagos y se quiso cambiar año/nivel/mes, 0 inválido
DROP PROCEDURE IF EXISTS SP_MODIFICAR_PENSIONES;
DELIMITER $$
CREATE PROCEDURE SP_MODIFICAR_PENSIONES(IN P_ID INT, IN P_ANIO INT, IN P_NIVEL INT, IN P_MES VARCHAR(30), IN P_FECHA_VEN DATE,
                                        IN P_PRECIO DECIMAL(14,2), IN P_MORA DECIMAL(14,2))
proc: BEGIN
  DECLARE V_ANIO, V_NIVEL INT;
  DECLARE V_MES VARCHAR(30);
  SELECT id_anio, id_nivel_academico, mes INTO V_ANIO, V_NIVEL, V_MES FROM pensiones WHERE id_pensiones = P_ID;
  IF V_ANIO IS NULL OR FIELD(P_MES, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') = 0 THEN
    SELECT 0; LEAVE proc;
  END IF;
  IF (V_ANIO <> P_ANIO OR V_NIVEL <> P_NIVEL OR V_MES <> P_MES)
     AND EXISTS (SELECT 1 FROM pago_pensiones WHERE id_pension = P_ID AND estado = 'VALIDO') THEN
    SELECT 4; LEAVE proc;
  END IF;
  IF FN_VENCIMIENTO_EN_ANIO(P_ANIO, P_FECHA_VEN) = 0 THEN SELECT 3; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM pensiones WHERE id_anio = P_ANIO AND id_nivel_academico = P_NIVEL AND mes = P_MES AND id_pensiones <> P_ID) THEN
    SELECT 2; LEAVE proc;
  END IF;
  UPDATE pensiones SET id_anio = P_ANIO, id_nivel_academico = P_NIVEL, mes = P_MES, fecha_vencimiento = P_FECHA_VEN,
         precio = P_PRECIO, mora = P_MORA, updated_at = NOW()
   WHERE id_pensiones = P_ID;
  SELECT 1;
END$$
DELIMITER ;

-- Crea las pensiones de un nivel para los meses indicados (puede cruzar el fin de año: SEPTIEMBRE → JUNIO).
-- No toca las que ya existen. Devuelve cuántas se crearon y cuántas ya estaban.
DROP PROCEDURE IF EXISTS SP_GENERAR_PENSIONES;
DELIMITER $$
CREATE PROCEDURE SP_GENERAR_PENSIONES(IN P_ANIO INT, IN P_NIVEL INT, IN P_MES_DESDE INT, IN P_MES_HASTA INT,
                                      IN P_DIA INT, IN P_PRECIO DECIMAL(14,2), IN P_MORA DECIMAL(14,2))
proc: BEGIN
  DECLARE V_MES, V_CREADAS, V_EXISTEN, V_PASOS INT DEFAULT 0;
  DECLARE V_NOMBRE VARCHAR(20);
  IF NOT EXISTS (SELECT 1 FROM `año_escolar` WHERE `Id_año_escolar` = P_ANIO)
     OR NOT EXISTS (SELECT 1 FROM nivel_academico WHERE Id_nivel = P_NIVEL)
     OR P_MES_DESDE NOT BETWEEN 1 AND 12 OR P_MES_HASTA NOT BETWEEN 1 AND 12 OR P_PRECIO <= 0 OR P_MORA < 0 THEN
    SELECT 0 AS codigo, 'Datos inválidos' AS mensaje, 0 AS creadas, 0 AS existentes; LEAVE proc;
  END IF;
  SET V_MES = P_MES_DESDE;
  ciclo: LOOP
    SET V_NOMBRE = ELT(V_MES, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE');
    IF EXISTS (SELECT 1 FROM pensiones WHERE id_anio = P_ANIO AND id_nivel_academico = P_NIVEL AND mes = V_NOMBRE) THEN
      SET V_EXISTEN = V_EXISTEN + 1;
    ELSE
      INSERT INTO pensiones(id_anio, id_nivel_academico, mes, fecha_vencimiento, precio, mora, created_at, updated_at)
      VALUES (P_ANIO, P_NIVEL, V_NOMBRE, FN_FECHA_EN_ANIO_ESCOLAR(P_ANIO, V_MES, P_DIA), P_PRECIO, P_MORA, NOW(), NULL);
      SET V_CREADAS = V_CREADAS + 1;
    END IF;
    SET V_PASOS = V_PASOS + 1;
    IF V_MES = P_MES_HASTA OR V_PASOS >= 12 THEN LEAVE ciclo; END IF;
    SET V_MES = IF(V_MES = 12, 1, V_MES + 1);
  END LOOP;
  SELECT 1 AS codigo, CONCAT(V_CREADAS, ' pensión(es) creada(s)', IF(V_EXISTEN > 0, CONCAT('; ', V_EXISTEN, ' ya existían y no se modificaron'), '')) AS mensaje,
         V_CREADAS AS creadas, V_EXISTEN AS existentes;
END$$
DELIMITER ;

-- Pensiones del año cuyo vencimiento cae fuera de su calendario, con la fecha propuesta
-- (mismo día y mes del campo "mes" dentro del año escolar). Con P_APLICAR = 1 las corrige y deja historial.
DROP PROCEDURE IF EXISTS SP_AJUSTAR_VENCIMIENTOS;
DELIMITER $$
CREATE PROCEDURE SP_AJUSTAR_VENCIMIENTOS(IN P_ANIO INT, IN P_APLICAR TINYINT, IN P_USU INT)
BEGIN
  DROP TEMPORARY TABLE IF EXISTS tmp_ajuste;
  CREATE TEMPORARY TABLE tmp_ajuste AS
  SELECT pe.id_pensiones, n.Nivel_academico AS nivel, pe.mes, pe.fecha_vencimiento AS fecha_actual,
         FN_FECHA_EN_ANIO_ESCOLAR(pe.id_anio,
             FIELD(pe.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'),
             DAY(pe.fecha_vencimiento)) AS fecha_nueva
    FROM pensiones pe INNER JOIN nivel_academico n ON n.Id_nivel = pe.id_nivel_academico
   WHERE pe.id_anio = P_ANIO AND FN_VENCIMIENTO_EN_ANIO(pe.id_anio, pe.fecha_vencimiento) = 0;
  IF P_APLICAR = 1 THEN
    INSERT INTO pensiones_ajustes(id_pension, fecha_anterior, fecha_nueva, usuario_id, created_at)
    SELECT id_pensiones, fecha_actual, fecha_nueva, P_USU, NOW() FROM tmp_ajuste WHERE fecha_nueva IS NOT NULL;
    UPDATE pensiones pe INNER JOIN tmp_ajuste t ON t.id_pensiones = pe.id_pensiones
       SET pe.fecha_vencimiento = t.fecha_nueva, pe.updated_at = NOW()
     WHERE t.fecha_nueva IS NOT NULL;
  END IF;
  SELECT id_pensiones, nivel, mes, fecha_actual, fecha_nueva FROM tmp_ajuste ORDER BY nivel, fecha_nueva;
  DROP TEMPORARY TABLE tmp_ajuste;
END$$
DELIMITER ;

-- Selector de pensiones al cobrar: solo las del nivel Y del año escolar de la matrícula
DROP PROCEDURE IF EXISTS SP_CARGAR_SELECT_PENSION_MATRICULA;
DELIMITER $$
CREATE PROCEDURE SP_CARGAR_SELECT_PENSION_MATRICULA(IN P_MATRI INT)
BEGIN
  SELECT pe.id_pensiones,
         CONCAT(pe.mes, ' ', YEAR(pe.fecha_vencimiento),
                IF(IFNULL(pg.pagado, 0) >= pe.precio, ' (PAGADO)',
                   IF(IFNULL(pg.pagado, 0) > 0, ' (PARCIAL)', ''))) AS texto,
         pe.fecha_vencimiento, pe.precio, IFNULL(pg.pagado, 0) AS pagado
    FROM matricula m
    INNER JOIN aulas a ON a.Id_aula = m.id_aula
    INNER JOIN pensiones pe ON pe.id_nivel_academico = a.id_nivel_academico AND pe.id_anio = m.`id_año`
    LEFT JOIN (SELECT id_pension, SUM(sub_total) AS pagado
                 FROM pago_pensiones
                WHERE id_matri = P_MATRI AND concepto = 'PENSION' AND estado = 'VALIDO'
                GROUP BY id_pension) pg ON pg.id_pension = pe.id_pensiones
   WHERE m.id_matricula = P_MATRI
   ORDER BY pe.fecha_vencimiento;
END$$
DELIMITER ;

-- Estado de pensiones (Fase 7): ahora solo cruza cada matrícula con las pensiones de su año escolar
DROP VIEW IF EXISTS vw_estado_pensiones;
CREATE VIEW vw_estado_pensiones AS
SELECT b.*,
       GREATEST(b.precio - b.pagado_pension, 0) AS saldo_pension,
       CASE WHEN b.exonerada = 1 OR b.vencida = 0 OR (b.precio - b.pagado_pension <= 0 AND b.pagado_mora = 0) THEN 0
            ELSE GREATEST(b.mora - b.pagado_mora, 0) END AS saldo_mora
  FROM (SELECT m.id_matricula, m.`id_año` AS id_anio, m.id_alumno, a.Id_aula AS id_aula, a.id_nivel_academico AS id_nivel,
               pe.id_pensiones AS id_pension, pe.mes, FIELD(pe.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO',
               'AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_num,
               pe.fecha_vencimiento, pe.precio, IFNULL(pe.mora, 0) AS mora,
               IFNULL(pg.pagado_pension, 0) AS pagado_pension, IFNULL(pg.pagado_mora, 0) AS pagado_mora,
               IFNULL(pg.exonerada, 0) AS exonerada,
               (IFNULL(pg.a_tiempo, 0) < pe.precio AND CURDATE() > pe.fecha_vencimiento) AS vencida
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
                 ON pg.id_matri = m.id_matricula AND pg.id_pension = pe.id_pensiones) b;

-- Aviso de la morosidad (Fase 7): misma regla que el ajuste de vencimientos
DROP PROCEDURE IF EXISTS SP_PENSIONES_FUERA_DE_ANIO;
DELIMITER $$
CREATE PROCEDURE SP_PENSIONES_FUERA_DE_ANIO(IN P_ANIO INT)
BEGIN
  SELECT n.Nivel_academico AS nivel, pe.mes, pe.fecha_vencimiento
    FROM pensiones pe
   INNER JOIN nivel_academico n ON n.Id_nivel = pe.id_nivel_academico
   WHERE pe.id_anio = P_ANIO AND FN_VENCIMIENTO_EN_ANIO(pe.id_anio, pe.fecha_vencimiento) = 0
   ORDER BY n.Nivel_academico, FIELD(pe.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO',
            'AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE');
END$$
DELIMITER ;

-- ---------- 4. Usuarios únicos ----------
CREATE TABLE IF NOT EXISTS usuarios_renombrados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usu_id INT NOT NULL,
  usuario_anterior VARCHAR(250) NOT NULL,
  usuario_nuevo VARCHAR(250) NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Se conserva el primer usuario de cada nombre (el de menor id con matrícula/docente/personal, o el menor id);
-- los demás pasan a usar el documento de su alumno/docente/personal o, si no lo tienen, nombre_id.
-- Los que no pertenecen a nadie quedan INACTIVOS.
DROP TEMPORARY TABLE IF EXISTS tmp_usu;
CREATE TEMPORARY TABLE tmp_usu AS
SELECT u.usu_id, u.usu_usuario,
       COALESCE((SELECT a.alum_dni FROM matricula m INNER JOIN alumnos a ON a.Id_alumno = m.id_alumno WHERE m.usu_id = u.usu_id LIMIT 1),
                (SELECT d.docente_dni FROM docentes d WHERE d.id_asusuario = u.usu_id LIMIT 1),
                (SELECT p.personal_adm_dni FROM personal_admi p WHERE p.id_ausuario = u.usu_id LIMIT 1)) AS documento,
       (EXISTS (SELECT 1 FROM matricula m WHERE m.usu_id = u.usu_id)
        OR EXISTS (SELECT 1 FROM docentes d WHERE d.id_asusuario = u.usu_id)
        OR EXISTS (SELECT 1 FROM personal_admi p WHERE p.id_ausuario = u.usu_id)) AS en_uso
  FROM usuario u;
DROP TEMPORARY TABLE IF EXISTS tmp_usu2;
CREATE TEMPORARY TABLE tmp_usu2 AS SELECT * FROM tmp_usu;
-- Usuario a cambiar: nombre vacío, o repetido y no es el que se conserva
DROP TEMPORARY TABLE IF EXISTS tmp_cambio;
CREATE TEMPORARY TABLE tmp_cambio AS
SELECT t.usu_id, IFNULL(t.usu_usuario, '') AS anterior, t.documento, t.en_uso
  FROM tmp_usu t
 WHERE TRIM(IFNULL(t.usu_usuario, '')) = ''
    OR t.usu_id <> (SELECT t2.usu_id FROM tmp_usu2 t2 WHERE t2.usu_usuario = t.usu_usuario
                     ORDER BY t2.en_uso DESC, t2.usu_id LIMIT 1);
UPDATE usuario u INNER JOIN tmp_cambio c ON c.usu_id = u.usu_id
   SET u.usu_usuario = IF(c.documento IS NOT NULL AND c.documento <> ''
                          AND NOT EXISTS (SELECT 1 FROM tmp_usu2 x WHERE x.usu_usuario = c.documento),
                          c.documento,
                          CONCAT(IF(c.anterior = '', 'USUARIO', c.anterior), '_', c.usu_id)),
       u.usu_estatus = IF(c.en_uso, u.usu_estatus, 'INACTIVO'),
       u.updated_at = NOW();
INSERT INTO usuarios_renombrados(usu_id, usuario_anterior, usuario_nuevo, motivo, created_at)
SELECT c.usu_id, c.anterior, u.usu_usuario,
       IF(c.anterior = '', 'Usuario vacío', IF(c.en_uso, 'Nombre de usuario repetido', 'Repetido y sin alumno/docente/personal: se desactivó')),
       NOW()
  FROM tmp_cambio c INNER JOIN usuario u ON u.usu_id = c.usu_id;
DROP TEMPORARY TABLE tmp_cambio;
DROP TEMPORARY TABLE tmp_usu2;
DROP TEMPORARY TABLE tmp_usu;

ALTER TABLE usuario ADD UNIQUE KEY uk_usu_usuario (usu_usuario);

-- Alta de docente: 3 = usuario vacío o ya existe; el docente queda unido a SU usuario (antes MAX(usu_id))
DROP PROCEDURE IF EXISTS SP_REGISTRAR_DOCENTES;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_DOCENTES`(IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `ESPE` INT, IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255))
proc: BEGIN
  DECLARE V_USU INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF EXISTS (SELECT 1 FROM docentes WHERE docente_dni = DNI) THEN SELECT 2; LEAVE proc; END IF;
  IF TRIM(IFNULL(USU, '')) = '' OR EXISTS (SELECT 1 FROM usuario WHERE usu_usuario = TRIM(USU)) THEN SELECT 3; LEAVE proc; END IF;
  START TRANSACTION;
  INSERT INTO usuario(usu_usuario, usu_contra, usu_email, usu_estatus, rol_id, empresa_id, created_at, updated_at)
  VALUES (TRIM(USU), CONTRA, EMAIL, 'ACTIVO', 2, 1, NOW(), NOW());
  SET V_USU = LAST_INSERT_ID();
  INSERT INTO docentes(docente_dni, docente_nombre, docente_apelli, especialidad_id, docente_sexo, docente_fechanacimiento,
                       docente_movil, docente_nro_alterno, docente_direccion, docente_estatus, docente_fotoperfil, id_asusuario, created_at, updated_at)
  VALUES (DNI, NOMBRES, APELLI, ESPE, SEXO, FECHANAC, CEL, CELALT, DIREC, 'ACTIVO', FOTO, V_USU, NOW(), NOW());
  COMMIT;
  SELECT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_REGISTRAR_PERSONAL;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_PERSONAL`(IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `TIPO` VARCHAR(30), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `ROL` INT)
proc: BEGIN
  DECLARE V_USU INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF EXISTS (SELECT 1 FROM personal_admi WHERE personal_adm_dni = DNI) THEN SELECT 2; LEAVE proc; END IF;
  IF TRIM(IFNULL(USU, '')) = '' OR EXISTS (SELECT 1 FROM usuario WHERE usu_usuario = TRIM(USU)) THEN SELECT 3; LEAVE proc; END IF;
  START TRANSACTION;
  INSERT INTO usuario(usu_usuario, usu_contra, usu_email, usu_estatus, rol_id, empresa_id, created_at, updated_at)
  VALUES (TRIM(USU), CONTRA, EMAIL, 'ACTIVO', ROL, 1, NOW(), NOW());
  SET V_USU = LAST_INSERT_ID();
  INSERT INTO personal_admi(personal_adm_dni, personal_adm_nombre, personal_adm_apellido, personal_adm_tipo, personal_adm_sexo,
                            personal_adm_fechanacimiento, personal_adm_movil, personal_adm_nro_alterno, personal_adm_direccion,
                            personal_adm_estatus, personal_adm_fotoperfil, id_ausuario, created_at, updated_at)
  VALUES (DNI, NOMBRES, APELLI, TIPO, SEXO, FECHANAC, CEL, CELALT, DIREC, 'ACTIVO', FOTO, V_USU, NOW(), NOW());
  COMMIT;
  SELECT 1;
END$$
DELIMITER ;

-- Usaba columnas que no existen (empleado_id, area_id, usu_rol…): fallaba siempre
DROP PROCEDURE IF EXISTS SP_REGISTRAR_USUARIO;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_USUARIO`(IN `USU` VARCHAR(255), IN `CONTRA` VARCHAR(255), IN `IDEMPLEADO` INT, IN `IDAREA` INT, IN `ROL` VARCHAR(25))
proc: BEGIN
  DECLARE V_ROL INT;
  SELECT Id_rol INTO V_ROL FROM roles WHERE Id_rol = ROL OR tipo_rol = ROL LIMIT 1;
  IF V_ROL IS NULL OR TRIM(IFNULL(USU, '')) = '' THEN SELECT 0; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM usuario WHERE usu_usuario = TRIM(USU)) THEN SELECT 2; LEAVE proc; END IF;
  INSERT INTO usuario(usu_usuario, usu_contra, usu_estatus, rol_id, empresa_id, created_at, updated_at)
  VALUES (TRIM(USU), CONTRA, 'ACTIVO', V_ROL, 1, NOW(), NOW());
  SELECT 1;
END$$
DELIMITER ;

-- 1 actualizado, 2 el nombre de usuario ya lo usa otra cuenta, 0 vacío
DROP PROCEDURE IF EXISTS SP_MODIFICAR_USUARIO;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_USUARIO`(IN `ID` INT, IN `USUARIO` VARCHAR(250), IN `ROL` INT, IN `EMAIL` VARCHAR(255))
proc: BEGIN
  IF TRIM(IFNULL(USUARIO, '')) = '' THEN SELECT 0; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM usuario WHERE usu_usuario = TRIM(USUARIO) AND usu_id <> ID) THEN SELECT 2; LEAVE proc; END IF;
  UPDATE usuario SET usu_usuario = TRIM(USUARIO), rol_id = ROL, usu_email = EMAIL, updated_at = NOW() WHERE usu_id = ID;
  SELECT 1;
END$$
DELIMITER ;

-- ---------- 5. Categorías (indicadores) con grupo ----------
-- Código a las categorías que no lo tenían: <código del grupo>.<n>
UPDATE indicadores i
   INNER JOIN (SELECT c.id_indicadores,
                      CONCAT(g.codigo, '.', (SELECT COUNT(*) FROM indicadores h WHERE h.padre_id = g.id_indicadores AND h.codigo IS NOT NULL)
                             + (SELECT COUNT(*) FROM indicadores h2 WHERE h2.padre_id = g.id_indicadores AND h2.codigo IS NULL AND h2.id_indicadores <= c.id_indicadores)) AS nuevo
                 FROM indicadores c INNER JOIN indicadores g ON g.id_indicadores = c.padre_id
                WHERE c.codigo IS NULL AND c.es_grupo = 0) x ON x.id_indicadores = i.id_indicadores
   SET i.codigo = x.nuevo;

DROP FUNCTION IF EXISTS FN_INDICADOR_DEL_SISTEMA;
DELIMITER $$
-- Categorías que usa el sistema (cobranza, planilla, comisiones): no se borran ni cambian de tipo/grupo
CREATE FUNCTION FN_INDICADOR_DEL_SISTEMA(P_ID INT) RETURNS TINYINT
READS SQL DATA
BEGIN
  RETURN P_ID = 1 OR EXISTS (SELECT 1 FROM indicadores WHERE id_indicadores = P_ID AND (es_grupo = 1 OR codigo IN ('5.1.1', '5.1.2', '5.5.1')));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS SP_LISTAR_INDICADORES;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_INDICADORES`()
BEGIN
  SELECT i.id_indicadores, i.codigo, i.tipo_indicador, i.padre_id, i.es_grupo, IFNULL(g.nombre, '') AS grupo,
         i.nombre, i.descripcion, i.estado, i.created_at, DATE_FORMAT(i.created_at, '%d-%m-%Y') AS fecha_formateada, i.updated_at,
         (SELECT COUNT(*) FROM ingresos x WHERE x.id_indicador = i.id_indicadores) + (SELECT COUNT(*) FROM egresos e WHERE e.id_indicador = i.id_indicadores) AS usos,
         (SELECT COUNT(*) FROM indicadores h WHERE h.padre_id = i.id_indicadores) AS hijos,
         FN_INDICADOR_DEL_SISTEMA(i.id_indicadores) AS del_sistema
    FROM indicadores i LEFT JOIN indicadores g ON g.id_indicadores = i.padre_id
   ORDER BY i.tipo_indicador = 'GASTOS', IFNULL(g.codigo, i.codigo), i.es_grupo DESC, i.codigo, i.nombre;
END$$
DELIMITER ;

-- Grupos para el selector (por tipo)
DROP PROCEDURE IF EXISTS SP_LISTAR_GRUPOS_INDICADOR;
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_GRUPOS_INDICADOR()
BEGIN
  SELECT id_indicadores, codigo, tipo_indicador, nombre FROM indicadores WHERE es_grupo = 1 AND estado = 'ACTIVO' ORDER BY codigo;
END$$
DELIMITER ;

-- 1 registrada, 2 ya existe, 3 grupo inválido (debe ser un grupo activo del mismo tipo)
DROP PROCEDURE IF EXISTS SP_REGISTRAR_INDICADORES;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_INDICADORES`(IN P_TIPO VARCHAR(20), IN P_NOMBRE VARCHAR(100), IN P_DESCRIP VARCHAR(255), IN P_PADRE INT)
proc: BEGIN
  DECLARE V_COD VARCHAR(10);
  DECLARE V_N INT;
  SELECT codigo INTO V_COD FROM indicadores WHERE id_indicadores = P_PADRE AND es_grupo = 1 AND estado = 'ACTIVO' AND tipo_indicador = P_TIPO;
  IF V_COD IS NULL THEN SELECT 3; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM indicadores WHERE tipo_indicador = P_TIPO AND nombre = TRIM(P_NOMBRE)) THEN SELECT 2; LEAVE proc; END IF;
  -- Siguiente número libre dentro del grupo
  SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(codigo, '.', -1) AS UNSIGNED)), 0) + 1 INTO V_N
    FROM indicadores WHERE padre_id = P_PADRE AND codigo LIKE CONCAT(V_COD, '.%');
  INSERT INTO indicadores(codigo, tipo_indicador, padre_id, es_grupo, nombre, descripcion, estado, created_at, updated_at)
  VALUES (CONCAT(V_COD, '.', V_N), P_TIPO, P_PADRE, 0, TRIM(P_NOMBRE), P_DESCRIP, 'ACTIVO', NOW(), NOW());
  SELECT 1;
END$$
DELIMITER ;

-- 1 actualizada, 2 nombre repetido, 3 grupo inválido, 4 no se puede cambiar el tipo o el grupo (del sistema o con movimientos de otro tipo)
DROP PROCEDURE IF EXISTS SP_MODIFICAR_INDICADOR;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_INDICADOR`(IN P_ID INT, IN P_TIPO VARCHAR(20), IN P_NOMBRE VARCHAR(100), IN P_DESCRIP VARCHAR(255), IN P_ESTADO VARCHAR(20), IN P_PADRE INT)
proc: BEGIN
  DECLARE V_TIPO VARCHAR(20);
  DECLARE V_PADRE, V_GRUPO INT;
  DECLARE V_COD VARCHAR(10);
  DECLARE V_N INT;
  SELECT tipo_indicador, padre_id, es_grupo INTO V_TIPO, V_PADRE, V_GRUPO FROM indicadores WHERE id_indicadores = P_ID;
  IF V_TIPO IS NULL OR P_ESTADO NOT IN ('ACTIVO', 'INACTIVO') THEN SELECT 0; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM indicadores WHERE tipo_indicador = P_TIPO AND nombre = TRIM(P_NOMBRE) AND id_indicadores <> P_ID) THEN
    SELECT 2; LEAVE proc;
  END IF;
  -- Un grupo solo cambia nombre, descripción y estado
  IF V_GRUPO = 1 THEN
    UPDATE indicadores SET nombre = TRIM(P_NOMBRE), descripcion = P_DESCRIP, estado = P_ESTADO, updated_at = NOW() WHERE id_indicadores = P_ID;
    SELECT 1; LEAVE proc;
  END IF;
  IF (P_TIPO <> V_TIPO OR NOT (P_PADRE <=> V_PADRE))
     AND (FN_INDICADOR_DEL_SISTEMA(P_ID) = 1
          OR (P_TIPO <> V_TIPO AND (EXISTS (SELECT 1 FROM ingresos WHERE id_indicador = P_ID) OR EXISTS (SELECT 1 FROM egresos WHERE id_indicador = P_ID)))) THEN
    SELECT 4; LEAVE proc;
  END IF;
  SELECT codigo INTO V_COD FROM indicadores WHERE id_indicadores = P_PADRE AND es_grupo = 1 AND tipo_indicador = P_TIPO;
  IF V_COD IS NULL THEN SELECT 3; LEAVE proc; END IF;
  IF NOT (P_PADRE <=> V_PADRE) THEN
    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(codigo, '.', -1) AS UNSIGNED)), 0) + 1 INTO V_N
      FROM indicadores WHERE padre_id = P_PADRE AND codigo LIKE CONCAT(V_COD, '.%');
    UPDATE indicadores SET padre_id = P_PADRE, codigo = CONCAT(V_COD, '.', V_N) WHERE id_indicadores = P_ID;
  END IF;
  UPDATE indicadores SET tipo_indicador = P_TIPO, nombre = TRIM(P_NOMBRE), descripcion = P_DESCRIP, estado = P_ESTADO, updated_at = NOW()
   WHERE id_indicadores = P_ID;
  SELECT 1;
END$$
DELIMITER ;

-- 1 eliminada, 2 tiene ingresos/egresos (desactívela), 3 es del sistema o un grupo con categorías
DROP PROCEDURE IF EXISTS SP_ELIMINAR_INDICADOR;
DELIMITER $$
CREATE PROCEDURE `SP_ELIMINAR_INDICADOR`(IN P_ID INT)
proc: BEGIN
  IF FN_INDICADOR_DEL_SISTEMA(P_ID) = 1 OR EXISTS (SELECT 1 FROM indicadores WHERE padre_id = P_ID) THEN SELECT 3; LEAVE proc; END IF;
  IF EXISTS (SELECT 1 FROM ingresos WHERE id_indicador = P_ID) OR EXISTS (SELECT 1 FROM egresos WHERE id_indicador = P_ID) THEN
    SELECT 2; LEAVE proc;
  END IF;
  DELETE FROM indicadores WHERE id_indicadores = P_ID;
  SELECT 1;
END$$
DELIMITER ;

-- ---------- Cobro: la pensión debe ser del nivel Y del año escolar de la matrícula ----------

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

  
  SELECT id_nivel_academico INTO V_NIVEL_PEN FROM pensiones
   WHERE id_pensiones = P_PENSION AND id_anio = (SELECT `id_año` FROM matricula WHERE id_matricula = P_MATRI);
  IF V_NIVEL_PEN IS NULL OR V_NIVEL_PEN <> V_NIVEL THEN
    SELECT 0 AS codigo, 'La pensión no corresponde al nivel ni al año escolar de la matrícula' AS mensaje, 0.00 AS pagado_pension, 0.00 AS pagado_mora;
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
