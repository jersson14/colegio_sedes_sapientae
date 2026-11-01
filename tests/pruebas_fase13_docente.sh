#!/usr/bin/env bash
# Fase 13 (después del paso 16): pantallas del docente rehechas: Notas de mis cursos (planilla), Tareas y Exámenes de mis cursos, Mis boletas.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
RN=$B/controller/notas/controlador_registro_notas_curso.php
TD=$B/controller/docente/controlador_trabajos.php
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login JOSE; login DANIEL20; login MANUEL123
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
# Un curso de JOSE con tres criterios; un curso ajeno
CURSO=$(q "select dd.Id_detalle_asig_docente from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente join usuario u on u.usu_id=d.id_asusuario join asignaturas a on a.Id_asignatura=dd.Id_asignatura where u.usu_usuario='JOSE' and a.nombre_asig not like 'RECREO%' and not exists (select 1 from criterios c where c.id_detalle_asignatura=dd.Id_detalle_asig_docente) order by 1 limit 1")
AJENO=$(q "select dd.Id_detalle_asig_docente from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente join usuario u on u.usu_id=d.id_asusuario where u.usu_usuario<>'JOSE' order by 1 limit 1")
sqlf "insert into criterios(id_detalle_asignatura, competencias, \`descripción_observa\`, estado, created_at) values ($CURSO,'CRITERIO UNO','','ACTIVO',now()),($CURSO,'CRITERIO DOS','','ACTIVO',now())" > /dev/null
C1=$(q "select min(id_criterio) from criterios where id_detalle_asignatura=$CURSO"); C2=$(q "select max(id_criterio) from criterios where id_detalle_asignatura=$CURSO")
L=$(post JOSE -d "accion=cargar&curso=$CURSO" $RN)
PER=$(echo "$L" | J '$d["periodo"]["id"]')
MAT=$(echo "$L" | J '$d["estudiantes"][0]["matricula"]')
OTRA=$(q "select m.id_matricula from matricula m where m.id_matricula not in (select m2.id_matricula from matricula m2 join asignaturas a on a.Id_grado=m2.id_aula join detalle_asignatura_docente dd on dd.Id_asignatura=a.Id_asignatura where dd.Id_detalle_asig_docente=$CURSO) limit 1")
g() { post ${2:-JOSE} -d "accion=guardar&curso=$CURSO&periodo=$PER" --data-urlencode "cambios=$1" $RN; }
nota() { q "select ifnull(group_concat(nota),'') from notas where id_matricula=$MAT and id_bimestre=$PER and id_criterio=$1"; }

