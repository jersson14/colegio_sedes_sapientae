#!/usr/bin/env bash
# Fase 7: reportes financieros y utilidades. Requiere la BD de prueba recién reiniciada (tests/reset_test_db.sh).
source "$(dirname "$0")/lib.sh"
R7=$B/controller/reportes
saldo() { q "call SP_SALDO_CUENTA($1, '$2', @s); select @s;"; }
HOY=$(date +%Y-%m-%d); ANIO=$(date +%Y); MES=$((10#$(date +%m)))
read P1 P15 P20 PFIN PANIO <<< "$(php -r '$p=new DateTime("first day of last month"); echo $p->format("Y-m-d")," ",$p->format("Y-m-15")," ",$p->format("Y-m-20")," ",$p->format("Y-m-t")," ",$p->format("Y");')"
MANANA=$(php -r 'echo date("Y-m-d", strtotime("+1 day"));')
crear_contador
login CONTA1; login JERSSON123; login JOSE; login DANIEL20
EF=$(q "select id_metodo from metodos_pago where nombre='EFECTIVO'"); TR=$(q "select id_metodo from metodos_pago where nombre='TRANSFERENCIA BANCARIA'")
post CONTA1 -d "tipo=BANCO&nombre=BANCO TEST&banco=BANCO&numero=123&saldo=0&fecha_saldo=2000-01-01" $B/controller/configuracion/controlador_cuenta_guardar.php > /dev/null
BANCO=$(q "select id_cuenta from cuentas_financieras where nombre='BANCO TEST'")
UNI=$(q "select id_indicadores from indicadores where nombre='VENTA DE UNIFORMES'")
LUZ=$(q "select id_indicadores from indicadores where nombre='PAGO DE LA LUZ'"); AGUA=$(q "select id_indicadores from indicadores where nombre='PAGO DE RECIBO AGUA'")

echo "== Acceso"
check "el contador ve Reportes financieros en su menú" "1" "$(grep -c 'reportes/view_reportes_financieros.php.*class="nav-link"' $T/CONTA1.html)"
check "  y el gráfico del año en su panel" "1" "$(grep -c 'id="pc_grafico"' $T/CONTA1.html)"
check "el contador abre la pantalla" "200" "$(code CONTA1 $B/view/reportes/view_reportes_financieros.php)"
check "el docente NO ve reportes" "403" "$(code JOSE -d "desde=$P1&hasta=$PFIN" $R7/controlador_resultados.php)"
check "el estudiante NO ve la morosidad" "403" "$(code DANIEL20 -d "anio=5&nivel=0&aula=0" $R7/controlador_morosidad.php)"
check "el docente NO abre el PDF de utilidad" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/JOSE.jar "$B/view/MPDF/REPORTE/estado_resultados.php?desde=$P1&hasta=$PFIN")"

echo "== Validación"
check "desde mayor que hasta -> 400" "400" "$(code CONTA1 -d "desde=$PFIN&hasta=$P1" $R7/controlador_resultados.php)"
check "fecha inválida -> 400" "400" "$(code CONTA1 -d "desde=2026-02-30&hasta=$PFIN" $R7/controlador_flujo_caja.php)"
check "rango de más de 10 años -> 400" "400" "$(code CONTA1 -d "desde=2000-01-01&hasta=$HOY" $R7/controlador_por_metodo.php)"

echo "== Movimientos del mes anterior ($P1 a $PFIN)"
ing() { post CONTA1 -d "$1" $B/controller/ingresos/controlador_registrar_ingresos.php | jq_ codigo; }
eg() { post CONTA1 -d "$1" $B/controller/egresos/controlador_registrar_egreso.php; }
check "ingreso 300 uniformes en efectivo" "1" "$(ing "indi=$UNI&cantidad=3&monto=300&fecha=$P15&metodo=$EF&cuenta=1")"
check "ingreso 120 por transferencia al banco" "1" "$(ing "indi=$UNI&cantidad=1&monto=120&fecha=$P15&metodo=$TR&cuenta=$BANCO&referencia=OP1")"
check "egreso luz 200 en efectivo" "1" "$(eg "indi=$LUZ&cantidad=1&monto=200&fecha=$P15&metodo=$EF&cuenta=1" | jq_ codigo)"
check "egreso agua 80 desde el banco" "1" "$(eg "indi=$AGUA&cantidad=1&monto=80&fecha=$P20&metodo=$TR&cuenta=$BANCO&referencia=OP2" | jq_ codigo)"
EG=$(eg "indi=$LUZ&cantidad=1&monto=50&fecha=$P20&metodo=$EF&cuenta=1" | jq_ id)
post CONTA1 -d "id=$EG&obser=duplicado" $B/controller/egresos/controlador_anular_egreso.php > /dev/null

echo "== Estado de resultados (criterio de cierre)"
r=$(post CONTA1 -d "desde=$P1&hasta=$PFIN" $R7/controlador_resultados.php)
check "utilidad del mes = suma manual de ingresos y egresos válidos (420 - 280)" "420 280 140 33.3" "$(echo "$r" | jq_ INGRESO.total) $(echo "$r" | jq_ EGRESO.total) $(echo "$r" | jq_ utilidad) $(echo "$r" | jq_ margen)"
r=$(post CONTA1 -d "desde=2020-01-01&hasta=$HOY" $R7/controlador_resultados.php)
check "historial: ingresos y egresos = SQL" "$(q "select sum(monto) from ingresos where estado='VALIDO'") $(q "select sum(monto) from egresos where estado='VALIDO'")" "$(echo "$r" | jq_ INGRESO.total) $(echo "$r" | jq_ EGRESO.total)"
check "la suma de los grupos = total" "$(echo "$r" | jq_ INGRESO.total)" "$(echo "$r" | jqsum INGRESO.grupos total)"

echo "== Comparativo, flujo y formas de pago"
r=$(post CONTA1 -d "anio=$PANIO" $R7/controlador_mensual.php)
check "mes anterior: ingresos 420, utilidad 140" "420 140" "$(echo "$r" | jq_ data.$((10#${P1:5:2} - 1)).ingresos) $(echo "$r" | jq_ data.$((10#${P1:5:2} - 1)).utilidad)"
r=$(post CONTA1 -d "desde=$P1&hasta=$PFIN" $R7/controlador_flujo_caja.php)
check "caja: 10000 + 300 - 200 = saldo del libro" "$(saldo 1 "$PFIN 23:59:59")" "$(echo "$r" | jq_ data.0.saldo_final)"
check "banco: saldo final 40" "40" "$(echo "$r" | jq_ data.1.saldo_final)"
r=$(post CONTA1 -d "desde=$P1&hasta=$PFIN" $R7/controlador_por_metodo.php)
check "cobros 420 y pagos 280 por forma de pago" "420 280" "$(echo "$r" | jq_ INGRESO.total) $(echo "$r" | jq_ EGRESO.total)"

echo "== Morosidad y proyección"
check "la vista coincide con la pantalla de cobro" "0" "$(php "$R/tests/comparar_morosidad.php" colegio2_test | cut -d' ' -f2)"
r=$(post CONTA1 -d "anio=5&nivel=0&aula=0" $R7/controlador_morosidad.php)
SQL_D=$(q "select sum(saldo_pension+saldo_mora) from vw_estado_pensiones where id_anio=5 and vencida=1")
check "deuda total = suma manual = panel" "$SQL_D $SQL_D" "$(echo "$r" | jq_ totales.deuda) $(post CONTA1 -X POST $B/controller/contabilidad/controlador_panel.php | jq_ morosidad)"
r=$(post CONTA1 -d "anio=5" $R7/controlador_proyeccion.php)
check "esperado = cobrado + vencido + por vencer" "$(echo "$r" | jq_ totales.esperado)" "$(echo "$r" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["totales"]; echo $d["cobrado"]+$d["vencido"]+$d["por_vencer"];')"

echo "== PDF"
check "PDF estado de resultados" "%PDF" "$(curl -s -b $T/CONTA1.jar "$B/view/MPDF/REPORTE/estado_resultados.php?desde=$P1&hasta=$PFIN" | head -c 4)"
check "PDF flujo de caja" "%PDF" "$(curl -s -b $T/CONTA1.jar "$B/view/MPDF/REPORTE/flujo_caja.php?desde=$P1&hasta=$PFIN" | head -c 4)"
check "PDF morosidad" "%PDF" "$(curl -s -b $T/CONTA1.jar "$B/view/MPDF/REPORTE/morosidad.php?anio=5&nivel=0&aula=0" | head -c 4)"
terminar
