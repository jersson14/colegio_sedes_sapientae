#!/usr/bin/env bash
# Funciones comunes de las pruebas HTTP (Fase 8C): se usan con "source tests/lib.sh".
# Requisitos: XAMPP (Apache + MariaDB en el puerto 3307), config/config.local.php apuntando a colegio2_test
# y la base de prueba recién reiniciada con tests/reset_test_db.sh.
R=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
B=http://localhost/_public_html
mkdir -p "$R/tests/tmp"
T=$(cygpath -m "$(mktemp -d -p "$R/tests/tmp")")
M="/c/xampp/mysql/bin/mysql.exe --default-character-set=utf8mb4 -uroot -P3307 -h127.0.0.1 colegio2_test -N -B"
ok=0; fail=0
php() { /c/xampp/php/php.exe "$@"; }
# Consulta a la BD de prueba (sin el \r que agrega mysql.exe en Windows)
q() { $M -e "$1" | tr -d '\r'; }
# Consultas con ñ u otros acentos: por archivo (los argumentos de mysql.exe no llevan UTF-8)
sqlf() { printf '%s' "$1" > "$T/q.sql"; $M < "$T/q.sql" | tr -d '\r'; }
# Los números se comparan con 2 decimales (420 = 420.00); el texto, tal cual
norm() { php -r 'echo implode(" ", array_map(function($t){ return is_numeric($t) ? number_format((float)$t, 2, ".", "") : $t; }, explode(" ", $argv[1])));' -- "$1"; }
check() { if [ "$(norm "$2")" == "$(norm "$3")" ]; then ok=$((ok+1)); echo "  OK   $1"; else fail=$((fail+1)); echo "  FALLA $1: esperado [$2], obtenido [$3]"; fi; }
# Valor de un JSON por ruta con puntos (data.0.total); listas devuelven su tamaño
jq_() { php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach(explode(".",$argv[1]) as $x){ $d=is_array($d)&&array_key_exists($x,$d)?$d[$x]:null; } echo is_bool($d)?($d?"true":"false"):(is_array($d)?count($d):(is_float($d)?number_format($d,2,".",""):$d));' "$1"; }
# Suma un campo de una lista del JSON: jqsum <ruta.lista> <campo>
jqsum() { php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach(explode(".",$argv[1]) as $x){ $d=$d[$x]; } echo number_format(array_sum(array_column($d,$argv[2])),2,".","");' "$1" "$2"; }
login() { local jar=$T/$1.jar; curl -s -c $jar -b $jar -o /dev/null $B/index.php; curl -s -c $jar -b $jar -o /dev/null -d "u=$1&c=${2:-Prueba123}" $B/controller/usuario/controlador_iniciar_sesion.php;
          curl -s -c $jar -b $jar $B/view/index.php > $T/$1.html; grep -o 'csrf-token" content="[a-f0-9]*' $T/$1.html | cut -d'"' -f3 > $T/$1.tok; }
post() { local u=$1; shift; curl -s -b $T/$u.jar -H "X-CSRF-Token: $(cat $T/$u.tok)" "$@"; }
code() { local u=$1; shift; curl -s -o $T/out -w "%{http_code}" -b $T/$u.jar -H "X-CSRF-Token: $(cat $T/$u.tok)" "$@"; }
dos() { php -r 'echo number_format((float)$argv[1],2,".","");' "$1"; }
# Usuario CONTA1 (rol CONTADOR, personal administrativo) y caja con saldo inicial 10 000
crear_contador() {
  local H=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
  $M -e "insert into usuario(usu_usuario,usu_contra,usu_email,usu_estatus,rol_id,empresa_id,created_at) select 'CONTA1','$H','c@x.com','ACTIVO',Id_rol,1,now() from roles where tipo_rol='CONTADOR';
         insert into personal_admi(personal_adm_dni,personal_adm_nombre,personal_adm_apellido,personal_adm_tipo,personal_adm_estatus,id_ausuario,created_at) select '44444444','ANA','CONTADORA','CONTADOR','ACTIVO',usu_id,now() from usuario where usu_usuario='CONTA1';
         update cuentas_financieras set saldo_inicial=10000, fecha_saldo_inicial='2000-01-01' where id_cuenta=1;"
}
terminar() { echo; echo "RESULTADO: $ok OK, $fail fallas"; rm -rf "$T"; [ "$fail" -eq 0 ]; }