echo "== Notas de mis cursos (planilla)"
N_CUR=$(sqlf "select count(*) from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente join usuario u on u.usu_id=d.id_asusuario join asignaturas a on a.Id_asignatura=dd.Id_asignatura where u.usu_usuario='JOSE' and ad.\`id_año\`=(select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1) and a.nombre_asig not like 'RECREO%'")
check "el docente ve solo sus cursos, con criterios y estudiantes del aula" "$N_CUR 2 1" "$(echo "$L" | J 'count($d["cursos"])') $(echo "$L" | J 'count($d["criterios"])') $(echo "$L" | J '(int)(count($d["estudiantes"])>0)')"
check "no abre ni califica un curso ajeno; el estudiante y el auxiliar no entran" "403 403 403 403" "$(code JOSE -d "accion=cargar&curso=$AJENO" $RN) $(code JOSE -d "accion=guardar&curso=$AJENO&periodo=$PER&cambios=[]" $RN) $(code DANIEL20 $RN) $(code MANUEL123 $RN)"
check "guardar dos notas nuevas" "2 15 08" "$(g "[{\"matricula\":$MAT,\"criterio\":$C1,\"nota\":\"15\"},{\"matricula\":$MAT,\"criterio\":$C2,\"nota\":\"8\"}]" | J '$d["guardadas"]') $(nota $C1) $(nota $C2)"
check "el promedio sale con la regla de la libreta" "12" "$(post JOSE -d "accion=cargar&curso=$CURSO&periodo=$PER" $RN | J '$d["promedios"]['$MAT']')"
check "corregir una nota no la duplica; vaciarla la borra" "1 17 ''" "$(g "[{\"matricula\":$MAT,\"criterio\":$C1,\"nota\":\"17\",\"conclusion\":\"Mejoro bastante\"}]" | J '$d["guardadas"]') $(nota $C1) '$(g "[{\"matricula\":$MAT,\"criterio\":$C2,\"nota\":\"\"}]" > /dev/null; nota $C2)'"
check "la conclusión se guarda con la nota" "Mejoro bastante" "$(sqlf "select conclusiones from notas where id_matricula=$MAT and id_bimestre=$PER and id_criterio=$C1")"
check "nota fuera de la escala -> 400 (no guarda nada)" "400 17" "$(code JOSE -d "accion=guardar&curso=$CURSO&periodo=$PER" --data-urlencode "cambios=[{\"matricula\":$MAT,\"criterio\":$C1,\"nota\":\"25\"}]" $RN) $(nota $C1)"
check "estudiante de otra aula o criterio de otro curso -> 403" "403 403" "$(code JOSE -d "accion=guardar&curso=$CURSO&periodo=$PER" --data-urlencode "cambios=[{\"matricula\":$OTRA,\"criterio\":$C1,\"nota\":\"10\"}]" $RN) $(code JOSE -d "accion=guardar&curso=$CURSO&periodo=$PER" --data-urlencode "cambios=[{\"matricula\":$MAT,\"criterio\":999999,\"nota\":\"10\"}]" $RN)"
$M -e "update periodos set estado='FINALIZADO' where id_periodo=$PER"
check "periodo finalizado: el docente ve bloqueado y no cambia; la dirección sí" "true 400 1" "$(post JOSE -d "accion=cargar&curso=$CURSO&periodo=$PER" $RN | J '$d["bloqueado"]?"true":"false"') $(code JOSE -d "accion=guardar&curso=$CURSO&periodo=$PER" --data-urlencode "cambios=[{\"matricula\":$MAT,\"criterio\":$C1,\"nota\":\"10\"}]" $RN) $(g "[{\"matricula\":$MAT,\"criterio\":$C1,\"nota\":\"18\"}]" JERSSON123 | J '$d["guardadas"]')"
$M -e "update periodos set estado='EN CURSO' where id_periodo=$PER"

echo "== Tareas y exámenes de mis cursos"
TT=$(post JOSE -d accion=tareas $TD)
check "tareas solo de sus cursos, con su avance de entregas" "0 1" "$(echo "$TT" | J 'count(array_filter($d["items"],function($x)use($argv){return !in_array($x["curso"],array_column($GLOBALS["d"]["cursos"],"id"));}))') $(echo "$TT" | J '(int)(count($d["items"])===0 || isset($d["items"][0]["asignadas"],$d["items"][0]["entregadas"]))')"
check "programar un examen (mismo controlador de siempre) y verlo en la lista" "1 1" "$(post JOSE -d "asig=$CURSO&tema=Examen de prueba&descrip=Unidad 1&fecha=2099-05-10 09:00" $B/controller/examenes/controlador_registro_examenes.php | tr -d '[:space:]') $(post JOSE -d accion=examenes $TD | J 'count(array_filter($d["items"],function($x){return $x["tema"]==="EXAMEN DE PRUEBA";}))')"
check "no programa exámenes en un curso ajeno" "403" "$(code JOSE -d "asig=$AJENO&tema=X&descrip=&fecha=2099-05-10 09:00" $B/controller/examenes/controlador_registro_examenes.php)"
check "el estudiante no usa el listado del docente" "403" "$(code DANIEL20 -d accion=tareas $TD)"

echo "== Menú y boletas"
check "menú del docente con las pantallas nuevas" "1 1 1" "$(for v in notas/view_registro_notas.php docente/view_tareas_docente.php docente/view_examenes_docente.php; do grep -c "cargar_contenido('contenido_principal','$v')\" class=\"nav-link\"" $T/JOSE.html; done | paste -sd' ')"
check "sin enlaces a las pantallas viejas" "0" "$(grep -c 'view_notas_profesor\|view_tareas_profesor\|view_examen_profesor' $T/JOSE.html)"
check "mis boletas responde con la lista del usuario en sesión" "true" "$(post JOSE $B/controller/planilla/controlador_mis_boletas.php | jq_ ok)"
terminar
