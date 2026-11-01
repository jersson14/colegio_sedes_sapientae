<?php
/**
 * Modo demostración (config.php 'modo_demo'): para la demo pública, que cualquiera puede probar.
 * Esconde del menú las pantallas que dejarían la demo inservible (usuarios, roles, permisos, configuración, año escolar...)
 * y el servidor rechaza sus controladores aunque se llamen directo. Tampoco se pueden cambiar contraseñas, fotos de perfil
 * ni el acceso de los usuarios, para que nadie deje a los demás sin entrar.
 * En la rama "venta" 'modo_demo' es false y todo está habilitado.
 */

/** Pantallas que no se muestran en la demo. */
const DEMO_VISTAS_BLOQUEADAS = [
    'usuario/view_usuario.php',
    'roles/view_roles.php',
    'configuracion/view_permisos.php',
    'configuracion/view_parametros.php',
    'configuracion/view_regional.php',
    'configuracion/view_apariencia.php',
    'configuracion/view_formas_pago.php',
    'configuracion/view_auditoria.php',
    'configuracion/view_respaldos.php',
    'configuracion/view_cambio_anio.php',
    'año/view_año_escolar.php',
    'periodo/view_periodo.php',
];

/** Acciones sueltas (de pantallas que sí se muestran) que tampoco se permiten en la demo. */
const DEMO_CONTROLADORES_BLOQUEADOS = [
    'controller/usuario/controlador_cambiar_mi_clave.php',
    'controller/usuario/controlador_modificar_usuario_contra.php',
    'controller/usuario/controlador_modificar_usuario_estatus.php',
    'controller/usuario/controlador_usuario_modificar_foto.php',
    'controller/alumnos/controlador_cambiar_acceso.php',
    'controller/docentes/controlador_eliminar_docente.php',
    'controller/personal_administrativo/controlador_eliminar_personal.php',
];

function modo_demo() {
    static $demo = null;
    if ($demo === null) {
        $config = require __DIR__ . '/../config/config.php';
        $demo = !empty($config['modo_demo']);
    }
    return $demo;
}

function demo_vista_bloqueada($vista) {
    return modo_demo() && in_array($vista, DEMO_VISTAS_BLOQUEADAS, true);
}

/** Controladores rechazados: los de las pantallas bloqueadas que ninguna otra pantalla usa, más las acciones sueltas. */
function demo_controladores_bloqueados() {
    static $lista = null;
    if ($lista === null) {
        $mapa = (require __DIR__ . '/../controller/_pantallas.php')['pantallas'];
        $bloq = $usados = [];
        foreach ($mapa as $vista => $p) {
            foreach ($p['controladores'] as $c) {
                if (in_array($vista, DEMO_VISTAS_BLOQUEADAS, true)) { $bloq[$c] = true; } else { $usados[$c] = true; }
            }
        }
        $lista = array_merge(array_keys(array_diff_key($bloq, $usados)), DEMO_CONTROLADORES_BLOQUEADOS);
    }
    return $lista;
}

function demo_controlador_bloqueado($ruta) {
    return modo_demo() && in_array($ruta, demo_controladores_bloqueados(), true);
}
