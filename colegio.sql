-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 25-12-2025 a las 18:06:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `colegio`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ELIMINAR_ASIGNATURA` (IN `ID` INT)   DELETE FROM asignaturas
WHERE Id_asignatura=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `LISTAR_ASIGNATURA_DOCENTE` ()   SELECT DISTINCT
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	asignatura_docente.created_at, 
	date_format(asignatura_docente.created_at, "%d-%m-%Y") AS fecha_formateada, 
	asignatura_docente.updated_at, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	asignaturas.Id_grado, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	`año_escolar`.`año_escolar`, 
	`año_escolar`.`Id_año_escolar`, 
	asignatura_docente.`id_año`
FROM
	asignatura_docente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	detalle_asignatura_docente
	ON 
		asignatura_docente.Id_asigdocente = detalle_asignatura_docente.Id_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
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
		asignatura_docente.`id_año` = `año_escolar`.`Id_año_escolar`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ACTUALIZAR_ASISTENCIA` (IN `ID_ASIS` INT, IN `FECHA` DATE, IN `ESTA` VARCHAR(20), IN `OBSER` VARCHAR(1000))   UPDATE asistencia
SET 
    fecha = FECHA,
    estado = ESTA,
    observacion = OBSER,
    updated_at = NOW()
WHERE 
    id_asistencia = ID_ASIS$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ANULAR_EGRESOS` (IN `ID` INT, IN `OBSERVA` VARCHAR(255), IN `USU` INT)   BEGIN
    UPDATE egresos
    SET
    motivo_anulacion=OBSERVA,
    fecha_anulacion=CURDATE(),
    estado='ANULADO',
    egresos.id_user=USU
    WHERE egresos.id_egresos=ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ANULAR_INGRESOS` (IN `ID` INT, IN `OBSERVA` VARCHAR(255), IN `USU` INT)   BEGIN
    UPDATE ingresos
    SET
    motivo_anulacion=OBSERVA,
    fecha_anulacion=CURDATE(),
    estado='ANULADO',
    ingresos.id_user=USU
    WHERE ingresos.id_ingreso=ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_AÑO` ()   SELECT
`año_escolar`.`Id_año_escolar`,
`año_escolar`.`año_escolar`
FROM
`año_escolar` 
ORDER BY `año_escolar` desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_AÑO_POR_ESTUDIANTE` (IN `ID` INT)   SELECT
	`año_escolar`.`Id_año_escolar`, 
	`año_escolar`.`año_escolar`, 
	matricula.usu_id, 
	usuario.usu_id, 
	aulas.Grado, 
	nivel_academico.Nivel_academico
FROM
	`año_escolar`
	INNER JOIN
	matricula
	ON 
		`año_escolar`.`Id_año_escolar` = matricula.`id_año`
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
	INNER JOIN
	aulas
	ON 
		matricula.id_aula = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
  WHERE usuario.usu_id=ID
ORDER BY
	`año_escolar`.`año_escolar` DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_AULAS_POR_DOCENTE` (IN `ID` INT)   SELECT DISTINCT
	aulas.Id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	usuario.usu_id
FROM
	aulas
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	detalle_asignatura_docente
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura AND
		aulas.Id_aula = asignaturas.Id_grado
WHERE
	usuario.usu_id = ID AND
	aulas.estado = 'ACTIVO'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_AULAS_POR_ESTUDIANTE` (IN `ID` INT)   SELECT DISTINCT
	aulas.Id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	usuario.usu_id
FROM
	aulas
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	detalle_asignatura_docente
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	usuario
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura AND
		aulas.Id_aula = asignaturas.Id_grado
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula AND
		usuario.usu_id = matricula.usu_id
WHERE
	usuario.usu_id = ID AND
	aulas.estado = 'ACTIVO'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_DATOS_USUARIO` (IN `ID` INT)   BEGIN
    DECLARE ultimo_mes VARCHAR(20);
    DECLARE siguiente_mes VARCHAR(20);

    -- Obtener el último mes pagado
    SELECT max(pensiones.mes)as maximo
 INTO ultimo_mes
    FROM pago_pensiones
    INNER JOIN pensiones ON pensiones.id_pensiones = pago_pensiones.id_pension
    INNER JOIN matricula ON pago_pensiones.id_matri = matricula.id_matricula
    WHERE matricula.usu_id  = ID
    ORDER BY pago_pensiones.fecha_pago DESC
    LIMIT 1;

    -- Determinar el siguiente mes a pagar
    SET siguiente_mes = CASE 
        WHEN ultimo_mes = 'ENERO' THEN 'FEBRERO'
        WHEN ultimo_mes = 'FEBRERO' THEN 'MARZO'
        WHEN ultimo_mes = 'MARZO' THEN 'ABRIL'
        WHEN ultimo_mes = 'ABRIL' THEN 'MAYO'
        WHEN ultimo_mes = 'MAYO' THEN 'JUNIO'
        WHEN ultimo_mes = 'JUNIO' THEN 'JULIO'
        WHEN ultimo_mes = 'JULIO' THEN 'AGOSTO'
        WHEN ultimo_mes = 'AGOSTO' THEN 'SEPTIEMBRE'
        WHEN ultimo_mes = 'SEPTIEMBRE' THEN 'OCTUBRE'
        WHEN ultimo_mes = 'OCTUBRE' THEN 'NOVIEMBRE'
        WHEN ultimo_mes = 'NOVIEMBRE' THEN 'DICIEMBRE'
        WHEN ultimo_mes = 'DICIEMBRE' THEN 'ENERO'
        ELSE NULL
    END;

    -- Consulta principal para obtener los datos del usuario
    SELECT
        pensiones.id_nivel_academico, 
        pago_pensiones.id_pago_pension, 
        pago_pensiones.id_matri, 
        pago_pensiones.concepto, 
        pago_pensiones.id_pension, 
        pago_pensiones.fecha_pago, 
        DATE_FORMAT(MAX(pago_pensiones.fecha_pago), "%d-%m-%Y") AS FECHA_pago, 
        pago_pensiones.sub_total, 
        pago_pensiones.created_at, 
        matricula.id_alumno, 
        alumnos.Id_alumno, 
        alumnos.alum_dni, 
        alumnos.alum_nombre, 
        alumnos.alum_apepat, 
        alumnos.alum_apemat, 
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante, 
        max(año_escolar.año_escolar)as año_escolar, 
        aulas.Grado, 
        seccion.seccion_nombre, 
        nivel_academico.Nivel_academico, 
        alumnos.tipo_alum, 
        matricula.departamento, 
        usuario.usu_id,
        siguiente_mes AS Mes_Toca_Pagar
    FROM
        pago_pensiones
    LEFT JOIN
        pensiones ON pensiones.id_pensiones = pago_pensiones.id_pension
    INNER JOIN
        matricula ON pago_pensiones.id_matri = matricula.id_matricula
    INNER JOIN
        alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN
        año_escolar ON matricula.id_año = año_escolar.Id_año_escolar
    INNER JOIN
        aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN
        seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN
        nivel_academico ON pensiones.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN
        usuario ON matricula.usu_id = usuario.usu_id
    WHERE
        usuario.usu_id = ID;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_HORARIOS_ID_AULA` (IN `ID` INT)   SELECT

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
    WHERE horas_aula.id_aula = ID and `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW())))
GROUP BY
    horas_aula.hora_inicio, horas_aula.hora_fin
    
ORDER BY
    horas_aula.hora_inicio$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE` (IN `ID` INT)   SELECT DISTINCT
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
	matricula.usu_id=ID AND `año_escolar` = (SELECT(YEAR(NOW())))$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE_AÑO` (IN `ID` INT, IN `AÑO` INT)   SELECT DISTINCT
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
	matricula.usu_id=ID AND `año_escolar`.`Id_año_escolar` = AÑO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_HORARIOS_ID_AULA_ESTUDIANTE_TODO` (IN `ID` INT)   SELECT DISTINCT
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
	matricula.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_HORA_ID_AULA` (IN `ID` INT, IN `AÑO` INT)   SELECT
	horas_aula.id_hora, 
	horas_aula.`id_año_academico`, 
	horas_aula.id_aula, 
	horas_aula.turno, 
	horas_aula.hora_inicio, 
	horas_aula.hora_fin, 
	horas_aula.estado, 
	horas_aula.created_at, 
	`año_escolar`.`año_escolar`
FROM
	horas_aula
	INNER JOIN
	`año_escolar`
	ON 
		horas_aula.`id_año_academico` = `año_escolar`.`Id_año_escolar`
WHERE horas_aula.id_aula =ID and `año_escolar`=AÑO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_PERIODO` ()   SELECT
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
WHERE `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW()))) AND NOW() BETWEEN periodos.fecha_inicio and periodos.fecha_fin$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_PERIODO2` ()   SELECT
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
INNER JOIN periodos ON periodos.`id_año_escolar` = `año_escolar`.`Id_año_escolar`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_PERIODO_CARGADOS` ()   SELECT DISTINCT
	notas.id_bimestre, 
  periodos.id_periodo,
	periodos.periodos
FROM
	notas
	INNER JOIN
	periodos
	ON 
		notas.id_bimestre = periodos.id_periodo
      order by periodos desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SEGUIMIENTO_TRAMITE` (IN `NUMERO` VARCHAR(12), IN `DNI` VARCHAR(8))   SELECT
	documento.documento_id, 
	documento.doc_dniremitente, 
	CONCAT_WS(' ',documento.doc_nombreremitente,documento.doc_apepatremitente,documento.doc_apematremitente),
	MONTHNAME(documento.doc_fecharegistro) AS FECHA,
		date_format(doc_fecharegistro, "%d de %M del %Y - %H:%i:%s %p") as fecha_formateada
FROM
	documento
WHERE documento.documento_id=NUMERO AND documento.doc_dniremitente=DNI$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SEGUIMIENTO_TRAMITE_DETALLE` (IN `NUMERO` VARCHAR(12))   SELECT DISTINCT
	movimiento.movimiento_id, 
	movimiento.documento_id,
	area.area_cod, 
	area.area_nombre,
	date_format(mov_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
	movimiento.mov_fecharegistro, 
	movimiento.mov_descripcion, 
	movimiento.mov_estatus
FROM
	movimiento
	INNER JOIN
	area
	ON 
		movimiento.areadestino_id = area.area_cod

	WHERE movimiento.documento_id=NUMERO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_AREA` ()   SELECT
	area.area_cod, 
	area.area_nombre, 
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat,
	CONCAT_WS(' ',empleado.emple_nombre,empleado.emple_apepat,empleado.emple_apemat) AS ENCARGADO

FROM
	area
	INNER JOIN
	usuario
	ON 
		area.area_cod = usuario.area_id
	INNER JOIN
	empleado
	ON 
		usuario.empleado_id = empleado.empleado_id
WHERE area.area_estado="ACTIVO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ASIGNATURA` (IN `ID` INT)   SELECT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado,
	aulas.Grado, 	
	asignaturas.observaciones, 
	asignaturas.estado, 
	asignaturas.created_at,
	date_format(asignaturas.created_at, "%d-%m-%Y") as fecha_formateada,	
	asignaturas.updated_at, 
	seccion.seccion_nombre,
  CONCAT_WS(' ',aulas.Grado,'-',seccion.seccion_nombre) as	GradoSECCION,
	nivel_academico.Nivel_academico
FROM
	asignaturas
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
		
 WHERE asignaturas.estado='SIN DOCENTE' AND asignaturas.Id_grado=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_AULA_ID` (IN `ID` INT)   SELECT
nivel_academico.Id_nivel,
aulas.Id_aula,
aulas.Grado
FROM
nivel_academico
INNER JOIN aulas ON aulas.id_nivel_academico = nivel_academico.Id_nivel
WHERE id_nivel_academico=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_BIMESTRE_DOCENTE` (IN `ID` INT)   SELECT DISTINCT
	notas.id_bimestre, 
	periodos.id_periodo, 
	periodos.periodos, 
	matricula.usu_id
FROM
	notas
	INNER JOIN
	periodos
	ON 
		notas.id_bimestre = periodos.id_periodo
	INNER JOIN
	aulas
	INNER JOIN
	`año_escolar`
	ON 
		periodos.`id_año_escolar` = `año_escolar`.`Id_año_escolar`
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula AND
		`año_escolar`.`Id_año_escolar` = matricula.`id_año` AND
		notas.id_matricula = matricula.id_matricula
WHERE
	matricula.id_matricula = ID
ORDER BY
	periodos.periodos DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_BIMESTRE_ESTUDIANTE` (IN `ID` INT)   BEGIN
    SELECT DISTINCT
        notas.id_bimestre, 
        periodos.id_periodo, 
        periodos.periodos
    FROM
        notas
        INNER JOIN periodos
            ON notas.id_bimestre = periodos.id_periodo
        INNER JOIN aulas
        INNER JOIN `año_escolar`
            ON periodos.`id_año_escolar` = `año_escolar`.`Id_año_escolar`
        INNER JOIN matricula
            ON aulas.Id_aula = matricula.id_aula
            AND `año_escolar`.`Id_año_escolar` = matricula.`id_año`
            AND notas.id_matricula = matricula.id_matricula
        INNER JOIN pago_pensiones
            ON matricula.id_matricula = pago_pensiones.id_matri
    WHERE
        pago_pensiones.id_matri = ID
        AND pago_pensiones.fecha_pago BETWEEN periodos.fecha_inicio AND periodos.fecha_fin
    ORDER BY
        periodos.periodos DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_CURSO_DOCENTE` (IN `ID` INT)   SELECT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	docentes.Id_docente
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
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
where docentes.Id_docente=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_DNI` ()   SELECT
	empleado.empleado_id, 
	empleado.emple_nrodocumento, 
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat, 
	CONCAT_WS(' ',empleado.emple_nombre,empleado.emple_apepat,empleado.emple_apemat) AS EMPLEADO, 
	empleado.emple_movil, 
	empleado.emple_email, 
	empleado.emple_direccion, 
	area.area_cod, 
	area.area_nombre, 
	usuario.usu_id, 
	usuario.area_id, 
	usuario.usu_rol
FROM
	area
	INNER JOIN
	usuario
	ON 
		area.area_cod = usuario.area_id
	INNER JOIN
	empleado
	ON 
		empleado.empleado_id = usuario.empleado_id
	WHERE empleado.emple_estatus="ACTIVO" and area.area_estado="ACTIVO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_DOCENTE` ()   SELECT
	docentes.Id_docente,
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	docentes.updated_at, 
	usuario.usu_id, 
	roles.Id_rol, 
	roles.tipo_rol, 
	especialidad.Especialidad, 
	especialidad.Id_especilidad
FROM
	docentes
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
	INNER JOIN
	roles
	ON 
		usuario.rol_id = roles.Id_rol
	INNER JOIN
	especialidad
	ON 
		docentes.especialidad_id = especialidad.Id_especilidad
	WHERE roles.tipo_rol='DOCENTE'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_EMPLEADO` ()   SELECT
	empleado.empleado_id, 
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat,
	CONCAT_WS(' ',emple_nombre,emple_apepat,emple_apemat)
FROM
	empleado
	WHERE empleado.emple_estatus="ACTIVO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ESPECIALIDAD` ()   SELECT
	especialidad.Id_especilidad, 
	especialidad.Especialidad
FROM
	especialidad$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ESTUDIANTE` ()   SELECT
alumnos.Id_alumno,
alumnos.alum_dni,
		CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
alumnos.alum_nombre,
alumnos.alum_apepat,
alumnos.alum_apemat
FROM
alumnos
WHERE alumnos.alum_estatus='NO'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_GRADO` ()   SELECT
	aulas.Id_aula, 
	aulas.Grado, 
	nivel_academico.Nivel_academico
FROM
	aulas
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_HORAS` (IN `ID` INT, IN `AÑO` INT)   SELECT
	horas_aula.id_hora, 
	CONCAT_WS(' - ',hora_inicio,hora_fin) AS HORAS, 
	horas_aula.hora_inicio, 
	horas_aula.hora_fin, 
	horas_aula.estado, 
	horas_aula.id_aula, 
	`año_escolar`.`año_escolar`
FROM
	horas_aula
	INNER JOIN
	`año_escolar`
	ON 
		horas_aula.`id_año_academico` = `año_escolar`.`Id_año_escolar`
WHERE
	horas_aula.id_aula=ID AND horas_aula.`id_año_academico`=AÑO AND horas_aula.estado='ACTIVO'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ID_DETALLE` (IN `ID` INT)   SELECT
asignaturas.Id_asignatura,
asignaturas.nombre_asig,
detalle_asignatura_docente.Id_detalle_asig_docente,
aulas.Grado,
aulas.Id_aula
FROM
asignaturas
INNER JOIN detalle_asignatura_docente ON asignaturas.Id_asignatura = detalle_asignatura_docente.Id_asignatura
INNER JOIN asignatura_docente ON detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
INNER JOIN aulas ON asignaturas.Id_grado = aulas.Id_aula
where Id_aula=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ID_DETALLE_ESTUDIANTE` (IN `ID` INT, IN `IDESTU` INT)   SELECT
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
	matricula.Id_aula = ID AND usuario.usu_id=IDESTU AND `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW())))$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ID_DETALLE_HORARIO` (IN `ID` INT, IN `AÑO` INT)   SELECT DISTINCT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	aulas.Grado, 
	aulas.Id_aula, 
	`año_escolar`.`año_escolar`, 
	`año_escolar`.`Id_año_escolar`
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
	`año_escolar`
	ON 
		asignatura_docente.`id_año` = `año_escolar`.`Id_año_escolar`
WHERE
	aulas.Id_aula = ID AND
	`año_escolar`.`Id_año_escolar` = `AÑO`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ID_DETALLE_PROFESOR` (IN `ID` INT, IN `IDPRO` INT)   SELECT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	aulas.Grado, 
	aulas.Id_aula, 
	usuario.usu_id
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
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
WHERE
	Id_aula = ID AND usuario.usu_id=IDPRO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_INDICADORES` ()   SELECT
	indicadores.id_indicadores, 
	indicadores.nombre
FROM
	indicadores
WHERE indicadores.tipo_indicador='INGRESOS'AND NOT indicadores.id_indicadores=1$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_INDICADORES_GASTOS` ()   SELECT
	indicadores.id_indicadores, 
	indicadores.nombre
FROM
	indicadores
WHERE indicadores.tipo_indicador='GASTOS'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_MATRICULADOS` ()   SELECT
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
	WHERE `año_escolar`= (SELECT YEAR(NOW()))$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_NIVELACA` ()   SELECT
	nivel_academico.Id_nivel, 
	nivel_academico.Nivel_academico
FROM
	nivel_academico$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_PENSION` (IN `ID` INT)   SELECT
pensiones.id_pensiones,
pensiones.mes,
pensiones.fecha_vencimiento,
nivel_academico.Id_nivel,
nivel_academico.Nivel_academico
FROM
pensiones
INNER JOIN nivel_academico ON pensiones.id_nivel_academico = nivel_academico.Id_nivel
WHERE nivel_academico.Id_nivel = ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ROL` ()   SELECT
	roles.Id_rol, 
	roles.tipo_rol
FROM
	roles$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ROLES_UNICO` ()   SELECT
	roles.Id_rol, 
	roles.tipo_rol
FROM
	roles$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_ROL_PERSONAL` ()   SELECT
	roles.Id_rol, 
	roles.tipo_rol
FROM
	roles
WHERE NOT tipo_rol = 'ESTUDIANTE' AND NOT tipo_rol='DOCENTE'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_SECCION` ()   SELECT
	seccion.seccion_id, 
	seccion.seccion_nombre
FROM
	seccion
ORDER BY seccion_nombre desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_COMPROBAR_COMPONENTE` (IN `ID_DETALLE` INT)   BEGIN
    DECLARE existencia INT;
    
    SELECT COUNT(*) INTO existencia
    FROM criterios
    WHERE criterios.id_detalle_asignatura = ID_DETALLE;
    
    IF existencia > 0 THEN
        SELECT 1; -- Existe
    ELSE
        SELECT 0; -- No existe
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_EDITAR_ASISTENCIA` (IN `ID_ASIS` INT, IN `ID_MATRI` INT, IN `FECHA` DATE, IN `ESTA` VARCHAR(20), IN `OBSER` VARCHAR(1000))   BEGIN
    DECLARE CANTIDAD INT;

    -- Verifica si existe un registro con el mismo id_matricula y fecha (excluyendo el registro actual)
    SELECT COUNT(*) INTO CANTIDAD 
    FROM asistencia 
    WHERE id_matricula = ID_MATRI 
      AND fecha = FECHA
      AND id_asistencia = ID_ASIS;  -- Verifica si el registro actual cumple con la condición

    IF CANTIDAD > 0 THEN
        -- Si existe, actualiza el registro
        UPDATE asistencia
        SET estado = ESTA, observacion = OBSER, updated_at = NOW()
        WHERE id_asistencia = ID_ASIS;
        SELECT 1; -- Indica éxito en la actualización
    ELSE
        SELECT 2; -- Indica que no se debe actualizar porque no coincide id_matricula y fecha
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_EDITAR_NOTAS` (IN `p_ID_NOTA` INT, IN `p_NOTA` CHAR(5), IN `p_CONCLUSIONES` VARCHAR(1000))   BEGIN
    DECLARE v_COUNT INT;
    DECLARE v_EXIT_CODE INT DEFAULT 0;
    DECLARE v_EXIT_MESSAGE VARCHAR(100);

    -- Verifica si existe un registro con el ID_NOTA proporcionado
    SELECT COUNT(*) INTO v_COUNT
    FROM notas
    WHERE id_nota_bole = p_ID_NOTA;

    IF v_COUNT > 0 THEN
        -- Si existe, intenta actualizar el registro
        UPDATE notas
        SET nota = p_NOTA, 
            conclusiones = p_CONCLUSIONES, 
            updated_at = NOW()
        WHERE id_nota_bole = p_ID_NOTA;

        IF ROW_COUNT() > 0 THEN
            SET v_EXIT_CODE = 1;
            SET v_EXIT_MESSAGE = 'Nota actualizada exitosamente';
        ELSE
            SET v_EXIT_CODE = 3;
            SET v_EXIT_MESSAGE = 'No se pudo actualizar la nota';
        END IF;
    ELSE
        SET v_EXIT_CODE = 2;
        SET v_EXIT_MESSAGE = 'No se encontró la nota con el ID proporcionado';
    END IF;

    -- Devuelve el código de salida y el mensaje
    SELECT v_EXIT_CODE AS exit_code, v_EXIT_MESSAGE AS exit_message;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_EDITAR_NOTAS_PAPAS` (IN `p_ID_NOTA` INT, IN `p_CRITERIOS` VARCHAR(2555), IN `p_NOTA` CHAR(5))   BEGIN
    DECLARE v_COUNT INT;
    DECLARE v_EXIT_CODE INT DEFAULT 0;
    DECLARE v_EXIT_MESSAGE VARCHAR(100);

    -- Verifica si existe un registro con el ID_NOTA proporcionado
    SELECT COUNT(*) INTO v_COUNT
    FROM notas_padre
    WHERE id_nota_papa = p_ID_NOTA;

    IF v_COUNT > 0 THEN
        -- Si existe, intenta actualizar el registro
        UPDATE notas_padre
        SET nota = p_NOTA, 
            criterio = p_CRITERIOS, 
            updated_at = NOW()
        WHERE id_nota_papa = p_ID_NOTA;

        IF ROW_COUNT() > 0 THEN
            SET v_EXIT_CODE = 1;
            SET v_EXIT_MESSAGE = 'Nota actualizada exitosamente';
        ELSE
            SET v_EXIT_CODE = 3;
            SET v_EXIT_MESSAGE = 'No se pudo actualizar la nota';
        END IF;
    ELSE
        SET v_EXIT_CODE = 2;
        SET v_EXIT_MESSAGE = 'No se encontró la nota con el ID proporcionado';
    END IF;

    -- Devuelve el código de salida y el mensaje
    SELECT v_EXIT_CODE AS exit_code, v_EXIT_MESSAGE AS exit_message;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_ALUMNO` (IN `ID` INT)   DELETE FROM alumnos 
WHERE alumnos.alum_dni=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_AÑO` (IN `ID` INT)   DELETE FROM año_escolar where Id_año_escolar=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_ASIGNACION_DOCENTE` (IN `ID` INT)   BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE asignatura_id INT;

    -- Declare cursor
    DECLARE cursor_asignaturas CURSOR FOR
        SELECT detalle_asignatura_docente.Id_asignatura 
        FROM detalle_asignatura_docente 
        WHERE detalle_asignatura_docente.Id_asig_docente = ID;

    -- Declare continue handler for cursor
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Open cursor
    OPEN cursor_asignaturas;

    -- Loop through all rows in cursor
    REPEAT
        -- Fetch row
        FETCH cursor_asignaturas INTO asignatura_id;

        -- If there is a row
        IF NOT done THEN
            -- Update the state of the assignment
            UPDATE asignaturas
            SET asignaturas.estado = 'SIN DOCENTE'
            WHERE asignaturas.Id_asignatura = asignatura_id;
        END IF;
    UNTIL done END REPEAT;

    -- Close cursor
    CLOSE cursor_asignaturas;

    -- Eliminate the assignment of the teacher
    DELETE FROM asignatura_docente 
    WHERE asignatura_docente.Id_asigdocente = ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_ASIGNACION_DOCENTE_UNICO` (IN `ID` INT)   BEGIN
    DECLARE asignatura_id INT;
    DECLARE id_asigdocente INT;    
    DECLARE TOTALCURSOS INT;

    -- Obtener el ID de la asignatura
    SELECT Id_asignatura
    INTO asignatura_id
    FROM detalle_asignatura_docente
    WHERE Id_detalle_asig_docente = ID;

    -- Obtener el ID de la asignación del docente
    SELECT Id_asig_docente
    INTO id_asigdocente
    FROM detalle_asignatura_docente
    WHERE Id_detalle_asig_docente = ID;

    -- Eliminar la asignación del docente
    DELETE FROM detalle_asignatura_docente
    WHERE Id_detalle_asig_docente = ID;

    -- Contar el total de cursos después de eliminar la asignación
    SELECT COUNT(*)
    INTO TOTALCURSOS
    FROM detalle_asignatura_docente
    WHERE Id_asig_docente = id_asigdocente;

    -- Actualizar el estado de la asignatura
    UPDATE asignaturas
    SET estado = 'SIN DOCENTE'
    WHERE Id_asignatura = asignatura_id;

    -- Actualizar el total de cursos del docente
    UPDATE asignatura_docente
    SET Total_cursos = TOTALCURSOS
    WHERE Id_asigdocente = id_asigdocente;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_ASISTENCIA_POR_FECHA_Y_AULA` (IN `FECHA` DATE, IN `ID_AULA` INT)   BEGIN
    DELETE asistencia
    FROM asistencia
    INNER JOIN matricula ON asistencia.id_matricula = matricula.id_matricula
    WHERE asistencia.fecha = FECHA
    AND matricula.id_aula = ID_AULA;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_AULA` (IN `ID` INT)   DELETE FROM aulas WHERE Id_aula =ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_COMPONENTES` (IN `ID` INT)   DELETE FROM criterios WHERE criterios.id_detalle_asignatura=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_COMPONENTES_UNICO` (IN `ID_CRI` INT)   DELETE FROM criterios WHERE criterios.id_criterio=ID_CRI$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_COMUNICADO` (IN `ID` INT)   DELETE FROM comunicados WHERE id_comunicado=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_DOCENTE` (IN `ID` INT)   DELETE FROM usuario WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_ESPECIALIDAD` (IN `ID` INT)   DELETE FROM especialidad WHERE Id_especilidad=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_EXAMEN` (IN `ID` CHAR(12))   DELETE FROM examen WHERE examen.id_examen=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_HORARIO` (IN `ID` INT)   BEGIN
    DELETE FROM horarios
    WHERE id_hora_aula IN (SELECT id_hora FROM horas_aula WHERE id_aula = ID);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_HORARIO_UNICO` (IN `ID` INT)   DELETE FROM horarios WHERE horarios.id_horario =ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_HORAS_AULA` (IN `ID` INT)   BEGIN
    DELETE FROM horas_aula
    WHERE id_aula = ID
      AND id_año_academico = (
          SELECT Id_año_escolar
          FROM año_escolar
          WHERE año_escolar = YEAR(NOW())
      );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_HORAS_UNICO` (IN `ID_HORA` INT)   DELETE FROM horas_aula WHERE horas_aula.id_hora=ID_HORA$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_INDICADOR` (IN `ID` INT)   DELETE FROM indicadores WHERE indicadores.id_indicadores=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_MATRICULA` (IN `ID` INT)   BEGIN

    DECLARE CONTAR INT;  -- Declaramos la variable local
    
    -- Asignación del valor a la variable local CONTAR
    SELECT COUNT(id_matri) INTO CONTAR FROM pago_pensiones WHERE pago_pensiones.id_matri = ID;

    -- Estructura de control IF
    IF CONTAR <= 3 THEN
        -- Si hay 3 o menos registros, elimina la matrícula
        DELETE FROM matricula WHERE matricula.id_matricula = ID;
        SELECT 1;  -- Retorna 1 si se realiza la eliminación
    ELSE
        SELECT 2;  -- Retorna 2 si no se realiza la eliminación debido a que hay más de 3 pensiones asociadas
    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_NIVEL_ACADEMICO` (IN `ID` INT)   DELETE FROM nivel_academico WHERE Id_nivel=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_PAGO_PENSION` (IN `ID` INT)   BEGIN
    -- Elimina primero los registros de ingresos que dependen del pago
    DELETE FROM ingresos WHERE ingresos.id_pago_pension = ID;

    -- Luego elimina el pago de la tabla pago_pensiones
    DELETE FROM pago_pensiones WHERE pago_pensiones.id_pago_pension = ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_PENSION` (IN `ID` INT)   DELETE FROM pensiones WHERE pensiones.id_pensiones=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_PERIODO` (IN `ID` INT)   DELETE FROM periodos WHERE periodos.`id_año_escolar`=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_PERIODO_UNICO` (IN `ID` INT)   DELETE FROM periodos WHERE periodos.id_periodo=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_PERSONAL` (IN `ID` INT)   DELETE FROM usuario WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_ROL` (IN `ID` INT)   DELETE FROM roles WHERE Id_rol=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_SECCION` (IN `ID` INT)   DELETE FROM seccion WHERE seccion_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_TAREA` (IN `ID` CHAR(12))   DELETE FROM tareas where id_tarea=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ENVIAR_TAREA` (IN `ID` CHAR(12), IN `RUTA` VARCHAR(255))   UPDATE detalle_tarea SET
	detalle_tarea.archivo_evnio_tarea=RUTA,
  detalle_tarea.estado='ENVIADO',
	detalle_tarea.fecha_envio=NOW()
	WHERE detalle_tarea.id_detalle_tarea=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_INSERTAR_Y_ACTUALIZAR_COMPONENTES` (IN `ID_ASIG_DETALLE` INT, IN `COMPO` VARCHAR(255), IN `OBSER` TEXT)   BEGIN
    -- Verificar si el componente ya existe
    DECLARE componente_existente INT;

    -- Contar los componentes existentes
    SELECT COUNT(*) INTO componente_existente
    FROM criterios
    WHERE id_detalle_asignatura = ID_ASIG_DETALLE;

    IF componente_existente = 0 THEN
        -- Si el componente no existe, registrar nuevo
        INSERT INTO criterios (id_detalle_asignatura, competencias, descripción_observa, created_at, updated_at)
        VALUES (ID_ASIG_DETALLE, COMPO, OBSER, NOW(), NOW());

        -- Retornar 1 para indicar que el componente fue registrado
        SELECT 1 AS resultado;
    ELSE
        -- Retornar 0 para indicar que el componente ya existe
        SELECT 0 AS resultado;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ADIGNDOCENTE_FILTRO` (IN `GRADO` INT)   SELECT DISTINCT
	asignatura_docente.Id_asigdocente, 
	asignatura_docente.Id_docente, 
	asignatura_docente.Total_cursos, 
	asignatura_docente.created_at, 
	date_format(asignatura_docente.created_at, "%d-%m-%Y") AS fecha_formateada, 
	asignatura_docente.updated_at, 
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	asignaturas.Id_grado, 
	aulas.Grado, 
	nivel_academico.Nivel_academico, 
	`año_escolar`.`año_escolar`, 
	`año_escolar`.`Id_año_escolar`, 
	asignatura_docente.`id_año`
FROM
	asignatura_docente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	detalle_asignatura_docente
	ON 
		asignatura_docente.Id_asigdocente = detalle_asignatura_docente.Id_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
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
  WHERE aulas.Id_aula=GRADO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS` ()   SELECT
alumnos.Id_alumno,
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
		CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante, 
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
		date_format(alum_fechanacimiento, "%d-%m-%Y") as fecha_formateada2, 
		alumnos.Edad, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_EXAMEN` (IN `IDDETA` INT)   SELECT
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
	Id_detalle_asig_docente = IDDETA and `año_escolar`=(SELECT YEAR(NOW()))$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_FECHA` (IN `FECHA` DATE, IN `ID_AULA` INT)   SELECT
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
CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,
asistencia.id_asistencia,
asistencia.id_matricula,
asistencia.mes,
asistencia.fecha,
		date_format(asistencia.fecha, "%d-%m-%Y") as fecha_formateada2, 

