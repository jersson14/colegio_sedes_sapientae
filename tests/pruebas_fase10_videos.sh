#!/usr/bin/env bash
# Videos en las tareas: subida de videos, enlaces (YouTube/Drive/Vimeo), edición sin perder material,
# reproducción por partes y que cada docente solo maneje las tareas de sus cursos.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
TA=$B/controller/tareas
DOCS="$R/controller/tareas/controller/tareas/documentos"
antes=$(ls "$DOCS")
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','jorge')"
login JERSSON123; login JOSE; login jorge; login DANIEL20
CURSO_JOSE=20; CURSO_jorge=44

# Video mínimo (cabecera MP4) y un archivo que dice ser video pero no lo es
printf '\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom\x00\x00\x00\x08free' > $T/clase.mp4
head -c 300000 /dev/urandom >> $T/clase.mp4
echo "no soy un video" > $T/falso.mp4
echo "guia" > $T/guia.pdf

echo "== Registro con video y enlaces"
ENL=$'https://www.youtube.com/watch?v=dQw4w9WgXcQ\nyoutu.be/dQw4w9WgXcQ\nhttps://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing\nhttps://ejemplo.com/clase'
r=$(post JOSE -F asig=$CURSO_JOSE -F tema=VIDEO1 -F fecha=2030-01-01T10:00 -F descrip=X -F "enlaces=$ENL" -F "archivos[]=@$T/clase.mp4" -F "archivos[]=@$T/guia.pdf" $TA/controlador_registro_tareas.php)
check "el docente registra una tarea con video, PDF y enlaces" "1" "$r"
CAR=$(q "select archivo_tarea from tareas where tema='VIDEO1'")
ID=$(q "select id_tarea from tareas where tema='VIDEO1'")
check "se guardan los archivos en la carpeta de la tarea" "CLASE.MP4 GUIA.PDF" "$(ls "$R/controller/tareas/$CAR" | tr '\n' ' ' | sed 's/ $//')"
check "enlaces sin repetir (el mismo video de YouTube en dos formatos cuenta una vez) y con https" "3" "$(q "select json_length(enlaces_video) from tareas where id_tarea='$ID'")"
J=$(post JOSE "$TA/controlador_archivos_tarea.php?carpeta=$CAR&formato=json")
check "la lista para editar trae archivos y enlaces (video marcado)" "2 3 true" "$(echo "$J" | jq_ archivos) $(echo "$J" | jq_ enlaces) $(echo "$J" | jq_ archivos.0.video)"

echo "== Página del material"
P=$(post JOSE "$TA/controlador_archivos_tarea.php?carpeta=$CAR")
check "reproductor del video subido" "1" "$(echo "$P" | grep -c '<video controls')"
check "YouTube y Drive se ven dentro " "2" "$(echo "$P" | grep -c '<iframe')"
check "YouTube con su identificador" "1" "$(echo "$P" | grep -c 'youtube-nocookie.com/embed/dQw4w9WgXcQ')"
check "Drive como vista previa" "1" "$(echo "$P" | grep -c 'drive.google.com/file/d/1AbCdEfGhIjKlMnOp/preview')"
check "otro sitio queda como enlace" "1" "$(echo "$P" | grep -c 'Abrir video: https://ejemplo.com/clase')"

echo "== Video por partes (para adelantar)"
U="$TA/controlador_archivos_tarea.php?carpeta=$CAR&archivo=CLASE.MP4"
TAM=$(stat -c %s "$T/clase.mp4")
check "pide un tramo: 206 con 100 bytes" "206 100" "$(curl -s -o $T/parte -w '%{http_code}' -b $T/JOSE.jar -H 'Range: bytes=100-199' "$U") $(stat -c %s $T/parte)"
check "el tramo es el correcto" "$(tail -c +101 $T/clase.mp4 | head -c 100 | md5sum | cut -c1-32)" "$(md5sum < $T/parte | cut -c1-32)"
check "completo: 200, tipo video/mp4 y tamaño" "200 video/mp4 $TAM" "$(curl -s -o $T/todo -w '%{http_code} %{content_type}' -b $T/JOSE.jar "$U") $(stat -c %s $T/todo)"
check "tramo fuera del archivo: 416" "416" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/JOSE.jar -H "Range: bytes=$((TAM+10))-" "$U")"

