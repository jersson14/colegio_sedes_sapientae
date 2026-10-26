#!/usr/bin/env bash
# Fase 13, paso 3 del plan: contraseñas (mínimo 8, temporal en el primer ingreso, cambiar la propia).
# Requiere la BD de prueba recién reiniciada.
source "$(dirname "$0")/lib.sh"
login JERSSON123
DID=$(q "select usu_id from usuario where usu_usuario='DANIEL20'")
MC=$B/controller/usuario/controlador_cambiar_mi_clave.php

echo "== El administrador restablece una contraseña (temporal)"
check "menos de 8 caracteres: rechazada" "400" "$(code JERSSON123 -d "id=$DID&con=corta1" $B/controller/usuario/controlador_modificar_usuario_contra.php)"
check "válida: guardada y marcada como temporal" "1 1" "$(post JERSSON123 -d "id=$DID&con=Temporal8x" $B/controller/usuario/controlador_modificar_usuario_contra.php) $(q "select debe_cambiar_clave from usuario where usu_id=$DID")"

echo "== Primer ingreso con la contraseña temporal"
login DANIEL20 Temporal8x
check "puede ingresar" "1" "$(grep -c 'Cree su contraseña' $T/DANIEL20.html)"
check "el sistema solo muestra la pantalla de cambio" "0" "$(grep -c 'id=\"contenido_principal\"' $T/DANIEL20.html)"
check "los demás controladores quedan bloqueados" "403" "$(code DANIEL20 -X POST $B/controller/asistencias/controlador_listar_alumnos_totales_estu.php)"
check "(con el motivo)" "1" "$(grep -c 'temporal' $T/out)"
check "contraseña actual equivocada: rechazado" "400" "$(code DANIEL20 -d 'actual=otra&nueva=MiClave2026&confirmar=MiClave2026' $MC)"
check "confirmación distinta: rechazado" "400" "$(code DANIEL20 -d 'actual=Temporal8x&nueva=MiClave2026&confirmar=MiClave2027' $MC)"
check "igual a la temporal: rechazado" "400" "$(code DANIEL20 -d 'actual=Temporal8x&nueva=Temporal8x&confirmar=Temporal8x' $MC)"
check "muy corta: rechazado" "400" "$(code DANIEL20 -d 'actual=Temporal8x&nueva=Corta1&confirmar=Corta1' $MC)"
check "igual al usuario: rechazado" "400" "$(code DANIEL20 -d 'actual=Temporal8x&nueva=daniel20&confirmar=daniel20' $MC)"
check "válida: cambiada y ya no es temporal" "true 0" "$(post DANIEL20 -d 'actual=Temporal8x&nueva=MiClave2026&confirmar=MiClave2026' $MC | jq_ ok) $(q "select debe_cambiar_clave from usuario where usu_id=$DID")"
check "en la misma sesión ya puede usar el sistema" "200" "$(code DANIEL20 -X POST $B/controller/asistencias/controlador_listar_alumnos_totales_estu.php)"
login DANIEL20 MiClave2026
check "entra con la nueva contraseña al sistema completo" "1" "$(grep -c 'id=\"contenido_principal\"' $T/DANIEL20.html)"
check "la temporal ya no sirve" "CREDENCIALES" "$(curl -s -c $T/x.jar -b $T/x.jar -o /dev/null $B/index.php; curl -s -c $T/x.jar -b $T/x.jar -d 'u=DANIEL20&c=Temporal8x' $B/controller/usuario/controlador_iniciar_sesion.php | jq_ motivo)"

echo "== Cambiar la propia contraseña (sin obligación)"
check "el administrador cambia la suya desde su menú" "true" "$(post JERSSON123 -d 'actual=Prueba123&nueva=Direccion2026&confirmar=Direccion2026' $MC | jq_ ok)"
check "(y no queda como temporal)" "0" "$(q "select debe_cambiar_clave from usuario where usu_usuario='JERSSON123'")"
check "el menú del usuario ofrece cambiar la contraseña" "2" "$(login JERSSON123 Direccion2026; grep -c 'Cambiar mi contraseña' $T/JERSSON123.html | awk '{print ($1>=2)?2:$1}')"
check "solo cambia la de la sesión (no la de otro usuario)" "$(q "select usu_contra from usuario where usu_usuario='JOSE'")" "$(post JERSSON123 -d 'id=9&actual=Direccion2026&nueva=OtraClave99&confirmar=OtraClave99' $MC > /dev/null; q "select usu_contra from usuario where usu_usuario='JOSE'")"

terminar
