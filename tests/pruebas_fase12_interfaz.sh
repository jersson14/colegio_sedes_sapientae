#!/usr/bin/env bash
# Fase 12: apariencia configurable (colores, menú, lema, escudo), panel de inicio del administrador e ingreso.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
AP=$B/controller/configuracion/controlador_apariencia.php
crear_contador
login JERSSON123; login CONTA1; login DANIEL20

echo "== Apariencia por defecto (migración 017): colores del escudo"
check "colores y menú por defecto" "#7A1F2B #C9A227 COLOR" "$(q "select concat(color_primario,' ',color_acento,' ',estilo_menu) from empresa limit 1")"
P=$(post JERSSON123 -X POST $B/view/index.php)
check "el sistema carga las variables del tema y su hoja de estilos" "1 1 1" "$(echo "$P" | grep -c 'id=\"tema-variables\"') $(echo "$P" | grep -c 'css/tema.css') $(echo "$P" | grep -c 'tema-menu-color')"
check "el menú muestra el escudo y el nombre del colegio (no una imagen fija)" "1 0" "$(echo "$P" | grep -c 'class=\"tema-escudo\"') $(echo "$P" | grep -c 'img/logo.jpeg\" alt=\"AdminLTE')"

echo "== Guardar y validar"
check "colores inválidos: rechazados" "400 400" "$(code JERSSON123 -d 'accion=guardar&primario=rojo&acento=%23C9A227&estilo_menu=COLOR' $AP) $(code JERSSON123 -d 'accion=guardar&primario=%237A1F2B&acento=%23C9A227&estilo_menu=ARCOIRIS' $AP)"
check "vista previa calcula el texto legible sobre cada color" "#FFFFFF #1A1A1A" "$(post JERSSON123 -d 'accion=previsualizar&primario=%231B2F55&acento=%23F2D16B&estilo_menu=COLOR' $AP | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["variables"]["--tema-primario-texto"]," ",$d["variables"]["--tema-acento-texto"];')"
check "color principal muy claro: avisa y oscurece los títulos" "1 1" "$(post JERSSON123 -d 'accion=previsualizar&primario=%23F4E9A0&acento=%231B2F55&estilo_menu=CLARO' $AP | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo ($d["aviso"] ? 1 : 0)," ",($d["variables"]["--tema-tinta"] !== "#F4E9A0" ? 1 : 0);')"
check "guarda colores, menú y lema" "true" "$(post JERSSON123 -d 'lema=Educaci%C3%B3n%20con%20valores' -d 'accion=guardar&primario=%231B2F55&acento=%23D4A72C&estilo_menu=CLARO' $AP | jq_ ok)"
check "(quedan guardados)" "#1B2F55 #D4A72C CLARO" "$(q "select concat(color_primario,' ',color_acento,' ',estilo_menu) from empresa limit 1")"
P=$(post JERSSON123 -X POST $B/view/index.php)
check "todo el sistema usa los nuevos colores y el menú claro" "1 1 1" "$(echo "$P" | grep -c -- '--tema-primario:#1B2F55') $(echo "$P" | grep -c 'tema-menu-claro') $(echo "$P" | grep -c 'sidebar-light-primary')"
check "el lema aparece junto al nombre (menú y panel)" "2" "$(echo "$P" | grep -c 'Educación con valores')"
check "solo el administrador cambia la apariencia" "403 403" "$(code CONTA1 -d 'accion=cargar' $AP) $(code DANIEL20 -d 'accion=guardar&primario=%23000000&acento=%23FFFFFF&estilo_menu=COLOR' $AP)"

echo "== Pantalla de ingreso"
I=$(curl -s $B/index.php)
check "muestra el escudo, el nombre y el lema del colegio con sus colores" "1 1 1" "$(echo "$I" | grep -c 'class=\"ing-escudo\"') $(echo "$I" | grep -c 'Educación con valores') $(echo "$I" | grep -c -- '--tema-primario:#1B2F55')"
check "ya no publica usuarios ni contraseñas" "0" "$(echo "$I" | grep -ci 'USUARIOS DE ACCESO\|Contraseña</th>')"

echo "== Panel de inicio del administrador"
J=$(post JERSSON123 -X POST $B/controller/panel/controlador_panel_admin.php)
check "trae año escolar, comunidad, asistencia, caja, pendientes y comunicados" "2026 15 1 1 1" "$(echo "$J" | jq_ anio.anio) $(echo "$J" | jq_ comunidad.estudiantes) $(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo isset($d["asistencia"]["P"]) ? 1 : 0;') $(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo isset($d["caja"]["morosidad"]) ? 1 : 0;') $(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo is_array($d["comunicados"]) ? 1 : 0;')"
check "la deuda del panel coincide con Morosidad" "$(post CONTA1 -d 'anio=5&nivel=0&aula=0' $B/controller/reportes/controlador_morosidad.php | jq_ totales.deuda)" "$(echo "$J" | jq_ caja.morosidad)"
check "los estudiantes del panel son los matriculados del año activo" "$(sqlf "select count(*) from matricula m join \`año_escolar\` a on a.\`Id_año_escolar\`=m.\`id_año\` where a.estado='ACTIVO'")" "$(echo "$J" | jq_ comunidad.estudiantes)"
check "solo el administrador ve el panel" "403 403" "$(code CONTA1 -X POST $B/controller/panel/controlador_panel_admin.php) $(code DANIEL20 -X POST $B/controller/panel/controlador_panel_admin.php)"

# dejar la apariencia por defecto
q "update empresa set color_primario='#7A1F2B', color_acento='#C9A227', estilo_menu='COLOR', lema=NULL" > /dev/null
terminar
