#!/usr/bin/env bash
# Fase 8D: limpieza, permisos, becas, vacantes, choques de horario, salud, anti-spam y auditoría.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
H=$B/controller/horarios
crear_contador
EF=$(q "select id_metodo from metodos_pago where nombre='EFECTIVO'")
login JERSSON123; login CONTA1; login DANIEL20; login MANUEL123
ENF=$(q "select u.usu_usuario from usuario u join roles r on r.Id_rol=u.rol_id where r.tipo_rol='ENFERMERA' limit 1")
PSI=$(q "select u.usu_usuario from usuario u join roles r on r.Id_rol=u.rol_id where r.tipo_rol='PSICOLOGA' limit 1")
# Solo algunos usuarios tienen la clave de prueba: se la pone a la enfermera y a la psicóloga
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('$ENF','$PSI')"
login $ENF; login $PSI

echo "== Módulos de otro proyecto retirados"
for c in controller/empleado/controlador_listar_empleado.php controller/tipo_documento/controlador_listar_tipo.php controller/area/controlador_listar_area.php view/MPDF/REPORTE/ticket_tramite.php; do
  check "$c ya no existe" "404" "$(code JERSSON123 -X POST $B/$c)"
done

echo "== Permisos corregidos"
check "el estudiante NO registra horarios" "403" "$(code DANIEL20 -d 'componentes=[]' $H/controlador_registro_horario_aula.php)"
check "el estudiante NO modifica horarios" "403" "$(code DANIEL20 -d 'componentes=[]' $H/controlador_modificar_horarios.php)"
check "la enfermera NO registra ni modifica atenciones psicológicas" "403 403" "$(code $ENF -d 'estu=1' $B/controller/atencion_psicologica/controlador_registro_psicologia.php) $(code $ENF -d 'id=1' $B/controller/atencion_psicologica/controlador_modificar_atencion_psico.php)"
check "la psicóloga NO registra ni modifica atenciones de enfermería" "403 403" "$(code $PSI -d 'estu=1' $B/controller/atencion_enfermeria/controlador_registro_enfermeria.php) $(code $PSI -d 'id=1' $B/controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php)"
AE=$(q "select id_atencion from atencion_salud where tipo_atencion='ENFERMERIA' limit 1")
check "el SP psicológico no modifica una atención de enfermería" "0 ENFERMERIA" "$(q "call SP_MODIFICAR_ATENCION_PSICOLOGICA($AE, 40, 'X', 'X', 'X', 9)") $(q "select tipo_atencion from atencion_salud where id_atencion=$AE and motivo_consulta<>'X'")"

echo "== Becas y descuentos"
# Matrícula 40 (inicial, 100 por mes): una pensión del año sin pagos
PB=$(q "select v.id_pension from vw_estado_pensiones v where v.id_matricula=40 and v.pagado_pension=0 order by v.fecha_vencimiento limit 1")
precio() { q "call SP_SALDO_PENSION(40, $PB)" | cut -f4; }
beca() { post JERSSON123 -d "id=40&$1" $B/controller/matricula/controlador_beca_guardar.php; }
check "sin beca: precio 100" "100" "$(precio)"
check "beca 50% -> precio 50" "1 50" "$(beca "tipo=PORCENTAJE&valor=50&desde=&motivo=rendimiento" | jq_ codigo) $(precio)"
check "la vista de morosidad usa el precio con beca" "50" "$(q "select precio from vw_estado_pensiones where id_matricula=40 and id_pension=$PB")"
check "  y coincide con la pantalla de cobro" "0" "$(php "$R/tests/comparar_morosidad.php" colegio2_test | cut -d' ' -f2)"
check "descuento fijo 30 -> precio 70" "1 70" "$(beca "tipo=MONTO&valor=30&desde=&motivo=hermanos" | jq_ codigo) $(precio)"
check "desde una fecha posterior al vencimiento -> precio completo" "1 100" "$(beca "tipo=MONTO&valor=30&desde=2099-01-01&motivo=hermanos" | jq_ codigo) $(precio)"
check "porcentaje mayor a 100 -> rechazado" "0" "$(beca "tipo=PORCENTAJE&valor=150&desde=&motivo=x" | jq_ codigo)"
check "sin motivo -> rechazado" "0" "$(beca "tipo=PORCENTAJE&valor=10&desde=&motivo=" | jq_ codigo)"
beca "tipo=PORCENTAJE&valor=50&desde=&motivo=rendimiento" > /dev/null
SALDO=$(q "call SP_SALDO_PENSION(40, $PB)" | cut -f12)
r=$(post JERSSON123 --form-string "id_matri=40" --form-string "lineas=[{\"concepto\":\"PENSION\",\"id_pension\":\"$PB\",\"monto\":\"$SALDO\"}]" --form-string "metodos=[{\"id_metodo\":\"$EF\",\"id_cuenta\":\"1\",\"monto\":\"$SALDO\"}]" $B/controller/pago_pension/controlador_detalle_pago_pension.php)
check "cobrar el saldo con beca ($SALDO) deja la pensión PAGADA" "true PAGADO" "$(echo "$r" | jq_ ok) $(q "call SP_SALDO_PENSION(40, $PB)" | cut -f13)"
check "quitar la beca" "1" "$(beca "tipo=NINGUNA&valor=0&desde=&motivo=" | jq_ codigo)"

