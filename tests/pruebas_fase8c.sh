#!/usr/bin/env bash
# Fase 8C: escala de notas por país. Requiere la BD de prueba recién reiniciada.
# (El escapado de datos en pantalla se prueba en el navegador: tests/navegador/prueba_escapado.sh)
source "$(dirname "$0")/lib.sh"
CFG=$B/controller/configuracion/controlador_regional_guardar.php
PE=$(q "select id_pais from paises where codigo_iso='PE'"); CL=$(q "select id_pais from paises where codigo_iso='CL'")
PEN=$(q "select id_moneda from monedas where codigo='PEN'"); CLP=$(q "select id_moneda from monedas where codigo='CLP'")
DT=$(q "select id_detalle_tarea from detalle_tarea order by id_detalle_tarea limit 1")
login JERSSON123

echo "== Escala por país"
check "Perú: 0 a 20, aprueba 11, AD/A/B/C" "0.00 20.00 11.00 0 AD,A,B,C AD,A,B" "$(q "select concat_ws(' ',nota_min,nota_max,nota_aprobatoria,nota_decimales,notas_literales,literales_aprobatorios) from paises where codigo_iso='PE'")"
check "Chile: 1.0 a 7.0, aprueba 4, 1 decimal" "1.00 7.00 4.00 1" "$(q "select concat_ws(' ',nota_min,nota_max,nota_aprobatoria,nota_decimales) from paises where codigo_iso='CL'")"
check "los 20 países tienen una escala coherente" "20" "$(q "select count(*) from paises where nota_max > nota_min and nota_aprobatoria between nota_min and nota_max")"
check "la escala llega al navegador (window.REGIONAL)" "1" "$(grep -c '"nota_aprobatoria":11' $T/JERSSON123.html)"
check "funciones PHP de la escala (prueba unitaria)" "fallas=0" "$(php "$R/tests/prueba_escala.php" | tail -1)"

echo "== Calificar tareas con la escala de Perú"
cal() { post JERSSON123 -d "id=$DT&nota=$1&obser=ok" $B/controller/tareas/controlador_registro_calificacion.php; }
check "nota 15 y literal 'ad' -> guardadas (AD)" "1 1 AD" "$(cal 15) $(cal ad) $(q "select calificacion from detalle_tarea where id_detalle_tarea=$DT")"
for n in 21 15.5 Z; do check "nota $n -> 400" "400" "$(code JERSSON123 -d "id=$DT&nota=$n&obser=x" $B/controller/tareas/controlador_registro_calificacion.php)"; done

echo "== Notas del bimestre"
check "registrar nota 25 -> rechazada (status 3)" "3" "$(post JERSSON123 --data-urlencode 'registros=[{"id_matri":"40","perio":"1","cri":"1","nota":"25","conclu":"x"}]' $B/controller/notas/controlador_registro_notas.php | jq_ status)"
check "nota de padres 'Q' -> rechazada" "0" "$(post JERSSON123 --data-urlencode 'registros=[{"id_matri":"40","perio":"1","criterio":"x","nota":"Q"}]' $B/controller/notas/controlador_registro_notas_padres.php | jq_ status)"
NB=$(q "select id_nota_bole from notas order by id_nota_bole limit 1")
check "editar nota a 30 -> rechazada (status 2)" "2" "$(post JERSSON123 --data-urlencode "registros=[{\"id_nota_bole\":\"$NB\",\"nota\":\"30\",\"conclusiones\":\"x\"}]" $B/controller/notas/controlador_editar_notas.php | jq_ status)"
check "editar nota a 'b' -> B, sin etiquetas en la conclusión" "1 B bien" "$(post JERSSON123 --data-urlencode "registros=[{\"id_nota_bole\":\"$NB\",\"nota\":\"b\",\"conclusiones\":\"<b>bien</b>\"}]" $B/controller/notas/controlador_editar_notas.php | jq_ status) $(q "select concat(trim(nota),' ',conclusiones) from notas where id_nota_bole=$NB")"

echo "== Cambiar a la escala de Chile"
check "mínimo mayor que el máximo -> 400" "400" "$(code JERSSON123 -d "pais=$CL&moneda=$CLP&zona=America/Santiago&nota_min=7&nota_max=1&nota_aprobatoria=4&nota_decimales=1&confirmar_moneda=1" $CFG)"
check "letras que aprueban fuera de la lista -> 400" "400" "$(code JERSSON123 -d "pais=$CL&moneda=$CLP&zona=America/Santiago&nota_min=1&nota_max=7&nota_aprobatoria=4&nota_decimales=1&notas_literales=A,B&literales_aprobatorios=Z&confirmar_moneda=1" $CFG)"
check "guardar Chile 1 a 7, aprueba 4, 1 decimal (queda en el historial)" "true 1" "$(post JERSSON123 -d "pais=$CL&moneda=$CLP&zona=America/Santiago&id_tributario=&extranjero=1&confirmar_moneda=1&nota_min=1&nota_max=7&nota_aprobatoria=4&nota_decimales=1&notas_literales=&literales_aprobatorios=" $CFG | jq_ ok) $(q "select count(*) from historial_configuracion where campo='escala_notas'")"
login JERSSON123
check "nota 6.5 y 6,5 -> guardadas como 6.5" "1 1 6.5" "$(cal 6.5) $(cal 6%2C5) $(q "select calificacion from detalle_tarea where id_detalle_tarea=$DT")"
for n in 7.5 6.55 0.5 AD; do check "nota $n -> 400" "400" "$(code JERSSON123 -d "id=$DT&nota=$n&obser=x" $B/controller/tareas/controlador_registro_calificacion.php)"; done
check "sin enviar la escala se conserva la actual" "true 7.00" "$(post JERSSON123 -d "pais=$CL&moneda=$CLP&zona=America/Santiago&id_tributario=&extranjero=1&confirmar_moneda=1" $CFG | jq_ ok) $(q "select nota_max from empresa limit 1")"
check "volver a Perú" "true" "$(post JERSSON123 -d "pais=$PE&moneda=$PEN&zona=America/Lima&id_tributario=&extranjero=1&confirmar_moneda=1&nota_min=0&nota_max=20&nota_aprobatoria=11&nota_decimales=0&notas_literales=AD,A,B,C&literales_aprobatorios=AD,A,B" $CFG | jq_ ok)"
terminar
