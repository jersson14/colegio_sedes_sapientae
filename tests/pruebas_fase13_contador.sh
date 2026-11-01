#!/usr/bin/env bash
# Fase 13 (después del paso 16): pantallas del contador rehechas: Ingresos, Gastos, Pensiones por nivel y Cajas y bancos.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario='JOSE'"
login CONTA1; login JERSSON123; login JOSE
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
HOY=$(date +%Y-%m-%d); INI=$(date +%Y-01-01)
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")

echo "== Menús con las pantallas nuevas (contador y dirección)"
for v in pago_pensiones/view_caja.php ingresos_y_egresos/view_movimientos_ingresos.php ingresos_y_egresos/view_movimientos_gastos.php pensiones/view_pensiones_nivel.php contabilidad/view_cuentas.php; do
  check "menú: $v" "1 1" "$(grep -c "'$v')\" class=\"nav-link\"" $T/CONTA1.html | sed 's/[1-9][0-9]*/1/') $(grep -c "'$v')\" class=\"nav-link\"" $T/JERSSON123.html | sed 's/[1-9][0-9]*/1/')"
done
check "sin enlaces a las pantallas viejas" "0 0" "$(grep -c "view_ingresos.php\|view_egresos.php\|view_cajas_bancos.php\|'pensiones/view_pensiones.php'\|view_pago_pensiones.php" $T/CONTA1.html) $(grep -c "view_ingresos.php\|view_egresos.php\|view_cajas_bancos.php\|'pensiones/view_pensiones.php'\|view_pago_pensiones.php" $T/JERSSON123.html)"
check "las pantallas cargan para el contador" "200 200 200 200" "$(for v in ingresos_y_egresos/view_movimientos_ingresos.php ingresos_y_egresos/view_movimientos_gastos.php pensiones/view_pensiones_nivel.php contabilidad/view_cuentas.php; do curl -s -o /dev/null -w '%{http_code}' -b $T/CONTA1.jar $B/view/$v; echo; done | paste -sd' ')"

echo "== Ingresos y gastos (mismos controladores que la pantalla)"
DIF0=$(post CONTA1 -d "fechaini=$INI&fechafin=$HOY" $B/controller/egresos/controlador_listar_diferencia_filtro.php | J 'number_format($d["data"][0]["Diferencia"],2,".","")')
R=$(post CONTA1 -d "indi=4&cantidad=1&monto=80&obse=Prueba&metodo=1&cuenta=1&fecha=$HOY" $B/controller/ingresos/controlador_registrar_ingresos.php)
ID=$(echo "$R" | jq_ id)
check "registrar un ingreso con su caja: aparece en la lista y suma a la diferencia" "1 1 $(php -r "echo number_format($DIF0+80,2,'.','');")" "$(echo "$R" | jq_ codigo) $(post CONTA1 -d "indi=0&fechaini=$INI&fechafin=$HOY" $B/controller/ingresos/controlador_listar_ingresos_diversos_filtros.php | J 'count(array_filter($d["data"],function($x)use($argv){return $x["id_ingreso"]==$argv[2] && $x["cuenta"]!=="";}))' "$ID") $(post CONTA1 -d "fechaini=$INI&fechafin=$HOY" $B/controller/egresos/controlador_listar_diferencia_filtro.php | J 'number_format($d["data"][0]["Diferencia"],2,".","")')"
check "y como entrada en Cajas y bancos" "80.00" "$(post CONTA1 -d "cuenta=1&desde=$HOY&hasta=$HOY" $B/controller/contabilidad/controlador_movimientos.php | J 'number_format($d["entradas"],2,".","")')"
check "anular sin motivo -> rechazado; con motivo deja de sumar" "400 $DIF0" "$(code CONTA1 -d "id=$ID&obser=" $B/controller/ingresos/controlador_anular_ingreso.php) $(post CONTA1 -d "id=$ID&obser=Prueba" $B/controller/ingresos/controlador_anular_ingreso.php > /dev/null; post CONTA1 -d "fechaini=$INI&fechafin=$HOY" $B/controller/egresos/controlador_listar_diferencia_filtro.php | J 'number_format($d["data"][0]["Diferencia"],2,".","")')"
check "registrar un gasto con beneficiario y comprobante" "1" "$(post CONTA1 -d "indi=2&cantidad=1&monto=30&obse=&metodo=1&cuenta=1&fecha=$HOY&beneficiario=Luz del Sur&comprobante=F001-123" $B/controller/egresos/controlador_registrar_egreso.php | jq_ codigo)"
check "el docente no entra a ingresos ni a gastos" "403 403" "$(code JOSE -d "indi=0&fechaini=$INI&fechafin=$HOY" $B/controller/ingresos/controlador_listar_ingresos_diversos_filtros.php) $(code JOSE -d "indi=0&fechaini=$INI&fechafin=$HOY" $B/controller/egresos/controlador_listar_egresos_diversos_filtros.php)"

