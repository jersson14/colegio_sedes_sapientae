#!/usr/bin/env bash
# Fase 13 (después del paso 16): pantallas de la dirección: Notas, Tareas y Exámenes con las pantallas nuevas (todos los cursos,
# con selector de aula), el auxiliar en Tareas, y las pantallas viejas quitadas.
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
HP=$(php -r 'echo password_hash(htmlspecialchars("Prueba123",ENT_QUOTES,"UTF-8"),PASSWORD_DEFAULT,["cost"=>10]);')
$M -e "update usuario set usu_contra='$HP' where usu_usuario in ('JOSE','MANUEL123')"
login JERSSON123; login MANUEL123; login JOSE
J() { local e=$1; shift; php -r '$d=json_decode(stream_get_contents(STDIN),true); eval("echo ".$argv[1].";");' "$e" "$@"; }
TODOS=$(sqlf "select count(*) from detalle_asignatura_docente dd join asignatura_docente ad on ad.Id_asigdocente=dd.Id_asig_docente join asignaturas a on a.Id_asignatura=dd.Id_asignatura where ad.\`id_año\`=(select \`Id_año_escolar\` from \`año_escolar\` where estado='ACTIVO' limit 1) and a.nombre_asig not like 'RECREO%'")

echo "== Menú de la dirección y del auxiliar"
for v in notas/view_registro_notas.php docente/view_tareas_docente.php docente/view_examenes_docente.php; do
  check "dirección: $v" "1" "$(grep -c "'$v')\" class=\"nav-link\"" $T/JERSSON123.html | sed 's/[1-9][0-9]*/1/')"
done
check "auxiliar: Tareas con la pantalla nueva" "1" "$(grep -c "'docente/view_tareas_docente.php')\" class=\"nav-link\"" $T/MANUEL123.html | sed 's/[1-9][0-9]*/1/')"

echo "== Ven todos los cursos (el docente solo los suyos)"
check "notas: la dirección ve todos los cursos del año" "$TODOS" "$(post JERSSON123 -d accion=cargar $B/controller/notas/controlador_registro_notas_curso.php | J 'count($d["cursos"])')"
check "tareas: dirección y auxiliar ven todos los cursos" "$TODOS $TODOS" "$(post JERSSON123 -d accion=tareas $B/controller/docente/controlador_trabajos.php | J 'count($d["cursos"])') $(post MANUEL123 -d accion=tareas $B/controller/docente/controlador_trabajos.php | J 'count($d["cursos"])')"
check "el docente sigue viendo solo los suyos" "1" "$(post JOSE -d accion=tareas $B/controller/docente/controlador_trabajos.php | J '(int)(count($d["cursos"]) < '$TODOS')')"
check "el auxiliar no califica notas" "403" "$(code MANUEL123 -d accion=cargar $B/controller/notas/controlador_registro_notas_curso.php)"
check "títulos según el rol" "1 1" "$(curl -s -b $T/JERSSON123.jar $B/view/notas/view_registro_notas.php | grep -c '>Notas</h1>') $(curl -s -b $T/JOSE.jar $B/view/notas/view_registro_notas.php | grep -c '>Notas de mis cursos</h1>')"

echo "== Pantallas viejas quitadas y estilo común"
check "las pantallas viejas ya no existen" "404 404 404 404" "$(for v in notas/view_notas.php tareas/view_tareas.php examen/view_examen.php pago_pensiones/view_pago_pensiones.php; do curl -s -o /dev/null -w '%{http_code}' -b $T/JERSSON123.jar $B/view/$v; echo; done | paste -sd' ')"
check "las tablas de catálogo usan el estilo nuevo (encabezado claro y botones con borde)" "1 1" "$(grep -c 'thead.tabla-cabecera, thead.tabla-cabecera th' $R/css/tema.css) $(grep -c 'table.dataTable td .btn-danger {' $R/css/tema.css)"
terminar
