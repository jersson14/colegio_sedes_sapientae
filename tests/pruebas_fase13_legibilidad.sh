#!/usr/bin/env bash
# Fase 13 paso 10: legibilidad de las tablas (nombres en formato título, fechas dd/mm/aaaa, estados con el
# mismo color para el mismo significado y tablas vacías con la acción siguiente). Lo de la página lo prueba
# tests/navegador/revisar_roles.sh (0 errores); aquí, las reglas de js/legibilidad.js y js/panel_comun.js.
source "$(dirname "$0")/lib.sh"
V=$(cd "$(dirname "$0")/.." && pwd -W 2>/dev/null || pwd)
login JERSSON123; login DANIEL20

nodo() {
  node -e "
global.window = {}; global.document = {};
var j = function () { return { on: function () {} }; }; j.fn = { dataTable: {} }; j.trim = function (s) { return String(s).trim(); };
global.textoPlano = function (s) { return s; };
var fs = require('fs');
eval(fs.readFileSync('$V/js/panel_comun.js', 'utf8').replace('window.PanelComun', 'global.PanelComun').replace(/function Asistencia_Abrir[\s\S]*\$/, ''));
window.PanelComun = global.PanelComun;
eval(fs.readFileSync('$V/js/legibilidad.js', 'utf8').replace('})(window.jQuery);', '})(j);'));
var L = window.Legibilidad;
$1"
}

echo "== Fechas"
check "aaaa-mm-dd y con hora" "31/12/2025 31/12/2025 10:40" "$(nodo "console.log(L.fecha('2025-12-31'), L.fecha('2025-12-31 10:40:50'))")"
check "medianoche sin hora; fecha vacía de MySQL como guion" "31/12/2025 —" "$(nodo "console.log(L.fecha('2025-12-31 00:00:00'), L.fecha('0000-00-00'))")"
check "dd-mm-aaaa con guiones pasa a barras" "28/12/2025 13:22 12/01/1996" "$(nodo "console.log(L.fecha('28-12-2025 13:22:05'), L.fecha('12-01-1996'))")"
check "dd-mm-aaaa - hh:mm:ss (con guion antes de la hora)" "31/12/2025 10:40" "$(nodo "console.log(L.fecha('31-12-2025 - 10:40:50'))")"
check "lo que no es fecha no se toca" "null null" "$(nodo "console.log(L.fecha('2025/12/31'), L.fecha('DNI 12-01'))")"

echo "== Formato título"
check "nombres con conectores en minúscula" "Juan de la Cruz Quispe" "$(nodo "console.log(L.titulo('JUAN DE LA CRUZ QUISPE'))")"
check "números romanos y sección del aula" "IV Bimestre|Primer Grado A|Luis XIV" "$(nodo "console.log([L.titulo('IV BIMESTRE'), L.titulo('PRIMER GRADO A'), L.titulo('LUIS XIV')].join('|'))")"
check "nombres que empiezan como romanos no se rompen" "Ivan Diaz|Ximena Rojas|Viviana Perez" "$(nodo "console.log([L.titulo('IVAN DIAZ'), L.titulo('XIMENA ROJAS'), L.titulo('VIVIANA PEREZ')].join('|'))")"
check "tildes y ñ" "Ñahui Peña Comunicación" "$(nodo "console.log(L.titulo('ÑAHUI PEÑA COMUNICACIÓN'))")"

echo "== Estados: mismo color, mismo significado"
EST=$(grep -A2 "ok: \[" "$(dirname "$0")/../js/legibilidad.js")
check "activo, pagado, calificado y presente son verdes" "4" "$(echo "$EST" | grep -o "'ACTIVO'\|'PAGADO'\|'CALIFICADO'\|'PRESENTE'" | wc -l)"
check "vencido, anulado y ausente son rojos" "3" "$(grep -A2 "mal: \[" "$(dirname "$0")/../js/legibilidad.js" | grep -o "'VENCIDO'\|'ANULADO'\|'AUSENTE'" | wc -l)"
check "los cinco colores están definidos en el tema" "5" "$(grep -c '^\.badge\.est-\(ok\|pend\|info\|mal\|neutro\)' "$(dirname "$0")/../css/tema.css")"
check "nivel y sexo conservan su color (no gris): inicial, primaria, secundaria, masculino, femenino" "5 0" "$(grep -c '^\.badge\.est-\(nivel-ini\|nivel-pri\|nivel-sec\|masc\|fem\) ' "$(dirname "$0")/../css/tema.css") $(grep -A1 "neutro: \[" "$(dirname "$0")/../js/legibilidad.js" | grep -c "MASCULINO\|SECUNDARIA")"

echo "== Carga"
check "el index carga legibilidad.js después de tablas_mejoras.js (todos los roles)" "1 1" "$(grep -A1 'tablas_mejoras.js' $T/JERSSON123.html | grep -c 'legibilidad.js') $(grep -c 'js/legibilidad.js' $T/DANIEL20.html)"
check "lo guardado no cambia: la lista sigue enviando los nombres como están en la base" "1" "$(post JERSSON123 -X POST $B/controller/alumnos/controlador_listar_alumnos.php | grep -c 'YESSENIA')"
terminar
