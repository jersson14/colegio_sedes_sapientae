#!/usr/bin/env bash
# Fase 13 paso 12: celular en Chrome headless (390 px) y escritorio (1440 px). Tablas como tarjetas, barra inferior
# del estudiante y del docente, botones de 44 px y botones de ícono con etiqueta. Requiere la BD de prueba recién reiniciada.
R=$(cd "$(dirname "$0")/../.." && pwd)
source "$R/tests/lib.sh" > /dev/null
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','DANIEL20')"
sid() { local jar=$T/s_$1.jar; curl -s -c $jar -b $jar -o /dev/null $B/index.php; curl -s -c $jar -b $jar -o /dev/null -d "u=$1&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php; tail -1 $jar | awk '{print $7}'; }
# Evalúa JS en la página (index.php) con un ancho dado; imprime el valor devuelto (texto)
evaluar() { JS="$3" PUERTO=$4 node "$R/tests/navegador/captura.js" "$1" "$T/cap_$4" "$2" 2>/dev/null | grep -m1 '^JS:' | sed 's/^JS: //; s/^"//; s/"$//'; }
ADM=$(sid JERSSON123); EST=$(sid DANIEL20); DOC=$(sid JOSE)

TABLA="new Promise(function(ok){ cargar_contenido('contenido_principal','alumnos/view_alumnos.php'); setTimeout(function(){
  var t=\$('#tabla_alumnos'), td=t.find('tbody tr').first().find('td');
  var sinEtq=\$('#contenido_principal button, #contenido_principal a.btn').filter(function(){ return !\$.trim(\$(this).text()) && !this.getAttribute('aria-label'); }).length;
  var alto=Math.min.apply(null, t.find('tbody .btn').map(function(){ return this.getBoundingClientRect().height; }).get());
  ok([t.hasClass('lg-tarjetas'), getComputedStyle(t.find('thead')[0]).display, td.filter('[data-label]').length > 3, t.find('td.lg-tit').first().text().trim(), alto >= 40, sinEtq].join('|'));
}, 2600); })"
echo "== Celular (390 px): Estudiantes como tarjetas"
r=$(evaluar $ADM 390 "$TABLA" 9480)
check "tabla en modo tarjeta, sin cabecera, con etiquetas y el nombre como título" "true|none|true|Yessenia Camacho Peralta" "$(echo "$r" | cut -d'|' -f1-4)"
check "botones de al menos 40 px y ninguno de solo ícono sin etiqueta" "true|0" "$(echo "$r" | cut -d'|' -f5-6)"
echo "== Escritorio (1440 px): la misma tabla sigue siendo tabla"
check "con cabecera visible" "table-header-group" "$(evaluar $ADM 1440 "$TABLA" 9481 | cut -d'|' -f2)"

BARRA="\$('.barra-inferior').length ? [getComputedStyle(\$('.barra-inferior')[0]).display, \$('.barra-inferior a').map(function(){return \$(this).text().trim()}).get().join(',')].join('|') : 'sin barra'"
echo "== Barra inferior"
check "estudiante en el celular: Inicio, Tareas, Notas, Pagos, Avisos" "flex|Inicio,Tareas,Notas,Pagos,Avisos" "$(evaluar $EST 390 "$BARRA" 9482)"
check "docente en el celular: Inicio, Asistencia, Tareas, Notas, Exámenes" "flex|Inicio,Asistencia,Tareas,Notas,Exámenes" "$(evaluar $DOC 390 "$BARRA" 9483)"
check "en el escritorio la barra no se ve" "none" "$(evaluar $EST 1440 "$BARRA" 9484 | cut -d'|' -f1)"
check "la dirección no tiene barra inferior" "sin barra" "$(evaluar $ADM 390 "$BARRA" 9485)"
check "Avisos lleva a los comunicados del inicio" "1" "$(evaluar $EST 390 "document.querySelectorAll('#avisos').length" 9486)"
rm -rf "$T"
terminar