echo "== Validaciones"
check "archivo .mp4 que no es video: rechazado" "400" "$(code JOSE -F asig=$CURSO_JOSE -F tema=FALSO -F fecha=2030-01-01T10:00 -F descrip=X -F "archivos[]=@$T/falso.mp4" $TA/controlador_registro_tareas.php)"
check "enlace inválido: rechazado" "400" "$(code JOSE -F asig=$CURSO_JOSE -F tema=MAL -F fecha=2030-01-01T10:00 -F descrip=X -F 'enlaces=javascript:alert(1)' $TA/controlador_registro_tareas.php)"
check "sin archivos ni enlaces: rechazado" "400" "$(code JOSE -F asig=$CURSO_JOSE -F tema=VACIA -F fecha=2030-01-01T10:00 -F descrip=X $TA/controlador_registro_tareas.php)"
check "solo con enlace: se acepta" "1" "$(post JOSE -F asig=$CURSO_JOSE -F tema=SOLOENLACE -F fecha=2030-01-01T10:00 -F descrip=X -F 'enlaces=https://vimeo.com/76979871' $TA/controlador_registro_tareas.php)"
check "Vimeo se ve dentro" "1" "$(post JOSE "$TA/controlador_archivos_tarea.php?carpeta=$(q "select archivo_tarea from tareas where tema='SOLOENLACE'")" | grep -c 'player.vimeo.com/video/76979871')"
check "ninguna tarea rechazada quedó guardada" "0" "$(q "select count(*) from tareas where tema in ('FALSO','MAL','VACIA')")"

echo "== Edición: se agregan y quitan archivos sin perder el resto"
r=$(post JOSE -F id=$ID -F asig=$CURSO_JOSE -F tema=VIDEO1 -F fecha=2030-01-02T10:00 -F descrip=Y -F 'enlaces=https://youtu.be/dQw4w9WgXcQ' -F 'quitar=["GUIA.PDF"]' -F "archivos[]=@$T/guia.pdf;filename=nueva.pdf" $TA/controlador_modificar_tareas.php)
check "modifica" "1" "$r"
check "misma carpeta, quita GUIA.PDF y suma NUEVA.PDF" "$CAR CLASE.MP4 NUEVA.PDF" "$(q "select archivo_tarea from tareas where id_tarea='$ID'") $(ls "$R/controller/tareas/$CAR" | tr '\n' ' ' | sed 's/ $//')"
check "enlaces reemplazados" "1" "$(q "select json_length(enlaces_video) from tareas where id_tarea='$ID'")"
check "no se puede quitar un archivo fuera de la carpeta" "1 si" "$(post JOSE -F id=$ID -F asig=$CURSO_JOSE -F tema=VIDEO1 -F fecha=2030-01-02T10:00 -F descrip=Y -F 'quitar=["../../../../../../index.php"]' $TA/controlador_modificar_tareas.php) $(test -f "$R/index.php" && echo si)"
check "(index.php sigue en su lugar)" "si" "$(test -f "$R/index.php" && echo si)"
check "quitar todo sin dejar material: rechazado" "400" "$(code JOSE -F id=$ID -F asig=$CURSO_JOSE -F tema=VIDEO1 -F fecha=2030-01-02T10:00 -F descrip=Y -F 'quitar=["CLASE.MP4","NUEVA.PDF"]' $TA/controlador_modificar_tareas.php)"

echo "== Cada docente con sus tareas"
check "otro docente NO publica en un curso ajeno" "403" "$(code jorge -F asig=$CURSO_JOSE -F tema=AJENA -F fecha=2030-01-01T10:00 -F descrip=X -F 'enlaces=https://youtu.be/dQw4w9WgXcQ' $TA/controlador_registro_tareas.php)"
check "otro docente NO modifica la tarea" "403" "$(code jorge -F id=$ID -F asig=$CURSO_jorge -F tema=X -F fecha=2030-01-01T10:00 -F descrip=X -F 'enlaces=https://youtu.be/dQw4w9WgXcQ' $TA/controlador_modificar_tareas.php)"
check "otro docente NO elimina ni finaliza la tarea" "403 403" "$(code jorge -d id=$ID $TA/controlador_eliminar_tarea.php) $(code jorge -d id=$ID -d estatus=FINALIZADO $TA/controlador_modificar_estado_tarea.php)"
check "otro docente NO ve el material ni los envíos" "403 403" "$(code jorge "$TA/controlador_archivos_tarea.php?carpeta=$CAR") $(code jorge -d id=$ID $TA/controlador_listar_tabla_envio_tareas.php)"
ENV=$(q "select d.id_detalle_tarea from detalle_tarea d join tareas t on t.id_tarea=d.id_tarea where t.id_detalle_asignatura<>$CURSO_jorge limit 1")
check "otro docente NO califica envíos de tareas ajenas" "403" "$(code jorge -d id=$ENV -d nota=15 $TA/controlador_registro_calificacion.php)"
check "la tarea sigue intacta" "VIDEO1 PENDIENTE" "$(q "select concat(tema,' ',estado) from tareas where id_tarea='$ID'")"
check "el administrador sí puede modificarla" "1" "$(post JERSSON123 -F id=$ID -F asig=$CURSO_JOSE -F tema=VIDEO1 -F fecha=2030-01-03T10:00 -F descrip=Z -F 'enlaces=https://youtu.be/dQw4w9WgXcQ' $TA/controlador_modificar_tareas.php)"
check "el estudiante NO ve material de tareas que no son suyas" "403" "$(code DANIEL20 "$TA/controlador_archivos_tarea.php?carpeta=$CAR")"

# Carpetas creadas por la prueba
for d in $(ls "$DOCS"); do echo "$antes" | grep -qx "$d" || rm -rf "$DOCS/$d"; done
terminar