asistencia.estado,
asistencia.observacion,
asistencia.created_at,
asistencia.updated_at
FROM
matricula
INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
WHERE aulas.Id_aula=ID_AULA AND
asistencia.fecha=FECHA$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_GRADO_ANIO` (IN `ID_AULA` INT)   SELECT
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
`año_escolar`.`año_escolar` = (SELECT YEAR(NOW()))$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_MES` (IN `MES` INT, IN `ID_AULA` INT)   BEGIN
    SELECT
				alumnos.Id_alumno,
        alumnos.alum_dni AS DNI,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        SUM(CASE WHEN asistencia.estado = 'PRESENTE' THEN 1 ELSE 0 END) AS total_asistencia_presente,
        SUM(CASE WHEN asistencia.estado = 'TARDE' THEN 1 ELSE 0 END) AS total_asistencia_tarde,
        SUM(CASE WHEN asistencia.estado = 'AUSENTE' THEN 1 ELSE 0 END) AS total_asistencia_ausente,
        SUM(CASE WHEN asistencia.estado = 'JUSTIFICADO' THEN 1 ELSE 0 END) AS total_asistencia_justificado
    FROM
        matricula
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
    WHERE
        aulas.Id_aula = ID_AULA AND
        MONTH(asistencia.fecha) = MES
    GROUP BY
        alumnos.alum_dni,
				alumnos.alum_apepat,
        Estudiante;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_TOTALES` (IN `AÑO` INT, IN `MES` VARCHAR(18), IN `ID_AULA` INT)   BEGIN
    SELECT
        alumnos.Id_alumno,
        alumnos.alum_dni AS DNI,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        SUM(CASE WHEN asistencia.estado = 'PRESENTE' THEN 1 ELSE 0 END) AS total_asistencia_presente,
        SUM(CASE WHEN asistencia.estado = 'TARDE' THEN 1 ELSE 0 END) AS total_asistencia_tarde,
        SUM(CASE WHEN asistencia.estado = 'AUSENTE' THEN 1 ELSE 0 END) AS total_asistencia_ausente,
        SUM(CASE WHEN asistencia.estado = 'JUSTIFICADO' THEN 1 ELSE 0 END) AS total_asistencia_justificado,
        SUM(CASE 
            WHEN asistencia.estado IN ('PRESENTE', 'TARDE', 'AUSENTE', 'JUSTIFICADO') 
            THEN 1 
            ELSE 0 

        END) AS total_general
    FROM
        matricula
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
    WHERE
        aulas.Id_aula = ID_AULA AND
        asistencia.mes = MES AND
				`año_escolar`.`Id_año_escolar`=AÑO

    GROUP BY
        alumnos.Id_alumno,
        alumnos.alum_dni,
        Estudiante;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_TOTALES_DIA` (IN `AÑO` INT, IN `MES` VARCHAR(18), IN `ID_AULA` INT)   BEGIN
    SELECT
        alumnos.Id_alumno,
        alumnos.alum_dni AS DNI,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        -- Días del 1 al 31
        MAX(CASE WHEN DAY(asistencia.fecha) = 1 THEN asistencia.estado ELSE '' END) AS dia_1,
        MAX(CASE WHEN DAY(asistencia.fecha) = 2 THEN asistencia.estado ELSE '' END) AS dia_2,
        MAX(CASE WHEN DAY(asistencia.fecha) = 3 THEN asistencia.estado ELSE '' END) AS dia_3,
        MAX(CASE WHEN DAY(asistencia.fecha) = 4 THEN asistencia.estado ELSE '' END) AS dia_4,
        MAX(CASE WHEN DAY(asistencia.fecha) = 5 THEN asistencia.estado ELSE '' END) AS dia_5,
        MAX(CASE WHEN DAY(asistencia.fecha) = 6 THEN asistencia.estado ELSE '' END) AS dia_6,
        MAX(CASE WHEN DAY(asistencia.fecha) = 7 THEN asistencia.estado ELSE '' END) AS dia_7,
        MAX(CASE WHEN DAY(asistencia.fecha) = 8 THEN asistencia.estado ELSE '' END) AS dia_8,
        MAX(CASE WHEN DAY(asistencia.fecha) = 9 THEN asistencia.estado ELSE '' END) AS dia_9,
        MAX(CASE WHEN DAY(asistencia.fecha) = 10 THEN asistencia.estado ELSE '' END) AS dia_10,
        MAX(CASE WHEN DAY(asistencia.fecha) = 11 THEN asistencia.estado ELSE '' END) AS dia_11,
        MAX(CASE WHEN DAY(asistencia.fecha) = 12 THEN asistencia.estado ELSE '' END) AS dia_12,
        MAX(CASE WHEN DAY(asistencia.fecha) = 13 THEN asistencia.estado ELSE '' END) AS dia_13,
        MAX(CASE WHEN DAY(asistencia.fecha) = 14 THEN asistencia.estado ELSE '' END) AS dia_14,
        MAX(CASE WHEN DAY(asistencia.fecha) = 15 THEN asistencia.estado ELSE '' END) AS dia_15,
        MAX(CASE WHEN DAY(asistencia.fecha) = 16 THEN asistencia.estado ELSE '' END) AS dia_16,
        MAX(CASE WHEN DAY(asistencia.fecha) = 17 THEN asistencia.estado ELSE '' END) AS dia_17,
        MAX(CASE WHEN DAY(asistencia.fecha) = 18 THEN asistencia.estado ELSE '' END) AS dia_18,
        MAX(CASE WHEN DAY(asistencia.fecha) = 19 THEN asistencia.estado ELSE '' END) AS dia_19,
        MAX(CASE WHEN DAY(asistencia.fecha) = 20 THEN asistencia.estado ELSE '' END) AS dia_20,
        MAX(CASE WHEN DAY(asistencia.fecha) = 21 THEN asistencia.estado ELSE '' END) AS dia_21,
        MAX(CASE WHEN DAY(asistencia.fecha) = 22 THEN asistencia.estado ELSE '' END) AS dia_22,
        MAX(CASE WHEN DAY(asistencia.fecha) = 23 THEN asistencia.estado ELSE '' END) AS dia_23,
        MAX(CASE WHEN DAY(asistencia.fecha) = 24 THEN asistencia.estado ELSE '' END) AS dia_24,
        MAX(CASE WHEN DAY(asistencia.fecha) = 25 THEN asistencia.estado ELSE '' END) AS dia_25,
        MAX(CASE WHEN DAY(asistencia.fecha) = 26 THEN asistencia.estado ELSE '' END) AS dia_26,
        MAX(CASE WHEN DAY(asistencia.fecha) = 27 THEN asistencia.estado ELSE '' END) AS dia_27,
        MAX(CASE WHEN DAY(asistencia.fecha) = 28 THEN asistencia.estado ELSE '' END) AS dia_28,
        MAX(CASE WHEN DAY(asistencia.fecha) = 29 THEN asistencia.estado ELSE '' END) AS dia_29,
        MAX(CASE WHEN DAY(asistencia.fecha) = 30 THEN asistencia.estado ELSE '' END) AS dia_30,
        MAX(CASE WHEN DAY(asistencia.fecha) = 31 THEN asistencia.estado ELSE '' END) AS dia_31
    FROM
        matricula
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
    WHERE
        aulas.Id_aula = ID_AULA AND
        asistencia.mes = MES AND
				`año_escolar`.`Id_año_escolar`=AÑO
				
    GROUP BY
        alumnos.Id_alumno,
        alumnos.alum_dni,
        Estudiante;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_TOTALES_DIA_ESTUDIANTE` (IN `ID` INT, IN `AÑO` INT, IN `MES` VARCHAR(18), IN `ID_AULA` INT)   BEGIN
    SELECT
        alumnos.Id_alumno,
        alumnos.alum_dni AS DNI,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        -- Días del 1 al 31
        MAX(CASE WHEN DAY(asistencia.fecha) = 1 THEN asistencia.estado ELSE '' END) AS dia_1,
        MAX(CASE WHEN DAY(asistencia.fecha) = 2 THEN asistencia.estado ELSE '' END) AS dia_2,
        MAX(CASE WHEN DAY(asistencia.fecha) = 3 THEN asistencia.estado ELSE '' END) AS dia_3,
        MAX(CASE WHEN DAY(asistencia.fecha) = 4 THEN asistencia.estado ELSE '' END) AS dia_4,
        MAX(CASE WHEN DAY(asistencia.fecha) = 5 THEN asistencia.estado ELSE '' END) AS dia_5,
        MAX(CASE WHEN DAY(asistencia.fecha) = 6 THEN asistencia.estado ELSE '' END) AS dia_6,
        MAX(CASE WHEN DAY(asistencia.fecha) = 7 THEN asistencia.estado ELSE '' END) AS dia_7,
        MAX(CASE WHEN DAY(asistencia.fecha) = 8 THEN asistencia.estado ELSE '' END) AS dia_8,
        MAX(CASE WHEN DAY(asistencia.fecha) = 9 THEN asistencia.estado ELSE '' END) AS dia_9,
        MAX(CASE WHEN DAY(asistencia.fecha) = 10 THEN asistencia.estado ELSE '' END) AS dia_10,
        MAX(CASE WHEN DAY(asistencia.fecha) = 11 THEN asistencia.estado ELSE '' END) AS dia_11,
        MAX(CASE WHEN DAY(asistencia.fecha) = 12 THEN asistencia.estado ELSE '' END) AS dia_12,
        MAX(CASE WHEN DAY(asistencia.fecha) = 13 THEN asistencia.estado ELSE '' END) AS dia_13,
        MAX(CASE WHEN DAY(asistencia.fecha) = 14 THEN asistencia.estado ELSE '' END) AS dia_14,
        MAX(CASE WHEN DAY(asistencia.fecha) = 15 THEN asistencia.estado ELSE '' END) AS dia_15,
        MAX(CASE WHEN DAY(asistencia.fecha) = 16 THEN asistencia.estado ELSE '' END) AS dia_16,
        MAX(CASE WHEN DAY(asistencia.fecha) = 17 THEN asistencia.estado ELSE '' END) AS dia_17,
        MAX(CASE WHEN DAY(asistencia.fecha) = 18 THEN asistencia.estado ELSE '' END) AS dia_18,
        MAX(CASE WHEN DAY(asistencia.fecha) = 19 THEN asistencia.estado ELSE '' END) AS dia_19,
        MAX(CASE WHEN DAY(asistencia.fecha) = 20 THEN asistencia.estado ELSE '' END) AS dia_20,
        MAX(CASE WHEN DAY(asistencia.fecha) = 21 THEN asistencia.estado ELSE '' END) AS dia_21,
        MAX(CASE WHEN DAY(asistencia.fecha) = 22 THEN asistencia.estado ELSE '' END) AS dia_22,
        MAX(CASE WHEN DAY(asistencia.fecha) = 23 THEN asistencia.estado ELSE '' END) AS dia_23,
        MAX(CASE WHEN DAY(asistencia.fecha) = 24 THEN asistencia.estado ELSE '' END) AS dia_24,
        MAX(CASE WHEN DAY(asistencia.fecha) = 25 THEN asistencia.estado ELSE '' END) AS dia_25,
        MAX(CASE WHEN DAY(asistencia.fecha) = 26 THEN asistencia.estado ELSE '' END) AS dia_26,
        MAX(CASE WHEN DAY(asistencia.fecha) = 27 THEN asistencia.estado ELSE '' END) AS dia_27,
        MAX(CASE WHEN DAY(asistencia.fecha) = 28 THEN asistencia.estado ELSE '' END) AS dia_28,
        MAX(CASE WHEN DAY(asistencia.fecha) = 29 THEN asistencia.estado ELSE '' END) AS dia_29,
        MAX(CASE WHEN DAY(asistencia.fecha) = 30 THEN asistencia.estado ELSE '' END) AS dia_30,
        MAX(CASE WHEN DAY(asistencia.fecha) = 31 THEN asistencia.estado ELSE '' END) AS dia_31
    FROM
        matricula
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
    WHERE
        matricula.usu_id=id AND
        aulas.Id_aula = ID_AULA AND
        asistencia.mes = MES AND
				`año_escolar`.`Id_año_escolar`=AÑO
    GROUP BY
        alumnos.Id_alumno,
        alumnos.alum_dni,
        Estudiante;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ALUMNOS_TOTALES_ESTUDIANTE` (IN `ID` INT, IN `AÑO` INT, IN `MES` VARCHAR(18), IN `ID_AULA` INT)   BEGIN
    SELECT
        alumnos.Id_alumno,
        alumnos.alum_dni AS DNI,
        CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) AS Estudiante,
        SUM(CASE WHEN asistencia.estado = 'PRESENTE' THEN 1 ELSE 0 END) AS total_asistencia_presente,
        SUM(CASE WHEN asistencia.estado = 'TARDE' THEN 1 ELSE 0 END) AS total_asistencia_tarde,
        SUM(CASE WHEN asistencia.estado = 'AUSENTE' THEN 1 ELSE 0 END) AS total_asistencia_ausente,
        SUM(CASE WHEN asistencia.estado = 'JUSTIFICADO' THEN 1 ELSE 0 END) AS total_asistencia_justificado,
        SUM(CASE 
            WHEN asistencia.estado IN ('PRESENTE', 'TARDE', 'AUSENTE', 'JUSTIFICADO') 
            THEN 1 
            ELSE 0 

        END) AS total_general
    FROM
        matricula
    INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
    INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
    INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
    WHERE
        matricula.usu_id=ID AND
        aulas.Id_aula = ID_AULA AND
        asistencia.mes = MES AND
				`año_escolar`.`Id_año_escolar`=AÑO

    GROUP BY
        alumnos.Id_alumno,
        alumnos.alum_dni,
        Estudiante;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AÑOS` ()   SELECT
	`año_escolar`.`Id_año_escolar`, 
	`año_escolar`.`año_escolar`, 
	`año_escolar`.`Nombre_año`, 
	`año_escolar`.fecha_inicio,
		date_format(fecha_inicio, "%d-%m-%Y") as fecha_ini, 
	`año_escolar`.fecha_fin,
		date_format(fecha_fin, "%d-%m-%Y") as fecha_fi, 
	`año_escolar`.descripcion, 
	`año_escolar`.estado, 
	`año_escolar`.created_at, 
	`año_escolar`.updated_at
FROM
	`año_escolar`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AREA` ()   SELECT
	area.area_cod, 
	area.area_nombre,
	date_format(area_fecha_registro, "%d-%m-%Y - %H:%i:%s") as fecha_formateada,
	area.area_fecha_registro, 
	area.area_estado
FROM
	area
	ORDER BY area_nombre asc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ASIGNATURAS` ()   SELECT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignaturas.estado, 
	asignaturas.created_at,
	date_format(asignaturas.created_at, "%d-%m-%Y") as fecha_formateada,	
	asignaturas.updated_at, 
	aulas.Grado, 
	seccion.seccion_nombre,
  CONCAT_WS(' ',aulas.Grado,'-',seccion.seccion_nombre) as	GradoSECCION,
	nivel_academico.Nivel_academico,
  nivel_academico.Id_nivel
FROM
	asignaturas
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ASIGNATURA_FILTRO` (IN `GRADO` INT)   SELECT
	asignaturas.Id_asignatura, 
	asignaturas.nombre_asig, 
	asignaturas.Id_grado, 
	asignaturas.observaciones, 
	asignaturas.estado, 
	asignaturas.created_at,
	date_format(asignaturas.created_at, "%d-%m-%Y") as fecha_formateada,	
	asignaturas.updated_at, 
	aulas.Grado, 
	seccion.seccion_nombre,
  CONCAT_WS(' ',aulas.Grado,'-',seccion.seccion_nombre) as	GradoSECCION,
	nivel_academico.Nivel_academico,
  nivel_academico.Id_nivel
FROM
	asignaturas
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	seccion
	ON 
		aulas.id_seccion = seccion.seccion_id
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
  WHERE aulas.Id_aula=GRADO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ASISTENCIA` ()   SELECT DISTINCT
aulas.Id_aula,
aulas.Grado,
seccion.seccion_nombre,
nivel_academico.Id_nivel,
nivel_academico.Nivel_academico,
`año_escolar`.`año_escolar`,
matricula.`id_año`,
asistencia.fecha,
	date_format(asistencia.fecha, "%d-%m-%Y") as fecha_formateada

FROM
matricula
INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
ORDER BY fecha DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ASISTENCIAS_FECHAS` (IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT DISTINCT
aulas.Id_aula,
aulas.Grado,
seccion.seccion_nombre,
nivel_academico.Id_nivel,
nivel_academico.Nivel_academico,
`año_escolar`.`año_escolar`,
matricula.`id_año`,
asistencia.fecha,
	date_format(asistencia.fecha, "%d-%m-%Y") as fecha_formateada

FROM
matricula
INNER JOIN asistencia ON asistencia.id_matricula = matricula.id_matricula
INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN seccion ON aulas.id_seccion = seccion.seccion_id
INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
WHERE asistencia.fecha BETWEEN FECHAINI AND FECHAFIN
ORDER BY fecha DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ATENCIONES_ENFERMERIA_FILTRO` (IN `IDGRADO` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	alumnos.Edad, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ATENCIONES_PSDICOLOGICAS_FILTRO` (IN `IDGRADO` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	alumnos.Edad, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ATENCION_ENFERME` ()   SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	alumnos.Edad, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ATENCION_PSICO` ()   SELECT
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,  
	alumnos.alum_sexo, 
	alumnos.alum_fechanacimiento, 
	alumnos.Edad, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AULAS` ()   SELECT
	aulas.Id_aula, 
	aulas.Grado, 
	aulas.id_nivel_academico, 
	aulas.id_seccion, 
	aulas.descripcion, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AULAS_FILTRO` (IN `ID` INT)   SELECT
	aulas.Id_aula, 
	aulas.Grado, 
	aulas.id_nivel_academico, 
	aulas.id_seccion, 
	aulas.descripcion, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AULAS_HORAS` ()   SELECT DISTINCT
	horas_aula.`id_año_academico`, 
	horas_aula.id_aula, 
	horas_aula.turno, 
    horas_aula.estado, 
 
	`año_escolar`.`año_escolar`, 
	aulas.Grado, 
	nivel_academico.Nivel_academico
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
    order by Grado,`año_escolar` desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AULAS_HORAS_FILTRO` (IN `AÑO` INT, IN `GRADO` INT)   SELECT DISTINCT
	horas_aula.`id_año_academico`, 
	horas_aula.id_aula, 
	horas_aula.turno, 
    horas_aula.estado, 
 
	`año_escolar`.`año_escolar`, 
	aulas.Grado, 
	nivel_academico.Nivel_academico
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
  WHERE `año_escolar`.`Id_año_escolar`=AÑO AND aulas.Id_aula=GRADO
    order by Grado,`año_escolar` desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMPONENTES` ()   SELECT DISTINCT
    seccion.seccion_nombre,
    aulas.Grado,
    aulas.Id_aula,
    nivel_academico.Nivel_academico,
		asignaturas.Id_asignatura,
    asignaturas.nombre_asig,
    detalle_asignatura_docente.Id_asig_docente,
    detalle_asignatura_docente.Id_asignatura,
    detalle_asignatura_docente.Id_detalle_asig_docente,
    criterios.id_detalle_asignatura,
		criterios.estado
FROM
    seccion
INNER JOIN aulas ON aulas.id_seccion = seccion.seccion_id -- Cambiado de `seccion.seccion_id` a `seccion.Id_seccion`
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN `año_escolar` ON 1=1 -- Reemplaza `ON 1=1` con la condición correcta si es necesario
INNER JOIN asignaturas ON asignaturas.Id_grado = aulas.Id_aula
INNER JOIN detalle_asignatura_docente ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
INNER JOIN criterios ON criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
ORDER BY criterios.created_at asc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMPONENTES_CURSO` (IN `ID` INT)   BEGIN
    SELECT
        criterios.id_criterio,
        criterios.id_detalle_asignatura,
        criterios.competencias,
        criterios.`descripción_observa`,
        criterios.estado,
        criterios.created_at,
					date_format(criterios.created_at, "%d-%m-%Y") as fecha_formateada,
        criterios.updated_at,
        aulas.Grado,
        asignaturas.nombre_asig

    FROM
        criterios
    INNER JOIN detalle_asignatura_docente ON criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
    INNER JOIN asignaturas ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
    INNER JOIN aulas ON asignaturas.Id_grado = aulas.Id_aula
    WHERE
        criterios.id_detalle_asignatura = ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMPONENTES_FILTRO` (IN `AULA` INT)   SELECT DISTINCT
    seccion.seccion_nombre,
    aulas.Grado,
    aulas.Id_aula,
    nivel_academico.Nivel_academico,
		asignaturas.Id_asignatura,
    asignaturas.nombre_asig,
    detalle_asignatura_docente.Id_asig_docente,
    detalle_asignatura_docente.Id_asignatura,
    detalle_asignatura_docente.Id_detalle_asig_docente,
    criterios.id_detalle_asignatura,
		criterios.estado
FROM
    seccion
INNER JOIN aulas ON aulas.id_seccion = seccion.seccion_id -- Cambiado de `seccion.seccion_id` a `seccion.Id_seccion`
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
INNER JOIN `año_escolar` ON 1=1 -- Reemplaza `ON 1=1` con la condición correcta si es necesario
INNER JOIN asignaturas ON asignaturas.Id_grado = aulas.Id_aula
INNER JOIN detalle_asignatura_docente ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
INNER JOIN criterios ON criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
WHERE Id_aula= AULA
ORDER BY criterios.created_at asc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMUNICADOS` ()   SELECT
	comunicados.id_comunicado, 
	comunicados.tipo, 
	comunicados.id_aula, 
	comunicados.descripcion, 
	comunicados.titulo, 
	comunicados.imagen, 
	comunicados.estado, 
	comunicados.id_usuario, 
	comunicados.created_at, 
	date_format(comunicados.created_at, "%d-%m-%Y") AS fecha_formateada, 
	comunicados.updated_at, 
	aulas.Grado
FROM
	comunicados
	INNER JOIN
	aulas
	ON 
		comunicados.id_aula = aulas.Id_aula
ORDER BY
	comunicados.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMUNICADOS2` ()   SELECT
	comunicados.id_comunicado, 
	comunicados.tipo, 
	comunicados.id_aula, 
	comunicados.descripcion, 
	comunicados.titulo, 
	comunicados.imagen, 
	comunicados.estado, 
	comunicados.id_usuario, 
	comunicados.created_at, 
	date_format(comunicados.created_at, "%d-%m-%Y") AS fecha_formateada, 
	comunicados.updated_at, 
	aulas.Grado
FROM
	comunicados
	INNER JOIN
	aulas
	ON 
		comunicados.id_aula = aulas.Id_aula
ORDER BY
	comunicados.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMUNICADOS_FILTRO` (IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT
	comunicados.id_comunicado, 
	comunicados.tipo, 
	comunicados.id_aula, 
	comunicados.descripcion, 
	comunicados.titulo, 
	comunicados.imagen, 
	comunicados.estado, 
	comunicados.id_usuario, 
	comunicados.created_at, 
	date_format(comunicados.created_at, "%d-%m-%Y") AS fecha_formateada, 
	comunicados.updated_at, 
	aulas.Grado
FROM
	comunicados
	INNER JOIN
	aulas
	ON 
		comunicados.id_aula = aulas.Id_aula
  WHERE comunicados.created_at BETWEEN FECHAINI AND FECHAFIN
ORDER BY
	comunicados.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CRITERIOS_NOTA` (IN `NIVEL` VARCHAR(255), IN `AULA` VARCHAR(22))   SELECT
	criterios.id_criterio, 
	criterios.id_detalle_asignatura, 
	criterios.competencias, 
	criterios.`descripción_observa`, 
	criterios.estado, 
	criterios.created_at, 
	date_format(criterios.created_at, "%d-%m-%Y") AS fecha_formateada, 
	criterios.updated_at, 
	aulas.Grado, 
	asignaturas.nombre_asig, 
	nivel_academico.Nivel_academico
FROM
	criterios
	INNER JOIN
	detalle_asignatura_docente
	ON 
		criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
WHERE
	aulas.Grado = AULA AND Nivel_academico=NIVEL$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CRITERIOS_NOTA_DOCENTE` (IN `NIVEL` VARCHAR(255), IN `AULA` VARCHAR(22), IN `ID` INT)   SELECT
	criterios.id_criterio, 
	criterios.id_detalle_asignatura, 
	criterios.competencias, 
	criterios.`descripción_observa`, 
	criterios.estado, 
	criterios.created_at, 
	date_format(criterios.created_at, "%d-%m-%Y") AS fecha_formateada, 
	criterios.updated_at, 
	aulas.Grado, 
	asignaturas.nombre_asig, 
	nivel_academico.Nivel_academico, 
	usuario.usu_id
FROM
	criterios
	INNER JOIN
	detalle_asignatura_docente
	ON 
		criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
WHERE
	aulas.Grado = AULA AND Nivel_academico=NIVEL AND usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CRITERIOS_NOTA_MOSTRAR` (IN `MATRI` INT, IN `PERIODO` INT)   SELECT
	criterios.id_criterio, 
	criterios.id_detalle_asignatura, 
	criterios.competencias, 
	criterios.`descripción_observa`, 
	criterios.estado, 
	criterios.created_at, 
	date_format(criterios.created_at, "%d-%m-%Y") AS fecha_formateada, 
	criterios.updated_at, 
	aulas.Grado, 
	asignaturas.nombre_asig, 
	nivel_academico.Nivel_academico, 
	notas.id_nota_bole, 
	notas.id_matricula, 
	notas.id_bimestre, 
	notas.id_criterio, 
	notas.nota, 
	notas.conclusiones
FROM
	criterios
	INNER JOIN
	detalle_asignatura_docente
	ON 
		criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	notas
	ON 
		criterios.id_criterio = notas.id_criterio
  WHERE notas.id_matricula=MATRI AND notas.id_bimestre = PERIODO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_ESTUDIANTE` (IN `MATRI` INT, IN `PERIODO` INT, IN `IDUSU` INT)   SELECT
	criterios.id_criterio, 
	criterios.id_detalle_asignatura, 
	criterios.competencias, 
	criterios.`descripción_observa`, 
	criterios.estado, 
	date_format(criterios.created_at, "%d-%m-%Y") AS fecha_formateada, 
	criterios.updated_at, 
	aulas.Grado, 
	asignaturas.nombre_asig, 
	nivel_academico.Nivel_academico, 
	notas.id_nota_bole, 
	notas.id_matricula, 
	notas.id_bimestre, 
	notas.id_criterio, 
	notas.nota, 
	notas.conclusiones, 
	usuario.usu_id
FROM
	criterios
	INNER JOIN
	detalle_asignatura_docente
	ON 
		criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	notas
	ON 
		criterios.id_criterio = notas.id_criterio
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	matricula
	ON 
		aulas.Id_aula = matricula.id_aula AND
		notas.id_matricula = matricula.id_matricula
	INNER JOIN
	usuario
	ON 
		matricula.usu_id = usuario.usu_id
  WHERE notas.id_matricula=MATRI AND notas.id_bimestre = PERIODO AND notas.id_bimestre=PERIODO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_PADRES` (IN `MATRI` INT, IN `PERIODO` INT)   SELECT
	notas_padre.id_nota_papa, 
	notas_padre.id_matricula, 
	notas_padre.id_bimestre, 
	notas_padre.criterio, 
	notas_padre.nota, 
	notas_padre.creared_at, 
	notas_padre.updated_at
FROM
	notas_padre
    WHERE notas_padre.id_matricula=MATRI AND notas_padre.id_bimestre = PERIODO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_PROFESOR` (IN `MATRI` INT, IN `PERIODO` INT, IN `IDPRO` INT)   SELECT
	criterios.id_criterio, 
	criterios.id_detalle_asignatura, 
	criterios.competencias, 
	criterios.`descripción_observa`, 
	criterios.estado, 
	criterios.created_at, 
	date_format(criterios.created_at, "%d-%m-%Y") AS fecha_formateada, 
	criterios.updated_at, 
	aulas.Grado, 
	asignaturas.nombre_asig, 
	nivel_academico.Nivel_academico, 
	notas.id_nota_bole, 
	notas.id_matricula, 
	notas.id_bimestre, 
	notas.id_criterio, 
	notas.nota, 
	notas.conclusiones, 
	usuario.usu_id
FROM
	criterios
	INNER JOIN
	detalle_asignatura_docente
	ON 
		criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
	INNER JOIN
	aulas
	ON 
		asignaturas.Id_grado = aulas.Id_aula
	INNER JOIN
	nivel_academico
	ON 
		aulas.id_nivel_academico = nivel_academico.Id_nivel
	INNER JOIN
	notas
	ON 
		criterios.id_criterio = notas.id_criterio
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	docentes
	ON 
		asignatura_docente.Id_docente = docentes.Id_docente
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
  WHERE notas.id_matricula=MATRI AND notas.id_bimestre = PERIODO AND usuario.usu_id=IDPRO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_CURSOS_DOCENTE` (IN `ID` INT)   SELECT DISTINCT
    asignatura_docente.Id_asigdocente,
    asignatura_docente.Id_docente,
    asignatura_docente.Total_cursos,
    asignatura_docente.created_at,
    DATE_FORMAT(asignatura_docente.created_at, "%d-%m-%Y") AS fecha_formateada,
    asignatura_docente.updated_at,
    docentes.docente_dni,
    docentes.docente_nombre,
    docentes.docente_apelli,
    CONCAT_WS(' ', docentes.docente_nombre, docentes.docente_apelli) AS Docente,
    asignaturas.nombre_asig,
    asignaturas.Id_grado,
		    asignaturas.observaciones,
    aulas.Grado,
    nivel_academico.Nivel_academico,
    detalle_asignatura_docente.Id_detalle_asig_docente,
    detalle_asignatura_docente.Id_asig_docente,
    detalle_asignatura_docente.Id_asignatura,
    detalle_asignatura_docente.created_at,
    detalle_asignatura_docente.updated_at
FROM
    asignatura_docente
    INNER JOIN docentes ON asignatura_docente.Id_docente = docentes.Id_docente
    INNER JOIN detalle_asignatura_docente ON asignatura_docente.Id_asigdocente = detalle_asignatura_docente.Id_asig_docente
    INNER JOIN asignaturas ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
    INNER JOIN aulas ON asignaturas.Id_grado = aulas.Id_aula
    INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
WHERE
    detalle_asignatura_docente.Id_asig_docente = ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_DIFERENCIA` ()   BEGIN
    DECLARE fecha_inicio DATE;
    DECLARE fecha_fin DATE;
    DECLARE totalgasto DECIMAL(10,2);
    DECLARE totalingresos DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);

    -- Definir las fechas de inicio y fin como la fecha actual
    SET @fecha_inicio := CURDATE();
    SET @fecha_fin := CURDATE();

    -- Calcular el total de gastos
    SET @totalgasto := (
        SELECT IFNULL(SUM(egresos.monto), 0) 
        FROM egresos 
        WHERE egresos.created_at BETWEEN @fecha_inicio AND @fecha_fin AND estado='VALIDO'
    );

    -- Calcular el total de ingresos
    SET @totalingresos := (
        SELECT IFNULL(SUM(ingresos.monto), 0) 
        FROM ingresos 
        WHERE ingresos.created_at BETWEEN @fecha_inicio AND @fecha_fin AND estado='VALIDO'
    );

    -- Calcular la diferencia entre ingresos y gastos
    SET @total := @totalingresos - @totalgasto;

    -- Devolver el resultado en un solo registro
    SELECT 
        DATE_FORMAT(@fecha_inicio, "%d-%m-%Y") AS FechaInicial,
        DATE_FORMAT(@fecha_fin, "%d-%m-%Y") AS FechaFinal,
        CONCAT_WS(' ', 'S/.', @totalingresos) AS totalingresos,
        CONCAT_WS(' ', 'S/.', @totalgasto) AS totalgasto,
        CONCAT_WS(' ', 'S/.', @total) AS Diferencia;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_DIFERENCIA_FILTRO` (IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   BEGIN
    DECLARE totalgasto DECIMAL(10,2);
    DECLARE totalingresos DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);

    -- Calcular el total de gastos
    SET totalgasto := (
        SELECT IFNULL(SUM(egresos.monto), 0) 
        FROM egresos 
        WHERE egresos.created_at BETWEEN FECHAINI AND FECHAFIN AND estado='VALIDO'
    );

    -- Calcular el total de ingresos
    SET totalingresos := (
        SELECT IFNULL(SUM(ingresos.monto), 0) 
        FROM ingresos 
        WHERE ingresos.created_at BETWEEN FECHAINI AND FECHAFIN AND estado='VALIDO'
    );

    -- Calcular la diferencia entre ingresos y gastos
    SET total := totalingresos - totalgasto;

    -- Devolver el resultado en un solo registro
    SELECT 
        DATE_FORMAT(FECHAINI, "%d-%m-%Y") AS FechaInicial,
        DATE_FORMAT(FECHAFIN, "%d-%m-%Y") AS FechaFinal,
        CONCAT_WS(' ', 'S/.', totalingresos) AS totalingresos,
        CONCAT_WS(' ', 'S/.', totalgasto) AS totalgasto,
        CONCAT_WS(' ', 'S/.', total) AS Diferencia;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_DOCENTES` ()   SELECT
docentes.Id_docente,
	docentes.docente_dni, 
	docentes.docente_nombre, 
	docentes.docente_apelli, 
	CONCAT_WS(' ',docentes.docente_nombre,docentes.docente_apelli) AS Docente, 
	docentes.especialidad_id, 
	docentes.docente_sexo, 
	docentes.docente_fechanacimiento, 
	date_format(docente_fechanacimiento, "%d-%m-%Y") AS fecha_formateada, 
	docentes.docente_movil, 
	docentes.docente_nro_alterno, 
	docentes.docente_direccion, 
	docentes.docente_estatus, 
	docentes.docente_fotoperfil, 
	docentes.id_asusuario, 
	docentes.created_at, 
	date_format(docentes.created_at, "%d-%m-%Y") AS fecha_formateada2, 
	docentes.updated_at, 
	usuario.usu_id, 
	usuario.usu_usuario, 
	usuario.usu_contra, 
	usuario.usu_estatus,
	usuario.usu_email, 	
	usuario.rol_id, 
	usuario.empresa_id, 
	usuario.created_at, 
	usuario.updated_at, 
	roles.Id_rol, 
	roles.tipo_rol, 
	especialidad.Especialidad, 
	especialidad.Id_especilidad
FROM
	docentes
	INNER JOIN
	usuario
	ON 
		docentes.id_asusuario = usuario.usu_id
	INNER JOIN
	roles
	ON 
		usuario.rol_id = roles.Id_rol
	INNER JOIN
	especialidad
	ON 
		docentes.especialidad_id = especialidad.Id_especilidad$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EGRESOS_DIVERSOS` ()   SELECT
	egresos.id_egresos, 
	egresos.id_indicador, 
	egresos.id_user, 
	egresos.cantidad, 
	egresos.monto, 
	egresos.observacion, 
	egresos.estado, 
	egresos.motivo_anulacion, 
	egresos.fecha_anulacion, 
	egresos.created_at, 
	egresos.updated, 
	indicadores.nombre,
    	date_format(egresos.created_at, "%d-%m-%Y") as fecha_formateada

FROM
	egresos
	INNER JOIN
	indicadores
	ON 
		egresos.id_indicador = indicadores.id_indicadores
WHERE
	egresos.created_at = CURDATE()$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EGRESOS_DIVERSOS_FILTRO` (IN `INDI` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT
	egresos.id_egresos, 
	egresos.id_indicador, 
	egresos.id_user, 
	egresos.cantidad, 
	egresos.monto, 
	egresos.observacion, 
	egresos.estado, 
	egresos.motivo_anulacion, 
	egresos.fecha_anulacion, 
	egresos.created_at, 
	egresos.updated, 
	indicadores.nombre,
    	date_format(egresos.created_at, "%d-%m-%Y") as fecha_formateada

FROM
	egresos
	INNER JOIN
	indicadores
	ON 
		egresos.id_indicador = indicadores.id_indicadores
WHERE
	egresos.id_indicador=INDI OR egresos.created_at BETWEEN FECHAINI AND FECHAFIN$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EMPLEADO` ()   SELECT
	empleado.empleado_id, 
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat, 
	empleado.emple_fechanacimiento, 
	empleado.emple_nrodocumento, 
	empleado.emple_movil, 
	empleado.emple_email, 
	empleado.emple_estatus, 
	empleado.emple_direccion, 
	empleado.empl_fotoperfil,
	CONCAT_WS(' ',emple_nombre,emple_apepat,emple_apemat)as empleado
FROM
	empleado$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EMPRESA` ()   SELECT empresa_id,emp_razon,emp_email,emp_cod,emp_telefono,emp_direccion,emp_logo
FROM empresa$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ESPECIALIDAD` ()   SELECT
	especialidad.Id_especilidad, 
	especialidad.Especialidad, 
	especialidad.Descripcion, 
	especialidad.Estado, 
	especialidad.created_at,
	date_format(created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada, 
	especialidad.updated_at
FROM
	especialidad$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EXAMENES` ()   SELECT
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
	nivel_academico.Nivel_academico
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
ORDER BY
	examen.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EXAMENES_ESTUDIANTES_ID_PENDIENTE` (IN `ID` INT)   SELECT
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
		  WHERE `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW()))) AND matricula.usu_id=ID AND examen.estado='PENDIENTE'



