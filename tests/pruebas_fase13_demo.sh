#!/usr/bin/env bash
# Modo demostración (utilitario/demo.php): qué se esconde y se rechaza en la demo pública. Las pruebas corren con
# 'modo_demo' => false (config.local.php de pruebas); aquí se revisan las listas y que el menú y la seguridad las usen.
source "$(dirname "$0")/lib.sh"
RW=$(cygpath -m "$R")
D() { php -r 'require "'"$RW"'/utilitario/demo.php"; '"$1"; }
login JERSSON123

echo "== Qué se bloquea"
check "pantallas: usuarios, roles, permisos, respaldos, año escolar y periodos" "1 1 1 1 1 1" "$(for v in usuario/view_usuario.php roles/view_roles.php configuracion/view_permisos.php configuracion/view_respaldos.php año/view_año_escolar.php periodo/view_periodo.php; do D "echo (int) in_array('$v', DEMO_VISTAS_BLOQUEADAS, true);"; echo; done | paste -sd' ')"
check "sus controladores quedan rechazados (listar usuarios, parámetros, cambio de año)" "1 1 1" "$(for c in controller/usuario/controlador_listar_usuario.php controller/configuracion/controlador_parametros.php controller/cambio_anio/controlador_ejecutar.php; do D "echo (int) in_array('$c', demo_controladores_bloqueados(), true);"; echo; done | paste -sd' ')"
check "nadie cambia contraseñas, fotos de perfil ni el acceso de los estudiantes" "1 1 1" "$(for c in controller/usuario/controlador_cambiar_mi_clave.php controller/usuario/controlador_usuario_modificar_foto.php controller/alumnos/controlador_cambiar_acceso.php; do D "echo (int) in_array('$c', demo_controladores_bloqueados(), true);"; echo; done | paste -sd' ')"
check "lo que comparten otras pantallas sigue libre (ayuda, listar estudiantes, caja)" "0 0 0" "$(for c in controller/ayuda/controlador_ayuda.php controller/alumnos/controlador_listar_alumnos.php controller/pago_pension/controlador_caja.php; do D "echo (int) in_array('$c', demo_controladores_bloqueados(), true);"; echo; done | paste -sd' ')"

echo "== Dónde se aplica"
check "la seguridad de los controladores y de las pantallas lo revisan" "1 1" "$(grep -c 'demo_controlador_bloqueado(\$ruta)' $R/controller/_seguridad.php) $(grep -c 'demo_vista_bloqueada(\$__vista)' $R/utilitario/vista_segura.php)"
check "el menú esconde las 12 pantallas (13 enlaces) y el cambio de contraseña" "13 2" "$(grep -c "<?php if (!demo_vista_bloqueada(" $R/view/index.php) $(grep -c '<?php if (!modo_demo()) { ?>' $R/view/index.php)"
check "con 'modo_demo' en false (pruebas y rama venta) todo se ve: Usuarios en el menú y su lista responde" "1 200" "$(grep -c "'usuario/view_usuario.php')\" class=\"nav-link\"" $T/JERSSON123.html) $(code JERSSON123 -X POST $B/controller/usuario/controlador_listar_usuario.php)"
check "los usuarios de prueba del ingreso existen en la demo y solo se muestran con el modo demo" "9 0" "$(D 'echo count(DEMO_USUARIOS);') $(curl -s $B/index.php | grep -c 'ing-demo-usuario\"')"
check "el generador de la demo crea esos usuarios" "1 1 1" "$(grep -c "'DIRECCION', 9, 'ADMINISTRADOR'" $R/scripts/demo/generar_demo.php) $(grep -c "'CONTADOR', 11, 'CONTADOR'" $R/scripts/demo/generar_demo.php) $(grep -c "const CLAVE = 'Colegio2026'" $R/scripts/demo/generar_demo.php)"
terminar
