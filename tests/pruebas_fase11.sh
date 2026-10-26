#!/usr/bin/env bash
# Fase 11: planilla mensual de asistencia, registro de tareas, asignación de tareas y reporte de estudiantes a medida.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
RA=$B/controller/registro_asistencia
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','jorge')"
login JERSSON123; login JOSE; login jorge; login DANIEL20; login CONTA1
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
AULA=18                 # secundaria quinto grado (7 estudiantes); JOSE no enseña ahí
AULA_JOSE=5             # inicial 3 años
DIA=$(php -r 'date_default_timezone_set("America/Lima"); echo date("Y-m-d", strtotime("monday this week") > time() ? strtotime("monday last week") : strtotime("monday this week"));')
MES=${DIA:0:7}
FUTURO=$(php -r 'date_default_timezone_set("America/Lima"); echo date("Y-m-d", strtotime("monday next week"));')
SABADO=$(php -r 'date_default_timezone_set("America/Lima"); echo date("Y-m-d", strtotime("saturday last week"));')

echo "== Base de datos (migración 013)"
check "un registro por estudiante y día (índice único)" "1" "$(q "select count(*) from information_schema.statistics where table_schema='colegio2_test' and table_name='asistencia' and index_name='uq_asistencia_dia' and seq_in_index=1")"
MAT=$(q "select id_matricula from matricula where id_aula=$AULA limit 1")
check "SP con fecha pasada: registra y luego detecta el duplicado" "1 2" "$(q "call SP_REGISTRAR_ASISTENCIA($MAT, '2026-03-10', 'PRESENTE', '')") $(q "call SP_REGISTRAR_ASISTENCIA($MAT, '2026-03-10', 'AUSENTE', '')")"
check "el mes guardado es el de la fecha marcada" "3" "$(q "select mes from asistencia where id_matricula=$MAT and fecha='2026-03-10'")"

echo "== Planilla mensual: quién ve qué aulas"
J=$(post JERSSON123 -d "anio=$ANIO&aula=$AULA&mes=$MES" $RA/controlador_planilla.php)
check "el administrador ve todas las aulas con estudiantes y la planilla del aula" "5 7" "$(echo "$J" | jq_ aulas) $(echo "$J" | jq_ estudiantes)"
check "solo días de lunes a viernes" "0" "$(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo count(array_filter($d["dias"], function($x){ return date("N", strtotime($x["fecha"])) > 5; }));')"
check "el docente solo ve las aulas donde enseña" "2" "$(post JOSE -d "anio=$ANIO" $RA/controlador_planilla.php | jq_ aulas)"
check "el docente NO abre la planilla de un aula ajena" "403" "$(code JOSE -d "anio=$ANIO&aula=$AULA&mes=$MES" $RA/controlador_planilla.php)"
check "el estudiante NO entra" "403 403" "$(code DANIEL20 -d "anio=$ANIO" $RA/controlador_planilla.php) $(code DANIEL20 -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"

