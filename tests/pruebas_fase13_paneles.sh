#!/usr/bin/env bash
# Fase 13, paso 4 del plan: paneles de inicio por rol. Cada uno ve lo suyo (docente: sus cursos y aulas;
# estudiante: su matrícula, sus tareas y sus pensiones). Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
PR=$B/controller/panel/controlador_panel_rol.php
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
ENF=$(q "select u.usu_usuario from usuario u join roles r on r.Id_rol=u.rol_id where r.tipo_rol='ENFERMERA' limit 1")
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','jorge','$ENF')"
login JERSSON123; login JOSE; login jorge; login DANIEL20; login MANUEL123; login CONTA1; login $ENF
DIA=$(php -r 'date_default_timezone_set("America/Lima"); echo [1=>"LUNES","MARTES","MIERCOLES","JUEVES","VIERNES"][(int)date("N")] ?? "";')
JOSE_ID=$(q "select usu_id from usuario where usu_usuario='JOSE'")
DAN_MAT=$(q "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by m.id_matricula desc limit 1")

echo "== Docente"
J=$(post JOSE -X POST $PR)
check "su panel: rol, año escolar y periodos" "DOCENTE 2026 4" "$(echo "$J" | jq_ rol) $(echo "$J" | jq_ anio.anio) $(echo "$J" | jq_ periodos)"
ESP_CLASES=$([ -n "$DIA" ] && sqlf "select count(*) from horarios h join horas_aula ha on ha.id_hora=h.id_hora_aula join detalle_asignatura_docente dd on dd.Id_detalle_asig_docente=h.id_detalle_asig_docente join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente where d.id_asusuario=$JOSE_ID and h.dia='$DIA' and h.estado='ACTIVO' and ad.\`id_año\`=5" || echo 0)
check "clases de hoy: solo las suyas según el horario" "$ESP_CLASES" "$(echo "$J" | jq_ clases)"
check "aulas donde enseña (las mismas que su planilla de asistencia)" "$(post JOSE -d 'anio=5' $B/controller/registro_asistencia/controlador_planilla.php | jq_ aulas)" "$(echo "$J" | jq_ aulas)"
JORGE_ID=$(q "select usu_id from usuario where usu_usuario='jorge'")
ESP_JORGE=$([ -n "$DIA" ] && sqlf "select count(*) from horarios h join horas_aula ha on ha.id_hora=h.id_hora_aula join detalle_asignatura_docente dd on dd.Id_detalle_asig_docente=h.id_detalle_asig_docente join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join docentes d on d.Id_docente=ad.Id_docente where d.id_asusuario=$JORGE_ID and h.dia='$DIA' and h.estado='ACTIVO' and ad.\`id_año\`=5" || echo 0)
check "otro docente ve sus propias clases" "$ESP_JORGE" "$(post jorge -X POST $PR | jq_ clases)"
check "una entrega por calificar aparece en su panel" "1" "$(q "update detalle_tarea d join tareas t on t.id_tarea=d.id_tarea set d.estado='ENVIADO', d.fecha_envio=now() where t.id_detalle_asignatura=20 limit 1"; post JOSE -X POST $PR | jq_ por_calificar)"
TEMA20=$(q "select tema from tareas where id_detalle_asignatura=20 limit 1")
check "y no en el de otro docente" "0" "$(post jorge -X POST $PR | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo count(array_filter($d["por_calificar"], function($t){ return $t["tema"]===$GLOBALS["argv"][1]; }));' "$TEMA20")"

echo "== Estudiante"
J=$(post DANIEL20 -X POST $PR)
check "su aula" "SECUNDARIA · QUINTO GRADO" "$(echo "$J" | jq_ matricula.aula)"
check "sus tareas pendientes (las de su matrícula)" "$(q "select count(*) from detalle_tarea x join tareas t on t.id_tarea=x.id_tarea where x.id_matriculado=$DAN_MAT and x.estado='PENDIENTE' and t.estado='PENDIENTE'")" "$(echo "$J" | jq_ tareas)"
check "sus pensiones con saldo (máximo 3)" "$(q "select least(count(*),3) from vw_estado_pensiones where id_matricula=$DAN_MAT and saldo_pension+saldo_mora>0")" "$(echo "$J" | jq_ pensiones)"
check "comunicados: generales y los de su aula" "0" "$(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo count(array_filter($d["comunicados"], function($c){ return $c["tipo"]!=="GENERAL" && stripos($c["grado"],"QUINTO")===false; }));')"
check "no recibe datos de docentes ni de caja" "0 0" "$(echo "$J" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo isset($d["por_calificar"])?1:0, " ", isset($d["caja"])?1:0;')"

echo "== Auxiliar, salud y contadora"
J=$(post MANUEL123 -X POST $PR)
check "auxiliar: todas las aulas con estudiantes" "$(sqlf "select count(distinct id_aula) from matricula where \`id_año\`=5")" "$(echo "$J" | jq_ aulas)"
J=$(post $ENF -X POST $PR)
check "enfermería: solo sus atenciones" "ENFERMERIA $(q "select least(count(*),6) from atencion_salud where tipo_atencion='ENFERMERIA'")" "$(echo "$J" | jq_ tipo) $(echo "$J" | jq_ recientes)"
check "contadora: encabezado y año escolar (su panel contable es aparte)" "CONTADOR 2026" "$(post CONTA1 -X POST $PR | jq_ rol) $(post CONTA1 -X POST $PR | jq_ anio.anio)"
check "el administrador usa su propio panel" "400" "$(code JERSSON123 -X POST $PR)"

echo "== Pantalla"
check "el docente ve su panel (no el antiguo de tablas)" "1 0" "$(grep -c 'id=\"pr_clases\"' $T/JOSE.html) $(grep -c 'id=\"tabla_tarea_menu\"' $T/JOSE.html)"
check "el estudiante ve su panel con sus pensiones" "1 0" "$(grep -c 'id=\"pr_pensiones\"' $T/DANIEL20.html) $(grep -c 'id=\"tabla_tarea_menu_estudiante\"' $T/DANIEL20.html)"
check "la contadora conserva su panel contable bajo el nuevo encabezado" "1 1" "$(grep -c 'id=\"pi_linea\"' $T/CONTA1.html) $(grep -c 'pc_alumnos_morosos' $T/CONTA1.html | awk '{print ($1>0)?1:0}')"
terminar
