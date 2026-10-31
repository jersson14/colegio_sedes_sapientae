#!/usr/bin/env bash
# Fase 13 paso 11: todos los PDF con la identidad del colegio (escudo, nombre, color), una sola fuente y un
# encabezado común (utilitario/reporte_pdf.php). Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
RP=$(cd "$(dirname "$0")/.." && pwd)/view/MPDF/REPORTE
login JERSSON123; login DANIEL20
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
PAGO=$(q "select id_pago from pagos order by id_pago limit 1")
PP=$(q "select concat(id_matri,'&idpagopen=',id_pago_pension) from pago_pensiones where estado='VALIDO' limit 1")
SINUSU=$(q "select id_matricula from matricula where usu_id is null limit 1")
DANI=$(sqlf "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20'")
AULA=$(q "select id_aula from horas_aula limit 1")
pdf() { curl -s -b $T/$1.jar -o $T/r.pdf -w '%{http_code}' "$B/view/MPDF/REPORTE/$2"; echo -n " $(head -c 4 $T/r.pdf)"; }
# Texto de la primera página (PyMuPDF); si no está instalado, se omite esa comprobación
texto() { python -c "import fitz,sys; print(fitz.open(sys.argv[1])[0].get_text())" "$(cygpath -w $T/r.pdf)" 2>/dev/null; }

echo "== Cada reporte se genera"
for r in "recibo.php?id=$PAGO" "boleta_pago.php?codigo=$PP" "cedula.php?codigo=$SINUSU" "kardex.php?codigo=$SINUSU" "horario.php?codigo=$AULA" \
         "asistencia_mensual.php?anio=$ANIO&aula=5&mes=2026-10" "libreta.php?matricula=$SINUSU" \
         "estado_resultados.php?desde=2025-01-01&hasta=2026-12-31" "flujo_caja.php?desde=2025-01-01&hasta=2026-12-31" "morosidad.php?anio=$ANIO&nivel=0&aula=0"; do
  check "${r%%\?*}" "200 %PDF" "$(pdf JERSSON123 "$r")"
done

echo "== Identidad del colegio"
NOMBRE=$(q "select emp_razon from empresa limit 1")
if python -c "import fitz" 2>/dev/null; then
  for r in "recibo.php?id=$PAGO" "cedula.php?codigo=$SINUSU" "estado_resultados.php?desde=2025-01-01&hasta=2026-12-31"; do
    pdf JERSSON123 "$r" > /dev/null
    check "${r%%\?*} lleva el nombre del colegio" "1" "$(texto | grep -c "$NOMBRE" | sed 's/[2-9]/1/')"
  done
  pdf JERSSON123 "cedula.php?codigo=$SINUSU" > /dev/null
  check "cédula de un estudiante sin usuario: con sus datos (antes salía vacía)" "0" "$(texto | grep -c 'No se encontró')"
  pdf JERSSON123 "kardex.php?codigo=$SINUSU" > /dev/null
  check "kardex de un estudiante sin usuario: con sus pagos (antes en blanco)" "1" "$(texto | grep -c 'Concepto')"
fi
check "ningún reporte con colores fijos del diseño anterior" "0" "$(grep -lE '#0A5D86|#f2f2f2|background-color: yellow|#fff3a8|#b0005a' $RP/*.php | wc -l)"
check "ningún reporte con una región escrita a mano" "0" "$(grep -liE 'apur[ií]mac|ugel|abancay' $RP/*.php | wc -l)"
check "todos usan el encabezado y el documento comunes" "$(ls $RP/*.php | wc -l) $(ls $RP/*.php | wc -l)" "$(grep -l 'reporte_pdf.php' $RP/*.php | wc -l) $(grep -l 'pdf_nuevo(' $RP/*.php | wc -l)"
check "una sola fuente (sin Arial ni otras)" "0" "$(grep -lE 'font-family: *(Arial|freeserif|aegean)' $RP/*.php | wc -l)"
check "el reporte sin uso con la región fija ya no existe" "0" "$(ls $RP/pago.php 2>/dev/null | wc -l)"

echo "== Permisos siguen igual"
check "el estudiante ve su kardex pero no el de otro" "200 403" "$(pdf DANIEL20 "kardex.php?codigo=$DANI" | cut -c1-3) $(pdf DANIEL20 "kardex.php?codigo=$SINUSU" | cut -c1-3)"
check "constancia de pago de otra matrícula -> 403" "403" "$(pdf DANIEL20 "boleta_pago.php?codigo=$PP" | cut -c1-3)"
terminar