echo "== Vacantes por aula"
check "el aula 5 no tiene límite y muestra sus matriculados" "NULL 5" "$(sqlf "select concat(ifnull(capacidad,'NULL'),' ',(select count(*) from matricula where id_aula=5 and \`id_año\`=5)) from aulas where Id_aula=5")"
mod_aula() { post JERSSON123 -d "id=5&grado=3 AÑOS&seccion=7&nivel=1&descrip=SALON&estatus=ACTIVO&capacidad=$1" $B/controller/aulas/controlador_modificar_aula.php; }
check "capacidad menor que los matriculados -> 3" "3" "$(mod_aula 3)"
check "capacidad 5 (aula llena)" "1" "$(mod_aula 5)"
$M -e "insert into alumnos(alum_dni,alum_nombre,alum_apepat,alum_apemat,alum_sexo,alum_fechanacimiento,alum_estatus,tipo_alum,alum_fotoperfil,created_at) values('77777779','PRUEBA','VACANTE','LLENA','FEMENINO','2020-01-01','NO','NUEVO','controller/alumnos/fotos/',now())"
ALU=$(q "select Id_alumno from alumnos where alum_dni='77777779'")
mat() { post JERSSON123 -d "estu=$ALU&a%C3%B1o=5&aula=$1&admi=0&nuevo=0&matri=0&proce=x&pro=x&depa=x&usu=VACANTE$1&contra=Prueba123&correo=x@x.com&metodo=$EF&cuenta=1" $B/controller/matricula/controlador_registro_matriculas.php; }
check "matricular en el aula llena -> 6" "6" "$(mat 5)"
check "matricular en otra aula -> 1" "1" "$(mat 6)"
check "capacidad vacía = sin límite" "1 NULL" "$(mod_aula '') $(q "select ifnull(capacidad,'NULL') from aulas where Id_aula=5")"

echo "== Horarios sin cruces"
hor() { post MANUEL123 --data-urlencode "componentes=$1" $H/controlador_registro_horario_aula.php; }
check "otro curso en un bloque ocupado del aula -> cruce" "3" "$(hor '[{"idhora":"41","idasig":"24","dia":"LUNES"}]' | jq_ codigo)"
r=$(hor '[{"idhora":"45","idasig":"22","dia":"LUNES"}]')
check "el docente ya dicta a esa hora en otra aula -> cruce explicado" "3 1" "$(echo "$r" | jq_ codigo) $(echo "$r" | grep -c 'ya dicta')"
check "repetir un bloque existente -> 2" "2" "$(hor '[{"idhora":"41","idasig":"20","dia":"LUNES"}]' | jq_ codigo)"
ANTES=$(q "select count(*) from horarios")
check "lote con un bloque libre y uno que cruza -> no guarda ninguno" "3 $ANTES" "$(hor '[{"idhora":"45","idasig":"24","dia":"VIERNES"},{"idhora":"41","idasig":"24","dia":"LUNES"}]' | jq_ codigo) $(q "select count(*) from horarios")"
check "dos filas del mismo envío en el mismo bloque -> cruce" "3" "$(hor '[{"idhora":"46","idasig":"24","dia":"VIERNES"},{"idhora":"46","idasig":"44","dia":"VIERNES"}]' | jq_ codigo)"
check "bloque libre -> registrado" "1" "$(hor '[{"idhora":"45","idasig":"24","dia":"VIERNES"}]' | jq_ codigo)"
check "día inválido -> 400" "400" "$(code MANUEL123 --data-urlencode 'componentes=[{"idhora":"45","idasig":"24","dia":"DOMINGO"}]' $H/controlador_registro_horario_aula.php)"

echo "== Formulario público anti-spam"
sol() { curl -s -H 'Content-Type: application/json' -d "$1" $B/controller/controlador_solicitudes.php; }
N0=$(q "select count(*) from solicitudes_informacion")
check "campo trampa lleno -> respuesta normal pero no se guarda" "success $N0" "$(sol '{"nombre":"Robot","email":"r1@x.com","telefono":"987654321","nivel":"primaria","mensaje":"hola hola hola","sitio_web":"http://spam","abierto_ms":9000}' | jq_ status) $(q "select count(*) from solicitudes_informacion")"
check "enviado al instante -> no se guarda" "$N0" "$(sol '{"nombre":"Robot","email":"r2@x.com","telefono":"987654321","nivel":"primaria","mensaje":"hola hola hola","abierto_ms":200}' > /dev/null; q "select count(*) from solicitudes_informacion")"
for i in 1 2 3; do sol "{\"nombre\":\"Persona $i\",\"email\":\"p$i@x.com\",\"telefono\":\"987654321\",\"nivel\":\"primaria\",\"mensaje\":\"Quisiera informes de matricula\",\"abierto_ms\":9000}" > /dev/null; done
check "3 solicitudes reales guardadas" "$((N0+3))" "$(q "select count(*) from solicitudes_informacion")"
check "la cuarta desde la misma IP en la hora -> rechazada" "warning $((N0+3))" "$(sol '{"nombre":"Persona 4","email":"p4@x.com","telefono":"987654321","nivel":"primaria","mensaje":"Quisiera informes de matricula","abierto_ms":9000}' | jq_ status) $(q "select count(*) from solicitudes_informacion")"
check "mensaje de más de 1000 caracteres -> error" "error" "$(sol "{\"nombre\":\"Persona 5\",\"email\":\"p5@x.com\",\"telefono\":\"987654321\",\"nivel\":\"primaria\",\"mensaje\":\"$(printf 'a%.0s' $(seq 1 1001))\",\"abierto_ms\":9000}" | jq_ status)"

echo "== Auditoría"
check "la beca quedó registrada con el usuario que la cambió" "9" "$(q "select usuario_id from auditoria where tabla='matricula' and registro_id='40' and accion='UPDATE' and despues like '%PORCENTAJE%' order by id_auditoria limit 1")"
check "el recibo del cobro queda con el usuario que cobró (administrador)" "9" "$(q "select usuario_id from auditoria where tabla='pagos' and accion='INSERT' order by id_auditoria desc limit 1")"
LUZ=$(q "select id_indicadores from indicadores where nombre='PAGO DE LA LUZ'")
post CONTA1 -d "indi=$LUZ&cantidad=1&monto=12&fecha=$(date +%Y-%m-%d)&metodo=$EF&cuenta=1" $B/controller/egresos/controlador_registrar_egreso.php > /dev/null
check "un egreso de la contadora queda con su usuario e IP" "$(q "select usu_id from usuario where usu_usuario='CONTA1'") 1" "$(q "select concat(usuario_id,' ',ip is not null) from auditoria where tabla='egresos' and accion='INSERT' order by id_auditoria desc limit 1")"
$M -e "update pensiones set precio=precio where id_pensiones=$PB"
check "un UPDATE sin cambios reales no se registra" "0" "$(q "select count(*) from auditoria where tabla='pensiones' and registro_id='$PB'")"
r=$(post JERSSON123 -d "tabla=matricula&desde=2020-01-01&hasta=$(date +%Y-%m-%d)&registro=40" $B/controller/configuracion/controlador_auditoria_listar.php)
check "la pantalla lista los cambios campo por campo" "1" "$(echo "$r" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["data"]; foreach($d as $a) foreach($a["cambios"] as $c) if($c["campo"]=="beca_tipo"){ echo 1; exit; } echo 0;')"
check "la contadora NO ve la auditoría" "403" "$(code CONTA1 -d "desde=2020-01-01&hasta=2026-12-31" $B/controller/configuracion/controlador_auditoria_listar.php)"
post JERSSON123 -d "id=22&con=Otra12345" $B/controller/usuario/controlador_modificar_usuario_contra.php > /dev/null
check "un cambio de contraseña no muestra la contraseña" "1" "$(post JERSSON123 -d "tabla=usuario&desde=2020-01-01&hasta=$(date +%Y-%m-%d)&registro=22" $B/controller/configuracion/controlador_auditoria_listar.php | grep -c 'cambiada')"
terminar
