#!/usr/bin/env bash
# Fase 13 paso 14 (a): importar estudiantes desde Excel. Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
I=$B/controller/alumnos/controlador_importar.php
V=$(cd "$(dirname "$0")/.." && pwd)
crear_contador
login JERSSON123; login CONTA1
subir() { curl -s -b $T/${3:-JERSSON123}.jar -H "X-CSRF-Token: $(cat $T/${3:-JERSSON123}.tok)" -F "modo=$2" -F "archivo=@$1" $I; }
codigo_subir() { curl -s -o /dev/null -w '%{http_code}' -b $T/${3:-JERSSON123}.jar -H "X-CSRF-Token: $(cat $T/${3:-JERSSON123}.tok)" -F "modo=$2" -F "archivo=@$1" $I; }
# Excel de prueba: 2 filas correctas y 6 con errores distintos (y una fila vacía que se ignora)
(cd "$V" && php -r '
require "utilitario/hoja_calculo.php";
$h=["DNI del estudiante","Nombres","Apellido paterno","Apellido materno","Sexo (F o M)","Fecha de nacimiento (dd/mm/aaaa)","Teléfono del estudiante","Dirección","DNI del padre","Nombres y apellidos del padre","Celular del padre","DNI de la madre","Nombres y apellidos de la madre","Celular de la madre"];
$f=[$h,
 ["71234567","Lucía","Peña","Quispe","F","15/03/2018","","Av. Sol 1","","","","40404040","Rosa Quispe","987654321"],
 ["71234568","Mateo","Rojas","Díaz","masculino","2017-05-02","","","41414141","Juan Rojas","912345678","42424242","Ana Díaz","912000111"],
 ["123","Malo","Doc","","M","01/01/2015","","","","","","43434343","X","999888777"],
 ["71234567","Repetido","Enel","Archivo","F","01/01/2015","","","","","","44444444","Y","999888777"],
 ["66655656","Ya","Existe","","F","01/01/2015","","","","","","45454545","Z","999888777"],
 ["71234569","Fecha","Mala","","F","31/02/2015","","","","","","46464646","W","999888777"],
 ["71234570","Sexo","Raro","","X","01/01/2015","","","","","","47474747","V","999888777"],
 ["71234571","Sin","Madre","","M","01/01/2015","","","","","","","",""],
 ["","","","","","","","","","","","","",""]];
file_put_contents($argv[1], hoja_crear_xlsx([["nombre"=>"Estudiantes","filas"=>$f]]));' "$(cygpath -w $T/i.xlsx)")

echo "== Plantilla"
check "se descarga como Excel con los 14 encabezados" "200 14" "$(curl -s -b $T/JERSSON123.jar -o $T/p.xlsx -w '%{http_code}' "$I?plantilla=1") $(cd "$V" && php -r 'require "utilitario/hoja_calculo.php"; echo count(hoja_leer($argv[1],"p.xlsx")[0]);' "$(cygpath -w $T/p.xlsx)")"

echo "== Revisar (no graba nada)"
R=$(subir $T/i.xlsx revisar)
err() { echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["filas"] as $r) if($r["fila"]==$argv[1]) echo implode("; ",$r["errores"]) ?: "OK";' "$1"; }
check "2 filas listas y 6 con errores (la vacía se ignora)" "2 6" "$(echo "$R" | jq_ listas) $(echo "$R" | jq_ con_error)"
check "documento inválido" "DNI del estudiante no válido" "$(err 4)"
check "documento repetido dentro del archivo" "Documento repetido en el archivo (fila 2)" "$(err 5)"
check "documento que ya existe en el sistema" "Ya existe un estudiante con ese documento" "$(err 6)"
check "fecha inexistente" "Fecha de nacimiento no válida (use dd/mm/aaaa)" "$(err 7)"
check "sexo mal escrito" "Sexo: use F o M" "$(err 8)"
check "faltan los datos de la madre" "3" "$(err 9 | grep -o 'Falta' | wc -l)"
check "normaliza: masculino -> MASCULINO, aaaa-mm-dd y mayúsculas" "MASCULINO 2017-05-02 MATEO" "$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["filas"][1]["datos"]; echo $d["sexo"]," ",$d["fechanaci"]," ",$d["nombre"];')"
check "revisar no grabó nada" "0" "$(q "select count(*) from alumnos where alum_dni in ('71234567','71234568')")"

echo "== Importar"
G=$(subir $T/i.xlsx grabar)
check "graba solo las 2 filas correctas" "2 6" "$(echo "$G" | jq_ grabadas) $(echo "$G" | jq_ con_error)"
check "estudiantes con sus padres, como en el registro manual" "LUCÍA|FEMENINO|2018-03-15|NUEVO|NO|ROSA QUISPE" "$(q "select concat_ws('|',a.alum_nombre,a.alum_sexo,a.alum_fechanacimiento,a.tipo_alum,a.alum_estatus,p.Datos_mama) from alumnos a join padres p on p.id_alu=a.Id_alumno where a.alum_dni='71234567'")"
check "importar otra vez el mismo archivo no duplica" "0 1" "$(subir $T/i.xlsx grabar | jq_ grabadas) $(q "select count(*) from alumnos where alum_dni='71234567'")"

echo "== Otros formatos y errores"
printf '\xEF\xBB\xBFDNI del estudiante;Nombres;Apellido paterno;Sexo;Fecha de nacimiento;DNI de la madre;Nombres y apellidos de la madre;Celular de la madre\r\n71234580;Ana;Pe\xc3\xb1a;F;01/02/2016;48484848;Rosa;987654321\r\n' > $T/a.csv
check "CSV con punto y coma (Excel en español)" "1 ANA PEÑA" "$(subir $T/a.csv revisar | jq_ listas) $(subir $T/a.csv revisar | php -r '$d=json_decode(stream_get_contents(STDIN),true)["filas"][0]["datos"]; echo $d["nombre"]," ",$d["apepa"];')"
printf 'Nombres;Apellido paterno\r\nAna;Peña\r\n' > $T/b.csv
check "faltan columnas obligatorias: lo dice" "1" "$(subir $T/b.csv revisar | grep -c 'faltan_columnas":\["DNI del estudiante')"
printf 'hola' > $T/c.pdf
check "archivo que no es Excel -> 400; contadora -> 403" "400 403" "$(codigo_subir $T/c.pdf revisar) $(codigo_subir $T/i.xlsx revisar CONTA1)"
check "botón en Estudiantes" "1" "$(curl -s -b $T/JERSSON123.jar $B/view/alumnos/view_alumnos.php | grep -c 'Importar_Abrir()')"
terminar
