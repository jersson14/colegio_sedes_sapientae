#!/usr/bin/env bash
# Fase 13 paso 16: consistencia fina (íconos, estilos heredados) y ayuda contextual.
source "$(dirname "$0")/lib.sh"
A=$B/controller/ayuda/controlador_ayuda.php
cd "$R"
FA=plantilla/plugins/fontawesome-free/css/all.min.css
login JERSSON123; login DANIEL20

echo "== Íconos y estilos"
faltan=$(grep -rhoE "\bfa[srb]? fa-[a-z0-9-]+" view js --include=*.php --include=*.js | grep -v MPDF | awk '{print $2}' | sort -u | while read i; do grep -q "\.$i:before" $FA || echo $i; done | tr '\n' ' ')
check "todos los íconos existen en Font Awesome 5 (sin nombres de la versión 6)" "" "$(echo $faltan)"
check "un solo prefijo: sin 'fa fa-' de Font Awesome 4" "0" "$(grep -rlE '(^|[^a-z-])fa fa-' view js --include=*.php --include=*.js | grep -vc MPDF)"
check "sin colores fijos en línea (solo los calculados: muestras de Apariencia, panel y color de cada curso)" "view/configuracion/view_apariencia.php js/panel_admin.js js/mi_portal.js js/horario_calendario.js js/aula_virtual.js js/apariencia.js" "$(grep -rlE 'style="[^"]*(color|background)' view js --include=*.php --include=*.js | grep -v MPDF | sort -r | tr '\n' ' ' | sed 's/ $//')"
check "el tema da color a las cabeceras de tabla y de ventana" "2" "$(grep -cE '^\.(tabla|ventana)-cabecera \{' css/tema.css)"
check "sin fuentes heredadas (cooper, arial black)" "0" "$(grep -rliE 'cooper|arial black' view js css --include=*.php --include=*.js --include=*.css | grep -vc MPDF)"

echo "== Ayuda contextual"
secciones=$(php -r 'preg_match_all("/\x27([^\x27]+\.php)\x27: \[([^\]]*)\]/", file_get_contents("js/ayuda.js"), $m); foreach($m[2] as $s){ preg_match_all("/\x27([^\x27]+)\x27/", $s, $t); foreach($t[1] as $x) echo $x, "\n"; }')
sin=$(echo "$secciones" | while read s; do grep -qxF "## $s" docs/guia_rapida.md || echo "[$s]"; done | tr '\n' ' ')
check "cada sección de ayuda existe en docs/guia_rapida.md" "" "$(echo $sin)"
check "15 pantallas con ayuda" "15" "$(grep -cE "^  '[a-z_]+/view_[a-z_]+\.php':" js/ayuda.js)"
H=$(post DANIEL20 -d "secciones=Env%C3%ADo%20de%20tareas%3A%20cu%C3%A1ndo%20se%20puede%20reenviar" $A)
check "el estudiante lee la ayuda de sus tareas, en HTML" "true 1" "$(echo "$H" | jq_ ok) $(echo "$H" | jq_ html | grep -c '<h5 class="ayuda-seccion">')"
check "varias secciones juntas" "2" "$(post JERSSON123 -d "secciones=Libretas%20de%20notas%7CContrase%C3%B1as" $A | jq_ html | grep -o 'ayuda-seccion' | wc -l)"
check "sin sección -> 400; sección inexistente -> 404" "400 404" "$(code JERSSON123 -d secciones= $A) $(code JERSSON123 -d 'secciones=No existe' $A)"
check "el texto de la guía se escapa (no se inyecta HTML)" "0" "$(post JERSSON123 --data-urlencode "secciones=Matricular a un estudiante" $A | jq_ html | grep -c '<script')"
check "el index carga la ayuda y la coloca al abrir cada pantalla" "1 1" "$(grep -c 'js/ayuda.js' $T/JERSSON123.html) $(grep -c 'Ayuda_Colocar(this, vista)' $T/JERSSON123.html)"
terminar