echo "== Guardar marcas"
M1=$(q "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO order by id_matricula limit 1" 2>/dev/null || true)
M1=$(sqlf "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO order by id_matricula limit 1")
M2=$(sqlf "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO order by id_matricula limit 1,1")
r=$(post JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M1,\"fecha\":\"$DIA\",\"codigo\":\"P\"},{\"matricula\":$M2,\"fecha\":\"$DIA\",\"codigo\":\"F\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)
check "guarda dos marcas" "2" "$(echo "$r" | jq_ guardados)"
check "quedan como PRESENTE y AUSENTE" "PRESENTE AUSENTE" "$(q "select estado from asistencia where id_matricula=$M1 and fecha='$DIA'") $(q "select estado from asistencia where id_matricula=$M2 and fecha='$DIA'")"
post JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M2,\"fecha\":\"$DIA\",\"codigo\":\"J\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php > /dev/null
check "cambiar la marca actualiza (sin duplicar)" "JUSTIFICADO 1" "$(q "select estado from asistencia where id_matricula=$M2 and fecha='$DIA'") $(q "select count(*) from asistencia where id_matricula=$M2 and fecha='$DIA'")"
post JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M2,\"fecha\":\"$DIA\",\"codigo\":\"\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php > /dev/null
check "dejar vacía la casilla borra la marca" "0" "$(q "select count(*) from asistencia where id_matricula=$M2 and fecha='$DIA'")"
J=$(post JERSSON123 -d "anio=$ANIO&aula=$AULA&mes=$MES" $RA/controlador_planilla.php)
check "la planilla devuelve la marca y los totales" "P 1 100" "$(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["estudiantes"] as $e){ if($e["matricula"]=='$M1'){ echo $e["marcas"][(int)substr("'$DIA'",8)]," ",$e["totales"]["P"]," ",$e["totales"]["porcentaje"]; } }')"
check "día futuro: rechazado" "400" "$(code JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M1,\"fecha\":\"$FUTURO\",\"codigo\":\"P\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"
check "sábado: rechazado" "400" "$(code JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M1,\"fecha\":\"$SABADO\",\"codigo\":\"P\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"
check "código inválido: rechazado" "400" "$(code JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M1,\"fecha\":\"$DIA\",\"codigo\":\"X\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"
OTRA=$(sqlf "select id_matricula from matricula where id_aula=$AULA_JOSE and \`id_año\`=$ANIO limit 1")
check "estudiante de otra aula: rechazado y no se guarda nada" "400 0" "$(code JERSSON123 --data-urlencode "cambios=[{\"matricula\":$M2,\"fecha\":\"$DIA\",\"codigo\":\"P\"},{\"matricula\":$OTRA,\"fecha\":\"$DIA\",\"codigo\":\"P\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php) $(q "select count(*) from asistencia where id_matricula=$M2 and fecha='$DIA'")"
check "el docente NO guarda en un aula ajena" "403" "$(code JOSE --data-urlencode "cambios=[{\"matricula\":$M1,\"fecha\":\"$DIA\",\"codigo\":\"F\"}]" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"
check "el docente sí guarda en su aula" "1" "$(post JOSE --data-urlencode "cambios=[{\"matricula\":$OTRA,\"fecha\":\"$DIA\",\"codigo\":\"T\"}]" -d "anio=$ANIO&aula=$AULA_JOSE" $RA/controlador_guardar.php | jq_ guardados)"
check "PDF de la planilla (con marcas y en blanco)" "200 application/pdf 200" "$(curl -s -o /dev/null -w '%{http_code} %{content_type}' -b $T/JERSSON123.jar "$B/view/MPDF/REPORTE/asistencia_mensual.php?anio=$ANIO&aula=$AULA&mes=$MES") $(curl -s -o /dev/null -w '%{http_code}' -b $T/JERSSON123.jar "$B/view/MPDF/REPORTE/asistencia_mensual.php?anio=$ANIO&aula=$AULA&mes=$MES&vacia=1")"
check "PDF: el docente NO imprime un aula ajena; el estudiante no entra" "403 403" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/JOSE.jar "$B/view/MPDF/REPORTE/asistencia_mensual.php?anio=$ANIO&aula=$AULA&mes=$MES") $(curl -s -o /dev/null -w '%{http_code}' -b $T/DANIEL20.jar "$B/view/MPDF/REPORTE/asistencia_mensual.php?anio=$ANIO&aula=$AULA&mes=$MES")"

echo "== Tareas: llegan a los estudiantes del año escolar del curso (migración 014)"
r=$(post JOSE -F asig=20 -F tema=PRUEBA_ASIGNA -F fecha=2030-01-01T10:00 -F descrip=X -F 'enlaces=https://youtu.be/dQw4w9WgXcQ' $B/controller/tareas/controlador_registro_tareas.php)
check "nueva tarea asignada a los 5 estudiantes del aula (antes 0)" "1 5" "$r $(q "select count(*) from detalle_tarea d join tareas t on t.id_tarea=d.id_tarea where t.tema='PRUEBA_ASIGNA'")"
check "la calificación inicial queda vacía (no 0)" "0" "$(q "select count(*) from detalle_tarea d join tareas t on t.id_tarea=d.id_tarea where t.tema='PRUEBA_ASIGNA' and d.calificacion is not null")"
ID=$(q "select id_tarea from tareas where tema='PRUEBA_ASIGNA'")
q "delete from detalle_tarea where id_tarea='$ID' order by id_detalle_tarea desc limit 1" > /dev/null

echo "== Registro de tareas"
J=$(post JOSE -d "anio=$ANIO&curso=20" $B/controller/registro_tareas/controlador_registro.php)
check "el docente ve su curso con sus tareas y estudiantes" "2 5" "$(echo "$J" | jq_ tareas) $(echo "$J" | jq_ estudiantes)"
check "el estudiante que no tenía la tarea la recibe al abrir el registro" "5" "$(q "select count(*) from detalle_tarea where id_tarea='$ID'")"
check "otro docente NO abre el registro de un curso ajeno" "403" "$(code jorge -d "anio=$ANIO&curso=20" $B/controller/registro_tareas/controlador_registro.php)"
check "el docente solo lista sus cursos" "0" "$(post JOSE -d "anio=$ANIO" $B/controller/registro_tareas/controlador_registro.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo count(array_filter($d["cursos"], function($c){ return strpos($c["docente"], "JOSE") === false && strpos($c["docente"], "JULIAN") === false; }));')"
check "el estudiante NO entra" "403" "$(code DANIEL20 -d "anio=$ANIO" $B/controller/registro_tareas/controlador_registro.php)"

echo "== Reporte de estudiantes a medida"
RE=$B/controller/reportes/controlador_reporte_estudiantes.php
check "catálogo de campos" "28" "$(post JERSSON123 -d accion=catalogo $RE | jq_ campos)"
J=$(post JERSSON123 -d "anio=$ANIO&campos=documento,apellidos,deuda" $RE)
check "columnas en el orden elegido" "Documento Apellidos Deuda vencida" "$(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo implode(" ", array_column($d["columnas"], "titulo"));')"
check "todos los matriculados del año" "$(sqlf "select count(*) from matricula where \`id_año\`=$ANIO")" "$(echo "$J" | jq_ filas)"
MOROSOS=$(post CONTA1 -d "anio=$ANIO&nivel=0&aula=0" $B/controller/reportes/controlador_morosidad.php | jq_ alumnos)
J=$(post CONTA1 -d "anio=$ANIO&campos=documento,deuda&con_deuda=1" $RE)
check "con deuda vencida: los mismos alumnos y total que Morosidad" "$MOROSOS $(post CONTA1 -d "anio=$ANIO&nivel=0&aula=0" $B/controller/reportes/controlador_morosidad.php | jq_ totales.deuda)" "$(echo "$J" | jq_ filas) $(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo number_format(array_sum(array_column($d["filas"], 1)),2,".","");')"
check "filtro por aula y sexo" "$(q "select count(*) from matricula m join alumnos a on a.Id_alumno=m.id_alumno where m.id_aula=$AULA and a.alum_sexo='FEMENINO'")" "$(post JERSSON123 -d "anio=$ANIO&aula=$AULA&sexo=FEMENINO&campos=nombres" $RE | jq_ filas)"
check "un campo que no está en el catálogo se ignora (no llega a la consulta)" "1" "$(post JERSSON123 --data-urlencode "campos=nombres,(select usu_contra from usuario limit 1)" -d "anio=$ANIO" $RE | jq_ columnas)"
check "sin campos: aviso" "400" "$(code JERSSON123 -d "anio=$ANIO&campos=" $RE)"
check "el estudiante y el docente NO entran" "403 403" "$(code DANIEL20 -d accion=catalogo $RE) $(code JOSE -d accion=catalogo $RE)"

for d in $(q "select substring_index(archivo_tarea,'/',-1) from tareas where tema='PRUEBA_ASIGNA'"); do rm -rf "$R/controller/tareas/controller/tareas/documentos/$d"; done
terminar
