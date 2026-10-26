-- =====================================================================
-- Fase 11: "año escolar actual" = el año escolar ACTIVO (no el año del calendario)
--   Varios procedimientos buscaban el año escolar cuyo número es YEAR(NOW()). Si el año escolar se llama
--   distinto al año del calendario (p. ej. "2025" con clases en 2026, o un año que empieza en un año y
--   termina en otro), horarios, alumnos por grado, tareas y exámenes pendientes, pagos del estudiante, etc.
--   salían vacíos. Generado por database/generadores/generar_015.php desde los procedimientos actuales.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP FUNCTION IF EXISTS FN_ANIO_ESCOLAR_ACTUAL;
DELIMITER $$
CREATE FUNCTION FN_ANIO_ESCOLAR_ACTUAL() RETURNS INT
    READS SQL DATA
BEGIN
  DECLARE V INT;
  -- 1) el año escolar ACTIVO (si hubiera más de uno, el más reciente)
  SELECT `año_escolar` INTO V FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC, `Id_año_escolar` DESC LIMIT 1;
  -- 2) si ninguno está activo: el que incluye la fecha de hoy
  IF V IS NULL THEN
    SELECT `año_escolar` INTO V FROM `año_escolar` WHERE CURDATE() BETWEEN fecha_inicio AND fecha_fin ORDER BY fecha_inicio DESC LIMIT 1;
  END IF;
  RETURN IFNULL(V, YEAR(CURDATE()));
END$$
DELIMITER ;

-- SP_CARGAR_HORARIOS_ID_AULA (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_CARGAR_HORARIOS_ID_AULA`;
DELIMITER $$
CREATE PROCEDURE `SP_CARGAR_HORARIOS_ID_AULA`(IN `ID` INT)
SELECT

    CONCAT_WS(' - ',hora_inicio,hora_fin)as hora,
    MAX(CASE WHEN horarios.dia = 'Lunes' THEN asignaturas.nombre_asig ELSE '' END) AS Lunes,
    MAX(CASE WHEN horarios.dia = 'Martes' THEN asignaturas.nombre_asig ELSE '' END) AS Martes,
    MAX(CASE WHEN horarios.dia = 'Miércoles' THEN asignaturas.nombre_asig ELSE '' END) AS Miercoles,
    MAX(CASE WHEN horarios.dia = 'Jueves' THEN asignaturas.nombre_asig ELSE '' END) AS Jueves,
    MAX(CASE WHEN horarios.dia = 'Viernes' THEN asignaturas.nombre_asig ELSE '' END) AS Viernes
FROM
    horarios
INNER JOIN
    horas_aula ON horarios.id_hora_aula = horas_aula.id_hora
INNER JOIN
    detalle_asignatura_docente ON horarios.id_detalle_asig_docente = detalle_asignatura_docente.Id_detalle_asig_docente
INNER JOIN
    asignaturas ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
INNER JOIN
    aulas ON horas_aula.id_aula = aulas.Id_aula
INNER JOIN  `año_escolar` on horas_aula.`id_año_academico` = `año_escolar`.`Id_año_escolar`
    WHERE horas_aula.id_aula = ID and `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL()))
GROUP BY
    horas_aula.hora_inicio, horas_aula.hora_fin
    
ORDER BY
    horas_aula.hora_inicio$$
DELIMITER ;

-- SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE`;
DELIMITER $$
CREATE PROCEDURE `SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE`(IN `ID` INT)
SELECT DISTINCT
	horas_aula.`id_año_academico`, 
	horas_aula.id_aula, 
	horas_aula.turno, 
	horas_aula.estado, 
	`año_escolar`.`año_escolar`, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	horarios.estado AS HORARIO, 
	seccion.seccion_nombre, 
	CONCAT_WS(' - ',Grado,seccion_nombre) AS GRADO, 
	usuario.usu_id
FROM
	horas_aula
	INNER JOIN
	`año_escolar`
	ON 
		horas_aula.`id_año_academico` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	aulas
	ON 
		horas_aula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	horarios
	ON 
		horas_aula.id_hora = horarios.id_hora_aula
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula AND
		`año_escolar`.`Id_año_escolar` = matricula.`id_año`
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id

WHERE
	matricula.usu_id=ID AND `año_escolar` = (SELECT(FN_ANIO_ESCOLAR_ACTUAL()))$$
DELIMITER ;

-- SP_CARGAR_PERIODO (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_CARGAR_PERIODO`;
DELIMITER $$
CREATE PROCEDURE `SP_CARGAR_PERIODO`()
SELECT
periodos.id_periodo,
periodos.periodos,
periodos.`id_año_escolar`,
periodos.tipo_perido,
periodos.fecha_inicio,
	date_format(periodos.fecha_inicio, "%d-%m-%Y") as fecha_formateada,

periodos.fecha_fin,
	date_format(periodos.fecha_fin, "%d-%m-%Y") as fecha_formateada2,
periodos.estado,
`año_escolar`.`año_escolar`,

periodos.created_at,
periodos.updated_at
FROM
`año_escolar`
INNER JOIN periodos ON periodos.`id_año_escolar` = `año_escolar`.`Id_año_escolar`
WHERE `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL())) AND NOW() BETWEEN periodos.fecha_inicio and periodos.fecha_fin$$
DELIMITER ;

-- SP_CARGAR_SELECT_ID_DETALLE_ESTUDIANTE (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_CARGAR_SELECT_ID_DETALLE_ESTUDIANTE`;
DELIMITER $$
CREATE PROCEDURE `SP_CARGAR_SELECT_ID_DETALLE_ESTUDIANTE`(IN `ID` INT, IN `IDESTU` INT)
SELECT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	aulas.Grado, 
	aulas.Id_aula, 
	usuario.usu_id, 
	`año_escolar`.`año_escolar`
FROM
	asignaturas
	INNER JOIN
	detalle_asignatura_docente
	ON 
		asignaturas.Id_asignatura = detalle_asignatura_docente.Id_asignatura
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
    

    
    WHERE
	matricula.Id_aula = ID AND usuario.usu_id=IDESTU AND `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL()))$$
DELIMITER ;

-- SP_CARGAR_SELECT_MATRICULADOS (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_CARGAR_SELECT_MATRICULADOS`;
DELIMITER $$
CREATE PROCEDURE `SP_CARGAR_SELECT_MATRICULADOS`()
SELECT
	matricula.id_matricula,
	CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,   
	matricula.id_alumno, 
	matricula.`id_año`, 
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat, 
	`año_escolar`.`año_escolar`
FROM
	alumnos
	INNER JOIN
	matricula
	ON 
		alumnos.Id_alumno = matricula.id_alumno
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	WHERE `año_escolar`= (SELECT FN_ANIO_ESCOLAR_ACTUAL())$$
DELIMITER ;

