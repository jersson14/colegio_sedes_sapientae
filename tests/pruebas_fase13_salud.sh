#!/usr/bin/env bash
# Fase 13 (después del paso 16): Enfermería y Psicología rehechas (resumen, búsqueda, historial).
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
S=$B/controller/salud/controlador_atenciones.php
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('adriana2','yessica2','JOSE')"
login JERSSON123; login adriana2; login yessica2; login JOSE; login DANIEL20
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
MAT=$(q "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by 1 desc limit 1")

echo "== Cada rol ve solo su tipo de atención"
check "enfermera: enfermería; psicóloga: psicología (aunque pida otro tipo)" "ENFERMERIA PSICOLOGIA" "$(post adriana2 -d "tipo=PSICOLOGIA" $S | J '$d["tipo"]') $(post yessica2 -d "tipo=ENFERMERIA" $S | J '$d["tipo"]')"
check "la dirección elige el tipo; docente y estudiante no entran" "PSICOLOGIA 403 403" "$(post JERSSON123 -d "tipo=PSICOLOGIA" $S | J '$d["tipo"]') $(code JOSE $S) $(code DANIEL20 $S)"
check "todas las atenciones de enfermería del rango" "$(q "select count(*) from atencion_salud where tipo_atencion='ENFERMERIA'")" "$(post adriana2 -d "desde=2000-01-01" $S | J 'count($d["atenciones"])')"

echo "== Registrar con el buscador"
check "el buscador encuentra estudiantes del año por apellido o DNI" "1 1" "$(post adriana2 -d "accion=buscar&q=chavez" $S | J '(int)in_array('$MAT',array_column($d["estudiantes"],"matricula"))') $(post adriana2 -d "accion=buscar&q=$(q "select al.alum_dni from alumnos al join matricula m on m.id_alumno=al.Id_alumno where m.id_matricula=$MAT")" $S | J 'count($d["estudiantes"])')"
check "con menos de 2 letras no busca" "0" "$(post adriana2 -d "accion=buscar&q=a" $S | J 'count($d["estudiantes"])')"
post adriana2 -d "estu=$MAT&motivo=Dolor de cabeza&diagno=Cefalea&observa=Descanso&idusu=999" $B/controller/atencion_enfermeria/controlador_registro_enfermeria.php > /dev/null
check "se registra con el controlador de siempre y queda a nombre de quien atiende" "1 adriana2" "$(q "select count(*) from atencion_salud where id_matricula=$MAT and motivo_consulta='DOLOR DE CABEZA'") $(q "select u.usu_usuario from atencion_salud x join usuario u on u.usu_id=x.id_usuario where x.id_matricula=$MAT and x.motivo_consulta='DOLOR DE CABEZA'")"
L=$(post adriana2 $S)
check "aparece hoy en el resumen y en los motivos frecuentes" "1 1" "$(echo "$L" | J '(int)($d["resumen"]["hoy"]>=1)') $(echo "$L" | J '(int)in_array("DOLOR DE CABEZA",array_column($d["motivos"],"motivo"))')"
check "historial del estudiante (solo de su tipo)" "1 $(q "select count(*) from atencion_salud where id_matricula=$MAT and tipo_atencion='PSICOLOGIA'")" "$(post adriana2 -d "accion=historial&matricula=$MAT" $S | J 'count($d["atenciones"])') $(post yessica2 -d "accion=historial&matricula=$MAT" $S | J 'count($d["atenciones"])')"

echo "== Menú y permisos"
check "menú: enfermería y psicología con las pantallas nuevas" "1 1" "$(grep -c "salud/view_enfermeria.php" $T/adriana2.html | sed 's/[1-9][0-9]*/1/') $(grep -c "salud/view_psicologia.php" $T/yessica2.html | sed 's/[1-9][0-9]*/1/')"
check "la enfermera no usa los controladores de psicología ni al revés" "403 403" "$(code adriana2 -d "estu=$MAT&motivo=x&diagno=&observa=&idusu=0" $B/controller/atencion_psicologica/controlador_registro_psicologia.php) $(code yessica2 -d "estu=$MAT&motivo=x&diagno=&observa=&idusu=0" $B/controller/atencion_enfermeria/controlador_registro_enfermeria.php)"
terminar
