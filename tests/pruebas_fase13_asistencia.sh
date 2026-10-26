#!/usr/bin/env bash
# Fase 13 paso 7: una sola pantalla de asistencia (registro con modo Día y observaciones).
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
RA=$B/controller/registro_asistencia
V=$(dirname "$0")/..
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login MANUEL123; login JOSE; login DANIEL20; login CONTA1
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
AULA=18
DIA=$(php -r 'date_default_timezone_set("America/Lima"); echo date("Y-m-d", strtotime("monday this week") > time() ? strtotime("monday last week") : strtotime("monday this week"));')
M1=$(sqlf "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO order by id_matricula limit 1")
M2=$(sqlf "select id_matricula from matricula where id_aula=$AULA and \`id_año\`=$ANIO order by id_matricula limit 1,1")
g() { printf '%s' "$1" > $T/c.json; post ${2:-MANUEL123} --data-urlencode "cambios@$T/c.json" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php; }
obs() { q "select concat(estado,'|',ifnull(observacion,'')) from asistencia where id_matricula=$1 and fecha='$DIA'"; }

echo "== Observaciones"
check "marca con observación (con tildes)" "1 AUSENTE|Cita médica" "$(g "[{\"matricula\":$M1,\"fecha\":\"$DIA\",\"codigo\":\"F\",\"obs\":\"Cita médica\"}]" | jq_ guardados) $(obs $M1)"
g "[{\"matricula\":$M1,\"fecha\":\"$DIA\",\"codigo\":\"J\"}]" > /dev/null
check "cambiar solo la marca conserva la observación" "JUSTIFICADO|Cita médica" "$(obs $M1)"
g "[{\"matricula\":$M1,\"fecha\":\"$DIA\",\"codigo\":\"J\",\"obs\":\"\"}]" > /dev/null
check "observación vacía la borra" "JUSTIFICADO|" "$(obs $M1)"
check "observación de más de 255 caracteres -> 400" "400" "$(printf '[{"matricula":%s,"fecha":"%s","codigo":"P","obs":"%s"}]' $M2 $DIA "$(printf 'x%.0s' $(seq 1 256))" > $T/c.json; code MANUEL123 --data-urlencode "cambios@$T/c.json" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"
check "sin marca, la observación no se guarda" "1 0" "$(g "[{\"matricula\":$M2,\"fecha\":\"$DIA\",\"codigo\":\"\",\"obs\":\"nada\"}]" | jq_ guardados) $(q "select count(*) from asistencia where id_matricula=$M2 and fecha='$DIA'")"
g "[{\"matricula\":$M2,\"fecha\":\"$DIA\",\"codigo\":\"T\",\"obs\":\"<b>llegó 8:20</b>\"}]" > /dev/null
P=$(post MANUEL123 -d "anio=$ANIO&aula=$AULA&mes=${DIA:0:7}" $RA/controlador_planilla.php)
check "la planilla trae las observaciones del mes (texto tal cual)" "<b>llegó 8:20</b>" "$(echo "$P" | php -r '$d=json_decode(stream_get_contents(STDIN),true); $m=$argv[1]; $dia=(int)substr($argv[2],8); foreach($d["estudiantes"] as $e){ if($e["matricula"]==$m) echo $e["obs"][$dia] ?? ""; }' $M2 $DIA)"
check "el docente no guarda en un aula ajena (ni con observación)" "403" "$(printf '[{"matricula":%s,"fecha":"%s","codigo":"P","obs":"x"}]' $M2 $DIA > $T/c.json; code JOSE --data-urlencode "cambios@$T/c.json" -d "anio=$ANIO&aula=$AULA" $RA/controlador_guardar.php)"

echo "== Una sola pantalla"
check "nadie tiene ya 'Asistencia del día' (pantalla antigua)" "0 0 0" "$(echo $(for u in JERSSON123 MANUEL123 JOSE; do grep -c 'view_asistencia.php' $T/$u.html; done))"
check "la dirección y el auxiliar ven 'Registro de asistencia'" "1 1" "$(grep -c '<p>Registro de asistencia</p>' $T/JERSSON123.html) $(grep -c '<p>Registro de asistencia</p>' $T/MANUEL123.html)"
check "la pantalla antigua y sus controladores ya no existen" "404 404 404" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/JERSSON123.jar $B/view/asistencia/view_asistencia.php) $(code JERSSON123 -d x=1 $B/controller/asistencias/controlador_registro_asistencias.php) $(code JERSSON123 -d x=1 $B/controller/asistencias/controlador_eliminar_asistencia.php)"
check "la pantalla tiene los modos Día y Mes" "2" "$(curl -s -b $T/MANUEL123.jar $B/view/asistencia/view_registro_asistencia.php | grep -c 'data-modo=')"
check "el reporte de asistencias usa su propio script" "1 0" "$(curl -s -b $T/MANUEL123.jar $B/view/asistencia/view_asistencia_reportes.php | grep -c 'asistencia_reportes.js') $(curl -s -b $T/MANUEL123.jar $B/view/asistencia/view_asistencia_reportes.php | grep -c 'modal_registro')"
check "los paneles abren el aula directo (Asistencia_Abrir)" "1 2 1" "$(grep -c 'function Asistencia_Abrir' $V/js/panel_comun.js) $(grep -c 'Asistencia_Abrir(' $V/js/panel_rol.js) $(grep -c 'Asistencia_Abrir(' $V/js/panel_admin.js)"
terminar
