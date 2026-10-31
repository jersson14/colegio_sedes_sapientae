#!/usr/bin/env bash
# Fase 13 paso 14 (b): comunicados con lectura. Requiere la BD de prueba recién reiniciada (migración 020).
source "$(dirname "$0")/lib.sh"
C=$B/controller/comunicados
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
OTRO=$(q "select u.usu_usuario from matricula m join usuario u on u.usu_id=m.usu_id where m.id_aula<>18 and u.usu_estatus='ACTIVO' limit 1")
$M -e "update usuario set usu_contra='$HP' where usu_usuario='$OTRO'"
login JERSSON123; login DANIEL20; login CONTA1; login $OTRO
COM=$(q "select id_comunicado from comunicados where tipo='POR GRADO' and id_aula=18 limit 1")
$M -e "insert into comunicados(tipo,id_aula,titulo,descripcion,imagen,estado,id_usuario,created_at) values ('GENERAL',NULL,'FERIADO','No hay clases el lunes','controller/comunicados/fotos/','ACTIVO',(select usu_id from usuario where usu_usuario='JERSSON123'),curdate()),('GENERAL',NULL,'BORRADOR OCULTO','x','','INACTIVO',(select usu_id from usuario where usu_usuario='JERSSON123'),curdate())"
GEN=$(q "select id_comunicado from comunicados where titulo='FERIADO'")
OCU=$(q "select id_comunicado from comunicados where titulo='BORRADOR OCULTO'")
panel() { post $1 -X POST $B/controller/panel/controlador_panel_rol.php | php -r '$d=json_decode(stream_get_contents(STDIN),true); foreach($d["comunicados"] as $c) if($c["id"]==$argv[1]) echo $c["leido"];' "$2"; }

echo "== El estudiante lee el comunicado"
check "en su panel aparece como no leído" "0" "$(panel DANIEL20 $COM)"
check "al abrirlo recibe el texto completo" "true 1" "$(post DANIEL20 -d id=$COM $C/controlador_ver_comunicado.php | jq_ ok) $(post DANIEL20 -d id=$COM $C/controlador_ver_comunicado.php | grep -c 'descripcion')"
check "queda registrado una sola vez y el panel lo marca leído" "1 1" "$(q "select count(*) from comunicado_lecturas where id_comunicado=$COM") $(panel DANIEL20 $COM)"
check "el estudiante de otra aula no abre un comunicado que no es para él" "404" "$(code $OTRO -d id=$COM $C/controlador_ver_comunicado.php)"
check "los generales los abre cualquiera (estudiante y contadora)" "true true" "$(post $OTRO -d id=$GEN $C/controlador_ver_comunicado.php | jq_ ok) $(post CONTA1 -d id=$GEN $C/controlador_ver_comunicado.php | jq_ ok)"
check "un comunicado oculto solo lo abre la dirección" "404 true" "$(code DANIEL20 -d id=$OCU $C/controlador_ver_comunicado.php) $(post JERSSON123 -d id=$OCU $C/controlador_ver_comunicado.php | jq_ ok)"

echo "== La dirección ve quién lo leyó"
L=$(post JERSSON123 -d id=$COM $C/controlador_lectura_comunicado.php)
TOT=$(sqlf "select count(*) from matricula where id_aula=18 and \`id_año\`=(select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO')")
check "va a las familias del aula: 1 leyó de $TOT" "1 $TOT" "$(echo "$L" | jq_ leidos) $(echo "$L" | jq_ total)"
check "quien leyó, con fecha; los demás primero (pendientes de avisar)" "1 0" "$(echo "$L" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["familias"]; echo (int)($d[count($d)-1]["leido_at"]!==null)," ",(int)($d[0]["leido_at"]!==null);')"
check "trae el celular de la madre o del padre para volver a avisar" "1" "$(echo "$L" | php -r '$d=json_decode(stream_get_contents(STDIN),true)["familias"]; echo (int)(count(array_filter($d,function($f){return $f["cel_madre"]||$f["cel_padre"];}))>0);')"
check "un comunicado general va a todo el año activo" "$(sqlf "select count(*) from matricula where \`id_año\`=(select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO')")" "$(post JERSSON123 -d id=$GEN $C/controlador_lectura_comunicado.php | jq_ total)"
check "la contadora no ve las lecturas; comunicado inexistente -> 404" "403 404" "$(code CONTA1 -d id=$COM $C/controlador_lectura_comunicado.php) $(code JERSSON123 -d id=999999 $C/controlador_lectura_comunicado.php)"

echo "== Pantallas"
check "todos los roles tienen la ventana del comunicado" "1 1 1" "$(grep -c 'id="modal_comunicado_ver"' $T/DANIEL20.html) $(grep -c 'id="modal_comunicado_ver"' $T/CONTA1.html) $(grep -c 'id="modal_comunicado_ver"' $T/JERSSON123.html)"
check "la dirección tiene el botón Lectura (en las dos listas) y su ventana" "2 1" "$(grep -c "class='lectura " $(dirname "$0")/../js/console_comunicados.js) $(curl -s -b $T/JERSSON123.jar $B/view/comunicado/view_comunicado.php | grep -c 'id="modal_lectura"')"
check "WhatsApp con el prefijo del país y el mensaje escrito" "https://wa.me/51987654321?text=Hola" "$(node -e "global.window={REGIONAL:{prefijo_tel:'+51'}};global.REGIONAL=window.REGIONAL;global.textoPlano=s=>s;eval(require('fs').readFileSync('$(cd "$(dirname "$0")/.." && pwd -W)/js/panel_comun.js','utf8').replace('window.PanelComun','global.PanelComun'));console.log(Aviso_WhatsApp_Url('987 654 321','Hola'))")"
terminar
