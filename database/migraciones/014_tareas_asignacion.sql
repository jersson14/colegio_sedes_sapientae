-- =====================================================================
-- Fase 11: las tareas llegan a los estudiantes del año escolar del curso
--   * SP_REGISTRAR_TAREA asignaba la tarea a los matriculados del año escolar cuyo número es el año
--     del calendario (YEAR(NOW())). Si el año escolar se llama distinto (p. ej. "2025" con fechas de 2026)
--     la tarea no le llegaba a nadie. Ahora usa el año escolar de la asignación del docente.
--   * La calificación inicial queda vacía (NULL) en vez de 0, que en la escala es una nota.
--   * Reparación: tareas PENDIENTES sin los estudiantes de su aula reciben los que faltan.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS SP_REGISTRAR_TAREA;
DELIMITER $$
CREATE PROCEDURE SP_REGISTRAR_TAREA(IN P_ASIGN INT, IN P_TEMA VARCHAR(150), IN P_FECHA DATETIME, IN P_DESCRI VARCHAR(255), IN P_RUTA VARCHAR(255))
BEGIN
  DECLARE V_CORR INT;
  DECLARE V_COD CHAR(12);
  IF EXISTS (SELECT 1 FROM tareas WHERE id_detalle_asignatura = P_ASIGN AND tema = P_TEMA AND DATE(created_at) = CURDATE()) THEN
    SELECT 2;
  ELSE
    SET V_CORR = (SELECT IFNULL(MAX(doc_ncorrelativo), 0) + 1 FROM tareas);
    SET V_COD = CONCAT('T', LPAD(V_CORR, 7, '0'));
    INSERT INTO tareas(id_tarea, id_detalle_asignatura, tema, descripcion, fecha_entrega, archivo_tarea, estado, created_at, updated_at, doc_ncorrelativo)
    VALUES (V_COD, P_ASIGN, P_TEMA, P_DESCRI, P_FECHA, P_RUTA, 'PENDIENTE', NOW(), NULL, V_CORR);
    -- estudiantes del aula del curso en el año escolar de la asignación del docente
    INSERT INTO detalle_tarea(id_tarea, id_matriculado, archivo_evnio_tarea, calificacion, observacion, estado, fecha_envio, created_at, updated_at)
    SELECT V_COD, m.id_matricula, '', NULL, '', 'PENDIENTE', NULL, NOW(), NULL
    FROM detalle_asignatura_docente dd
    JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
    JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
    JOIN matricula m ON m.id_aula = asg.Id_grado AND m.`id_año` = ad.`id_año`
    WHERE dd.Id_detalle_asig_docente = P_ASIGN;
    SELECT 1;
  END IF;
END$$
DELIMITER ;

-- Tareas pendientes a las que les faltan estudiantes de su aula (incluye matriculados después de crear la tarea)
INSERT INTO detalle_tarea(id_tarea, id_matriculado, archivo_evnio_tarea, calificacion, observacion, estado, fecha_envio, created_at, updated_at)
SELECT t.id_tarea, m.id_matricula, '', NULL, '', 'PENDIENTE', NULL, NOW(), NULL
FROM tareas t
JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = t.id_detalle_asignatura
JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
JOIN matricula m ON m.id_aula = asg.Id_grado AND m.`id_año` = ad.`id_año`
WHERE t.estado = 'PENDIENTE'
  AND NOT EXISTS (SELECT 1 FROM detalle_tarea d WHERE d.id_tarea = t.id_tarea AND d.id_matriculado = m.id_matricula);
