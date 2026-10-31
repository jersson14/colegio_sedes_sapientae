#!/usr/bin/env bash
# Fase 13 paso 14 (c): avisos a las familias (WhatsApp con mensaje escrito y registro de lo avisado).
# Requiere la BD de prueba recién reiniciada (migraciones 020 y 021).
source "$(dirname "$0")/lib.sh"
A=$B/controller/avisos/controlador_avisos.php
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login MANUEL123; login CONTA1; login JOSE; login DANIEL20
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
DANI=$(sqlf "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20'")
HOY=$(date +%Y-%m-%d)
$M -e "insert into asistencia(id_matricula,mes,fecha,estado,observacion,created_at) values ($DANI, month(curdate()), curdate(), 'AUSENTE', '', now());
       update tareas set fecha_entrega = now() - interval 2 day where id_tarea='T0000003';
       update tareas set fecha_entrega = now() - interval 30 day where id_tarea='T0000005';
       insert into comunicados(tipo,id_aula,titulo,descripcion,imagen,estado,id_usuario,created_at) values ('GENERAL',NULL,'SIMULACRO','El viernes','','ACTIVO',(select usu_id from usuario where usu_usuario='JERSSON123'),curdate());"
COM=$(q "select id_comunicado from comunicados where titulo='SIMULACRO'")
lista() { post ${1:-JERSSON123} -d "accion=listar&fecha=$HOY${2:+&$2}" $A; }
cuenta() { php -r '$d=json_decode(stream_get_contents(STDIN),true); echo count(array_filter($d["avisos"],function($a)use($argv){return $a["tipo"]===$argv[1] && ($argv[2]===""||$a["referencia"]==$argv[2]) && ($argv[3]===""||$a["matricula"]==$argv[3]);}));' "$1" "${2:-}" "${3:-}"; }
marcar() { post ${5:-JERSSON123} -d "accion=marcar&tipo=$1&referencia=$2&matricula=$3&destino=$4" $A; }

echo "== Qué avisos ve cada rol"
check "dirección: los cuatro tipos" "FALTA,TAREA,PENSION,COMUNICADO" "$(lista | php -r 'echo implode(",",json_decode(stream_get_contents(STDIN),true)["tipos"]);')"
check "auxiliar: faltas, tareas y comunicados; contadora: pensiones" "FALTA,TAREA,COMUNICADO PENSION" "$(lista MANUEL123 | php -r 'echo implode(",",json_decode(stream_get_contents(STDIN),true)["tipos"]);') $(lista CONTA1 | php -r 'echo implode(",",json_decode(stream_get_contents(STDIN),true)["tipos"]);')"
check "el docente no entra" "403" "$(code JOSE -d accion=listar $A)"

echo "== Motivos"
L=$(lista)
check "la falta de hoy de Daniel, con el celular de la madre" "1 1" "$(echo "$L" | cuenta FALTA "$HOY" $DANI) $(echo "$L" | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["avisos"] as $a) if($a["tipo"]=="FALTA") echo (int)!empty($a["cel_madre"]);')"
check "tareas vencidas hace 2 días sí; hace 30 días ya no" "1 0" "$(echo "$L" | cuenta TAREA T0000003 | sed 's/[1-9][0-9]*/1/') $(echo "$L" | cuenta TAREA T0000005)"
check "pensiones: un solo aviso por familia (no uno por mes)" "$(sqlf "select count(distinct v.id_matricula) from vw_estado_pensiones v join matricula m on m.id_matricula=v.id_matricula where m.\`id_año\`=$ANIO and v.saldo_pension+v.saldo_mora>0 and v.exonerada=0 and (v.vencida=1 or v.fecha_vencimiento<=curdate()+interval 3 day)")" "$(echo "$L" | cuenta PENSION)"
check "comunicado reciente: las familias que no lo leyeron" "1" "$(echo "$L" | cuenta COMUNICADO $COM $DANI)"
post DANIEL20 -d id=$COM $B/controller/comunicados/controlador_ver_comunicado.php > /dev/null
check "cuando la familia lo lee, ya no hay que avisarle" "0" "$(lista | cuenta COMUNICADO $COM $DANI)"

echo "== Registrar lo avisado"
check "marcar la falta: queda registrada una vez" "true false 1" "$(marcar FALTA $HOY $DANI 985747444 | jq_ nuevo) $(marcar FALTA $HOY $DANI 985747444 | jq_ nuevo) $(q "select count(*) from avisos_enviados where tipo='FALTA' and id_matricula=$DANI")"
check "ya no aparece como pendiente; con 'ya avisados' sí, con quién lo envió" "0 JERSSON123" "$(lista | cuenta FALTA "$HOY" $DANI) $(lista JERSSON123 con_enviados=1 | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["avisos"] as $a) if($a["tipo"]=="FALTA") echo $a["enviado_por"];')"
check "el auxiliar no registra avisos de pensiones; la contadora no de faltas" "403 403" "$(code MANUEL123 -d "accion=marcar&tipo=PENSION&referencia=x&matricula=$DANI" $A) $(code CONTA1 -d "accion=marcar&tipo=FALTA&referencia=x&matricula=$DANI" $A)"

echo "== Configuración"
check "solo la dirección configura; más de 30 días -> 400" "403 400" "$(code CONTA1 -d 'accion=configurar&falta=1' $A) $(code JERSSON123 -d 'accion=configurar&falta=1&dias_pension=40' $A)"
post JERSSON123 -d "accion=configurar&tarea=1&pension=1&comunicado=1&dias_pension=5" $A > /dev/null
check "apagar las faltas: el auxiliar ya no las ve ni las puede registrar" "TAREA,COMUNICADO 403" "$(lista MANUEL123 | php -r 'echo implode(",",json_decode(stream_get_contents(STDIN),true)["tipos"]);') $(code MANUEL123 -d "accion=marcar&tipo=FALTA&referencia=x&matricula=$DANI" $A)"
check "los días de anticipación se guardan" "5" "$(q "select valor from avisos_config where clave='dias_pension'")"
check "menú: dirección, auxiliar y contadora tienen Avisos a las familias" "1 1 1 0" "$(grep -c 'avisos/view_avisos.php' $T/JERSSON123.html) $(grep -c 'avisos/view_avisos.php' $T/MANUEL123.html) $(grep -c 'avisos/view_avisos.php' $T/CONTA1.html) $(grep -c 'avisos/view_avisos.php' $T/DANIEL20.html)"
terminar