ORDER BY
	examen.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EXAMENES_FILTRO` (IN `GRADO` INT)   SELECT
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
	nivel_academico.Nivel_academico
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
    WHERE aulas.Id_aula=GRADO
ORDER BY
	examen.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EXAMENES_PROFESOR` (IN `AÑO` INT, IN `GRADO` INT, IN `ID` INT)   SELECT
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
		    WHERE `año_escolar`.`Id_año_escolar`=AÑO AND aulas.Id_aula=GRADO AND usuario.usu_id=ID

ORDER BY
	examen.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EXAMENES_PROFESOR_SOLO` (IN `ID` INT)   SELECT
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
WHERE `año_escolar`.`año_escolar` = (SELECT(YEAR(NOW()))) AND usuario.usu_id = ID AND examen.estado="PENDIENTE"

ORDER BY
	examen.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_HORARIOS` ()   SELECT DISTINCT
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
	`año_escolar` = (SELECT(YEAR(NOW())))$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_HORARIOS_FILTRO` (IN `AÑO` INT, IN `GRADO` INT)   SELECT DISTINCT
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
	`año_escolar`.`Id_año_escolar`=AÑO AND aulas.Id_aula=GRADO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_INDICADORES` ()   SELECT
	indicadores.id_indicadores, 
	indicadores.tipo_indicador, 
	indicadores.nombre, 
	indicadores.descripcion, 
	indicadores.estado, 
	indicadores.created_at,
  date_format(indicadores.created_at, "%d-%m-%Y") as fecha_formateada,
 
	indicadores.updated_at
