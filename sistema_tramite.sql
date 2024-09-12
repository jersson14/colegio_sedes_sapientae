-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-09-2023 a las 18:42:00
-- Versión del servidor: 10.1.38-MariaDB
-- Versión de PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_tramite`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_DNI_UL` (IN `ID` INT)  SELECT
	usuario.usu_id, 
	empleado.emple_nrodocumento,
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat,
	CONCAT_WS(' ',empleado.emple_nombre,empleado.emple_apepat,empleado.emple_apemat) AS 		 USUARIO,
	area.area_nombre, 
	usuario.usu_usuario, 
	usuario.usu_contra, 
	usuario.usu_feccreacion, 
	usuario.usu_fecupdate, 
	usuario.empleado_id, 
	usuario.usu_observacion, 
	usuario.usu_estatus, 
	usuario.area_id, 
	usuario.usu_rol, 
	usuario.empresa_id, 
	area.area_cod,
 	
	empleado.empl_fotoperfil
FROM
	usuario
	INNER JOIN
	area
	ON 
		usuario.area_id = area.area_cod
	INNER JOIN
	empleado
	ON 
		usuario.empleado_id = empleado.empleado_id
	where usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SEGUIMIENTO_TRAMITE` (IN `NUMERO` VARCHAR(12), IN `DNI` VARCHAR(8))  SELECT
	documento.documento_id, 
	documento.doc_dniremitente, 
	CONCAT_WS(' ',documento.doc_nombreremitente,documento.doc_apepatremitente,documento.doc_apematremitente),
	MONTHNAME(documento.doc_fecharegistro) AS FECHA,
		date_format(doc_fecharegistro, "%d de %M del %Y - %H:%i:%s %p") as fecha_formateada
FROM
	documento
WHERE documento.documento_id=NUMERO AND documento.doc_dniremitente=DNI$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SEGUIMIENTO_TRAMITE_DETALLE` (IN `NUMERO` VARCHAR(12))  SELECT DISTINCT
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_AREA` ()  SELECT
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_AREA_SOLO` ()  SELECT
area.area_cod,area.area_nombre
FROM area
WHERE area_estado="ACTIVO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_DNI` ()  SELECT
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_EMPLEADO` ()  SELECT
	empleado.empleado_id, 
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat,
	CONCAT_WS(' ',emple_nombre,emple_apepat,emple_apemat)
FROM
	empleado
	WHERE empleado.emple_estatus="ACTIVO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_SELECT_TIPO` ()  SELECT
	tipo_documento.tipodocumento_id, 
	tipo_documento.tipodo_descripcion
FROM
	tipo_documento
	
WHERE tipo_documento.tipodo_estado='ACTIVO'$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CARGAR_TICKET` (IN `NUMERO` VARCHAR(12))  SELECT
	documento.documento_id, 
	documento.doc_dniremitente, 
	CONCAT_WS(' ',documento.doc_nombreremitente,documento.doc_apepatremitente,documento.doc_apematremitente),
	MONTHNAME(documento.doc_fecharegistro) AS FECHA,
		date_format(doc_fecharegistro, "%d de %M del %Y - %H:%i:%s %p") as fecha_formateada
FROM
	documento
WHERE documento.documento_id=NUMERO$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ELIMINAR_TRAMITE` (IN `ID` CHAR(12))  BEGIN
DELETE FROM movimiento WHERE documento_id=ID;
DELETE FROM documento WHERE documento_id=ID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_AREA` ()  SELECT
	area.area_cod, 
	area.area_nombre,
	date_format(area_fecha_registro, "%d-%m-%Y - %H:%i:%s") as fecha_formateada,
	area.area_fecha_registro, 
	area.area_estado
FROM
	area
	ORDER BY area_nombre asc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMUNICADOS` ()  SELECT
	comunicados.id_comunicado, 
	comunicados.titulo, 
	comunicados.descripcion, 
	date_format(fecha_registro, "%d-%m-%Y") as fecha_formateada,
	comunicados.fecha_registro, 
	comunicados.id_usuario,
	comunicados.estado,
	comunicados.enlace
FROM
	comunicados
	ORDER BY comunicados.fecha_registro DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_COMUNICADOS2` ()  SELECT
	comunicados.id_comunicado, 
	comunicados.titulo, 
	comunicados.descripcion, 
	date_format(fecha_registro, "%d-%m-%Y") as fecha_formateada,
	comunicados.fecha_registro, 
	comunicados.id_usuario,
	comunicados.estado,
	comunicados.enlace
FROM
	comunicados
	ORDER BY comunicados.fecha_registro DESC LIMIT 4$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_EMPLEADO` ()  SELECT
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_HORA` ()  SELECT
	id_horario,
	hora_inicio, 
	hora_fin, 
	usuario.usu_id, 
	usuario.usu_usuario
FROM
	horario
	INNER JOIN
	usuario
	ON 
		horario.usu_id = usuario.usu_id$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTIFICACION_COMUNICADO` ()  SELECT
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_NOTIFICACION_TRAMITE` (IN `IDAREA` INT)  SELECT
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TIPO_DOCUMENTO` ()  SELECT
	tipo_documento.tipodocumento_id, 
	tipo_documento.tipodo_descripcion, 
	tipo_documento.tipodo_estado,
	tipo_documento.requisitos,
	DATE_FORMAT(tipodo_feregistro, "%d-%m-%Y  %h:%m:%s")as fecha_tipo,
	tipo_documento.tipodo_feregistro
FROM
	tipo_documento$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_DOC ACPETADO` (IN `AREA` INT, IN `TIPOUSU` VARCHAR(255))  BEGIN
SET @ROL:=TIPOUSU;
IF @ROL='Secretario (a)' then 
SELECT

count(*)

FROM
	area
	INNER JOIN
	usuario
	ON 
		area.area_cod = usuario.area_id
	INNER JOIN
	documento
	ON 
		area.area_cod = documento.area_destino AND
		area.area_cod = documento.area_id AND
		area.area_cod = documento.area_origen
		where documento.area_origen = AREA and documento.doc_estatus="PENDIENTE";
		
		SELECT

count(*)

FROM
	area
	INNER JOIN
	usuario
	ON 
		area.area_cod = usuario.area_id
	INNER JOIN
	documento
	ON 
		area.area_cod = documento.area_destino AND
		area.area_cod = documento.area_id AND
		area.area_cod = documento.area_origen;
		END IF;
		END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_DOC_PENDIENTE` ()  SELECT count(documento_id)as totaldocpen FROM documento where doc_estatus="PENDIENTE"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TOTAL_EMPLEADO` ()  select count(empleado_id) as total from empleado$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAE_REQUISITO` (IN `ID` INT)  SELECT 
	tipo_documento.tipodo_descripcion,
	tipo_documento.requisitos
FROM
	tipo_documento
	
	WHERE tipo_documento.tipodocumento_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAE_REQUISITO_DNI` (IN `DNI` CHAR(12))  SELECT
	empleado.empleado_id,
	empleado.emple_nrodocumento, 	
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat,
	CONCAT_WS(' ',empleado.emple_nombre,empleado.emple_apepat,empleado.emple_apemat) AS EMPLEADO, 	
	empleado.emple_movil, 
	empleado.emple_email, 
	empleado.emple_direccion
FROM
	empleado
	WHERE 	empleado.emple_nrodocumento=DNI$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE` ()  SELECT
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
		date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,

	documento.area_origen, 
	documento.area_destino, 
	documento.area_id,
	documento.dias_pasados,
	dias_respuesta,
	acciones,
	doc_observaciones,
	dias_respuesta,
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
		ORDER BY doc_fecharegistro desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA` (IN `IDUSUARIO` INT)  BEGIN
DECLARE IDAREA INT;
SET @IDAREA :=(SELECT area_id FROM usuario WHERE usu_id=IDUSUARIO);
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
	documento.area_origen, 
	documento.area_destino, 
	documento.area_id,
	documento.dias_pasados,
	documento.dias_respuesta, 	
 		acciones,
	doc_observaciones,
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
		WHERE documento.area_destino=@IDAREA
				ORDER BY doc_fecharegistro desc;
		END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA2` (IN `IDAREA` INT)  SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
	
	documento.area_origen, 
	documento.area_destino, 
	documento.area_id,
	documento.dias_pasados, 	
	documento.dias_respuesta, 	
	acciones,
	doc_observaciones,
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
	WHERE area_origen=IDAREA
				ORDER BY doc_fecharegistro desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_BUSCAR` (IN `IDAREA` INT, IN `ESTADO` VARCHAR(20))  SELECT
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
	documento.area_origen, 
	documento.area_destino, 
	documento.area_id,
	documento.dias_pasados,
	acciones,
	doc_observaciones, 	
	documento.dias_respuesta,
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
	WHERE area_origen=IDAREA AND documento.doc_estatus=ESTADO
				ORDER BY doc_fecharegistro desc$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_ESTADO` (IN `FINICIO` DATETIME, IN `FFIN` DATETIME, IN `ESTADO` VARCHAR(20))  BEGIN
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
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
	WHERE doc_fecharegistro BETWEEN FINICIO AND FFIN AND doc_estatus=ESTADO
			ORDER BY doc_fecharegistro desc;
	END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_ESTADO_TA` (IN `FINICIO` DATETIME, IN `FFIN` DATETIME, IN `ESTADO` VARCHAR(20), IN `IDAREA` INT)  BEGIN
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
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
	WHERE doc_fecharegistro BETWEEN FINICIO AND FFIN AND doc_estatus=ESTADO
	HAVING area_destino=IDAREA
			ORDER BY doc_fecharegistro desc;
	END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_FECHAS` (IN `FINICIO` DATETIME, IN `FFIN` DATETIME, IN `IDAREA` INT)  BEGIN
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
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
	WHERE doc_fecharegistro BETWEEN FINICIO AND FFIN
