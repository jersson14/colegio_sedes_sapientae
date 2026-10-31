#!/usr/bin/env bash
# Fase 13 paso 13: cambio de año escolar asistido. Regla: solo se propone promover a quien APROBÓ TODOS sus cursos.
# Requiere la BD de prueba recién reiniciada (crea el año 2027 y lo activa al final).
source "$(dirname "$0")/lib.sh"
CA=$B/controller/cambio_anio
crear_contador
login JERSSON123; login CONTA1
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
sqlf "insert into \`año_escolar\`(\`año_escolar\`, Nombre_año, fecha_inicio, fecha_fin, descripcion, estado, created_at) values (2027, 'AÑO 2027', '2027-03-08', '2027-12-17', '', 'INACTIVO', now())" > /dev/null
NUEVO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where \`año_escolar\`=2027")
mat() { sqlf "select m.id_matricula from matricula m join alumnos a on a.Id_alumno=m.id_alumno where a.alum_dni='$1' and m.\`id_año\`=$ANIO"; }
YES=$(mat 66655656); JOSE=$(mat 52220200); ALEX=$(mat 55516555); SANDRA=$(mat 45511551); JUAN=$(mat 32231232)
IV=$(sqlf "select id_periodo from periodos where \`id_año_escolar\`=$ANIO and periodos='IV BIMESTRE'")
# José Luis aprueba su curso con 15 (Yessenia ya tiene Comunicación Integral desaprobada con 3)
sqlf "insert into notas(id_matricula,id_bimestre,id_criterio,nota,conclusiones,creared_at) values ($JOSE,$IV,1,'15','',curdate())" > /dev/null
prop() { post JERSSON123 -d "origen=$ANIO&destino=$NUEVO" $CA/controlador_propuesta.php; }
est() { php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["aulas"] as $g) foreach($g["estudiantes"] as $e) if($e["matricula"]==$argv[1]) echo $e["resultado"]," ",$e["sugerida"]," ",implode(",",$e["desaprobados"]);' "$1"; }
P=$(prop)

echo "== Regla de promoción: solo si aprobó todo"
check "aprobó todos sus cursos -> se propone promover" "APROBO_TODO PROMOVER " "$(echo "$P" | est $JOSE)"
check "con un curso desaprobado -> NO se promueve: decide la dirección" "DESAPROBADOS LUEGO COMUNICACIÓN INTEGRAL" "$(echo "$P" | est $YES)"
check "sin notas -> tampoco se promueve solo" "SIN_NOTAS LUEGO " "$(echo "$P" | est $ALEX)"
check "aprobó todo en el último grado de secundaria -> egresa" "APROBO_TODO EGRESA " "$(echo "$P" | est $JUAN)"

echo "== Grado siguiente"
sig() { echo "$P" | php -r '$d=json_decode(stream_get_contents(STDIN),true); $n=[]; foreach($d["todas_las_aulas"] as $a) $n[$a["id"]]=$a["nivel"]." ".$a["grado"]; foreach($d["aulas"] as $g) if($g["aula"]["id"]==$argv[1]) echo $g["siguiente"]," ",($g["aula_siguiente"]?$n[$g["aula_siguiente"]]:"-");' "$1"; }
check "Inicial 3 años -> Inicial 4 años" "AULA INICIAL 4 AÑOS" "$(sig 5)"
check "Primer grado A -> Segundo grado (no hay sección A en segundo)" "AULA PRIMARIA SEGUNDO GRADO" "$(sig 20)"
check "Quinto de secundaria -> egresa (no pasa al nivel comodín TODOS)" "EGRESA -" "$(sig 18)"
check "5 años pasa a primer grado de primaria (cambio de nivel)" "8" "$(cd "$(dirname "$0")/.." && php -r 'require "model/model_cambio_anio.php"; $m=new Modelo_Cambio_Anio(); $r=new ReflectionMethod($m,"aulas"); $r->setAccessible(true); $p=new ReflectionProperty($m,"c"); $p->setAccessible(true); $p->setValue($m,$m->conexionPDO()); $a=$r->invoke($m); foreach($a as $x) if($x["id"]==7) $c=$x; echo Modelo_Cambio_Anio::Aula_Siguiente($c,$a)["aula"]["id"];')"

echo "== Hacer el cambio de año"
DEC="[{\"matricula\":$JOSE,\"accion\":\"PROMOVER\",\"aula\":6},{\"matricula\":$YES,\"accion\":\"LUEGO\"},{\"matricula\":$ALEX,\"accion\":\"REPETIR\"},{\"matricula\":$SANDRA,\"accion\":\"NO_CONTINUA\"},{\"matricula\":$JUAN,\"accion\":\"EGRESA\"}]"
ej() { printf '%s' "$DEC" > $T/d.json; post JERSSON123 --data-urlencode "decisiones@$T/d.json" -d "origen=$ANIO&destino=$NUEVO&copiar_periodos=1&copiar_pensiones=1&copiar_cursos=1&copiar_horarios=1&activar=$1" $CA/controlador_ejecutar.php; }
R=$(ej "")
campo() { echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo implode(" ",array_map(function($k)use($d){return is_array($d[$k])?count($d[$k]):$d[$k];},array_slice($argv,1)));' "$@"; }
check "2 matrículas: José al grado siguiente y Alexander repite; 1 para decidir luego; 2 egresan o no continúan" "2 1 1 1 2" "$(campo matriculas promovidos repiten pendientes egresan)"
check "José en 4 años y Alexander otra vez en 3 años (año nuevo)" "6 5" "$(sqlf "select id_aula from matricula m join alumnos a on a.Id_alumno=m.id_alumno where a.alum_dni='52220200' and m.\`id_año\`=$NUEVO") $(sqlf "select id_aula from matricula m join alumnos a on a.Id_alumno=m.id_alumno where a.alum_dni='55516555' and m.\`id_año\`=$NUEVO")"
check "Yessenia (desaprobó) NO quedó matriculada en el año nuevo" "0" "$(sqlf "select count(*) from matricula m join alumnos a on a.Id_alumno=m.id_alumno where a.alum_dni='66655656' and m.\`id_año\`=$NUEVO")"
check "la matrícula nueva no cobra nada (se cobra después en Cobrar pensión)" "0.00 3" "$(sqlf "select concat(pago_matricula,' ',(select count(*) from pago_pensiones p where p.id_matri=m.id_matricula and p.sub_total=0)) from matricula m join alumnos a on a.Id_alumno=m.id_alumno where a.alum_dni='52220200' and m.\`id_año\`=$NUEVO")"
check "periodos copiados con fechas del año nuevo" "4 2027" "$(campo periodos) $(sqlf "select min(year(fecha_inicio)) from periodos where \`id_año_escolar\`=$NUEVO")"
check "pensiones copiadas (mismas que el año que termina)" "$(q "select count(*) from pensiones where id_anio=$ANIO")" "$(campo pensiones)"
check "cursos de docentes, criterios, horas y horarios copiados" "1 1 1 1" "$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo (int)($d["cursos"]>0)," ",(int)($d["criterios"]>0)," ",(int)($d["horas"]>0)," ",(int)($d["horarios"]>0);')"
check "sin activar: el año activo no cambia" "$ANIO" "$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO'")"

echo "== Repetirlo no duplica nada y luego activar"
R=$(ej 1)
check "segunda vez: 0 matrículas nuevas (2 ya estaban) y 0 copias" "0 2 0 0 0" "$(campo matriculas omitidos periodos pensiones cursos)"
check "activar: el año nuevo pasa a ser el activo y los periodos del anterior se cierran" "$NUEVO 4" "$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO'") $(sqlf "select count(*) from periodos where \`id_año_escolar\`=$ANIO and estado='FINALIZADO'")"

echo "== Validaciones y permisos"
check "decisión inventada -> 400; mismo año -> 400" "400 400" "$(printf '[{"matricula":1,"accion":"APROBAR"}]' > $T/x.json; code JERSSON123 --data-urlencode "decisiones@$T/x.json" -d "origen=$ANIO&destino=$NUEVO" $CA/controlador_ejecutar.php) $(code JERSSON123 -d "origen=$ANIO&destino=$ANIO&decisiones=[]" $CA/controlador_ejecutar.php)"
check "la contadora no puede hacer el cambio de año" "403 403" "$(code CONTA1 -d "origen=$ANIO" $CA/controlador_propuesta.php) $(code CONTA1 -d "origen=$ANIO&destino=$NUEVO&decisiones=[]" $CA/controlador_ejecutar.php)"
terminar