FROM
	indicadores$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_INGRESOS_DIVERSOS` ()   SELECT
	ingresos.id_ingreso, 
	ingresos.id_pago_pension, 
	ingresos.id_indicador, 
	indicadores.tipo_indicador, 
	indicadores.nombre, 
	ingresos.id_user, 
	ingresos.cantidad, 
	ingresos.monto, 
	ingresos.observacion, 
	ingresos.created_at,
  ingresos.estado,
  ingresos.motivo_anulacion,
  ingresos.fecha_anulacion,
  	date_format(ingresos.created_at, "%d-%m-%Y") as fecha_formateada

FROM
	ingresos
	INNER JOIN
	indicadores
	ON 
		ingresos.id_indicador = indicadores.id_indicadores
WHERE ingresos.created_at=CURDATE()$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_INGRESOS_DIVERSOS_FILTRO` (IN `INDI` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   BEGIN
    SELECT
        ingresos.id_ingreso, 
        ingresos.id_pago_pension, 
        ingresos.id_indicador, 
        indicadores.tipo_indicador, 
        indicadores.nombre, 
        ingresos.id_user, 
        ingresos.cantidad, 
        ingresos.monto, 
        ingresos.observacion, 
        ingresos.created_at,
        ingresos.estado,
        ingresos.motivo_anulacion,
        ingresos.fecha_anulacion,
        date_format(ingresos.created_at, "%d-%m-%Y") as fecha_formateada
    FROM
        ingresos
    INNER JOIN
        indicadores ON ingresos.id_indicador = indicadores.id_indicadores
    WHERE 
        -- Filtro por indicador si se proporciona
        (INDI IS NOT NULL AND ingresos.id_indicador = INDI)
        -- Filtro por rango de fechas si se proporcionan ambas fechas
        OR (FECHAINI IS NOT NULL AND FECHAFIN IS NOT NULL AND ingresos.created_at BETWEEN FECHAINI AND FECHAFIN);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_INGRESOS_PENSIONES` ()   SELECT
	ingresos.id_ingreso, 
	ingresos.id_pago_pension, 
	ingresos.id_indicador, 
	indicadores.tipo_indicador, 
	indicadores.nombre, 
	ingresos.id_user, 
	ingresos.cantidad, 
	ingresos.monto, 
	ingresos.observacion, 
	ingresos.created_at, 
	ingresos.estado, 
	ingresos.motivo_anulacion, 
	ingresos.fecha_anulacion, 
	date_format(ingresos.created_at, "%d-%m-%Y") AS fecha_formateada, 
	pensiones.mes, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
  CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,
  CONCAT_WS(' - ',observacion,mes)AS PAGADO
FROM
	ingresos
	INNER JOIN
	indicadores
	ON 
		ingresos.id_indicador = indicadores.id_indicadores
	left JOIN
	pago_pensiones
	ON 
		ingresos.id_pago_pension = pago_pensiones.id_pago_pension
	left JOIN
	pensiones
	ON 
		pago_pensiones.id_pension = pensiones.id_pensiones
	INNER JOIN
	matricula
	ON 
		pago_pensiones.id_matri = matricula.id_matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
WHERE
	ingresos.created_at = CURDATE() AND
	ingresos.id_indicador = 1$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_INGRESOS_PENSIONES_FILTRO` (IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT
	ingresos.id_ingreso, 
	ingresos.id_pago_pension, 
	ingresos.id_indicador, 
	indicadores.tipo_indicador, 
	indicadores.nombre, 
	ingresos.id_user, 
	ingresos.cantidad, 
	ingresos.monto, 
	ingresos.observacion, 
	ingresos.created_at, 
	ingresos.estado, 
	ingresos.motivo_anulacion, 
	ingresos.fecha_anulacion, 
	date_format(ingresos.created_at, "%d-%m-%Y") AS fecha_formateada, 
	pensiones.mes, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
  CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante,
  CONCAT_WS(' - ',observacion,mes)AS PAGADO
FROM
	ingresos
	INNER JOIN
	indicadores
	ON 
		ingresos.id_indicador = indicadores.id_indicadores
	INNER JOIN
	pago_pensiones
	ON 
		ingresos.id_pago_pension = pago_pensiones.id_pago_pension
	INNER JOIN
	pensiones
	ON 
		pago_pensiones.id_pension = pensiones.id_pensiones
	INNER JOIN
	matricula
	ON 
		pago_pensiones.id_matri = matricula.id_matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
WHERE
	ingresos.created_at BETWEEN FECHAINI AND FECHAFIN AND
	ingresos.id_indicador = 1$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_MATRICULADOS` ()   SELECT
matricula.id_matricula,
matricula.id_alumno,
matricula.id_aula,
matricula.`id_año`,
matricula.pago_admi,
matricula.pago_alu_nuevo,
matricula.pago_matricula,


CONCAT_WS('','S/ ',pago_matricula) AS MATRICULA,

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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_MATRICULADOS_FILTRO` (IN `AÑO` INT, IN `AULA` INT)   SELECT
matricula.id_matricula,
matricula.id_alumno,
matricula.id_aula,
matricula.`id_año`,
matricula.pago_admi,
matricula.pago_alu_nuevo,
matricula.pago_matricula,


CONCAT_WS('','S/ ',pago_matricula) AS MATRICULA,

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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NIVEL_ACADEMICO` ()   SELECT
	nivel_academico.Id_nivel, 
	nivel_academico.descripcion, 
	nivel_academico.Nivel_academico, 
	nivel_academico.estado, 
	nivel_academico.create_at,
	date_format(create_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada,  
	nivel_academico.updated_at
FROM
	nivel_academico$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTAS_AULA_AÑO` (IN `IDAÑO` INT, IN `AULA` INT)   BEGIN
    -- Usar una variable de sesión para contar el número de notas por matrícula
    DECLARE CONTADOR INT;

    -- Seleccionar las notas y contar cuántas hay por cada matrícula
    SELECT
        matricula.id_matricula, 
        matricula.id_alumno, 
        matricula.id_aula, 
        matricula.`id_año`, 
        matricula.pago_admi, 
        matricula.pago_alu_nuevo, 
        matricula.pago_matricula, 
        CONCAT_WS('','S/ ',pago_matricula) AS MATRICULA, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTAS_AULA_AÑO_ALUMNOS` (IN `IDAÑO` INT, IN `ID` INT)   BEGIN
    -- Usar una variable de sesión para contar el número de notas por matrícula
    DECLARE CONTADOR INT;

    -- Seleccionar las notas y contar cuántas hay por cada matrícula
    SELECT
	matricula.id_matricula, 
	matricula.id_alumno, 
	matricula.id_aula, 
	matricula.`id_año`, 
	matricula.pago_admi, 
	matricula.pago_alu_nuevo, 
	matricula.pago_matricula, 
	CONCAT_WS('','S/ ',pago_matricula) AS MATRICULA, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTAS_AULA_AÑO_PROFESOR` (IN `IDAÑO` INT, IN `AULA` INT)   BEGIN
    -- Usar una variable de sesión para contar el número de notas por matrícula
    DECLARE CONTADOR INT;

    -- Seleccionar las notas y contar cuántas hay por cada matrícula
    SELECT
        matricula.id_matricula, 
        matricula.id_alumno, 
        matricula.id_aula, 
        matricula.`id_año`, 
        matricula.pago_admi, 
        matricula.pago_alu_nuevo, 
        matricula.pago_matricula, 
        CONCAT_WS('','S/ ',pago_matricula) AS MATRICULA, 
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTIFICACION_COMUNICADO` ()   SELECT
	comunicados.id_comunicado, 
	comunicados.titulo, 
	comunicados.descripcion, 
	comunicados.enlace,
	date_format(fecha_registro, "%d-%m-%Y") as fecha_formateada,
	comunicados.fecha_registro, 
	comunicados.id_usuario, 
	comunicados.estado
FROM
	comunicados
	where estado='NUEVO' and fecha_registro=CURDATE()$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTIFICACION_TRAMITE` (IN `IDAREA` INT)   SELECT
	documento.documento_id, 
	documento.doc_dniremitente, 
	CONCAT_WS(' ',documento.doc_nombreremitente,documento.doc_apepatremitente,documento.doc_apematremitente) AS REMITENTE, 
	documento.doc_nombreremitente, 
	documento.doc_apepatremitente, 
	documento.doc_apematremitente, 
	documento.tipodocumento_id, 
	tipo_documento.tipodo_descripcion, 
	documento.doc_estatus, 
	documento.doc_nrodocumento, 
	documento.doc_celularremitente, 
	documento.doc_emailremitente, 
	documento.doc_direccionremitente, 
	documento.doc_representacion, 
	documento.doc_ruc, 
	documento.doc_empresa, 
	documento.doc_folio, 
	documento.doc_archivo, 
	documento.doc_asunto, 
	documento.doc_fecharegistro,
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s") as fecha_formateada,
	documento.area_origen, 
	documento.area_destino, 
	documento.area_id, 
	origen.area_nombre as origen, 
	destino.area_nombre as destino
FROM
	documento
	INNER JOIN
	tipo_documento
	ON 
		documento.tipodocumento_id = tipo_documento.tipodocumento_id
	INNER JOIN
	area AS origen
	ON 
		documento.area_origen = origen.area_cod
	INNER JOIN
	area AS destino
	ON 
		documento.area_destino = destino.area_cod


		WHERE area_destino=IDAREA AND doc_estatus="PENDIENTE"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PAGOS` (IN `ID` INT)   BEGIN
    SELECT
	pensiones.id_nivel_academico, 
	pensiones.mes, 
	pago_pensiones.id_pago_pension, 
	pago_pensiones.id_matri, 
	pago_pensiones.concepto, 
	pago_pensiones.id_pension, 
	pago_pensiones.fecha_pago,
			date_format(fecha_pago, "%d-%m-%Y") as fecha_formateada2,  
	pago_pensiones.sub_total, 
	pago_pensiones.created_at,
  pago_pensiones.motivo_edicion, 
	matricula.id_alumno, 
	alumnos.Id_alumno, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat,
	alumnos.alum_apemat,
			CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante

FROM
	pago_pensiones
	LEFT JOIN
	pensiones
	ON 
		pensiones.id_pensiones = pago_pensiones.id_pension
	INNER JOIN
	matricula
	ON 
		pago_pensiones.id_matri = matricula.id_matricula
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
WHERE
	pago_pensiones.id_matri = ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PAGOS_FILTRO` (IN `AÑO` INT, IN `AULA` INT)   BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        CONCAT_WS('', 'S/ ', pago_matricula) AS MATRICULA,
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
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula
    WHERE matricula.`id_año`=AÑO AND matricula.id_aula=AULA
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PAGO_PENSION` ()   BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        CONCAT_WS('', 'S/ ', pago_matricula) AS MATRICULA,
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
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula

		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PAGO_PENSION_ID` (IN `ID` INT)   BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        CONCAT_WS('', 'S/ ', pago_matricula) AS MATRICULA,
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
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula
    WHERE matricula.usu_id=ID AND `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW())))
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PAGO_PENSION_ID_AÑO` (IN `ID` INT, IN `AÑO` INT)   BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        CONCAT_WS('', 'S/ ', pago_matricula) AS MATRICULA,
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
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula
    WHERE matricula.usu_id=ID AND `año_escolar`.`Id_año_escolar`=AÑO
		GROUP BY
    matricula.id_matricula
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PAGO_PENSION_TODO` (IN `ID` INT)   BEGIN
    SELECT
        matricula.id_matricula,
        matricula.id_alumno,
        matricula.id_aula,
        matricula.`id_año`,
        matricula.pago_admi,
        matricula.pago_alu_nuevo,
        matricula.pago_matricula,
        CONCAT_WS('', 'S/ ', pago_matricula) AS MATRICULA,
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
		INNER JOIN pago_pensiones ON pago_pensiones.id_matri = matricula.id_matricula
    WHERE matricula.usu_id=ID
		GROUP BY
    `año_escolar`
		
    ORDER BY
     `año_escolar`.`año_escolar` DESC;
		
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PENSIONES` ()   SELECT
pensiones.id_pensiones,
pensiones.id_nivel_academico,
pensiones.mes,
pensiones.fecha_vencimiento,
date_format(fecha_vencimiento, "%d-%m-%Y") AS fecha_formateada,
pensiones.precio,
CONCAT_WS('','S/ ',precio) AS PRECIO,
pensiones.mora,
CONCAT_WS('','S/ ',mora) AS MORA,
pensiones.created_at,
pensiones.updated_at,
nivel_academico.Nivel_academico
FROM
pensiones
INNER JOIN nivel_academico ON pensiones.id_nivel_academico = nivel_academico.Id_nivel and NOT nivel_academico="TODOS"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PENSIONES_FILTRO` (IN `IDNIVEL` INT)   SELECT
pensiones.id_pensiones,
pensiones.id_nivel_academico,
pensiones.mes,
pensiones.fecha_vencimiento,
date_format(fecha_vencimiento, "%d-%m-%Y") AS fecha_formateada,
pensiones.precio,
CONCAT_WS('','S/ ',precio) AS PRECIO,
pensiones.mora,
CONCAT_WS('','S/ ',mora) AS MORA,
pensiones.created_at,
pensiones.updated_at,
nivel_academico.Nivel_academico
FROM
pensiones
INNER JOIN nivel_academico ON pensiones.id_nivel_academico = nivel_academico.Id_nivel and NOT nivel_academico="TODOS"
WHERE pensiones.id_nivel_academico=IDNIVEL$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PERIODOS` ()   SELECT DISTINCT
`año_escolar`.`año_escolar`,
periodos.tipo_perido,
periodos.`id_año_escolar`
FROM
`año_escolar`
INNER JOIN periodos ON periodos.`id_año_escolar` = `año_escolar`.`Id_año_escolar`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PERIODOS_POR_AÑO` (IN `ID` INT)   SELECT
`año_escolar`.`año_escolar`,
periodos.id_periodo,
periodos.`id_año_escolar`,
periodos.tipo_perido,
periodos.periodos,
periodos.fecha_inicio,
	date_format(periodos.fecha_inicio, "%d-%m-%Y") as fecha_formateada,

periodos.fecha_fin,
	date_format(periodos.fecha_fin, "%d-%m-%Y") as fecha_formateada2,
periodos.estado,

periodos.created_at,
periodos.updated_at
FROM
`año_escolar`
INNER JOIN periodos ON periodos.`id_año_escolar` = `año_escolar`.`Id_año_escolar`
WHERE `año_escolar`.`Id_año_escolar`=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_PERSONAL_ADMIN` ()   SELECT
	personal_admi.personal_adm_id, 
	personal_admi.personal_adm_dni, 
	personal_admi.personal_adm_nombre, 
	personal_admi.personal_adm_apellido,
	CONCAT_WS(' ',personal_admi.personal_adm_nombre,personal_admi.personal_adm_apellido) AS Personal, 
	
	personal_admi.personal_adm_tipo, 
	personal_admi.personal_adm_sexo, 
	personal_admi.personal_adm_fechanacimiento,
	date_format(personal_admi.personal_adm_fechanacimiento, "%d-%m-%Y") AS fecha_formateada, 
	
	personal_admi.personal_adm_movil, 
	personal_admi.personal_adm_nro_alterno, 
	personal_admi.personal_adm_direccion, 
	personal_admi.personal_adm_estatus, 
	personal_admi.personal_adm_fotoperfil, 
	personal_admi.id_ausuario, 
	personal_admi.created_at,
date_format(personal_admi.created_at, "%d-%m-%Y") AS fecha_formateada2, 	
	personal_admi.updated_at, 
		usuario.usu_id, 
	usuario.usu_usuario, 
	usuario.usu_contra, 
	usuario.usu_email, 
	usuario.usu_estatus, 
	usuario.rol_id, 
	roles.tipo_rol
FROM
	personal_admi
	INNER JOIN
	usuario
	ON 
		personal_admi.id_ausuario = usuario.usu_id
	INNER JOIN
	roles
	ON 
		usuario.rol_id = roles.Id_rol$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_ROLES` ()   SELECT
	roles.Id_rol, 
	roles.tipo_rol, 
	roles.descripcion, 
	roles.estado, 
	roles.created_at,
	date_format(created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada,  
	roles.updated_at
FROM
	roles$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_SECCIONES` ()   SELECT
	seccion.seccion_id, 
	seccion.seccion_nombre, 
	seccion.seccion_descripcion, 
	seccion.seccion_estado, 
	seccion.created_at,
	date_format(created_at, "%d-%m-%Y - %H:%i:%s") as fecha_formateada, 
 
	seccion.updated_at
FROM
	seccion$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS` ()   SELECT
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
	aulas.Grado
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
ORDER BY
	tareas.created_at DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_ENVIADAS` (IN `ID` CHAR(12))   SELECT
	detalle_tarea.id_detalle_tarea, 
	detalle_tarea.id_tarea, 
	detalle_tarea.id_matriculado, 
	detalle_tarea.archivo_evnio_tarea, 
	detalle_tarea.calificacion, 
	detalle_tarea.observacion, 
	detalle_tarea.estado, 
	detalle_tarea.fecha_envio,
	date_format(fecha_envio, "%d-%m-%Y - %H:%i:%s") as fecha_formateada2,
	detalle_tarea.created_at, 
	detalle_tarea.updated_at, 
	matricula.id_matricula, 
	alumnos.alum_dni, 
	alumnos.alum_nombre, 
	alumnos.alum_apepat, 
	alumnos.alum_apemat,
	CONCAT_WS(' ',alumnos.alum_nombre,alumnos.alum_apepat,alumnos.alum_apemat) AS Estudiante
FROM
	detalle_tarea
	INNER JOIN
	matricula
	ON 
		detalle_tarea.id_matriculado = matricula.id_matricula
	INNER JOIN
	tareas
	ON 
		detalle_tarea.id_tarea = tareas.id_tarea
	INNER JOIN
	alumnos
	ON 
		matricula.id_alumno = alumnos.Id_alumno
	WHERE detalle_tarea.id_tarea=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_ESTUDIANTES` (IN `AÑO` INT, IN `GRADO` INT, IN `IDCURSO` INT, IN `ID` INT)   SELECT DISTINCT
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
  WHERE `año_escolar`.`Id_año_escolar`=AÑO AND aulas.Id_aula=GRADO AND detalle_asignatura_docente.Id_detalle_asig_docente=IDCURSO AND usuario.usu_id=ID
ORDER BY
	tareas.created_at ASC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_ESTUDIANTES_ID_PENDIENTE` (IN `ID` INT)   SELECT DISTINCT
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
  WHERE `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW()))) AND usuario.usu_id=ID AND detalle_tarea.estado='PENDIENTE'
ORDER BY
	tareas.created_at ASC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_ESTUDIANTES_SOLO` (IN `ID` INT)   SELECT DISTINCT
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
  WHERE `año_escolar`.`año_escolar`=(SELECT(YEAR(NOW()))) AND usuario.usu_id=ID
ORDER BY
	tareas.created_at ASC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_FILTRO` (IN `AULA` INT, IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   SELECT
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
  aulas.Id_aula
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
  WHERE aulas.Id_aula= AULA OR tareas.created_at BETWEEN FECHAINI AND FECHAFIN
ORDER BY
	tareas.created_at ASC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_PROFESOR` (IN `AÑO` INT, IN `GRADO` INT, IN `ID` INT)   SELECT DISTINCT
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
  WHERE `año_escolar`.`Id_año_escolar`=AÑO AND aulas.Id_aula=GRADO AND usuario.usu_id=ID
ORDER BY
	tareas.created_at ASC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TAREAS_PROFESOR_ID` (IN `ID` INT)   SELECT DISTINCT
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
WHERE `año_escolar`.`año_escolar` = (SELECT(YEAR(NOW()))) AND usuario.usu_id = ID AND tareas.estado="PENDIENTE"
ORDER BY
	tareas.created_at ASC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TIPO` (IN `ID` INT)   SELECT
alumnos.Id_alumno,
alumnos.tipo_alum
FROM
alumnos
WHERE Id_alumno=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_ADMINISTRATIVOS` ()   SELECT
	COUNT(personal_admi.personal_adm_id)AS TOTAL
FROM
	personal_admi$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_DOCENTES` ()   SELECT
	COUNT(docentes.Id_docente)AS TOTAL
FROM
	docentes$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_DOC_PENDIENTE` ()   SELECT count(documento_id)as totaldocpen FROM documento where doc_estatus="PENDIENTE"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_EGRESOS_HOY` ()   SELECT
	SUM(egresos.monto)AS TOTAL_GASTO2
FROM
	egresos
WHERE egresos.created_at=CURDATE()$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_EMPLEADO` ()   select count(empleado_id) as total from empleado$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_ENFERMERIA` ()   SELECT
	COUNT(atencion_salud.id_atencion)AS TOTAL_PSICO
FROM
	atencion_salud
WHERE atencion_salud.tipo_atencion='ENFERMERIA'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_ESTUDIANTES` ()   SELECT
	COUNT(alumnos.Id_alumno) AS TOTAL
FROM
	alumnos
	INNER JOIN
	padres
	ON 
		alumnos.Id_alumno = padres.id_alu$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_INGRESOS_HOY` ()   SELECT
	SUM(ingresos.monto)AS TOTAL_GASTO
FROM
	ingresos
WHERE ingresos.created_at=CURDATE()$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_PSICOLOGIA` ()   SELECT
	COUNT(atencion_salud.id_atencion)AS TOTAL_PSICO
FROM
	atencion_salud
WHERE atencion_salud.tipo_atencion='PSICOLOGIA'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_USUARIOS` ()   SELECT
	COUNT(usuario.usu_id)AS TOTAL
FROM
	usuario$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAE_MONTO` (IN `ID` INT)   SELECT
pensiones.id_pensiones,
pensiones.precio,
pensiones.fecha_vencimiento
FROM
pensiones

WHERE id_pensiones=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAE_NIVEL` (IN `ID` INT)   SELECT
aulas.Id_aula,
nivel_academico.Nivel_academico
FROM
aulas
INNER JOIN nivel_academico ON aulas.id_nivel_academico = nivel_academico.Id_nivel
WHERE Id_aula=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_USUARIO` ()   BEGIN
  SELECT DISTINCT
    -- DNI y nombres de docentes, personal administrativo y alumnos
    COALESCE(docentes.docente_dni, personal_admi.personal_adm_dni, alumnos.alum_dni) AS dni,
    COALESCE(docentes.docente_nombre, personal_admi.personal_adm_nombre, alumnos.alum_nombre) AS nombre,
    COALESCE(docentes.docente_apelli, personal_admi.personal_adm_apellido, alumnos.alum_apepat) AS apellido,
    CONCAT_WS(' ', 
      COALESCE(docentes.docente_nombre, personal_admi.personal_adm_nombre, alumnos.alum_nombre),
      COALESCE(docentes.docente_apelli, personal_admi.personal_adm_apellido, alumnos.alum_apepat, alumnos.alum_apemat)
    ) AS nombre_completo,
    
    -- Foto de perfil
    COALESCE(docentes.docente_fotoperfil, personal_admi.personal_adm_fotoperfil, alumnos.alum_fotoperfil) AS foto_perfil,
    
    -- Datos de usuario
    usuario.usu_id, 
    usuario.usu_usuario, 
    usuario.usu_contra, 
    usuario.usu_email, 
    usuario.usu_estatus, 
    usuario.rol_id, 
    usuario.empresa_id,
    DATE_FORMAT(usuario.created_at, "%d-%m-%Y - %H:%i:%s") AS fecha_formateada,	
    usuario.created_at, 
    usuario.updated_at, 
    
    -- Datos de roles
    roles.Id_rol, 
    roles.tipo_rol
    
  FROM
    usuario
  INNER JOIN roles ON usuario.rol_id = roles.Id_rol

  -- Join con docentes, personal administrativo y alumnos
  LEFT JOIN docentes ON usuario.usu_id = docentes.id_asusuario
  LEFT JOIN personal_admi ON usuario.usu_id = personal_admi.id_ausuario
  LEFT JOIN matricula ON usuario.usu_id = matricula.usu_id
  LEFT JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_USUARIOS_FILTRO` (IN `IDROL` INT)   BEGIN
  SELECT DISTINCT
    -- DNI y nombres de docentes, personal administrativo y alumnos
    COALESCE(docentes.docente_dni, personal_admi.personal_adm_dni, alumnos.alum_dni) AS dni,
    COALESCE(docentes.docente_nombre, personal_admi.personal_adm_nombre, alumnos.alum_nombre) AS nombre,
    COALESCE(docentes.docente_apelli, personal_admi.personal_adm_apellido, alumnos.alum_apepat) AS apellido,
    CONCAT_WS(' ', 
      COALESCE(docentes.docente_nombre, personal_admi.personal_adm_nombre, alumnos.alum_nombre),
      COALESCE(docentes.docente_apelli, personal_admi.personal_adm_apellido, alumnos.alum_apepat, alumnos.alum_apemat)
    ) AS nombre_completo,
    
    -- Foto de perfil
    COALESCE(docentes.docente_fotoperfil, personal_admi.personal_adm_fotoperfil, alumnos.alum_fotoperfil) AS foto_perfil,
    
    -- Datos de usuario
    usuario.usu_id, 
    usuario.usu_usuario, 
    usuario.usu_contra, 
    usuario.usu_email, 
    usuario.usu_estatus, 
    usuario.rol_id, 
    usuario.empresa_id,
    DATE_FORMAT(usuario.created_at, "%d-%m-%Y - %H:%i:%s") AS fecha_formateada,	
    usuario.created_at, 
    usuario.updated_at, 
    
    -- Datos de roles
    roles.Id_rol, 
    roles.tipo_rol
    
  FROM
    usuario
  INNER JOIN roles ON usuario.rol_id = roles.Id_rol

  -- Join con docentes, personal administrativo y alumnos
  LEFT JOIN docentes ON usuario.usu_id = docentes.id_asusuario
  LEFT JOIN personal_admi ON usuario.usu_id = personal_admi.id_ausuario
  LEFT JOIN matricula ON usuario.usu_id = matricula.usu_id
  LEFT JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
  WHERE usuario.rol_id=IDROL;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTA_COMPO_CURSO_EDITAR` (IN `ID` INT)   SELECT DISTINCT
criterios.id_criterio,
criterios.id_detalle_asignatura,
criterios.competencias,
criterios.`descripción_observa`,
asignaturas.nombre_asig,
detalle_asignatura_docente.Id_asig_docente,
detalle_asignatura_docente.Id_detalle_asig_docente,
asignatura_docente.Id_asigdocente
FROM
criterios
INNER JOIN detalle_asignatura_docente ON criterios.id_detalle_asignatura = detalle_asignatura_docente.Id_detalle_asig_docente
INNER JOIN asignaturas ON detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
INNER JOIN asignatura_docente ON detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente

WHERE detalle_asignatura_docente.Id_detalle_asig_docente=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTA_HORARIOS_EDITAR` (IN `ID` INT)   SELECT
	horas_aula.id_hora, 
	horas_aula.hora_inicio, 
	horas_aula.hora_fin,
  horarios.id_horario,
  CONCAT_WS(' - ',hora_inicio,hora_fin) AS HORA, 
	asignaturas.nombre_asig, 
	detalle_asignatura_docente.Id_detalle_asig_docente, 
	horarios.dia, 
	horas_aula.id_aula
FROM
	horarios
	INNER JOIN
	horas_aula
	ON 
		horarios.id_hora_aula = horas_aula.id_hora
	INNER JOIN
	detalle_asignatura_docente
	ON 
		horarios.id_detalle_asig_docente = detalle_asignatura_docente.Id_detalle_asig_docente
	INNER JOIN
	asignatura_docente
	ON 
		detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
	INNER JOIN
	asignaturas
	ON 
		detalle_asignatura_docente.Id_asignatura = asignaturas.Id_asignatura
WHERE
	horas_aula.id_aula=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ALUMNOS` (IN `ID` INT, IN `DNI` CHAR(8), IN `NOMBRES` VARCHAR(255), IN `APEPA` VARCHAR(255), IN `APEMATE` VARCHAR(255), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` CHAR(9), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `IDPA` INT, IN `DNIPA` CHAR(8), IN `DATOSPA` VARCHAR(255), IN `CELPA` VARCHAR(255), IN `DNIMA` CHAR(8), IN `DATOSMA` VARCHAR(255), IN `CELMA` VARCHAR(255))   BEGIN
    DECLARE EDAD INT;
    DECLARE NDOCUMENTOACTUAL CHAR(8);
    DECLARE EXISTE_DNI_OTRO INT;

    -- Calcular la edad
    SET @EDAD := (TIMESTAMPDIFF(YEAR, FECHANAC, NOW()));

    -- Obtener el DNI actual del alumno que se va a actualizar
    SET @NDOCUMENTOACTUAL := (SELECT alum_dni FROM alumnos WHERE Id_alumno = ID LIMIT 1);

    -- Verificar si el DNI proporcionado ya existe en la base de datos y pertenece a otro alumno
    SET @EXISTE_DNI_OTRO := (SELECT COUNT(*) FROM alumnos WHERE alum_dni = DNI AND Id_alumno != ID);

    -- Si el DNI proporcionado es el mismo que el actual, permitir la actualización
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

        SELECT 1; -- Indica que la actualización fue exitosa

    -- Si el DNI proporcionado no es el mismo que el actual y ya existe en la base de datos, no permitir la actualización
    ELSEIF @EXISTE_DNI_OTRO > 0 THEN
        SELECT 2; -- Indica que el DNI ya existe y no se puede actualizar

    -- Si el DNI no existe en la base de datos, permitir la actualización
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

        SELECT 1; -- Indica que la actualización fue exitosa
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_AÑO` (IN `ID` INT, IN `AÑO` INT, IN `NOMBRE` VARCHAR(255), IN `FECHAINI` DATE, IN `FECHAFIN` DATE, IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE AÑOACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @AÑOACTUAL:=(SELECT año_escolar FROM año_escolar WHERE Id_año_escolar=ID);
IF @AÑOACTUAL = AÑO THEN
	UPDATE año_escolar SET
	año_escolar=AÑO,
	Nombre_año=NOMBRE,
	fecha_inicio= FECHAINI,
	fecha_fin=FECHAFIN,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_año_escolar=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM año_escolar where año_escolar=NOMBRE or fecha_inicio=FECHAINI and fecha_fin=FECHAFIN);
	IF @CANTIDAD=0 THEN
	UPDATE año_escolar SET
	año_escolar=AÑO,
	Nombre_año=NOMBRE,
	fecha_inicio= FECHAINI,
	fecha_fin=FECHAFIN,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_año_escolar=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_AREA` (IN `ID` INT, IN `NAREA` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE AREAACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @AREAACTUAL:=(SELECT area_nombre FROM area WHERE area_cod=ID);
IF @AREAACTUAL = NAREA THEN
	UPDATE area SET
	area_estado=ESTADO,
	area_nombre=NAREA
	WHERE area_cod=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM area WHERE area_nombre=NAREA);
	IF @CANTIDAD=0 THEN
		UPDATE area SET
		area_estado=ESTADO,
		area_nombre=NAREA
		WHERE area_cod=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ASIGNATURA` (IN `ID` INT, IN `ASIGNA` VARCHAR(255), IN `GRADO` INT, IN `OBSERVA` VARCHAR(255))   BEGIN
DECLARE ASIGACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @ASIGACTUAL:=(SELECT nombre_asig FROM asignaturas WHERE asignaturas.Id_asignatura=ID);
IF @ASIGACTUAL = ASIGNA THEN
	UPDATE asignaturas SET
	nombre_asig=ASIGNA,
	Id_grado=GRADO,
	observaciones=OBSERVA,
	updated_at=NOW()
	WHERE Id_asignatura=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM asignaturas where asignaturas.nombre_asig=ASIGNA and asignaturas.Id_grado=GRADO);
	IF @CANTIDAD=0 THEN
	UPDATE asignaturas SET
	nombre_asig=ASIGNA,
	Id_grado=GRADO,
	observaciones=OBSERVA,
	updated_at=NOW()
	WHERE Id_asignatura=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ASIG_DOCENTE_UNICO` (IN `IDASIG_DOCENTE` INT, IN `IDS_ASIGNATURAS` VARCHAR(255))   BEGIN
    DECLARE CANTIDAD INT;
    DECLARE TOTALCURSOS INT;
    DECLARE ASIG_ID VARCHAR(10);
    DECLARE POS INT DEFAULT 1;
    DECLARE LEN INT;
    DECLARE COMMA_POS INT;

    SET LEN = LENGTH(IDS_ASIGNATURAS);

    WHILE POS <= LEN DO
        SET COMMA_POS = LOCATE(',', IDS_ASIGNATURAS, POS);

        IF COMMA_POS = 0 THEN
            SET COMMA_POS = LEN + 1;
        END IF;

        SET ASIG_ID = SUBSTRING(IDS_ASIGNATURAS, POS, COMMA_POS - POS);

        -- Obtener el conteo de registros que coinciden
        SELECT COUNT(*) INTO CANTIDAD 
        FROM detalle_asignatura_docente 
        WHERE Id_asig_docente = IDASIG_DOCENTE AND Id_asignatura = ASIG_ID;

        IF CANTIDAD = 0 THEN
            -- Insertar nuevo registro
            INSERT INTO detalle_asignatura_docente(Id_asig_docente, Id_asignatura, created_at, updated_at) 
            VALUES (IDASIG_DOCENTE, ASIG_ID, NOW(), NOW());

            -- Actualizar estado de la asignatura
            UPDATE asignaturas
            SET estado = 'CON DOCENTE'
            WHERE Id_asignatura = ASIG_ID;
        END IF;

        SET POS = COMMA_POS + 1;
    END WHILE;

    -- Obtener el total de cursos
    SELECT COUNT(Id_asignatura) INTO TOTALCURSOS 
    FROM detalle_asignatura_docente 
    WHERE Id_asig_docente = IDASIG_DOCENTE;

    -- Actualizar el total de cursos en asignatura_docente
    UPDATE asignatura_docente
    SET Total_cursos = TOTALCURSOS
    WHERE Id_asigdocente = IDASIG_DOCENTE;

    -- Retornar 1 indicando que la operación fue exitosa
    SELECT 1 AS resultado;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ATENCION_ENFERMERIA` (IN `ID` INT, IN `IDESTU` INT, IN `MOTIVO` VARCHAR(255), IN `DIAGNO` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `IDUSU` INT)   UPDATE atencion_salud
SET
id_matricula=IDESTU,
id_usuario=IDUSU,
motivo_consulta=MOTIVO,
diagnostico=DIAGNO,
observaciones=OBSERVA,
updated_at=NOW()
WHERE id_atencion=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ATENCION_PSICOLOGICA` (IN `ID` INT, IN `IDESTU` INT, IN `MOTIVO` VARCHAR(255), IN `DIAGNO` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `IDUSU` INT)   UPDATE atencion_salud
SET
id_matricula=IDESTU,
id_usuario=IDUSU,
motivo_consulta=MOTIVO,
diagnostico=DIAGNO,
observaciones=OBSERVA,
updated_at=NOW()
WHERE id_atencion=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_AULAS` (IN `ID` INT, IN `GRADO1` VARCHAR(255), IN `SECCION` INT, IN `NIVEL` INT, IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE GRADOACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @GRADOACTUAL:=(SELECT Grado FROM aulas WHERE Id_aula=ID);
IF @GRADOACTUAL = GRADO1 THEN
	UPDATE aulas SET
	Grado=GRADO1,
	id_nivel_academico=NIVEL,
	id_seccion=SECCION,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated=NOW()
	WHERE Id_aula=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM aulas where Grado=GRADO1 and id_nivel_academico=NIVEL and id_seccion=SECCION);
	IF @CANTIDAD=0 THEN
	UPDATE aulas SET
	Grado=GRADO1,
	id_nivel_academico=NIVEL,
	id_seccion=SECCION,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated=NOW()
	WHERE Id_aula=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_COMPONENTE` (IN `ID_ASIG_DETALLE` INT, IN `COMPO` VARCHAR(255), IN `OBSER` TEXT)   BEGIN
    -- Verificar si el componente ya existe
    DECLARE componente_existente INT;

    SELECT COUNT(*) INTO componente_existente
    FROM criterios
    WHERE criterios.id_detalle_asignatura = ID_ASIG_DETALLE;

    IF componente_existente > 0 THEN
        -- Actualizar el componente
        UPDATE criterios
        SET criterios.competencias = COMPO,
            `descripción_observa` = OBSER
        WHERE criterios.id_detalle_asignatura = ID_ASIG_DETALLE;

        -- Retornar 1 para indicar éxito
        SELECT 1;
    ELSE
        -- Retornar 2 para indicar que el componente no existe
        SELECT 2;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_COMUNICADO` (IN `ID` INT, IN `TIPO` VARCHAR(255), IN `GRADO` INT, IN `TITU` VARCHAR(255), IN `DESCRI` VARCHAR(255), IN `ESTA` VARCHAR(20), IN `RUTA` VARCHAR(255), IN `USU` INT)   BEGIN
    DECLARE ID_ACTUAL INT;
    DECLARE EXISTE_COMU_OTRO INT;

    -- Obtener el DNI actual del docente que se va a actualizar
    SET @ID_ACTUAL := (SELECT comunicados.id_comunicado FROM comunicados WHERE comunicados.id_comunicado = ID LIMIT 1);

    -- Verificar si el DNI proporcionado ya existe en la base de datos y pertenece a otro docente
		    SET @EXISTE_COMU_OTRO := (SELECT COUNT(*) FROM comunicados where comunicados.tipo=TIPO AND comunicados.id_aula=GRADO AND comunicados.created_at=NOW());

    -- Si el DNI proporcionado es el mismo que el actual o no existe en la base de datos, permitir la actualización
    IF @ID_ACTUAL = ID THEN
        UPDATE comunicados
        SET
            tipo = TIPO,
            id_aula = GRADO,
            titulo = TITU,
            descripcion = DESCRI,
            imagen = RUTA,
            estado = ESTA,
            id_usuario = USU,
            updated_at = NOW()
        WHERE id_comunicado = ID;

        SELECT 1; -- Indica que la actualización fue exitosa

    -- Si el DNI proporcionado ya existe en la base de datos pero pertenece a otro docente, no permitir la actualización
    ELSEIF @EXISTE_COMU_OTRO > 0 THEN
        SELECT 2; -- Indica que el DNI ya existe y no se puede actualizar

    -- Si el DNI no existe en la base de datos, permitir la actualización
    ELSE
          UPDATE comunicados
        SET
            tipo = TIPO,
            id_aula = GRADO,
            titulo = TITU,
            descripcion = DESCRI,
            imagen = RUTA,
            estado = ESTA,
            id_usuario = USU,
            updated_at = NOW()
        WHERE id_comunicado = ID;

        SELECT 1; -- Indica que la actualización fue exitosa
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_DOCENTES` (IN `ID` INT, IN `DNI` CHAR(8), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `ESPE` INT, IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` CHAR(9), IN `CELALT` CHAR(9), IN `DIREC` VARCHAR(255), IN `ESTADO` VARCHAR(20), IN `FOTO` VARCHAR(255))   BEGIN
    DECLARE DNI_ACTUAL CHAR(8);
    DECLARE EXISTE_DNI_OTRO INT;

    -- Obtener el DNI actual del docente que se va a actualizar
    SET @DNI_ACTUAL := (SELECT docente_dni FROM docentes WHERE Id_docente = ID LIMIT 1);

    -- Verificar si el DNI proporcionado ya existe en la base de datos y pertenece a otro docente
    SET @EXISTE_DNI_OTRO := (SELECT COUNT(*) FROM docentes WHERE docente_dni = DNI AND Id_docente != ID);

    -- Si el DNI proporcionado es el mismo que el actual o no existe en la base de datos, permitir la actualización
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

        SELECT 1; -- Indica que la actualización fue exitosa

    -- Si el DNI proporcionado ya existe en la base de datos pero pertenece a otro docente, no permitir la actualización
    ELSEIF @EXISTE_DNI_OTRO > 0 THEN
        SELECT 2; -- Indica que el DNI ya existe y no se puede actualizar

    -- Si el DNI no existe en la base de datos, permitir la actualización
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

        SELECT 1; -- Indica que la actualización fue exitosa
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_DOCENTE_FOTO` (IN `DNI` CHAR(8), IN `RUTA` VARCHAR(255))   UPDATE docentes SET
docentes.docente_fotoperfil=RUTA
WHERE docentes.docente_dni=DNI$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EGRESOS` (IN `ID` INT, IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(5,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT)   BEGIN
    UPDATE egresos
    SET
    id_indicador=INDI,
    id_user=USU,
    cantidad=CANTIDAD,
    monto=MONTO,
    observacion=OBSERVA,
    updated=CURDATE()
    WHERE egresos.id_egresos=ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EMPLEADO` (IN `ID` INT, IN `NDOCUMENTO` CHAR(12), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(100), IN `APEMAT` VARCHAR(100), IN `FECHA` DATE, IN `MOVIL` CHAR(9), IN `DIRECCION` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE NDOCUMENTOACTUAL CHAR(12);
DECLARE CANTIDAD INT;
SET @NDOCUMENTOACTUAL:=(SELECT emple_nrodocumento FROM empleado WHERE empleado_id=ID);
IF @NDOCUMENTOACTUAL = NDOCUMENTO THEN
	UPDATE empleado SET
	emple_nrodocumento=NDOCUMENTO,
	emple_nombre=NOMBRE,
	emple_apepat=APEPAT,
	emple_apemat=APEMAT,
	emple_fechanacimiento=FECHA,
	emple_movil=MOVIL,
	emple_direccion=DIRECCION,
	emple_email=EMAIL,
	 emple_estatus=ESTADO
	WHERE empleado_id=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM empleado WHERE emple_nrodocumento=NDOCUMENTO);
IF @CANTIDAD=0 THEN
UPDATE empleado SET
	emple_nrodocumento=NDOCUMENTO,
	emple_nombre=NOMBRE,
	emple_apepat=APEPAT,
	emple_apemat=APEMAT,
	emple_fechanacimiento=FECHA,
	emple_movil=MOVIL,
	emple_direccion=DIRECCION,
	emple_email=EMAIL,
	 emple_estatus=ESTADO
	WHERE empleado_id=ID;
	SELECT 1;
ELSE
SELECT 2;

END IF;

END IF;



END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EMPLEADO_FOTO` (IN `ID` INT, IN `RUTA` VARCHAR(255))   UPDATE empleado SET
empl_fotoperfil=RUTA
WHERE empleado_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EMPRESA` (IN `ID` INT, IN `NOMBRE` VARCHAR(250), IN `EMAIL` VARCHAR(250), IN `COD` VARCHAR(10), IN `TELEFONO` VARCHAR(20), IN `DIRECCION` VARCHAR(250))   UPDATE empresa SET
	emp_razon=NOMBRE,
	emp_email=EMAIL,
	emp_cod=COD,
	emp_telefono=TELEFONO,
	emp_direccion=DIRECCION
	WHERE empresa_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EMPRESA_FOTO` (IN `ID` INT, IN `RUTA` VARCHAR(255))   UPDATE empresa SET
emp_logo=RUTA
WHERE empresa_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ENVIAR_TAREA` (IN `ID` CHAR(12), IN `RUTA` VARCHAR(255))   UPDATE detalle_tarea SET
	detalle_tarea.archivo_evnio_tarea=RUTA,
	detalle_tarea.updated_at=NOW()
	WHERE detalle_tarea.id_detalle_tarea=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ESPECIALIDAD` (IN `ID` INT, IN `ESPE` VARCHAR(100), IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE ESPEACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @ESPEACTUAL:=(SELECT Especialidad FROM especialidad WHERE Id_especilidad=ID);
IF @ESPEACTUAL = ESPE THEN
	UPDATE especialidad SET
	Especialidad=ESPE,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_especilidad=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM especialidad WHERE Especialidad=ESPE);
	IF @CANTIDAD=0 THEN
	UPDATE especialidad SET
	Especialidad=ESPE,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_especilidad=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ESTUDIANTE_FOTO` (IN `DNI` CHAR(8), IN `RUTA` VARCHAR(255))   UPDATE alumnos SET
alumnos.alum_fotoperfil=RUTA
WHERE alumnos.alum_dni=DNI$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EXAMENES` (IN `ID` CHAR(12), IN `IDDETA` INT, IN `TEMA` VARCHAR(255), IN `FECHA` DATETIME, IN `DESCRIP` VARCHAR(255))   BEGIN
    DECLARE IDDETALLE VARCHAR(255);
    DECLARE FECHA1 DATE;
    DECLARE CANTIDAD INT;

    -- Obtener el mes y la fecha de vencimiento actual del registro con el ID proporcionado
    SET IDDETALLE := (SELECT examen.id_detalle_asignatura FROM examen WHERE examen.id_examen = ID);
    SET FECHA1 := (SELECT examen.fecha_examen FROM examen WHERE examen.id_examen = ID);

    -- Comprobar si el mes y la fecha de vencimiento actuales son iguales a los proporcionados
    IF IDDETALLE = IDDETA AND FECHA1 = FECHA THEN
        -- Actualizar el registro si el mes y la fecha de vencimiento son los mismos
        UPDATE examen SET
            id_detalle_asignatura = IDDETA,
            tema_examen = TEMA,
            descripcion = DESCRIP,
            fecha_examen = FECHA,
            updated_at = NOW()
        WHERE id_examen = ID;
        -- Devolver 1 para indicar que la actualización fue exitosa
        SELECT 1;
    ELSE
        -- Contar cuántos registros existen con el mismo nivel académico, mes y fecha de vencimiento
        SET CANTIDAD := (SELECT COUNT(*) FROM examen where examen.id_detalle_asignatura=IDDETA and examen.fecha_examen=FECHA);
        
        -- Si no hay registros duplicados, actualizar el registro
        IF CANTIDAD = 0 THEN
            UPDATE examen SET
            id_detalle_asignatura = IDDETA,
            tema_examen = TEMA,
            descripcion = DESCRIP,
            fecha_examen = FECHA,
            updated_at = NOW()
        WHERE id_examen = ID;
            -- Devolver 1 para indicar que la actualización fue exitosa
            SELECT 1;
        ELSE
            -- Devolver 2 para indicar que no se puede actualizar debido a un conflicto de duplicación
            SELECT 2;
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EXAMEN_ESTATUS` (IN `ID` CHAR(12), IN `ESTA` VARCHAR(20))   UPDATE examen
SET examen.estado=ESTA
WHERE examen.id_examen=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_INDICADOR` (IN `ID` INT, IN `TIPO` VARCHAR(20), IN `NOMBRE` VARCHAR(100), IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE INDIACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @INDIACTUALL:=(SELECT indicadores.id_indicadores FROM indicadores WHERE indicadores.id_indicadores=ID);
IF @INDIACTUAL = ID THEN
	UPDATE indicadores SET
	tipo_indicador=TIPO,
  nombre=NOMBRE,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE id_indicadores=ID;
	SELECT 1;

ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM indicadores WHERE indicadores.tipo_indicador=TIPO AND indicadores.nombre=NOMBRE AND indicadores.estado=ESTADO);
	IF @CANTIDAD=0 THEN
	UPDATE indicadores SET
	tipo_indicador=TIPO,
  nombre=NOMBRE,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE id_indicadores=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_INGRESOS` (IN `ID` INT, IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(5,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT)   BEGIN
    UPDATE ingresos
    SET
    id_indicador=INDI,
    id_user=USU,
    cantidad=CANTIDAD,
    monto=MONTO,
    observacion=OBSERVA,
    updated=CURDATE()
    WHERE ingresos.id_ingreso=ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_MATRICULA` (IN `ID` INT, IN `IDESTU` INT, IN `AÑO` INT, IN `AULA` INT, IN `ADMIN` DECIMAL(5,2), IN `NUEVO` DECIMAL(5,2), IN `MATRI` DECIMAL(5,2), IN `PROCEDEN` VARCHAR(100), IN `PROVI` VARCHAR(50), IN `DEPAR` VARCHAR(50))   BEGIN
    DECLARE NDOCUMENTOACTUAL CHAR(12);
    DECLARE CANTIDAD INT;
    
    -- Obtener el id_alumno actual para la matrícula dada
    SET @NDOCUMENTOACTUAL := (SELECT matricula.id_alumno FROM matricula WHERE matricula.id_matricula = ID);
    
    -- Si el ID del alumno coincide con el actual, simplemente actualizamos
    IF @NDOCUMENTOACTUAL = IDESTU THEN
        UPDATE matricula SET
            id_alumno = IDESTU,
            id_año = AÑO,
            id_aula = AULA,
            pago_admi = ADMIN,
            pago_alu_nuevo = NUEVO,
            pago_matricula = MATRI,
            procedencia_colegio = PROCEDEN,
            provincia = PROVI,
            departamento = DEPAR,
            updated_at = NOW()
        WHERE id_matricula = ID;
        SELECT 1; -- Operación de actualización exitosa
    ELSE
        -- Contar cuántos registros existen con el mismo id_alumno y año, excluyendo el actual
        SET @CANTIDAD := (SELECT COUNT(*) FROM matricula WHERE matricula.id_alumno = IDESTU AND matricula.id_aula= AULA);
        
        -- Si no existen registros, se permite la actualización
        IF @CANTIDAD = 0 THEN
            UPDATE matricula SET
                id_alumno = IDESTU,
                id_año = AÑO,
                id_aula = AULA,
                pago_admi = ADMIN,
                pago_alu_nuevo = NUEVO,
                pago_matricula = MATRI,
                procedencia_colegio = PROCEDEN,
                provincia = PROVI,
                departamento = DEPAR,
                updated_at = NOW()
            WHERE id_matricula = ID;
            SELECT 1; -- Operación de actualización exitosa
        ELSE
            SELECT 2; -- Error: ya existe un registro con el mismo id_alumno y año
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_NIVEL_ACADEMICO` (IN `ID` INT, IN `NIVEL_ACA` VARCHAR(100), IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE NIVACACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @NIVACACTUAL:=(SELECT Nivel_academico FROM nivel_academico WHERE Id_nivel=ID);
IF @NIVACACTUAL = NIVEL_ACA THEN
	UPDATE nivel_academico SET
	Nivel_academico=NIVEL_ACA,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_nivel=ID;
	SELECT 1;

ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM nivel_academico WHERE Nivel_academico=NIVEL_ACA);
	IF @CANTIDAD=0 THEN
	UPDATE nivel_academico SET
	Nivel_academico=NIVEL_ACA,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_nivel=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_O_REGISTRAR_COMPONENTE` (IN `ID_ASIG_DETALLE` INT, IN `COMPO` VARCHAR(255), IN `OBSER` TEXT)   BEGIN
    -- Verificar si el componente ya existe
    DECLARE componente_existente INT;

    -- Contar los componentes existentes
    SELECT COUNT(*) INTO componente_existente
    FROM criterios
    WHERE id_detalle_asignatura = ID_ASIG_DETALLE;

    IF componente_existente > 0 THEN
        -- Si el componente existe, actualizar
        UPDATE criterios
        SET competencias = COMPO,
            descripción_observa = OBSER
        WHERE id_detalle_asignatura = ID_ASIG_DETALLE;

        -- Retornar 1 para indicar que el componente fue actualizado
        SELECT 1 AS resultado;
    ELSE
        -- Si el componente no existe, registrar nuevo
        INSERT INTO criterios (id_detalle_asignatura, competencias, descripción_observa, created_at, updated_at)
        VALUES (ID_ASIG_DETALLE, COMPO, OBSER, NOW(), NOW());

        -- Retornar 0 para indicar que el componente fue registrado
        SELECT 0 AS resultado;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_PAGO_PENSION` (IN `ID` INT, IN `MONTO` DECIMAL(5,2), IN `DESCRIP` VARCHAR(255))   UPDATE pago_pensiones
SET 
pago_pensiones.sub_total=MONTO,
pago_pensiones.motivo_edicion=DESCRIP,
pago_pensiones.updated_at=CURDATE()
WHERE pago_pensiones.id_pago_pension=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_PENSIONES` (IN `ID` INT, IN `NIVEL` INT, IN `MES` VARCHAR(30), IN `FECHA_VEN` DATE, IN `PRECIO` DECIMAL(5,2), IN `MORA` DECIMAL(5,2))   BEGIN
    DECLARE MESACTUAL VARCHAR(255);
    DECLARE FECHA_VEN_ACTUAL DATE;
    DECLARE CANTIDAD INT;

    -- Obtener el mes y la fecha de vencimiento actual del registro con el ID proporcionado
    SET MESACTUAL := (SELECT mes FROM pensiones WHERE id_pensiones = ID);
    SET FECHA_VEN_ACTUAL := (SELECT fecha_vencimiento FROM pensiones WHERE id_pensiones = ID);

    -- Comprobar si el mes y la fecha de vencimiento actuales son iguales a los proporcionados
    IF MESACTUAL = MES AND FECHA_VEN_ACTUAL = FECHA_VEN THEN
        -- Actualizar el registro si el mes y la fecha de vencimiento son los mismos
        UPDATE pensiones SET
            id_nivel_academico = NIVEL,
            mes = MES,
            fecha_vencimiento = FECHA_VEN,
            precio = PRECIO,
            mora = MORA,
            updated_at = NOW()
        WHERE id_pensiones = ID;
        -- Devolver 1 para indicar que la actualización fue exitosa
        SELECT 1;
    ELSE
        -- Contar cuántos registros existen con el mismo nivel académico, mes y fecha de vencimiento
        SET CANTIDAD := (SELECT COUNT(*) FROM pensiones WHERE id_nivel_academico = NIVEL AND mes = MES AND fecha_vencimiento = FECHA_VEN);
        
        -- Si no hay registros duplicados, actualizar el registro
        IF CANTIDAD = 0 THEN
            UPDATE pensiones SET
                id_nivel_academico = NIVEL,
                mes = MES,
                fecha_vencimiento = FECHA_VEN,
                precio = PRECIO,
                mora = MORA,
                updated_at = NOW()
            WHERE id_pensiones = ID;
            -- Devolver 1 para indicar que la actualización fue exitosa
            SELECT 1;
        ELSE
            -- Devolver 2 para indicar que no se puede actualizar debido a un conflicto de duplicación
            SELECT 2;
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_PERSONAL` (IN `ID` INT, IN `DNI` CHAR(8), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `TIPO` VARCHAR(50), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` CHAR(9), IN `CELALT` CHAR(9), IN `DIREC` VARCHAR(255), IN `ESTADO` VARCHAR(20), IN `FOTO` VARCHAR(255))   BEGIN
    DECLARE DNI_ACTUAL CHAR(8);
    DECLARE EXISTE_DNI_OTRO INT;

    -- Obtener el DNI actual del docente que se va a actualizar
    SET @DNI_ACTUAL := (SELECT personal_adm_dni FROM personal_admi WHERE personal_adm_id = ID LIMIT 1);

    -- Verificar si el DNI proporcionado ya existe en la base de datos y pertenece a otro docente
    SET @EXISTE_DNI_OTRO := (SELECT COUNT(*) FROM personal_admi WHERE personal_adm_dni = DNI AND personal_adm_id != ID);

    -- Si el DNI proporcionado es el mismo que el actual o no existe en la base de datos, permitir la actualización
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

        SELECT 1; -- Indica que la actualización fue exitosa

    -- Si el DNI proporcionado ya existe en la base de datos pero pertenece a otro docente, no permitir la actualización
    ELSEIF @EXISTE_DNI_OTRO > 0 THEN
        SELECT 2; -- Indica que el DNI ya existe y no se puede actualizar

    -- Si el DNI no existe en la base de datos, permitir la actualización
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

        SELECT 1; -- Indica que la actualización fue exitosa
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_ROLES` (IN `ID` INT, IN `ROL` VARCHAR(100), IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE ROLACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @ROLACTUAL:=(SELECT tipo_rol FROM roles WHERE Id_rol=ID);
IF @ROLACTUAL = ROL THEN
	UPDATE roles SET
	tipo_rol=ROL,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_rol=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM roles WHERE tipo_rol=ROL);
	IF @CANTIDAD=0 THEN
	UPDATE roles SET
	tipo_rol=ROL,
	descripcion=DESCRIP,
	estado=ESTADO,
	updated_at=NOW()
	WHERE Id_rol=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_SECCION` (IN `ID` INT, IN `SECCION` VARCHAR(100), IN `DESCRIP` VARCHAR(255), IN `ESTADO` VARCHAR(20))   BEGIN
DECLARE SECCIONACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @SECCIONACTUAL:=(SELECT seccion_nombre FROM seccion WHERE seccion_id=ID);
IF @SECCIONACTUAL = SECCION THEN
	UPDATE seccion SET
	seccion_nombre=SECCION,
	seccion_descripcion=DESCRIP,
	seccion_estado=ESTADO,
	updated_at=NOW()
	WHERE seccion_id=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*) FROM seccion WHERE seccion_nombre=SECCION);
	IF @CANTIDAD=0 THEN
	UPDATE seccion SET
	seccion_nombre=SECCION,
	seccion_descripcion=DESCRIP,
	seccion_estado=ESTADO,
	updated_at=NOW()
	WHERE seccion_id=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;
END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_TAREAS` (IN `ID` CHAR(12), IN `ASIGN` INT, IN `TEMA` VARCHAR(150), IN `FECHA` DATETIME, IN `DESCRI` VARCHAR(255), IN `RUTA` VARCHAR(255))   BEGIN
DECLARE IDDETALLE INT;
DECLARE CANTIDAD INT;
SET @IDDETALLE:=(SELECT tareas.id_detalle_asignatura FROM tareas WHERE id_tarea=ID);
IF @IDDETALLE = ASIGN THEN
	UPDATE tareas SET
	id_detalle_asignatura=ASIGN,
	tema=TEMA,
	descripcion=DESCRI,
	fecha_entrega=FECHA,
	archivo_tarea=RUTA,
	updated_at=NOW()
	WHERE id_tarea=ID;
	SELECT 1;
ELSE
SET @CANTIDAD:=(SELECT COUNT(*)
    FROM tareas
    WHERE tareas.id_detalle_asignatura = ASIGN
    AND tareas.tema = TEMA
    AND DATE(tareas.created_at) = CURDATE());
IF @CANTIDAD=0 THEN
	UPDATE tareas SET
	id_detalle_asignatura=ASIGN,
	tema=TEMA,
	descripcion=DESCRI,
	fecha_entrega=FECHA,
	archivo_tarea=RUTA,
	updated_at=NOW()
	WHERE id_tarea=ID;
	SELECT 1;
ELSE
SELECT 2;

END IF;

END IF;



END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_TAREA_ESTATUS` (IN `ID` CHAR(12), IN `ESTATU` VARCHAR(20))   BEGIN
UPDATE tareas
SET estado=ESTATU,
updated_at=NOW()
WHERE tareas.id_tarea=ID;

UPDATE detalle_tarea
SET 
calificacion=5,
observacion='NO ENTREGO TAREA',
estado='CALIFICADO',
updated_at=NOW()
WHERE detalle_tarea.id_tarea=ID AND detalle_tarea.estado='PENDIENTE';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_USUARIO` (IN `ID` INT, IN `USUARIO` VARCHAR(20), IN `ROL` INT, IN `EMAIL` VARCHAR(255))   UPDATE usuario SET
usuario.usu_usuario = USUARIO,
usuario.rol_id=ROL,
usuario.usu_email=EMAIL
WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_USUARIO_CONTRA` (IN `ID` INT, IN `CONTRA` VARCHAR(250))   UPDATE usuario SET
usuario.usu_contra=CONTRA
WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_USUARIO_ESTATUS` (IN `ID` INT, IN `ESTATUS` VARCHAR(20))   UPDATE usuario SET
usuario.usu_estatus=ESTATUS
WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ALUMNOS` (IN `DNI` CHAR(8), IN `NOMBRES` VARCHAR(255), IN `APEPA` VARCHAR(255), IN `APEMATE` VARCHAR(255), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` CHAR(9), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `DNIPA` CHAR(8), IN `DATOSPA` VARCHAR(255), IN `CELPA` VARCHAR(255), IN `DNIMA` CHAR(8), IN `DATOSMA` VARCHAR(255), IN `CELMA` VARCHAR(255))   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_AÑOS` (IN `AÑO` INT, IN `NOMBRE` VARCHAR(255), IN `FECHAINI` DATE, IN `FECHAFIN` DATE, IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM `año_escolar` where año_escolar=NOMBRE or fecha_inicio=FECHAINI and fecha_fin=FECHAFIN);
IF @CANTIDAD = 0 THEN
INSERT INTO `año_escolar`(año_escolar,Nombre_año,fecha_inicio,fecha_fin,descripcion,estado,created_at,updated_at)VALUE(AÑO,NOMBRE,FECHAINI,FECHAFIN,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_AREA` (IN `NAREA` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM area where area_nombre=NAREA);
IF @CANTIDAD = 0 THEN
INSERT INTO area(area_nombre,area_fecha_registro)VALUE(NAREA,NOW());
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ASIGNATURAS` (IN `ASIGNATU` VARCHAR(255), IN `GRADO` INT, IN `OBSERVA` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM asignaturas where asignaturas.nombre_asig=ASIGNATU and asignaturas.Id_grado=GRADO);
IF @CANTIDAD = 0 THEN
INSERT INTO asignaturas(nombre_asig,Id_grado,observaciones,estado,created_at,updated_at)VALUE(ASIGNATU,GRADO,OBSERVA,'SIN DOCENTE',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ASIGNATURA_DOCENTE` (IN `AÑO` INT, IN `IDDOCENTE` INT)   BEGIN 

DECLARE ULTI INT;

INSERT INTO asignatura_docente(Id_docente,id_año,Total_cursos,created_at,updated_at)VALUES
(IDDOCENTE,AÑO,'',NOW(),'');

SET @ULTI:=(SELECT MAX(asignatura_docente.Id_asigdocente) FROM asignatura_docente);
select @ULTI;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ASISTENCIA` (IN `ID_MATRI` INT, IN `FECHA` DATE, IN `ESTA` VARCHAR(20), IN `OBSER` VARCHAR(1000))   BEGIN
    DECLARE CANTIDAD INT;

    -- Verifica si ya existe una asistencia para el mismo estudiante y fecha
    SELECT COUNT(*) INTO CANTIDAD 
    FROM asistencia 
    WHERE asistencia.id_matricula = ID_MATRI 
      AND DATE(asistencia.created_at) = FECHA;

    IF CANTIDAD = 0 THEN
        -- Inserta la asistencia si no existe un registro previo para la fecha
        INSERT INTO asistencia(id_matricula, mes, fecha, estado, observacion, created_at, updated_at)
        VALUES (
            ID_MATRI, 
            MONTH(NOW()), 
            FECHA, 
            ESTA, 
            OBSER, 
            NOW(), 
            NULL
        );
        SELECT 1; -- Indica éxito en la inserción
    ELSE
        SELECT 2; -- Indica que ya existe un registro para la misma fecha y estudiante
    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ATENCION_ENFERME` (IN `IDESTU` INT, IN `MOTIVO` VARCHAR(255), IN `DIAGNO` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `IDUSU` INT)   INSERT INTO atencion_salud(id_matricula,id_usuario,tipo_atencion,motivo_consulta,diagnostico,observaciones,created_at,updated_at)
VALUE(IDESTU,IDUSU,'ENFERMERIA',MOTIVO,DIAGNO,OBSERVA,NOW(),'')$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ATENCION_PSICO` (IN `IDESTU` INT, IN `MOTIVO` VARCHAR(255), IN `DIAGNO` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `IDUSU` INT)   INSERT INTO atencion_salud(id_matricula,id_usuario,tipo_atencion,motivo_consulta,diagnostico,observaciones,created_at,updated_at)
VALUE(IDESTU,IDUSU,'PSICOLOGIA',MOTIVO,DIAGNO,OBSERVA,NOW(),'')$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_AULAS` (IN `GRADO1` VARCHAR(255), IN `SECCION` INT, IN `NIVEL` INT, IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM aulas where Grado=GRADO1 and id_nivel_academico=NIVEL and id_seccion=SECCION);
IF @CANTIDAD = 0 THEN
INSERT INTO aulas(Grado,id_nivel_academico,id_seccion,descripcion,estado,created_at,updated)VALUE(GRADO1,NIVEL,SECCION,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_AULA_HORA` (IN `AÑO` INT, IN `AULA` INT, IN `TURNO` VARCHAR(20), IN `INICIO` TIME, IN `FIN` TIME)   BEGIN
    DECLARE contador INT;

    -- Verificar si el componente ya existe
    SELECT COUNT(*) INTO contador 
    FROM horas_aula 
    WHERE id_aula = AULA AND hora_inicio = hora_inicio and hora_fin=FIN;

    IF contador > 0 THEN
        SELECT 2; -- Ya existe
    ELSE
        -- Insertar nuevo componente
        INSERT INTO horas_aula (id_año_academico, id_aula, turno, hora_inicio,hora_fin,estado,created_at,updated_at) 
        VALUES (AÑO, AULA, TURNO,INICIO,FIN, 'ACTIVO',NOW(),'');
        
        SELECT 1; -- Éxito
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_CALIFICACION` (IN `ID` INT, IN `NOTA` INT, IN `OBSER` VARCHAR(255))   UPDATE detalle_tarea
SET 
detalle_tarea.calificacion=NOTA,
detalle_tarea.observacion=OBSER,
detalle_tarea.estado='CALIFICADO'
WHERE detalle_tarea.id_detalle_tarea=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_COMPONENTE` (IN `ID_ASIG_DETALLE` INT, IN `COMPO` VARCHAR(100), IN `OBSER` TEXT)   BEGIN
    DECLARE contador INT;

    -- Verificar si el componente ya existe
    SELECT COUNT(*) INTO contador 
    FROM criterios 
    WHERE id_detalle_asignatura = ID_ASIG_DETALLE AND competencias = COMPO;

    IF contador > 0 THEN
        SELECT 2; -- Ya existe
    ELSE
        -- Insertar nuevo componente
        INSERT INTO criterios (id_detalle_asignatura, competencias, descripción_observa, estado,created_at,updated_at) 
        VALUES (ID_ASIG_DETALLE, COMPO, OBSER, 'ACTIVO',NOW(),'');
        
        SELECT 1; -- Éxito
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_COMUNICADOS` (IN `TIPO` VARCHAR(255), IN `GRADO` INT, IN `TITU` VARCHAR(255), IN `DESCRI` VARCHAR(255), IN `RUTA` VARCHAR(255), IN `USU` INT)   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM comunicados where comunicados.tipo=TIPO AND comunicados.id_aula=GRADO AND comunicados.created_at=NOW());
IF @CANTIDAD = 0 THEN
INSERT INTO comunicados(tipo,id_aula,titulo,descripcion,imagen,estado,id_usuario,created_at,updated_at)VALUE(TIPO,GRADO,TITU,DESCRI,RUTA,'ACTIVO',USU,NOW(),'');

SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_DETALLE_ASIGNA_DOCENTE` (IN `ID` INT, IN `ASIGNATURA` INT)   BEGIN
DECLARE TOTALCURSOS INT;
INSERT INTO detalle_asignatura_docente(Id_asig_docente,Id_asignatura,created_at,updated_at)values
(ID,ASIGNATURA,NOW(),'');

UPDATE asignaturas
SET asignaturas.estado='CON DOCENTE'
WHERE Id_asignatura=ASIGNATURA;


SET @TOTALCURSOS:=(SELECT COUNT(ASIGNATURA) FROM detalle_asignatura_docente WHERE  detalle_asignatura_docente.Id_asig_docente=ID);

UPDATE asignatura_docente
SET asignatura_docente.Total_cursos=@TOTALCURSOS
WHERE Id_asigdocente=ID;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_DETALLE_PENSION_PAGO` (IN `ID` INT, IN `CONCEPTO` VARCHAR(30), IN `PENSION` INT, IN `PAGO` DECIMAL(5,2))   BEGIN
DECLARE VER INT;
DECLARE ULID INT;

SET @VER:=(SELECT COUNT(*) FROM pago_pensiones WHERE pago_pensiones.id_matri=ID AND pago_pensiones.id_pension=PENSION);

IF @VER =0 THEN
INSERT INTO pago_pensiones(id_matri,concepto,id_pension,fecha_pago,sub_total,created_at,updated_at)values
(ID,CONCEPTO,PENSION,NOW(),PAGO,NOW(),'');
	SET @ULID:=(SELECT MAX(id_pago_pension) AS id FROM pago_pensiones);

INSERT INTO ingresos(id_pago_pension,id_indicador,id_user,cantidad,monto,observacion,estado,motivo_anulacion,fecha_anulacion,created_at,updated)
VALUES(@ULID,1,9,1,PAGO,CONCEPTO,'VALIDO','','',CURDATE(),'');
SELECT 1;
ELSE
SELECT 2;
END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_DOCENTES` (IN `DNI` CHAR(8), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `ESPE` INT, IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` CHAR(9), IN `CELALT` CHAR(9), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255))   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_EGRESOS` (IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(5,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT)   BEGIN
    INSERT INTO egresos 
    (id_indicador, id_user, cantidad, monto, observacion, estado, created_at, updated)
    VALUES
    (INDI,USU, CANTIDAD, MONTO, OBSERVA, 'VALIDO', CURDATE(), NULL);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_EMPLEADO` (IN `NDOCUMENTO` CHAR(12), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(100), IN `APEMAT` VARCHAR(100), IN `FECHA` DATE, IN `MOVIL` CHAR(9), IN `DIRECCION` VARCHAR(255), IN `EMAIL` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM empleado WHERE emple_nrodocumento=NDOCUMENTO);
IF @CANTIDAD=0 THEN
INSERT INTO empleado(emple_nrodocumento,emple_nombre,emple_apepat,emple_apemat,emple_fechanacimiento,emple_movil,emple_direccion,emple_email,emple_feccreacion,emple_estatus,empl_fotoperfil)VALUES(NDOCUMENTO,NOMBRE,APEPAT,APEMAT,FECHA,MOVIL,DIRECCION,EMAIL,CURDATE(),'ACTIVO','controller/empleado/FOTOS/usuario.png');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ESPECIALIDAD` (IN `ESPECI` VARCHAR(100), IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM especialidad where Especialidad=ESPECI);
IF @CANTIDAD = 0 THEN
INSERT INTO especialidad(Especialidad,Descripcion,Estado,created_at,updated_at)VALUE(ESPECI,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_EXAMENES` (IN `IDDETA` INT, IN `TEMA` VARCHAR(255), IN `FECHA` DATETIME, IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE Contar int;
DECLARE cantidad INT;
DECLARE cod char(12);
SET @Contar:=(SELECT COUNT(*) FROM examen where examen.id_detalle_asignatura=IDDETA and examen.fecha_examen=FECHA);
SET @cantidad :=(SELECT IFNULL(MAX(doc_ncorrelativo),0) FROM examen);
IF @Contar =0 then

	IF @cantidad >= 1 AND @cantidad <= 8 THEN
	SET @cod :=(SELECT CONCAT('E000000',(@cantidad+1)));
	ELSEIF @cantidad >= 9 AND @cantidad <= 98 THEN
	SET @cod :=(SELECT CONCAT('E00000',(@cantidad+1)));
	ELSEIF @cantidad >= 99 AND @cantidad <= 998 THEN
	SET @cod :=(SELECT CONCAT('E0000',(@cantidad+1)));
	ELSEIF @cantidad >= 999 AND @cantidad <= 9998 THEN
	SET @cod :=(SELECT CONCAT('E000',(@cantidad+1)));
	ELSEIF @cantidad >= 9999 AND @cantidad <= 99998 THEN
	SET @cod :=(SELECT CONCAT('E00',(@cantidad+1)));
	ELSEIF @cantidad >= 99999 AND @cantidad <= 999998 THEN
	SET @cod :=(SELECT CONCAT('E0',(@cantidad+1)));
	ELSEIF @cantidad >= 999999 THEN
	SET @cod :=(SELECT CONCAT('E',(@cantidad+1)));
	ELSE
	SET @cod :=(SELECT CONCAT('D0000001'));
	END IF;
	INSERT INTO examen(id_examen,id_detalle_asignatura,tema_examen,descripcion,fecha_examen,estado,created_at,updated_at,doc_ncorrelativo) 
	
	VALUES(@cod,IDDETA,TEMA,DESCRIP,FECHA,'PENDIENTE',NOW(),'',(@cantidad+1));
		SELECT 1;
	
	ELSE
	SELECT 2;
	
	END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_HORARIO_AULA` (IN `HORA` INT, IN `CURSO` INT, IN `DIA` VARCHAR(20))   BEGIN
    DECLARE contador INT;

    -- Verificar si el componente ya existe
    SELECT COUNT(*) INTO contador 
    FROM horarios 
    WHERE horarios.id_hora_aula = HORA AND horarios.id_detalle_asig_docente = CURSO and horarios.dia=DIA;

    IF contador > 0 THEN
        SELECT 2; -- Ya existe
    ELSE
        -- Insertar nuevo componente
        INSERT INTO horarios (id_hora_aula, id_detalle_asig_docente, dia, estado,created_at,updated_at) 
        VALUES (HORA, CURSO, DIA,'ACTIVO',NOW(),'');
        
        SELECT 1; -- Éxito
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_INDICADORES` (IN `TIPO` VARCHAR(20), IN `NOMBRE` VARCHAR(100), IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM indicadores where indicadores.tipo_indicador=TIPO AND indicadores.nombre=NOMBRE);
IF @CANTIDAD = 0 THEN
INSERT INTO indicadores(tipo_indicador,nombre,descripcion,estado,created_at,updated_at)VALUE(TIPO,NOMBRE,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_INGRESOS` (IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(5,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT)   BEGIN
    INSERT INTO ingresos 
    (id_pago_pension,id_indicador, id_user, cantidad, monto, observacion, estado, created_at, updated)
    VALUES
    (NULL,INDI,USU, CANTIDAD, MONTO, OBSERVA, 'VALIDO', CURDATE(), NULL);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_MATRICULA` (IN `IDESTU` INT, IN `AÑO` INT, IN `AULA` INT, IN `ADMIN` DECIMAL(5,2), IN `NUEVO` DECIMAL(5,2), IN `MATRI` DECIMAL(5,2), IN `PROCEDEN` VARCHAR(100), IN `PROVI` VARCHAR(50), IN `DEPAR` VARCHAR(50), IN `USU` VARCHAR(8), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255))   BEGIN
    DECLARE ULTI INT;
    DECLARE CANTIDAD INT;
    DECLARE TIPO VARCHAR(10);
    DECLARE VERI INT;
    DECLARE MAX_USU_ID INT;
    DECLARE ULID INT;

    -- Obtener el estado del alumno
    SELECT alumnos.tipo_alum INTO TIPO
    FROM alumnos 
    WHERE alumnos.Id_alumno = IDESTU;

    -- Obtener el ID del usuario si existe
    SELECT DISTINCT usuario.usu_id INTO VERI
    FROM matricula
    INNER JOIN usuario ON matricula.usu_id = usuario.usu_id
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    WHERE alumnos.Id_alumno = IDESTU;

    -- Verificar si ya existe una matrícula con el mismo IDESTU y AÑO
    SELECT COUNT(*) INTO CANTIDAD
    FROM matricula 
    WHERE matricula.id_alumno = IDESTU 
      AND matricula.id_año = AÑO;

    IF TIPO = 'NUEVO' THEN
        IF CANTIDAD = 0 THEN
            -- Insertar usuario
            INSERT INTO usuario(
                usu_usuario,
                usu_contra,
                usu_email,
                usu_estatus,
                rol_id,
                empresa_id,
                created_at,
                updated_at
            ) VALUES (
                USU,
                CONTRA,
                EMAIL,
                'ACTIVO',
                '1',
                '1',
                NOW(),
                NOW()
            );

            -- Obtener el último ID de usuario insertado
            SELECT LAST_INSERT_ID() INTO MAX_USU_ID;

            -- ACTUALIZANDO CAMPO ALUMNOS
            UPDATE alumnos
            SET alum_estatus = 'SI',
                tipo_alum = 'ANTIGUO'
            WHERE Id_alumno = IDESTU;

            -- Insertar matrícula
            INSERT INTO matricula(
                id_alumno,
                id_año,
                id_aula,
                pago_admi,
                pago_alu_nuevo,
                pago_matricula,
                procedencia_colegio,
                provincia,
                departamento,
                usu_id,
                created_at,
                updated_at
            ) VALUES (
                IDESTU,
                AÑO,
                AULA,
                ADMIN,
                NUEVO,
                MATRI,
                PROCEDEN,
                PROVI,
                DEPAR,
                MAX_USU_ID,
                NOW(),
                NOW()
            );

            -- Obtener el último ID de matrícula insertado
            SET ULTI := (SELECT MAX(id_matricula) FROM matricula);

            -- Insertar pagos
            INSERT INTO pago_pensiones(
                id_matri,
                concepto,
                id_pension,
                fecha_pago,
                sub_total,
                created_at,
                updated_at
            ) VALUES
            (ULTI, 'ADMISION', NULL, NOW(), ADMIN, NOW(), NOW()),
            (ULTI, 'ALUMNO NUEVO', NULL, NOW(), NUEVO, NOW(), NOW()),
            (ULTI, 'MATRICULA', NULL, NOW(), MATRI, NOW(), NOW());
            
            	SET @ULID:=(SELECT MAX(id_pago_pension) AS id FROM pago_pensiones);

            INSERT INTO ingresos(id_pago_pension,id_indicador,id_user,cantidad,monto,observacion,estado,motivo_anulacion,fecha_anulacion,created_at,updated)
            VALUES
            (@ULID,1,9,1,ADMIN,'ADMISION','VALIDO','','',CURDATE(),''),
            (@ULID,1,9,1,NUEVO,'ALUMNO NUEVO','VALIDO','','',CURDATE(),''),
            (@ULID,1,9,1,MATRI,'MATRICULA','VALIDO','','',CURDATE(),'')

            ;
            -- Retornar 1 si la operación fue exitosa
            SELECT 1;
        ELSE
            -- Retornar 2 si ya existe una matrícula con el mismo IDESTU y AÑO
            SELECT 2;
        END IF;
				
    ELSE IF TIPO = 'ANTIGUO' THEN
		
        IF CANTIDAD = 0 THEN
            -- Activar usuario
            UPDATE usuario
            SET usu_estatus = 'ACTIVO'
            WHERE usu_id = VERI;

            -- ACTUALIZANDO CAMPO ALUMNOS
            UPDATE alumnos
            SET alum_estatus = 'SI',
                tipo_alum = 'ANTIGUO'
            WHERE Id_alumno = IDESTU;

            -- Insertar matrícula
            INSERT INTO matricula(
                id_alumno,
                id_año,
                id_aula,
                pago_admi,
                pago_alu_nuevo,
                pago_matricula,
                procedencia_colegio,
                provincia,
                departamento,
                usu_id,
                created_at,
                updated_at
            ) VALUES (
                IDESTU,
                AÑO,
                AULA,
                ADMIN,
                NUEVO,
                MATRI,
                PROCEDEN,
                PROVI,
                DEPAR,
                VERI,
                NOW(),
                NOW()
            );

            -- Obtener el último ID de matrícula insertado
            SET ULTI := (SELECT MAX(id_matricula) FROM matricula);

            -- Insertar pagos
            INSERT INTO pago_pensiones(
                id_matri,
                concepto,
                id_pension,
                fecha_pago,
                sub_total,
                created_at,
                updated_at
            ) VALUES
            (ULTI, 'ADMISION', NULL, NOW(), ADMIN, NOW(), NOW()),
            (ULTI, 'ALUMNO NUEVO', NULL, NOW(), NUEVO, NOW(), NOW()),
            (ULTI, 'MATRICULA', NULL, NOW(), MATRI, NOW(), NOW());
  INSERT INTO ingresos(id_pago_pension,id_indicador,id_user,cantidad,monto,observacion,estado,motivo_anulacion,fecha_anulacion,created_at,updated)
            VALUES
            (@ULID,1,9,1,ADMIN,'ADMISION','VALIDO','','',CURDATE(),''),
            (@ULID,1,9,1,NUEVO,'ALUMNO NUEVO','VALIDO','','',CURDATE(),''),
            (@ULID,1,9,1,MATRI,'MATRICULA','VALIDO','','',CURDATE(),'');
            -- Retornar 1 si la operación fue exitosa
            SELECT 1;
        ELSE
            -- Retornar 2 si ya existe una matrícula con el mismo IDESTU y AÑO
            SELECT 2;
        END IF;
    END IF;
		        END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_NIVEL_ACADEMICO` (IN `NIVEL_ACA` VARCHAR(100), IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM nivel_academico where Nivel_academico=NIVEL_ACA);
IF @CANTIDAD = 0 THEN
INSERT INTO nivel_academico(Nivel_academico,descripcion,estado,create_at,updated_at)VALUE(NIVEL_ACA,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_NOTAS` (IN `registros_json` JSON)   BEGIN
    DECLARE idx INT DEFAULT 0;
    DECLARE total INT;
    DECLARE id_matricula INT;
    DECLARE id_bimestre INT;
    DECLARE id_criterio INT;
    DECLARE nota CHAR(5);
    DECLARE conclusiones VARCHAR(1000);

    -- Obtener el número de elementos en el JSON
    SET total = JSON_LENGTH(registros_json);

    -- Iterar sobre cada registro en el JSON
    WHILE idx < total DO
        -- Extraer los valores del JSON
        SET id_matricula = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].id_matri')));
        SET id_bimestre = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].perio')));
        SET id_criterio = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].cri')));
        SET nota = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].nota')));
        SET conclusiones = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].conclu')));
        
        -- Verificar si id_matricula existe en la tabla matricula
        IF EXISTS (
            SELECT 1
            FROM matricula
            WHERE id_matricula = id_matricula
        ) THEN
            -- Insertar el registro en la tabla principal si no existe
            INSERT INTO notas (id_matricula, id_bimestre, id_criterio, nota, conclusiones, creared_at)
            SELECT
                id_matricula,
                id_bimestre,
                id_criterio,
                nota,
                conclusiones,
                NOW() -- Asignar la fecha actual
            FROM dual
            WHERE NOT EXISTS (
                SELECT 1
                FROM notas
                WHERE notas.id_matricula = id_matricula
                  AND notas.id_bimestre = id_bimestre
                  AND notas.id_criterio = id_criterio
            );
        ELSE
            -- Si no existe, registrar un error
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: id_matricula no existe en la tabla matricula';
        END IF;

        -- Incrementar el índice
        SET idx = idx + 1;
    END WHILE;

    -- Devolver número de registros insertados
    SELECT ROW_COUNT() AS inserted_count;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_NOTAS_PADRES` (IN `registros_json` JSON)   BEGIN
    DECLARE idx INT DEFAULT 0;
    DECLARE total INT;
    DECLARE id_matricula INT;
    DECLARE id_bimestre VARCHAR(10);
    DECLARE criterio VARCHAR(255);
    DECLARE nota VARCHAR(10);
    DECLARE processed_count INT DEFAULT 0;
    DECLARE affected_rows INT;

    -- Obtener el número de elementos en el JSON
    SET total = JSON_LENGTH(registros_json);

    -- Iniciar una transacción
    START TRANSACTION;

    -- Iterar sobre cada registro en el JSON
    WHILE idx < total DO
        -- Extraer los valores del JSON
        SET id_matricula = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].id_matri')));
        SET id_bimestre = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].perio')));
        SET criterio = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].competencia')));
        SET nota = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].nota')));

        -- Insertar o actualizar el registro
        INSERT INTO notas_padre (id_matricula, id_bimestre, criterio, nota, creared_at, updated_at)
        VALUES (id_matricula, id_bimestre, criterio, nota, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            nota = VALUES(nota),
            updated_at = NOW();

        -- Obtener el número de filas afectadas
        SET affected_rows = ROW_COUNT();
        
        -- Incrementar el contador de registros procesados
        SET processed_count = processed_count + affected_rows;

        -- Incrementar el índice
        SET idx = idx + 1;
    END WHILE;

    -- Confirmar la transacción

    COMMIT;

    -- Devolver número de registros procesados
    SELECT processed_count AS processed_count;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_PENSIONES` (IN `NIVEL` INT, IN `MES` VARCHAR(30), IN `FECHA_VEN` DATE, IN `PRECIO` DECIMAL(5,2), IN `MORA` DECIMAL(5,2))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM pensiones where pensiones.id_nivel_academico=NIVEL and pensiones.mes=MES AND pensiones.fecha_vencimiento);
