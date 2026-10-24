#!/usr/bin/env bash
# Fase 8B: planilla del personal administrativo y asistencia docente. Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
P=$B/controller/planilla
A=$B/controller/asistencia_docente
saldo_caja() { q "call SP_SALDO_CUENTA(1, '9999-12-31', @s); select @s;"; }
HOY=$(date +%Y-%m-%d); ANIO=$(date +%Y); MES=$((10#$(date +%m)))
D1=$(date +%Y-%m-01); D2=$(date +%Y-%m-02); D3=$HOY
crear_contador
EF=$(q "select id_metodo from metodos_pago where nombre='EFECTIVO'")
TC=$(q "select id_tipo_contrato from tipos_contrato where nombre='TIEMPO COMPLETO'"); PH=$(q "select id_tipo_contrato from tipos_contrato where nombre='POR HORAS'")
AUX=$(q "select personal_adm_id from personal_admi p join usuario u on u.usu_id=p.id_ausuario where u.usu_usuario='MANUEL123'")
DJOSE=$(q "select Id_docente from docentes d join usuario u on u.usu_id=d.id_asusuario where u.usu_usuario='JOSE'")
DOTRO=$(q "select Id_docente from docentes where docente_estatus='ACTIVO' and Id_docente<>$DJOSE order by Id_docente limit 1")
login CONTA1; login JERSSON123; login JOSE; login MANUEL123

echo "== Migración y menús"
check "las planillas existentes quedan como DOCENTE" "0" "$(q "select count(*) from planillas where tipo_personal<>'DOCENTE'")"
check "no se puede guardar un contrato sin persona (restricción)" "1" "$($M -e "insert into contratos_docente(tipo_contrato_id,fecha_inicio,metodo_pago_id,created_at) values($TC,'2026-01-01',$EF,now())" 2>&1 | grep -c 'CONSTRAINT')"
check "el administrador ve Planilla de personal y Asistencia docente" "1 1" "$(grep -c 'Planilla de personal' $T/JERSSON123.html) $(grep -c 'asistencia_docente/view_asistencia_docente.php' $T/JERSSON123.html)"
check "el auxiliar ve Asistencia docente y Mis boletas" "1 1" "$(grep -c 'asistencia_docente/view_asistencia_docente.php' $T/MANUEL123.html) $(grep -c 'view_mis_boletas.php' $T/MANUEL123.html)"

echo "== Contratos del personal administrativo"
check "lista del personal administrativo (3 + la contadora)" "4" "$(post CONTA1 -d "tipo_personal=ADMINISTRATIVO" $P/controlador_contratos_listar.php | jq_ data)"
check "tipo de personal inválido -> 400" "400" "$(code CONTA1 -d "tipo_personal=OTRO" $P/controlador_contratos_listar.php)"
ctr() { post CONTA1 -d "$1" $P/controlador_contrato_guardar.php | jq_ "${2:-codigo}"; }
check "contrato del auxiliar: tiempo completo 1500" "1" "$(ctr "tipo_personal=ADMINISTRATIVO&docente=$AUX&tipo=$TC&inicio=$ANIO-01-01&sueldo=1500&horas=40&metodo=$EF")"
check "persona inexistente -> rechazado" "0" "$(ctr "tipo_personal=ADMINISTRATIVO&docente=9999&tipo=$TC&inicio=$ANIO-01-01&sueldo=1500&metodo=$EF")"
check "docente por horas 25/h y docente tiempo completo 2000" "1 1" "$(ctr "tipo_personal=DOCENTE&docente=$DJOSE&tipo=$PH&inicio=$ANIO-01-01&tarifa=25&horas=10&metodo=$EF") $(ctr "tipo_personal=DOCENTE&docente=$DOTRO&tipo=$TC&inicio=$ANIO-01-01&sueldo=2000&horas=40&metodo=$EF")"

echo "== Asistencia docente"
check "el docente NO registra asistencia" "403" "$(code JOSE -d "fecha=$HOY" $A/controlador_dia.php)"
g() { post MANUEL123 --data-urlencode "fecha=$1" --data-urlencode "filas=$2" $A/controlador_guardar.php; }
check "día 1 presente 4 h; día 2 tardanza 4 h y 10 min" "1 1" "$(g $D1 "[{\"docente\":$DJOSE,\"estado\":\"PRESENTE\",\"horas\":\"4\",\"minutos\":\"0\"}]" | jq_ guardadas) $(g $D2 "[{\"docente\":$DJOSE,\"estado\":\"TARDANZA\",\"horas\":\"4\",\"minutos\":\"10\"}]" | jq_ guardadas)"
check "tardanza sin minutos -> rechazada" "0" "$(g $D2 "[{\"docente\":$DJOSE,\"estado\":\"TARDANZA\",\"horas\":\"4\",\"minutos\":\"0\"}]" | jq_ guardadas)"
check "hoy: falta (aunque envíen 4 h, se guardan 0)" "1 0" "$(g $D3 "[{\"docente\":$DJOSE,\"estado\":\"FALTA\",\"horas\":\"4\",\"minutos\":\"0\"}]" | jq_ guardadas) $(q "select horas_dictadas from asistencia_docente where docente_id=$DJOSE and fecha='$D3'")"
check "fecha futura -> rechazada" "0" "$(g 2099-01-01 "[{\"docente\":$DJOSE,\"estado\":\"PRESENTE\",\"horas\":\"4\",\"minutos\":\"0\"}]" | jq_ guardadas)"
check "corregir el día 1 a 5 h (no duplica)" "1 1" "$(g $D1 "[{\"docente\":$DJOSE,\"estado\":\"PRESENTE\",\"horas\":\"5\",\"minutos\":\"0\"}]" | jq_ guardadas) $(q "select count(*) from asistencia_docente where docente_id=$DJOSE and fecha='$D1'")"

echo "== Planillas separadas por tipo de personal"
r=$(post CONTA1 -d "anio=$ANIO&mes=$MES&tipo_personal=DOCENTE" $P/controlador_planilla_generar.php)
PD=$(echo "$r" | jq_ id)
check "planilla DOCENTE: el docente por horas cobra la asistencia (9 h × 25)" "1 9.00 ASISTENCIA 225.00" "$(echo "$r" | jq_ codigo) $(q "select concat(horas,' ',horas_origen,' ',base) from planilla_detalle where id_planilla=$PD and docente_id=$DJOSE")"
r=$(post CONTA1 -d "anio=$ANIO&mes=$MES&tipo_personal=ADMINISTRATIVO" $P/controlador_planilla_generar.php)
PA=$(echo "$r" | jq_ id)
check "planilla ADMINISTRATIVA del mismo mes con el auxiliar (1500)" "1 1 1500.00" "$(echo "$r" | jq_ codigo) $(q "select concat(count(*),' ',sum(base)) from planilla_detalle where id_planilla=$PA")"
check "generarla otra vez -> 2" "2" "$(post CONTA1 -d "anio=$ANIO&mes=$MES&tipo_personal=ADMINISTRATIVO" $P/controlador_planilla_generar.php | jq_ codigo)"
DA=$(q "select id_detalle from planilla_detalle where id_planilla=$PA")

echo "== Adelanto, aprobación y pago del personal administrativo"
check "adelanto de 200 al auxiliar (se descuenta en su borrador: neto 1300)" "1 1300.00" "$(post CONTA1 -d "tipo_personal=ADMINISTRATIVO&docente=$AUX&monto=200&fecha=$HOY&metodo=$EF&cuenta=1" $P/controlador_adelanto_registrar.php | jq_ codigo) $(q "select neto from planilla_detalle where id_detalle=$DA")"
check "la contadora NO aprueba; el administrador sí" "403 1" "$(code CONTA1 -d "id=$PA" $P/controlador_planilla_aprobar.php) $(post JERSSON123 -d "id=$PA" $P/controlador_planilla_aprobar.php | jq_ codigo)"
check "pagar 1300 al auxiliar -> planilla PAGADA" "1 PAGADA" "$(post CONTA1 -d "id=$DA&monto=1300&metodo=$EF&cuenta=1&fecha=$HOY" $P/controlador_pagar.php | jq_ codigo) $(q "select estado from planillas where id_planilla=$PA")"
check "egresos en 5.1.2 = 1500 y ninguno en 5.1.1" "1500.00 0" "$(q "select sum(e.monto) from egresos e join indicadores i on i.id_indicadores=e.id_indicador where i.codigo='5.1.2' and e.estado='VALIDO'") $(q "select count(*) from egresos e join indicadores i on i.id_indicadores=e.id_indicador where i.codigo='5.1.1' and e.estado='VALIDO'")"
check "caja = 10000 - 200 - 1300 = 8500" "8500" "$(saldo_caja)"

echo "== Bloqueo de asistencia y boletas"
post JERSSON123 -d "id=$PD" $P/controlador_planilla_aprobar.php > /dev/null
check "con la planilla docente aprobada, la asistencia del mes ya no cambia" "0" "$(g $D1 "[{\"docente\":$DJOSE,\"estado\":\"FALTA\",\"horas\":\"0\",\"minutos\":\"0\"}]" | jq_ guardadas)"
check "el auxiliar ve su boleta; el docente la suya" "ADMINISTRATIVO DOCENTE" "$(post MANUEL123 -X POST $P/controlador_mis_boletas.php | jq_ data.0.tipo_personal) $(post JOSE -X POST $P/controlador_mis_boletas.php | jq_ data.0.tipo_personal)"
DJ=$(q "select id_detalle from planilla_detalle where id_planilla=$PD and docente_id=$DJOSE")
check "el auxiliar abre su boleta PDF pero no la de un docente" "%PDF 403" "$(curl -s -b $T/MANUEL123.jar "$B/view/MPDF/REPORTE/boleta_docente.php?id=$DA" | head -c 4) $(curl -s -o /dev/null -w '%{http_code}' -b $T/MANUEL123.jar "$B/view/MPDF/REPORTE/boleta_docente.php?id=$DJ")"
terminar
