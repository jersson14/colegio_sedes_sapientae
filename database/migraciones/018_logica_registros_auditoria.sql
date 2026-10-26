-- =====================================================================
-- Fase 13 (paso 1 del plan): registros sin cruces y auditoría ampliada
-- Generado por database/generadores/generar_018.php
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Estudiante y sus padres: en una transacción y con el id del registro recién creado
DROP PROCEDURE IF EXISTS `SP_REGISTRAR_ALUMNOS`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_ALUMNOS`(IN P_DNI VARCHAR(20), IN P_NOMBRES VARCHAR(255), IN P_APEPA VARCHAR(255), IN P_APEMA VARCHAR(255),
  IN P_SEXO VARCHAR(20), IN P_FECHANAC DATE, IN P_CEL VARCHAR(20), IN P_DIREC VARCHAR(255), IN P_FOTO VARCHAR(255),
  IN P_DNIPA VARCHAR(20), IN P_DATOSPA VARCHAR(255), IN P_CELPA VARCHAR(255), IN P_DNIMA VARCHAR(20), IN P_DATOSMA VARCHAR(255), IN P_CELMA VARCHAR(255))
BEGIN
  DECLARE V_ID INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF EXISTS (SELECT 1 FROM alumnos WHERE alum_dni = P_DNI) THEN
    SELECT 2;
  ELSE
    START TRANSACTION;
    INSERT INTO alumnos(alum_dni, alum_nombre, alum_apepat, alum_apemat, alum_sexo, alum_fechanacimiento, Edad, alum_movil, alum_direccion,
                        alum_estatus, tipo_alum, alum_fotoperfil, created_at, updated_at)
    VALUES (P_DNI, P_NOMBRES, P_APEPA, P_APEMA, P_SEXO, P_FECHANAC, TIMESTAMPDIFF(YEAR, P_FECHANAC, CURDATE()), P_CEL, P_DIREC,
            'NO', 'NUEVO', P_FOTO, NOW(), NULL);
    SET V_ID = LAST_INSERT_ID();
    INSERT INTO padres(id_alu, Dni_papa, Datos_papa, Celular_papa, Dni_mama, Datos_mama, Celular_mama, created_at, updated_at)
    VALUES (V_ID, P_DNIPA, P_DATOSPA, P_CELPA, P_DNIMA, P_DATOSMA, P_CELMA, NOW(), NULL);
    COMMIT;
    SELECT 1;
  END IF;
END$$
DELIMITER ;

-- Asignación de cursos a un docente: devuelve el id del registro recién creado
DROP PROCEDURE IF EXISTS `SP_REGISTRAR_ASIGNATURA_DOCENTE`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_ASIGNATURA_DOCENTE`(IN P_ANIO INT, IN P_IDDOCENTE INT)
BEGIN
  INSERT INTO asignatura_docente(Id_docente, `id_año`, Total_cursos, created_at, updated_at)
  VALUES (P_IDDOCENTE, P_ANIO, 0, NOW(), NULL);
  SELECT LAST_INSERT_ID();
END$$
DELIMITER ;

-- ---------- Triggers de auditoría ----------

