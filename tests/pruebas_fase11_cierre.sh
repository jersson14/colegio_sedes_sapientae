#!/usr/bin/env bash
# Fase 11 (cierre): año escolar actual, edad, notas y exámenes por docente, activar/desactivar y reenvío de tareas.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','jorge')"
login JERSSON123; login JOSE; login jorge; login DANIEL20
JOSE_ID=$(q "select usu_id from usuario where usu_usuario='JOSE'")
DANIEL_ALU=$(q "select m.id_alumno from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' limit 1")
entra() { local jar=$T/x_$1.jar; curl -s -c $jar -b $jar -o /dev/null $B/index.php; curl -s -c $jar -b $jar -d "u=$1&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php | head -c 300; }

echo "== Año escolar actual (migraciones 015 y 016)"
check "el año escolar en curso se llama 2026 y es el actual" "2026 2026" "$(sqlf "select \`año_escolar\` from \`año_escolar\` where estado='ACTIVO'") $(q 'select FN_ANIO_ESCOLAR_ACTUAL()')"
check "IV bimestre dentro del año escolar" "2026-10-19 2026-12-11" "$(q "select concat(fecha_inicio,' ',fecha_fin) from periodos where periodos='IV BIMESTRE' and fecha_inicio like '2026%'")"
check "ningún procedimiento usa el año del calendario como año escolar" "0" "$(q "select count(*) from information_schema.routines where routine_schema='colegio2_test' and routine_definition regexp 'YEAR[[:space:]]*\\\\([[:space:]]*NOW'")"
check "alumnos del aula para la asistencia diaria (antes vacío)" "7" "$(q 'call SP_LISTAR_ALUMNOS_GRADO_ANIO(18)' | wc -l)"
check "edad calculada a la fecha" "0" "$(q "select count(*) from alumnos where Edad <> TIMESTAMPDIFF(YEAR, alum_fechanacimiento, CURDATE())")"
check "el listado de estudiantes trae la edad calculada" "$(q "select TIMESTAMPDIFF(YEAR, alum_fechanacimiento, CURDATE()) from alumnos where Id_alumno=$DANIEL_ALU")" "$(post JERSSON123 -X POST $B/controller/alumnos/controlador_listar_alumnos.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["data"] as $a){ if($a["Id_alumno"]=='$DANIEL_ALU'){ echo $a["Edad"]; } }')"

echo "== Notas: cada docente en sus cursos"
MAT_JOSE=$(sqlf "select m.id_matricula from matricula m where m.id_aula=5 and m.\`id_año\`=5 limit 1")
MAT_OTRA=$(q "select id_matricula from matricula where id_aula=18 limit 1")
nota() { code JOSE --data-urlencode "registros=[{\"id_matri\":$1,\"perio\":40,\"cri\":$2,\"nota\":\"15\",\"conclu\":\"\"}]" $B/controller/notas/controlador_registro_notas.php; }
check "NO registra notas con un criterio de otro docente" "403" "$(nota $MAT_JOSE 42)"
check "NO registra notas a un estudiante de otra aula" "403" "$(nota $MAT_OTRA 1)"
check "sí registra en su curso y su aula" "200" "$(nota $MAT_JOSE 1)"

echo "== Exámenes: cada docente en sus cursos"
EX=$B/controller/examenes
check "NO crea examen en un curso ajeno" "403" "$(code jorge -d 'asig=20&tema=X&fecha=2030-01-01&descrip=X' $EX/controlador_registro_examenes.php)"
check "NO modifica, elimina ni cierra un examen ajeno" "403 403 403" "$(code JOSE -d 'id=E0000005&asig=20&tema=X&fecha=2030-01-01&descrip=X' $EX/controlador_modificar_examen.php) $(code JOSE -d 'id=E0000005' $EX/controlador_eliminar_examenes.php) $(code JOSE -d 'id=E0000005&estatus=FINALIZADO' $EX/controlador_modificar_estado_examen.php)"
check "NO ve los alumnos de un curso ajeno; sí los suyos" "403 200" "$(code jorge -d 'id=20' $EX/controlador_listar_alumnos_examen.php) $(code jorge -d 'id=44' $EX/controlador_listar_alumnos_examen.php)"
check "el examen ajeno sigue igual" "1" "$(q "select count(*) from examen where id_examen='E0000005' and tema_examen<>'X'")"

echo "== Activar / desactivar"
check "el listado de estudiantes indica el acceso al sistema" "ACTIVO" "$(post JERSSON123 -X POST $B/controller/alumnos/controlador_listar_alumnos.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["data"] as $a){ if($a["Id_alumno"]=='$DANIEL_ALU'){ echo $a["acceso"]; } }')"
check "desactivar el acceso del estudiante" "true" "$(post JERSSON123 -d "id=$DANIEL_ALU&estado=INACTIVO" $B/controller/alumnos/controlador_cambiar_acceso.php | jq_ ok)"
check "(ya no puede ingresar)" "1" "$(entra DANIEL20 | grep -ci inactiv)"
post JERSSON123 -d "id=$DANIEL_ALU&estado=ACTIVO" $B/controller/alumnos/controlador_cambiar_acceso.php > /dev/null
check "activar de nuevo: vuelve a ingresar" "0" "$(entra DANIEL20 | grep -ci inactiv)"
check "desactivar al docente: él y su usuario quedan inactivos" "true INACTIVO INACTIVO" "$(post JERSSON123 -d "id=$JOSE_ID&estado=INACTIVO" $B/controller/docentes/controlador_cambiar_estado.php | jq_ ok) $(q "select concat(d.docente_estatus,' ',u.usu_estatus) from docentes d join usuario u on u.usu_id=d.id_asusuario where u.usu_id=$JOSE_ID")"
check "(el docente inactivo no ingresa)" "1" "$(entra JOSE | grep -ci inactiv)"
post JERSSON123 -d "id=$JOSE_ID&estado=ACTIVO" $B/controller/docentes/controlador_cambiar_estado.php > /dev/null
check "activar de nuevo al docente" "ACTIVO ACTIVO" "$(q "select concat(d.docente_estatus,' ',u.usu_estatus) from docentes d join usuario u on u.usu_id=d.id_asusuario where u.usu_id=$JOSE_ID")"
check "solo el administrador activa o desactiva" "403 403" "$(code JOSE -d "id=$DANIEL_ALU&estado=INACTIVO" $B/controller/alumnos/controlador_cambiar_acceso.php) $(code jorge -d "id=$JOSE_ID&estado=INACTIVO" $B/controller/docentes/controlador_cambiar_estado.php)"
check "estado inválido: rechazado" "400" "$(code JERSSON123 -d "id=$JOSE_ID&estado=BORRADO" $B/controller/docentes/controlador_cambiar_estado.php)"

echo "== Reenvío de tareas (hasta que el docente califique o cierre)"
login DANIEL20
echo "mi tarea" > $T/mi_tarea.pdf
check "NO reenvía una tarea ya calificada" "400" "$(code DANIEL20 -F iddetalle=46 -F "archivos[]=@$T/mi_tarea.pdf" $B/controller/tareas/controlador_registro_tareas_estudiante.php)"
check "(sigue calificada)" "CALIFICADO" "$(q 'select estado from detalle_tarea where id_detalle_tarea=46')"
check "sí envía una tarea pendiente" "ENVIADO" "$(code DANIEL20 -F iddetalle=57 -F "archivos[]=@$T/mi_tarea.pdf" $B/controller/tareas/controlador_registro_tareas_estudiante.php > /dev/null; q 'select estado from detalle_tarea where id_detalle_tarea=57')"
check "y la puede reenviar mientras no esté calificada (el mismo controlador entrega y reenvía)" "200" "$(code DANIEL20 -F iddetalle=57 -F "archivos[]=@$T/mi_tarea.pdf" $B/controller/tareas/controlador_registro_tareas_estudiante.php)"
check "el docente la ve marcada fuera de plazo (entrega vencida)" "1" "$(post jorge -d id=T0000004 $B/controller/tareas/controlador_listar_tabla_envio_tareas.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["data"] as $e){ if($e["id_detalle_tarea"]==57){ echo $e["fuera_plazo"]; } }')"
q "update tareas set estado='FINALIZADO' where id_tarea='T0000006'" > /dev/null
check "NO envía a una tarea que el docente cerró" "400" "$(code DANIEL20 -F iddetalle=68 -F "archivos[]=@$T/mi_tarea.pdf" $B/controller/tareas/controlador_registro_tareas_estudiante.php)"

# carpetas de envío creadas por la prueba
for d in $(q "select substring_index(archivo_evnio_tarea,'/',-1) from detalle_tarea where id_detalle_tarea=57"); do rm -rf "$R/controller/tareas/controller/tareas/documentos/$d"; done
terminar
