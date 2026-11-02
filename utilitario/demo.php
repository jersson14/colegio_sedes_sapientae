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
    'configuracion/view_pagina_web.php',
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

/** Usuarios de prueba que la pantalla de ingreso muestra en la demo (scripts/demo/generar_demo.php; todos con DEMO_CLAVE). */
const DEMO_CLAVE = 'Colegio2026';
const DEMO_USUARIOS = [
    ['Administrador', 'DIRECCION', 'Todo el colegio'],
    ['Contador', 'CONTADOR', 'Caja, pensiones y cuentas'],
    ['Auxiliar', 'AUXILIAR', 'Asistencia y tareas'],
    ['Docente', 'MHUAMAN', 'Matemática, 1.° a 3.° de secundaria'],
    ['Docente', 'EPEREZ', 'Tutora de 1.° de primaria'],
    ['Estudiante', '77368344', '1.° de secundaria, al día'],
    ['Estudiante', '75448446', '1.° de primaria, con deuda'],
    ['Enfermera', 'ENFERMERA', 'Atenciones de enfermería'],
    ['Psicóloga', 'PSICOLOGA', 'Atenciones de psicología'],
];

/** Los usuarios de prueba que existen y están activos (vacío fuera de la demo). */
function demo_usuarios_ingreso() {
    if (!modo_demo()) { return []; }
    try {
        require_once __DIR__ . '/../model/model_conexion.php';
        $q = (new conexionBD())->conexionPDO()->query("SELECT usu_usuario FROM usuario WHERE usu_estatus = 'ACTIVO'");
        $activos = array_flip($q->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
    return array_values(array_filter(DEMO_USUARIOS, function ($u) use ($activos) { return isset($activos[$u[1]]); }));
}
