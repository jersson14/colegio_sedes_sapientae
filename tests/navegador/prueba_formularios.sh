#!/usr/bin/env bash
# Fase 13 (paso 2): componente común de formularios (js/formulario.js) en Chrome headless, sobre "Nuevo estudiante".
# Obligatorios marcados junto al campo (también en la pestaña de padres), fechas fuera de rango, aviso de cambios sin
# guardar, un solo envío con varios clics y cierre sin preguntar tras guardar. Requiere la BD de prueba recién reiniciada.
R=$(cd "$(dirname "$0")/../.." && pwd)
# Seguro: solo contra la base de prueba (ver tests/lib.sh)
grep -q "colegio2_test" "$R/config/config.local.php" 2>/dev/null || { echo "ALTO: config/config.local.php no apunta a colegio2_test." >&2; exit 1; }
B=http://localhost/_public_html
mkdir -p "$R/tests/tmp"; O=$(cygpath -m "$R/tests/tmp")
M="/c/xampp/mysql/bin/mysql.exe --default-character-set=utf8mb4 -uroot -P3307 -h127.0.0.1 colegio2_test -N"
jar=$R/tests/tmp/f.$(date +%s%N).jar
curl -s -c $jar -b $jar -o /dev/null $B/index.php
curl -s -c $jar -b $jar -o /dev/null -d "u=JERSSON123&c=Prueba123" $B/controller/usuario/controlador_iniciar_sesion.php
SID=$(tail -1 $jar | awk '{print $7}')
BTN="\$('#modal_registro .modal-footer button:contains(Registrar)')[0]"
P1="new Promise(ok=>{window.__envios=0;\$(document).ajaxSend((e,x,o)=>{if(/registrar_alumno/.test(o.url))window.__envios++});AbrirRegistro();setTimeout(()=>{$BTN.click();setTimeout(()=>ok('marcados='+\$('#modal_registro .is-invalid').length+' envios='+window.__envios),700)},900)})"
P2="new Promise(ok=>{const v={txt_dni:'72000077',txt_nomb:'ROSA',txt_ape_pa:'MAMANI',txt_apema:'TORRES',txt_fecha_na:'2030-01-01',txt_direc:'AV SOL 100'};Object.keys(v).forEach(k=>\$('#'+k).val(v[k]).trigger('input'));\$('#txt_sexo').val(\$('#txt_sexo option').eq(1).val()).trigger('change');$BTN.click();setTimeout(()=>ok('fecha='+(\$('#txt_fecha_na').nextAll('.fm-error').text().indexOf('La fecha debe estar')>=0)+' pestana_padres='+\$('#custom-tabs-one-profile').hasClass('active')+' madre='+\$('#txt_dni_ma').hasClass('is-invalid')),700)})"
P3="new Promise(ok=>{\$('#modal_registro').modal('hide');setTimeout(()=>{const t=\$('.swal2-title').text();Swal.clickCancel();setTimeout(()=>ok('pregunta='+(t.indexOf('Descartar')>=0)+' sigue_abierta='+\$('#modal_registro').hasClass('show')),500)},600)})"
P4="new Promise(ok=>{\$('#txt_fecha_na').val('2016-05-20').trigger('input');\$('#txt_dni_ma').val('41000077');\$('#txt_nom_ma').val('JUANA TORRES');\$('#txt_cel_ma').val('987654321');const b=$BTN;b.click();b.click();b.click();setTimeout(()=>{Swal.clickConfirm();setTimeout(()=>ok('envios='+window.__envios+' cerrada='+!\$('#modal_registro').hasClass('show')+' pregunta='+(\$('.swal2-title').text().indexOf('Descartar')>=0)),1500)},1800)})"
SAL=$(timeout 120 node "$R/tests/navegador/navegar_vista.js" "$SID" "$O" alumnos/view_alumnos.php "$P1" "$P2" "$P3" "$P4" 2>&1)
ok=0; fail=0
check() { if echo "$SAL" | grep -q "$2"; then ok=$((ok+1)); echo "  OK   $1"; else fail=$((fail+1)); echo "  FALLA $1 (esperado: $2)"; fi; }
check "guardar vacío marca los obligatorios y no envía" "paso 1: \"marcados=10 envios=0\""
check "fecha fuera de rango, abre la pestaña de padres y marca a la madre" "paso 2: \"fecha=true pestana_padres=true madre=true\""
check "cerrar con datos escritos pregunta y se puede seguir editando" "paso 3: \"pregunta=true sigue_abierta=true\""
check "tres clics = un solo envío; tras guardar cierra sin preguntar" "paso 4: \"envios=1 cerrada=true pregunta=false\""
check "el estudiante quedó registrado una vez" "$( [ "$($M -e "select count(*) from alumnos where alum_dni='72000077'" | tr -d '\r')" = 1 ] && echo paso)"
echo "RESULTADO: $ok OK, $fail fallas"
[ "$fail" -eq 0 ]
