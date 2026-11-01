#!/usr/bin/env bash
# Fase 13 (después del paso 16): Cobrar pensión (caja) del contador: buscar, estado de cuenta, cobrar y anular.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
C=$B/controller/pago_pension/controlador_caja.php
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario='JOSE'"
login CONTA1; login JOSE; login DANIEL20
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
MAT=$(q "select m.id_matricula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by 1 desc limit 1")

echo "== Buscar y estado de cuenta"
check "busca por apellido y trae la deuda" "1 1" "$(post CONTA1 -d "accion=buscar&anio=$ANIO&q=chavez" $C | J '(int)in_array('$MAT',array_column($d["estudiantes"],"matricula"))') $(post CONTA1 -d "accion=buscar&anio=$ANIO&q=chavez" $C | J '(int)isset($d["estudiantes"][0]["vencido"],$d["estudiantes"][0]["pendiente"])')"
E=$(post CONTA1 -d "accion=estado&matricula=$MAT" $C)
check "estado: pensiones del año, otros conceptos con su saldo y recibos" "$(q "select count(*) from vw_estado_pensiones where id_matricula=$MAT") 3 1" "$(echo "$E" | J 'count($d["pensiones"])') $(echo "$E" | J 'count($d["otros"])') $(echo "$E" | J '(int)isset($d["otros"][0]["saldo"])')"
check "solo el contador (y la dirección); docente y estudiante no" "403 403" "$(code JOSE -d accion=anios $C) $(code DANIEL20 -d accion=anios $C)"

echo "== Cobrar dos meses (uno con la mora exonerada) repartido en dos pagos"
P1=$(echo "$E" | J '$d["pensiones"][0]["id"]'); P2=$(echo "$E" | J '$d["pensiones"][1]["id"]')
S1=$(echo "$E" | J 'number_format($d["pensiones"][0]["saldo_pension"]+$d["pensiones"][0]["saldo_mora"],2,".","")')
MORA2=$(echo "$E" | J '$d["pensiones"][1]["saldo_mora"]'); S2=$(echo "$E" | J 'number_format($d["pensiones"][1]["saldo_pension"],2,".","")')
TOTAL=$(php -r "echo number_format($S1+$S2,2,'.','');"); MITAD=$(php -r "echo number_format(floor(($S1+$S2)/2),2,'.','');"); RESTO=$(php -r "echo number_format($S1+$S2-floor(($S1+$S2)/2),2,'.','');")
LIN="[{\"concepto\":\"PENSION\",\"id_pension\":$P1,\"monto\":\"$S1\",\"exonerar\":0,\"motivo\":\"\"},{\"concepto\":\"PENSION\",\"id_pension\":$P2,\"monto\":\"$S2\",\"exonerar\":1,\"motivo\":\"Hermanos en el colegio\"}]"
MET="[{\"id_metodo\":1,\"id_cuenta\":1,\"monto\":\"$MITAD\",\"referencia\":\"\"},{\"id_metodo\":1,\"id_cuenta\":1,\"monto\":\"$RESTO\",\"referencia\":\"\"}]"
check "formas de pago que no suman el total -> no registra" "false" "$(post CONTA1 -d "id_matri=$MAT" --data-urlencode "lineas=$LIN" --data-urlencode "metodos=[{\"id_metodo\":1,\"id_cuenta\":1,\"monto\":\"1.00\",\"referencia\":\"\"}]" $B/controller/pago_pension/controlador_detalle_pago_pension.php | jq_ ok)"
R=$(post CONTA1 -d "id_matri=$MAT" --data-urlencode "lineas=$LIN" --data-urlencode "metodos=$MET" $B/controller/pago_pension/controlador_detalle_pago_pension.php)
check "se registra con un recibo por el total" "true $TOTAL" "$(echo "$R" | jq_ ok) $(echo "$R" | J 'number_format($d["total"],2,".","")')"
E2=$(post CONTA1 -d "accion=estado&matricula=$MAT" $C)
check "los dos meses quedan pagados (la mora exonerada no se cobra)" "0.00 0.00" "$(echo "$E2" | J 'number_format($d["pensiones"][0]["saldo_pension"]+$d["pensiones"][0]["saldo_mora"],2,".","")') $(echo "$E2" | J 'number_format($d["pensiones"][1]["saldo_pension"]+$d["pensiones"][1]["saldo_mora"],2,".","")')"
check "el recibo aparece primero: dos pensiones y sus moras (una exonerada), y sus dos pagos" "$(echo "$R" | jq_ recibo) 4 1 2" "$(echo "$E2" | J '$d["recibos"][0]["recibo"]') $(echo "$E2" | J 'count($d["recibos"][0]["conceptos"])') $(echo "$E2" | J 'count(array_filter($d["recibos"][0]["conceptos"],function($c){return strpos($c["concepto"],"(EXONERADA)")!==false;}))') $(q "select count(*) from pagos_metodos where id_pago=$(echo "$R" | jq_ id_pago)")"

echo "== Anular"
ID=$(echo "$E2" | J '$d["recibos"][0]["id_pago_pension"]')
check "anular sin motivo -> rechazado" "400" "$(code CONTA1 -d "id=$ID&motivo=" $B/controller/pago_pension/controlador_eliminar_pago_pension.php)"
post CONTA1 -d "id=$ID&motivo=Error de digitacion" $B/controller/pago_pension/controlador_eliminar_pago_pension.php > /dev/null
check "anulado: los meses vuelven a deberse y el recibo sale de la lista" "1 0" "$(post CONTA1 -d "accion=estado&matricula=$MAT" $C | J '(int)($d["pensiones"][0]["saldo_pension"]>0)') $(post CONTA1 -d "accion=estado&matricula=$MAT" $C | J 'count(array_filter($d["recibos"],function($r)use($argv){return $r["recibo"]===$argv[2];}))' "$(echo "$R" | jq_ recibo)")"

echo "== Menú"
check "el contador abre la caja nueva desde el menú" "1 0" "$(grep -c "pago_pensiones/view_caja.php')\" class=\"nav-link\"" $T/CONTA1.html) $(grep -c "pago_pensiones/view_pago_pensiones.php')\" class=\"nav-link\"" $T/CONTA1.html)"
terminar
