-- =====================================================================
-- Fase 7: reportes financieros y utilidades
--   * Estado de resultados (utilidad / pérdida) por categoría
--   * Comparativo mensual contra el año anterior
--   * Flujo de caja por cuenta
--   * Ingresos y egresos por forma de pago y cuenta
--   * Morosidad y proyección de pensiones
--   * Costo de planilla por tipo de contrato
--   * Cierres de caja con diferencias
--   * Panel del contador con utilidad y morosidad
-- Solo lectura: no modifica datos.
-- =====================================================================

-- Estado de cada pensión de cada matrícula (una fila por matrícula × pensión de su nivel).
-- Usa la misma regla que SP_CALCULAR_SALDO_PENSION (Fase 2):
--   vencida = no se pagó completa hasta el vencimiento y hoy ya pasó esa fecha;
--   la mora no se cobra si fue exonerada, si no está vencida o si la pensión se pagó sin mora.
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
         INNER JOIN pensiones pe ON pe.id_nivel_academico = a.id_nivel_academico
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

-- Estado de resultados: ingresos y egresos válidos del período por categoría.
-- Los ingresos de cobranza se separan por concepto (matrícula, pensión, mora…).
DROP PROCEDURE IF EXISTS SP_REPORTE_RESULTADOS;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_RESULTADOS(IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  SELECT t.tipo, IFNULL(g.codigo, '') AS grupo_codigo, IFNULL(g.nombre, 'SIN GRUPO') AS grupo,
         t.id_indicador, IFNULL(i.codigo, '') AS codigo, IFNULL(i.nombre, 'SIN CATEGORÍA') AS categoria,
         t.concepto, COUNT(*) AS cantidad, SUM(t.monto) AS total
    FROM (SELECT 'INGRESO' AS tipo, x.id_indicador, pp.concepto, x.monto
            FROM ingresos x LEFT JOIN pago_pensiones pp ON pp.id_pago_pension = x.id_pago_pension
           WHERE x.estado = 'VALIDO' AND x.created_at BETWEEN P_DESDE AND P_HASTA
          UNION ALL
          SELECT 'EGRESO', e.id_indicador, NULL, e.monto
            FROM egresos e
           WHERE e.estado = 'VALIDO' AND e.created_at BETWEEN P_DESDE AND P_HASTA) t
    LEFT JOIN indicadores i ON i.id_indicadores = t.id_indicador
    LEFT JOIN indicadores g ON g.id_indicadores = i.padre_id
   GROUP BY t.tipo, t.id_indicador, t.concepto
   ORDER BY FIELD(t.tipo, 'INGRESO', 'EGRESO'), g.codigo IS NULL, g.codigo, i.nombre,
            FIELD(t.concepto, 'MATRICULA', 'ADMISION', 'ALUMNO NUEVO', 'PENSION', 'MORA');
END$$
DELIMITER ;

-- Ingresos y egresos de cada mes del año y del año anterior
DROP PROCEDURE IF EXISTS SP_REPORTE_MENSUAL;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_MENSUAL(IN P_ANIO INT)
BEGIN
  SELECT ms.mes,
         IFNULL(SUM(CASE WHEN t.tipo = 'I' AND YEAR(t.fecha) = P_ANIO THEN t.monto END), 0) AS ingresos,
         IFNULL(SUM(CASE WHEN t.tipo = 'E' AND YEAR(t.fecha) = P_ANIO THEN t.monto END), 0) AS egresos,
         IFNULL(SUM(CASE WHEN t.tipo = 'I' AND YEAR(t.fecha) = P_ANIO - 1 THEN t.monto END), 0) AS ingresos_anterior,
         IFNULL(SUM(CASE WHEN t.tipo = 'E' AND YEAR(t.fecha) = P_ANIO - 1 THEN t.monto END), 0) AS egresos_anterior
    FROM (SELECT 1 AS mes UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
          UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12) ms
    LEFT JOIN (SELECT 'I' AS tipo, created_at AS fecha, monto FROM ingresos
                WHERE estado = 'VALIDO' AND created_at BETWEEN MAKEDATE(P_ANIO - 1, 1) AND MAKEDATE(P_ANIO + 1, 1) - INTERVAL 1 DAY
               UNION ALL
               SELECT 'E', created_at, monto FROM egresos
                WHERE estado = 'VALIDO' AND created_at BETWEEN MAKEDATE(P_ANIO - 1, 1) AND MAKEDATE(P_ANIO + 1, 1) - INTERVAL 1 DAY) t
           ON MONTH(t.fecha) = ms.mes
   GROUP BY ms.mes
   ORDER BY ms.mes;
END$$
DELIMITER ;

-- Flujo de caja: por cada caja/cuenta, saldo al inicio del período, entradas y salidas por origen y saldo final.
-- La última fila (id_cuenta NULL) suma los cobros, ingresos y egresos registrados sin caja/cuenta
-- (anteriores a la Fase 5), para que el flujo cuadre con el estado de resultados.
DROP PROCEDURE IF EXISTS SP_REPORTE_FLUJO_CAJA;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_FLUJO_CAJA(IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  SELECT c.id_cuenta, c.tipo, c.nombre, c.estado,
         c.saldo_inicial + IFNULL((SELECT SUM(IF(m.tipo = 'ENTRADA', m.monto, -m.monto)) FROM movimientos_cuenta m
                                    WHERE m.cuenta_id = c.id_cuenta AND m.estado = 'VALIDO' AND DATE(m.fecha) < P_DESDE
                                      AND DATE(m.fecha) >= IFNULL(c.fecha_saldo_inicial, '1000-01-01')), 0) AS saldo_anterior,
         IFNULL(SUM(IF(m.tipo = 'ENTRADA' AND m.origen IN ('PAGO', 'INGRESO'), m.monto, 0)), 0) AS cobros,
         IFNULL(SUM(IF(m.tipo = 'ENTRADA' AND m.origen = 'TRANSFERENCIA', m.monto, 0)), 0) AS transferencias_entrada,
         IFNULL(SUM(IF(m.tipo = 'ENTRADA' AND m.origen = 'AJUSTE', m.monto, 0)), 0) AS ajustes_entrada,
         IFNULL(SUM(IF(m.tipo = 'SALIDA' AND m.origen IN ('EGRESO', 'PLANILLA'), m.monto, 0)), 0) AS pagos,
         IFNULL(SUM(IF(m.tipo = 'SALIDA' AND m.origen = 'TRANSFERENCIA', m.monto, 0)), 0) AS transferencias_salida,
         IFNULL(SUM(IF(m.tipo = 'SALIDA' AND m.origen = 'AJUSTE', m.monto, 0)), 0) AS ajustes_salida
    FROM cuentas_financieras c
    LEFT JOIN movimientos_cuenta m ON m.cuenta_id = c.id_cuenta AND m.estado = 'VALIDO'
                                  AND DATE(m.fecha) BETWEEN P_DESDE AND P_HASTA
                                  AND DATE(m.fecha) >= IFNULL(c.fecha_saldo_inicial, '1000-01-01')
   GROUP BY c.id_cuenta
  UNION ALL
  SELECT NULL, 'SIN CUENTA', 'Sin caja/cuenta asignada', 'ACTIVO', 0,
         (SELECT IFNULL(SUM(pm.monto), 0) FROM pagos_metodos pm INNER JOIN pagos p ON p.id_pago = pm.id_pago
           WHERE p.estado = 'VALIDO' AND pm.cuenta_id IS NULL AND DATE(p.fecha) BETWEEN P_DESDE AND P_HASTA)
         + (SELECT IFNULL(SUM(monto), 0) FROM ingresos
             WHERE estado = 'VALIDO' AND id_pago_pension IS NULL AND cuenta_id IS NULL AND created_at BETWEEN P_DESDE AND P_HASTA),
         0, 0,
         (SELECT IFNULL(SUM(monto), 0) FROM egresos
           WHERE estado = 'VALIDO' AND cuenta_id IS NULL AND created_at BETWEEN P_DESDE AND P_HASTA),
         0, 0;
END$$
DELIMITER ;

-- Cobros y pagos del período por forma de pago y caja/cuenta (con comisiones de los cobros con tarjeta)
DROP PROCEDURE IF EXISTS SP_REPORTE_POR_METODO;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_POR_METODO(IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  SELECT x.tipo, IFNULL(mp.nombre, 'SIN FORMA DE PAGO') AS metodo, IFNULL(mp.tipo, 'OTRO') AS metodo_tipo,
         IFNULL(c.nombre, 'Sin caja/cuenta') AS cuenta, COUNT(*) AS operaciones,
         SUM(x.monto) AS total, SUM(x.comision) AS comision
    FROM (SELECT 'INGRESO' AS tipo, pm.metodo_pago_id, pm.cuenta_id, pm.monto, IFNULL(pm.comision, 0) AS comision
            FROM pagos_metodos pm INNER JOIN pagos p ON p.id_pago = pm.id_pago
           WHERE p.estado = 'VALIDO' AND DATE(p.fecha) BETWEEN P_DESDE AND P_HASTA
          UNION ALL
          SELECT 'INGRESO', metodo_pago_id, cuenta_id, monto, 0 FROM ingresos
           WHERE estado = 'VALIDO' AND id_pago_pension IS NULL AND created_at BETWEEN P_DESDE AND P_HASTA
          UNION ALL
          SELECT 'EGRESO', metodo_pago_id, cuenta_id, monto, 0 FROM egresos
           WHERE estado = 'VALIDO' AND created_at BETWEEN P_DESDE AND P_HASTA) x
    LEFT JOIN metodos_pago mp ON mp.id_metodo = x.metodo_pago_id
    LEFT JOIN cuentas_financieras c ON c.id_cuenta = x.cuenta_id
   GROUP BY x.tipo, x.metodo_pago_id, x.cuenta_id
   ORDER BY FIELD(x.tipo, 'INGRESO', 'EGRESO'), total DESC;
END$$
DELIMITER ;

-- Morosidad: pensiones vencidas con saldo, por alumno, del año escolar (filtros opcionales: 0 = todos)
DROP PROCEDURE IF EXISTS SP_REPORTE_MOROSIDAD;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_MOROSIDAD(IN P_ANIO INT, IN P_NIVEL INT, IN P_AULA INT)
BEGIN
  SELECT v.id_matricula, al.alum_dni AS documento,
         CONCAT_WS(' ', al.alum_apepat, al.alum_apemat, al.alum_nombre) AS alumno,
         n.Nivel_academico AS nivel, a.Grado AS grado, s.seccion_nombre AS seccion,
         TRIM(CONCAT_WS(' / ', NULLIF(pa.Celular_mama, ''), NULLIF(pa.Celular_papa, ''), NULLIF(al.alum_movil, ''))) AS telefonos,
         v.id_pension, v.mes, v.fecha_vencimiento, DATEDIFF(CURDATE(), v.fecha_vencimiento) AS dias_atraso,
         v.precio, v.pagado_pension, v.saldo_pension, v.saldo_mora, v.saldo_pension + v.saldo_mora AS deuda
    FROM vw_estado_pensiones v
   INNER JOIN alumnos al ON al.Id_alumno = v.id_alumno
   INNER JOIN aulas a ON a.Id_aula = v.id_aula
   INNER JOIN nivel_academico n ON n.Id_nivel = v.id_nivel
    LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
    LEFT JOIN (SELECT id_alu, MAX(Celular_mama) AS Celular_mama, MAX(Celular_papa) AS Celular_papa
                 FROM padres GROUP BY id_alu) pa ON pa.id_alu = v.id_alumno
   WHERE v.id_anio = P_ANIO AND v.vencida = 1 AND v.saldo_pension + v.saldo_mora > 0
     AND (P_NIVEL = 0 OR v.id_nivel = P_NIVEL) AND (P_AULA = 0 OR v.id_aula = P_AULA)
   ORDER BY n.Nivel_academico, a.Grado, s.seccion_nombre, alumno, v.fecha_vencimiento;
END$$
DELIMITER ;

-- Proyección de pensiones del año escolar por mes: lo que debía cobrarse, lo cobrado,
-- lo vencido sin cobrar y lo que falta vencer
DROP PROCEDURE IF EXISTS SP_REPORTE_PROYECCION;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_PROYECCION(IN P_ANIO INT)
BEGIN
  SELECT v.mes_num, v.mes, MIN(v.fecha_vencimiento) AS vencimiento, COUNT(*) AS matriculas,
         SUM(v.precio) AS esperado,
         SUM(LEAST(v.pagado_pension, v.precio)) AS cobrado,
         SUM(IF(v.vencida = 1, v.saldo_pension, 0)) AS vencido,
         SUM(IF(v.vencida = 1, v.saldo_mora, 0)) AS mora_pendiente,
         SUM(IF(v.vencida = 0, v.saldo_pension, 0)) AS por_vencer
    FROM vw_estado_pensiones v
   WHERE v.id_anio = P_ANIO
   GROUP BY v.mes_num, v.mes
   ORDER BY v.mes_num;
END$$
DELIMITER ;

-- Pensiones cuyo vencimiento cae fuera del calendario del año escolar (dato a revisar en Pensiones)
DROP PROCEDURE IF EXISTS SP_PENSIONES_FUERA_DE_ANIO;
DELIMITER $$
CREATE PROCEDURE SP_PENSIONES_FUERA_DE_ANIO(IN P_ANIO INT)
BEGIN
  SELECT n.Nivel_academico AS nivel, pe.mes, pe.fecha_vencimiento
    FROM pensiones pe
   INNER JOIN nivel_academico n ON n.Id_nivel = pe.id_nivel_academico
   CROSS JOIN `año_escolar` ae
   WHERE ae.`Id_año_escolar` = P_ANIO
     AND pe.id_nivel_academico IN (SELECT a.id_nivel_academico FROM matricula m INNER JOIN aulas a ON a.Id_aula = m.id_aula
                                    WHERE m.`id_año` = P_ANIO)
     AND YEAR(pe.fecha_vencimiento) <> YEAR(ae.fecha_inicio)
   ORDER BY n.Nivel_academico, FIELD(pe.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO',
            'AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE');
END$$
DELIMITER ;

-- Costo de planilla docente del año por mes y tipo de contrato (planillas no anuladas)
DROP PROCEDURE IF EXISTS SP_REPORTE_PLANILLA;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_PLANILLA(IN P_ANIO INT)
BEGIN
  SELECT p.mes, p.estado, d.tipo_contrato, COUNT(*) AS docentes, SUM(d.horas) AS horas,
         SUM(d.base) AS base, SUM(d.ingresos) AS ingresos, SUM(d.base + d.ingresos) AS bruto,
         SUM(d.descuentos) AS descuentos, SUM(d.adelantos) AS adelantos, SUM(d.neto) AS neto, SUM(d.pagado) AS pagado
    FROM planillas p
   INNER JOIN planilla_detalle d ON d.id_planilla = p.id_planilla
   WHERE p.anio = P_ANIO AND p.estado <> 'ANULADA'
   GROUP BY p.mes, p.estado, d.tipo_contrato
   ORDER BY p.mes, d.tipo_contrato;
END$$
DELIMITER ;

-- Cierres de caja del período (todas las cajas), con diferencias
DROP PROCEDURE IF EXISTS SP_REPORTE_CIERRES;
DELIMITER $$
CREATE PROCEDURE SP_REPORTE_CIERRES(IN P_DESDE DATE, IN P_HASTA DATE)
BEGIN
  SELECT ci.id_cierre, ci.fecha, c.nombre AS cuenta, ci.saldo_inicial, ci.entradas, ci.salidas, ci.saldo_esperado,
         ci.saldo_contado, ci.diferencia, ci.ajustado, ci.observacion, u.usu_usuario AS usuario
    FROM cierres_caja ci
   INNER JOIN cuentas_financieras c ON c.id_cuenta = ci.cuenta_id
    LEFT JOIN usuario u ON u.usu_id = ci.usuario_id
   WHERE ci.fecha BETWEEN P_DESDE AND P_HASTA
   ORDER BY ci.fecha DESC, c.nombre;
END$$
DELIMITER ;

-- Panel del contador: se agregan la morosidad del año escolar activo
DROP PROCEDURE IF EXISTS SP_PANEL_CONTABLE;
DELIMITER $$
CREATE PROCEDURE SP_PANEL_CONTABLE()
BEGIN
  DECLARE V_ANIO INT;
  SELECT `Id_año_escolar` INTO V_ANIO FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY `Id_año_escolar` DESC LIMIT 1;
  SELECT
    (SELECT IFNULL(SUM(monto), 0) FROM ingresos WHERE estado = 'VALIDO' AND created_at = CURDATE()) AS ingresos_hoy,
    (SELECT IFNULL(SUM(monto), 0) FROM egresos WHERE estado = 'VALIDO' AND created_at = CURDATE()) AS egresos_hoy,
    (SELECT IFNULL(SUM(monto), 0) FROM ingresos WHERE estado = 'VALIDO' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS ingresos_mes,
    (SELECT IFNULL(SUM(monto), 0) FROM egresos WHERE estado = 'VALIDO' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS egresos_mes,
    (SELECT COUNT(*) FROM pagos WHERE estado = 'VALIDO' AND DATE(fecha) = CURDATE()) AS recibos_hoy,
    (SELECT IFNULL(SUM(saldo_pension + saldo_mora), 0) FROM vw_estado_pensiones WHERE id_anio = V_ANIO AND vencida = 1) AS morosidad,
    (SELECT COUNT(DISTINCT id_matricula) FROM vw_estado_pensiones
      WHERE id_anio = V_ANIO AND vencida = 1 AND saldo_pension + saldo_mora > 0) AS alumnos_morosos;
END$$
DELIMITER ;
