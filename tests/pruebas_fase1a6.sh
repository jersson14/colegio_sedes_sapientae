#!/usr/bin/env bash
# Fases 1 a 6: casos esenciales de seguridad, cobro de pensiones, configuración regional, recibos y formas de pago,
# contabilidad (cajas) y planilla docente. Rehecha en la Fase 9 (las suites originales se perdieron con la carpeta
# temporal de la sesión). Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
crear_contador
EF=$(q "select id_metodo from metodos_pago where nombre='EFECTIVO'"); TR=$(q "select id_metodo from metodos_pago where nombre='TRANSFERENCIA BANCARIA'")
HOY=$(date +%Y-%m-%d)
saldo() { q "call SP_SALDO_CUENTA($1, '9999-12-31', @s); select @s;"; }

echo "== Fase 1: seguridad"
r=$(curl -s -d "u=JERSSON123&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php)
check "login correcto sin devolver el hash de la contraseña" "true 0" "$(echo "$r" | jq_ ok) $(echo "$r" | grep -c '\$2y\$')"
check "clave incorrecta -> CREDENCIALES" "CREDENCIALES" "$(curl -s -d "u=JOSE&c=mala" $B/controller/usuario/controlador_iniciar_sesion.php | jq_ motivo)"
for i in 1 2 3 4 5; do curl -s -o /dev/null -d "u=MANUEL123&c=mala$i" $B/controller/usuario/controlador_iniciar_sesion.php; done
check "5 intentos fallidos bloquean el usuario (aunque luego use la clave correcta)" "BLOQUEADO" "$(curl -s -d "u=MANUEL123&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php | jq_ motivo)"
$M -e "delete from login_intentos"
check "sin sesión, un controlador responde 401" "401" "$(curl -s -o /dev/null -w '%{http_code}' -X POST $B/controller/alumnos/controlador_listar_alumnos.php)"
login JERSSON123; login JOSE; login DANIEL20; login CONTA1
check "sin token CSRF -> 403" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/JERSSON123.jar -X POST $B/controller/alumnos/controlador_listar_alumnos.php)"
check "el docente NO lista usuarios" "403" "$(code JOSE -X POST $B/controller/usuario/controlador_listar_usuario.php)"
check "el estudiante NO registra egresos" "403" "$(code DANIEL20 -d "indi=2&monto=1" $B/controller/egresos/controlador_registrar_egreso.php)"
check "la sesión no acepta el rol enviado por el navegador" "404" "$(code DANIEL20 -d "rol=ADMINISTRADOR" $B/controller/usuario/controlador_crear_sesion.php)"
check "archivos de prueba retirados (phpinfo)" "404" "$(curl -s -o /dev/null -w '%{http_code}' $B/phpinfo.php)"
check "respaldos y .git no se descargan" "403 404" "$(curl -s -o /dev/null -w '%{http_code}' $B/respaldos/colegio2_20261023_1545.sql) $(curl -s -o /dev/null -w '%{http_code}' $B/.git/config)"
echo '<?php echo "x";' > $T/malo.php
check "subir un .php como foto -> rechazado" "400" "$(code JERSSON123 -F "id=6" -F "nombrefoto=malo.php" -F "fotoactual=x" -F "foto=@$T/malo.php;type=image/png" $B/controller/alumnos/controlador_modificar_foto_estudiante.php)"

