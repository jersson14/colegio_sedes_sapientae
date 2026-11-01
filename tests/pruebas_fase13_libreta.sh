#!/usr/bin/env bash
# Fase 13 paso 9: libreta de notas por periodo, promedios con la escala, orden de mérito y PDF.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
C=$B/controller/boletas/controlador_libreta.php
PDF=$B/view/MPDF/REPORTE/libreta.php
V=$(dirname "$0")/..
crear_contador
login JERSSON123; login DANIEL20; login CONTA1
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
AULA=18
I=$(sqlf "select id_periodo from periodos where \`id_año_escolar\`=$ANIO and periodos='I BIMESTRE'")
IV=$(sqlf "select id_periodo from periodos where \`id_año_escolar\`=$ANIO and periodos='IV BIMESTRE'")
A=$(sqlf "select m.id_matricula from matricula m join notas n on n.id_matricula=m.id_matricula where m.id_aula=$AULA limit 1")
B2=$(sqlf "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO and id_matricula<>$A order by id_matricula limit 1")
C3=$(sqlf "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO and id_matricula not in ($A,$B2) order by id_matricula limit 1")
DANI=$(sqlf "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20'")
# A: IV 12,15,15 (ya está) -> 14 ; I 16,16 -> 16 ; final 15
# B: IV 15,13 -> 14 ; I 16 -> 16 ; final 15 (empata con A)
# C: IV 8,9 -> 8.5 -> 9 (redondeo hacia arriba, desaprobado)
sqlf "insert into notas(id_matricula,id_bimestre,id_criterio,nota,conclusiones,creared_at) values
 ($A,$I,42,'16','',curdate()),($A,$I,43,'16','',curdate()),
 ($B2,$IV,42,'15','',curdate()),($B2,$IV,43,'13','',curdate()),($B2,$I,42,'16','',curdate()),
 ($C3,$IV,42,'8','',curdate()),($C3,$IV,43,'9','',curdate())" > /dev/null
lib() { post JERSSON123 -d "anio=$ANIO&aula=$AULA&periodo=$1" $C; }
fila() { php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["libreta"]["estudiantes"] as $e){ if($e["matricula"]==$argv[1]){ echo ($e["puesto"]??"-")," ",($e["promedio"]??"-")," ",$e["desaprobados"]; } }' "$1"; }

echo "== Notas por periodo y orden de mérito"
R=$(lib $IV)
check "IV bimestre: A 14 (12,15,15), puesto 1" "1 14 0" "$(echo "$R" | fila $A)"
check "IV bimestre: B 14 empata con A y comparte el puesto 1" "1 14 0" "$(echo "$R" | fila $B2)"
check "IV bimestre: C 8 y 9 -> 9, desaprobado, puesto 3 (después del empate)" "3 9 1" "$(echo "$R" | fila $C3)"
check "sin notas: al final y sin puesto" "-" "$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); $u=end($d["libreta"]["estudiantes"]); echo $u["puesto"]??"-";')"
check "el detalle por criterio no viaja a la pantalla" "0" "$(echo "$R" | grep -c '"detalle"')"
R=$(lib "")
check "todo el año: A y B 15 (promedio de I e IV); C 9" "1 15 0|1 15 0|3 9 1" "$(echo "$R" | fila $A)|$(echo "$R" | fila $B2)|$(echo "$R" | fila $C3)"
check "todo el año: una columna por periodo" "1 4" "$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["libreta"]; echo (int)$d["todo_el_anio"]," ",count($d["periodos"]);')"
check "periodo de otro año -> 400; la contadora no entra" "400 403" "$(code JERSSON123 -d "anio=$ANIO&aula=$AULA&periodo=999999" $C) $(code CONTA1 -d "anio=$ANIO" $C)"

echo "== Reglas de la escala (utilitario/libreta.php)"
E=$(cd "$V" && php -r 'require "utilitario/libreta.php"; echo libreta_promedio(["10","11"])," ",libreta_promedio(["AD","A","B"])," ",libreta_promedio(["A","B","C"])," ",libreta_letra_de_numero(18)," ",var_export(libreta_promedio(["x"]),true);')
check "10,11 -> 11; AD,A,B -> A; A,B,C -> B; 18 -> AD; nota inválida -> null" "11 A B AD NULL" "$E"

echo "== PDF"
pdf() { curl -s -b $T/$1.jar -o $T/l.pdf -w '%{http_code}' "$PDF?$2"; echo -n " $(head -c 4 $T/l.pdf)"; }
check "libreta de un estudiante, del año y de un periodo" "200 %PDF 200 %PDF" "$(pdf JERSSON123 "matricula=$A") $(pdf JERSSON123 "matricula=$A&periodo=$IV")"
check "libretas del aula y orden de mérito" "200 %PDF 200 %PDF" "$(pdf JERSSON123 "anio=$ANIO&aula=$AULA") $(pdf JERSSON123 "anio=$ANIO&aula=$AULA&merito=1")"
check "el estudiante abre la suya, no la de otro ni la del aula" "200 403 403" "$(pdf DANIEL20 "matricula=$DANI" | cut -c1-3) $(pdf DANIEL20 "matricula=$A" | cut -c1-3) $(pdf DANIEL20 "anio=$ANIO&aula=$AULA" | cut -c1-3)"
check "la contadora no abre libretas" "403" "$(pdf CONTA1 "matricula=$A" | cut -c1-3)"
check "sin datos de una región fija en el PDF ni en el código" "0" "$(grep -rlE 'APURÍMAC|UGEL Abancay' $V/view/MPDF/REPORTE/*.php | wc -l)"

echo "== Pantallas"
check "Libretas de notas en el menú y su script" "1 1" "$(grep -c '<p>Libretas de notas</p>' $T/JERSSON123.html) $(curl -s -b $T/JERSSON123.jar $B/view/boletas/view_boletas.php | grep -c 'libretas.js')"
check "el estudiante tiene el botón de su libreta (Mis notas)" "1" "$(grep -c "REPORTE/libreta.php?matricula=" $V/js/mi_portal.js)"
check "ya no existen las boletas antiguas" "0 0 0" "$(ls $V/js/console_boletas.js $V/view/MPDF/REPORTE/notas_por_bimestre.php $V/view/MPDF/REPORTE/notas_general.php 2>/dev/null | wc -l) 0 0"
terminar