IF @CANTIDAD = 0 THEN
INSERT INTO pensiones(id_nivel_academico,mes,fecha_vencimiento,precio,mora,created_at,updated_at)VALUE(NIVEL,MES,FECHA_VEN,PRECIO,MORA,NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_PERIODO` (IN `ANIO` INT, IN `TIPOPERIO` VARCHAR(50), IN `PERIO` VARCHAR(50), IN `FECHAINI` DATE, IN `FECHAFIN` DATE)   BEGIN
    DECLARE contador INT;

    -- Verificar si ya existe un registro con los mismos datos
    SELECT COUNT(*) INTO contador
    FROM periodos
    WHERE id_año_escolar = ANIO 
      AND tipo_perido = TIPOPERIO 
      AND periodos = PERIO
      AND fecha_inicio = FECHAINI
      AND fecha_fin = FECHAFIN;

    IF contador > 0 THEN
        -- Si existe, realizar una acción (ej. establecer un resultado)
        SELECT 2;
    ELSE
        -- Si no existe, insertamos el nuevo registro
        INSERT INTO periodos (id_año_escolar, tipo_perido, periodos, fecha_inicio, fecha_fin, estado, created_at, updated_at)
        VALUES (ANIO, TIPOPERIO, PERIO, FECHAINI, FECHAFIN, 'EN CURSO', NOW(), NOW());

        -- Confirmar transacción
        COMMIT;
        -- Informar que la inserción fue exitosa
        SELECT 1;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_PERSONAL` (IN `DNI` CHAR(8), IN `NOMBRES` VARCHAR(255), IN `APELLI` VARCHAR(255), IN `TIPO` VARCHAR(30), IN `SEXO` VARCHAR(20), IN `FECHANAC` DATE, IN `CEL` CHAR(9), IN `CELALT` CHAR(9), IN `DIREC` VARCHAR(255), IN `FOTO` VARCHAR(255), IN `USU` VARCHAR(8), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `ROL` INT)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_ROLES` (IN `ROL` VARCHAR(100), IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM roles where tipo_rol=ROL);
IF @CANTIDAD = 0 THEN
INSERT INTO roles(tipo_rol,descripcion,estado,created_at,updated_at)VALUE(ROL,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_SECCION` (IN `SECCION` VARCHAR(100), IN `DESCRIP` VARCHAR(255))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM seccion where seccion_nombre=SECCION);
IF @CANTIDAD = 0 THEN
INSERT INTO seccion(seccion_nombre,seccion_descripcion,seccion_estado,created_at,updated_at)VALUE(SECCION,DESCRIP,'ACTIVO',NOW(),'');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_TAREA` (IN `ASIGN` INT, IN `TEMA` VARCHAR(150), IN `FECHA` DATETIME, IN `DESCRI` VARCHAR(255), IN `RUTA` VARCHAR(255))   BEGIN
    DECLARE Contar INT;
    DECLARE cantidad INT;
    DECLARE cod CHAR(12);
    DECLARE alum_id_matricula INT;
    DECLARE alum_id_alumno INT;
    DECLARE ULTI CHAR(12);
    DECLARE CURSO INT;
    DECLARE done INT DEFAULT FALSE;

    DECLARE alum_cursor CURSOR FOR
        SELECT matricula.id_matricula, matricula.id_alumno
        FROM matricula
        INNER JOIN aulas ON matricula.id_aula = aulas.Id_aula
        INNER JOIN asignaturas ON aulas.Id_aula = asignaturas.Id_grado
        INNER JOIN `año_escolar` ON matricula.`id_año` = `año_escolar`.`Id_año_escolar`
        WHERE Id_asignatura = @CURSO AND `año_escolar` = (SELECT YEAR(NOW()));

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SET @CURSO := (
        SELECT asignaturas.Id_asignatura
        FROM asignaturas
        INNER JOIN detalle_asignatura_docente ON asignaturas.Id_asignatura = detalle_asignatura_docente.Id_asignatura
        INNER JOIN asignatura_docente ON detalle_asignatura_docente.Id_asig_docente = asignatura_docente.Id_asigdocente
        INNER JOIN docentes ON asignatura_docente.Id_docente = docentes.Id_docente
        WHERE Id_detalle_asig_docente = ASIGN
    );

SET @Contar := (
    SELECT COUNT(*)
    FROM tareas
    WHERE tareas.id_detalle_asignatura = ASIGN
    AND tareas.tema = TEMA
    AND DATE(tareas.created_at) = CURDATE()
);    
SET @cantidad := (SELECT IFNULL(MAX(doc_ncorrelativo), 0) FROM tareas);

    IF @Contar = 0 THEN
        IF @cantidad >= 1 AND @cantidad <= 8 THEN
            SET @cod := (SELECT CONCAT('T000000', (@cantidad + 1)));
        ELSEIF @cantidad >= 9 AND @cantidad <= 98 THEN
            SET @cod := (SELECT CONCAT('T00000', (@cantidad + 1)));
        ELSEIF @cantidad >= 99 AND @cantidad <= 998 THEN
            SET @cod := (SELECT CONCAT('T0000', (@cantidad + 1)));
        ELSEIF @cantidad >= 999 AND @cantidad <= 9998 THEN
            SET @cod := (SELECT CONCAT('T000', (@cantidad + 1)));
        ELSEIF @cantidad >= 9999 AND @cantidad <= 99998 THEN
            SET @cod := (SELECT CONCAT('T00', (@cantidad + 1)));
        ELSEIF @cantidad >= 99999 AND @cantidad <= 999998 THEN
            SET @cod := (SELECT CONCAT('T0', (@cantidad + 1)));
        ELSEIF @cantidad >= 999999 THEN
            SET @cod := (SELECT CONCAT('T', (@cantidad + 1)));
        ELSE
            SET @cod := (SELECT CONCAT('T0000001'));
        END IF;

        INSERT INTO tareas(
            id_tarea, id_detalle_asignatura, tema, descripcion, fecha_entrega, archivo_tarea, estado, created_at, updated_at,doc_ncorrelativo
        ) VALUES (
            @cod, ASIGN, TEMA, DESCRI, FECHA, RUTA, 'PENDIENTE', NOW(), '',(@cantidad+1)
        );

        SET @ULTI := (SELECT MAX(id_tarea) FROM tareas);

        OPEN alum_cursor;

        read_loop: LOOP
            FETCH alum_cursor INTO alum_id_matricula, alum_id_alumno;
            IF done THEN
                LEAVE read_loop;
            END IF;

            INSERT INTO detalle_tarea(
                id_tarea, id_matriculado, archivo_evnio_tarea, calificacion, observacion, estado, fecha_envio, created_at, updated_at
            ) VALUES (
                @ULTI, alum_id_matricula, '', 0, '', 'PENDIENTE', '', NOW(), ''
            );
        END LOOP;

        CLOSE alum_cursor;

        SELECT 1;
    ELSE
        SELECT 2;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_USUARIO` (IN `USU` VARCHAR(255), IN `CONTRA` VARCHAR(255), IN `IDEMPLEADO` INT, IN `IDAREA` INT, IN `ROL` VARCHAR(25))   BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM usuario WHERE usu_usuario=USU);
IF @CANTIDAD=0 THEN
INSERT INTO usuario(usu_usuario,usu_contra,empleado_id,area_id,usu_rol,usu_feccreacion,usu_estatus,empresa_id)VALUES(USU,CONTRA,IDEMPLEADO,IDAREA,ROL,CURDATE(),'ACTIVO',2);
SELECT 1;
ELSE
SELECT 2;
END IF;