-- SP_ELIMINAR_HORAS_AULA (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_ELIMINAR_HORAS_AULA`;
DELIMITER $$
CREATE PROCEDURE `SP_ELIMINAR_HORAS_AULA`(IN `ID` INT)
BEGIN
    DELETE FROM horas_aula
    WHERE id_aula = ID
      AND id_año_academico = (
          SELECT Id_año_escolar
          FROM año_escolar
          WHERE año_escolar = FN_ANIO_ESCOLAR_ACTUAL()
      );
END$$
DELIMITER ;

-- SP_LISTAR_ALUMNOS_EXAMEN (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ALUMNOS_EXAMEN`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ALUMNOS_EXAMEN`(IN `IDDETA` INT)
SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
	alumnos.alum_sexo,
		CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,
 
	aulas.Grado, 
	asignaturas.nombre_asig, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	matricula.`id_año`, 
	`año_escolar`.`año_escolar`
FROM
	alumnos
	INNER JOIN
	matricula
	ON 
		alumnos.Id_alumno = matricula.id_alumno
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	detalle_asignatura_docente
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura AND
		aulas.Id_aula = asignaturas.Id_grado
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
WHERE
	Id_detalle_asig_docente = IDDETA and `año_escolar`=(SELECT FN_ANIO_ESCOLAR_ACTUAL())$$
DELIMITER ;

-- SP_LISTAR_ALUMNOS_GRADO_ANIO (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ALUMNOS_GRADO_ANIO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ALUMNOS_GRADO_ANIO`(IN `ID_AULA` INT)
SELECT
aulas.Grado,
seccion.seccion_nombre,
nivel_academico.Nivel_academico,
`año_escolar`.`año_escolar`,
matricula.`id_año`,
matricula.id_alumno,
matricula.id_matricula,
matricula.id_aula,
alumnos.alum_dni,
alumnos.alum_nombre,
alumnos.alum_apepat,
alumnos.alum_apemat,
alumnos.Id_alumno,
		CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante

FROM
matricula
INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
WHERE
aulas.Id_aula = ID_AULA AND
`año_escolar`.`año_escolar` = (SELECT FN_ANIO_ESCOLAR_ACTUAL())$$
DELIMITER ;

-- SP_LISTAR_EXAMENES_ESTUDIANTES_ID_PENDIENTE (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_EXAMENES_ESTUDIANTES_ID_PENDIENTE`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_EXAMENES_ESTUDIANTES_ID_PENDIENTE`(IN `ID` INT)
SELECT
	examen.id_examen, 
	examen.id_detalle_asignatura, 
	examen.tema_examen, 
	examen.descripcion, 
	examen.fecha_examen, 
	date_format(examen.fecha_examen, "%d-%m-%Y - %H:%i:%s") AS FECHA_EXAMEN, 
	examen.estado AS ESTADO, 
	examen.created_at, 
	date_format(examen.created_at, "%d-%m-%Y") AS fecha_publicacion, 
	examen.updated_at, 
	examen.doc_ncorrelativo, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	detalle_asignatura_docente.Id_asig_docente, 
	detalle_asignatura_docente.Id_asignatura, 
	detalle_asignatura_docente.created_at, 
	detalle_asignatura_docente.updated_at, 
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	docentes.Id_docente, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',asignaturas.nombre_asig,' - ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	`año_escolar`.`año_escolar`, 
	matricula.usu_id
FROM
	examen
	INNER JOIN
	detalle_asignatura_docente
	ON 
		examen.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	`año_escolar`
	ON 
		asignatura_docente.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula AND
		`año_escolar`.`Id_año_escolar` = matricula.`id_año`
		  WHERE `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL())) AND matricula.usu_id=ID AND examen.estado='PENDIENTE'



ORDER BY
	examen.created_at DESC$$
DELIMITER ;

-- SP_LISTAR_EXAMENES_PROFESOR_SOLO (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_EXAMENES_PROFESOR_SOLO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_EXAMENES_PROFESOR_SOLO`(IN `ID` INT)
SELECT
	examen.id_examen, 
	examen.id_detalle_asignatura, 
	examen.tema_examen, 
	examen.descripcion, 
	examen.fecha_examen, 
	date_format(examen.fecha_examen, "%d-%m-%Y - %H:%i:%s") AS FECHA_EXAMEN, 
	examen.estado AS ESTADO, 
	examen.created_at, 
	date_format(examen.created_at, "%d-%m-%Y") AS fecha_publicacion, 
	examen.updated_at, 
	examen.doc_ncorrelativo, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	detalle_asignatura_docente.Id_asig_docente, 
	detalle_asignatura_docente.Id_asignatura, 
	detalle_asignatura_docente.created_at, 
	detalle_asignatura_docente.updated_at, 
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	docentes.Id_docente, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',asignaturas.nombre_asig,' - ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	`año_escolar`.`año_escolar`, 
	usuario.usu_id
FROM
	examen
	INNER JOIN
	detalle_asignatura_docente
	ON 
		examen.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	`año_escolar`
	ON 
		asignatura_docente.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
WHERE `año_escolar`.`año_escolar` = (SELECT(FN_ANIO_ESCOLAR_ACTUAL())) AND usuario.usu_id = ID AND examen.estado="PENDIENTE"

ORDER BY
	examen.created_at DESC$$
DELIMITER ;

-- SP_LISTAR_HORARIOS (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_HORARIOS`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_HORARIOS`()
SELECT DISTINCT
	horas_aula.`id_año_academico`, 
	horas_aula.id_aula, 
	horas_aula.turno, 
	horas_aula.estado, 
	`año_escolar`.`año_escolar`, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	horarios.estado AS HORARIO, 
	seccion.seccion_nombre,
  CONCAT_WS(' - ',Grado,seccion_nombre) as GRADO
FROM
	horas_aula
	INNER JOIN
	`año_escolar`
	ON 
		horas_aula.`id_año_academico` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	aulas
	ON 
		horas_aula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	horarios
	ON 
		horas_aula.id_hora = horarios.id_hora_aula
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id

WHERE
	`año_escolar` = (SELECT(FN_ANIO_ESCOLAR_ACTUAL()))$$
DELIMITER ;

-- SP_LISTAR_PAGO_PENSION_ID (1 reemplazo)
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
    WHERE matricula.usu_id=ID AND `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL()))
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$
DELIMITER ;

-- SP_LISTAR_TAREAS_ESTUDIANTES_ID_PENDIENTE (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_TAREAS_ESTUDIANTES_ID_PENDIENTE`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_TAREAS_ESTUDIANTES_ID_PENDIENTE`(IN `ID` INT)
SELECT DISTINCT
	tareas.id_tarea, 
	tareas.id_detalle_asignatura, 
	tareas.tema, 
	tareas.descripcion, 
	tareas.fecha_entrega, 
	date_format(tareas.fecha_entrega, "%d-%m-%Y - %H:%i:%s") AS fecha_entrega2, 
	tareas.archivo_tarea, 
	tareas.estado, 
	tareas.created_at, 
	date_format(tareas.created_at, "%d-%m-%Y") AS fecha_publicacion, 
	tareas.updated_at, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	detalle_asignatura_docente.Id_asig_docente, 
	detalle_asignatura_docente.Id_asignatura, 
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignaturas.estado, 
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	docentes.Id_docente, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',asignaturas.nombre_asig,' - ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	aulas.Grado, 
	usuario.usu_id, 
	aulas.Id_aula, 
	`año_escolar`.`Id_año_escolar`,
  detalle_tarea.id_detalle_tarea,
      detalle_tarea.archivo_evnio_tarea,  
          detalle_tarea.calificacion,  
    detalle_tarea.observacion,  
 
	detalle_tarea.estado  AS ESTADO
FROM
	tareas
	INNER JOIN
	detalle_asignatura_docente
	ON 
		tareas.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
	INNER JOIN
	detalle_tarea
	ON 
		matricula.id_matricula = detalle_tarea.id_matriculado AND
		tareas.id_tarea = detalle_tarea.id_tarea
  WHERE `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL())) AND usuario.usu_id=ID AND detalle_tarea.estado='PENDIENTE'
ORDER BY
	tareas.created_at ASC$$
DELIMITER ;

-- SP_LISTAR_TAREAS_ESTUDIANTES_SOLO (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_TAREAS_ESTUDIANTES_SOLO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_TAREAS_ESTUDIANTES_SOLO`(IN `ID` INT)
SELECT DISTINCT
	tareas.id_tarea, 
	tareas.id_detalle_asignatura, 
	tareas.tema, 
	tareas.descripcion, 
	tareas.fecha_entrega, 
	date_format(tareas.fecha_entrega, "%d-%m-%Y - %H:%i:%s") AS fecha_entrega2, 
	tareas.archivo_tarea, 
	tareas.estado, 
	tareas.created_at, 
	date_format(tareas.created_at, "%d-%m-%Y") AS fecha_publicacion, 
	tareas.updated_at, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	detalle_asignatura_docente.Id_asig_docente, 
	detalle_asignatura_docente.Id_asignatura, 
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignaturas.estado, 
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	docentes.Id_docente, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',asignaturas.nombre_asig,' - ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	aulas.Grado, 
	usuario.usu_id, 
	aulas.Id_aula, 
	`año_escolar`.`Id_año_escolar`,
  detalle_tarea.id_detalle_tarea,
    detalle_tarea.archivo_evnio_tarea,
    detalle_tarea.calificacion,
    detalle_tarea.observacion,  
	detalle_tarea.estado  AS ESTADO
FROM
	tareas
	INNER JOIN
	detalle_asignatura_docente
	ON 
		tareas.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
	INNER JOIN
	detalle_tarea
	ON 
		matricula.id_matricula = detalle_tarea.id_matriculado AND
		tareas.id_tarea = detalle_tarea.id_tarea
  WHERE `año_escolar`.`año_escolar`=(SELECT(FN_ANIO_ESCOLAR_ACTUAL())) AND usuario.usu_id=ID
ORDER BY
	tareas.created_at ASC$$
DELIMITER ;

-- SP_LISTAR_TAREAS_PROFESOR_ID (1 reemplazo)
DROP PROCEDURE IF EXISTS `SP_LISTAR_TAREAS_PROFESOR_ID`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_TAREAS_PROFESOR_ID`(IN `ID` INT)
SELECT DISTINCT
	tareas.id_tarea, 
	tareas.id_detalle_asignatura, 
	tareas.tema, 
	tareas.descripcion, 
	tareas.fecha_entrega, 
	date_format(tareas.fecha_entrega, "%d-%m-%Y - %H:%i:%s") AS fecha_entrega2, 
	tareas.archivo_tarea, 
	tareas.estado AS ESTADO, 
	tareas.created_at, 
	date_format(tareas.created_at, "%d-%m-%Y") AS fecha_publicacion, 
	tareas.updated_at, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	detalle_asignatura_docente.Id_asig_docente, 
	detalle_asignatura_docente.Id_asignatura, 
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignaturas.estado, 
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	docentes.Id_docente, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',asignaturas.nombre_asig,' - ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	aulas.Grado, 
	usuario.usu_id, 
	aulas.Id_aula, 
	`año_escolar`.`Id_año_escolar`
FROM
	tareas
	INNER JOIN
	detalle_asignatura_docente
	ON 
		tareas.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula
	INNER JOIN
	`año_escolar`
	ON 
		matricula.`id_año` = `año_escolar`.`Id_año_escolar`
WHERE `año_escolar`.`año_escolar` = (SELECT(FN_ANIO_ESCOLAR_ACTUAL())) AND usuario.usu_id = ID AND tareas.estado="PENDIENTE"
ORDER BY
	tareas.created_at ASC$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- Edad calculada siempre a la fecha (antes quedaba fija desde el registro)
-- ---------------------------------------------------------------------
UPDATE alumnos SET Edad = TIMESTAMPDIFF(YEAR, alum_fechanacimiento, CURDATE()) WHERE alum_fechanacimiento IS NOT NULL;

-- SP_LISTAR_ALUMNOS (edad calculada)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ALUMNOS`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ALUMNOS`()
SELECT
alumnos.Id_alumno,
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
		CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
		date_format(alum_fechanacimiento, "%d-%m-%Y") as fecha_formateada2, 
		TIMESTAMPDIFF(YEAR, alumnos.alum_fechanacimiento, CURDATE()) AS Edad, 
	alumnos.alum_movil, 
	alumnos.alum_direccion, 
	alumnos.alum_estatus,
	alumnos.tipo_alum, 
	
	alumnos.alum_fotoperfil, 
	alumnos.created_at,
	date_format(alumnos.created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada, 
	
	alumnos.updated_at, 
	padres.Id_alu,
	padres.id_papas, 	
	padres.Datos_papa, 
	padres.Dni_papa, 
	padres.Celular_papa, 
	padres.Datos_mama, 
	padres.Dni_mama, 
	padres.Celular_mama, 
	padres.created_at as fecha_Registro
FROM
	alumnos
	INNER JOIN
	padres
	ON 
		alumnos.Id_alumno = padres.Id_alu$$
DELIMITER ;

-- SP_LISTAR_ATENCIONES_ENFERMERIA_FILTRO (edad calculada)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ATENCIONES_ENFERMERIA_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ATENCIONES_ENFERMERIA_FILTRO`(IN `IDGRADO` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)
SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	TIMESTAMPDIFF(YEAR, alumnos.alum_fechanacimiento, CURDATE()) AS Edad, 
	matricula.id_matricula, 
	matricula.id_alumno, 
	matricula.`id_año`, 
	matricula.id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	atencion_salud.id_atencion, 
	atencion_salud.id_matricula, 
	atencion_salud.id_usuario, 
	atencion_salud.tipo_atencion, 
	atencion_salud.motivo_consulta, 
	atencion_salud.diagnostico, 
	atencion_salud.observaciones, 
	atencion_salud.created_at, 

			date_format(atencion_salud.created_at, "%d-%m-%Y") as fecha_formateada2, 
	atencion_salud.updated_at, 
	usuario.usu_id, 
	personal_admi.personal_adm_id, 
	personal_admi.personal_adm_dni, 
	personal_admi.personal_adm_nombre, 
	personal_admi.personal_adm_apellido,
		CONCAT_WS(' ',personal_admi.personal_adm_nombre,personal_admi.personal_adm_apellido) AS Personal

FROM
	alumnos
	INNER JOIN
	matricula
	ON 
		alumnos.Id_alumno = matricula.id_alumno
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	atencion_salud
	ON 
		matricula.id_matricula = atencion_salud.id_matricula
	INNER JOIN
	usuario
	ON 
		atencion_salud.id_usuario = usuario.usu_id
	INNER JOIN
	personal_admi
	ON 
		usuario.usu_id = personal_admi.id_ausuario
	WHERE atencion_salud.tipo_atencion='ENFERMERIA' AND matricula.id_aula=IDGRADO AND atencion_salud.created_at BETWEEN FECHAINI AND FECHAFIN$$
DELIMITER ;

-- SP_LISTAR_ATENCIONES_PSDICOLOGICAS_FILTRO (edad calculada)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ATENCIONES_PSDICOLOGICAS_FILTRO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ATENCIONES_PSDICOLOGICAS_FILTRO`(IN `IDGRADO` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)
SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	TIMESTAMPDIFF(YEAR, alumnos.alum_fechanacimiento, CURDATE()) AS Edad, 
	matricula.id_matricula, 
	matricula.id_alumno, 
	matricula.`id_año`, 
	matricula.id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	atencion_salud.id_atencion, 
	atencion_salud.id_matricula, 
	atencion_salud.id_usuario, 
	atencion_salud.tipo_atencion, 
	atencion_salud.motivo_consulta, 
	atencion_salud.diagnostico, 
	atencion_salud.observaciones, 
	atencion_salud.created_at, 

			date_format(atencion_salud.created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada2, 
	atencion_salud.updated_at, 
	usuario.usu_id, 
	personal_admi.personal_adm_id, 
	personal_admi.personal_adm_dni, 
	personal_admi.personal_adm_nombre, 
	personal_admi.personal_adm_apellido,
		CONCAT_WS(' ',personal_admi.personal_adm_nombre,personal_admi.personal_adm_apellido) AS Personal

FROM
	alumnos
	INNER JOIN
	matricula
	ON 
		alumnos.Id_alumno = matricula.id_alumno
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	atencion_salud
	ON 
		matricula.id_matricula = atencion_salud.id_matricula
	INNER JOIN
	usuario
	ON 
		atencion_salud.id_usuario = usuario.usu_id
	INNER JOIN
	personal_admi
	ON 
		usuario.usu_id = personal_admi.id_ausuario
	WHERE atencion_salud.tipo_atencion='PSICOLOGIA' AND matricula.id_aula=IDGRADO AND atencion_salud.created_at BETWEEN FECHAINI AND FECHAFIN$$
DELIMITER ;

-- SP_LISTAR_ATENCION_ENFERME (edad calculada)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ATENCION_ENFERME`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ATENCION_ENFERME`()
SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	TIMESTAMPDIFF(YEAR, alumnos.alum_fechanacimiento, CURDATE()) AS Edad, 
	matricula.id_matricula, 
	matricula.id_alumno, 
	matricula.`id_año`, 
	matricula.id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	atencion_salud.id_atencion, 
	atencion_salud.id_matricula, 
	atencion_salud.id_usuario, 
	atencion_salud.tipo_atencion, 
	atencion_salud.motivo_consulta, 
	atencion_salud.diagnostico, 
	atencion_salud.observaciones, 
	atencion_salud.created_at, 

			date_format(atencion_salud.created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada2, 
	atencion_salud.updated_at, 
	usuario.usu_id, 
	personal_admi.personal_adm_id, 
	personal_admi.personal_adm_dni, 
	personal_admi.personal_adm_nombre, 
	personal_admi.personal_adm_apellido,
		CONCAT_WS(' ',personal_admi.personal_adm_nombre,personal_admi.personal_adm_apellido) AS Personal

FROM
	alumnos
	INNER JOIN
	matricula
	ON 
		alumnos.Id_alumno = matricula.id_alumno
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	atencion_salud
	ON 
		matricula.id_matricula = atencion_salud.id_matricula
	INNER JOIN
	usuario
	ON 
		atencion_salud.id_usuario = usuario.usu_id
	INNER JOIN
	personal_admi
	ON 
		usuario.usu_id = personal_admi.id_ausuario
	WHERE atencion_salud.tipo_atencion='ENFERMERIA'$$
DELIMITER ;

-- SP_LISTAR_ATENCION_PSICO (edad calculada)
DROP PROCEDURE IF EXISTS `SP_LISTAR_ATENCION_PSICO`;
DELIMITER $$
CREATE PROCEDURE `SP_LISTAR_ATENCION_PSICO`()
SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	TIMESTAMPDIFF(YEAR, alumnos.alum_fechanacimiento, CURDATE()) AS Edad, 
	matricula.id_matricula, 
	matricula.id_alumno, 
	matricula.`id_año`, 
	matricula.id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	atencion_salud.id_atencion, 
	atencion_salud.id_matricula, 
	atencion_salud.id_usuario, 
	atencion_salud.tipo_atencion, 
	atencion_salud.motivo_consulta, 
	atencion_salud.diagnostico, 
	atencion_salud.observaciones, 
	atencion_salud.created_at, 

			date_format(atencion_salud.created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada2, 
	atencion_salud.updated_at, 
	usuario.usu_id, 
	personal_admi.personal_adm_id, 
	personal_admi.personal_adm_dni, 
	personal_admi.personal_adm_nombre, 
	personal_admi.personal_adm_apellido,
		CONCAT_WS(' ',personal_admi.personal_adm_nombre,personal_admi.personal_adm_apellido) AS Personal

FROM
	alumnos
	INNER JOIN
	matricula
	ON 
		alumnos.Id_alumno = matricula.id_alumno
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	atencion_salud
	ON 
		matricula.id_matricula = atencion_salud.id_matricula
	INNER JOIN
	usuario
	ON 
		atencion_salud.id_usuario = usuario.usu_id
	INNER JOIN
	personal_admi
	ON 
		usuario.usu_id = personal_admi.id_ausuario
	WHERE atencion_salud.tipo_atencion='PSICOLOGIA'$$
DELIMITER ;
