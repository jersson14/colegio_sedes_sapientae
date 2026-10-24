#!/usr/bin/env bash
# Fase 8C: guarda textos con código (<img onerror>) en la BD de prueba y verifica en Chrome headless que
# ninguna pantalla lo ejecute (con la protección) y que la prueba sí lo detecta sin ella.
# Requiere la BD de prueba recién reiniciada. Modifica datos: reiniciarla después.
R=$(cd "$(dirname "$0")/../.." && pwd)
B=http://localhost/_public_html
mkdir -p "$R/tests/tmp"; O=$(cygpath -m "$R/tests/tmp")
M="/c/xampp/mysql/bin/mysql.exe --default-character-set=utf8mb4 -uroot -P3307 -h127.0.0.1 colegio2_test"
cat > "$R/tests/tmp/xss.sql" <<'EOF'
SET @p := '<img src=x onerror="window.__xss=(window.__xss||0)+1">';
UPDATE especialidad SET Especialidad = CONCAT('MAT ', @p) ORDER BY Id_especilidad LIMIT 1;
UPDATE alumnos SET alum_nombre = CONCAT('ANA ', @p) WHERE Id_alumno = 6;
UPDATE seccion SET seccion_nombre = CONCAT('A ', @p) ORDER BY seccion_id LIMIT 1;
UPDATE comunicados SET titulo = CONCAT('AVISO ', @p) ORDER BY id_comunicado LIMIT 1;
UPDATE indicadores SET descripcion = @p WHERE id_indicadores = 2;
EOF
$M < "$R/tests/tmp/xss.sql"
sid() { local jar=$R/tests/tmp/$1.$(date +%s%N).jar; curl -s -c $jar -b $jar -o /dev/null $B/index.php; curl -s -c $jar -b $jar -o /dev/null -d "u=$1&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php; tail -1 $jar | awk '{print $7}'; }
DESPROTEGER="\$.fn.dataTable.defaults.columnDefs = []; window.textoSeguro = function(x){ return x; }; 1"
PASO="'xss=' + (window.__xss || 0) + ' visible=' + (document.getElementById('contenido_principal').innerText.indexOf('onerror') >= 0)"
fallas=0
for v in especialidades/view_especialidades.php alumnos/view_alumnos.php seccion/view_seccion.php comunicado/view_comunicado.php ingresos_y_egresos/view_indicadores.php docentes/view_docentes.php matricula/view_matricula.php; do
  a=$(timeout 90 node "$R/tests/navegador/navegar_vista.js" "$(sid JERSSON123)" "$O" $v "$PASO" 2>&1 | grep "paso 1" | sed 's/paso 1: //')
  b=$(PRE="$DESPROTEGER" timeout 90 node "$R/tests/navegador/navegar_vista.js" "$(sid JERSSON123)" "$O" $v "$PASO" 2>&1 | grep "paso 1" | sed 's/paso 1: //')
  echo "$v | protegido: $a | sin protección: $b"
  case "$a" in *"xss=0 visible=true"*) ;; *) fallas=$((fallas+1)) ;; esac
done
echo "RESULTADO: $fallas pantalla(s) con problemas"
[ "$fallas" -eq 0 ]
