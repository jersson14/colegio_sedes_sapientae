-- =====================================================================
-- Migración 003 – Fase 3: configuración regional (país, moneda, zona horaria, documento)
-- Un solo país y una sola moneda por colegio (configurable en Configuración > Datos regionales).
-- Aplicar con: mysql --default-character-set=utf8mb4 colegio2 < 003_configuracion_regional.sql
-- =====================================================================

-- ---------- 1. Catálogos ----------
CREATE TABLE IF NOT EXISTS monedas (
  id_moneda     INT AUTO_INCREMENT PRIMARY KEY,
  codigo        CHAR(3)     NOT NULL UNIQUE,      -- ISO 4217
  nombre        VARCHAR(60) NOT NULL,
  simbolo       VARCHAR(5)  NOT NULL,
  decimales     TINYINT     NOT NULL DEFAULT 2,
  sep_miles     VARCHAR(1)  NOT NULL DEFAULT ',',
  sep_decimal   CHAR(1)     NOT NULL DEFAULT '.',
  simbolo_antes TINYINT(1)  NOT NULL DEFAULT 1,
  estado        ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS paises (
  id_pais        INT AUTO_INCREMENT PRIMARY KEY,
  codigo_iso     CHAR(2)     NOT NULL UNIQUE,
  nombre         VARCHAR(80) NOT NULL,
  locale         VARCHAR(10) NOT NULL,             -- es-PE, es-EC, pt-BR...
  moneda_id      INT         NOT NULL,             -- moneda habitual (se puede cambiar)
  zona_horaria   VARCHAR(60) NOT NULL,
  prefijo_tel    VARCHAR(6)  NOT NULL,
  id_tributario  VARCHAR(20) NOT NULL,             -- RUC, NIT, CUIT, RUT...
  FOREIGN KEY (moneda_id) REFERENCES monedas(id_moneda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipos_documento_identidad (
  id_tipo_doc  INT AUTO_INCREMENT PRIMARY KEY,
  pais_id      INT          NOT NULL,
  nombre       VARCHAR(40)  NOT NULL,              -- DNI, Cédula, CI, RUT...
  long_min     TINYINT      NOT NULL,
  long_max     TINYINT      NOT NULL,
  regex        VARCHAR(150) NOT NULL,              -- se valida sin espacios ni puntos, en mayúsculas
  ejemplo      VARCHAR(30)  NOT NULL,
  FOREIGN KEY (pais_id) REFERENCES paises(id_pais),
  UNIQUE KEY (pais_id, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO monedas (codigo, nombre, simbolo, decimales, sep_miles, sep_decimal, simbolo_antes) VALUES
  ('PEN', 'Sol peruano',          'S/',  2, ',', '.', 1),
  ('USD', 'Dólar estadounidense', '$',   2, ',', '.', 1),
  ('BOB', 'Boliviano',            'Bs',  2, '.', ',', 1),
  ('ARS', 'Peso argentino',       '$',   2, '.', ',', 1),
  ('CLP', 'Peso chileno',         '$',   0, '.', ',', 1),
  ('COP', 'Peso colombiano',      '$',   0, '.', ',', 1),
  ('MXN', 'Peso mexicano',        '$',   2, ',', '.', 1),
  ('VES', 'Bolívar',              'Bs.', 2, '.', ',', 1),
  ('PYG', 'Guaraní',              '₲',   0, '.', ',', 1),
  ('UYU', 'Peso uruguayo',        '$U',  2, '.', ',', 1),
  ('BRL', 'Real brasileño',       'R$',  2, '.', ',', 1),
  ('CRC', 'Colón costarricense',  '₡',   2, ' ', ',', 1),
  ('PAB', 'Balboa',               'B/.', 2, ',', '.', 1),
  ('GTQ', 'Quetzal',              'Q',   2, ',', '.', 1),
  ('HNL', 'Lempira',              'L',   2, ',', '.', 1),
  ('NIO', 'Córdoba',              'C$',  2, ',', '.', 1),
  ('DOP', 'Peso dominicano',      'RD$', 2, ',', '.', 1),
  ('CUP', 'Peso cubano',          '$',   2, ',', '.', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO paises (codigo_iso, nombre, locale, moneda_id, zona_horaria, prefijo_tel, id_tributario)
SELECT p.iso, p.nombre, p.locale, m.id_moneda, p.zona, p.prefijo, p.trib FROM (
  SELECT 'PE' iso, 'Perú' nombre, 'es-PE' locale, 'PEN' mon, 'America/Lima' zona, '+51' prefijo, 'RUC' trib UNION ALL
  SELECT 'EC', 'Ecuador', 'es-EC', 'USD', 'America/Guayaquil', '+593', 'RUC' UNION ALL
  SELECT 'BO', 'Bolivia', 'es-BO', 'BOB', 'America/La_Paz', '+591', 'NIT' UNION ALL
  SELECT 'AR', 'Argentina', 'es-AR', 'ARS', 'America/Argentina/Buenos_Aires', '+54', 'CUIT' UNION ALL
  SELECT 'CL', 'Chile', 'es-CL', 'CLP', 'America/Santiago', '+56', 'RUT' UNION ALL
  SELECT 'CO', 'Colombia', 'es-CO', 'COP', 'America/Bogota', '+57', 'NIT' UNION ALL
  SELECT 'MX', 'México', 'es-MX', 'MXN', 'America/Mexico_City', '+52', 'RFC' UNION ALL
  SELECT 'VE', 'Venezuela', 'es-VE', 'VES', 'America/Caracas', '+58', 'RIF' UNION ALL
  SELECT 'PY', 'Paraguay', 'es-PY', 'PYG', 'America/Asuncion', '+595', 'RUC' UNION ALL
  SELECT 'UY', 'Uruguay', 'es-UY', 'UYU', 'America/Montevideo', '+598', 'RUT' UNION ALL
  SELECT 'BR', 'Brasil', 'pt-BR', 'BRL', 'America/Sao_Paulo', '+55', 'CNPJ' UNION ALL
  SELECT 'CR', 'Costa Rica', 'es-CR', 'CRC', 'America/Costa_Rica', '+506', 'Cédula jurídica' UNION ALL
  SELECT 'PA', 'Panamá', 'es-PA', 'PAB', 'America/Panama', '+507', 'RUC' UNION ALL
  SELECT 'GT', 'Guatemala', 'es-GT', 'GTQ', 'America/Guatemala', '+502', 'NIT' UNION ALL
  SELECT 'HN', 'Honduras', 'es-HN', 'HNL', 'America/Tegucigalpa', '+504', 'RTN' UNION ALL
  SELECT 'SV', 'El Salvador', 'es-SV', 'USD', 'America/El_Salvador', '+503', 'NIT' UNION ALL
  SELECT 'NI', 'Nicaragua', 'es-NI', 'NIO', 'America/Managua', '+505', 'RUC' UNION ALL
  SELECT 'DO', 'República Dominicana', 'es-DO', 'DOP', 'America/Santo_Domingo', '+1', 'RNC' UNION ALL
  SELECT 'CU', 'Cuba', 'es-CU', 'CUP', 'America/Havana', '+53', 'NIT' UNION ALL
  SELECT 'PR', 'Puerto Rico', 'es-PR', 'USD', 'America/Puerto_Rico', '+1', 'EIN'
) p INNER JOIN monedas m ON m.codigo = p.mon
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Documento principal de cada país. El valor se valida en mayúsculas y sin espacios ni puntos.
INSERT INTO tipos_documento_identidad (pais_id, nombre, long_min, long_max, regex, ejemplo)
SELECT pa.id_pais, d.nombre, d.mn, d.mx, d.rx, d.ej FROM (
  SELECT 'PE' iso, 'DNI' nombre, 8 mn, 8 mx, '^[0-9]{8}$' rx, '12345678' ej UNION ALL
  SELECT 'EC', 'Cédula', 10, 10, '^[0-9]{10}$', '1712345678' UNION ALL
  SELECT 'BO', 'CI', 5, 13, '^[0-9]{5,10}(-?[0-9A-Z]{1,3})?$', '1234567-1B' UNION ALL
  SELECT 'AR', 'DNI', 7, 8, '^[0-9]{7,8}$', '30123456' UNION ALL
  SELECT 'CL', 'RUT', 8, 10, '^[0-9]{7,8}-?[0-9K]$', '12345678-5' UNION ALL
  SELECT 'CO', 'Cédula', 6, 10, '^[0-9]{6,10}$', '1012345678' UNION ALL
  SELECT 'MX', 'CURP', 18, 18, '^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$', 'GODE561231HDFRRN09' UNION ALL
  SELECT 'VE', 'Cédula', 6, 11, '^([VE]-?)?[0-9]{6,9}$', 'V-12345678' UNION ALL
  SELECT 'PY', 'CI', 5, 8, '^[0-9]{5,8}$', '1234567' UNION ALL
  SELECT 'UY', 'CI', 7, 9, '^[0-9]{6,7}-?[0-9]$', '1234567-8' UNION ALL
  SELECT 'BR', 'CPF', 11, 14, '^[0-9]{3}[0-9]{3}[0-9]{3}-?[0-9]{2}$', '12345678909' UNION ALL
  SELECT 'CR', 'Cédula', 9, 9, '^[0-9]{9}$', '112345678' UNION ALL
  SELECT 'PA', 'Cédula', 5, 15, '^([0-9]{1,2}|PE|E|N|[0-9]{1,2}(AV|PI))-[0-9]{1,4}-[0-9]{1,6}$', '8-123-4567' UNION ALL
  SELECT 'GT', 'DPI', 13, 13, '^[0-9]{13}$', '1234567890101' UNION ALL
  SELECT 'HN', 'DNI', 13, 15, '^[0-9]{4}-?[0-9]{4}-?[0-9]{5}$', '0801199012345' UNION ALL
  SELECT 'SV', 'DUI', 9, 10, '^[0-9]{8}-?[0-9]$', '01234567-8' UNION ALL
  SELECT 'NI', 'Cédula', 14, 16, '^[0-9]{3}-?[0-9]{6}-?[0-9]{4}[A-Z]$', '001-010190-0001A' UNION ALL
  SELECT 'DO', 'Cédula', 11, 13, '^[0-9]{3}-?[0-9]{7}-?[0-9]$', '001-1234567-8' UNION ALL
  SELECT 'CU', 'CI', 11, 11, '^[0-9]{11}$', '85010112345' UNION ALL
  SELECT 'PR', 'Documento', 4, 20, '^[A-Z0-9-]{4,20}$', 'A1234567'
) d INNER JOIN paises pa ON pa.codigo_iso = d.iso
ON DUPLICATE KEY UPDATE regex = VALUES(regex);

-- ---------- 2. Configuración del colegio (una fila en empresa) ----------
ALTER TABLE empresa
  ADD COLUMN pais_id                   INT          NULL AFTER emp_logo,
  ADD COLUMN moneda_id                 INT          NULL AFTER pais_id,
  ADD COLUMN zona_horaria              VARCHAR(60)  NULL AFTER moneda_id,
  ADD COLUMN identificacion_tributaria VARCHAR(20)  NULL AFTER zona_horaria,
  -- Aceptar también pasaporte / carné de extranjería (letras y números, 4 a 20) para alumnos extranjeros
  ADD COLUMN permite_doc_extranjero    TINYINT(1)   NOT NULL DEFAULT 1 AFTER identificacion_tributaria,
  ADD CONSTRAINT fk_empresa_pais   FOREIGN KEY (pais_id)   REFERENCES paises(id_pais),
  ADD CONSTRAINT fk_empresa_moneda FOREIGN KEY (moneda_id) REFERENCES monedas(id_moneda);

-- El colegio actual queda configurado como Perú / Sol (lo que el sistema usaba fijo)
UPDATE empresa SET
  pais_id = (SELECT id_pais FROM paises WHERE codigo_iso = 'PE'),
  moneda_id = (SELECT id_moneda FROM monedas WHERE codigo = 'PEN'),
  zona_horaria = 'America/Lima'
WHERE pais_id IS NULL;

-- ---------- 3. Documentos y teléfonos: CHAR(8)/CHAR(9) (solo Perú) -> VARCHAR(20) ----------
ALTER TABLE alumnos
  MODIFY alum_dni   VARCHAR(20) NOT NULL,
  MODIFY alum_movil VARCHAR(20) NULL;
ALTER TABLE padres
  MODIFY Dni_papa VARCHAR(20) NULL,
  MODIFY Dni_mama VARCHAR(20) NULL;
ALTER TABLE docentes
  MODIFY docente_dni         VARCHAR(20) NOT NULL,
  MODIFY docente_movil       VARCHAR(20) NULL,
  MODIFY docente_nro_alterno VARCHAR(20) NULL;
ALTER TABLE personal_admi
  MODIFY personal_adm_dni         VARCHAR(20) NULL,
  MODIFY personal_adm_movil       VARCHAR(20) NULL,
  MODIFY personal_adm_nro_alterno VARCHAR(20) NULL;
ALTER TABLE auxiliar
  MODIFY auxiliar_dni         VARCHAR(20) NULL,
  MODIFY auxiliar_movil       VARCHAR(20) NULL,
  MODIFY auxiliar_nro_alterno VARCHAR(20) NULL;
-- CHAR rellenaba con espacios: se quitan en los datos existentes
UPDATE alumnos SET alum_dni = TRIM(alum_dni), alum_movil = TRIM(alum_movil);
UPDATE padres SET Dni_papa = TRIM(Dni_papa), Dni_mama = TRIM(Dni_mama);
UPDATE docentes SET docente_dni = TRIM(docente_dni), docente_movil = TRIM(docente_movil), docente_nro_alterno = TRIM(docente_nro_alterno);
UPDATE personal_admi SET personal_adm_dni = TRIM(personal_adm_dni), personal_adm_movil = TRIM(personal_adm_movil), personal_adm_nro_alterno = TRIM(personal_adm_nro_alterno);

-- ---------- 5. Historial de cambios de configuración (quién cambió país/moneda y cuándo) ----------
CREATE TABLE IF NOT EXISTS historial_configuracion (
  id_historial   INT AUTO_INCREMENT PRIMARY KEY,
  campo          VARCHAR(40)  NOT NULL,
  valor_anterior VARCHAR(100) NULL,
  valor_nuevo    VARCHAR(100) NULL,
  usuario_id     INT          NOT NULL,
  fecha          DATETIME     NOT NULL,
  INDEX (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 4. Procedimientos: montos sin símbolo fijo y documentos de hasta 20 caracteres ----------

DROP PROCEDURE IF EXISTS `SP_LISTAR_DIFERENCIA`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_DIFERENCIA`()
BEGIN
    DECLARE fecha_inicio DATE;
    DECLARE fecha_fin DATE;
    DECLARE totalgasto DECIMAL(10,2);
    DECLARE totalingresos DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);

    
    SET @fecha_inicio := CURDATE();
    SET @fecha_fin := CURDATE();

    
    SET @totalgasto := (
        SELECT IFNULL(SUM(egresos.monto), 0) 
        FROM egresos 
        WHERE egresos.created_at BETWEEN @fecha_inicio AND @fecha_fin AND estado='VALIDO'
    );

    
    SET @totalingresos := (
        SELECT IFNULL(SUM(ingresos.monto), 0) 
        FROM ingresos 
        WHERE ingresos.created_at BETWEEN @fecha_inicio AND @fecha_fin AND estado='VALIDO'
    );

    
    SET @total := @totalingresos - @totalgasto;

    
    SELECT 
        DATE_FORMAT(@fecha_inicio, "%d-%m-%Y") AS FechaInicial,
        DATE_FORMAT(@fecha_fin, "%d-%m-%Y") AS FechaFinal,
        @totalingresos AS totalingresos,
        @totalgasto AS totalgasto,
        @total AS Diferencia;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_DIFERENCIA_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_DIFERENCIA_FILTRO`(IN `FECHAINI` DATE, IN `FECHAFIN` DATE)
BEGIN
    DECLARE totalgasto DECIMAL(10,2);
    DECLARE totalingresos DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);

    
    SET totalgasto := (
        SELECT IFNULL(SUM(egresos.monto), 0) 
        FROM egresos 
        WHERE egresos.created_at BETWEEN FECHAINI AND FECHAFIN AND estado='VALIDO'
    );

    
    SET totalingresos := (
        SELECT IFNULL(SUM(ingresos.monto), 0) 
        FROM ingresos 
        WHERE ingresos.created_at BETWEEN FECHAINI AND FECHAFIN AND estado='VALIDO'
    );

    
    SET total := totalingresos - totalgasto;

    
    SELECT 
        DATE_FORMAT(FECHAINI, "%d-%m-%Y") AS FechaInicial,
        DATE_FORMAT(FECHAFIN, "%d-%m-%Y") AS FechaFinal,
        totalingresos AS totalingresos,
        totalgasto AS totalgasto,
        total AS Diferencia;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_MATRICULADOS`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_MATRICULADOS`()
SELECT
matricula.id_matricula,
matricula.id_alumno,
matricula.id_aula,
matricula.`id_año`,
matricula.pago_admi,
matricula.pago_alu_nuevo,
matricula.pago_matricula,


pago_matricula AS MATRICULA,

matricula.procedencia_colegio,
matricula.provincia,
matricula.departamento,
matricula.usu_id,
matricula.created_at,
matricula.updated_at,
alumnos.alum_dni,
alumnos.alum_nombre,
alumnos.alum_apepat,
alumnos.alum_apemat,
CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
alumnos.tipo_alum,
`año_escolar`.`año_escolar`,
aulas.Grado,
nivel_academico.Nivel_academico,
nivel_academico.Id_nivel
FROM
matricula
INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
ORDER BY matricula.created_at desc$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_MATRICULADOS_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_MATRICULADOS_FILTRO`(IN `AÑO` INT, IN `AULA` INT)
SELECT
matricula.id_matricula,
matricula.id_alumno,
matricula.id_aula,
matricula.`id_año`,
matricula.pago_admi,
matricula.pago_alu_nuevo,
matricula.pago_matricula,


pago_matricula AS MATRICULA,

matricula.procedencia_colegio,
matricula.provincia,
matricula.departamento,
matricula.usu_id,
matricula.created_at,
matricula.updated_at,
alumnos.alum_dni,
alumnos.alum_nombre,
alumnos.alum_apepat,
alumnos.alum_apemat,
CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
alumnos.tipo_alum,
`año_escolar`.`año_escolar`,
aulas.Grado,
nivel_academico.Nivel_academico
FROM
matricula
INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
WHERE matricula.`id_año`=AÑO AND matricula.id_aula=AULA
ORDER BY matricula.created_at desc$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_NOTAS_AULA_AÑO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_NOTAS_AULA_AÑO`(IN `IDAÑO` INT, IN `AULA` INT)
BEGIN
    
    DECLARE CONTADOR INT;

    
    SELECT
        matricula.id_matricula, 
        matricula.id_alumno, 
        matricula.id_aula, 
        matricula.`id_año`, 
        matricula.pago_admi, 
        matricula.pago_alu_nuevo, 
        matricula.pago_matricula, 
        pago_matricula AS MATRICULA, 
        matricula.procedencia_colegio, 
        matricula.provincia, 
        matricula.departamento, 
        matricula.usu_id, 
        matricula.created_at, 
        matricula.updated_at, 
        alumnos.alum_dni, 
        alumnos.alum_nombre, 
        alumnos.alum_apepat, 
        alumnos.alum_apemat, 
        CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
        alumnos.tipo_alum, 
        `año_escolar`.`año_escolar`, 
        aulas.Grado, 
        nivel_academico.Nivel_academico, 
        seccion.seccion_nombre,
        (SELECT COUNT(notas.id_bimestre)
         FROM notas
         INNER JOIN periodos
         ON notas.id_bimestre = periodos.id_periodo
         WHERE NOW() BETWEEN periodos.fecha_inicio AND periodos.fecha_fin
         AND notas.id_matricula = matricula.id_matricula) AS contar
    FROM
        matricula
    INNER JOIN
        alumnos
    ON 
        matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN
        `año_escolar`
    ON 
        matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN
        aulas
    ON 
        matricula.id_aula = aulas.Id_aula
    INNER JOIN
        nivel_academico
    ON 
        aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN
        seccion
    ON 
        aulas.id_seccion = seccion.seccion_id
    WHERE 
        matricula.`id_año` = IDAÑO 
        AND matricula.id_aula = AULA
    ORDER BY 
        `año_escolar`.`año_escolar` DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_NOTAS_AULA_AÑO_ALUMNOS`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_NOTAS_AULA_AÑO_ALUMNOS`(IN `IDAÑO` INT, IN `ID` INT)
BEGIN
    
    DECLARE CONTADOR INT;

    
    SELECT
	matricula.id_matricula, 
	matricula.id_alumno, 
	matricula.id_aula, 
	matricula.`id_año`, 
	matricula.pago_admi, 
	matricula.pago_alu_nuevo, 
	matricula.pago_matricula, 
	pago_matricula AS MATRICULA, 
	matricula.procedencia_colegio, 
	matricula.provincia, 
	matricula.departamento, 
	matricula.usu_id, 
	matricula.created_at, 
	matricula.updated_at, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat, 
	CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
	alumnos.tipo_alum, 
	`año_escolar`.`año_escolar`, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	seccion.seccion_nombre, 
	(SELECT COUNT(notas.id_bimestre)
         FROM notas
         INNER JOIN periodos
         ON notas.id_bimestre = periodos.id_periodo
         WHERE NOW() BETWEEN periodos.fecha_inicio AND periodos.fecha_fin
         AND notas.id_matricula = matricula.id_matricula) AS contar, 
	usuario.usu_id
FROM
	matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
    WHERE 
        matricula.`id_año` = IDAÑO 
        AND usuario.usu_id = ID
    ORDER BY 
        `año_escolar`.`año_escolar` DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_NOTAS_AULA_AÑO_PROFESOR`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_NOTAS_AULA_AÑO_PROFESOR`(IN `IDAÑO` INT, IN `AULA` INT)
BEGIN
    
    DECLARE CONTADOR INT;

    
    SELECT
        matricula.id_matricula, 
        matricula.id_alumno, 
        matricula.id_aula, 
        matricula.`id_año`, 
        matricula.pago_admi, 
        matricula.pago_alu_nuevo, 
        matricula.pago_matricula, 
        pago_matricula AS MATRICULA, 
        matricula.procedencia_colegio, 
        matricula.provincia, 
        matricula.departamento, 
        matricula.usu_id, 
        matricula.created_at, 
        matricula.updated_at, 
        alumnos.alum_dni, 
        alumnos.alum_nombre, 
        alumnos.alum_apepat, 
        alumnos.alum_apemat, 
        CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
        alumnos.tipo_alum, 
        `año_escolar`.`año_escolar`, 
        aulas.Grado, 
        nivel_academico.Nivel_academico, 
        seccion.seccion_nombre,
        (SELECT COUNT(notas.id_bimestre)
         FROM notas
         INNER JOIN periodos
         ON notas.id_bimestre = periodos.id_periodo
         WHERE NOW() BETWEEN periodos.fecha_inicio AND periodos.fecha_fin
         AND notas.id_matricula = matricula.id_matricula) AS contar
    FROM
        matricula
    INNER JOIN
        alumnos
    ON 
        matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN
        `año_escolar`
    ON 
        matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN
        aulas
    ON 
        matricula.id_aula = aulas.Id_aula
    INNER JOIN
        nivel_academico
    ON 
        aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN
        seccion
    ON 
        aulas.id_seccion = seccion.seccion_id
    WHERE 
        matricula.`id_año` = IDAÑO 
        AND matricula.id_aula = AULA
    ORDER BY 
        `año_escolar`.`año_escolar` DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PAGOS_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PAGOS_FILTRO`(IN `AÑO` INT, IN `AULA` INT)
BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        pago_matricula AS MATRICULA,
        matricula.procedencia_colegio,
        matricula.provincia,
        matricula.departamento,
        matricula.usu_id,
        matricula.created_at,
        matricula.updated_at,
        alumnos.alum_dni,
        alumnos.alum_nombre,
        alumnos.alum_apepat,
        alumnos.alum_apemat,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        alumnos.tipo_alum,
        `año_escolar`.`año_escolar`,
        aulas.Grado,
        nivel_academico.Nivel_academico,
								nivel_academico.Id_nivel,

        DATE_FORMAT(matricula.created_at, "%d-%m-%Y") AS FECHA,
				 DATE_FORMAT(MAX(pago_pensiones.fecha_pago) , "%d-%m-%Y") AS FECHA_pago,
				pago_pensiones.id_pension,
				pago_pensiones.id_matri

    FROM
        matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula AND pago_pensiones.estado = 'VALIDO'
    WHERE matricula.`id_año`=AÑO AND matricula.id_aula=AULA
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PAGO_PENSION`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PAGO_PENSION`()
BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        pago_matricula AS MATRICULA,
        matricula.procedencia_colegio,
        matricula.provincia,
        matricula.departamento,
        matricula.usu_id,
        matricula.created_at,
        matricula.updated_at,
        alumnos.alum_dni,
        alumnos.alum_nombre,
        alumnos.alum_apepat,
        alumnos.alum_apemat,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        alumnos.tipo_alum,
        `año_escolar`.`año_escolar`,
        aulas.Grado,
        nivel_academico.Nivel_academico,
				nivel_academico.Id_nivel,
        DATE_FORMAT(matricula.created_at, "%d-%m-%Y") AS FECHA,
				 DATE_FORMAT(MAX(pago_pensiones.fecha_pago) , "%d-%m-%Y") AS FECHA_pago,
				pago_pensiones.id_pension,
				pago_pensiones.id_matri

    FROM
        matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula AND pago_pensiones.estado = 'VALIDO'

		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PAGO_PENSION_ID`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PAGO_PENSION_ID`(IN `ID` INT)
BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        pago_matricula AS MATRICULA,
        matricula.procedencia_colegio,
        matricula.provincia,
        matricula.departamento,
        matricula.usu_id,
        matricula.created_at,
        matricula.updated_at,
        alumnos.alum_dni,
        alumnos.alum_nombre,
        alumnos.alum_apepat,
        alumnos.alum_apemat,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        alumnos.tipo_alum,
        `año_escolar`.`año_escolar`,
        aulas.Grado,
        nivel_academico.Nivel_academico,
        DATE_FORMAT(matricula.created_at, "%d-%m-%Y") AS FECHA,
				 DATE_FORMAT(MAX(pago_pensiones.fecha_pago) , "%d-%m-%Y") AS FECHA_pago,
				pago_pensiones.id_pension,
				pago_pensiones.id_matri

    FROM
        matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula AND pago_pensiones.estado = 'VALIDO'
    WHERE matricula.usu_id=ID AND `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW())))
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PAGO_PENSION_ID_AÑO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PAGO_PENSION_ID_AÑO`(IN `ID` INT, IN `AÑO` INT)
BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        pago_matricula AS MATRICULA,
        matricula.procedencia_colegio,
        matricula.provincia,
        matricula.departamento,
        matricula.usu_id,
        matricula.created_at,
        matricula.updated_at,
        alumnos.alum_dni,
        alumnos.alum_nombre,
        alumnos.alum_apepat,
        alumnos.alum_apemat,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        alumnos.tipo_alum,
        `año_escolar`.`año_escolar`,
        aulas.Grado,
        nivel_academico.Nivel_academico,
        DATE_FORMAT(matricula.created_at, "%d-%m-%Y") AS FECHA,
				 DATE_FORMAT(MAX(pago_pensiones.fecha_pago) , "%d-%m-%Y") AS FECHA_pago,
				pago_pensiones.id_pension,
				pago_pensiones.id_matri

    FROM
        matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula AND pago_pensiones.estado = 'VALIDO'
    WHERE matricula.usu_id=ID AND `año_escolar`.`Id_año_escolar`=AÑO
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PAGO_PENSION_TODO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PAGO_PENSION_TODO`(IN `ID` INT)
BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        pago_matricula AS MATRICULA,
        matricula.procedencia_colegio,
        matricula.provincia,
        matricula.departamento,
        matricula.usu_id,
        matricula.created_at,
        matricula.updated_at,
        alumnos.alum_dni,
        alumnos.alum_nombre,
        alumnos.alum_apepat,
        alumnos.alum_apemat,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        alumnos.tipo_alum,
        `año_escolar`.`año_escolar`,
        aulas.Grado,
        nivel_academico.Nivel_academico,
        DATE_FORMAT(matricula.created_at, "%d-%m-%Y") AS FECHA,
				 DATE_FORMAT(MAX(pago_pensiones.fecha_pago) , "%d-%m-%Y") AS FECHA_pago,
				pago_pensiones.id_pension,
				pago_pensiones.id_matri

    FROM
        matricula
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula AND pago_pensiones.estado = 'VALIDO'
    WHERE matricula.usu_id=ID
		GROUP BY
    `año_escolar`
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PENSIONES`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PENSIONES`()
SELECT
pensiones.id_pensiones,
pensiones.id_nivel_academico,
pensiones.mes,
pensiones.fecha_vencimiento,
date_format(fecha_vencimiento, "%d-%m-%Y") AS fecha_formateada,
pensiones.precio,
precio AS PRECIO,
pensiones.mora,
mora AS MORA,
pensiones.created_at,
pensiones.updated_at,
nivel_academico.Nivel_academico
FROM
pensiones
INNER JOIN nivel_academico ON pensiones.id_nivel_academico = nivel_academico.Id_nivel and NOT nivel_academico="TODOS"$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_LISTAR_PENSIONES_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_PENSIONES_FILTRO`(IN `IDNIVEL` INT)
SELECT
pensiones.id_pensiones,
pensiones.id_nivel_academico,
pensiones.mes,
pensiones.fecha_vencimiento,
date_format(fecha_vencimiento, "%d-%m-%Y") AS fecha_formateada,
pensiones.precio,
precio AS PRECIO,
pensiones.mora,
mora AS MORA,
pensiones.created_at,
pensiones.updated_at,
nivel_academico.Nivel_academico
FROM
pensiones
INNER JOIN nivel_academico ON pensiones.id_nivel_academico = nivel_academico.Id_nivel and NOT nivel_academico="TODOS"
WHERE pensiones.id_nivel_academico=IDNIVEL$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_REGISTRAR_ALUMNOS`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_ALUMNOS`(IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APEPA` VARCHAR(255), IN `APEMATE` VARCHAR(255), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `DNIPA` VARCHAR(20), IN `DATOSPA` VARCHAR(255), IN `CELPA` VARCHAR(255), IN `DNIMA` VARCHAR(20), IN `DATOSMA` VARCHAR(255), IN `CELMA` VARCHAR(255))
BEGIN
DECLARE EDAD INT;
DECLARE CANTIDAD INT;
DECLARE ulti int;

SET @EDAD:=(TIMESTAMPDIFF( YEAR, FECHANAC, now()));
SET @CANTIDAD:=(SELECT COUNT(*) FROM alumnos where alum_dni=DNI);
IF @CANTIDAD = 0 THEN
INSERT INTO alumnos(alum_dni,alum_nombre,alum_apepat,alum_apemat,alum_sexo,alum_fechanacimiento,Edad,alum_movil,alum_direccion,alum_estatus,tipo_alum,alum_fotoperfil,created_at,updated_at)VALUE(DNI,NOMBRES,APEPA,APEMATE,SEXO,FECHANAC,@EDAD,CEL,DIREC,'NO','NUEVO',FOTO,NOW(),'');
	SET @ulti:=(SELECT MAX(Id_alumno) AS id FROM alumnos);
INSERT INTO padres(id_alu,Dni_papa,Datos_papa,Celular_papa,Dni_mama,Datos_mama,Celular_mama,created_at,updated_at)VALUE(@ulti,DNIPA,DATOSPA,CELPA,DNIMA,DATOSMA,CELMA,NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_ALUMNOS`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_ALUMNOS`(IN `ID` INT, IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APEPA` VARCHAR(255), IN `APEMATE` VARCHAR(255), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `IDPA` INT, IN `DNIPA` VARCHAR(20), IN `DATOSPA` VARCHAR(255), IN `CELPA` VARCHAR(255), IN `DNIMA` VARCHAR(20), IN `DATOSMA` VARCHAR(255), IN `CELMA` VARCHAR(255))
BEGIN
    DECLARE EDAD INT;
    DECLARE NDOCUMENTOACTUAL VARCHAR(20);
    DECLARE EXISTE_DNI_OTRO INT;

    
    SET @EDAD := (TIMESTAMPDIFF(YEAR, FECHANAC, NOW()));

    
    SET @NDOCUMENTOACTUAL := (SELECT alum_dni FROM alumnos WHERE Id_alumno = ID LIMIT 1);

    
    SET @EXISTE_DNI_OTRO := (SELECT COUNT(*) FROM alumnos WHERE alum_dni = DNI AND Id_alumno != ID);

    
    IF @NDOCUMENTOACTUAL = DNI THEN
        UPDATE alumnos
        SET
            alum_dni = DNI,
            alum_nombre = NOMBRES,
            alum_apepat = APEPA,
            alum_apemat = APEMATE,
            alum_sexo = SEXO,
            alum_fechanacimiento = FECHANAC,
            Edad = @EDAD,
            alum_movil = CEL,
            alum_direccion = DIREC,
            alum_fotoperfil = FOTO,
            updated_at = NOW()
        WHERE Id_alumno = ID;

        UPDATE padres
        SET
            Dni_papa = DNIPA,
            Datos_papa = DATOSPA,
            Celular_papa = CELPA,
            Dni_mama = DNIMA,
            Datos_mama = DATOSMA,
            Celular_mama = CELMA,
            updated_at = NOW()
        WHERE id_papas = IDPA;

        SELECT 1; 

    
    ELSEIF @EXISTE_DNI_OTRO > 0 THEN
        SELECT 2; 

    
    ELSE
        UPDATE alumnos
        SET
            alum_dni = DNI,
            alum_nombre = NOMBRES,
            alum_apepat = APEPA,
            alum_apemat = APEMATE,
            alum_sexo = SEXO,
            alum_fechanacimiento = FECHANAC,
            Edad = @EDAD,
            alum_movil = CEL,
            alum_direccion = DIREC,
            alum_fotoperfil = FOTO,
            updated_at = NOW()
        WHERE Id_alumno = ID;

        UPDATE padres
        SET
            Dni_papa = DNIPA,
            Datos_papa = DATOSPA,
            Celular_papa = CELPA,
            Dni_mama = DNIMA,
            Datos_mama = DATOSMA,
            Celular_mama = CELMA,
            updated_at = NOW()
        WHERE id_papas = IDPA;

        SELECT 1; 
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_REGISTRAR_DOCENTES`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_DOCENTES`(IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `ESPE` INT, IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255))
BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM docentes where docentes.docente_dni=DNI);
IF @CANTIDAD = 0 THEN
INSERT INTO usuario(usu_usuario,usu_contra,usu_email,usu_estatus,rol_id,empresa_id,created_at,updated_at)VALUE(USU,CONTRA,EMAIL,'ACTIVO','2','1',NOW(),'');

INSERT INTO docentes(docente_dni,docente_nombre,docente_apelli,especialidad_id,docente_sexo,docente_fechanacimiento,docente_movil,docente_nro_alterno,docente_direccion,docente_estatus,docente_fotoperfil,id_asusuario,created_at,updated_at)VALUE(DNI,NOMBRES,APELLI,ESPE,SEXO,FECHANAC,CEL,CELALT,DIREC,'ACTIVO',FOTO,(SELECT MAX(`usu_id`) from `usuario`),NOW(),'');

SELECT 1;
ELSE
SELECT 2;

END IF;

END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_DOCENTES`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_DOCENTES`(IN `ID` INT, IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `ESPE` INT, IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `ESTADO` VARCHAR(20), IN `FOTO` VARCHAR(255))
BEGIN
    DECLARE DNI_ACTUAL VARCHAR(20);
    DECLARE EXISTE_DNI_OTRO INT;

    
    SET @DNI_ACTUAL := (SELECT docente_dni FROM docentes WHERE Id_docente = ID LIMIT 1);

    
    SET @EXISTE_DNI_OTRO := (SELECT COUNT(*) FROM docentes WHERE docente_dni = DNI AND Id_docente != ID);

    
    IF @DNI_ACTUAL = DNI THEN
        UPDATE docentes

        SET
            docente_dni = DNI,
            docente_nombre = NOMBRES,
            docente_apelli = APELLI,
            especialidad_id = ESPE,
            docente_sexo = SEXO,
            docente_fechanacimiento = FECHANAC,
            docente_movil = CEL,
            docente_nro_alterno = CELALT,
            docente_direccion = DIREC,
            docente_estatus = ESTADO,
            docente_fotoperfil = FOTO,
            updated_at = NOW()
        WHERE Id_docente = ID;

        SELECT 1; 

    
    ELSEIF @EXISTE_DNI_OTRO > 0 THEN
        SELECT 2; 

    
    ELSE
        UPDATE docentes
        SET
            docente_dni = DNI,
            docente_nombre = NOMBRES,
            docente_apelli = APELLI,
            especialidad_id = ESPE,
            docente_sexo = SEXO,
            docente_fechanacimiento = FECHANAC,
            docente_movil = CEL,
            docente_nro_alterno = CELALT,
            docente_direccion = DIREC,
            docente_estatus = ESTADO,
            docente_fotoperfil = FOTO,
            updated_at = NOW()
        WHERE Id_docente = ID;

        SELECT 1; 
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_REGISTRAR_PERSONAL`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_PERSONAL`(IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `TIPO` VARCHAR(30), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(20), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `ROL` INT)
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

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_PERSONAL`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_PERSONAL`(IN `ID` INT, IN `DNI` VARCHAR(20), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `TIPO` VARCHAR(50), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` VARCHAR(20), IN `CELALT` VARCHAR(20), IN `DIREC` VARCHAR(255), IN `ESTADO` VARCHAR(20), IN `FOTO` VARCHAR(255))
BEGIN
    DECLARE DNI_ACTUAL VARCHAR(20);
    DECLARE EXISTE_DNI_OTRO INT;

    
    SET @DNI_ACTUAL := (SELECT personal_adm_dni FROM personal_admi WHERE personal_adm_id = ID LIMIT 1);

    
    SET @EXISTE_DNI_OTRO := (SELECT COUNT(*) FROM personal_admi WHERE personal_adm_dni = DNI AND personal_adm_id != ID);

    
    IF @DNI_ACTUAL = DNI THEN
        UPDATE personal_admi
        SET
            personal_adm_dni = DNI,
            personal_adm_nombre = NOMBRES,
            personal_adm_apellido = APELLI,
            personal_adm_tipo = TIPO,
            personal_adm_sexo = SEXO,
            personal_adm_fechanacimiento = FECHANAC,
            personal_adm_movil = CEL,
            personal_adm_nro_alterno = CELALT,
            personal_adm_direccion = DIREC,
            personal_adm_estatus = ESTADO,
            personal_adm_fotoperfil = FOTO,
            updated_at = NOW()
        WHERE personal_adm_id = ID;

        SELECT 1; 

    
    ELSEIF @EXISTE_DNI_OTRO > 0 THEN
        SELECT 2; 

    
    ELSE
        UPDATE personal_admi
        SET
            personal_adm_dni = DNI,
            personal_adm_nombre = NOMBRES,
            personal_adm_apellido = APELLI,
            personal_adm_tipo = TIPO,
            personal_adm_sexo = SEXO,
            personal_adm_fechanacimiento = FECHANAC,
            personal_adm_movil = CEL,
            personal_adm_nro_alterno = CELALT,
            personal_adm_direccion = DIREC,
            personal_adm_estatus = ESTADO,
            personal_adm_fotoperfil = FOTO,
            updated_at = NOW()
        WHERE personal_adm_id = ID;

        SELECT 1; 
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_DOCENTE_FOTO`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_DOCENTE_FOTO`(IN `DNI` VARCHAR(20), IN `RUTA` VARCHAR(255))
UPDATE docentes SET
docentes.docente_fotoperfil=RUTA
WHERE docentes.docente_dni=DNI$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SP_MODIFICAR_ESTUDIANTE_FOTO`;
DELIMITER $$
CREATE PROCEDURE `SP_MODIFICAR_ESTUDIANTE_FOTO`(IN `DNI` VARCHAR(20), IN `RUTA` VARCHAR(255))
UPDATE alumnos SET
alumnos.alum_fotoperfil=RUTA
WHERE alumnos.alum_dni=DNI$$
DELIMITER ;
