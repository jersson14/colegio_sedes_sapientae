#!/usr/bin/env bash
# Abre, con un usuario de cada rol, todas las pantallas de su menú en Chrome headless y reporta errores de
# JavaScript o avisos de "Acceso denegado" (permiso faltante). Requiere la BD de prueba recién reiniciada.
R=$(cd "$(dirname "$0")/../.." && pwd)
source "$R/tests/lib.sh" > /dev/null
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
usuario_rol() { q "select u.usu_usuario from usuario u join roles r on r.Id_rol=u.rol_id where r.tipo_rol='$1' and u.usu_estatus='ACTIVO' order by u.usu_id limit 1"; }
php "$R/tests/herramientas/revisar_permisos.php" --json | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["vistas"] as $r=>$v) echo $r." ".implode(" ",$v)."\n";' > "$T/vistas.txt"
sid() { local jar=$T/s_$1.jar; curl -s -c $jar -b $jar -o /dev/null $B/index.php; curl -s -c $jar -b $jar -o /dev/null -d "u=$1&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php; tail -1 $jar | awk '{print $7}'; }
puerto=9380
while read -r rol vistas; do
  u=$(usuario_rol "$rol")
  [ "$rol" = "CONTADOR" ] && u=CONTA1
  $M -e "update usuario set usu_contra='$HP' where usu_usuario='$u'"
  puerto=$((puerto+1))
  ( PUERTO=$puerto timeout 900 node "$R/tests/navegador/navegar_todas.js" "$(sid "$u")" "$T" $vistas > "$T/rol_$rol.txt" 2>&1 ) &
done < "$T/vistas.txt"
wait
fallas=0
for f in "$T"/rol_*.txt; do
  rol=$(basename "$f" .txt | sed 's/rol_//')
  echo "== $rol: $(tail -1 "$f")"
  grep -v ": ok$\|TOTAL" "$f" | sed 's/^/   /'
  tail -1 "$f" | grep -q "TOTAL ERRORES: 0" || fallas=$((fallas+1))
done
rm -rf "$T"
echo "Roles con errores: $fallas"
[ "$fallas" -eq 0 ]
