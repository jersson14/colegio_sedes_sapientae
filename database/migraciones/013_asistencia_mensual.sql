-- =====================================================================
-- Fase 11: registro mensual de asistencia (planilla por aula y mes)
--   * Un solo registro por estudiante y día: índice único (id_matricula, fecha).
--   * SP_REGISTRAR_ASISTENCIA comparaba la fecha marcada con la fecha de CREACIÓN del registro
--     (DATE(created_at)) y guardaba el mes actual en vez del mes de la fecha: al registrar un día
--     anterior no detectaba el duplicado y los totales por mes salían en otro mes.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Duplicados (mismo estudiante y día): se conserva el registro más reciente
DELETE a FROM asistencia a
  JOIN asistencia b ON b.id_matricula = a.id_matricula AND b.fecha = a.fecha AND b.id_asistencia > a.id_asistencia;

UPDATE asistencia SET mes = MONTH(fecha) WHERE fecha IS NOT NULL AND (mes IS NULL OR mes <> MONTH(fecha));

ALTER TABLE asistencia ADD UNIQUE KEY uq_asistencia_dia (id_matricula, fecha);

DROP PROCEDURE IF EXISTS SP_REGISTRAR_ASISTENCIA;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_ASISTENCIA(IN P_ID_MATRI INT, IN P_FECHA DATE, IN P_ESTA VARCHAR(20), IN P_OBSER VARCHAR(1000))
BEGIN
  IF EXISTS (SELECT 1 FROM asistencia WHERE id_matricula = P_ID_MATRI AND fecha = P_FECHA) THEN
    SELECT 2;
  ELSE
    INSERT INTO asistencia(id_matricula, mes, fecha, estado, observacion, created_at, updated_at)
    VALUES (P_ID_MATRI, MONTH(P_FECHA), P_FECHA, P_ESTA, P_OBSER, NOW(), NULL);
    SELECT 1;
  END IF;
END$$
DELIMITER ;
