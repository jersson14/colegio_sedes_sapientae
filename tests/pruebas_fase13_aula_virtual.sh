#!/usr/bin/env bash
# Fase 13 (después del paso 16): calendario de horarios (semana y mes) y aula virtual por curso.
# Requiere la BD de prueba recién reiniciada (migración 023).
source "$(dirname "$0")/lib.sh"
HC=$B/controller/horarios/controlador_calendario.php
AV=$B/controller/aula_virtual/controlador_aula_virtual.php
PA=$B/controller/configuracion/controlador_parametros.php
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login MANUEL123; login JOSE; login DANIEL20; login CONTA1
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
AULA_D=$(q "select m.id_aula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by m.id_matricula desc limit 1")
MAT_D=$(q "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by m.id_matricula desc limit 1")
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }

echo "== Calendario de horarios"
check "el estudiante ve el horario de su aula, aunque pida otra" "aula $AULA_D" "$(post DANIEL20 -d aula=5 $HC | J '$d["modo"]." ".$d["aula"]')"
N_AULA=$(sqlf "select count(*) from horarios h join horas_aula ha on ha.id_hora=h.id_hora_aula where h.estado='ACTIVO' and ha.estado='ACTIVO' and ha.id_aula=$AULA_D and ha.\`id_año_academico\`=$ANIO")
check "todas las clases activas del aula, con día, hora, curso y docente" "$N_AULA 1" "$(post DANIEL20 $HC | J 'count($d["clases"])." ".(int)(isset($d["clases"][0]["dia"],$d["clases"][0]["inicio"],$d["clases"][0]["asignatura"],$d["clases"][0]["docente"]))')"
check "con las fechas del año escolar (para el mes)" "1" "$(post DANIEL20 $HC | J '(int)(!empty($d["anio"]["fecha_inicio"]) && !empty($d["anio"]["fecha_fin"]))')"
N_DOC=$(sqlf "select count(*) from horarios h join horas_aula ha on ha.id_hora=h.id_hora_aula join detalle_asignatura_docente dd on dd.Id_detalle_asig_docente=h.id_detalle_asig_docente join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente join usuario u on u.usu_id=d.id_asusuario where u.usu_usuario='JOSE' and h.estado='ACTIVO' and ha.estado='ACTIVO' and ha.id_aula in (select id_aula from horas_aula) and ha.\`id_año_academico\`=$ANIO")
check "el docente ve sus clases de todas sus aulas" "mio $N_DOC" "$(post JOSE $HC | J '$d["modo"]." ".count($d["clases"])')"
check "el docente no abre un aula donde no enseña" "403" "$(code JOSE -d aula=$AULA_D $HC)"
check "dirección y auxiliar ven cualquier aula; la contadora no entra" "$AULA_D $AULA_D 403" "$(post JERSSON123 -d aula=$AULA_D $HC | J '$d["aula"]') $(post MANUEL123 -d aula=$AULA_D $HC | J '$d["aula"]') $(code CONTA1 $HC)"

echo "== Sábados en el horario"
HORA=$(sqlf "select id_hora from horas_aula where id_aula=$AULA_D and \`id_año_academico\`=$ANIO and estado='ACTIVO' limit 1")
CURSO=$(q "select h.id_detalle_asig_docente from horarios h join horas_aula ha on ha.id_hora=h.id_hora_aula where ha.id_aula=$AULA_D and h.estado='ACTIVO' limit 1")
BLOQUE="componentes=[{\"idhora\":$HORA,\"idasig\":$CURSO,\"dia\":\"SABADO\"}]"
check "sin clases los sábados, el horario no acepta el sábado" "400" "$(code JERSSON123 --data-urlencode "$BLOQUE" $B/controller/horarios/controlador_registro_horario_aula.php)"
post JERSSON123 -d "accion=guardar&login_max_intentos=5&login_minutos_bloqueo=15&video_max_mb=100&clases_sabado=1&asistencia_P=Presente&asistencia_T=Tardanza&asistencia_F=Falta&asistencia_J=Justificada" $PA > /dev/null
code JERSSON123 --data-urlencode "$BLOQUE" $B/controller/horarios/controlador_registro_horario_aula.php > /dev/null
check "con sábados se guarda y aparece en el calendario (día 6)" "1 true" "$(q "select count(*) from horarios where dia='SABADO' and id_hora_aula=$HORA") $(post DANIEL20 $HC | J '(count(array_filter($d["clases"],function($c){return $c["dia"]==6;}))>0 ? "true":"false")')"
check "el editor de horarios ofrece el sábado según el parámetro (en sus dos listas de días)" "2" "$(grep -c "parametro('clases_sabado')" "$R/view/horario/view_horario.php")"

