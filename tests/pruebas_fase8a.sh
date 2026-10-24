#!/usr/bin/env bash
# Fase 8A: pensiones por año escolar, usuarios únicos y categorías con grupo. Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
P=$B/controller/pensiones
I=$B/controller/indicadores
H=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
crear_contador
sqlf "insert into \`año_escolar\`(\`año_escolar\`,Nombre_año,fecha_inicio,fecha_fin,descripcion,estado,created_at,updated_at) values('2027','PRUEBA','2027-03-01','2027-12-15','x','INACTIVO',now(),now())"
A27=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where \`año_escolar\`='2027'")
login CONTA1; login JERSSON123; login JOSE

echo "== Migración"
check "todas las pensiones tienen año escolar (año 5)" "30 0" "$(q "select concat(count(*),' ',sum(id_anio<>5)) from pensiones")"
check "ya no hay usuarios repetidos ni vacíos" "0 0" "$(q "select count(*) from (select usu_usuario from usuario group by usu_usuario having count(*)>1) x") $(q "select count(*) from usuario where trim(usu_usuario)=''")"
check "4 usuarios renombrados con registro" "4" "$(q "select count(*) from usuarios_renombrados")"
check "el 2.º JOAQUIN2 ahora usa el documento del alumno" "22626969" "$(q "select usu_usuario from usuario where usu_id=63")"
check "el ALEXANDE sin alumno quedó inactivo" "INACTIVO" "$(q "select usu_estatus from usuario where usu_id=65")"
$M -e "update usuario set usu_contra='$H' where usu_id=63"
login 22626969
check "el alumno renombrado entra con su documento" "1" "$(grep -q 'csrf-token' $T/22626969.html && echo 1)"
check "categorías con código (luz = 5.2.1)" "5.2.1" "$(q "select codigo from indicadores where nombre='PAGO DE LA LUZ'")"

echo "== Pensiones por año escolar"
r=$(post CONTA1 -d "anio=5&nivel=0" $P/controlador_listar_pensiones.php)
check "listado del año 5: 30 pensiones, 27 fuera del calendario" "30 27" "$(echo "$r" | jq_ data) $(echo "$r" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["data"]; echo count(array_filter($d, function($f){ return $f["fecha_en_anio"]==0; }));')"
reg() { post CONTA1 -d "$1" $P/controlador_registro_pensiones.php; }
check "mes repetido del mismo año -> 2" "2" "$(reg "anio=5&nivel=1&mes=MARZO&fecha=2026-03-31&precio=100&mora=10")"
check "vencimiento fuera del año escolar -> 3" "3" "$(reg "anio=$A27&nivel=1&mes=MARZO&fecha=2026-03-31&precio=100&mora=10")"
check "sin año escolar -> 400" "400" "$(code CONTA1 -d "nivel=1&mes=MARZO&fecha=2027-03-31&precio=100" $P/controlador_registro_pensiones.php)"
check "mismo nivel y mes en otro año -> 1" "1" "$(reg "anio=$A27&nivel=1&mes=MARZO&fecha=2027-03-31&precio=120&mora=12")"
g=$(post CONTA1 -d "anio=$A27&nivel=1&desde=3&hasta=12&dia=0&precio=120&mora=12" $P/controlador_generar_pensiones.php)
check "generar MAR-DIC 2027: 9 nuevas, 1 ya existía" "9 1" "$(echo "$g" | jq_ creadas) $(echo "$g" | jq_ existentes)"
check "  vencen el último día del mes" "2027-04-30 2027-12-31" "$(q "select fecha_vencimiento from pensiones where id_anio=$A27 and id_nivel_academico=1 and mes in ('ABRIL','DICIEMBRE') order by fecha_vencimiento" | tr '\n' ' ' | sed 's/ $//')"
g=$(post CONTA1 -d "anio=$A27&nivel=2&desde=11&hasta=2&dia=15&precio=150&mora=15" $P/controlador_generar_pensiones.php)
check "generar NOV→FEB cruza el fin de año (4 meses; ene/feb en 2028)" "4 2028-01-15 2028-02-15" "$(echo "$g" | jq_ creadas) $(q "select fecha_vencimiento from pensiones where id_anio=$A27 and id_nivel_academico=2 and mes in ('ENERO','FEBRERO') order by fecha_vencimiento" | tr '\n' ' ' | sed 's/ $//')"
check "día 31 en febrero = último día (2028 bisiesto)" "2028-02-29" "$(q "select FN_FECHA_EN_ANIO_ESCOLAR($A27, 2, 31)")"
check "el docente no genera pensiones" "403" "$(code JOSE -d "anio=$A27&nivel=1&desde=3&hasta=4&dia=0&precio=1" $P/controlador_generar_pensiones.php)"

echo "== Cobro y morosidad solo con las pensiones del año de la matrícula"
check "el selector de la matrícula 40 (año 5) no muestra las de 2027" "10" "$(post CONTA1 -d "id_matri=40" $B/controller/pago_pension/controlador_cargar_select_pension.php | php -r 'echo count(json_decode(stream_get_contents(STDIN),true));')"
check "la vista de estado sigue con 150 filas" "150" "$(q "select count(*) from vw_estado_pensiones")"
P27=$(q "select id_pensiones from pensiones where id_anio=$A27 and id_nivel_academico=1 and mes='MARZO'")
EF=$(q "select id_metodo from metodos_pago where nombre='EFECTIVO'")
r=$(post CONTA1 --form-string "id_matri=40" --form-string "lineas=[{\"concepto\":\"PENSION\",\"id_pension\":\"$P27\",\"monto\":\"120\"}]" --form-string "metodos=[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"120\"}]" $B/controller/pago_pension/controlador_detalle_pago_pension.php)
check "cobrar a una matrícula de 2026 la pensión de 2027 -> rechazado" "false" "$(echo "$r" | jq_ ok)"
check "la vista coincide con la pantalla de cobro" "0" "$(php "$R/tests/comparar_morosidad.php" colegio2_test | cut -d' ' -f2)"

echo "== Modificar, eliminar y ajustar vencimientos"
mod() { post CONTA1 -d "$1" $P/controlador_modificar_pension.php; }
check "con pagos no cambia de nivel -> 4" "4" "$(mod "id=37&anio=5&nivel=2&mes=ABRIL&fecha=2025-04-30&precio=100&mora=10")"
check "con pagos sí cambia el precio (con fecha válida) -> 1" "1" "$(mod "id=37&anio=5&nivel=1&mes=ABRIL&fecha=2026-04-30&precio=105&mora=10")"
check "eliminar con pagos -> 2 / sin pagos -> 1" "2 1" "$(post CONTA1 -d "id=37" $P/controlador_eliminar_pensiones.php) $(post CONTA1 -d "id=$P27" $P/controlador_eliminar_pensiones.php)"
r=$(post CONTA1 -d "anio=5&aplicar=0" $P/controlador_ajustar_vencimientos.php)
check "vista previa: 26 a ajustar, sin cambios todavía" "26 0" "$(echo "$r" | jq_ data) $(q "select count(*) from pensiones_ajustes")"
r=$(post CONTA1 -d "anio=5&aplicar=1" $P/controlador_ajustar_vencimientos.php)
check "aplicar: 26 ajustadas con historial y ya no quedan" "26 26 0" "$(echo "$r" | jq_ data) $(q "select count(*) from pensiones_ajustes") $(post CONTA1 -d "anio=5&aplicar=0" $P/controlador_ajustar_vencimientos.php | jq_ data)"
check "la vista sigue coincidiendo con la pantalla de cobro" "0" "$(php "$R/tests/comparar_morosidad.php" colegio2_test | cut -d' ' -f2)"

echo "== Usuarios únicos al registrar"
doc() { post JERSSON123 -F "dni=$1" -F "nombre=PRUEBA" -F "apelli=DOCENTE" -F "espe=1" -F "sexo=MASCULINO" -F "fechanaci=1990-01-01" -F "telf=987654321" -F "telfal=" -F "direc=x" -F "nombrefoto=" -F "usu=$2" -F "contra=Prueba123" -F "email=d@x.com" $B/controller/docentes/controlador_registrar_docente.php; }
check "docente con usuario ya usado -> 3" "3" "$(doc 71717171 JOSE)"
check "docente con usuario nuevo -> 1, unido a SU usuario" "1 DOCPRUEBA8" "$(doc 71717171 DOCPRUEBA8) $(q "select u.usu_usuario from docentes d join usuario u on u.usu_id=d.id_asusuario where d.docente_dni='71717171'")"
check "renombrar un usuario al nombre de otro -> 2" "2" "$(post JERSSON123 -d "id=63&usu=JOSE&rol=1&correo=x@x.com" $B/controller/usuario/controlador_modificar_usuario.php)"

echo "== Categorías con grupo"
G52=$(q "select id_indicadores from indicadores where codigo='5.2'"); G53=$(q "select id_indicadores from indicadores where codigo='5.3'"); G42=$(q "select id_indicadores from indicadores where codigo='4.2'")
LUZ=$(q "select id_indicadores from indicadores where nombre='PAGO DE LA LUZ'")
cat_() { post CONTA1 -d "$1" $I/controlador_registro_indicadores.php; }
check "grupo de otro tipo -> 3" "3" "$(cat_ "tipo=GASTOS&nombre=INTERNET&descrip=&padre=$G42")"
check "nombre con etiquetas HTML -> 400" "400" "$(code CONTA1 -d "tipo=GASTOS&nombre=<b>x</b>&descrip=&padre=$G52" $I/controlador_registro_indicadores.php)"
check "INTERNET Y TELÉFONO en servicios básicos -> 1, código 5.2.3" "1 5.2.3" "$(cat_ "tipo=GASTOS&nombre=internet y tel%C3%A9fono&descrip=&padre=$G52") $(q "select codigo from indicadores where nombre like 'INTERNET%'")"
mod_() { post CONTA1 -d "$1" $I/controlador_modificar_indicador.php; }
check "editar la luz (antes fallaba la validación) -> 1" "1" "$(mod_ "id=$LUZ&tipo=GASTOS&nombre=PAGO DE LA LUZ&descrip=empresa&esta=ACTIVO&padre=$G52")"
check "pasar la luz (con egresos) a INGRESOS -> 4" "4" "$(mod_ "id=$LUZ&tipo=INGRESOS&nombre=PAGO DE LA LUZ&descrip=&esta=ACTIVO&padre=$G42")"
check "mover la luz a 5.3 -> código 5.3.1" "1 5.3.1" "$(mod_ "id=$LUZ&tipo=GASTOS&nombre=PAGO DE LA LUZ&descrip=&esta=ACTIVO&padre=$G53") $(q "select codigo from indicadores where id_indicadores=$LUZ")"
check "eliminar: con egresos 2, grupo 3, del sistema 3" "2 3 3" "$(post CONTA1 -d "id=$LUZ" $I/controlador_eliminar_indicador.php) $(post CONTA1 -d "id=$G53" $I/controlador_eliminar_indicador.php) $(post CONTA1 -d "id=1" $I/controlador_eliminar_indicador.php)"
terminar
