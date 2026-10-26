#!/usr/bin/env bash
# Fase 13 paso 5: menú ordenado por tareas, buscador del menú y títulos claros en cada pantalla.
source "$(dirname "$0")/lib.sh"
V=$(dirname "$0")/../view
crear_contador
login JERSSON123; login CONTA1; login JOSE; login DANIEL20; login MANUEL123

echo "== Menú del administrador"
A=$T/JERSSON123.html
for s in 'ESTUDIANTES Y MATRÍCULA' 'ACADÉMICO' 'ASISTENCIA' 'PENSIONES Y CAJA' 'PERSONAL' 'COMUNICACIÓN Y SALUD' 'ORGANIZACIÓN DEL COLEGIO' 'CONFIGURACIÓN'; do
  check "sección $s" "1" "$(grep -c "menu-seccion\">$s<" $A)"
done
check "Usuarios ya no está oculto" "1 0" "$(grep -c "usuario/view_usuario.php')\" class=\"nav-link" $A) $(grep -B1 "usuario/view_usuario.php" $A | grep -c 'hidden')"
check "se fue 'Menú principal'; ahora es Inicio" "0 1" "$(grep -c 'Menú principal' $A) $(grep -c '<p>Inicio</p>' $A)"
check "no quedan cabeceras grises fijas" "0" "$(grep -c 'background-color:Gray' $A)"

echo "== Buscador del menú en todos los roles"
for u in JERSSON123 CONTA1 JOSE DANIEL20 MANUEL123; do
  check "$u tiene el buscador y menu.js" "1 1" "$(grep -c 'id="menu_buscar"' $T/$u.html) $(grep -c 'js/menu.js' $T/$u.html)"
done
check "el estudiante ve Mis notas, Mi horario y Mis pagos" "1 1 1" "$(grep -c '<p>Mis notas</p>' $T/DANIEL20.html) $(grep -c '<p>Mi horario</p>' $T/DANIEL20.html) $(grep -c '<p>Mis pagos</p>' $T/DANIEL20.html)"
check "el docente ve Notas de mis cursos" "1" "$(grep -c '<p>Notas de mis cursos</p>' $T/JOSE.html)"

echo "== Títulos de las pantallas"
check "ninguna pantalla dice MANTENIMIENTO" "0" "$(grep -rl 'MANTENIMIENTO' $V --include=*.php | wc -l)"
check "ninguna miga de pan dice MENU ni apunta a ../index.php" "0" "$(grep -rlE '>MENU<|href=\"\.\./index\.php\"' $V --include=*.php | wc -l)"
check "cada título tiene su línea de ayuda" "$(grep -rl 'pagina-titulo' $V --include=*.php | wc -l)" "$(grep -rl 'pagina-ayuda' $V --include=*.php | wc -l)"
check "títulos en mayúsculas sostenidas: ninguno" "0" "$(grep -rhoE 'pagina-titulo\">[A-ZÁÉÍÓÚÑ ]{4,}<' $V --include=*.php | wc -l)"
check "la pantalla de estudiantes abre con su título" "1" "$(curl -s -b $T/JERSSON123.jar $B/view/alumnos/view_alumnos.php | grep -c 'pagina-titulo\">Estudiantes<')"
terminar