echo "== Pensiones por nivel"
L=$(post CONTA1 -d "anio=$ANIO&nivel=0" $B/controller/pensiones/controlador_listar_pensiones.php)
check "cada pensión trae su nivel, si vence dentro del año y cuántos pagos tiene" "1" "$(echo "$L" | J '(int)isset($d["data"][0]["id_nivel_academico"],$d["data"][0]["fecha_en_anio"],$d["data"][0]["pagos"])')"
P=$(echo "$L" | J 'implode("",array_map(function($p){static $u=0; if($u||$p["pagos"]>0) return ""; $u=1; return $p["id_pensiones"]."|".$p["id_nivel_academico"]."|".$p["mes"]."|".$p["anio_escolar"]."-".str_pad($p["mes_num"],2,"0",STR_PAD_LEFT)."-28";},$d["data"]))')
IFS='|' read PID PNIV PMES PFEC <<< "$P"
check "editar el precio y la fecha (dentro del año) de un mes sin pagos" "1 135.00" "$(post CONTA1 -d "id=$PID&anio=$ANIO&nivel=$PNIV&mes=$PMES&fecha=$PFEC&precio=135&mora=5" $B/controller/pensiones/controlador_modificar_pension.php | tr -d '[:space:]') $(q "select precio from pensiones where id_pensiones=$PID")"
check "revisar vencimientos fuera del año (sin aplicar)" "true 0" "$(post CONTA1 -d "anio=$ANIO&aplicar=0" $B/controller/pensiones/controlador_ajustar_vencimientos.php | jq_ ok) $(post CONTA1 -d "anio=$ANIO&aplicar=0" $B/controller/pensiones/controlador_ajustar_vencimientos.php | jq_ aplicado)"

echo "== Cajas y bancos"
S=$(post CONTA1 $B/controller/contabilidad/controlador_saldos.php)
check "saldos por cuenta con lo de hoy y lo pendiente de conciliar" "1" "$(echo "$S" | J '(int)isset($d["data"][0]["saldo"],$d["data"][0]["entradas_hoy"],$d["data"][0]["sin_conciliar"])')"
R=$(post CONTA1 -d "cuenta=1&fecha=$HOY" $B/controller/contabilidad/controlador_caja_resumen.php)
ESP=$(echo "$R" | J 'number_format($d["data"]["saldo_esperado"],2,".","")')
check "cierre de caja: con lo contado igual al esperado, cuadra" "1 0.00" "$(post CONTA1 -d "cuenta=1&fecha=$HOY&contado=$ESP&observacion=&ajustar=0" $B/controller/contabilidad/controlador_caja_cerrar.php | jq_ codigo) $(post CONTA1 -d "cuenta=1&fecha=$HOY" $B/controller/contabilidad/controlador_caja_resumen.php | J 'number_format($d["cierres"][0]["diferencia"],2,".","")')"
terminar
