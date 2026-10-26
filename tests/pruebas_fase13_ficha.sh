#!/usr/bin/env bash
# Fase 13 paso 6: buscador general y ficha única del estudiante. Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
F=$B/controller/alumnos
crear_contador
login JERSSON123; login MANUEL123; login CONTA1; login JOSE; login DANIEL20
buscar() { post ${2:-JERSSON123} --data-urlencode "q@$1" $F/controlador_buscar_global.php; }
texto() { printf '%s' "$1" > $T/q.txt; echo $T/q.txt; }
ids() { php -r '$d=json_decode(stream_get_contents(STDIN),true); echo implode(" ",array_column($d["data"],"id"));'; }
ALU=$(q "select Id_alumno from alumnos where alum_dni='72451251'")

echo "== Buscador general"
check "por apellido: los tres Chavez" "3" "$(buscar $(texto chavez) | jq_ data)"
check "nombre y apellido en cualquier orden, con ñ" "$ALU" "$(buscar $(texto 'peña daniel') | ids)"
check "por el inicio del documento" "$ALU" "$(buscar $(texto 7245) | ids)"
check "menos de 2 letras: nada" "0" "$(buscar $(texto d) | jq_ data)"
check "% no es comodín" "0" "$(buscar $(texto '%%') | jq_ data)"
check "texto con codificación antigua (Latin-1) no da error" "$ALU" "$(printf 'pe\xf1a daniel' > $T/l1.txt; buscar $T/l1.txt | ids)"
check "auxiliar y contadora buscan; docente y estudiante no" "200 200 403 403" "$(echo $(for u in MANUEL123 CONTA1 JOSE DANIEL20; do code $u -d q=chavez $F/controlador_buscar_global.php; echo; done))"
check "la barra superior tiene el buscador: admin, auxiliar, contadora sí; docente, estudiante no" "1 1 1 0 0" "$(echo $(for u in JERSSON123 MANUEL123 CONTA1 JOSE DANIEL20; do grep -c 'id="buscador_global"' $T/$u.html; done))"

echo "== Ficha: secciones según el rol"
sec() { post $1 -d "id=$ALU" $F/controlador_ficha.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo implode(",",$d["secciones"])." ".(isset($d["salud"])?"salud":"-")." ".(isset($d["pagos"])?"pagos":"-")." ".(isset($d["notas"])?"notas":"-");'; }
check "dirección: todo" "datos,padres,asistencia,notas,tareas,pensiones,salud salud pagos notas" "$(sec JERSSON123)"
check "auxiliar: sin pagos ni salud" "datos,padres,asistencia,notas,tareas - - notas" "$(sec MANUEL123)"
check "contadora: sin notas, asistencia ni salud" "datos,padres,pensiones - pagos -" "$(sec CONTA1)"
check "docente y estudiante no abren fichas" "403 403" "$(code JOSE -d id=$ALU $F/controlador_ficha.php) $(code DANIEL20 -d id=$ALU $F/controlador_ficha.php)"
check "estudiante inexistente -> 404; id inválido -> 400" "404 400" "$(code JERSSON123 -d id=999999 $F/controlador_ficha.php) $(code JERSSON123 -d id=abc $F/controlador_ficha.php)"
check "datos del estudiante y de sus padres" "72451251 1" "$(post JERSSON123 -d id=$ALU $F/controlador_ficha.php | jq_ alumno.documento) $(post JERSSON123 -d id=$ALU $F/controlador_ficha.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo (int)!empty($d["padres"]["padre"]);')"

echo "== Ficha: varios años"
MACT=$(q "select id_matricula from matricula where id_alumno=$ALU order by id_matricula desc limit 1")
ANT=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado<>'ACTIVO' order by fecha_inicio limit 1")
AULA=$(q "select id_aula from matricula where id_matricula=$MACT")
sqlf "insert into matricula(id_alumno,\`id_año\`,id_aula,created_at) values($ALU,$ANT,$AULA,'2024-03-01')" > /dev/null
MANT=$(q "select max(id_matricula) from matricula where id_alumno=$ALU")
OTRA=$(q "select id_matricula from matricula where id_alumno<>$ALU limit 1")
mat() { post JERSSON123 -d "id=$ALU&matricula=$1" $F/controlador_ficha.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo count($d["matriculas"])." ".$d["matricula"]["id"];'; }
check "por defecto, la matrícula del año más reciente" "2 $MACT" "$(mat '')"
check "se puede ver un año anterior" "2 $MANT" "$(mat $MANT)"
check "una matrícula de otro estudiante se ignora" "2 $MACT" "$(mat $OTRA)"

echo "== Lista de estudiantes"
check "botón Ficha en la lista y sin la ventana 'Mostrar'" "1 0" "$(grep -c "class='ficha " js/console_alumnos.js) $(grep -c 'modal_mas' "$(dirname "$0")/../view/alumnos/view_alumnos.php")"
check "la vista de la ficha abre con sesión" "1" "$(curl -s -b $T/JERSSON123.jar $B/view/alumnos/view_ficha.php | grep -c 'id=\"ficha_contenido\"')"
terminar