DROP TRIGGER IF EXISTS `trg_aud_notas_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_notas_ins` AFTER INSERT ON `notas` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_nota_bole', NEW.`id_nota_bole`, 'id_matricula', NEW.`id_matricula`, 'id_bimestre', NEW.`id_bimestre`, 'id_criterio', NEW.`id_criterio`, 'nota', NEW.`nota`, 'conclusiones', NEW.`conclusiones`, 'creared_at', NEW.`creared_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('notas', NEW.`id_nota_bole`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_notas_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_notas_upd` AFTER UPDATE ON `notas` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_nota_bole', OLD.`id_nota_bole`, 'id_matricula', OLD.`id_matricula`, 'id_bimestre', OLD.`id_bimestre`, 'id_criterio', OLD.`id_criterio`, 'nota', OLD.`nota`, 'conclusiones', OLD.`conclusiones`, 'creared_at', OLD.`creared_at`);
  SET V_DESPUES = JSON_OBJECT('id_nota_bole', NEW.`id_nota_bole`, 'id_matricula', NEW.`id_matricula`, 'id_bimestre', NEW.`id_bimestre`, 'id_criterio', NEW.`id_criterio`, 'nota', NEW.`nota`, 'conclusiones', NEW.`conclusiones`, 'creared_at', NEW.`creared_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('notas', NEW.`id_nota_bole`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_notas_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_notas_del` AFTER DELETE ON `notas` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_nota_bole', OLD.`id_nota_bole`, 'id_matricula', OLD.`id_matricula`, 'id_bimestre', OLD.`id_bimestre`, 'id_criterio', OLD.`id_criterio`, 'nota', OLD.`nota`, 'conclusiones', OLD.`conclusiones`, 'creared_at', OLD.`creared_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('notas', OLD.`id_nota_bole`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_notas_padre_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_notas_padre_ins` AFTER INSERT ON `notas_padre` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_nota_papa', NEW.`id_nota_papa`, 'id_matricula', NEW.`id_matricula`, 'id_bimestre', NEW.`id_bimestre`, 'criterio', NEW.`criterio`, 'nota', NEW.`nota`, 'creared_at', NEW.`creared_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('notas_padre', NEW.`id_nota_papa`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_notas_padre_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_notas_padre_upd` AFTER UPDATE ON `notas_padre` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_nota_papa', OLD.`id_nota_papa`, 'id_matricula', OLD.`id_matricula`, 'id_bimestre', OLD.`id_bimestre`, 'criterio', OLD.`criterio`, 'nota', OLD.`nota`, 'creared_at', OLD.`creared_at`);
  SET V_DESPUES = JSON_OBJECT('id_nota_papa', NEW.`id_nota_papa`, 'id_matricula', NEW.`id_matricula`, 'id_bimestre', NEW.`id_bimestre`, 'criterio', NEW.`criterio`, 'nota', NEW.`nota`, 'creared_at', NEW.`creared_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('notas_padre', NEW.`id_nota_papa`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_notas_padre_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_notas_padre_del` AFTER DELETE ON `notas_padre` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_nota_papa', OLD.`id_nota_papa`, 'id_matricula', OLD.`id_matricula`, 'id_bimestre', OLD.`id_bimestre`, 'criterio', OLD.`criterio`, 'nota', OLD.`nota`, 'creared_at', OLD.`creared_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('notas_padre', OLD.`id_nota_papa`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_asistencia_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_asistencia_ins` AFTER INSERT ON `asistencia` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_asistencia', NEW.`id_asistencia`, 'id_matricula', NEW.`id_matricula`, 'mes', NEW.`mes`, 'fecha', NEW.`fecha`, 'estado', NEW.`estado`, 'observacion', NEW.`observacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('asistencia', NEW.`id_asistencia`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_asistencia_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_asistencia_upd` AFTER UPDATE ON `asistencia` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_asistencia', OLD.`id_asistencia`, 'id_matricula', OLD.`id_matricula`, 'mes', OLD.`mes`, 'fecha', OLD.`fecha`, 'estado', OLD.`estado`, 'observacion', OLD.`observacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_asistencia', NEW.`id_asistencia`, 'id_matricula', NEW.`id_matricula`, 'mes', NEW.`mes`, 'fecha', NEW.`fecha`, 'estado', NEW.`estado`, 'observacion', NEW.`observacion`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('asistencia', NEW.`id_asistencia`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_asistencia_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_asistencia_del` AFTER DELETE ON `asistencia` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_asistencia', OLD.`id_asistencia`, 'id_matricula', OLD.`id_matricula`, 'mes', OLD.`mes`, 'fecha', OLD.`fecha`, 'estado', OLD.`estado`, 'observacion', OLD.`observacion`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('asistencia', OLD.`id_asistencia`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_detalle_tarea_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_detalle_tarea_ins` AFTER INSERT ON `detalle_tarea` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_detalle_tarea', NEW.`id_detalle_tarea`, 'id_tarea', NEW.`id_tarea`, 'id_matriculado', NEW.`id_matriculado`, 'archivo_evnio_tarea', NEW.`archivo_evnio_tarea`, 'calificacion', NEW.`calificacion`, 'observacion', NEW.`observacion`, 'estado', NEW.`estado`, 'fecha_envio', NEW.`fecha_envio`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('detalle_tarea', NEW.`id_detalle_tarea`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_detalle_tarea_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_detalle_tarea_upd` AFTER UPDATE ON `detalle_tarea` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_detalle_tarea', OLD.`id_detalle_tarea`, 'id_tarea', OLD.`id_tarea`, 'id_matriculado', OLD.`id_matriculado`, 'archivo_evnio_tarea', OLD.`archivo_evnio_tarea`, 'calificacion', OLD.`calificacion`, 'observacion', OLD.`observacion`, 'estado', OLD.`estado`, 'fecha_envio', OLD.`fecha_envio`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_detalle_tarea', NEW.`id_detalle_tarea`, 'id_tarea', NEW.`id_tarea`, 'id_matriculado', NEW.`id_matriculado`, 'archivo_evnio_tarea', NEW.`archivo_evnio_tarea`, 'calificacion', NEW.`calificacion`, 'observacion', NEW.`observacion`, 'estado', NEW.`estado`, 'fecha_envio', NEW.`fecha_envio`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('detalle_tarea', NEW.`id_detalle_tarea`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_detalle_tarea_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_detalle_tarea_del` AFTER DELETE ON `detalle_tarea` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_detalle_tarea', OLD.`id_detalle_tarea`, 'id_tarea', OLD.`id_tarea`, 'id_matriculado', OLD.`id_matriculado`, 'archivo_evnio_tarea', OLD.`archivo_evnio_tarea`, 'calificacion', OLD.`calificacion`, 'observacion', OLD.`observacion`, 'estado', OLD.`estado`, 'fecha_envio', OLD.`fecha_envio`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('detalle_tarea', OLD.`id_detalle_tarea`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_alumnos_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_alumnos_ins` AFTER INSERT ON `alumnos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('Id_alumno', NEW.`Id_alumno`, 'alum_dni', NEW.`alum_dni`, 'alum_nombre', NEW.`alum_nombre`, 'alum_apepat', NEW.`alum_apepat`, 'alum_apemat', NEW.`alum_apemat`, 'alum_sexo', NEW.`alum_sexo`, 'alum_fechanacimiento', NEW.`alum_fechanacimiento`, 'Edad', NEW.`Edad`, 'alum_movil', NEW.`alum_movil`, 'alum_direccion', NEW.`alum_direccion`, 'alum_estatus', NEW.`alum_estatus`, 'tipo_alum', NEW.`tipo_alum`, 'alum_fotoperfil', NEW.`alum_fotoperfil`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('alumnos', NEW.`Id_alumno`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_alumnos_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_alumnos_upd` AFTER UPDATE ON `alumnos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('Id_alumno', OLD.`Id_alumno`, 'alum_dni', OLD.`alum_dni`, 'alum_nombre', OLD.`alum_nombre`, 'alum_apepat', OLD.`alum_apepat`, 'alum_apemat', OLD.`alum_apemat`, 'alum_sexo', OLD.`alum_sexo`, 'alum_fechanacimiento', OLD.`alum_fechanacimiento`, 'Edad', OLD.`Edad`, 'alum_movil', OLD.`alum_movil`, 'alum_direccion', OLD.`alum_direccion`, 'alum_estatus', OLD.`alum_estatus`, 'tipo_alum', OLD.`tipo_alum`, 'alum_fotoperfil', OLD.`alum_fotoperfil`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('Id_alumno', NEW.`Id_alumno`, 'alum_dni', NEW.`alum_dni`, 'alum_nombre', NEW.`alum_nombre`, 'alum_apepat', NEW.`alum_apepat`, 'alum_apemat', NEW.`alum_apemat`, 'alum_sexo', NEW.`alum_sexo`, 'alum_fechanacimiento', NEW.`alum_fechanacimiento`, 'Edad', NEW.`Edad`, 'alum_movil', NEW.`alum_movil`, 'alum_direccion', NEW.`alum_direccion`, 'alum_estatus', NEW.`alum_estatus`, 'tipo_alum', NEW.`tipo_alum`, 'alum_fotoperfil', NEW.`alum_fotoperfil`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('alumnos', NEW.`Id_alumno`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_alumnos_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_alumnos_del` AFTER DELETE ON `alumnos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('Id_alumno', OLD.`Id_alumno`, 'alum_dni', OLD.`alum_dni`, 'alum_nombre', OLD.`alum_nombre`, 'alum_apepat', OLD.`alum_apepat`, 'alum_apemat', OLD.`alum_apemat`, 'alum_sexo', OLD.`alum_sexo`, 'alum_fechanacimiento', OLD.`alum_fechanacimiento`, 'Edad', OLD.`Edad`, 'alum_movil', OLD.`alum_movil`, 'alum_direccion', OLD.`alum_direccion`, 'alum_estatus', OLD.`alum_estatus`, 'tipo_alum', OLD.`tipo_alum`, 'alum_fotoperfil', OLD.`alum_fotoperfil`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('alumnos', OLD.`Id_alumno`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_docentes_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_docentes_ins` AFTER INSERT ON `docentes` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('Id_docente', NEW.`Id_docente`, 'docente_dni', NEW.`docente_dni`, 'docente_nombre', NEW.`docente_nombre`, 'docente_apelli', NEW.`docente_apelli`, 'especialidad_id', NEW.`especialidad_id`, 'docente_sexo', NEW.`docente_sexo`, 'docente_fechanacimiento', NEW.`docente_fechanacimiento`, 'docente_movil', NEW.`docente_movil`, 'docente_nro_alterno', NEW.`docente_nro_alterno`, 'docente_direccion', NEW.`docente_direccion`, 'docente_estatus', NEW.`docente_estatus`, 'docente_fotoperfil', NEW.`docente_fotoperfil`, 'id_asusuario', NEW.`id_asusuario`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('docentes', NEW.`Id_docente`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_docentes_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_docentes_upd` AFTER UPDATE ON `docentes` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('Id_docente', OLD.`Id_docente`, 'docente_dni', OLD.`docente_dni`, 'docente_nombre', OLD.`docente_nombre`, 'docente_apelli', OLD.`docente_apelli`, 'especialidad_id', OLD.`especialidad_id`, 'docente_sexo', OLD.`docente_sexo`, 'docente_fechanacimiento', OLD.`docente_fechanacimiento`, 'docente_movil', OLD.`docente_movil`, 'docente_nro_alterno', OLD.`docente_nro_alterno`, 'docente_direccion', OLD.`docente_direccion`, 'docente_estatus', OLD.`docente_estatus`, 'docente_fotoperfil', OLD.`docente_fotoperfil`, 'id_asusuario', OLD.`id_asusuario`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('Id_docente', NEW.`Id_docente`, 'docente_dni', NEW.`docente_dni`, 'docente_nombre', NEW.`docente_nombre`, 'docente_apelli', NEW.`docente_apelli`, 'especialidad_id', NEW.`especialidad_id`, 'docente_sexo', NEW.`docente_sexo`, 'docente_fechanacimiento', NEW.`docente_fechanacimiento`, 'docente_movil', NEW.`docente_movil`, 'docente_nro_alterno', NEW.`docente_nro_alterno`, 'docente_direccion', NEW.`docente_direccion`, 'docente_estatus', NEW.`docente_estatus`, 'docente_fotoperfil', NEW.`docente_fotoperfil`, 'id_asusuario', NEW.`id_asusuario`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('docentes', NEW.`Id_docente`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_docentes_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_docentes_del` AFTER DELETE ON `docentes` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('Id_docente', OLD.`Id_docente`, 'docente_dni', OLD.`docente_dni`, 'docente_nombre', OLD.`docente_nombre`, 'docente_apelli', OLD.`docente_apelli`, 'especialidad_id', OLD.`especialidad_id`, 'docente_sexo', OLD.`docente_sexo`, 'docente_fechanacimiento', OLD.`docente_fechanacimiento`, 'docente_movil', OLD.`docente_movil`, 'docente_nro_alterno', OLD.`docente_nro_alterno`, 'docente_direccion', OLD.`docente_direccion`, 'docente_estatus', OLD.`docente_estatus`, 'docente_fotoperfil', OLD.`docente_fotoperfil`, 'id_asusuario', OLD.`id_asusuario`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('docentes', OLD.`Id_docente`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_periodos_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_periodos_ins` AFTER INSERT ON `periodos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('id_periodo', NEW.`id_periodo`, 'id_año_escolar', NEW.`id_año_escolar`, 'tipo_perido', NEW.`tipo_perido`, 'periodos', NEW.`periodos`, 'fecha_inicio', NEW.`fecha_inicio`, 'fecha_fin', NEW.`fecha_fin`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('periodos', NEW.`id_periodo`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_periodos_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_periodos_upd` AFTER UPDATE ON `periodos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_periodo', OLD.`id_periodo`, 'id_año_escolar', OLD.`id_año_escolar`, 'tipo_perido', OLD.`tipo_perido`, 'periodos', OLD.`periodos`, 'fecha_inicio', OLD.`fecha_inicio`, 'fecha_fin', OLD.`fecha_fin`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('id_periodo', NEW.`id_periodo`, 'id_año_escolar', NEW.`id_año_escolar`, 'tipo_perido', NEW.`tipo_perido`, 'periodos', NEW.`periodos`, 'fecha_inicio', NEW.`fecha_inicio`, 'fecha_fin', NEW.`fecha_fin`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('periodos', NEW.`id_periodo`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_periodos_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_periodos_del` AFTER DELETE ON `periodos` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('id_periodo', OLD.`id_periodo`, 'id_año_escolar', OLD.`id_año_escolar`, 'tipo_perido', OLD.`tipo_perido`, 'periodos', OLD.`periodos`, 'fecha_inicio', OLD.`fecha_inicio`, 'fecha_fin', OLD.`fecha_fin`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('periodos', OLD.`id_periodo`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_ano_escolar_ins`;
DELIMITER $$
CREATE TRIGGER `trg_aud_ano_escolar_ins` AFTER INSERT ON `año_escolar` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = NULL;
  SET V_DESPUES = JSON_OBJECT('Id_año_escolar', NEW.`Id_año_escolar`, 'año_escolar', NEW.`año_escolar`, 'Nombre_año', NEW.`Nombre_año`, 'fecha_inicio', NEW.`fecha_inicio`, 'fecha_fin', NEW.`fecha_fin`, 'descripcion', NEW.`descripcion`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('año_escolar', NEW.`Id_año_escolar`, 'INSERT', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_ano_escolar_upd`;
DELIMITER $$
CREATE TRIGGER `trg_aud_ano_escolar_upd` AFTER UPDATE ON `año_escolar` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('Id_año_escolar', OLD.`Id_año_escolar`, 'año_escolar', OLD.`año_escolar`, 'Nombre_año', OLD.`Nombre_año`, 'fecha_inicio', OLD.`fecha_inicio`, 'fecha_fin', OLD.`fecha_fin`, 'descripcion', OLD.`descripcion`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = JSON_OBJECT('Id_año_escolar', NEW.`Id_año_escolar`, 'año_escolar', NEW.`año_escolar`, 'Nombre_año', NEW.`Nombre_año`, 'fecha_inicio', NEW.`fecha_inicio`, 'fecha_fin', NEW.`fecha_fin`, 'descripcion', NEW.`descripcion`, 'estado', NEW.`estado`, 'created_at', NEW.`created_at`);
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('año_escolar', NEW.`Id_año_escolar`, 'UPDATE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_aud_ano_escolar_del`;
DELIMITER $$
CREATE TRIGGER `trg_aud_ano_escolar_del` AFTER DELETE ON `año_escolar` FOR EACH ROW
BEGIN
  DECLARE V_ANTES, V_DESPUES LONGTEXT;
  SET V_ANTES = JSON_OBJECT('Id_año_escolar', OLD.`Id_año_escolar`, 'año_escolar', OLD.`año_escolar`, 'Nombre_año', OLD.`Nombre_año`, 'fecha_inicio', OLD.`fecha_inicio`, 'fecha_fin', OLD.`fecha_fin`, 'descripcion', OLD.`descripcion`, 'estado', OLD.`estado`, 'created_at', OLD.`created_at`);
  SET V_DESPUES = NULL;
  IF NOT (V_ANTES <=> V_DESPUES) THEN
    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)
    VALUES ('año_escolar', OLD.`Id_año_escolar`, 'DELETE', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());
  END IF;
END$$
DELIMITER ;