HAVING	area_destino=IDAREA
			ORDER BY doc_fecharegistro desc;

	END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_FECHAS_TA` (IN `FINICIO` DATETIME, IN `FFIN` DATETIME, IN `IDAREA` INT)  BEGIN
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
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
	WHERE doc_fecharegistro BETWEEN FINICIO AND FFIN 
	HAVING	area_destino=IDAREA
				ORDER BY doc_fecharegistro desc;

	END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_TIPO_DOC` (IN `FINICIO` DATETIME, IN `FFIN` DATETIME, IN `TIPO` INT)  BEGIN
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
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
	WHERE doc_fecharegistro BETWEEN FINICIO AND FFIN AND documento.tipodocumento_id=TIPO
				ORDER BY doc_fecharegistro desc;

	END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_AREA_TIPO_DOC_TA` (IN `FINICIO` DATETIME, IN `FFIN` DATETIME, IN `TIPO_DOC` INT, IN `IDAREA` INT)  BEGIN
SELECT
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
	date_format(doc_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
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
	WHERE doc_fecharegistro BETWEEN FINICIO AND FFIN AND documento.tipodocumento_id=TIPO_DOC
	HAVING area_destino=IDAREA
				ORDER BY doc_fecharegistro desc;

	END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_ESTADO` (IN `ESTADO` VARCHAR(20))  SELECT
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
	documento.area_origen, 
	documento.area_destino, 
	documento.area_id,
	documento.dias_pasados,
		documento.dias_respuesta, 	

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
		WHERE doc_estatus=ESTADO
		ORDER BY doc_fecharegistro DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_TRAMITE_SEGUIMIENTO` (IN `ID` CHAR(12))  SELECT
	movimiento.movimiento_id, 
	movimiento.documento_id, 
	movimiento.area_origen_id, 
	area.area_nombre,
	date_format(mov_fecharegistro, "%d-%m-%Y - %H:%i:%s %p") as fecha_formateada,
 
	movimiento.mov_fecharegistro, 
	movimiento.mov_descripcion,
	movimiento.mov_estatus,
	movimiento.mov_archivo,
	movimiento.mov_acciones
FROM
	movimiento
	INNER JOIN
	area
	ON 
		movimiento.area_origen_id = area.area_cod
	WHERE movimiento.documento_id = ID
		ORDER BY mov_fecharegistro DESC$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_LISTAR_USUARIO` ()  SELECT
	usuario.usu_id, 
	usuario.usu_usuario, 
	usuario.empleado_id, 
	usuario.usu_observacion, 
	usuario.usu_estatus, 
	usuario.area_id, 
	usuario.usu_rol, 
	usuario.empresa_id, 
	CONCAT_WS(' ',empleado.emple_nombre,empleado.emple_apepat,empleado.emple_apemat) AS nempleado, 
	area.area_nombre, 
	area.area_cod
FROM
	usuario
	INNER JOIN
	empleado
	ON 
		usuario.empleado_id = empleado.empleado_id
	INNER JOIN
	area
	ON 
		usuario.area_id = area.area_cod$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_AREA` (IN `ID` INT, IN `NAREA` VARCHAR(255), IN `ESTADO` VARCHAR(20))  BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_COMUNICADO` (IN `ID` INT, IN `TITULO` VARCHAR(255), IN `DESCRIPCION` TEXT, IN `ENLACE` VARCHAR(255))  UPDATE comunicados SET
titulo=TITULO,
descripcion=DESCRIPCION,
enlace=ENLACE
WHERE id_comunicado=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EMPLEADO` (IN `ID` INT, IN `NDOCUMENTO` CHAR(12), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(100), IN `APEMAT` VARCHAR(100), IN `FECHA` DATE, IN `MOVIL` CHAR(9), IN `DIRECCION` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `ESTADO` VARCHAR(20))  BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_EMPLEADO_FOTO` (IN `ID` INT, IN `RUTA` VARCHAR(255))  UPDATE empleado SET
empl_fotoperfil=RUTA
WHERE empleado_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_HORARIO` (IN `ID` INT, IN `HORAINICIO` VARCHAR(20), IN `HORAFIN` VARCHAR(20))  UPDATE horario SET
hora_inicio=HORAINICIO, hora_fin=HORAFIN
where id_horario=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_TIPO_DOCUMENTO` (IN `ID` INT, IN `NTIPO` VARCHAR(255), IN `ESTADO` VARCHAR(20), IN `REQUISITOS` VARCHAR(255))  BEGIN
DECLARE TIPOACTUAL VARCHAR(255);
DECLARE CANTIDAD INT;
SET @TIPOACTUAL:=(SELECT tipodo_descripcion FROM tipo_documento WHERE tipodocumento_id=ID);
IF @TIPOACTUAL = NTIPO THEN
		UPDATE tipo_documento SET
		tipodo_descripcion=NTIPO,
		tipodo_estado=ESTADO,
		requisitos=REQUISITOS
		WHERE tipodocumento_id=ID;