END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_VERIFICAR_USUARIO` (IN `USU` VARCHAR(255))   BEGIN
  SELECT
    CONVERT(docentes.docente_nombre USING utf8) AS docente_nombre,
    CONVERT(docentes.docente_apelli USING utf8) AS docente_apelli,
    CONVERT(CONCAT_WS(' ', docentes.docente_nombre, docentes.docente_apelli) USING utf8) AS Docente,
    CONVERT(docentes.docente_fotoperfil USING utf8) AS docente_fotoperfil,
    usuario.usu_id, 
    usuario.usu_usuario, 
    usuario.usu_contra, 
    usuario.usu_email, 
    usuario.usu_estatus, 
    usuario.rol_id, 
    usuario.empresa_id,
    DATE_FORMAT(usuario.created_at, "%d-%m-%Y - %H:%i:%s") AS fecha_formateada,	
    usuario.created_at, 
    usuario.updated_at, 
    roles.Id_rol, 
    roles.tipo_rol,
    CONVERT(docentes.docente_movil USING utf8) AS docente_movil,
    CONVERT(docentes.docente_direccion USING utf8) AS docente_direccion,
    DATE_FORMAT(docentes.docente_fechanacimiento, "%d-%m-%Y") AS fechana,
    CONVERT(docentes.docente_dni USING utf8) AS docente_dni
  FROM
    docentes
    INNER JOIN usuario ON docentes.id_asusuario = usuario.usu_id
    INNER JOIN roles ON usuario.rol_id = roles.Id_rol
  WHERE
    usuario.usu_usuario = BINARY USU

  UNION

  SELECT
    CONVERT(personal_admi.personal_adm_nombre USING utf8) AS personal_adm_nombre, 
    CONVERT(personal_admi.personal_adm_apellido USING utf8) AS personal_adm_apellido,
    CONVERT(CONCAT_WS(' ', personal_admi.personal_adm_nombre, personal_admi.personal_adm_apellido) USING utf8) AS Docente,
    CONVERT(personal_admi.personal_adm_fotoperfil USING utf8) AS personal_adm_fotoperfil,
    usuario.usu_id, 
    usuario.usu_usuario, 
    usuario.usu_contra, 
    usuario.usu_email, 
    usuario.usu_estatus, 
    usuario.rol_id, 
    usuario.empresa_id,
    DATE_FORMAT(usuario.created_at, "%d-%m-%Y - %H:%i:%s") AS fecha_formateada,	
    usuario.created_at, 
    usuario.updated_at, 
    roles.Id_rol, 
    roles.tipo_rol,
    CONVERT(personal_admi.personal_adm_movil USING utf8) AS personal_adm_movil,
    CONVERT(personal_admi.personal_adm_direccion USING utf8) AS personal_adm_direccion,
    DATE_FORMAT(personal_admi.personal_adm_fechanacimiento, "%d-%m-%Y") AS fechanaadmin,
    CONVERT(personal_admi.personal_adm_dni USING utf8) AS personal_adm_dni
  FROM
    personal_admi
    INNER JOIN usuario ON personal_admi.id_ausuario = usuario.usu_id
    INNER JOIN roles ON usuario.rol_id = roles.Id_rol
  WHERE
    usuario.usu_usuario = BINARY USU

  UNION

  SELECT
    CONVERT(alumnos.alum_nombre USING utf8) AS alum_nombre, 
    CONVERT(alumnos.alum_apepat USING utf8) AS alum_apepat,
    CONVERT(CONCAT_WS(' ', alumnos.alum_nombre, alumnos.alum_apepat, alumnos.alum_apemat) USING utf8) AS Docente,
    CONVERT(alumnos.alum_fotoperfil USING utf8) AS alum_fotoperfil,
    usuario.usu_id, 
    usuario.usu_usuario, 
    usuario.usu_contra, 
    usuario.usu_email, 
    usuario.usu_estatus, 
    usuario.rol_id, 
    usuario.empresa_id,
    DATE_FORMAT(usuario.created_at, "%d-%m-%Y - %H:%i:%s") AS fecha_formateada,	
    usuario.created_at, 
    usuario.updated_at, 
    roles.Id_rol, 
    roles.tipo_rol,
    CONVERT(alumnos.alum_movil USING utf8) AS alum_movil,
    CONVERT(alumnos.alum_direccion USING utf8) AS alum_direccion,
    DATE_FORMAT(alumnos.alum_fechanacimiento, "%d-%m-%Y") AS fechanaalumno,
    CONVERT(alumnos.alum_dni USING utf8) AS alum_dni
  FROM
    alumnos
    INNER JOIN matricula ON alumnos.Id_alumno = matricula.id_alumno
    INNER JOIN usuario ON matricula.usu_id = usuario.usu_id
    INNER JOIN roles ON usuario.rol_id = roles.Id_rol
  WHERE
    usuario.usu_usuario = BINARY USU;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `Id_alumno` int(11) NOT NULL,
  `alum_dni` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `alum_nombre` varchar(100) DEFAULT NULL,
  `alum_apepat` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `alum_apemat` varchar(100) DEFAULT NULL,
  `alum_sexo` enum('FEMENINO','MASCULINO') DEFAULT NULL,
  `alum_fechanacimiento` date DEFAULT NULL,
  `Edad` int(11) DEFAULT NULL,
  `alum_movil` char(9) DEFAULT NULL,
  `alum_direccion` varchar(255) DEFAULT NULL,
  `alum_estatus` enum('SI','NO') NOT NULL,
  `tipo_alum` enum('NUEVO','ANTIGUO') DEFAULT NULL,
  `alum_fotoperfil` varchar(255) NOT NULL DEFAULT 'Fotos/admin.png',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`Id_alumno`, `alum_dni`, `alum_nombre`, `alum_apepat`, `alum_apemat`, `alum_sexo`, `alum_fechanacimiento`, `Edad`, `alum_movil`, `alum_direccion`, `alum_estatus`, `tipo_alum`, `alum_fotoperfil`, `created_at`, `updated_at`) VALUES
(6, '66655656', 'YESSENIA', 'CAMACHO', 'PERALTA', 'FEMENINO', '2022-07-14', 3, '952626262', 'AV. CANADA N° 121', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2024-09-10 14:09:30', '2025-12-25 10:02:49'),
(7, '52220200', 'JOSE LUIS', 'JIMENEZ', 'DAVILA', 'MASCULINO', '2022-06-14', 3, '929292662', 'AV. NUÑEZ N° 232', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/IMG1-10-2024-14-175.jpg', '2024-09-10 14:10:49', '2025-12-25 10:03:02'),
(9, '72451251', 'DANIEL', 'CHAVEZ', 'PEÑA', 'MASCULINO', '2009-08-14', 16, '', 'JR CUSCO N° 32', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 09:54:47', '0000-00-00 00:00:00'),
(10, '32231232', 'JUAN', 'DAVILA', 'PEÑA', 'MASCULINO', '2009-01-01', 16, '', 'AV. CUSCO N° 123', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/IMG25-12-2025-9-703.png', '2025-12-25 09:57:22', '0000-00-00 00:00:00'),
(11, '23233223', 'JOAQUIN', 'CHAVEZ', 'FANOLA', 'MASCULINO', '2009-02-12', 16, '921551515', 'JR. TACNA N° 2323', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 09:58:11', '0000-00-00 00:00:00'),
(12, '15311554', 'ESTEFANY', 'PERALTA', 'HUAMAN', 'FEMENINO', '2009-08-11', 16, '920555151', 'AV. BARCENAS', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 09:59:09', '0000-00-00 00:00:00'),
(13, '15511556', 'MELANI', 'PANIAGUA', 'VERA', 'FEMENINO', '2009-12-12', 16, '921515115', 'JR. CANADA N° 213', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 09:59:57', '0000-00-00 00:00:00'),
(14, '51515151', 'PEDRO', 'DAVILA', 'PEREZ', 'MASCULINO', '2009-08-12', 16, '', 'JR. CANADA N° 3213', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 10:01:05', '0000-00-00 00:00:00'),
(15, '34535345', 'GUILLERMO', 'PEÑA', 'SANCHEZ', 'MASCULINO', '2009-09-04', 16, '915155155', 'AV. GUATEMALA', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 10:02:32', '0000-00-00 00:00:00'),
(16, '45511551', 'SANDRA', 'PEÑA', 'VERA', 'FEMENINO', '2022-12-12', 3, '922511515', 'AV. DIAZ BARCENAS', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 10:03:50', '0000-00-00 00:00:00'),
(17, '22626969', 'JOAQUIN JORGE', 'PEÑA', 'PEÑA', 'MASCULINO', '2022-11-25', 3, '', 'AV. HUANCA N° 133', 'SI', 'ANTIGUO', 'controller/alumnos/fotos/', '2025-12-25 10:04:43', '0000-00-00 00:00:00'),
(18, '55516555', 'ALEXANDER', 'PERALTA', 'CARRION', 'MASCULINO', '2022-04-13', 3, '', 'AV. DIAZ S/N', 'NO', 'NUEVO', 'controller/alumnos/fotos/', '2025-12-25 10:06:04', '0000-00-00 00:00:00'),
(19, '62262626', 'ANDRES', 'CHAVEZ', 'VALENZUELA', 'MASCULINO', '2022-08-12', 3, '', 'JR. CUSCO N° 2323', 'NO', 'NUEVO', 'controller/alumnos/fotos/', '2025-12-25 10:23:09', '0000-00-00 00:00:00'),
(20, '15515115', 'MARY CARMEN', 'FARFAN', 'DAMIAN', 'FEMENINO', '2022-01-08', 3, '', 'AV. NUÑEZ N° 122', 'NO', 'NUEVO', 'controller/alumnos/fotos/', '2025-12-25 10:24:08', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `Id_asignatura` int(255) NOT NULL,
  `nombre_asig` varchar(255) NOT NULL,
  `Id_grado` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `estado` enum('SIN DOCENTE','CON DOCENTE') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignaturas`
--

