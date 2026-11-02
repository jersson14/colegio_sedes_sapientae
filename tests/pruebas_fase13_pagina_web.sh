#!/usr/bin/env bash
# Página pública administrable (Configuración → Página web): landing.php muestra el contenido guardado, con los colores
# y el escudo de Apariencia; solo el administrador la edita; las fotos se suben a img/web y se validan.
# Requiere la BD de prueba recién reiniciada (migración 025) y 'modo_demo' => false.
source "$(dirname "$0")/lib.sh"
PW=$B/controller/configuracion/controlador_pagina_web.php
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE')"
login JERSSON123; login JOSE
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
# Datos actuales con cambios: $1 = expresión PHP que modifica $d
con_cambios() { post JERSSON123 -d accion=cargar $PW | php -r '$d=json_decode(stream_get_contents(STDIN),true)["datos"]; '"$1"' echo json_encode($d);'; }

echo "== Página de inicio"
L=$(curl -s $B/)
check "la raíz del sitio abre la página pública (landing.php) con el contenido de fábrica" "1 1" "$(echo "$L" | grep -c '<title>Colegio Sedes Sapientiae') $(echo "$L" | grep -c 'href="index.php" class="btn-ingresar"')"
check "usa los colores de Apariencia y el formulario de contacto sigue conectado" "1 1" "$(echo "$L" | grep -c 'id="tema-variables"') $(grep -c "controller/controlador_solicitudes.php" $R/landing.js)"

echo "== Editar"
check "solo el administrador la edita (el docente no)" "403" "$(code JOSE -d accion=cargar $PW)"
D=$(con_cambios '$d["hero_titulo"]="Colegio San Prueba <script>x</script>"; $d["whatsapp"]="+51 987 654 321"; $d["facebook"]="facebook.com/sanprueba"; $d["niveles"][0]["mostrar"]=false; $d["valores"]=[];')
check "guarda los cambios" "true" "$(post JERSSON123 --data-urlencode accion=guardar --data-urlencode "datos=$D" $PW | jq_ ok)"
L=$(curl -s $B/landing.php)
check "la página muestra el título nuevo (pestaña, portada y pie), sin dejar pasar etiquetas" "3 0" "$(echo "$L" | grep -c 'Colegio San Prueba &lt;script&gt;') $(echo "$L" | grep -c '<script>x</script>')"
check "botón de WhatsApp (solo dígitos) y enlace de Facebook con https" "1 1" "$(echo "$L" | grep -c 'href="https://wa.me/51987654321"') $(echo "$L" | grep -c 'href="https://facebook.com/sanprueba"')"
check "un nivel oculto no aparece en la página ni en el formulario; sin valores no hay sección" "0 0 0" "$(echo "$L" | grep -c 'Educación Inicial') $(echo "$L" | grep -c 'value="inicial"') $(echo "$L" | grep -c 'id="valores"')"
check "correo inválido, sin título o sin niveles: no se guarda" "400 400 400" "$(post JERSSON123 --data-urlencode accion=guardar --data-urlencode "datos=$(con_cambios '$d["email"]="no-es-correo";')" -o /dev/null -w '%{http_code}' $PW) $(post JERSSON123 --data-urlencode accion=guardar --data-urlencode "datos=$(con_cambios '$d["hero_titulo"]="";')" -o /dev/null -w '%{http_code}' $PW) $(post JERSSON123 --data-urlencode accion=guardar --data-urlencode "datos=$(con_cambios 'foreach($d["niveles"] as &$n){$n["mostrar"]=false;}')" -o /dev/null -w '%{http_code}' $PW)"

echo "== Fotos"
php -r '$i=imagecreatetruecolor(40,20); imagepng($i,$argv[1]);' "$(cygpath -m $T)/portada.png"
printf '<?php echo 1;' > $T/malo.png
RUTA=$(post JERSSON123 -F accion=imagen -F campo=hero -F "imagen=@$T/portada.png" $PW | J '$d["ruta"]')
check "sube la foto de portada a img/web y la página la usa" "1 1" "$(echo "$RUTA" | grep -c '^img/web/IMG.*\.png$') $(curl -s $B/landing.php | grep -c "url('$RUTA')")"
check "un archivo que no es imagen se rechaza" "400" "$(code JERSSON123 -F accion=imagen -F campo=hero -F "imagen=@$T/malo.png" $PW)"
post JERSSON123 -d accion=quitar_imagen -d campo=hero $PW > /dev/null
check "volver a la foto original borra la subida" "1 no" "$(curl -s $B/landing.php | grep -c "url('img/fondo.jpeg')") $([ -f "$R/$RUTA" ] && echo si || echo no)"
check "la carpeta de fotos no ejecuta código" "1" "$(grep -c 'Require all denied' $R/img/web/.htaccess)"

echo "== Demo"
check "en la demo la pantalla queda bloqueada; en el menú está bajo Apariencia" "1 1" "$(grep -c "'configuracion/view_pagina_web.php'," $R/utilitario/demo.php) $(grep -c "'configuracion/view_pagina_web.php')\" class=\"nav-link\"" $T/JERSSON123.html)"
$M -e "delete from pagina_web"
terminar