SELECT 1;
ELSE
	SET @CANTIDAD:=(SELECT COUNT(*) FROM tipo_documento WHERE tipodo_descripcion=NTIPO);
	IF @CANTIDAD=0 THEN
		UPDATE tipo_documento SET
		tipodo_descripcion=NTIPO,
		tipodo_estado=ESTADO,
		requisitos=REQUISITOS
		WHERE tipodocumento_id=ID;
		SELECT 1;	
	ELSE
		SELECT 2;	
	END IF;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_TRAMITE_ESTATUS` (IN `ID` INT, IN `ESTATUS` VARCHAR(50))  UPDATE documento SET
documento.doc_estatus=ESTATUS,
documento.dias_pasados=''
WHERE documento.doc_nrodocumento=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_USUARIO` (IN `ID` VARCHAR(255), IN `IDEMPLEADO` INT, IN `IDAREA` INT, IN `ROL` VARCHAR(25))  UPDATE usuario SET
usuario.empleado_id = IDEMPLEADO,
usuario.area_id=IDAREA,
usuario.usu_rol=ROL
WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_USUARIO_CONTRA` (IN `ID` INT, IN `CONTRA` VARCHAR(250))  UPDATE usuario SET
usuario.usu_contra=CONTRA
WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_MODIFICAR_USUARIO_ESTATUS` (IN `ID` INT, IN `ESTATUS` VARCHAR(20))  UPDATE usuario SET
usuario.usu_estatus=ESTATUS
WHERE usuario.usu_id=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_RECHAZAR_TRAMITE` (IN `ID` INT, IN `DESCRIPCION` VARCHAR(255), IN `LOCAL1` INT)  BEGIN
UPDATE movimiento SET 
mov_estatus ='RECHAZADO',
mov_descripcion=DESCRIPCION
WHERE documento_id=ID AND mov_estatus='PENDIENTE';
UPDATE documento SET 
doc_estatus='RECHAZADO',
documento.dias_pasados='',
documento.area_origen=LOCAL1
WHERE documento_id=ID AND doc_estatus='PENDIENTE';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_AREA` (IN `NAREA` VARCHAR(255))  BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM area where area_nombre=NAREA);
IF @CANTIDAD = 0 THEN
INSERT INTO area(area_nombre,area_fecha_registro)VALUE(NAREA,NOW());
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_COMUNICADOS` (IN `TITULO` VARCHAR(255), IN `DESCRIPCION` TEXT, IN `IDUSU` INT, IN `ENLACE` VARCHAR(255))  BEGIN
DECLARE ID INT;
SET ID:=(SELECT id_comunicado FROM comunicados where fecha_registro<CURDATE() AND estado='NUEVO');

INSERT INTO comunicados(titulo,descripcion,fecha_registro,id_usuario,estado,enlace)VALUES(TITULO,DESCRIPCION,NOW(),IDUSU,'NUEVO',ENLACE);


UPDATE comunicados
set estado='PASADO'
WHERE id_comunicado=ID;


END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_EMPLEADO` (IN `NDOCUMENTO` CHAR(12), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(100), IN `APEMAT` VARCHAR(100), IN `FECHA` DATE, IN `MOVIL` CHAR(9), IN `DIRECCION` VARCHAR(255), IN `EMAIL` VARCHAR(255))  BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM empleado WHERE emple_nrodocumento=NDOCUMENTO);
IF @CANTIDAD=0 THEN
INSERT INTO empleado(emple_nrodocumento,emple_nombre,emple_apepat,emple_apemat,emple_fechanacimiento,emple_movil,emple_direccion,emple_email,emple_feccreacion,emple_estatus,empl_fotoperfil)VALUES(NDOCUMENTO,NOMBRE,APEPAT,APEMAT,FECHA,MOVIL,DIRECCION,EMAIL,CURDATE(),'ACTIVO','controller/empleado/FOTOS/usuario.png');
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_TIPO_DOCUMENTO` (IN `TIPODOC` VARCHAR(255), IN `REQUISITOS` VARCHAR(255))  BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM tipo_documento where tipodo_descripcion=TIPODOC);
IF @CANTIDAD = 0 THEN
INSERT INTO tipo_documento(tipodo_descripcion,tipodo_estado,requisitos,tipodo_feregistro)VALUE(TIPODOC,'ACTIVO',REQUISITOS,NOW());
SELECT 1;
ELSE
SELECT 2;

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_TRAMITE` (IN `DNI` CHAR(8), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(50), IN `APEMAT` VARCHAR(50), IN `CEL` CHAR(9), IN `EMAIL` VARCHAR(150), IN `DIRECCION` VARCHAR(255), IN `REPRESENTACION` VARCHAR(50), IN `RUC` CHAR(12), IN `RAZON` VARCHAR(255), IN `AREAPRINCIPAL` INT, IN `AREADESTINO` INT, IN `TIPO` INT, IN `NRODOCUMENTO` VARCHAR(15), IN `ASUNTO` VARCHAR(255), IN `RUTA` VARCHAR(255), IN `FOLIO` INT, IN `IDUSUARIO` INT, IN `ACCION` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `RESPU` INT)  BEGIN
DECLARE cantidad INT;
DECLARE cod char(12);
SET @cantidad :=(SELECT IFNULL(MAX(doc_ncorrelativo),0) FROM documento );
IF @cantidad >= 1 AND @cantidad <= 8 THEN
SET @cod :=(SELECT CONCAT('D000000',(@cantidad+1)));
ELSEIF @cantidad >= 9 AND @cantidad <= 98 THEN
SET @cod :=(SELECT CONCAT('D00000',(@cantidad+1)));
ELSEIF @cantidad >= 99 AND @cantidad <= 998 THEN
SET @cod :=(SELECT CONCAT('D0000',(@cantidad+1)));
ELSEIF @cantidad >= 999 AND @cantidad <= 9998 THEN
SET @cod :=(SELECT CONCAT('D000',(@cantidad+1)));
ELSEIF @cantidad >= 9999 AND @cantidad <= 99998 THEN
SET @cod :=(SELECT CONCAT('D00',(@cantidad+1)));
ELSEIF @cantidad >= 99999 AND @cantidad <= 999998 THEN
SET @cod :=(SELECT CONCAT('D0',(@cantidad+1)));
ELSEIF @cantidad >= 999999 THEN
SET @cod :=(SELECT CONCAT('D',(@cantidad+1)));
ELSE
SET @cod :=(SELECT CONCAT('D0000001'));
END IF;
INSERT INTO documento(documento_id,doc_dniremitente,doc_nombreremitente,doc_apepatremitente,doc_apematremitente,doc_celularremitente,doc_emailremitente,doc_direccionremitente,doc_representacion,doc_ruc,doc_empresa,area_origen,area_destino,tipodocumento_id,doc_nrodocumento,doc_asunto,doc_archivo,doc_folio,doc_ncorrelativo,dias_pasados,acciones,doc_observaciones,dias_respuesta) VALUES(@cod,DNI,NOMBRE,APEPAT,APEMAT,CEL,EMAIL,DIRECCION,REPRESENTACION,RUC,RAZON,AREAPRINCIPAL,AREADESTINO,TIPO,NRODOCUMENTO,ASUNTO,RUTA,FOLIO,(@cantidad+1),0,ACCION,OBSERVA,RESPU);
SELECT @cod;
INSERT INTO movimiento(documento_id,area_origen_id,areadestino_id,mov_fecharegistro,mov_descripcion,mov_estatus,usuario_id,mov_archivo,mov_acciones)VALUES(@cod,AREAPRINCIPAL,AREADESTINO,NOW(),ASUNTO,'PENDIENTE',IDUSUARIO,RUTA,ACCION);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_TRAMITE_DERIVAR` (IN `ID` CHAR(15), IN `ORIGEN` INT, IN `DESTINO` INT, IN `DESCRIPCION` VARCHAR(255), IN `IDUSUARIO` INT, IN `RUTA` VARCHAR(255), IN `TIPO` VARCHAR(255), IN `ACCION` VARCHAR(255))  BEGIN
DECLARE IDMOVIMENTO INT;
SET @IDMOVIMENTO:=(select movimiento_id from movimiento where mov_estatus='PENDIENTE' AND documento_id=ID);
IF TIPO = "FINALIZAR" THEN

UPDATE movimiento SET
mov_estatus='DERIVADO'
where movimiento_id=@IDMOVIMENTO;
UPDATE documento SET
area_origen=ORIGEN,
area_destino=ORIGEN,
doc_estatus='FINALIZADO',
documento.dias_pasados=''
WHERE documento_id=ID;
INSERT INTO movimiento(documento_id,area_origen_id,areadestino_id,mov_fecharegistro,mov_descripcion,mov_estatus,usuario_id,mov_archivo,mov_acciones) VALUES(ID,ORIGEN,ORIGEN,NOW(),DESCRIPCION,'FINALIZADO',IDUSUARIO,RUTA,ACCION);

ELSE

UPDATE movimiento SET
mov_estatus='DERIVADO'
where movimiento_id=@IDMOVIMENTO;
UPDATE documento SET
area_origen=ORIGEN,
area_destino=DESTINO,
doc_estatus='PENDIENTE',
documento.dias_pasados=0
WHERE documento_id=ID;
INSERT INTO movimiento(documento_id,area_origen_id,areadestino_id,mov_fecharegistro,mov_descripcion,mov_estatus,usuario_id,mov_archivo,mov_acciones) VALUES(ID,ORIGEN,DESTINO,NOW(),DESCRIPCION,'PENDIENTE',IDUSUARIO,RUTA,ACCION);

END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_TRAMITE_EXTERNO` (IN `DNI` CHAR(8), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(50), IN `APEMAT` VARCHAR(50), IN `CEL` CHAR(9), IN `EMAIL` VARCHAR(150), IN `DIRECCION` VARCHAR(255), IN `REPRESENTACION` VARCHAR(50), IN `RUC` CHAR(12), IN `RAZON` VARCHAR(255), IN `TIPO` INT, IN `NRODOCUMENTO` VARCHAR(15), IN `ASUNTO` VARCHAR(255), IN `RUTA` VARCHAR(255), IN `FOLIO` INT)  BEGIN
DECLARE cantidad INT;
DECLARE cod char(12);
SET @cantidad :=(SELECT IFNULL(MAX(doc_ncorrelativo),0) FROM documento );
IF @cantidad >= 1 AND @cantidad <= 8 THEN
SET @cod :=(SELECT CONCAT('D000000',(@cantidad+1)));
ELSEIF @cantidad >= 9 AND @cantidad <= 98 THEN
SET @cod :=(SELECT CONCAT('D00000',(@cantidad+1)));
ELSEIF @cantidad >= 99 AND @cantidad <= 998 THEN
SET @cod :=(SELECT CONCAT('D0000',(@cantidad+1)));
ELSEIF @cantidad >= 999 AND @cantidad <= 9998 THEN
SET @cod :=(SELECT CONCAT('D000',(@cantidad+1)));
ELSEIF @cantidad >= 9999 AND @cantidad <= 99998 THEN
SET @cod :=(SELECT CONCAT('D00',(@cantidad+1)));
ELSEIF @cantidad >= 99999 AND @cantidad <= 999998 THEN
SET @cod :=(SELECT CONCAT('D0',(@cantidad+1)));
ELSEIF @cantidad >= 999999 THEN
SET @cod :=(SELECT CONCAT('D',(@cantidad+1)));
ELSE
SET @cod :=(SELECT CONCAT('D0000001'));
END IF;
INSERT INTO documento(documento_id,doc_dniremitente,doc_nombreremitente,doc_apepatremitente,doc_apematremitente,doc_celularremitente,doc_emailremitente,doc_direccionremitente,doc_representacion,doc_ruc,doc_empresa,area_origen,area_destino,tipodocumento_id,doc_nrodocumento,doc_asunto,doc_archivo,doc_folio,doc_ncorrelativo,dias_pasados,acciones,doc_observaciones,dias_respuesta) VALUES(@cod,DNI,NOMBRE,APEPAT,APEMAT,CEL,EMAIL,DIRECCION,REPRESENTACION,RUC,RAZON,1,1,TIPO,NRODOCUMENTO,ASUNTO,RUTA,FOLIO,(@cantidad+1),0,'--REVISAR--','','');
SELECT @cod;
INSERT INTO movimiento(documento_id,area_origen_id,areadestino_id,mov_fecharegistro,mov_descripcion,mov_estatus,usuario_id,mov_archivo,mov_acciones)VALUES(@cod,1,1,NOW(),ASUNTO,'PENDIENTE',3,RUTA,'--REVISAR--');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_TRAMITE_UL` (IN `DNI` CHAR(8), IN `NOMBRE` VARCHAR(150), IN `APEPAT` VARCHAR(50), IN `APEMAT` VARCHAR(50), IN `CEL` CHAR(9), IN `EMAIL` VARCHAR(150), IN `DIRECCION` VARCHAR(255), IN `REPRESENTACION` VARCHAR(50), IN `RUC` CHAR(12), IN `RAZON` VARCHAR(255), IN `AREAPRINCIPAL` INT, IN `AREADESTINO` INT, IN `TIPO` INT, IN `NRODOCUMENTO` VARCHAR(15), IN `ASUNTO` VARCHAR(255), IN `RUTA` VARCHAR(255), IN `FOLIO` INT, IN `IDUSUARIO` INT, IN `ACCION` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `RESPU` INT)  BEGIN
DECLARE cantidad INT;
DECLARE cod char(12);
SET @cantidad :=(SELECT IFNULL(MAX(doc_ncorrelativo),0) FROM documento );
IF @cantidad >= 1 AND @cantidad <= 8 THEN
SET @cod :=(SELECT CONCAT('D000000',(@cantidad+1)));
ELSEIF @cantidad >= 9 AND @cantidad <= 98 THEN
SET @cod :=(SELECT CONCAT('D00000',(@cantidad+1)));
ELSEIF @cantidad >= 99 AND @cantidad <= 998 THEN
SET @cod :=(SELECT CONCAT('D0000',(@cantidad+1)));
ELSEIF @cantidad >= 999 AND @cantidad <= 9998 THEN
SET @cod :=(SELECT CONCAT('D000',(@cantidad+1)));
ELSEIF @cantidad >= 9999 AND @cantidad <= 99998 THEN
SET @cod :=(SELECT CONCAT('D00',(@cantidad+1)));
ELSEIF @cantidad >= 99999 AND @cantidad <= 999998 THEN
SET @cod :=(SELECT CONCAT('D0',(@cantidad+1)));
ELSEIF @cantidad >= 999999 THEN
SET @cod :=(SELECT CONCAT('D',(@cantidad+1)));
ELSE
SET @cod :=(SELECT CONCAT('D0000001'));
END IF;
INSERT INTO documento(documento_id,doc_dniremitente,doc_nombreremitente,doc_apepatremitente,doc_apematremitente,doc_celularremitente,doc_emailremitente,doc_direccionremitente,doc_representacion,doc_ruc,doc_empresa,area_origen,area_destino,tipodocumento_id,doc_nrodocumento,doc_asunto,doc_archivo,doc_folio,doc_ncorrelativo,dias_pasados,acciones,doc_observaciones,dias_respuesta) VALUES(@cod,DNI,NOMBRE,APEPAT,APEMAT,CEL,EMAIL,DIRECCION,REPRESENTACION,RUC,RAZON,AREAPRINCIPAL,AREADESTINO,TIPO,NRODOCUMENTO,ASUNTO,RUTA,FOLIO,(@cantidad+1),0,ACCION,OBSERVA,RESPU);
SELECT @cod;
INSERT INTO movimiento(documento_id,area_origen_id,areadestino_id,mov_fecharegistro,mov_descripcion,mov_estatus,usuario_id,mov_archivo,mov_acciones)VALUES(@cod,AREAPRINCIPAL,AREADESTINO,NOW(),ASUNTO,'PENDIENTE',IDUSUARIO,RUTA,ACCION);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_REGISTRAR_USUARIO` (IN `USU` VARCHAR(255), IN `CONTRA` VARCHAR(255), IN `IDEMPLEADO` INT, IN `IDAREA` INT, IN `ROL` VARCHAR(25))  BEGIN
DECLARE CANTIDAD INT;
SET @CANTIDAD:=(SELECT COUNT(*) FROM usuario WHERE usu_usuario=USU);
IF @CANTIDAD=0 THEN
INSERT INTO usuario(usu_usuario,usu_contra,empleado_id,area_id,usu_rol,usu_feccreacion,usu_estatus,empresa_id)VALUES(USU,CONTRA,IDEMPLEADO,IDAREA,ROL,CURDATE(),'ACTIVO',2);
SELECT 1;
ELSE
SELECT 2;
END IF;


