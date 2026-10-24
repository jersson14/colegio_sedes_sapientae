-- =====================================================================
-- Fase 8 (bloque C): escala de notas según el país
--   * Cada país trae su escala por defecto (mínimo, máximo, nota aprobatoria, decimales y,
--     si se usan, calificaciones literales como AD/A/B/C en Perú).
--   * El colegio guarda su escala en empresa (se propone la del país y se puede ajustar en
--     Configuración > Datos regionales). Notas, notas de padres y calificaciones de tareas se validan con ella.
--   * La calificación de tareas era INT (6.5 se guardaba como 6 y no admitía letras): ahora texto como en notas.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE paises
  ADD COLUMN nota_min DECIMAL(5,2) NOT NULL DEFAULT 0,
  ADD COLUMN nota_max DECIMAL(5,2) NOT NULL DEFAULT 20,
  ADD COLUMN nota_aprobatoria DECIMAL(5,2) NOT NULL DEFAULT 11,
  ADD COLUMN nota_decimales TINYINT NOT NULL DEFAULT 0,
  ADD COLUMN notas_literales VARCHAR(60) NOT NULL DEFAULT '',          -- p. ej. 'AD,A,B,C' (de mayor a menor)
  ADD COLUMN literales_aprobatorios VARCHAR(60) NOT NULL DEFAULT '';   -- p. ej. 'AD,A,B'

UPDATE paises SET nota_min = 0, nota_max = 20,  nota_aprobatoria = 11, nota_decimales = 0, notas_literales = 'AD,A,B,C', literales_aprobatorios = 'AD,A,B' WHERE codigo_iso = 'PE';
UPDATE paises SET nota_min = 0, nota_max = 10,  nota_aprobatoria = 7,  nota_decimales = 2 WHERE codigo_iso = 'EC';
UPDATE paises SET nota_min = 0, nota_max = 100, nota_aprobatoria = 51, nota_decimales = 0 WHERE codigo_iso = 'BO';
UPDATE paises SET nota_min = 1, nota_max = 10,  nota_aprobatoria = 6,  nota_decimales = 0 WHERE codigo_iso = 'AR';
UPDATE paises SET nota_min = 1, nota_max = 7,   nota_aprobatoria = 4,  nota_decimales = 1 WHERE codigo_iso = 'CL';
UPDATE paises SET nota_min = 1, nota_max = 5,   nota_aprobatoria = 3,  nota_decimales = 1 WHERE codigo_iso = 'CO';
UPDATE paises SET nota_min = 5, nota_max = 10,  nota_aprobatoria = 6,  nota_decimales = 1 WHERE codigo_iso = 'MX';
UPDATE paises SET nota_min = 1, nota_max = 20,  nota_aprobatoria = 10, nota_decimales = 0 WHERE codigo_iso = 'VE';
UPDATE paises SET nota_min = 1, nota_max = 5,   nota_aprobatoria = 2,  nota_decimales = 0 WHERE codigo_iso = 'PY';
UPDATE paises SET nota_min = 1, nota_max = 12,  nota_aprobatoria = 6,  nota_decimales = 0 WHERE codigo_iso = 'UY';
UPDATE paises SET nota_min = 0, nota_max = 10,  nota_aprobatoria = 6,  nota_decimales = 1 WHERE codigo_iso IN ('BR', 'SV');
UPDATE paises SET nota_min = 0, nota_max = 100, nota_aprobatoria = 65, nota_decimales = 0 WHERE codigo_iso = 'CR';
UPDATE paises SET nota_min = 0, nota_max = 100, nota_aprobatoria = 60, nota_decimales = 0 WHERE codigo_iso IN ('CU', 'GT', 'NI', 'PR');
UPDATE paises SET nota_min = 0, nota_max = 100, nota_aprobatoria = 70, nota_decimales = 0 WHERE codigo_iso IN ('HN', 'DO');
UPDATE paises SET nota_min = 1, nota_max = 5,   nota_aprobatoria = 3,  nota_decimales = 1 WHERE codigo_iso = 'PA';

-- Escala del colegio: la de su país
ALTER TABLE empresa
  ADD COLUMN nota_min DECIMAL(5,2) NULL,
  ADD COLUMN nota_max DECIMAL(5,2) NULL,
  ADD COLUMN nota_aprobatoria DECIMAL(5,2) NULL,
  ADD COLUMN nota_decimales TINYINT NULL,
  ADD COLUMN notas_literales VARCHAR(60) NULL,
  ADD COLUMN literales_aprobatorios VARCHAR(60) NULL;
UPDATE empresa e INNER JOIN paises p ON p.id_pais = e.pais_id
   SET e.nota_min = p.nota_min, e.nota_max = p.nota_max, e.nota_aprobatoria = p.nota_aprobatoria,
       e.nota_decimales = p.nota_decimales, e.notas_literales = p.notas_literales, e.literales_aprobatorios = p.literales_aprobatorios;

-- Calificación de tareas: texto (decimales o literales), como las notas del bimestre
ALTER TABLE detalle_tarea MODIFY calificacion VARCHAR(5) NULL;

DROP PROCEDURE IF EXISTS SP_REGISTRAR_CALIFICACION;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_CALIFICACION`(IN P_ID INT, IN P_NOTA VARCHAR(5), IN P_OBSER VARCHAR(255))
BEGIN
  UPDATE detalle_tarea
     SET calificacion = P_NOTA, observacion = P_OBSER, estado = 'CALIFICADO'
   WHERE id_detalle_tarea = P_ID;
  SELECT ROW_COUNT() > 0;
END$$
DELIMITER ;
