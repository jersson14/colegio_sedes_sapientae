-- Fase 13: los horarios admiten el sábado (se ofrece solo con "Clases los sábados" en Parámetros del sistema).
ALTER TABLE horarios MODIFY dia ENUM('LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO') NOT NULL;

-- Misma validación de cruces que en la migración 011, con el sábado entre los días válidos
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
  IF V_AULA IS NULL OR V_DOC IS NULL OR P_DIA NOT IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO') THEN
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