END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TOTAL_DOCUMENTOS_ACEPTADOS` ()  SELECT count(documento_id)as totaldocpen FROM documento where doc_estatus="ACEPTADO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TOTAL_DOCUMENTOS_ACEPTADOS_AREA` (IN `IDAREA` INT)  SELECT count(*)as totaldocpend FROM documento where doc_estatus="PENDIENTE" and area_origen=IDAREA$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TOTAL_DOCUMENTOS_FINALIZADO` ()  SELECT count(documento_id)as totaldocpen FROM documento where doc_estatus="FINALIZADO"$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TOTAL_DOCUMENTOS_PENDIENTES2` (IN `IDUSUARIO` INT)  BEGIN
DECLARE IDAREA INT;
SET @IDAREA :=(SELECT area_id FROM usuario WHERE usu_id=IDUSUARIO);
SELECT
COUNT(documento.documento_id)as total,
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
		WHERE documento.area_destino=@IDAREA;
		END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TRAER_DATOS` ()  SELECT 
id_horario,
hora_inicio,
hora_fin

FROM horario$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TRAER_DATOS2` (IN `ID` INT)  SELECT id_horario,hora_inicio,hora_fin
FROM horario
WHERE id_horario=ID$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_TRAER_WIDGET` (IN `IDAREA` INT)  SELECT
	(select COUNT(*) FROM documento WHERE doc_estatus="PENDIENTE" AND area_origen=IDAREA),
	(select COUNT(*) FROM documento WHERE doc_estatus="ACEPTADO" AND area_origen=IDAREA),
	(select COUNT(*) FROM documento WHERE doc_estatus="FINALIZADO" AND area_origen=IDAREA)
FROM
	documento LIMIT 1$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_VERIFICAR_USUARIO` (IN `USU` VARCHAR(255))  SELECT
	usuario.usu_id, 
	usuario.usu_usuario, 
	usuario.usu_contra, 
	usuario.usu_feccreacion, 
	usuario.usu_fecupdate, 
	usuario.empleado_id, 
	usuario.usu_observacion, 
	usuario.usu_estatus, 
	usuario.area_id, 
	usuario.usu_rol, 
	usuario.empresa_id, 
	area.area_nombre, 
	area.area_cod, 
	empleado.emple_nombre, 
	empleado.emple_apepat, 
	empleado.emple_apemat,
	CONCAT_WS(' ',empleado.emple_nombre,empleado.emple_apepat,empleado.emple_apemat) AS USUARIO, 	
	empleado.empl_fotoperfil