echo "== Fase 2: cobro de pensiones"
cobro() { post JERSSON123 --form-string "id_matri=$1" --form-string "lineas=$2" --form-string "metodos=$3" $B/controller/pago_pension/controlador_detalle_pago_pension.php; }
PB=$(q "select v.id_pension from vw_estado_pensiones v where v.id_matricula=40 and v.pagado_pension=0 and v.vencida=1 order by v.fecha_vencimiento limit 1")
ST=$(q "call SP_SALDO_PENSION(40, $PB)" | cut -f12)
check "pensión vencida: el saldo incluye la mora (100 + 10)" "110" "$ST"
check "pagar más que el saldo -> rechazado" "false" "$(cobro 40 "[{\"concepto\":\"PENSION\",\"id_pension\":\"$PB\",\"monto\":\"999\"}]" "[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"999\"}]" | jq_ ok)"
r=$(cobro 40 "[{\"concepto\":\"PENSION\",\"id_pension\":\"$PB\",\"monto\":\"110\"}]" "[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"110\"}]")
check "cobrar 110: primero la mora, luego la pensión" "true 10.00 100.00" "$(echo "$r" | jq_ ok) $(q "select concat(sum(if(concepto='MORA',sub_total,0)),' ',sum(if(concepto='PENSION',sub_total,0))) from pago_pensiones where id_matri=40 and id_pension=$PB and estado='VALIDO'")"
check "volver a cobrar la misma pensión -> rechazado" "false" "$(cobro 40 "[{\"concepto\":\"PENSION\",\"id_pension\":\"$PB\",\"monto\":\"1\"}]" "[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"1\"}]" | jq_ ok)"
PAGO=$(echo "$r" | jq_ id_pago)
check "cada pago genera su ingreso (sin duplicar)" "110.00" "$(q "select sum(i.monto) from ingresos i join pago_pensiones pp on pp.id_pago_pension=i.id_pago_pension where pp.id_pago=$PAGO and i.estado='VALIDO'")"

echo "== Fase 3: configuración regional"
check "el navegador recibe moneda y documento del país" "1" "$(grep -c '"moneda":"PEN"' $T/JERSSON123.html)"
check "documento inválido para Perú (7 dígitos) -> rechazado" "400" "$(code JERSSON123 -F "dni=1234567" -F "nombre=X" -F "apelli=Y" -F "espe=1" -F "sexo=MASCULINO" -F "fechanaci=1990-01-01" -F "telf=987654321" -F "telfal=" -F "direc=x" -F "nombrefoto=" -F "usu=ZZDOC" -F "contra=x" -F "email=x@x.com" $B/controller/docentes/controlador_registrar_docente.php)"

echo "== Fase 4: recibos y formas de pago"
check "el recibo tiene serie y número correlativo" "1" "$(q "select count(*) from pagos where id_pago=$PAGO and serie<>'' and numero>0")"
P2=$(q "select v.id_pension from vw_estado_pensiones v where v.id_matricula=40 and v.pagado_pension=0 and v.vencida=1 and v.id_pension<>$PB order by v.fecha_vencimiento limit 1")
r=$(cobro 40 "[{\"concepto\":\"PENSION\",\"id_pension\":\"$P2\",\"monto\":\"110\"}]" "[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"60\"},{\"id_metodo\":\"$TR\",\"id_cuenta\":\"1\",\"monto\":\"50\",\"referencia\":\"OP123\"}]")
check "pago mixto (efectivo + transferencia) en un solo recibo" "true 2" "$(echo "$r" | jq_ ok) $(q "select count(*) from pagos_metodos where id_pago=$(echo "$r" | jq_ id_pago)")"
check "las formas de pago deben sumar el total -> si no, rechazado" "false" "$(cobro 40 "[{\"concepto\":\"MATRICULA\",\"monto\":\"50\"}]" "[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"40\"}]" | jq_ ok)"
check "el PDF del recibo se genera" "%PDF" "$(curl -s -b $T/JERSSON123.jar "$B/view/MPDF/REPORTE/recibo.php?id=$PAGO" | head -c 4)"
check "el estudiante NO ve un recibo ajeno" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/DANIEL20.jar "$B/view/MPDF/REPORTE/recibo.php?id=$PAGO")"
PP=$(q "select id_pago_pension from pago_pensiones where id_pago=$PAGO limit 1")
check "anular el recibo: queda ANULADO (no se borra) y el saldo vuelve" "ANULADO 110" "$(post JERSSON123 -d "id=$PP&motivo=prueba" $B/controller/pago_pension/controlador_eliminar_pago_pension.php > /dev/null; q "select estado from pagos where id_pago=$PAGO") $(q "call SP_SALDO_PENSION(40, $PB)" | cut -f12 | cut -d. -f1)"

echo "== Fase 5: contabilidad"
LUZ=$(q "select id_indicadores from indicadores where nombre='PAGO DE LA LUZ'")
C0=$(saldo 1)
check "egreso de 50 desde la caja" "1" "$(post CONTA1 -d "indi=$LUZ&cantidad=1&monto=50&fecha=$HOY&metodo=$EF&cuenta=1" $B/controller/egresos/controlador_registrar_egreso.php | jq_ codigo)"
check "  la caja baja 50" "$(dos "$(php -r "echo $C0 - 50;")")" "$(saldo 1)"
check "egreso mayor al efectivo de la caja -> saldo insuficiente" "3" "$(post CONTA1 -d "indi=$LUZ&cantidad=1&monto=99999999&fecha=$HOY&metodo=$EF&cuenta=1" $B/controller/egresos/controlador_registrar_egreso.php | jq_ codigo)"
check "egreso con fecha futura -> rechazado" "0" "$(post CONTA1 -d "indi=$LUZ&cantidad=1&monto=5&fecha=2099-01-01&metodo=$EF&cuenta=1" $B/controller/egresos/controlador_registrar_egreso.php | jq_ codigo)"
check "la contadora no entra a alumnos" "403" "$(code CONTA1 -X POST $B/controller/alumnos/controlador_listar_alumnos.php)"

echo "== Fase 6: planilla docente"
P=$B/controller/planilla
TC=$(q "select id_tipo_contrato from tipos_contrato where nombre='TIEMPO COMPLETO'")
D1=$(q "select Id_docente from docentes where docente_estatus='ACTIVO' order by Id_docente limit 1")
check "contrato de tiempo completo 2000" "1" "$(post CONTA1 -d "tipo_personal=DOCENTE&docente=$D1&tipo=$TC&inicio=$(date +%Y)-01-01&sueldo=2000&horas=40&metodo=$EF" $P/controlador_contrato_guardar.php | jq_ codigo)"
r=$(post CONTA1 -d "anio=$(date +%Y)&mes=$((10#$(date +%m)))&tipo_personal=DOCENTE" $P/controlador_planilla_generar.php)
PL=$(echo "$r" | jq_ id)
check "planilla generada; la contadora NO la aprueba" "1 403" "$(echo "$r" | jq_ codigo) $(code CONTA1 -d "id=$PL" $P/controlador_planilla_aprobar.php)"
check "pagar antes de aprobar -> rechazado" "2" "$(post CONTA1 -d "id=$(q "select id_detalle from planilla_detalle where id_planilla=$PL limit 1")&monto=10&metodo=$EF&cuenta=1" $P/controlador_pagar.php | jq_ codigo)"
check "el administrador aprueba" "1" "$(post JERSSON123 -d "id=$PL" $P/controlador_planilla_aprobar.php | jq_ codigo)"
terminar
