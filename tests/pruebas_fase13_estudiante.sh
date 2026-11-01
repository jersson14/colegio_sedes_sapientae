#!/usr/bin/env bash
# Fase 13 (después del paso 16): pantallas del estudiante rehechas: Mis tareas, Mis notas, Mis pagos y Mi asistencia.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
E=$B/controller/estudiante
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login JOSE; login DANIEL20
MAT=$(q "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by m.id_matricula desc limit 1")
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }

echo "== Solo para el estudiante, siempre con su propia matrícula"
check "las cuatro pantallas responden al estudiante" "1111" "$(for x in mis_tareas mis_notas mis_pagos mi_asistencia; do post DANIEL20 $E/controlador_$x.php | J '(int)$d["ok"]'; done | paste -sd' ')"
check "dirección y docente no entran" "403 403" "$(code JERSSON123 $E/controlador_mis_notas.php) $(code JOSE $E/controlador_mis_pagos.php)"
check "la matrícula sale de la sesión (no se puede pedir la de otro)" "$MAT $MAT" "$(post DANIEL20 $E/controlador_mis_pagos.php | J '$d["matricula"]["matricula"]') $(post DANIEL20 -d "matricula=1&id=1&anio=999" $E/controlador_mis_pagos.php | J '$d["matricula"]["matricula"]')"

echo "== Mis tareas"
TA=$(post DANIEL20 $E/controlador_mis_tareas.php)
check "todas sus tareas, con curso y docente" "$(q "select count(*) from detalle_tarea where id_matriculado=$MAT") 1" "$(echo "$TA" | J 'count($d["tareas"])') $(echo "$TA" | J '(int)(!empty($d["tareas"][0]["asignatura"]) && !empty($d["tareas"][0]["docente"]))')"
# Una tarea publicada antes de su matrícula también le aparece
CURSO=$(q "select dd.Id_detalle_asig_docente from detalle_asignatura_docente dd join asignaturas a on a.Id_asignatura=dd.Id_asignatura join matricula m on m.id_aula=a.Id_grado where m.id_matricula=$MAT limit 1")
$M -e "insert into tareas(id_tarea,id_detalle_asignatura,tema,descripcion,fecha_entrega,archivo_tarea,estado,created_at) values ('T9900099',$CURSO,'NUEVA','x',now()+interval 2 day,'','PENDIENTE',curdate())"
check "las tareas nuevas del curso le llegan como pendientes" "PENDIENTE" "$(post DANIEL20 $E/controlador_mis_tareas.php | J 'implode("",array_map(function($t){return $t["id"]==="T9900099"?$t["envio_estado"]:"";},$d["tareas"]))')"

echo "== Mis notas"
N=$(post DANIEL20 $E/controlador_mis_notas.php)
check "su libreta: él mismo, periodos y cursos del aula" "$MAT 1 1" "$(echo "$N" | J '$d["libreta"]["yo"]["matricula"]') $(echo "$N" | J '(int)(count($d["libreta"]["periodos"])>0)') $(echo "$N" | J '(int)(count($d["libreta"]["cursos"])>0)')"
check "no trae datos de los compañeros (solo cuántos son)" "0 1" "$(echo "$N" | J '(int)array_key_exists("estudiantes",$d["libreta"])') $(echo "$N" | J '(int)($d["libreta"]["total_aula"]>0)')"
check "mismo promedio y puesto que la libreta de la dirección" "$(post JERSSON123 -d "anio=$(echo "$N" | J '$d["matricula"]["anio_id"]')&aula=$(echo "$N" | J '$d["matricula"]["aula"]')" $B/controller/boletas/controlador_libreta.php | J 'implode("",array_map(function($e)use($argv){return $e["matricula"]==$argv[2]?$e["promedio"]."|".$e["puesto"]:"";},$d["libreta"]["estudiantes"]))' $MAT)" "$(echo "$N" | J '$d["libreta"]["yo"]["promedio"]."|".$d["libreta"]["yo"]["puesto"]')"

echo "== Mis pagos"
P=$(post DANIEL20 $E/controlador_mis_pagos.php)
check "sus pensiones del año con su estado" "$(q "select count(*) from vw_estado_pensiones where id_matricula=$MAT") 1" "$(echo "$P" | J 'count($d["pensiones"])') $(echo "$P" | J '(int)isset($d["pensiones"][0]["saldo_pension"],$d["pensiones"][0]["vencida"])')"
TOTAL=$(post JERSSON123 -d id=$MAT $B/controller/pago_pension/controlador_listar_tabla_pagos.php | J 'number_format($d["total_sub_total"],2,".","")')
check "los pagos agrupados por recibo suman lo mismo que el historial" "$TOTAL" "$(echo "$P" | J 'number_format(array_sum(array_column($d["recibos"],"total")),2,".","")')"

echo "== Mi asistencia"
A=$(post DANIEL20 $E/controlador_mi_asistencia.php)
check "cada día registrado, con código P/T/F/J" "$(q "select count(*) from asistencia where id_matricula=$MAT") 1" "$(echo "$A" | J 'count($d["dias"])') $(echo "$A" | J '(int)(count($d["dias"])===0 || in_array($d["dias"][0]["codigo"],["P","T","F","J"],true))')"
check "con los nombres de las marcas de Parámetros del sistema" "Presente Falta" "$(echo "$A" | J '$d["nombres"]["P"]." ".$d["nombres"]["F"]')"

echo "== Menú y pantallas viejas"
check "menú del estudiante con las pantallas nuevas" "1 1 1 1" "$(for v in mis_tareas mis_notas mis_pagos mi_asistencia; do grep -c "cargar_contenido('contenido_principal','estudiante/view_$v.php')\" class=\"nav-link\"" $T/DANIEL20.html; done | paste -sd' ')"
check "barra del celular: Notas y Pagos abren las nuevas" "1 1" "$(grep -c "estudiante/view_mis_notas.php');Barra_Activa" $T/DANIEL20.html) $(grep -c "estudiante/view_mis_pagos.php');Barra_Activa" $T/DANIEL20.html)"
check "sin enlaces a las pantallas viejas" "0" "$(grep -c 'view_notas_estudiante\|view_tareas_estudiante\|view_pago_pensiones_estudiante\|view_asistencia_reporte_estudiante' $T/DANIEL20.html)"
check "el estudiante ya no usa los listados viejos" "403 403" "$(code DANIEL20 -d id=$MAT $B/controller/pago_pension/controlador_listar_tabla_pagos.php) $(code DANIEL20 $B/controller/tareas/controlador_listar_tareas_estudiante_solo.php)"
terminar