FROM
	usuario
	INNER JOIN
	area
	ON 
		usuario.area_id = area.area_cod
	INNER JOIN
	empleado
	ON 
		usuario.empleado_id = empleado.empleado_id
	where usuario.usu_usuario = BINARY USU$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `area`
--

CREATE TABLE `area` (
  `area_cod` int(11) NOT NULL COMMENT 'Codigo auto-incrementado del movimiento del area',
  `area_nombre` varchar(50) COLLATE utf8_spanish_ci NOT NULL COMMENT 'nombre del area',
  `area_fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'fecha del registro del movimiento',
  `area_estado` enum('ACTIVO','INACTIVO') COLLATE utf8_spanish_ci NOT NULL COMMENT 'estado del area'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Entidad Area' ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `area`
--

INSERT INTO `area` (`area_cod`, `area_nombre`, `area_fecha_registro`, `area_estado`) VALUES
(1, 'MESA DE PARTES', '2023-08-25 14:08:00', 'ACTIVO'),
(2, 'SISTEMA WEB', '2023-08-25 14:08:07', 'INACTIVO'),
(3, 'CONTABILIDAD', '2023-08-25 18:30:22', 'ACTIVO'),
(4, 'PATRIMONIO', '2023-08-25 18:30:29', 'ACTIVO'),
(5, 'ESTADISTICA', '2023-08-25 18:30:37', 'ACTIVO'),
(6, 'EPIDEMIOLOGIA', '2023-08-25 18:30:57', 'ACTIVO'),
(7, 'DIRECCION GENERAL', '2023-08-25 18:31:09', 'ACTIVO'),
(8, 'RECURSOS HUMANOS', '2023-08-25 18:32:09', 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados`
--

CREATE TABLE `comunicados` (
  `id_comunicado` int(11) NOT NULL,
  `titulo` varchar(1000) NOT NULL,
  `descripcion` text NOT NULL,
  `enlace` varchar(10000) DEFAULT NULL,
  `fecha_registro` date NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `estado` enum('NUEVO','PASADO') NOT NULL,
  `com_correlativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `comunicados`
--

INSERT INTO `comunicados` (`id_comunicado`, `titulo`, `descripcion`, `enlace`, `fecha_registro`, `id_usuario`, `estado`, `com_correlativo`) VALUES
(1, 'AUTORIDADES REFUERZAN ATENCIÓN DE SALUD MENTAL EN APURÍMAC', 'Como parte del plan de fortalecimiento de los servicios de salud mental, el Ministerio de Salud (Minsa) y el Gobierno Regional de Apurímac a través de la Dirección Regional de Salud, inauguró en la Provincia de Aymaraes-Chalhuanca el Centro de Salud Mental Comunitaria “Señor de Animas” siendo uno de los nueve servicios especializado en Salud mental y adicciones en la región Apurímac.', 'https://www.diresaapurimac.gob.pe/web/noticias/autoridades-refuerzan-atencion-de-salud-mental-en-apurimac/', '2023-08-26', 2, 'PASADO', NULL),
(2, '¡HISTÓRICO! SE INICIA CONSTRUCCIÓN DE MODERNO HOSPITAL DE TAMBOBAMBA CON MÁS DE S/ 113 MILLONES', 'Entregan terreno a empresa constructora para dar inicio del mega proyecto.\nGracias a la gestión del Gobernador Percy Godoy Medina y la Dirección Regional de Salud Apurímac, el Programa Nacional de Inversiones en Salud (Pronis) del MINSA entregó el terreno a la empresa contratista china Gezhoura Group Company Limited Sucursal Perú para dar inicio a la construcción del anhelado proyecto del Hospital Tambobamba II-1 en la provincia de Cotabambas.', 'https://www.diresaapurimac.gob.pe/web/noticias/historico-se-inicia-construccion-de-moderno-hospital-de-tambobamba-con-mas-de-s-113-millones/', '2023-08-28', 2, 'NUEVO', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documento`
--

CREATE TABLE `documento` (
  `documento_id` char(12) COLLATE utf8_spanish_ci NOT NULL,
  `doc_dniremitente` char(8) COLLATE utf8_spanish_ci NOT NULL,
  `doc_nombreremitente` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `doc_apepatremitente` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `doc_apematremitente` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `doc_celularremitente` char(9) COLLATE utf8_spanish_ci NOT NULL,
  `doc_emailremitente` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `doc_direccionremitente` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `doc_representacion` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `doc_ruc` char(12) COLLATE utf8_spanish_ci NOT NULL,
  `doc_empresa` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `tipodocumento_id` int(11) NOT NULL,
  `doc_nrodocumento` varchar(15) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `doc_folio` int(11) NOT NULL,
  `doc_asunto` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `doc_archivo` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `doc_fecharegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  `area_id` int(11) DEFAULT '1',
  `doc_estatus` enum('PENDIENTE','RECHAZADO','ACEPTADO','FINALIZADO','SIN RESPUESTA') COLLATE utf8_spanish_ci NOT NULL,
  `area_origen` int(11) NOT NULL DEFAULT '0',
  `area_destino` int(11) DEFAULT NULL,
  `doc_ncorrelativo` int(11) DEFAULT NULL,
  `dias_pasados` int(11) DEFAULT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `acciones` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `doc_observaciones` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `dias_respuesta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `documento`
--

INSERT INTO `documento` (`documento_id`, `doc_dniremitente`, `doc_nombreremitente`, `doc_apepatremitente`, `doc_apematremitente`, `doc_celularremitente`, `doc_emailremitente`, `doc_direccionremitente`, `doc_representacion`, `doc_ruc`, `doc_empresa`, `tipodocumento_id`, `doc_nrodocumento`, `doc_folio`, `doc_asunto`, `doc_archivo`, `doc_fecharegistro`, `area_id`, `doc_estatus`, `area_origen`, `area_destino`, `doc_ncorrelativo`, `dias_pasados`, `id_empleado`, `acciones`, `doc_observaciones`, `dias_respuesta`) VALUES
('D0000001', '55151151', 'JOSE', 'SANCHEZ', 'MEDINA', '926262625', 'jose21@gmail.com', 'JR. CHALHUANCA N° 222', 'A NOMBRE PROPIO', '', '', 3, '3233', 1, 'SOLICITO PAGO DE PERSONAL DE APOYO', 'controller/tramite/documentos/ARCH258202313840.PDF', '2023-08-21 13:56:53', 1, 'FINALIZADO', 5, 5, 1, 0, NULL, '1 2 3 ON', '', 3),
('D0000002', '26626266', 'ANDREA', 'SANCHEZ', 'JIMENEZ', '966226262', 'andrea12@gmail.com', 'AV. DIAZ BARCENAS N° 323', 'A NOMBRE PROPIO', '', '', 2, '2332', 1, 'SE ENVIA OFICIO PARA RESPONDER CON IFORME DE COMPRA DE VIENES DE LA OFICINA DE CONTABILIDAD', 'controller/tramite/documentos/ARCH258202314177.PDF', '2023-08-25 14:52:53', 1, 'RECHAZADO', 3, 5, 2, 0, NULL, 'ON', '', 4),
('D0000003', '15511515', 'LUIS', 'CAMACHO', 'VELARDE', '926622656', 'luis2112@gmail.com', 'JR. HUANCAVELICA N° 323', 'A NOMBRE PROPIO', '', '', 3, '3223', 1, 'REVISAR DOCUMENTO PARA PAGO DE ASISTENTE TECNICO', 'controller/tramite/documentos/ARCH25820231532.PDF', '2023-08-25 15:27:19', 1, 'RECHAZADO', 5, 3, 3, 0, NULL, '1 2 3 ON', '', 5),
('D0000004', '26626266', 'ANDREA', 'SANCHEZ', 'JIMENEZ', '966226262', 'andrea12@gmail.com', 'AV. DIAZ BARCENAS N° 323', 'A NOMBRE PROPIO', '', '', 1, '3223', 1, 'COMPRAS', 'controller/tramite/documentos/ARCH258202317849.PDF', '2023-08-25 17:16:23', 1, 'RECHAZADO', 5, 5, 4, 0, NULL, '1 2 3 ON', '', 3),
('D0000005', '55151151', 'JOSE', 'SANCHEZ', 'MEDINA', '926262625', 'jose21@gmail.com', 'JR. CHALHUANCA N° 222', 'A NOMBRE PROPIO', '', '', 1, '33', 1, 'COMPRA DE MATERIALES', 'controller/tramite/documentos/ARCH258202318385.PDF', '2023-08-25 18:24:59', 1, 'RECHAZADO', 1, 5, 5, 0, NULL, '-ACCIÓN--TRAMITAR--REVISAR-ON', 'TODO BIEN', 3),
('D0000006', '26626266', 'ANDREA', 'SANCHEZ', 'JIMENEZ', '966226262', 'andrea12@gmail.com', 'AV. DIAZ BARCENAS N° 323', 'A NOMBRE PROPIO', '', '', 3, '33232', 1, 'SOLICITO PAGO ASESOR DEL MES DE AGOSTO', 'controller/tramite/documentos/ARCH258202318793.PDF', '2023-08-25 18:28:00', 1, 'RECHAZADO', 1, 3, 6, 0, NULL, '-2.TRAMITAR--3.REVISAR-ON', '', 3),
('D0000007', '26626266', 'ANDREA', 'SANCHEZ', 'JIMENEZ', '966226262', 'andrea12@gmail.com', 'AV. DIAZ BARCENAS N° 323', 'A NOMBRE PROPIO', '', '', 3, '54545', 20, 'COMPRA DE MATERIALES DE OFICINA', 'controller/tramite/documentos/ARCH268202317894.PDF', '2023-08-26 17:43:59', 1, 'FINALIZADO', 5, 5, 7, 0, NULL, '-2. TRAMITAR--3. REVISAR-ON', 'LAPICEROS, GRAPAS, ENGRAMPADO 10 DE CADA 1', 3),
('D0000008', '55151151', 'JOSE', 'SANCHEZ', 'MEDINA', '926262625', 'jose21@gmail.com', 'JR. CHALHUANCA N° 222', 'A NOMBRE PROPIO', '', '', 3, '1235', 2, 'SOLICITUD PARA VER SI HAY DISPONIBLE DINERO PARA COMPRAS', 'controller/tramite/documentos/ARCH278202316329.PDF', '2023-08-27 16:21:55', 1, 'FINALIZADO', 1, 1, 8, 0, NULL, '-2. TRAMITAR--3. REVISAR-ON', '', 3),
('D0000009', '54545454', 'LUCIA', 'SANCHEZ', 'JIMENEZ', '985623232', 'lucia12@gmail.com', 'JR. CANADA N° 322', 'A NOMBRE PROPIO', '', '', 3, '3223', 1, 'PRESENTACION DE CV DOCUMENTADO PARA CONVOCATORIA CAS N° 332', 'controller/tramite/documentos/ARCH29-8-2023-9-766.PDF', '2023-08-29 09:47:49', 1, 'RECHAZADO', 1, 1, 9, 0, NULL, '--REVISAR--', '', 0),
('D0000010', '62262626', 'JUAN CARLOS', 'MEDINA', 'SANCHEZ', '926161616', 'juanca2@gmail.com', 'JR. CUSCO N° 323', 'A NOMBRE PROPIO', '', '', 1, '2323', 32, 'TRABAO', 'controller/tramite/documentos/ARCH9-9-2023-11-846.PDF', '2023-09-09 11:05:46', 1, 'RECHAZADO', 3, 3, 10, 0, NULL, '-1. ACCIÓN--2. TRAMITAR-ON', '', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `empleado_id` int(11) NOT NULL,
  `emple_nombre` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emple_apepat` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emple_apemat` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emple_feccreacion` date DEFAULT NULL,
  `emple_fechanacimiento` date DEFAULT NULL,
  `emple_nrodocumento` char(12) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emple_movil` char(9) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emple_email` varchar(250) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emple_estatus` enum('ACTIVO','INACTIVO') COLLATE utf8_spanish_ci NOT NULL,
  `emple_direccion` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `empl_fotoperfil` varchar(255) COLLATE utf8_spanish_ci NOT NULL DEFAULT 'Fotos/admin.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`empleado_id`, `emple_nombre`, `emple_apepat`, `emple_apemat`, `emple_feccreacion`, `emple_fechanacimiento`, `emple_nrodocumento`, `emple_movil`, `emple_email`, `emple_estatus`, `emple_direccion`, `empl_fotoperfil`) VALUES
(1, 'JERSSON JORGE', 'CORILLA', 'MIRANDA', '2023-03-01', '2023-03-31', '234234', '918654046', 'jersson14071996@gmail.com', 'ACTIVO', 'JR. NICOLAS DE PIEROLA Nº 105', 'controller/empleado/FOTOS/IMG28-8-2023-11-64.jpg'),
(2, 'JOSE', 'SANCHEZ', 'MEDINA', '2023-08-25', '1985-01-24', '55151151', '926262625', 'jose21@gmail.com', 'ACTIVO', 'JR. CHALHUANCA N° 222', 'controller/empleado/FOTOS/IMG28-8-2023-11-195.png'),
(3, 'ANDREA', 'SANCHEZ', 'JIMENEZ', '2023-08-25', '1995-01-24', '26626266', '966226262', 'andrea12@gmail.com', 'ACTIVO', 'AV. DIAZ BARCENAS N° 323', 'controller/empleado/FOTOS/IMG28-8-2023-11-271.jpeg'),
(4, 'LUIS', 'CAMACHO', 'VELARDE', '2023-08-25', '1998-01-24', '15511515', '926622656', 'luis2112@gmail.com', 'ACTIVO', 'JR. HUANCAVELICA N° 323', 'controller/empleado/FOTOS/usuario.png'),
(5, 'JUAN CARLOS', 'MEDINA', 'SANCHEZ', '2023-08-27', '2000-07-25', '62262626', '926161616', 'juanca2@gmail.com', 'ACTIVO', 'JR. CUSCO N° 323', 'controller/empleado/FOTOS/usuario.png'),
(6, 'CELIA', 'MIRANDA', 'MUNGUIA', '2023-08-29', '1972-01-24', '09747535', '988505521', 'cmirandam@utea.edu.pe', 'ACTIVO', 'JR NICOLAS DE PIEROLA N° 15', 'controller/empleado/FOTOS/IMG29-8-2023-16-940.jpg'),
(7, 'WILFREDO', 'CARRIÓN', 'UMERES', '2023-09-07', '1995-05-11', '31044054', '952541551', 'willy22@gmail.co', 'ACTIVO', 'AV 28 DE ABRIL 235', 'controller/empleado/FOTOS/IMG7-9-2023-18-768.jpg'),
(8, 'ELIAS', 'CARRIÓN', 'UMERES', '2023-09-10', '1985-05-25', '41239943', '935951872', 'eliascar888@hotmail.com', 'ACTIVO', 'AV 28 DE ABRIL N° 234', 'controller/empleado/FOTOS/IMG10-9-2023-11-957.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `empresa_id` int(11) NOT NULL,
  `emp_razon` varchar(250) COLLATE utf8_spanish_ci NOT NULL,
  `emp_email` varchar(250) COLLATE utf8_spanish_ci NOT NULL,
  `emp_cod` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
  `emp_telefono` varchar(20) COLLATE utf8_spanish_ci NOT NULL,
  `emp_direccion` varchar(250) COLLATE utf8_spanish_ci NOT NULL,
  `emp_logo` varchar(255) COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`empresa_id`, `emp_razon`, `emp_email`, `emp_cod`, `emp_telefono`, `emp_direccion`, `emp_logo`) VALUES
(2, 'DIRESA', 'info@diresaapurimac.gob.pe', '3434', '(083) 321117', ' Av. Daniel Alcides Carrión s/n Abancay – Apurímac', 'diresa.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento`
--

CREATE TABLE `movimiento` (
  `movimiento_id` int(11) NOT NULL,
  `documento_id` char(12) COLLATE utf8_spanish_ci NOT NULL,
  `area_origen_id` int(11) DEFAULT NULL,
  `areadestino_id` int(11) NOT NULL,
  `mov_fecharegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  `mov_descripcion` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `mov_estatus` enum('PENDIENTE','CONFORME','INCOFORME','ACEPTADO','DERIVADO','FINALIZADO','RECHAZADO') COLLATE utf8_spanish_ci DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `mov_archivo` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `mov_descripcion_original` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `mov_acciones` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `movimiento`
--

INSERT INTO `movimiento` (`movimiento_id`, `documento_id`, `area_origen_id`, `areadestino_id`, `mov_fecharegistro`, `mov_descripcion`, `mov_estatus`, `usuario_id`, `mov_archivo`, `mov_descripcion_original`, `mov_acciones`) VALUES
(1, 'D0000001', 1, 3, '2023-08-25 13:56:53', 'SOLICITO PAGO DE PERSONAL DE APOYO', 'DERIVADO', 2, 'controller/tramite/documentos/ARCH258202313840.PDF', NULL, '1 2 3 ON'),
(2, 'D0000001', 3, 5, '2023-08-25 14:26:24', '', 'DERIVADO', 4, 'controller/tramite_area/documentos/ARCH258202314395.PDF', NULL, '1 2 3 '),
(3, 'D0000001', 5, 5, '2023-08-25 14:28:58', 'TRAMITE FINALIZADO', 'FINALIZADO', 5, 'controller/tramite_area/documentos/ARCH258202314656.PDF', NULL, '1 17 '),
(4, 'D0000002', 3, 5, '2023-08-25 14:52:53', 'NO CUMPLE REQUISITOS', 'RECHAZADO', 4, 'controller/tramite/documentos/ARCH258202314177.PDF', NULL, 'ON'),
(5, 'D0000003', 5, 3, '2023-08-25 15:27:19', 'NO CONTIENE EL DNI', 'RECHAZADO', 5, 'controller/tramite/documentos/ARCH25820231532.PDF', NULL, '1 2 3 ON'),
(6, 'D0000004', 3, 5, '2023-08-25 17:16:23', 'NO CUMPE', 'RECHAZADO', 2, 'controller/tramite/documentos/ARCH258202317849.PDF', NULL, '1 2 3 ON'),
(7, 'D0000005', 1, 3, '2023-08-25 18:24:59', 'COMPRA DE MATERIALES', 'DERIVADO', 2, 'controller/tramite/documentos/ARCH258202318385.PDF', NULL, '-ACCIÓN--TRAMITAR--REVISAR-ON'),
(8, 'D0000006', 3, 1, '2023-08-25 18:28:00', 'SOLICITO PAGO ASESOR DEL MES DE AGOSTO', 'DERIVADO', 2, 'controller/tramite/documentos/ARCH258202318793.PDF', NULL, '-2.TRAMITAR--3.REVISAR-ON'),
(9, 'D0000005', 3, 5, '2023-08-25 18:33:56', 'NO CUMPLE', 'RECHAZADO', 4, 'controller/tramite_area/documentos/ARCH258202318245.PDF', NULL, ''),
(10, 'D0000006', 1, 3, '2023-08-25 18:47:13', 'NO CUMPLE', 'RECHAZADO', 3, '', NULL, '-2. TRAMITAR--3. REVISAR-'),
(11, 'D0000007', 3, 5, '2023-08-26 17:43:59', 'NO CUMPLE', 'RECHAZADO', 2, 'controller/tramite/documentos/ARCH268202317894.PDF', NULL, '-2. TRAMITAR--3. REVISAR-ON'),
(12, 'D0000008', 1, 3, '2023-08-27 16:21:55', 'SOLICITUD PARA VER SI HAY DISPONIBLE DINERO PARA COMPRAS', 'DERIVADO', 2, 'controller/tramite/documentos/ARCH278202316329.PDF', NULL, '-2. TRAMITAR--3. REVISAR-ON'),
(13, 'D0000008', 3, 4, '2023-08-27 16:23:38', 'CONFORME', 'DERIVADO', 4, 'controller/tramite_area/documentos/ARCH278202316265.PDF', NULL, '-1. ACCIÓN--2. TRAMITAR--3. REVISAR-'),
(14, 'D0000008', 4, 5, '2023-08-27 16:25:15', 'TODO OKEY', 'DERIVADO', 6, 'controller/tramite_area/documentos/ARCH278202316541.PDF', NULL, '-2. TRAMITAR--3. REVISAR--5. COORDINAR-'),
(15, 'D0000008', 5, 3, '2023-08-27 16:26:14', 'RESPUESTA A CONTABILIDAD', 'DERIVADO', 5, 'controller/tramite_area/documentos/ARCH278202316187.PDF', NULL, '-3. REVISAR--6. CONOCIMIENTO-'),
(16, 'D0000008', 3, 1, '2023-08-27 16:27:25', 'SE RESPONDE QUE SI SE PUEDE COMPRAR YA QUE TIENE FONDOS', 'DERIVADO', 4, 'controller/tramite_area/documentos/ARCH278202316727.PDF', NULL, '-1. ACCIÓN--10. DAR RESPUESTA-'),
(17, 'D0000008', 1, 1, '2023-08-27 16:28:34', 'SE TRAMITA PARA COMPRAS DE MATERIALES DE ESCRITORIO', 'FINALIZADO', 3, '', NULL, '-1. ACCIÓN--2. TRAMITAR-'),
(25, 'D0000009', 1, 1, '2023-08-29 09:47:49', 'NO CUMPLE', 'RECHAZADO', 3, 'controller/tramite/documentos/ARCH29-8-2023-9-766.PDF', NULL, '--REVISAR--'),
(26, 'D0000007', 5, 5, '2023-09-09 11:04:14', 'TODO BIE', 'FINALIZADO', 5, '', NULL, '-2. TRAMITAR-'),
(27, 'D0000010', 4, 3, '2023-09-09 11:05:46', 'OKS', 'RECHAZADO', 6, 'controller/tramite/documentos/ARCH9-9-2023-11-846.PDF', NULL, '-1. ACCIÓN--2. TRAMITAR-ON');

--
-- Disparadores `movimiento`
--
DELIMITER $$
CREATE TRIGGER `actualizar` BEFORE INSERT ON `movimiento` FOR EACH ROW UPDATE
documento
set
dias_pasados=DATEDIFF(CURDATE(),doc_fecharegistro)
WHERE documento.doc_estatus="PENDIENTE"
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_documento`
--

CREATE TABLE `tipo_documento` (
  `tipodocumento_id` int(11) NOT NULL COMMENT 'Codigo auto-incrementado del tipo documento',
  `tipodo_descripcion` varchar(50) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Descripcion del  tipo documento',
  `tipodo_estado` enum('ACTIVO','INACTIVO') COLLATE utf8_spanish_ci NOT NULL COMMENT 'estado del tipo de documento',
  `requisitos` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `tipodo_feregistro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Entidad Documento' ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `tipo_documento`
--

INSERT INTO `tipo_documento` (`tipodocumento_id`, `tipodo_descripcion`, `tipodo_estado`, `requisitos`, `tipodo_feregistro`) VALUES
(1, 'CARTA', 'ACTIVO', '- DNI, PARTIDA DE NACIMIENTO', '2023-03-22 15:26:16'),
(2, 'OFICIO', 'ACTIVO', '- DNI', '2023-04-16 10:29:36'),
(3, 'SOLICITUD', 'ACTIVO', '- DNI', '2023-04-16 10:29:43'),
(4, 'MEMORANDUM', 'ACTIVO', 'NINGUNO', '2023-04-18 16:55:14'),
(5, 'RECIBO', 'ACTIVO', '-DNI\n-BOLETA SUNAT', '2023-04-18 22:25:36'),
(6, 'CARTA CIRCULAR', 'ACTIVO', 'DNI', '2023-04-19 16:21:14'),
(7, 'CARTA DE PRESENTACIÒN', 'ACTIVO', '-CV\n-CARTA DE PRESENTACION', '2023-04-19 16:21:29'),
(8, 'CARTA DE REQUERIMIENTO', 'ACTIVO', '-REQUERIMIENTO\n-SOLICITUD', '2023-04-19 16:21:39'),
(9, 'CARTA MULTIPLE', 'ACTIVO', NULL, '2023-04-19 16:21:48'),
(10, 'CARTA NOTARIAL', 'ACTIVO', NULL, '2023-04-19 16:21:57'),
(11, 'CÉDULA DE NOTIFICACIÓN', 'ACTIVO', NULL, '2023-04-19 16:22:14'),
(12, 'CONTRATO', 'ACTIVO', NULL, '2023-04-19 16:22:47'),
(13, 'CONVENIO', 'ACTIVO', NULL, '2023-04-19 16:22:51'),
(14, 'DOCUMENTO CONFIDENCIAL', 'ACTIVO', '- DNI\n- PARTIDA NACIMIENTO\n- TITULO PROFESIONAL', '2023-04-19 16:23:04'),
(15, 'DOCUMENTO VIA FAX', 'ACTIVO', NULL, '2023-04-19 16:23:17'),
(16, 'DOCUMENTO VIA EMAIL', 'ACTIVO', NULL, '2023-04-19 16:23:32'),
(17, 'EXHORTO', 'ACTIVO', NULL, '2023-04-19 16:23:42'),
(18, 'INFORME', 'ACTIVO', NULL, '2023-04-19 16:23:45'),
(19, 'INFORME CONFIDENCIAL', 'ACTIVO', NULL, '2023-04-19 16:23:58'),
(20, 'DOCUMENTO DE SUPERVISIÓN', 'ACTIVO', NULL, '2023-04-19 16:24:51'),
(21, 'INFORME TÉCNICO', 'ACTIVO', NULL, '2023-04-19 16:24:56'),
(22, 'MEMORANDUM MULTIPLE', 'ACTIVO', NULL, '2023-04-19 16:25:06'),
(23, 'MEMORIAL', 'ACTIVO', NULL, '2023-04-19 16:25:26'),
(24, 'NOTIFICACIONES JUDICIALES', 'ACTIVO', NULL, '2023-04-19 16:25:32'),
(25, 'OFICIO CIRCULAR', 'ACTIVO', NULL, '2023-04-19 16:25:42'),
(26, 'OFICIO MULTIPLE', 'ACTIVO', NULL, '2023-04-19 16:25:47'),
(27, 'PETITORIO', 'ACTIVO', NULL, '2023-04-19 16:25:58'),
(28, 'PRAES', 'ACTIVO', NULL, '2023-04-19 16:26:05'),
(29, 'PROVEIDO', 'ACTIVO', NULL, '2023-04-19 16:26:09'),
(30, 'RESOLUCIONES', 'ACTIVO', NULL, '2023-04-19 16:26:18'),
(31, 'ADENDA', 'ACTIVO', NULL, '2023-04-19 16:26:33'),
(32, 'ARCHIVADO', 'ACTIVO', NULL, '2023-04-19 16:26:44'),
(33, 'OTROS', 'ACTIVO', NULL, '2023-04-19 16:26:50'),
(34, 'OFERTA', 'ACTIVO', 'DNI', '2023-04-22 20:40:02'),
(35, 'TESTER', 'ACTIVO', 'DNI', '2023-04-23 12:05:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `usu_id` int(11) NOT NULL,
  `usu_usuario` varchar(250) COLLATE utf8_spanish_ci DEFAULT '',
  `usu_contra` varchar(250) COLLATE utf8_spanish_ci DEFAULT NULL,
  `usu_feccreacion` date DEFAULT NULL,
  `usu_fecupdate` date DEFAULT NULL,
  `empleado_id` int(11) DEFAULT NULL,
  `usu_observacion` varchar(250) COLLATE utf8_spanish_ci DEFAULT NULL,
  `usu_estatus` enum('ACTIVO','INACTIVO') COLLATE utf8_spanish_ci NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `usu_rol` enum('Secretario (a)','Administrador') COLLATE utf8_spanish_ci NOT NULL,
  `empresa_id` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`usu_id`, `usu_usuario`, `usu_contra`, `usu_feccreacion`, `usu_fecupdate`, `empleado_id`, `usu_observacion`, `usu_estatus`, `area_id`, `usu_rol`, `empresa_id`) VALUES
(2, 'jersson', '$2y$12$OFdwUIOo./CC.vnSX.73LeKctoIi.kB632x0q42O9cB.gJMdFu5iC', '2023-08-25', NULL, 1, NULL, 'ACTIVO', 2, 'Administrador', 2),
(3, 'JOSE', '$2y$12$5UiKKQQIR704.54/IopYpuF/OVx.octgbZi7U0BD3S1OXcPkNo8gO', '2023-08-25', NULL, 2, NULL, 'ACTIVO', 1, 'Secretario (a)', 2),
(4, 'ANDREA', '$2y$12$J3VwFNFSAjqdo0yuYdWL6e.kDmetlR9QVjCf5EtKuIzIRM/aDdmFC', '2023-08-25', NULL, 3, NULL, 'ACTIVO', 3, 'Secretario (a)', 2),
(5, 'LUIS', '$2y$12$p.qLHsx8sRemyzO..tXey..eEIQcprHXlhjHtYgtHDBzEj2QDF232', '2023-08-25', NULL, 4, NULL, 'ACTIVO', 5, 'Secretario (a)', 2),
(6, 'JUAN', '$2y$12$arbO9/L.m8Z.TnXdFsfdDOaTVGSVGb/P/FUVMLCciB5t0WdKQF7tG', '2023-08-27', NULL, 5, NULL, 'ACTIVO', 4, 'Secretario (a)', 2),
(7, 'WILLY', '$2y$12$KX8EUu4Qz0a7lsUclaT4IOZr9dNR/QV3nKUyOq2tx.LcJne1G2Hba', '2023-09-07', NULL, 7, NULL, 'ACTIVO', 4, 'Administrador', 2),
(8, 'eliascar', '$2y$12$wdPTnurFDab5l.0lT7VTg.unBYNeYT/3DXkzbGNX2GENiSvAuJ3F2', '2023-09-10', NULL, 8, NULL, 'ACTIVO', 6, 'Administrador', 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`area_cod`) USING BTREE,
  ADD UNIQUE KEY `unico` (`area_nombre`) USING BTREE;

--
-- Indices de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id_comunicado`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `documento`
--
ALTER TABLE `documento`
  ADD PRIMARY KEY (`documento_id`) USING BTREE,
  ADD KEY `tipodocumento_id` (`tipodocumento_id`) USING BTREE,
  ADD KEY `area_id` (`area_id`),
  ADD KEY `area_origen` (`area_origen`),
  ADD KEY `area_destino` (`area_destino`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`empleado_id`) USING BTREE;

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`empresa_id`) USING BTREE;

--
-- Indices de la tabla `movimiento`
--
ALTER TABLE `movimiento`
  ADD PRIMARY KEY (`movimiento_id`) USING BTREE,
  ADD KEY `area_origen_id` (`area_origen_id`) USING BTREE,
  ADD KEY `areadestino_id` (`areadestino_id`) USING BTREE,
  ADD KEY `usuario_id` (`usuario_id`) USING BTREE,
  ADD KEY `documento_id` (`documento_id`) USING BTREE;

--
-- Indices de la tabla `tipo_documento`
--
ALTER TABLE `tipo_documento`
  ADD PRIMARY KEY (`tipodocumento_id`) USING BTREE;

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`usu_id`) USING BTREE,
  ADD KEY `empleado_id` (`empleado_id`) USING BTREE,
  ADD KEY `area_id` (`area_id`) USING BTREE,
  ADD KEY `empresa_id` (`empresa_id`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `area`
--
ALTER TABLE `area`
  MODIFY `area_cod` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Codigo auto-incrementado del movimiento del area', AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id_comunicado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `empleado_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `empresa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `movimiento`
--
ALTER TABLE `movimiento`
  MODIFY `movimiento_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `tipo_documento`
--
ALTER TABLE `tipo_documento`
  MODIFY `tipodocumento_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Codigo auto-incrementado del tipo documento', AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `usu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD CONSTRAINT `comunicados_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`usu_id`);

--
-- Filtros para la tabla `documento`
--
ALTER TABLE `documento`
  ADD CONSTRAINT `documento_ibfk_1` FOREIGN KEY (`tipodocumento_id`) REFERENCES `tipo_documento` (`tipodocumento_id`),
  ADD CONSTRAINT `documento_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `area` (`area_cod`),
  ADD CONSTRAINT `documento_ibfk_3` FOREIGN KEY (`area_origen`) REFERENCES `area` (`area_cod`),
  ADD CONSTRAINT `documento_ibfk_4` FOREIGN KEY (`area_destino`) REFERENCES `area` (`area_cod`),
  ADD CONSTRAINT `documento_ibfk_5` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`empleado_id`);

--
-- Filtros para la tabla `movimiento`
--
ALTER TABLE `movimiento`
  ADD CONSTRAINT `movimiento_ibfk_1` FOREIGN KEY (`area_origen_id`) REFERENCES `area` (`area_cod`),
  ADD CONSTRAINT `movimiento_ibfk_2` FOREIGN KEY (`areadestino_id`) REFERENCES `area` (`area_cod`),
  ADD CONSTRAINT `movimiento_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`usu_id`),
  ADD CONSTRAINT `movimiento_ibfk_4` FOREIGN KEY (`documento_id`) REFERENCES `documento` (`documento_id`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`),
  ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `area` (`area_cod`),
  ADD CONSTRAINT `usuario_ibfk_3` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`empresa_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
