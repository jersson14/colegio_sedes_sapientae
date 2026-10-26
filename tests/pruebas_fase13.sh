#!/usr/bin/env bash
# Fase 13, paso 1 del plan: registros sin cruces, auditoría ampliada, correos, duplicados, periodos y errores claros.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
login JERSSON123
ADMIN_ID=$(q "select usu_id from usuario where usu_usuario='JERSSON123'")
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
RA=$B/controller/alumnos/controlador_registrar_alumno.php
alumno() { post JERSSON123 -d "dni=$1&nombre=$2&apepa=QUISPE&apema=ROJAS&sexo=FEMENINO&fechanaci=2015-04-10&telf=&direc=JR+LIMA+123&nombrefoto=&dnipa=&nompa=&celpa=&dnima=40000001&nomma=MARIA+ROJAS&celma=987654321$3" $RA; }

echo "== Registro de estudiante: padres unidos al estudiante correcto (migración 018)"
check "registra" "1" "$(alumno 71000001 ANA)"
check "los padres quedan con el id del estudiante recién creado" "1" "$(q "select count(*) from padres p join alumnos a on a.Id_alumno=p.id_alu where a.alum_dni='71000001' and p.Dni_mama='40000001'")"
check "el procedimiento ya no usa MAX(id)" "0" "$(q "select count(*) from information_schema.routines where routine_schema='colegio2_test' and routine_name in ('SP_REGISTRAR_ALUMNOS','SP_REGISTRAR_ASIGNATURA_DOCENTE') and routine_definition like '%MAX(%'")"
DOC=$(q "select Id_docente from docentes limit 1")
check "asignación de cursos: devuelve el id del registro creado" "$(q "select ifnull(max(Id_asigdocente),0)+1 from asignatura_docente")" "$(q "call SP_REGISTRAR_ASIGNATURA_DOCENTE($ANIO, $DOC)")"

echo "== Posible duplicado (mismo nombre y nacimiento, otro documento)"
check "avisa y no graba" "7|71000001 0" "$(alumno 71000002 ANA) $(q "select count(*) from alumnos where alum_dni='71000002'")"
check "si se confirma que es otra persona, registra" "1 1" "$(alumno 71000002 ANA '&confirmar_duplicado=1') $(q "select count(*) from alumnos where alum_dni='71000002'")"
check "otro nombre: registra sin preguntar" "1" "$(alumno 71000003 LUCIA)"

echo "== Auditoría ampliada"
check "triggers en notas, asistencia, tareas, estudiantes, docentes, periodos y año escolar" "24" "$(sqlf "select count(*) from information_schema.triggers where trigger_schema='colegio2_test' and event_object_table in ('notas','notas_padre','asistencia','detalle_tarea','alumnos','docentes','periodos','año_escolar')")"
check "el estudiante registrado queda en la auditoría con quién lo hizo" "1" "$(q "select count(*) from auditoria where tabla='alumnos' and accion='INSERT' and usuario_id=$ADMIN_ID and despues like '%71000001%'")"
MAT=$(q "select id_matricula from matricula limit 1")
post JERSSON123 --data-urlencode "cambios=[{\"matricula\":$MAT,\"fecha\":\"2026-03-10\",\"codigo\":\"F\"}]" -d "anio=$ANIO&aula=$(q "select id_aula from matricula where id_matricula=$MAT")" $B/controller/registro_asistencia/controlador_guardar.php > /dev/null
check "una falta marcada queda en la auditoría" "1" "$(q "select count(*) from auditoria where tabla='asistencia' and usuario_id=$ADMIN_ID and despues like '%AUSENTE%'")"
NOTA=$(q "select id_nota_bole from notas limit 1")
q "update notas set nota='18' where id_nota_bole=$NOTA" > /dev/null
check "un cambio de nota queda con el valor anterior y el nuevo" "1" "$(q "select count(*) from auditoria where tabla='notas' and accion='UPDATE' and registro_id='$NOTA' and despues like '%\"nota\": \"18\"%'")"
check "la pantalla de auditoría ofrece las tablas nuevas" "1" "$(post JERSSON123 -d 'tabla=notas&desde=2026-01-01&hasta=2026-12-31' $B/controller/configuracion/controlador_auditoria_listar.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo isset($d["tablas"]["asistencia"]) ? 1 : 0;')"

echo "== Correos"
EMP="id=1&nom=SEDES+SAPIENTIAE&cod=3434&tel=946701820&dir=JR+MAYTA+CAPAC"
check "correo inválido: rechazado en el servidor" "400" "$(code JERSSON123 -d "$EMP&email=no-es-correo" $B/controller/empresa/controlador_modificar_empresa.php)"
check "correo con + y dominio largo: aceptado" "1" "$(post JERSSON123 -d "$EMP&email=direccion%2Bpadres@colegio.education" $B/controller/empresa/controlador_modificar_empresa.php)"

echo "== Periodos"
# los datos van por archivo: Windows estropea la ñ de "año_escolar" en los argumentos de curl
pjson() { printf '[{"año_escolar":"%s","tipo_periodo":"BIMESTRE","periodo":"%s","fecha_inicio":"%s","fecha_fin":"%s"}]' "$ANIO" "$1" "$2" "$3" > $T/per.json; }
per() { pjson "$@"; post JERSSON123 --data-urlencode "periodos@$T/per.json" $B/controller/periodos/controlador_registro_periodos.php; }
pcode() { pjson "$@"; code JERSSON123 --data-urlencode "periodos@$T/per.json" $B/controller/periodos/controlador_registro_periodos.php; }
check "fuera del año escolar: rechazado" "400" "$(pcode 'V BIMESTRE' 2026-12-14 2027-01-20)"
check "inicio después del fin: rechazado" "400" "$(pcode 'V BIMESTRE' 2026-06-10 2026-06-01)"
check "se cruza con el II bimestre: rechazado" "400" "$(pcode 'V BIMESTRE' 2026-07-20 2026-08-01)"
check "(el mensaje dice con cuál se cruza)" "1" "$(pcode 'V BIMESTRE' 2026-07-20 2026-08-01 > /dev/null; grep -c 'II BIMESTRE' $T/out)"
check "un periodo correcto sí se guarda" "1 5" "$(per 'SEMANA DE RECUPERACION' 2026-08-01 2026-08-02 | head -c 1) $(sqlf "select count(*) from periodos where \`id_año_escolar\`=$ANIO")"

echo "== Errores claros en lugar de errores de PHP"
AULA=$(q "select id_aula from matricula limit 1")
check "eliminar un aula con matrículas: 409 con el motivo" "409" "$(code JERSSON123 -d "id=$AULA" $B/controller/aulas/controlador_eliminar_rol.php)"
check "(el mensaje explica qué hacer)" "1" "$(grep -c 'relacionada' $T/out)"
check "(el aula sigue)" "1" "$(q "select count(*) from aulas where Id_aula=$AULA")"

terminar
