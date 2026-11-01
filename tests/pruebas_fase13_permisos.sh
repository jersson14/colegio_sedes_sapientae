#!/usr/bin/env bash
# Fase 13 paso 15: permisos por rol y parámetros del sistema desde una pantalla.
# Requiere la BD de prueba recién reiniciada (migración 022).
source "$(dirname "$0")/lib.sh"
P=$B/controller/configuracion/controlador_permisos.php
PA=$B/controller/configuracion/controlador_parametros.php
PEN=$B/controller/pensiones/controlador_anios_escolares.php
REP=$B/controller/reportes/controlador_reporte_estudiantes.php
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login MANUEL123; login JOSE
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
guardar() { post JERSSON123 -d "accion=guardar&rol=$1&vista=$2&permitido=$3" $P; }
celda() { post JERSSON123 -d accion=listar $P | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["pantallas"] as $p) if($p["vista"]===$argv[1]) { $c=$p["roles"][$argv[2]]; echo ($c["actual"]?1:0),($c["cambio"]?1:0),($c["editable"]?1:0); }' "$1" "$2"; }

echo "== El mapa de pantallas está al día"
cp controller/_pantallas.php "$T/pantallas.php" 2>/dev/null || cp "$R/controller/_pantallas.php" "$T/pantallas.php"
(cd "$R" && php tests/herramientas/revisar_permisos.php --generar > /dev/null)
check "controller/_pantallas.php coincide con el menú (regenerarlo con --generar)" "igual" "$(cmp -s "$R/controller/_pantallas.php" "$T/pantallas.php" && echo igual || echo distinto)"

echo "== Permisos por rol"
check "solo la dirección entra" "403 403" "$(code MANUEL123 -d accion=listar $P) $(code MANUEL123 -d accion=listar $PA)"
check "de fábrica: el auxiliar no ve pensiones, sí el reporte de estudiantes (pensiones se le puede dar)" "001 101" "$(celda pensiones/view_pensiones.php AUXILIAR) $(celda reportes/view_reporte_estudiantes.php AUXILIAR)"
check "y sus controladores responden igual (400 = entra, faltan datos)" "403 400" "$(code MANUEL123 $PEN) $(code MANUEL123 $REP)"
guardar AUXILIAR pensiones/view_pensiones.php 1 > /dev/null
guardar AUXILIAR reportes/view_reporte_estudiantes.php 0 > /dev/null
check "dar pensiones y quitar el reporte: queda marcado como cambio" "111 011" "$(celda pensiones/view_pensiones.php AUXILIAR) $(celda reportes/view_reporte_estudiantes.php AUXILIAR)"
check "los controladores siguen el cambio" "200 403" "$(code MANUEL123 $PEN) $(code MANUEL123 $REP)"
login MANUEL123
check "menú del auxiliar: pensiones en MÁS PANTALLAS y el reporte oculto" "1 1" "$(grep -c "data-vista=\"pensiones/view_pensiones.php\"" $T/MANUEL123.html) $(grep -o 'PANTALLAS_QUITADAS = [^;]*' $T/MANUEL123.html | grep -c reporte_estudiantes)"
check "lo que el auxiliar usa en su inicio no se pierde" "200" "$(code MANUEL123 $B/controller/panel/controlador_panel_rol.php)"
check "el docente no se ve afectado" "403" "$(code JOSE $PEN)"
check "pantallas críticas y propias de otro rol no se pueden dar" "400 400 400" "$(code JERSSON123 -d 'accion=guardar&rol=AUXILIAR&vista=configuracion/view_permisos.php&permitido=1' $P) $(code JERSSON123 -d 'accion=guardar&rol=AUXILIAR&vista=configuracion/view_respaldos.php&permitido=1' $P) $(code JERSSON123 -d 'accion=guardar&rol=DOCENTE&vista=estudiante/view_mis_pagos.php&permitido=1' $P)"
check "aunque se fuerce en la tabla, permisos no se abre a otro rol" "403" "$($M -e "insert into permisos_pantalla values ('AUXILIAR','configuracion/view_permisos.php',1,null,now())"; code MANUEL123 -d accion=listar $P)"
guardar AUXILIAR reportes/view_reporte_estudiantes.php 1 > /dev/null
check "volver a lo de fábrica borra el cambio" "101" "$(celda reportes/view_reporte_estudiantes.php AUXILIAR)"
post JERSSON123 -d "accion=restablecer&rol=AUXILIAR" $P > /dev/null
check "restablecer el rol: sin cambios y pensiones vuelve a 403" "0 403" "$(q "select count(*) from permisos_pantalla where rol='AUXILIAR'") $(code MANUEL123 $PEN)"