INSERT INTO `asignaturas` (`Id_asignatura`, `nombre_asig`, `Id_grado`, `observaciones`, `estado`, `created_at`, `updated_at`) VALUES
(2, 'COMUNICACIÓN INTEGRAL', 5, 'NINGUNA', 'CON DOCENTE', '2024-07-08', '2024-07-08 17:07:04'),
(4, 'CIENCIA Y AMBIENTE', 5, 'NINGUNA', 'SIN DOCENTE', '2024-07-08', '0000-00-00 00:00:00'),
(7, 'PERSONAL SOCIAL', 5, '', 'SIN DOCENTE', '2024-08-01', '0000-00-00 00:00:00'),
(8, 'RAZONAMIENTO MATEMATICO', 5, '', 'SIN DOCENTE', '2024-08-01', '0000-00-00 00:00:00'),
(9, 'COMUNICACION INTEGRAL', 6, '', 'SIN DOCENTE', '2024-08-02', '0000-00-00 00:00:00'),
(10, 'SOCIALES', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(11, 'ANATOMIA', 18, 'CORRECTO', 'SIN DOCENTE', '2024-09-04', '2024-09-08 16:48:34'),
(12, 'BIOLOGIA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(13, 'ALGEBRA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(14, 'EDUCACION FISICA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(15, 'GEOMETRIA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(16, 'COMUNICACION', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(17, 'FISICA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(18, 'DPCC', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(19, 'QUIMICA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(20, 'RELIGION', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(21, 'TUTORIA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(22, 'INGLES', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(23, 'RAZONAMIENTO MATEMATICO', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(24, 'ARITMETICA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(25, 'COMPUTACION', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(26, 'TRIGONOMETRIA', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(27, 'ARTE', 18, '', 'SIN DOCENTE', '2024-09-04', '0000-00-00 00:00:00'),
(28, 'RECREO', 18, '', 'SIN DOCENTE', '2024-09-04', '2024-09-04 17:09:42'),
(29, 'PERSONAL SOCIAL', 6, '', 'SIN DOCENTE', '2024-09-08', '0000-00-00 00:00:00'),
(30, 'MATEMATICA', 18, '', 'SIN DOCENTE', '2024-09-10', '0000-00-00 00:00:00'),
(31, 'MATEMATICA', 15, '', 'SIN DOCENTE', '2024-09-10', '0000-00-00 00:00:00'),
(32, 'COMUNICACIÓN', 15, '', 'SIN DOCENTE', '2024-09-10', '0000-00-00 00:00:00'),
(33, 'FISICA', 15, '', 'SIN DOCENTE', '2024-09-10', '0000-00-00 00:00:00'),
(34, 'QUIMICA', 15, '', 'SIN DOCENTE', '2024-09-10', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura_docente`
--

CREATE TABLE `asignatura_docente` (
  `Id_asigdocente` int(11) NOT NULL,
  `Id_docente` int(11) DEFAULT NULL,
  `id_año` int(11) DEFAULT NULL,
  `Total_cursos` int(11) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignatura_docente`
--

INSERT INTO `asignatura_docente` (`Id_asigdocente`, `Id_docente`, `id_año`, `Total_cursos`, `created_at`, `updated_at`) VALUES
(27, 1, 5, 1, '2024-08-01', '0000-00-00 00:00:00.000000'),
(30, 1, 5, 1, '2024-08-01', '0000-00-00 00:00:00.000000'),
(31, 15, 5, 1, '2024-08-02', '0000-00-00 00:00:00.000000'),
(33, 11, 5, 20, '2024-09-04', '0000-00-00 00:00:00.000000'),
(34, 6, 5, 1, '2024-09-10', '0000-00-00 00:00:00.000000'),
(35, 10, 5, 1, '2024-09-10', '0000-00-00 00:00:00.000000'),
(42, 2, 1, 2, '2024-09-23', '0000-00-00 00:00:00.000000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id_asistencia` int(255) NOT NULL,
  `id_matricula` int(255) DEFAULT NULL,
  `mes` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `estado` enum('PRESENTE','TARDE','AUSENTE','JUSTIFICADO') DEFAULT NULL,
  `observacion` varchar(1000) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id_asistencia`, `id_matricula`, `mes`, `fecha`, `estado`, `observacion`, `created_at`, `updated_at`) VALUES
(41, 31, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(42, 32, '12', '2025-12-25', 'TARDE', '', '2025-12-25', NULL),
(43, 40, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(44, 41, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(45, 33, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(46, 34, '12', '2025-12-25', 'AUSENTE', 'FIEBRE', '2025-12-25', NULL),
(47, 35, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(48, 36, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(49, 37, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(50, 38, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL),
(51, 39, '12', '2025-12-25', 'PRESENTE', '', '2025-12-25', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atencion_salud`
--

CREATE TABLE `atencion_salud` (
  `id_atencion` int(11) NOT NULL,
  `id_matricula` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `tipo_atencion` enum('PSICOLOGIA','ENFERMERIA') DEFAULT NULL,
  `motivo_consulta` varchar(255) DEFAULT NULL,
  `diagnostico` varchar(255) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `atencion_salud`
--

INSERT INTO `atencion_salud` (`id_atencion`, `id_matricula`, `id_usuario`, `tipo_atencion`, `motivo_consulta`, `diagnostico`, `observaciones`, `created_at`, `updated_at`) VALUES
(10, 31, 9, 'PSICOLOGIA', 'PROBLEMAS FAMILIARES', 'FAMILIA DISFUNCIONAL', 'TERAPIAS SEMANALES 1 VEZ', '2025-12-25 11:02:46', '0000-00-00 00:00:00'),
(11, 31, 32, 'PSICOLOGIA', 'PROBLEMAS FAMILIARES', 'FAMILIA DISFUNCIONAL', 'TERAPIAS SEMANALES', '2025-12-25 11:03:33', '0000-00-00 00:00:00'),
(12, 33, 32, 'PSICOLOGIA', 'PROBLEMAS CON LA ENAMORADA', 'EMOCIONAL', 'TERAPIA 1 MES', '2025-12-25 11:04:04', '0000-00-00 00:00:00'),
(13, 39, 50, 'ENFERMERIA', 'HERIDA EN EL PIE', 'HERIDA ABIERTA', 'SE LE DIO MEDICAMENTOS', '2025-12-25 11:04:55', '0000-00-00 00:00:00'),
(14, 32, 50, 'ENFERMERIA', 'ROTURA DE CABEZA', 'SANGRADO Y ABERTURA DE FRENTE', 'CURACION Y LAVADO', '2025-12-25 11:05:27', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `Id_aula` int(11) NOT NULL,
  `Grado` varchar(255) DEFAULT NULL,
  `id_nivel_academico` int(11) DEFAULT NULL,
  `id_seccion` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`Id_aula`, `Grado`, `id_nivel_academico`, `id_seccion`, `descripcion`, `estado`, `created_at`, `updated`) VALUES
(4, 'TODOS', 5, 7, 'HACE REFERENCIA A TODOS', 'ACTIVO', '2024-08-04 11:28:59', '0000-00-00 00:00:00'),
(5, '3 AÑOS', 1, 7, 'SALON PARA LOS NIÑOS DE 3 AÑOS', 'ACTIVO', '2024-06-23 11:38:04', '2024-07-16 17:13:05'),
(6, '4 AÑOS', 1, 7, 'SALON PARA LOS NIÑOS DE 4 AÑOS', 'ACTIVO', '2024-07-16 17:12:17', '0000-00-00 00:00:00'),
(7, '5 AÑOS', 1, 7, 'SALON PARA LOS NIÑOS DE 5 AÑOS ULTIMO GRADO DE INICIAL', 'ACTIVO', '2024-07-16 17:12:42', '0000-00-00 00:00:00'),
(8, 'PRIMER GRADO', 2, 7, 'SALON PARA LOS NIÑOS DE 6 AÑOS', 'ACTIVO', '2024-07-16 17:12:54', '0000-00-00 00:00:00'),
(9, 'SEGUNDO GRADO', 2, 7, 'SALON PARA LOS NIÑOS DE 7 AÑOS', 'ACTIVO', '2024-07-16 17:13:16', '0000-00-00 00:00:00'),
(10, 'TERCER GRADO', 2, 7, 'SALON PARA LOS NIÑOS DE 8 AÑOS', 'ACTIVO', '2024-07-16 17:13:30', '0000-00-00 00:00:00'),
(11, 'CUARTO GRADO', 2, 7, 'SALON PARA LOS NIÑOS DE 9 AÑOS', 'ACTIVO', '2024-07-16 17:13:40', '0000-00-00 00:00:00'),
(12, 'QUINTO GRADO', 2, 7, 'SALON PARA LOS NIÑOS DE 10 AÑOS', 'ACTIVO', '2024-07-16 17:13:50', '0000-00-00 00:00:00'),
(13, 'SEXTO GRADO', 2, 7, 'SALON PARA LOS NIÑOS DE 11 AÑOS', 'ACTIVO', '2024-07-16 17:14:02', '0000-00-00 00:00:00'),
(14, 'PRIMER GRADO', 3, 7, 'SALON PARA LOS ADOLESCENTES DE 12 AÑOS', 'ACTIVO', '2024-07-16 17:14:28', '0000-00-00 00:00:00'),
(15, 'SEGUNDO GRADO', 3, 7, 'SALON PARA LOS ADOLESCENTES DE 13 AÑOS', 'ACTIVO', '2024-07-16 17:14:35', '2024-07-16 17:15:22'),
(16, 'TERCER GRADO', 3, 7, 'SALON PARA LOS ADOLESCENTES DE 14 AÑOS', 'ACTIVO', '2024-07-16 17:14:46', '0000-00-00 00:00:00'),
(17, 'CUARTO GRADO', 3, 7, 'SALON PARA LOS ADOLESCENTES DE 15 AÑOS', 'ACTIVO', '2024-07-16 17:14:54', '0000-00-00 00:00:00'),
(18, 'QUINTO GRADO', 3, 7, 'SALON PARA LOS ADOLESCENTES DE 16 AÑOS ES EL ULTIMO AÑO QUE SE CURSA', 'ACTIVO', '2024-07-16 17:15:10', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auxiliar`
--

CREATE TABLE `auxiliar` (
  `auxiliar_id` int(11) NOT NULL,
  `auxiliar_dni` char(8) DEFAULT NULL,
  `auxiliar_nombre` varchar(100) DEFAULT NULL,
  `auxiliar_apepat` varchar(100) DEFAULT NULL,
  `auxiliar_apemat` varchar(100) DEFAULT NULL,
  `auxiliar_sexo` enum('FEMENINO','MASCULINO') DEFAULT NULL,
  `auxiliar_fechanacimiento` date DEFAULT NULL,
  `auxiliar_movil` char(9) DEFAULT NULL,
  `auxiliar_nro_alterno` char(9) DEFAULT NULL,
  `auxiliar_direccion` varchar(255) DEFAULT NULL,
  `auxiliar_tipo_contrato` varchar(255) DEFAULT NULL,
  `auxiliar_estatus` enum('ACTIVO','INACTIVO') NOT NULL,
  `auxiliar_fotoperfil` varchar(255) NOT NULL DEFAULT 'Fotos/admin.png',
  `id_usuario` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `auxiliar`
--

INSERT INTO `auxiliar` (`auxiliar_id`, `auxiliar_dni`, `auxiliar_nombre`, `auxiliar_apepat`, `auxiliar_apemat`, `auxiliar_sexo`, `auxiliar_fechanacimiento`, `auxiliar_movil`, `auxiliar_nro_alterno`, `auxiliar_direccion`, `auxiliar_tipo_contrato`, `auxiliar_estatus`, `auxiliar_fotoperfil`, `id_usuario`, `created_at`, `updated_at`) VALUES
(1, 'JERSSON', NULL, 'CORILLA', 'MIRANDA', NULL, '2023-03-31', '918654046', NULL, 'JR. NICOLAS DE PIEROLA Nº 105', NULL, 'ACTIVO', 'controller/empleado/FOTOS/IMG28-8-2023-11-64.jpg', NULL, NULL, NULL),
(2, 'JOSE', NULL, 'SANCHEZ', 'MEDINA', NULL, '1985-01-24', '926262625', NULL, 'JR. CHALHUANCA N° 222', NULL, 'ACTIVO', 'controller/empleado/FOTOS/IMG28-8-2023-11-195.png', NULL, NULL, NULL),
(3, 'ANDREA', NULL, 'SANCHEZ', 'JIMENEZ', NULL, '1995-01-24', '966226262', NULL, 'AV. DIAZ BARCENAS N° 323', NULL, 'ACTIVO', 'controller/empleado/FOTOS/IMG28-8-2023-11-271.jpeg', NULL, NULL, NULL),
(4, 'LUIS', NULL, 'CAMACHO', 'VELARDE', NULL, '1998-01-24', '926622656', NULL, 'JR. HUANCAVELICA N° 323', NULL, 'ACTIVO', 'controller/empleado/FOTOS/usuario.png', NULL, NULL, NULL),
(5, 'JUAN CAR', NULL, 'MEDINA', 'SANCHEZ', NULL, '2000-07-25', '926161616', NULL, 'JR. CUSCO N° 323', NULL, 'ACTIVO', 'controller/empleado/FOTOS/usuario.png', NULL, NULL, NULL),
(6, 'CELIA', NULL, 'MIRANDA', 'MUNGUIA', NULL, '1972-01-24', '988505521', NULL, 'JR NICOLAS DE PIEROLA N° 15', NULL, 'ACTIVO', 'controller/empleado/FOTOS/IMG29-8-2023-16-940.jpg', NULL, NULL, NULL),
(7, 'WILFREDO', NULL, 'CARRIÓN', 'UMERES', NULL, '1995-05-11', '952541551', NULL, 'AV 28 DE ABRIL 235', NULL, 'ACTIVO', 'controller/empleado/FOTOS/IMG7-9-2023-18-768.jpg', NULL, NULL, NULL),
(8, 'ELIAS', NULL, 'CARRIÓN', 'UMERES', NULL, '1985-05-25', '935951872', NULL, 'AV 28 DE ABRIL N° 234', NULL, 'ACTIVO', 'controller/empleado/FOTOS/IMG10-9-2023-11-957.jpg', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `año_escolar`
--

CREATE TABLE `año_escolar` (
  `Id_año_escolar` int(11) NOT NULL,
  `año_escolar` int(11) DEFAULT NULL,
  `Nombre_año` varchar(255) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `año_escolar`
--

INSERT INTO `año_escolar` (`Id_año_escolar`, `año_escolar`, `Nombre_año`, `fecha_inicio`, `fecha_fin`, `descripcion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 2023, 'AÑO DE LA UNIDAD, LA PAZ Y EL DESARROLLO', '2023-03-13', '2023-12-22', 'BUEN AñO', 'INACTIVO', '2023-02-28 16:59:54', '2024-06-16 17:35:12'),
(2, 2024, 'AñO DEL BICENTENARIO, DE LA CONSOLIDACIóN DE NUESTRA INDEPENDENCIA, Y DE LA CONMEMORACIóN DE LAS HEROICAS BATALLAS DE JUNíN Y AYACUCHO', '2024-03-04', '2024-12-20', 'AñO EN CURSO', 'INACTIVO', '2024-06-16 17:21:43', '2025-12-24 11:01:15'),
(5, 2025, 'AñO DE LA RECUPERACIóN Y CONSOLIDACIóN DE LA ECONOMíA PERUANA', '2026-03-09', '2026-12-11', 'AÑO EN CURSO', 'ACTIVO', '2025-12-24 10:35:45', '2025-12-24 11:01:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados`
--

CREATE TABLE `comunicados` (
  `id_comunicado` int(11) NOT NULL,
  `tipo` enum('GENERAL','POR GRADO') DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `titulo` varchar(1000) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(10000) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comunicados`
--

INSERT INTO `comunicados` (`id_comunicado`, `tipo`, `id_aula`, `titulo`, `descripcion`, `imagen`, `estado`, `id_usuario`, `created_at`, `updated_at`) VALUES
(9, 'POR GRADO', 18, 'REUNION DE CLAUSURA DE AÑO', 'SE INVITA  A TODO LOS PADRES DE FAMILIA PARA REUNION DE CLAUSURA EL 31 DE DICIEMBRE DEL 2025', 'controller/comunicados/fotos/IMG25-12-2025-10-494.png', 'ACTIVO', 9, '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `criterios`
--

CREATE TABLE `criterios` (
  `id_criterio` int(255) NOT NULL,
  `id_detalle_asignatura` int(255) DEFAULT NULL,
  `competencias` varchar(500) DEFAULT NULL,
  `descripción_observa` varchar(500) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `criterios`
--

INSERT INTO `criterios` (`id_criterio`, `id_detalle_asignatura`, `competencias`, `descripción_observa`, `estado`, `created_at`, `updated_at`) VALUES
(1, 20, 'Escribe diversos tipos de texto en su lengua materna', '', 'ACTIVO', '2024-08-19', '2024-08-25 16:35:10'),
(8, 20, 'Lee diversos tipos de texto en su lengua materna', '', 'ACTIVO', '2024-08-25', '0000-00-00 00:00:00'),
(18, 20, 'Se comunica oralmente en su lengua materna', '', 'ACTIVO', '2024-08-25', '0000-00-00 00:00:00'),
(19, 22, 'Resuelve problemas de cantidad', '', 'ACTIVO', '2024-08-25', '0000-00-00 00:00:00'),
(20, 22, 'Resuelve problemas de regularidad, equivalencia y cambio', '', 'ACTIVO', '2024-08-25', '0000-00-00 00:00:00'),
(21, 22, 'Resuelve problemas de forma, movimiento y localización', '', 'ACTIVO', '2024-08-25', '0000-00-00 00:00:00'),
(28, 24, 'avanza con los ejercicios', '', 'ACTIVO', '2024-09-08', '0000-00-00 00:00:00'),
(29, 64, 'Resuelve problemas de cantidad', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(30, 64, 'Resuelve problemas de regularidad, equivalencia y cambio', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(31, 64, 'Resuelve problemas de forma, movimiento y localización', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(32, 64, 'Resuelve problemas de gestión de datos e incertidumbres', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(33, 65, 'Se comunica oralmente en su lengua materna', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(34, 65, 'Lee diversos tipos de texto en su lengua materna', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(35, 65, 'Escribe diversos tipos de texto en su lengua materna', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(36, 66, 'Indaga mediante métodos científicos para construir conocimientos', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(37, 66, 'Explica el mundo fisico basandose en conocimineto sobre los seres vivios, materia y energia, biodive', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(38, 66, 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(39, 67, 'Indaga mediante metodos cientificos para construir conocimientos', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(40, 67, 'Explica el mundo fisico basandose en conocimineto sobre los seres vivios, materia y energia, biodive', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(41, 67, 'Diseña y construye soluciones tecnologicas para resolver problemas de su entorno.', '', 'ACTIVO', '2024-09-10', '0000-00-00 00:00:00'),
(42, 47, 'Resuelve problemas de cantidad', '', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(43, 47, 'Resuelve problemas de regularidad, equivalencia y cambio', '', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(44, 47, 'Resuelve problemas de forma, movimiento y localización', '', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(45, 47, 'Resuelve problemas de gestión de datos e incertidumbres', '', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_asignatura_docente`
--

CREATE TABLE `detalle_asignatura_docente` (
  `Id_detalle_asig_docente` int(11) NOT NULL,
  `Id_asig_docente` int(11) DEFAULT NULL,
  `Id_asignatura` int(11) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_asignatura_docente`
--

INSERT INTO `detalle_asignatura_docente` (`Id_detalle_asig_docente`, `Id_asig_docente`, `Id_asignatura`, `created_at`, `updated_at`) VALUES
(20, 30, 2, '2024-08-01', '0000-00-00 00:00:00.000000'),
(21, 30, 4, '2024-08-01', '0000-00-00 00:00:00.000000'),
(22, 30, 8, '2024-08-01', '2024-08-01 16:37:50.000000'),
(23, 30, 7, '2024-08-01', '2024-08-01 16:38:21.000000'),
(24, 31, 9, '2024-08-02', '0000-00-00 00:00:00.000000'),
(44, 33, 10, '2024-09-04', '0000-00-00 00:00:00.000000'),
(45, 33, 11, '2024-09-04', '0000-00-00 00:00:00.000000'),
(46, 33, 12, '2024-09-04', '0000-00-00 00:00:00.000000'),
(47, 33, 13, '2024-09-04', '0000-00-00 00:00:00.000000'),
(48, 33, 14, '2024-09-04', '0000-00-00 00:00:00.000000'),
(49, 33, 15, '2024-09-04', '0000-00-00 00:00:00.000000'),
(50, 33, 16, '2024-09-04', '0000-00-00 00:00:00.000000'),
(51, 33, 17, '2024-09-04', '0000-00-00 00:00:00.000000'),
(52, 33, 18, '2024-09-04', '0000-00-00 00:00:00.000000'),
(53, 33, 19, '2024-09-04', '0000-00-00 00:00:00.000000'),
(54, 33, 20, '2024-09-04', '0000-00-00 00:00:00.000000'),
(55, 33, 21, '2024-09-04', '0000-00-00 00:00:00.000000'),
(56, 33, 22, '2024-09-04', '0000-00-00 00:00:00.000000'),
(57, 33, 23, '2024-09-04', '0000-00-00 00:00:00.000000'),
(58, 33, 24, '2024-09-04', '0000-00-00 00:00:00.000000'),
(59, 33, 25, '2024-09-04', '0000-00-00 00:00:00.000000'),
(60, 33, 26, '2024-09-04', '0000-00-00 00:00:00.000000'),
(61, 33, 27, '2024-09-04', '0000-00-00 00:00:00.000000'),
(62, 33, 28, '2024-09-04', '0000-00-00 00:00:00.000000'),
(64, 34, 31, '2024-09-10', '0000-00-00 00:00:00.000000'),
(65, 34, 32, '2024-09-10', '0000-00-00 00:00:00.000000'),
(66, 35, 33, '2024-09-10', '0000-00-00 00:00:00.000000'),
(67, 35, 34, '2024-09-10', '0000-00-00 00:00:00.000000'),
(70, 42, 9, '2024-09-23', '0000-00-00 00:00:00.000000'),
(71, 42, 29, '2024-09-23', '0000-00-00 00:00:00.000000'),
(72, 33, 2, '2025-12-25', '2025-12-25 09:53:10.000000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_tarea`
--

CREATE TABLE `detalle_tarea` (
  `id_detalle_tarea` int(11) NOT NULL,
  `id_tarea` char(12) DEFAULT NULL,
  `id_matriculado` int(11) DEFAULT NULL,
  `archivo_evnio_tarea` varchar(255) DEFAULT NULL,
  `calificacion` int(11) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('PENDIENTE','ENVIADO','CALIFICADO') DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_tarea`
--

INSERT INTO `detalle_tarea` (`id_detalle_tarea`, `id_tarea`, `id_matriculado`, `archivo_evnio_tarea`, `calificacion`, `observacion`, `estado`, `fecha_envio`, `created_at`, `updated_at`) VALUES
(42, 'T0000001', 31, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(43, 'T0000001', 32, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(44, 'T0000001', 40, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(45, 'T0000001', 41, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(46, 'T0000002', 33, '', 5, 'NO ENTREGO TAREA', 'CALIFICADO', '0000-00-00 00:00:00', '2025-12-25', '2025-12-25 10:57:58'),
(47, 'T0000002', 34, 'controller/tareas/documentos/tarea_alumnos_1766677709', 15, 'TODO BIEN', 'CALIFICADO', '2025-12-25 10:48:29', '2025-12-25', '0000-00-00 00:00:00'),
(48, 'T0000002', 35, '', 5, 'NO ENTREGO TAREA', 'CALIFICADO', '0000-00-00 00:00:00', '2025-12-25', '2025-12-25 10:57:58'),
(49, 'T0000002', 36, '', 5, 'NO ENTREGO TAREA', 'CALIFICADO', '0000-00-00 00:00:00', '2025-12-25', '2025-12-25 10:57:58'),
(50, 'T0000002', 37, '', 5, 'NO ENTREGO TAREA', 'CALIFICADO', '0000-00-00 00:00:00', '2025-12-25', '2025-12-25 10:57:58'),
(51, 'T0000002', 38, '', 5, 'NO ENTREGO TAREA', 'CALIFICADO', '0000-00-00 00:00:00', '2025-12-25', '2025-12-25 10:57:58'),
(52, 'T0000002', 39, '', 5, 'NO ENTREGO TAREA', 'CALIFICADO', '0000-00-00 00:00:00', '2025-12-25', '2025-12-25 10:57:58'),
(53, 'T0000003', 31, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(54, 'T0000003', 32, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(55, 'T0000003', 40, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(56, 'T0000003', 41, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(57, 'T0000004', 33, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(58, 'T0000004', 34, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(59, 'T0000004', 35, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(60, 'T0000004', 36, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(61, 'T0000004', 37, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(62, 'T0000004', 38, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00'),
(63, 'T0000004', 39, '', 0, '', 'PENDIENTE', '0000-00-00 00:00:00', '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `Id_docente` int(255) NOT NULL,
  `docente_dni` char(8) NOT NULL,
  `docente_nombre` varchar(100) DEFAULT NULL,
  `docente_apelli` varchar(100) DEFAULT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `docente_sexo` enum('FEMENINO','MASCULINO') DEFAULT NULL,
  `docente_fechanacimiento` date DEFAULT NULL,
  `docente_movil` char(9) DEFAULT NULL,
  `docente_nro_alterno` char(9) DEFAULT NULL,
  `docente_direccion` varchar(255) DEFAULT NULL,
  `docente_estatus` enum('ACTIVO','INACTIVO') NOT NULL,
  `docente_fotoperfil` varchar(255) NOT NULL DEFAULT 'Fotos/admin.png',
  `id_asusuario` int(11) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `docentes`
--

INSERT INTO `docentes` (`Id_docente`, `docente_dni`, `docente_nombre`, `docente_apelli`, `especialidad_id`, `docente_sexo`, `docente_fechanacimiento`, `docente_movil`, `docente_nro_alterno`, `docente_direccion`, `docente_estatus`, `docente_fotoperfil`, `id_asusuario`, `created_at`, `updated_at`) VALUES
(1, '12354847', 'JULIAN', 'ALVAREZ', 1, 'MASCULINO', '1996-01-12', '926622626', '988777111', 'JR. CANADA N° 1874', 'ACTIVO', 'controller/docentes/fotos/IMG6-7-2024-15-856.jpg', 10, '2024-07-01', '2024-07-06 15:09:46'),
(2, '15511565', 'YESSICA', 'SALAZAR PEÑA', 5, 'FEMENINO', '2000-12-12', '926626262', '985888777', 'JR. HUANCA N° 448', 'ACTIVO', 'controller/docentes/fotos/IMG6-7-2024-15-971.jpg', 13, '2024-07-01', '2024-07-06 15:08:38'),
(3, '22355447', 'CELIA', 'MIRANDA', 3, 'FEMENINO', '1972-01-24', '988505521', NULL, 'JR NICOLAS DE PIEROLA N° 15', 'ACTIVO', 'controller/empleado/FOTOS/IMG29-8-2023-16-940.jpg', NULL, NULL, NULL),
(4, '25665555', 'ELIAS', 'CARRIÓN', 4, 'MASCULINO', '1985-05-25', '935951872', NULL, 'AV 28 DE ABRIL N° 234', 'ACTIVO', 'controller/empleado/FOTOS/IMG10-9-2023-11-957.jpg', NULL, NULL, NULL),
(5, '26544474', 'ANDREA', 'SANCHEZ', 2, 'FEMENINO', '1995-01-24', '966226262', NULL, 'AV. DIAZ BARCENAS N° 323', 'ACTIVO', 'controller/empleado/FOTOS/IMG28-8-2023-11-271.jpeg', NULL, NULL, NULL),
(6, '26622626', 'JESUS', 'MONTAÑO TAYPE', 5, 'MASCULINO', '1990-01-15', '996262626', '933222111', 'AV. CANADA N° 477', 'ACTIVO', 'controller/docentes/fotos/IMG6-7-2024-15-272.jpg', 20, '2024-07-06', '2024-07-06 15:08:48'),
(8, '55488555', 'JUAN CARLOS', 'MEDINA', 3, 'MASCULINO', '2000-07-25', '926161616', NULL, 'JR. CUSCO N° 323', 'ACTIVO', 'controller/empleado/FOTOS/usuario.png', NULL, NULL, NULL),
(9, '55559744', 'LUIS', 'CAMACHO', 2, 'MASCULINO', '1998-01-24', '926622656', NULL, 'JR. HUANCAVELICA N° 323', 'ACTIVO', 'controller/empleado/FOTOS/usuario.png', NULL, NULL, NULL),
(10, '56151515', 'JOAQUIN', 'PEÑA LOZANO', 5, 'FEMENINO', '1996-01-15', '959296262', '922654785', 'JR. PUNO N° 448', 'ACTIVO', 'controller/docentes/fotos/IMG1-10-2024-14-407.png', 19, '2024-07-06', '2024-07-06 15:08:59'),
(11, '56525414', 'JORGE DANIEL', 'CAMPOS MALPARTIDA', 2, 'MASCULINO', '1998-01-01', '955888888', '952661488', 'JR. CANADA N', 'ACTIVO', 'controller/docentes/fotos/IMG4-7-2024-16-554.jpg', 11, '2024-07-01', '2024-07-06 09:40:53'),
(12, '66262626', 'YENI', 'CHAVEZ LLANCAY', 3, 'FEMENINO', '1995-01-12', '959995929', '', 'AV. CIRCUNVALACIÓN', 'ACTIVO', 'controller/docentes/fotos/IMG4-7-2024-16-890.jpg', 12, '2024-07-01', '2024-07-04 16:33:37'),
(13, '69855524', 'WILFREDO', 'CARRIÓN', 4, 'MASCULINO', '1995-05-11', '952541551', NULL, 'AV 28 DE ABRIL 235', 'ACTIVO', 'controller/empleado/FOTOS/IMG7-9-2023-18-768.jpg', NULL, NULL, NULL),
(14, '72646121', 'JERSSON JORGE', 'CORILLA MIRANDA', 4, 'MASCULINO', '1996-03-15', '918654042', '', 'JR. NICOLAS DE PIEROLA Nº 105', 'ACTIVO', 'controller/docentes/fotos/IMG4-7-2024-16-429.jpg', 9, '2024-07-01', '2024-07-06 10:15:21'),
(15, '26266226', 'CELIA', 'MIRANDA MUNGUIA', 2, 'FEMENINO', '1995-01-15', '926262626', '958777888', 'JR NICOLAS DE PIEROLA N° 477', 'INACTIVO', 'controller/docentes/fotos/IMG6-7-2024-15-616.jpg', 21, '2024-07-06', '2024-08-02 16:09:43'),
(16, '59595959', 'VANESSA', 'CHAVEZ HUAMAN', 3, 'FEMENINO', '1990-01-15', '995995955', '995955959', 'JR. CUSCO N° 477', 'ACTIVO', 'controller/docentes/fotos/', 33, '2024-07-16', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

CREATE TABLE `egresos` (
  `id_egresos` int(11) NOT NULL,
  `id_indicador` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `monto` decimal(5,2) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `estado` enum('VALIDO','ANULADO') DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `fecha_anulacion` date DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `egresos`
--

INSERT INTO `egresos` (`id_egresos`, `id_indicador`, `id_user`, `cantidad`, `monto`, `observacion`, `estado`, `motivo_anulacion`, `fecha_anulacion`, `created_at`, `updated`) VALUES
(1, 2, 9, 2, 350.00, 'PAGO DE LA LUZ DE MES DE AGOSTO Y SETIEMBRE', 'VALIDO', NULL, NULL, '2024-10-08', '2024-10-08 00:00:00'),
(2, 2, 9, 1, 180.00, NULL, 'VALIDO', NULL, NULL, '2024-10-07', NULL),
(3, 3, 9, 1, 250.00, 'SE PAGO EL RECIBO DE AGUA', 'ANULADO', 'ANULADO POR MALA DIGITACIÓN', '2024-10-08', '2024-10-08', NULL),
(4, 2, 9, 2, 999.99, '', 'VALIDO', NULL, NULL, '2025-12-25', NULL),
(5, 3, 9, 1, 222.00, '', 'VALIDO', NULL, NULL, '2025-12-25', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `empresa_id` int(11) NOT NULL,
  `emp_razon` varchar(250) NOT NULL,
  `emp_email` varchar(250) NOT NULL,
  `emp_cod` varchar(10) NOT NULL,
  `emp_telefono` varchar(20) NOT NULL,
  `emp_direccion` varchar(250) NOT NULL,
  `emp_logo` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`empresa_id`, `emp_razon`, `emp_email`, `emp_cod`, `emp_telefono`, `emp_direccion`, `emp_logo`, `created_at`, `updated_at`) VALUES
(1, 'SEDES SAPIENTIAE', 'CONTACTO@GMAIL.COM', '3434', '946 701 820', 'JR MAYTA CAPAC S/N ', 'controller/empresa/FOTOS/IMG28-9-2024-11-637.jpeg', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad`
--

CREATE TABLE `especialidad` (
  `Id_especilidad` int(11) NOT NULL,
  `Especialidad` varchar(100) DEFAULT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `Estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidad`
--

INSERT INTO `especialidad` (`Id_especilidad`, `Especialidad`, `Descripcion`, `Estado`, `created_at`, `updated_at`) VALUES
(1, 'MATEMATICO', 'DOCENTES EPECIALISTAS EN NUMEROS Y RM', 'ACTIVO', '2024-06-16 15:02:54', '2024-06-16 16:11:23'),
(2, 'COMUNICACION', 'DOCENTES ESPECIALISTAS EN LETRAS', 'ACTIVO', '2024-06-16 15:28:03', '0000-00-00 00:00:00'),
(3, 'QUIMICO', 'ESPCIALISTA EN TODO SOBRE QUIMICA', 'ACTIVO', '2024-06-16 15:30:01', '0000-00-00 00:00:00'),
(4, 'FISICO', 'ESPECIALISTA EN FISICA Y NUMEROS', 'ACTIVO', '2024-06-16 15:30:45', '0000-00-00 00:00:00'),
(5, 'DANZA', 'ESPECIALISTA EN DANZA Y MUSICA', 'ACTIVO', '2024-06-16 15:31:15', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen`
--

CREATE TABLE `examen` (
  `id_examen` char(12) NOT NULL,
  `id_detalle_asignatura` int(11) DEFAULT NULL,
  `tema_examen` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_examen` datetime DEFAULT NULL,
  `estado` enum('PENDIENTE','REALIZADO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `doc_ncorrelativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen`
--

INSERT INTO `examen` (`id_examen`, `id_detalle_asignatura`, `tema_examen`, `descripcion`, `fecha_examen`, `estado`, `created_at`, `updated_at`, `doc_ncorrelativo`) VALUES
('D0000001', 47, 'FUNCIONES', 'ESTUDIAR PARA EL EXAMEN', '2025-12-29 09:30:30', 'REALIZADO', '2025-12-25', '0000-00-00 00:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id_horario` int(11) NOT NULL,
  `id_hora_aula` int(11) DEFAULT NULL,
  `id_detalle_asig_docente` int(11) DEFAULT NULL,
  `dia` enum('LUNES','MARTES','MIERCOLES','JUEVES','VIERNES') DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id_horario`, `id_hora_aula`, `id_detalle_asig_docente`, `dia`, `estado`, `created_at`, `updated_at`) VALUES
(72, 41, 20, 'LUNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(73, 42, 21, 'LUNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(74, 43, 23, 'LUNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(75, 44, 22, 'LUNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(76, 41, 21, 'MARTES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(77, 42, 20, 'MARTES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(78, 43, 22, 'MARTES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(79, 44, 20, 'MARTES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(80, 41, 20, 'MIERCOLES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(81, 42, 23, 'MIERCOLES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(82, 43, 21, 'MIERCOLES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(83, 44, 22, 'MIERCOLES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(84, 41, 20, 'JUEVES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(85, 42, 20, 'JUEVES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(86, 43, 22, 'JUEVES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(87, 44, 22, 'JUEVES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(88, 41, 22, 'VIERNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(89, 42, 21, 'VIERNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(90, 43, 21, 'VIERNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(91, 44, 22, 'VIERNES', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(92, 62, 44, 'LUNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(93, 63, 46, 'LUNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(94, 64, 47, 'LUNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(95, 65, 49, 'LUNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(96, 66, 50, 'LUNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(97, 62, 53, 'MARTES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(98, 63, 54, 'MARTES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(99, 64, 56, 'MARTES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(100, 65, 57, 'MARTES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(101, 66, 57, 'MARTES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(102, 62, 59, 'MIERCOLES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(103, 63, 61, 'MIERCOLES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(104, 64, 61, 'MIERCOLES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(105, 65, 59, 'MIERCOLES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(106, 66, 59, 'MIERCOLES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(107, 62, 45, 'JUEVES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(108, 63, 47, 'JUEVES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(109, 64, 49, 'JUEVES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(110, 65, 53, 'JUEVES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(111, 66, 55, 'JUEVES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(112, 62, 46, 'VIERNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(113, 63, 49, 'VIERNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(114, 64, 44, 'VIERNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(115, 65, 53, 'VIERNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(116, 66, 57, 'VIERNES', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horas_aula`
--

CREATE TABLE `horas_aula` (
  `id_hora` int(11) NOT NULL,
  `id_año_academico` int(11) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `turno` enum('MAÑANA','TARDE') DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horas_aula`
--

INSERT INTO `horas_aula` (`id_hora`, `id_año_academico`, `id_aula`, `turno`, `hora_inicio`, `hora_fin`, `estado`, `created_at`, `updated_at`) VALUES
(41, 5, 5, 'MAÑANA', '08:00:00', '09:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(42, 5, 5, 'MAÑANA', '10:00:00', '11:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(43, 5, 5, 'MAÑANA', '11:00:00', '12:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(44, 5, 5, 'MAÑANA', '12:00:00', '13:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(45, 5, 8, 'MAÑANA', '07:45:00', '08:45:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(46, 5, 8, 'MAÑANA', '08:45:00', '09:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(47, 5, 8, 'MAÑANA', '09:30:00', '10:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(48, 5, 8, 'MAÑANA', '11:00:00', '12:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(49, 5, 8, 'MAÑANA', '12:00:00', '13:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(50, 5, 14, 'MAÑANA', '07:30:00', '08:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(51, 5, 14, 'MAÑANA', '08:30:00', '09:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(52, 5, 14, 'MAÑANA', '09:30:00', '10:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(53, 5, 14, 'MAÑANA', '11:00:00', '12:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(54, 5, 14, 'MAÑANA', '12:00:00', '13:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(55, 5, 14, 'MAÑANA', '13:00:00', '14:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(56, 5, 15, 'MAÑANA', '07:30:00', '08:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(57, 5, 15, 'MAÑANA', '08:30:00', '09:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(58, 5, 15, 'MAÑANA', '09:30:00', '10:30:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(59, 5, 15, 'MAÑANA', '11:00:00', '12:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(60, 5, 15, 'MAÑANA', '12:00:00', '13:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(61, 5, 15, 'MAÑANA', '13:00:00', '14:00:00', 'ACTIVO', '2025-12-24', '0000-00-00 00:00:00'),
(62, 5, 18, 'MAÑANA', '07:30:00', '08:30:00', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(63, 5, 18, 'MAÑANA', '08:30:00', '09:30:00', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(64, 5, 18, 'MAÑANA', '09:30:00', '10:30:00', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(65, 5, 18, 'MAÑANA', '11:00:00', '12:00:00', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00'),
(66, 5, 18, 'MAÑANA', '13:00:00', '14:00:00', 'ACTIVO', '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `indicadores`
--

CREATE TABLE `indicadores` (
  `id_indicadores` int(11) NOT NULL,
  `tipo_indicador` enum('GASTOS','INGRESOS') DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `indicadores`
--

INSERT INTO `indicadores` (`id_indicadores`, `tipo_indicador`, `nombre`, `descripcion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'INGRESOS', 'PENSIONES  Y OTROS', 'INGRESOS POR MARTE DE LOS ESTUDIANTES', 'ACTIVO', '2024-10-07', '0000-00-00 00:00:00'),
(2, 'GASTOS', 'PAGO DE LA LUZ', 'SERVICIO QUE SE PAGA CADA MES', 'ACTIVO', '2024-10-06', '0000-00-00 00:00:00'),
(3, 'GASTOS', 'PAGO DE RECIBO AGUA', 'ESTE INDICADOR ES UN SERVICIO', 'ACTIVO', '2024-10-06', '2024-10-06 12:12:41'),
(4, 'INGRESOS', 'VENTA DE UNIFORMES', 'ESTO ES UN INGRESO POR VENTA DE UNIFORMES', 'ACTIVO', '2024-10-06', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `id_ingreso` int(11) NOT NULL,
  `id_pago_pension` int(11) DEFAULT NULL,
  `id_indicador` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `monto` decimal(5,2) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `estado` enum('VALIDO','ANULADO') DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `fecha_anulacion` date DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingresos`
--

INSERT INTO `ingresos` (`id_ingreso`, `id_pago_pension`, `id_indicador`, `id_user`, `cantidad`, `monto`, `observacion`, `estado`, `motivo_anulacion`, `fecha_anulacion`, `created_at`, `updated`) VALUES
(26, NULL, 1, 9, 1, 0.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(27, NULL, 1, 9, 1, 0.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(28, NULL, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(29, NULL, 1, 9, 1, 0.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(30, NULL, 1, 9, 1, 0.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(31, NULL, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(32, 108, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(33, 108, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(34, 108, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(35, 111, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(36, 111, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(37, 111, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(38, 114, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(39, 114, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(40, 114, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(41, 117, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(42, 117, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(43, 117, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(44, 120, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(45, 120, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(46, 120, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(47, 123, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(48, 123, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(49, 123, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(50, 126, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(51, 126, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(52, 126, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(53, 129, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(54, 129, 1, 9, 1, 150.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(55, 129, 1, 9, 1, 150.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(56, 132, 1, 9, 1, 100.00, 'ADMISION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(57, 132, 1, 9, 1, 80.00, 'ALUMNO NUEVO', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(58, 132, 1, 9, 1, 100.00, 'MATRICULA', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(59, 133, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(60, 134, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(61, 135, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(62, 136, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(63, 137, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(64, 138, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(65, 139, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(66, 140, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(67, 141, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(68, 142, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(69, 143, 1, 9, 1, 100.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(70, 144, 1, 9, 1, 100.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(71, 145, 1, 9, 1, 100.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(72, 146, 1, 9, 1, 100.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(73, 147, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(74, 148, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(77, 151, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(78, 152, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(79, 153, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(80, 154, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(81, 155, 1, 9, 1, 150.00, 'PENSION', 'VALIDO', '', '0000-00-00', '2025-12-25', '0000-00-00 00:00:00'),
(82, NULL, 4, 9, 1, 150.00, '', 'VALIDO', NULL, NULL, '2025-12-25', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_eventos_alumnos`
--

CREATE TABLE `log_eventos_alumnos` (
  `id` int(11) NOT NULL,
  `evento` varchar(100) NOT NULL,
  `anio` int(11) NOT NULL,
  `fecha_ejecucion` datetime NOT NULL,
  `alumnos_afectados` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matricula`
--

CREATE TABLE `matricula` (
  `id_matricula` int(11) NOT NULL,
  `id_alumno` int(11) DEFAULT NULL,
  `id_año` int(11) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `pago_admi` decimal(5,2) DEFAULT NULL,
  `pago_alu_nuevo` decimal(5,2) DEFAULT NULL,
  `pago_matricula` decimal(5,2) DEFAULT NULL,
  `procedencia_colegio` varchar(255) DEFAULT NULL,
  `provincia` varchar(255) DEFAULT NULL,
  `departamento` varchar(255) DEFAULT NULL,
  `usu_id` int(11) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `matricula`
--

INSERT INTO `matricula` (`id_matricula`, `id_alumno`, `id_año`, `id_aula`, `pago_admi`, `pago_alu_nuevo`, `pago_matricula`, `procedencia_colegio`, `provincia`, `departamento`, `usu_id`, `created_at`, `updated_at`) VALUES
(31, 6, 5, 5, 0.00, 0.00, 150.00, '', '', '', NULL, '2025-12-25', '2025-12-25 10:07:27'),
(32, 7, 5, 5, 0.00, 0.00, 150.00, '', '', '', NULL, '2025-12-25', '2025-12-25 10:08:00'),
(33, 9, 5, 18, 100.00, 150.00, 150.00, 'LA SALLE', 'ABANCAY', 'APURIMAC', 55, '2025-12-25', '2025-12-25 10:09:18'),
(34, 10, 5, 18, 100.00, 150.00, 150.00, 'LA SALLE', 'ABANCAY', 'APURIMAC', 56, '2025-12-25', '2025-12-25 10:09:45'),
(35, 11, 5, 18, 100.00, 150.00, 150.00, 'AMERICA', 'ABANCAY', 'APURIMAC', 57, '2025-12-25', '2025-12-25 10:11:26'),
(36, 12, 5, 18, 100.00, 150.00, 150.00, 'ROSARIO', 'ABANCAY', 'APURIMAC', 58, '2025-12-25', '2025-12-25 10:12:12'),
(37, 13, 5, 18, 100.00, 150.00, 150.00, 'ROSARIO', 'ABANCAY', 'APURIMAC', 59, '2025-12-25', '2025-12-25 10:12:57'),
(38, 14, 5, 18, 100.00, 150.00, 150.00, 'PITAGORAS', 'CUSCO', 'CUSCO', 60, '2025-12-25', '2025-12-25 10:16:01'),
(39, 15, 5, 18, 100.00, 150.00, 150.00, 'PITAGORAS', 'CUSCO', 'CUSCO', 61, '2025-12-25', '2025-12-25 10:16:07'),
(40, 16, 5, 5, 100.00, 150.00, 150.00, 'AURORA', 'ABANCAY', 'APURIMAC', 62, '2025-12-25', '2025-12-25 10:17:07'),
(41, 17, 5, 5, 100.00, 80.00, 100.00, '', '', '', 63, '2025-12-25', '2025-12-25 10:22:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivel_academico`
--

CREATE TABLE `nivel_academico` (
  `Id_nivel` int(11) NOT NULL,
  `Nivel_academico` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `nivel_academico`
--

INSERT INTO `nivel_academico` (`Id_nivel`, `Nivel_academico`, `descripcion`, `estado`, `create_at`, `updated_at`) VALUES
(1, 'INICIAL', 'PARA NIÑOS DE 3 A 5 AÑOS SOLO 3 GRADOS1', 'ACTIVO', '2024-06-17 14:56:38', '2025-12-13 13:16:26'),
(2, 'PRIMARIA', 'CONSTA DE 6 GRADOS ACADEMICO Y ESTA CONSTITUIDO POR ALUMNOS DESDE LOS 6 HASTA LOS 11 AÑOS', 'ACTIVO', '2024-06-17 15:11:26', '2024-06-17 15:34:50'),
(3, 'SECUNDARIA', 'CONSTA DE 5 NIVELES DE GRADO Y SON PARA ALUMNOS QUE ESTAN EN LA ETAPA DE LA PUBERTAD Y DESARROLLO DE 12 A 16 AÑOS', 'ACTIVO', '2024-06-17 15:12:08', '0000-00-00 00:00:00'),
(5, 'TODOS', 'GENERAL', 'ACTIVO', '2024-07-20 10:50:19', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `id_nota_bole` int(255) NOT NULL,
  `id_matricula` int(11) DEFAULT NULL,
  `id_bimestre` int(11) DEFAULT NULL,
  `id_criterio` int(255) DEFAULT NULL,
  `nota` char(5) DEFAULT NULL,
  `conclusiones` varchar(255) DEFAULT NULL,
  `creared_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notas`
--

INSERT INTO `notas` (`id_nota_bole`, `id_matricula`, `id_bimestre`, `id_criterio`, `nota`, `conclusiones`, `creared_at`, `updated_at`) VALUES
(235, 34, 44, 42, '12', '', '2025-12-25', NULL),
(236, 34, 44, 43, '15', '', '2025-12-25', NULL),
(237, 34, 44, 44, '15', '', '2025-12-25', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_padre`
--

CREATE TABLE `notas_padre` (
  `id_nota_papa` int(11) NOT NULL,
  `id_matricula` int(11) DEFAULT NULL,
  `id_bimestre` int(11) DEFAULT NULL,
  `criterio` varchar(255) DEFAULT NULL,
  `nota` char(5) DEFAULT NULL,
  `creared_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notas_padre`
--

INSERT INTO `notas_padre` (`id_nota_papa`, `id_matricula`, `id_bimestre`, `criterio`, `nota`, `creared_at`, `updated_at`) VALUES
(165, 34, 44, 'Asiste y participa activamente en actividades y reuniones del Aula', 'A', '2025-12-25', '2025-12-25 10:55:28'),
(166, 34, 44, 'Acude regularmente al colegio en el horario de atencion a padres de familia, para dialogar con los docentes sobre el rendimiento academico y la conducta de su hijo. (a)', 'A', '2025-12-25', '2025-12-25 10:55:28'),
(167, 34, 44, 'Cumple con las cuotas de pension.', 'A', '2025-12-25', '2025-12-25 10:55:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres`
--

CREATE TABLE `padres` (
  `id_papas` int(11) NOT NULL,
  `id_alu` int(11) NOT NULL,
  `Dni_papa` char(8) DEFAULT NULL,
  `Datos_papa` varchar(255) DEFAULT NULL,
  `Celular_papa` char(255) DEFAULT NULL,
  `Dni_mama` char(8) DEFAULT NULL,
  `Datos_mama` varchar(255) DEFAULT NULL,
  `Celular_mama` char(255) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `padres`
--

INSERT INTO `padres` (`id_papas`, `id_alu`, `Dni_papa`, `Datos_papa`, `Celular_papa`, `Dni_mama`, `Datos_mama`, `Celular_mama`, `created_at`, `updated_at`) VALUES
(6, 6, '66262162', 'CARLOS CAMACHO PERALES', '995259229', '84844848', 'ANDREA PERALTA CHAVEZ', '926626262', '2024-09-10', '2025-12-25 10:02:49'),
(7, 7, '', '', '', '48484848', 'YESSICA DAVILA PACHECO', '926622626', '2024-09-10', '2025-12-25 10:03:02'),
(9, 9, '23232332', 'JUAN QUIÑONES', '', '23233223', 'ADRIANA SANCHEZ', '985747444', '2025-12-25', '0000-00-00 00:00:00'),
(10, 10, '', '', '', '12333223', 'ANDREA PEÑA', '921511515', '2025-12-25', '0000-00-00 00:00:00'),
(11, 11, '', '', '', '23232323', 'DANIELA CHAVEZ HUAMAN', '991151515', '2025-12-25', '0000-00-00 00:00:00'),
(12, 12, '', '', '', '51515151', 'ESTEFANY PERALTA', '949445515', '2025-12-25', '0000-00-00 00:00:00'),
(13, 13, '', '', '', '52515155', 'JUANA PANIAGUA', '992922622', '2025-12-25', '0000-00-00 00:00:00'),
(14, 14, '22332322', 'DANIEL DAVILA', '921151515', '33223233', 'ANDREA PEREZ', '911515512', '2025-12-25', '0000-00-00 00:00:00'),
(15, 15, '', '', '', '15515115', 'ANDREA SANCHEZ DAVILA', '921551511', '2025-12-25', '0000-00-00 00:00:00'),
(16, 16, '', '', '', '51151515', 'DANIELA VERA', '926661661', '2025-12-25', '0000-00-00 00:00:00'),
(17, 17, '', '', '', '51151551', 'CELIA PEÑA', '941511515', '2025-12-25', '0000-00-00 00:00:00'),
(18, 18, '32232323', 'JUAN PERALTA', '921515515', '23232332', 'SHAMELY CARRION', '932220220', '2025-12-25', '0000-00-00 00:00:00'),
(19, 19, '', '', '', '42334343', 'DANIELA VALENZUELA', '941551511', '2025-12-25', '0000-00-00 00:00:00'),
(20, 20, '', '', '', '11551511', 'ANDREA DAMIAN', '915511515', '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_pensiones`
--

CREATE TABLE `pago_pensiones` (
  `id_pago_pension` int(11) NOT NULL,
  `id_matri` int(11) DEFAULT NULL,
  `concepto` enum('ADMISION','ALUMNO NUEVO','MATRICULA','PENSION') DEFAULT NULL,
  `id_pension` int(11) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `sub_total` decimal(5,2) DEFAULT NULL,
  `motivo_edicion` varchar(255) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago_pensiones`
--

INSERT INTO `pago_pensiones` (`id_pago_pension`, `id_matri`, `concepto`, `id_pension`, `fecha_pago`, `sub_total`, `motivo_edicion`, `created_at`, `updated_at`) VALUES
(100, 31, 'ADMISION', NULL, '2025-12-25', 0.00, NULL, '2025-12-25', '2025-12-25 10:07:27'),
(101, 31, 'ALUMNO NUEVO', NULL, '2025-12-25', 0.00, NULL, '2025-12-25', '2025-12-25 10:07:27'),
(102, 31, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:07:27'),
(103, 32, 'ADMISION', NULL, '2025-12-25', 0.00, NULL, '2025-12-25', '2025-12-25 10:08:00'),
(104, 32, 'ALUMNO NUEVO', NULL, '2025-12-25', 0.00, NULL, '2025-12-25', '2025-12-25 10:08:00'),
(105, 32, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:08:00'),
(106, 33, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:09:18'),
(107, 33, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:09:18'),
(108, 33, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:09:18'),
(109, 34, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:09:45'),
(110, 34, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:09:45'),
(111, 34, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:09:45'),
(112, 35, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:11:26'),
(113, 35, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:11:26'),
(114, 35, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:11:26'),
(115, 36, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:12:12'),
(116, 36, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:12:12'),
(117, 36, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:12:12'),
(118, 37, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:12:57'),
(119, 37, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:12:57'),
(120, 37, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:12:57'),
(121, 38, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:15:02'),
(122, 38, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:15:02'),
(123, 38, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:15:02'),
(124, 39, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:15:37'),
(125, 39, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:15:37'),
(126, 39, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:15:37'),
(127, 40, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:17:07'),
(128, 40, 'ALUMNO NUEVO', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:17:07'),
(129, 40, 'MATRICULA', NULL, '2025-12-25', 150.00, NULL, '2025-12-25', '2025-12-25 10:17:07'),
(130, 41, 'ADMISION', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:22:22'),
(131, 41, 'ALUMNO NUEVO', NULL, '2025-12-25', 80.00, NULL, '2025-12-25', '2025-12-25 10:22:22'),
(132, 41, 'MATRICULA', NULL, '2025-12-25', 100.00, NULL, '2025-12-25', '2025-12-25 10:22:22'),
(133, 38, 'PENSION', 56, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(134, 38, 'PENSION', 57, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(135, 38, 'PENSION', 58, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(136, 38, 'PENSION', 59, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(137, 38, 'PENSION', 60, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(138, 38, 'PENSION', 61, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(139, 38, 'PENSION', 62, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(140, 38, 'PENSION', 63, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(141, 38, 'PENSION', 64, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(142, 38, 'PENSION', 65, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(143, 32, 'PENSION', 36, '2025-12-25', 100.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(144, 32, 'PENSION', 37, '2025-12-25', 100.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(145, 32, 'PENSION', 38, '2025-12-25', 100.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(146, 32, 'PENSION', 39, '2025-12-25', 100.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(147, 35, 'PENSION', 56, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(148, 35, 'PENSION', 57, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(151, 37, 'PENSION', 56, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(152, 37, 'PENSION', 57, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(153, 37, 'PENSION', 58, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(154, 37, 'PENSION', 59, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00'),
(155, 37, 'PENSION', 60, '2025-12-25', 150.00, NULL, '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pensiones`
--

CREATE TABLE `pensiones` (
  `id_pensiones` int(11) NOT NULL,
  `id_nivel_academico` int(11) DEFAULT NULL,
  `mes` enum('ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `precio` decimal(5,2) DEFAULT NULL,
  `mora` decimal(5,2) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pensiones`
--

INSERT INTO `pensiones` (`id_pensiones`, `id_nivel_academico`, `mes`, `fecha_vencimiento`, `precio`, `mora`, `created_at`, `updated_at`) VALUES
(36, 1, 'MARZO', '2026-03-31', 100.00, 10.00, '2025-12-25', '2025-12-25 11:43:03'),
(37, 1, 'ABRIL', '2025-04-30', 100.00, 10.00, '2025-12-25', '2025-12-25 10:28:34'),
(38, 1, 'MAYO', '2025-05-30', 100.00, 10.00, '2025-12-25', '2025-12-25 10:28:50'),
(39, 1, 'JUNIO', '2025-06-27', 100.00, 10.00, '2025-12-25', '2025-12-25 10:29:01'),
(40, 1, 'JULIO', '2025-07-31', 100.00, 10.00, '2025-12-25', '2025-12-25 10:29:12'),
(41, 1, 'AGOSTO', '2025-08-29', 100.00, 10.00, '2025-12-25', '2025-12-25 10:29:22'),
(42, 1, 'SEPTIEMBRE', '2025-09-30', 100.00, 10.00, '2025-12-25', '2025-12-25 10:29:37'),
(43, 1, 'OCTUBRE', '2025-10-30', 100.00, 10.00, '2025-12-25', '2025-12-25 10:29:45'),
(44, 1, 'NOVIEMBRE', '2025-11-28', 100.00, 10.00, '2025-12-25', '2025-12-25 10:29:54'),
(45, 1, 'DICIEMBRE', '2024-12-20', 100.00, 10.00, '2025-12-25', '2025-12-25 10:32:19'),
(46, 2, 'MARZO', '2025-03-31', 120.00, 15.00, '2025-12-25', '2025-12-25 10:30:19'),
(47, 2, 'ABRIL', '2025-04-30', 120.00, 15.00, '2025-12-25', '2025-12-25 10:30:36'),
(48, 2, 'MAYO', '2025-05-30', 120.00, 15.00, '2025-12-25', '2025-12-25 10:30:49'),
(49, 2, 'JUNIO', '2025-06-30', 120.00, 15.00, '2025-12-25', '2025-12-25 10:31:07'),
(50, 2, 'JULIO', '2025-07-31', 120.00, 15.00, '2025-12-25', '2025-12-25 10:31:16'),
(51, 2, 'AGOSTO', '2025-08-29', 120.00, 15.00, '2025-12-25', '2025-12-25 10:31:28'),
(52, 2, 'SEPTIEMBRE', '2025-09-30', 120.00, 15.00, '2025-12-25', '2025-12-25 10:31:38'),
(53, 2, 'OCTUBRE', '2025-10-30', 120.00, 15.00, '2025-12-25', '2025-12-25 10:31:59'),
(54, 2, 'NOVIEMBRE', '2025-11-28', 120.00, 15.00, '2025-12-25', '2025-12-25 10:32:11'),
(55, 2, 'DICIEMBRE', '2026-12-31', 120.00, 15.00, '2025-12-25', '0000-00-00 00:00:00'),
(56, 3, 'MARZO', '2026-03-31', 150.00, 20.00, '2025-12-25', '2025-12-25 11:43:47'),
(57, 3, 'ABRIL', '2025-04-30', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(58, 3, 'MAYO', '2025-05-30', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(59, 3, 'JUNIO', '2025-06-30', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(60, 3, 'JULIO', '2025-07-31', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(61, 3, 'AGOSTO', '2025-08-29', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(62, 3, 'SEPTIEMBRE', '2025-09-30', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(63, 3, 'OCTUBRE', '2025-10-31', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(64, 3, 'NOVIEMBRE', '2025-11-28', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00'),
(65, 3, 'DICIEMBRE', '2025-12-31', 150.00, 20.00, '2025-12-25', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id_periodo` int(255) NOT NULL,
  `id_año_escolar` int(255) DEFAULT NULL,
  `tipo_perido` enum('BIMESTRE','TRIMESTRE','CUATRIMESTRE','SEMESTRE') DEFAULT NULL,
  `periodos` varchar(255) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('EN CURSO','FINALIZADO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id_periodo`, `id_año_escolar`, `tipo_perido`, `periodos`, `fecha_inicio`, `fecha_fin`, `estado`, `created_at`, `updated_at`) VALUES
(12, 2, 'BIMESTRE', 'I BIMESTRE', '2024-03-01', '2024-05-31', 'EN CURSO', '2024-08-26', '0000-00-00 00:00:00'),
(13, 2, 'BIMESTRE', 'II BIMESTRE', '2024-06-01', '2024-07-31', 'EN CURSO', '2024-08-26', '0000-00-00 00:00:00'),
(14, 2, 'BIMESTRE', 'III BIMESTRE', '2024-08-01', '2024-09-30', 'EN CURSO', '2024-08-26', '0000-00-00 00:00:00'),
(39, 2, 'BIMESTRE', 'IV BIMESTRE', '2024-10-01', '2024-12-25', 'EN CURSO', '2024-08-27', '2024-08-27 16:06:24'),
(40, 5, 'BIMESTRE', 'I BIMESTRE', '2026-03-09', '2026-05-15', 'EN CURSO', '2025-12-24', '2025-12-24 10:38:15'),
(41, 5, 'BIMESTRE', 'II BIMESTRE', '2026-05-18', '2026-07-31', 'EN CURSO', '2025-12-24', '2025-12-24 10:38:15'),
(42, 5, 'BIMESTRE', 'III BIMESTRE', '2026-08-03', '2026-10-16', 'EN CURSO', '2025-12-24', '2025-12-24 10:38:15'),
(44, 5, 'BIMESTRE', 'IV BIMESTRE', '2025-12-01', '2025-12-31', 'EN CURSO', '2025-12-25', '2025-12-25 10:50:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_admi`
--

CREATE TABLE `personal_admi` (
  `personal_adm_id` int(11) NOT NULL,
  `personal_adm_dni` char(8) DEFAULT NULL,
  `personal_adm_nombre` varchar(100) DEFAULT NULL,
  `personal_adm_apellido` varchar(100) DEFAULT NULL,
  `personal_adm_tipo` varchar(20) DEFAULT NULL,
  `personal_adm_sexo` enum('FEMENINO','MASCULINO') DEFAULT NULL,
  `personal_adm_fechanacimiento` date DEFAULT NULL,
  `personal_adm_movil` char(9) DEFAULT NULL,
  `personal_adm_nro_alterno` char(9) DEFAULT NULL,
  `personal_adm_direccion` varchar(255) DEFAULT NULL,
  `personal_adm_estatus` enum('ACTIVO','INACTIVO') NOT NULL,
  `personal_adm_fotoperfil` varchar(255) NOT NULL DEFAULT 'Fotos/admin.png',
  `id_ausuario` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `personal_admi`
--

INSERT INTO `personal_admi` (`personal_adm_id`, `personal_adm_dni`, `personal_adm_nombre`, `personal_adm_apellido`, `personal_adm_tipo`, `personal_adm_sexo`, `personal_adm_fechanacimiento`, `personal_adm_movil`, `personal_adm_nro_alterno`, `personal_adm_direccion`, `personal_adm_estatus`, `personal_adm_fotoperfil`, `id_ausuario`, `created_at`, `updated_at`) VALUES
(1, '65248747', 'MANUEL', 'FARFAN ARANDO', 'AUXILIAR', 'MASCULINO', '1990-12-12', '987777888', '985412339', 'JR. CUSCO S/N', 'ACTIVO', 'controller/personal_administrativo/fotos/IMG6-7-2024-15-242.jpg', 22, '2024-07-06 12:04:59', '2024-07-06 15:08:09'),
(4, '11551151', 'YESSICA', 'CHAVEZ ANDRADA', 'PSICOLOGA', 'FEMENINO', '1998-12-12', '995959559', '955926265', 'JR. ARICA N° 544', 'ACTIVO', 'controller/personal_administrativo/fotos/IMG6-7-2024-15-32.jpg', 32, '2024-07-06 14:47:59', '2024-07-06 15:08:17'),
(5, '95959599', 'ADRIANA', 'CAMACHO VALER', 'ENFERMERA', 'FEMENINO', '1990-07-15', '992929929', '929262222', 'AV. CUSCO N° 477', 'ACTIVO', 'controller/personal_administrativo/fotos/IMG1-8-2024-15-52.webp', 50, '2024-08-01 15:25:03', '2024-08-01 15:26:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `Id_rol` int(11) NOT NULL,
  `tipo_rol` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`Id_rol`, `tipo_rol`, `descripcion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'ESTUDIANTE', 'ROL QUE DA PERMISOS A ESTUDIANTE', 'ACTIVO', '2024-06-16 16:14:00', '2024-06-16 16:35:54'),
(2, 'DOCENTE', 'PERMISOS QUE EL DOCENTE TENDRA', 'ACTIVO', '2024-06-16 16:25:52', '0000-00-00 00:00:00'),
(3, 'AUXILIAR', 'EL AUXILIAR ES LA PERSONA QUE REGISTRA LAS ASISTENCIAS', 'ACTIVO', '2024-06-16 16:36:18', '0000-00-00 00:00:00'),
(4, 'ENFERMERA', 'PERSONA ENCARGADA DE LOS PRIMEROS AUXILIOS Y TRATAR EN ALGUN ACCIDENTE AL PERSONAL DEL COLEGIO', 'ACTIVO', '2024-06-16 16:37:09', '0000-00-00 00:00:00'),
(5, 'PSICOLOGA', 'PERSONA ENCARGADA DE LA SALUD MENTAL DE LOS ESTUDIANTES', 'ACTIVO', '2024-06-16 16:37:28', '0000-00-00 00:00:00'),
(9, 'ADMINISTRADOR', 'ES LA PERSONA QUE TIENE ACCESO A TODAS LA FUNCIONALIDADES DEL SISTEMA EN ESTE CASO PUEDE SER EL DIRECTOR DEL COLEGIO', 'ACTIVO', '2024-06-16 16:45:12', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion`
--

CREATE TABLE `seccion` (
  `seccion_id` int(11) NOT NULL COMMENT 'Codigo auto-incrementado del movimiento del area',
  `seccion_nombre` varchar(255) NOT NULL COMMENT 'nombre del area',
  `seccion_descripcion` text DEFAULT NULL,
  `seccion_estado` enum('ACTIVO','INACTIVO') NOT NULL COMMENT 'estado del area',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'fecha del registro del movimiento',
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Entidad Area' ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `seccion`
--

INSERT INTO `seccion` (`seccion_id`, `seccion_nombre`, `seccion_descripcion`, `seccion_estado`, `created_at`, `updated_at`) VALUES
(2, 'B', 'SON ESTUDIANTES PROMEDIO', 'ACTIVO', '2024-06-17 21:30:49', '0000-00-00 00:00:00'),
(3, 'C', 'SON ESTUDIANTES CON PROBLEMAS EN EL AREA ACADéMICA', 'ACTIVO', '2024-06-17 21:31:04', '0000-00-00 00:00:00'),
(6, 'A', 'SECCIóN PARA ALUMNOS CON MAYOR RENDIMIENTO ACADEMICO', 'ACTIVO', '2024-06-23 16:37:13', '0000-00-00 00:00:00'),
(7, 'UNICO', 'AULA UNICA PARA COLEGIOS PRIVADOS', 'ACTIVO', '2024-06-23 16:37:27', '0000-00-00 00:00:00'),
(8, 'D', 'ES SECCION PÁRA MUJERES', 'ACTIVO', '2024-08-02 21:02:07', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` char(12) NOT NULL,
  `id_detalle_asignatura` int(11) DEFAULT NULL,
  `tema` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `archivo_tarea` varchar(255) DEFAULT NULL,
  `estado` enum('PENDIENTE','FINALIZADO') DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `doc_ncorrelativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tareas`
--

INSERT INTO `tareas` (`id_tarea`, `id_detalle_asignatura`, `tema`, `descripcion`, `fecha_entrega`, `archivo_tarea`, `estado`, `created_at`, `updated_at`, `doc_ncorrelativo`) VALUES
('T0000001', 20, 'ABECEDARIO', 'REALIZAR EL EJERCICIO ', '2025-12-30 10:40:50', 'controller/tareas/documentos/tarea_alumnos_1766677308', 'PENDIENTE', '2025-12-25', '0000-00-00 00:00:00', 1),
('T0000002', 47, 'FUINCIONES', 'REALIZAR LOS EJERCICIOS', '2025-12-31 10:40:50', 'controller/tareas/documentos/tarea_alumnos_1766677364', 'FINALIZADO', '2025-12-25', '2025-12-25 10:57:58', 2),
('T0000003', 23, 'SOCIEDAD', 'SACAR NOTICIAS DEL PERIODICO', '2025-12-31 09:39:50', 'controller/tareas/documentos/tarea_alumnos_1766677415', 'PENDIENTE', '2025-12-25', '0000-00-00 00:00:00', 3),
('T0000004', 59, 'MICROSOFT WORD', 'DARLE FORMATO AL DOCUMENTO Q SE ENTREGO ', '2025-12-31 12:00:00', 'controller/tareas/documentos/tarea_alumnos_1766677471', 'PENDIENTE', '2025-12-25', '0000-00-00 00:00:00', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `usu_id` int(11) NOT NULL,
  `usu_usuario` varchar(250) DEFAULT '',
  `usu_contra` varchar(250) DEFAULT NULL,
  `usu_email` varchar(255) DEFAULT NULL,
  `usu_estatus` enum('ACTIVO','INACTIVO') NOT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`usu_id`, `usu_usuario`, `usu_contra`, `usu_email`, `usu_estatus`, `rol_id`, `empresa_id`, `created_at`, `updated_at`) VALUES
(9, 'JERSSON123', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'jersson14071996@gmail.com', 'ACTIVO', 9, 1, '2024-06-17 09:08:34', NULL),
(10, 'JOSE', '$2y$12$OFdwUIOo./CC.vnSX.73LeKctoIi.kB632x0q42O9cB.gJMdFu5iC', 'jose1996@gmail.com', 'ACTIVO', 2, 1, '2024-07-01 15:54:13', NULL),
(11, 'jorge', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'jorge@gmail.com', 'ACTIVO', 2, 1, '2024-07-01 17:12:58', '0000-00-00 00:00:00'),
(12, 'YENI123', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'YENI12@GMAIL.COM', 'ACTIVO', 2, 1, '2024-07-01 17:13:56', '0000-00-00 00:00:00'),
(13, 'YESSICA', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'YESSICA@GMAIL.COM', 'ACTIVO', 9, 1, '2024-07-01 17:17:27', '0000-00-00 00:00:00'),
(19, 'joaquin1', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'joaquin@gmail.com', 'ACTIVO', 2, 1, '2024-07-06 09:29:19', '0000-00-00 00:00:00'),
(20, 'jesus123', '$2y$12$XLMEugfYc9qUBHSd9tDy1u.JFKqQEF1nVnIaAb5IJO.bWFFn/Vd.G', 'jesus@gmail.com', 'ACTIVO', 2, 1, '2024-07-06 09:39:08', '0000-00-00 00:00:00'),
(21, 'celia123', '$2y$12$uUo8CxjBR44SQRu5v/lb5OxiHW0.3B7GUyLozQRcwGU9AQMDEDRVi', 'celiam@gmail.com', 'ACTIVO', 2, 1, '2024-07-06 11:03:22', '0000-00-00 00:00:00'),
(22, 'MANUEL123', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'manuel@gmail.com', 'ACTIVO', 3, 1, '2024-07-06 12:03:30', NULL),
(32, 'yessica2', '$2y$12$HhO5cdWyK/eUXakQbTsDD.eoBb.4Q2Xv.M.LFTGZmoyelXZIJ0/Aa', 'yessica2024@gmail.com', 'ACTIVO', 5, 1, '2024-07-06 14:47:59', '0000-00-00 00:00:00'),
(33, 'vanesa12', '$2y$12$wdK0BDWldHnpcuRXgtiBKOI3mji/VUe03b3vSVXbhfqGnth0zWqka', 'vanessa21@gmail.com', 'ACTIVO', 2, 1, '2024-07-16 14:59:59', '0000-00-00 00:00:00'),
(34, 'JOSE21', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'jose2022@utea.edu.pe', 'ACTIVO', 1, 1, '2024-07-16 16:30:37', NULL),
(47, 'daniel12', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'daniel2024@gmail.com', 'ACTIVO', 1, 1, '2024-07-20 10:56:48', '2024-07-20 10:56:48'),
(48, 'ANDREA20', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'ANDREA123@GMAIL.COM', 'ACTIVO', 1, 1, '2024-07-20 10:58:07', '2024-07-20 10:58:07'),
(49, 'ESTEFANY', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'ESTEFANY12@GMAIL.COM', 'ACTIVO', 1, 1, '2024-07-20 13:09:06', '2024-07-20 13:09:06'),
(50, 'adriana2', '$2y$12$HhO5cdWyK/eUXakQbTsDD.eoBb.4Q2Xv.M.LFTGZmoyelXZIJ0/Aa', 'adriana12@gmail.com', 'ACTIVO', 4, 1, '2024-08-01 15:25:03', '0000-00-00 00:00:00'),
(51, 'JUAN12', '$2y$12$W4hhB7qG1A9nkFKfH2sFKO9dLTnq0ZQaX2/zlt9fd1j2wJ0rGY6U2', 'JUAN2024@GMAIL.COM', 'ACTIVO', 1, 1, '2024-08-03 11:55:40', '2024-08-03 11:55:40'),
(52, 'YESSENIA', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'YESSENIA2024@GMAIL.COM', 'ACTIVO', 1, 1, '2024-09-10 14:11:48', '2024-09-10 14:11:48'),
(53, 'JOSE2024', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'JOSE2024@GMAIL.COM', 'ACTIVO', 1, 1, '2024-09-10 14:12:44', '2024-09-10 14:12:44'),
(54, 'JHOSEP20', '$2y$12$p9laoSBws/oUR0yPfYmDaO1XpBZV37.qfv2Uzns2iaUE3lqIh0cQq', 'JHOSEP12@GMAIL.COM', 'ACTIVO', 1, 1, '2024-10-08 12:22:35', '2024-10-08 12:22:35'),
(55, 'DANIEL20', '$2y$12$d90vou6Pixw8BI89z2SU8.7DB7G7W5jQBVT2gxJ7tTaISrLfxGRE2', 'DANIEL12@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:09:18', '2025-12-25 10:09:18'),
(56, 'JUAN2025', '$2y$12$XTefa9oTShr5BCBClsgXSuW7Ieth.Hms.UnI/6Fm/WD/o0zwHPC.S', 'JUAN2@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:09:45', '2025-12-25 10:09:45'),
(57, 'JOAQUIN2', '$2y$12$yGD6WQDYkSogTiEyebgs/.jSBARYV0.23jmrSsv9.8Ss3L4KNDNRy', 'JOAQUIN21@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:11:26', '2025-12-25 10:11:26'),
(58, 'PERALTA2', '$2y$12$lUhuMRZAMDdBAwmF58vRwe7NcXxXj4pABZWoM9RbM2Tl.fyWp5bkS', 'PERALTA12@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:12:12', '2025-12-25 10:12:12'),
(59, 'PANIAGUA', '$2y$12$n6750GGqr45jGYdtx5iktu/uK4fc9tB1TYo4GkNeYrqOkpGQlz5cu', 'PANIAGUA12@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:12:57', '2025-12-25 10:12:57'),
(60, 'DAVILA20', '$2y$12$.GhiKAeVPl3LZMzhUfan4Ot/OhlWtmGkXkQ8xS1RzP7pqlrArnYcC', 'DAVILA12@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:15:02', '2025-12-25 10:15:02'),
(61, 'PEñA2025', '$2y$12$rrMj7DyqGrW9jOnmhN5VRea8uZ1bMEH2CEIyOjUCc6SA7vBLGKGbS', 'PEñA12@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:15:36', '2025-12-25 10:15:36'),
(62, 'SANDRA20', '$2y$12$vjoJrtk7W16vWHb1gP2zme5BnPkGMKj8JGbk3L1rmwK/agNxurlxa', 'SANDRA12@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:17:07', '2025-12-25 10:17:07'),
(63, 'JOAQUIN2', '$2y$12$tay6gLTwfH1NgrIPvOf6MuYmwRouUcFhs2ZmsEHiqZuf4NY6PSule', 'JOAQUIN1221@GMAIL.COM', 'ACTIVO', 1, 1, '2025-12-25 10:22:22', '2025-12-25 10:22:22');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`Id_alumno`) USING BTREE,
  ADD KEY `alum_dni` (`alum_dni`);

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`Id_asignatura`),
  ADD KEY `fk_grado` (`Id_grado`);

--
-- Indices de la tabla `asignatura_docente`
--
ALTER TABLE `asignatura_docente`
  ADD PRIMARY KEY (`Id_asigdocente`),
  ADD KEY `fk_docente` (`Id_docente`),
  ADD KEY `fk_año1221` (`id_año`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `fk_matirrr` (`id_matricula`);

--
-- Indices de la tabla `atencion_salud`
--
ALTER TABLE `atencion_salud`
  ADD PRIMARY KEY (`id_atencion`),
  ADD KEY `fk_uiserr` (`id_usuario`),
  ADD KEY `fk_estudiante22` (`id_matricula`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`Id_aula`),
  ADD KEY `fk_nivel` (`id_nivel_academico`),
  ADD KEY `fk_seccion` (`id_seccion`);

--
-- Indices de la tabla `auxiliar`
--
ALTER TABLE `auxiliar`
  ADD PRIMARY KEY (`auxiliar_id`) USING BTREE,
  ADD KEY `fk_user` (`id_usuario`);

--
-- Indices de la tabla `año_escolar`
--
ALTER TABLE `año_escolar`
  ADD PRIMARY KEY (`Id_año_escolar`) USING BTREE,
  ADD KEY `Id_año_escolar` (`Id_año_escolar`);

--
-- Indices de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id_comunicado`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `fk_aula` (`id_aula`);

--
-- Indices de la tabla `criterios`
--
ALTER TABLE `criterios`
  ADD PRIMARY KEY (`id_criterio`),
  ADD KEY `fk_id_detalles21` (`id_detalle_asignatura`);

--
-- Indices de la tabla `detalle_asignatura_docente`
--
ALTER TABLE `detalle_asignatura_docente`
  ADD PRIMARY KEY (`Id_detalle_asig_docente`),
  ADD KEY `fk_asigdocente` (`Id_asig_docente`),
  ADD KEY `fk_asignatura` (`Id_asignatura`);

--
-- Indices de la tabla `detalle_tarea`
--
ALTER TABLE `detalle_tarea`
  ADD PRIMARY KEY (`id_detalle_tarea`),
  ADD KEY `fk_matriculado` (`id_matriculado`),
  ADD KEY `fk_tarea` (`id_tarea`);

--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`Id_docente`) USING BTREE,
  ADD KEY `fk_año` (`id_asusuario`),
  ADD KEY `fk_espe` (`especialidad_id`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id_egresos`) USING BTREE,
  ADD KEY `indi` (`id_indicador`),
  ADD KEY `user_ingresos21` (`id_user`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`empresa_id`) USING BTREE;

--
-- Indices de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  ADD PRIMARY KEY (`Id_especilidad`);

--
-- Indices de la tabla `examen`
--
ALTER TABLE `examen`
  ADD PRIMARY KEY (`id_examen`) USING BTREE,
  ADD KEY `fk_id_stealle_asignatura` (`id_detalle_asignatura`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `id_hora_aula` (`id_hora_aula`),
  ADD KEY `fk_detalle_asig_docen2` (`id_detalle_asig_docente`);

--
-- Indices de la tabla `horas_aula`
--
ALTER TABLE `horas_aula`
  ADD PRIMARY KEY (`id_hora`),
  ADD KEY `fk_aoño` (`id_año_academico`),
  ADD KEY `fk_aulaaaaa` (`id_aula`);

--
-- Indices de la tabla `indicadores`
--
ALTER TABLE `indicadores`
  ADD PRIMARY KEY (`id_indicadores`);

--
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id_ingreso`),
  ADD KEY `fk_pago_pen` (`id_pago_pension`),
  ADD KEY `indi` (`id_indicador`),
  ADD KEY `user_ingresos21` (`id_user`);

--
-- Indices de la tabla `log_eventos_alumnos`
--
ALTER TABLE `log_eventos_alumnos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `matricula`
--
ALTER TABLE `matricula`
  ADD PRIMARY KEY (`id_matricula`),
  ADD KEY `fk_año12` (`id_año`),
  ADD KEY `fk_alumno12` (`id_alumno`),
  ADD KEY `fk_aula44` (`id_aula`),
  ADD KEY `fk_usu21` (`usu_id`);

--
-- Indices de la tabla `nivel_academico`
--
ALTER TABLE `nivel_academico`
  ADD PRIMARY KEY (`Id_nivel`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id_nota_bole`),
  ADD KEY `id_matri` (`id_matricula`),
  ADD KEY `id_crite` (`id_criterio`),
  ADD KEY `id_bimes` (`id_bimestre`);

--
-- Indices de la tabla `notas_padre`
--
ALTER TABLE `notas_padre`
  ADD PRIMARY KEY (`id_nota_papa`) USING BTREE,
  ADD KEY `id_matri` (`id_matricula`),
  ADD KEY `id_crite` (`criterio`),
  ADD KEY `id_bimes` (`id_bimestre`);

--
-- Indices de la tabla `padres`
--
ALTER TABLE `padres`
  ADD PRIMARY KEY (`id_papas`) USING BTREE,
  ADD KEY `fk_alu` (`id_alu`);

--
-- Indices de la tabla `pago_pensiones`
--
ALTER TABLE `pago_pensiones`
  ADD PRIMARY KEY (`id_pago_pension`),
  ADD KEY `fk_matri` (`id_matri`),
  ADD KEY `fk_pension` (`id_pension`);

--
-- Indices de la tabla `pensiones`
--
ALTER TABLE `pensiones`
  ADD PRIMARY KEY (`id_pensiones`),
  ADD KEY `fk_nivel12` (`id_nivel_academico`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id_periodo`),
  ADD KEY `fk_añoss` (`id_año_escolar`);

--
-- Indices de la tabla `personal_admi`
--
ALTER TABLE `personal_admi`
  ADD PRIMARY KEY (`personal_adm_id`) USING BTREE,
  ADD KEY `fk_año` (`id_ausuario`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`Id_rol`);

--
-- Indices de la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD PRIMARY KEY (`seccion_id`) USING BTREE,
  ADD UNIQUE KEY `unico` (`seccion_nombre`) USING BTREE;

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `fk_id_stealle_asignatura` (`id_detalle_asignatura`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`usu_id`) USING BTREE,
  ADD KEY `empresa_id` (`empresa_id`) USING BTREE,
  ADD KEY `fk_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `Id_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `Id_asignatura` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `asignatura_docente`
--
ALTER TABLE `asignatura_docente`
  MODIFY `Id_asigdocente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id_asistencia` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `atencion_salud`
--
ALTER TABLE `atencion_salud`
  MODIFY `id_atencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `Id_aula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `auxiliar`
--
ALTER TABLE `auxiliar`
  MODIFY `auxiliar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `año_escolar`
--
ALTER TABLE `año_escolar`
  MODIFY `Id_año_escolar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id_comunicado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `criterios`
--
ALTER TABLE `criterios`
  MODIFY `id_criterio` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `detalle_asignatura_docente`
--
ALTER TABLE `detalle_asignatura_docente`
  MODIFY `Id_detalle_asig_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `detalle_tarea`
--
ALTER TABLE `detalle_tarea`
  MODIFY `id_detalle_tarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de la tabla `docentes`
--
ALTER TABLE `docentes`
  MODIFY `Id_docente` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id_egresos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `empresa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  MODIFY `Id_especilidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT de la tabla `horas_aula`
--
ALTER TABLE `horas_aula`
  MODIFY `id_hora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de la tabla `indicadores`
--
ALTER TABLE `indicadores`
  MODIFY `id_indicadores` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de la tabla `log_eventos_alumnos`
--
ALTER TABLE `log_eventos_alumnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `matricula`
--
ALTER TABLE `matricula`
  MODIFY `id_matricula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `nivel_academico`
--
ALTER TABLE `nivel_academico`
  MODIFY `Id_nivel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notas`
--
ALTER TABLE `notas`
  MODIFY `id_nota_bole` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT de la tabla `notas_padre`
--
ALTER TABLE `notas_padre`
  MODIFY `id_nota_papa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT de la tabla `padres`
--
ALTER TABLE `padres`
  MODIFY `id_papas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `pago_pensiones`
--
ALTER TABLE `pago_pensiones`
  MODIFY `id_pago_pension` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT de la tabla `pensiones`
--
ALTER TABLE `pensiones`
  MODIFY `id_pensiones` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id_periodo` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `personal_admi`
--
ALTER TABLE `personal_admi`
  MODIFY `personal_adm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `Id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `seccion`
--
ALTER TABLE `seccion`
  MODIFY `seccion_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Codigo auto-incrementado del movimiento del area', AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `usu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `fk_grado` FOREIGN KEY (`Id_grado`) REFERENCES `aulas` (`Id_aula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignatura_docente`
--
ALTER TABLE `asignatura_docente`
  ADD CONSTRAINT `fk_año1221` FOREIGN KEY (`id_año`) REFERENCES `año_escolar` (`Id_año_escolar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_docente` FOREIGN KEY (`Id_docente`) REFERENCES `docentes` (`Id_docente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `fk_matirrr` FOREIGN KEY (`id_matricula`) REFERENCES `matricula` (`id_matricula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `atencion_salud`
--
ALTER TABLE `atencion_salud`
  ADD CONSTRAINT `fk_estudiante22` FOREIGN KEY (`id_matricula`) REFERENCES `matricula` (`id_matricula`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uiserr` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`usu_id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `fk_nivel` FOREIGN KEY (`id_nivel_academico`) REFERENCES `nivel_academico` (`Id_nivel`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`seccion_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `auxiliar`
--
ALTER TABLE `auxiliar`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`usu_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD CONSTRAINT `comunicados_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`usu_id`),
  ADD CONSTRAINT `fk_aula` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`Id_aula`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `criterios`
--
ALTER TABLE `criterios`
  ADD CONSTRAINT `fk_id_detalles21` FOREIGN KEY (`id_detalle_asignatura`) REFERENCES `detalle_asignatura_docente` (`Id_detalle_asig_docente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_asignatura_docente`
--
ALTER TABLE `detalle_asignatura_docente`
  ADD CONSTRAINT `fk_asigdocente` FOREIGN KEY (`Id_asig_docente`) REFERENCES `asignatura_docente` (`Id_asigdocente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asignatura` FOREIGN KEY (`Id_asignatura`) REFERENCES `asignaturas` (`Id_asignatura`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_tarea`
--
ALTER TABLE `detalle_tarea`
  ADD CONSTRAINT `fk_matriculado` FOREIGN KEY (`id_matriculado`) REFERENCES `matricula` (`id_matricula`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tarea` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD CONSTRAINT `fk_espe` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidad` (`Id_especilidad`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user1` FOREIGN KEY (`id_asusuario`) REFERENCES `usuario` (`usu_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD CONSTRAINT `egresos_ibfk_2` FOREIGN KEY (`id_indicador`) REFERENCES `indicadores` (`id_indicadores`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `egresos_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`usu_id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `examen`
--
ALTER TABLE `examen`
  ADD CONSTRAINT `examen_ibfk_1` FOREIGN KEY (`id_detalle_asignatura`) REFERENCES `detalle_asignatura_docente` (`Id_detalle_asig_docente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `fk_detalle_asig_docen2` FOREIGN KEY (`id_detalle_asig_docente`) REFERENCES `detalle_asignatura_docente` (`Id_detalle_asig_docente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `id_hora_aula` FOREIGN KEY (`id_hora_aula`) REFERENCES `horas_aula` (`id_hora`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `horas_aula`
--
ALTER TABLE `horas_aula`
  ADD CONSTRAINT `fk_aoño` FOREIGN KEY (`id_año_academico`) REFERENCES `año_escolar` (`Id_año_escolar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aulaaaaa` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`Id_aula`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD CONSTRAINT `fk_pago_pen` FOREIGN KEY (`id_pago_pension`) REFERENCES `pago_pensiones` (`id_pago_pension`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `indi` FOREIGN KEY (`id_indicador`) REFERENCES `indicadores` (`id_indicadores`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `user_ingresos21` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`usu_id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `matricula`
--
ALTER TABLE `matricula`
  ADD CONSTRAINT `fk_alumno12` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`Id_alumno`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aula44` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`Id_aula`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_año12` FOREIGN KEY (`id_año`) REFERENCES `año_escolar` (`Id_año_escolar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usu21` FOREIGN KEY (`usu_id`) REFERENCES `usuario` (`usu_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `id_bimes` FOREIGN KEY (`id_bimestre`) REFERENCES `periodos` (`id_periodo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `id_crite` FOREIGN KEY (`id_criterio`) REFERENCES `criterios` (`id_criterio`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `id_matri` FOREIGN KEY (`id_matricula`) REFERENCES `matricula` (`id_matricula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notas_padre`
--
ALTER TABLE `notas_padre`
  ADD CONSTRAINT `notas_padre_ibfk_1` FOREIGN KEY (`id_bimestre`) REFERENCES `periodos` (`id_periodo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_padre_ibfk_3` FOREIGN KEY (`id_matricula`) REFERENCES `matricula` (`id_matricula`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `padres`
--
ALTER TABLE `padres`
  ADD CONSTRAINT `fk_alu` FOREIGN KEY (`id_alu`) REFERENCES `alumnos` (`Id_alumno`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pago_pensiones`
--
ALTER TABLE `pago_pensiones`
  ADD CONSTRAINT `fk_matri` FOREIGN KEY (`id_matri`) REFERENCES `matricula` (`id_matricula`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pension` FOREIGN KEY (`id_pension`) REFERENCES `pensiones` (`id_pensiones`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pensiones`
--
ALTER TABLE `pensiones`
  ADD CONSTRAINT `fk_nivel12` FOREIGN KEY (`id_nivel_academico`) REFERENCES `nivel_academico` (`Id_nivel`);

--
-- Filtros para la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD CONSTRAINT `fk_añoss` FOREIGN KEY (`id_año_escolar`) REFERENCES `año_escolar` (`Id_año_escolar`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `personal_admi`
--
ALTER TABLE `personal_admi`
  ADD CONSTRAINT `personal_admi_ibfk_1` FOREIGN KEY (`id_ausuario`) REFERENCES `usuario` (`usu_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `fk_id_stealle_asignatura` FOREIGN KEY (`id_detalle_asignatura`) REFERENCES `detalle_asignatura_docente` (`Id_detalle_asig_docente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`empresa_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`Id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `actualizar_estado_tareas` ON SCHEDULE EVERY 1 MINUTE STARTS '2024-10-10 16:14:14' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE tareas
  SET estado = 'FINALIZADO'
  WHERE fecha_entrega <= NOW()
    AND estado != 'FINALIZADO'$$

CREATE DEFINER=`root`@`localhost` EVENT `actualizar_estado_examen` ON SCHEDULE EVERY 1 MINUTE STARTS '2024-10-10 16:05:29' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    UPDATE examen
    SET estado = 'REALIZADO'
    WHERE fecha_examen <= NOW() AND estado != 'REALIZADO';
END$$

CREATE DEFINER=`root`@`localhost` EVENT `actualizar_estado_alumnos_fin_anio` ON SCHEDULE EVERY 1 YEAR STARTS '2024-12-31 23:59:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DECLARE alumnos_actualizados INT;
    DECLARE anio_actual INT;
    
    -- Obtener el año actual
    SET anio_actual = YEAR(NOW());
    
    -- Actualizar estado de alumnos
    UPDATE alumnos
    SET alum_estatus = 'NO',
        updated_at = NOW()
    WHERE alum_estatus = 'SI';
    
    -- Obtener cantidad de registros actualizados
    SET alumnos_actualizados = ROW_COUNT();
    
    -- Registrar en log
    INSERT INTO log_eventos_alumnos (
        evento, 
        anio, 
        fecha_ejecucion, 
        alumnos_afectados
    )
    VALUES (
        'Actualización automática fin de año', 
        anio_actual,
        NOW(), 
        alumnos_actualizados
    );
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