echo "== Aula virtual: cursos"
N_CUR=$(sqlf "select count(*) from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join asignaturas a on a.Id_asignatura=dd.Id_asignatura where a.Id_grado=$AULA_D and ad.\`id_año\`=$ANIO and a.nombre_asig not like 'RECREO%'")
L=$(post DANIEL20 $AV)
check "el estudiante ve los cursos de su aula (sin el recreo)" "$N_CUR 0" "$(echo "$L" | J 'count($d["cursos"])') $(echo "$L" | J 'count(array_filter($d["cursos"],function($c){return stripos($c["asignatura"],"RECREO")===0;}))')"
check "cada tarjeta trae docente, próxima clase y sus tareas por entregar o vencidas" "1" "$(echo "$L" | J '(int)(array_key_exists("docente",$d["cursos"][0]) && array_key_exists("proxima",$d["cursos"][0]) && isset($d["cursos"][0]["por_entregar"],$d["cursos"][0]["vencidas"]))')"
N_JOSE=$(sqlf "select count(*) from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente join usuario u on u.usu_id=d.id_asusuario join asignaturas a on a.Id_asignatura=dd.Id_asignatura where u.usu_usuario='JOSE' and ad.\`id_año\`=$ANIO and a.nombre_asig not like 'RECREO%'")
check "el docente ve sus cursos con lo que tiene por revisar" "$N_JOSE 1" "$(post JOSE $AV | J 'count($d["cursos"])." ".(int)isset($d["cursos"][0]["por_revisar"])')"
check "dirección: elige el aula; la contadora no entra" "$AULA_D 403" "$(post JERSSON123 -d aula=$AULA_D $AV | J '$d["aula"]') $(code CONTA1 $AV)"

echo "== Aula virtual: un curso"
# Un curso del aula de Daniel con una tarea abierta, y otro de otra aula
CD=$(q "select t.id_detalle_asignatura from tareas t join detalle_tarea dt on dt.id_tarea=t.id_tarea where dt.id_matriculado=$MAT_D and t.estado='PENDIENTE' and dt.estado='PENDIENTE' limit 1")
OTRO=$(q "select dd.Id_detalle_asig_docente from detalle_asignatura_docente dd join asignaturas a on a.Id_asignatura=dd.Id_asignatura where a.Id_grado<>$AULA_D limit 1")
C=$(post DANIEL20 -d "accion=curso&curso=$CD" $AV)
check "el estudiante abre su curso: tareas con su entrega, horario y compañeros" "1 1 1" "$(echo "$C" | J '(int)(count($d["tareas"])>0)." ".(int)array_key_exists("envio_estado",$d["tareas"][0])." ".(int)(count($d["estudiantes"])>0)')"
check "no abre un curso de otra aula; el docente no abre cursos ajenos" "403 403" "$(code DANIEL20 -d "accion=curso&curso=$OTRO" $AV) $(code JOSE -d "accion=curso&curso=$CD" $AV)"
check "a los compañeros solo se les ve el nombre" "apellidos,nombres" "$(echo "$C" | J 'implode(",",array_keys($d["estudiantes"][0]))' | tr ',' '\n' | sort | paste -sd,)"
ENV=$(echo "$C" | J 'implode("",array_map(function($t){return $t["envio_estado"]==="PENDIENTE" && $t["estado"]==="PENDIENTE" ? $t["envio"]." " : "";},$d["tareas"]))' | awk '{print $1}')
printf '%%PDF-1.4\n%%%%EOF\n' > "$T/trabajo.pdf"
R1=$(post DANIEL20 -F "iddetalle=$ENV" -F "archivoactual=" -F "archivos[]=@$T/trabajo.pdf;type=application/pdf" $B/controller/tareas/controlador_registro_tareas_estudiante.php)
check "entregar desde el aula virtual (mismo controlador de tareas) y ver 'Entregada'" "1 ENVIADO" "$(echo "$R1" | tr -d '[:space:]') $(post DANIEL20 -d "accion=curso&curso=$CD" $AV | J 'implode("",array_map(function($t)use($argv){return $t["envio"]==$argv[2]?$t["envio_estado"]:"";},$d["tareas"]))' "$ENV")"
check "el docente del curso ve esa entrega por revisar" "1" "$(post JERSSON123 -d "accion=curso&curso=$CD" $AV | J '(int)(array_sum(array_column($d["tareas"],"entregadas"))>=1)')"

echo "== Menú y permisos"
check "aula virtual en el menú de dirección, auxiliar, docente y estudiante" "1 1 1 1" "$(for u in JERSSON123 MANUEL123 JOSE DANIEL20; do grep -c "cargar_contenido('contenido_principal','aula_virtual/view_aula_virtual.php')\"" $T/$u.html; done | paste -sd' ')"
check "calendario de horarios en los cuatro; 'Mi horario' del estudiante (menú e inicio) es el calendario" "1 1 1 2 0" "$(for u in JERSSON123 MANUEL123 JOSE DANIEL20; do grep -c "cargar_contenido('contenido_principal','horario/view_calendario.php')\"" $T/$u.html; done | paste -sd' ') $(grep -c view_horario_edtudiante $T/DANIEL20.html)"
check "en el celular, 'Aula' en la barra del estudiante y del docente" "1 1" "$(grep -c '<i class="fas fa-chalkboard"></i>Aula</a>' $T/DANIEL20.html) $(grep -c '<i class="fas fa-chalkboard"></i>Aula</a>' $T/JOSE.html)"
check "el estudiante ya no usa los listados del horario viejo" "403" "$(code DANIEL20 $B/controller/horarios/controlador_listar_horarios_estudiante_todo.php)"
check "la entrega de tareas solo se carga para el estudiante" "1 0" "$(curl -s -b $T/DANIEL20.jar $B/view/aula_virtual/view_aula_virtual.php | grep -c entrega_tarea.js) $(curl -s -b $T/JOSE.jar $B/view/aula_virtual/view_aula_virtual.php | grep -c entrega_tarea.js)"
terminar