echo "== Parámetros"
L=$(post JERSSON123 -d accion=listar $PA)
check "de fábrica: 5 intentos, Presente, sin sábados" "5 Presente 0" "$(echo "$L" | jq_ valores.login_max_intentos) $(echo "$L" | jq_ valores.asistencia_P) $(echo "$L" | jq_ valores.clases_sabado)"
check "muestra el límite del servidor" "1" "$(echo "$L" | jq_ limite_servidor_mb | grep -c '^[1-9][0-9]*$')"
base="accion=guardar&login_max_intentos=5&login_minutos_bloqueo=15&video_max_mb=100&clases_sabado=0&asistencia_P=Presente&asistencia_T=Tardanza&asistencia_F=Falta&asistencia_J=Justificada"
check "valores fuera de rango o vacíos -> 400" "400 400 400" "$(code JERSSON123 -d "${base/login_max_intentos=5/login_max_intentos=99}" $PA) $(code JERSSON123 -d "${base/video_max_mb=100/video_max_mb=abc}" $PA) $(code JERSSON123 -d "${base/asistencia_P=Presente/asistencia_P=}" $PA)"
# Bloqueo por intentos: con 2 intentos se bloquea al tercero; con 0 no se bloquea nunca
post JERSSON123 -d "${base/login_max_intentos=5/login_max_intentos=2}" $PA > /dev/null
intento() { curl -s -d "u=JOSE&c=mala" $B/controller/usuario/controlador_iniciar_sesion.php | jq_ motivo; }
check "2 intentos: el tercero ya está bloqueado" "CREDENCIALES CREDENCIALES BLOQUEADO" "$(intento) $(intento) $(intento)"
post JERSSON123 -d "${base/login_max_intentos=5/login_max_intentos=0}" $PA > /dev/null
check "0 intentos: sin bloqueo" "CREDENCIALES CREDENCIALES" "$(intento) $(intento)"
$M -e "delete from login_intentos where usuario='JOSE'"
# Sábados y nombres de la asistencia
RA=$B/controller/registro_asistencia
MES=$(date +%Y-%m)
dias() { post MANUEL123 -d "anio=$ANIO&aula=18&mes=$MES" $RA/controlador_planilla.php | jq_ dias; }
SAB=$(php -r '$d=new DateTime("today"); while($d->format("N")!=6) $d->modify("-1 day"); echo $d->format("Y-m-d");')
D0=$(dias)
MAT=$(post MANUEL123 -d "anio=$ANIO&aula=18&mes=$MES" $RA/controlador_planilla.php | jq_ estudiantes.0.matricula)
check "sin sábados no se registra asistencia un sábado" "400" "$(code MANUEL123 -d "anio=$ANIO&aula=18" --data-urlencode "cambios=[{\"matricula\":$MAT,\"fecha\":\"$SAB\",\"codigo\":\"P\"}]" $RA/controlador_guardar.php)"
printf '%s' "${base/clases_sabado=0/clases_sabado=1}" | sed 's/asistencia_P=Presente/asistencia_P=Asisti%C3%B3/' > "$T/pa.txt"
post JERSSON123 -d @"$T/pa.txt" $PA > /dev/null
check "con sábados la planilla tiene más días y acepta el sábado" "1 200" "$([ "$(dias)" -gt "$D0" ] && echo 1 || echo 0) $(code MANUEL123 -d "anio=$ANIO&aula=18" --data-urlencode "cambios=[{\"matricula\":$MAT,\"fecha\":\"$SAB\",\"codigo\":\"P\"}]" $RA/controlador_guardar.php)"
check "la planilla trae el nombre nuevo de la P" "Asistió" "$(post MANUEL123 -d "anio=$ANIO&aula=18&mes=$MES" $RA/controlador_planilla.php | jq_ nombres.P)"
post JERSSON123 -d "${base/video_max_mb=100/video_max_mb=7}" $PA > /dev/null
check "el tamaño de video se usa al subir" "7" "$(cd "$R" && php -d upload_max_filesize=2048M -d post_max_size=2048M -r 'require "utilitario/archivos.php"; echo limite_video_mb();')"
post JERSSON123 -d "$base" $PA > /dev/null
check "menú: Permisos por rol y Parámetros solo para la dirección" "1 1 0 0" "$(grep -c 'configuracion/view_permisos.php' $T/JERSSON123.html) $(grep -c 'configuracion/view_parametros.php' $T/JERSSON123.html) $(grep -c 'configuracion/view_permisos.php' $T/MANUEL123.html) $(grep -c 'configuracion/view_parametros.php' $T/MANUEL123.html)"
terminar
