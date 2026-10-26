-- =====================================================================
-- Fase 11: corrección de datos del año escolar en curso (autorizada por el colegio el 2026-10-25)
--   * El año escolar activo se llamaba "2025" pero sus clases son de marzo a diciembre de 2026.
--   * Su IV bimestre tenía fechas de diciembre de 2025: va de después del III bimestre al cierre del año.
-- Solo cambia si los datos siguen exactamente como estaban (se puede aplicar sin riesgo en otra copia).
-- El nombre oficial del año ("AÑO DE ...") se revisa en Gestión escolar > Año escolar.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE `año_escolar` a
   SET a.`año_escolar` = 2026, a.updated_at = NOW()
 WHERE a.`año_escolar` = 2025 AND a.fecha_inicio = '2026-03-09' AND a.fecha_fin = '2026-12-11'
   AND NOT EXISTS (SELECT 1 FROM (SELECT `año_escolar` FROM `año_escolar`) x WHERE x.`año_escolar` = 2026);

UPDATE periodos p
  JOIN `año_escolar` a ON a.`Id_año_escolar` = p.`id_año_escolar`
   SET p.fecha_inicio = '2026-10-19', p.fecha_fin = a.fecha_fin, p.updated_at = NOW()
 WHERE p.periodos = 'IV BIMESTRE' AND p.fecha_inicio = '2025-12-01' AND p.fecha_fin = '2025-12-31'
   AND a.fecha_inicio = '2026-03-09';
