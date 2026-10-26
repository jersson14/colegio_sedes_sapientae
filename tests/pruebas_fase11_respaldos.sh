#!/usr/bin/env bash
# Fase 11: respaldos desde el sistema (Configuración > Respaldos). Genera, restaura en una base aparte y compara.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
RC=$B/controller/respaldos/controlador_respaldos.php
MY="/c/xampp/mysql/bin/mysql.exe --default-character-set=utf8mb4 -uroot -P3307 -h127.0.0.1"
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario='JOSE'"
login JERSSON123; login CONTA1; login JOSE; login DANIEL20

echo "== Generar"
J=$(post JERSSON123 -d accion=generar $RC)
ARCH=$(echo "$J" | jq_ archivo)
check "respaldo completo: tablas, vistas, rutinas y triggers" "true $(q "select count(*) from information_schema.tables where table_schema='colegio2_test' and table_type='BASE TABLE'") $(q "select count(*) from information_schema.tables where table_schema='colegio2_test' and table_type='VIEW'") $(q "select count(*) from information_schema.routines where routine_schema='colegio2_test'") $(q "select count(*) from information_schema.triggers where trigger_schema='colegio2_test'") 0" "$(echo "$J" | jq_ ok) $(echo "$J" | jq_ tablas) $(echo "$J" | jq_ vistas) $(echo "$J" | jq_ rutinas) $(echo "$J" | jq_ triggers) $(echo "$J" | jq_ avisos)"
check "aparece en la lista como generado desde el sistema" "Generado desde el sistema" "$(post JERSSON123 -d accion=listar $RC | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["respaldos"] as $r){ if($r["archivo"]==="'$ARCH'"){ echo $r["tipo"]; } }')"

echo "== Restaurar en una base aparte y comparar"
$MY -e "drop database if exists colegio2_restaurado; create database colegio2_restaurado character set utf8mb4 collate utf8mb4_unicode_ci"
gzip -dc "$R/respaldos/$ARCH" | $MY colegio2_restaurado 2> $T/err.txt
check "se restaura sin errores" "" "$(cat $T/err.txt)"
objetos() { $MY -N -e "select (select count(*) from information_schema.tables where table_schema='$1' and table_type='BASE TABLE'), (select count(*) from information_schema.tables where table_schema='$1' and table_type='VIEW'), (select count(*) from information_schema.routines where routine_schema='$1'), (select count(*) from information_schema.triggers where trigger_schema='$1')" | tr -d '\r' | tr '\t' ' '; }
check "mismas tablas, vistas, rutinas y triggers" "$(objetos colegio2_test)" "$(objetos colegio2_restaurado)"
printf "select table_name from information_schema.tables where table_schema='colegio2_test' and table_type='BASE TABLE'" > $T/t.sql
dif=""
while read -r t; do
  printf "checksum table colegio2_test.\`%s\`; checksum table colegio2_restaurado.\`%s\`;" "$t" "$t" > $T/c.sql
  set -- $($MY -N < $T/c.sql | tr -d '\r' | cut -f2)
  [ "$1" != "$2" ] && dif="$dif $t"
done < <($MY -N < $T/t.sql | tr -d '\r')
check "los datos de las 60 tablas son idénticos (checksum)" "" "$dif"
check "la vista de pensiones funciona igual en la copia" "$($MY -N colegio2_test -e 'select count(*), sum(saldo_pension) from vw_estado_pensiones' | tr -d '\r')" "$($MY -N colegio2_restaurado -e 'select count(*), sum(saldo_pension) from vw_estado_pensiones' | tr -d '\r')"
check "restaurar no duplicó la auditoría (triggers creados después de los datos)" "$(q 'select count(*) from auditoria')" "$($MY -N colegio2_restaurado -e 'select count(*) from auditoria' | tr -d '\r')"
$MY -e "drop database colegio2_restaurado"

echo "== Descargar"
check "descarga el archivo comprimido válido" "200" "$(curl -s -o $T/d.gz -w '%{http_code}' -b $T/JERSSON123.jar "$RC?accion=descargar&archivo=$ARCH")"
check "(el archivo descargado es un gzip válido)" "ok" "$(gzip -t $T/d.gz && echo ok)"
check "no descarga otros archivos del servidor" "404 404" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/JERSSON123.jar "$RC?accion=descargar&archivo=../config/config.php") $(curl -s -o /dev/null -w '%{http_code}' -b $T/JERSSON123.jar "$RC?accion=descargar&archivo=..%2F..%2Findex.php")"
check "la carpeta respaldos no se sirve por la web" "403" "$(curl -s -o /dev/null -w '%{http_code}' "$B/respaldos/$ARCH")"

echo "== Solo el administrador"
for u in CONTA1 JOSE DANIEL20; do
  check "$u NO genera, lista ni descarga" "403 403 403" "$(code $u -d accion=generar $RC) $(code $u -d accion=listar $RC) $(curl -s -o /dev/null -w '%{http_code}' -b $T/$u.jar "$RC?accion=descargar&archivo=$ARCH")"
done

echo "== Eliminar"
check "no elimina fuera de la carpeta" "404" "$(code JERSSON123 -d accion=eliminar -d archivo=../index.php $RC)"
check "elimina el respaldo" "true no" "$(post JERSSON123 -d accion=eliminar -d archivo=$ARCH $RC | jq_ ok) $(test -f "$R/respaldos/$ARCH" && echo si || echo no)"
check "(index.php sigue en su lugar)" "si" "$(test -f "$R/index.php" && echo si)"

rm -f "$R"/respaldos/colegio2_test_*_manual.sql.gz
terminar
