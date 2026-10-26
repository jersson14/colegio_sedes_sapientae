#!/usr/bin/env bash
# Fase 13 paso 8: matrícula paso a paso y reutilizar los datos de los padres. Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
MA=$B/controller/matricula
V=$(dirname "$0")/..
crear_contador
login JERSSON123; login CONTA1
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
ANT=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado<>'ACTIVO' order by fecha_inicio limit 1")
DANIEL=$(q "select Id_alumno from alumnos where alum_dni='72451251'")
ctx() { post JERSSON123 -d "$1" $MA/controlador_contexto.php; }
lista() { php -r '$d=json_decode(stream_get_contents(STDIN),true); echo implode(" ",array_column($d["estudiantes"],"id"));'; }
# Hermana nueva de una familia ya registrada (Yessenia Camacho): la madre quedó sin celular en este registro
sqlf "insert into alumnos(alum_dni,alum_nombre,alum_apepat,alum_apemat,alum_sexo,alum_fechanacimiento,alum_movil,alum_direccion,alum_estatus,tipo_alum,alum_fotoperfil,created_at) values('70000001','LUCIA','CAMACHO','PERALTA','FEMENINO','2018-05-10','','AV. CANADA 121','NO','NUEVO','controller/alumnos/fotos/',now()); insert into padres(id_alu,Dni_papa,Datos_papa,Celular_papa,Dni_mama,Datos_mama,Celular_mama,created_at) select max(Id_alumno),'66262162','CARLOS CAMACHO PERALES','995259229','84844848','ANDREA PERALTA CHAVEZ','',now() from alumnos;" > /dev/null
LUCIA=$(q "select Id_alumno from alumnos where alum_dni='70000001'")

echo "== Contexto del asistente"
check "sin año: usa el año escolar activo" "$ANIO" "$(ctx anio=0 | jq_ anio)"
check "lista solo a quien no está matriculado en el año (la estudiante nueva sí, Daniel no)" "1 0" "$(ctx "anio=$ANIO" | lista | tr ' ' '\n' | grep -c "^$LUCIA$") $(ctx "anio=$ANIO" | lista | tr ' ' '\n' | grep -c "^$DANIEL$")"
check "un estudiante antiguo aparece para matricularse en otro año" "1" "$(ctx "anio=$ANT" | lista | tr ' ' '\n' | grep -c "^$DANIEL$")"
check "estudiante nuevo: sin usuario, sin matrícula previa, con sus padres" "NUEVO - - CARLOS CAMACHO PERALES" "$(ctx "anio=$ANIO&estu=$LUCIA" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["estudiante"]; echo $d["tipo"]," ",$d["usuario"]?:"-"," ",$d["ultima"]?:"-"," ",$d["padres"]["padre"];')"
check "estudiante ya matriculado este año: lo indica (con su aula)" "DANIEL20 1" "$(ctx "anio=$ANIO&estu=$DANIEL" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["estudiante"]; echo $d["usuario"]," ",(int)!empty($d["en_anio"]["aula"]);')"
check "aulas con vacantes y montos sugeridos por nivel" "1 1" "$(ctx "anio=$ANIO" | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo (int)(count($d["aulas"])>0 && array_key_exists("vacantes",$d["aulas"][0]))," ",(int)isset($d["montos"]["1"]["matricula"]);')"
check "estudiante inexistente -> 404; contadora -> 403" "404 403" "$(code JERSSON123 -d "anio=$ANIO&estu=999999" $MA/controlador_contexto.php) $(code CONTA1 -d "anio=$ANIO" $MA/controlador_contexto.php)"

echo "== Matricular (mismo controlador de siempre)"
AULA=$(q "select id_aula from matricula where id_alumno=$DANIEL order by id_matricula desc limit 1")
r=$(post JERSSON123 -d "a%C3%B1o=$ANT&estu=$DANIEL&aula=$AULA&admi=0&nuevo=0&matri=0&proce=&pro=&depa=&usu=&contra=&correo=&metodo=&cuenta=&referencia=" $MA/controlador_registro_matriculas.php)
check "el antiguo se matricula en otro año y conserva su usuario" "1 1" "$r $(q "select count(distinct usu_id) from matricula where id_alumno=$DANIEL")"

echo "== Padres ya registrados"
P=$B/controller/alumnos/controlador_buscar_padre.php
check "documento del padre: lo encuentra con sus hijos y ofrece a la madre" "PADRE CARLOS CAMACHO PERALES 2 ANDREA PERALTA CHAVEZ" "$(post JERSSON123 -d dni=66262162 $P | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["rol"]," ",$d["nombre"]," ",count($d["hijos"])," ",$d["otro"]["nombre"];')"
check "el celular de la madre sale del hermano que lo tiene" "926626262" "$(post JERSSON123 -d dni=66262162 $P | jq_ otro.celular)"
check "documento de la madre: rol MADRE" "MADRE" "$(post JERSSON123 -d dni=84844848 $P | jq_ rol)"
check "al editar a un estudiante no se sugiere a sí mismo" "1" "$(post JERSSON123 -d "dni=66262162&alumno=$LUCIA" $P | jq_ hijos)"
check "documento desconocido o inválido: nada" "false false" "$(post JERSSON123 -d dni=00000000 $P | jq_ encontrado) $(post JERSSON123 -d "dni=1' or 1=1" $P | jq_ encontrado)"
check "la contadora no consulta padres" "403" "$(code CONTA1 -d dni=66262162 $P)"

echo "== Pantalla"
check "la ventana tiene 5 pasos y carga el asistente" "5 1" "$(grep -c '<li data-paso=' $V/view/matricula/view_matricula.php) $(grep -c 'src="../js/matricula_pasos.js' $V/view/matricula/view_matricula.php)"
check "ya no queda el formulario anterior" "0 0" "$(grep -c 'Validar_Informacion' $V/view/matricula/view_matricula.php) $(grep -c 'function Registrar_matriculado' $V/js/console_matriculas.js)"
check "Enter solo pulsa un botón visible" "1" "$(grep -c "filter(':visible').filter(function () { return esBotonGuardar" $V/js/formulario.js)"
terminar
