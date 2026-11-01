#!/usr/bin/env bash
# Fase 13 (final): material de apoyo del aula virtual. El docente de su curso (o la dirección y el auxiliar) publica archivos,
# videos y enlaces; los estudiantes del aula los ven en la pestaña "Material" y los abren por el controlador (nunca directo).
# Requiere la BD de prueba recién reiniciada (migración 024).
source "$(dirname "$0")/lib.sh"
AV=$B/controller/aula_virtual/controlador_aula_virtual.php
MC=$B/controller/aula_virtual/controlador_material.php
crear_contador
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login MANUEL123; login JOSE; login DANIEL20; login CONTA1
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
ANIO=$(sqlf "select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1")
AULA_D=$(q "select m.id_aula from matricula m join usuario u on u.usu_id=m.usu_id where u.usu_usuario='DANIEL20' order by m.id_matricula desc limit 1")
CURSOS="from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join asignaturas a on a.Id_asignatura=dd.Id_asignatura join docentes d on d.Id_docente=ad.Id_docente join usuario u on u.usu_id=d.id_asusuario where ad.\`id_año\`=$ANIO and a.nombre_asig not like 'RECREO%'"
CURSO_D=$(sqlf "select dd.Id_detalle_asig_docente $CURSOS and a.Id_grado=$AULA_D and u.usu_usuario<>'JOSE' limit 1")
CURSO_J=$(sqlf "select dd.Id_detalle_asig_docente $CURSOS and a.Id_grado<>$AULA_D and u.usu_usuario='JOSE' limit 1")
printf '%%PDF-1.4 guia de fracciones' > $T/guia.pdf
printf '<?php echo 1;' > $T/malo.php
DIR="$R/controller/aula_virtual/material"

echo "== Publicar"
check "hay un curso del aula del estudiante (de otro docente) y uno de JOSE en otra aula" "1 1" "$([ -n "$CURSO_D" ] && echo 1) $([ -n "$CURSO_J" ] && echo 1)"
R1=$(post JERSSON123 -F accion=guardar -F curso=$CURSO_D -F "titulo=Separata de fracciones" -F "descripcion=Leer antes de la clase" \
     -F "enlaces=https://youtu.be/dQw4w9WgXcQ" -F "archivos[]=@$T/guia.pdf" -F "archivos[]=@$T/guia.pdf" $MC)
ID1=$(echo "$R1" | J '$d["id"]')
check "la dirección publica material con archivos y un enlace" "true" "$(echo "$R1" | jq_ ok)"
CARP=$(sqlf "select carpeta from material_curso where id_material=$ID1")
check "los archivos quedan en su carpeta; dos con el mismo nombre no se pisan" "guia (2).pdf|guia.pdf" "$(ls "$DIR/$CARP" | sort | paste -sd'|')"
check "el docente publica en su curso; el auxiliar también puede" "true true" "$(post JOSE -F accion=guardar -F curso=$CURSO_J -F titulo=Lectura -F 'descripcion=Capitulo 1' -F "archivos[]=@$T/guia.pdf" $MC | jq_ ok) $(post MANUEL123 -F accion=guardar -F curso=$CURSO_D -F titulo=Aviso -F 'enlaces=https://es.wikipedia.org/wiki/Fracci%C3%B3n' $MC | jq_ ok)"
check "el docente no publica en un curso que no es suyo" "403" "$(code JOSE -F accion=guardar -F curso=$CURSO_D -F titulo=X -F descripcion=Y $MC)"
check "el estudiante y la contadora no publican" "403 403" "$(code DANIEL20 -F accion=guardar -F curso=$CURSO_D -F titulo=X -F descripcion=Y $MC) $(code CONTA1 -F accion=guardar -F curso=$CURSO_D -F titulo=X -F descripcion=Y $MC)"
check "sin título o vacío no se guarda; un .php tampoco" "400 400 400" "$(code JERSSON123 -F accion=guardar -F curso=$CURSO_D -F descripcion=Y $MC) $(code JERSSON123 -F accion=guardar -F curso=$CURSO_D -F titulo=X $MC) $(code JERSSON123 -F accion=guardar -F curso=$CURSO_D -F titulo=X -F "archivos[]=@$T/malo.php" $MC)"

echo "== Ver y abrir"
C=$(post DANIEL20 -d accion=curso -d curso=$CURSO_D $AV)
check "el estudiante ve el material de su curso, el más nuevo primero" "2 Aviso" "$(echo "$C" | J 'count($d["material"])." ".$d["material"][0]["titulo"]')"
check "con sus archivos (nombre y tamaño) y el video listo para verse dentro" "2 1 youtube-nocookie" "$(echo "$C" | J 'count($d["material"][1]["archivos"])." ".(int)($d["material"][1]["archivos"][0]["tamano"]>0)." ".(strpos($d["material"][1]["enlaces"][0]["video"],"youtube-nocookie")!==false?"youtube-nocookie":"no")')"
check "un enlace que no es video se muestra como enlace; el estudiante no puede publicar" "vacio false" "$(echo "$C" | J '($d["material"][0]["enlaces"][0]["video"]===""?"vacio":"x")." ".($d["puede_publicar"]?"true":"false")')"
check "la dirección sí puede publicar (y la ruta de la carpeta no se envía)" "true 0" "$(post JERSSON123 -d accion=curso -d curso=$CURSO_D $AV | J '($d["puede_publicar"]?"true":"false")." ".(int)isset($d["material"][0]["carpeta"])')"
check "el estudiante abre el archivo (PDF en el navegador)" "200 %PDF-1.4 guia de fracciones" "$(code DANIEL20 "$MC?id=$ID1&archivo=guia.pdf") $(cat $T/out)"
ID_J=$(sqlf "select id_material from material_curso where id_detalle_asig_docente=$CURSO_J limit 1")
check "no abre material de otra aula; un nombre inventado o con ruta no existe" "403 404 404" "$(code DANIEL20 "$MC?id=$ID_J&archivo=guia.pdf") $(code DANIEL20 "$MC?id=$ID1&archivo=otro.pdf") $(code DANIEL20 "$MC?id=$ID1&archivo=..%2F..%2Fcontrolador_material.php")"
check "la carpeta no se abre directo por la web" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $T/DANIEL20.jar "$B/controller/aula_virtual/material/$CARP/guia.pdf")"

echo "== Quitar"
check "el docente no quita material de un curso ajeno" "403" "$(code JOSE -d accion=eliminar -d id=$ID1 $MC)"
post JERSSON123 -d accion=eliminar -d id=$ID1 $MC > /dev/null
check "la dirección lo quita: se borra el registro y su carpeta" "0 no" "$(sqlf "select count(*) from material_curso where id_material=$ID1") $([ -d "$DIR/$CARP" ] && echo si || echo no)"

echo "== Pantalla"
check "pestaña Material en el curso y tarjeta con su acceso" "1 1" "$(grep -c 'data-pestana="material"' $R/view/aula_virtual/view_aula_virtual.php) $(grep -c "data-pestana=\"material\" title=\"Material de apoyo\"" $R/js/aula_virtual.js)"
check "el formulario de subida solo llega a quien publica (no al estudiante)" "1 0" "$(post JOSE $B/view/aula_virtual/view_aula_virtual.php | grep -c 'id="av_modal_material"') $(post DANIEL20 $B/view/aula_virtual/view_aula_virtual.php | grep -c 'id="av_modal_material"')"
rm -rf "$DIR"/mat_* 2>/dev/null
terminar
