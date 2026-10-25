<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_reporte_estudiantes.php';
// Reporte de estudiantes a medida: ?accion=catalogo devuelve campos y opciones de filtro; si no, genera el reporte
$MR = new Modelo_Reporte_Estudiantes();
$MA = new Modelo_Registro_Asistencia();
$catalogo = Modelo_Reporte_Estudiantes::Campos();

if (($_POST['accion'] ?? '') === 'catalogo') {
    $anios = $MA->Anios();
    $anio = (int) ($_POST['anio'] ?? 0);
    if (!$anio) {
        foreach ($anios as $a) {
            if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
        }
    }
    $campos = [];
    foreach ($catalogo as $k => $c) {
        $campos[] = ['clave' => $k, 'grupo' => $c[0], 'titulo' => $c[1], 'tipo' => $c[3]];
    }
    responder_json(['campos' => $campos, 'anios' => $anios, 'anio' => $anio, 'niveles' => $MR->Niveles(), 'aulas' => $MA->Aulas($anio)]);
}

$campos = array_values(array_filter(explode(',', (string) ($_POST['campos'] ?? '')), function ($k) use ($catalogo) {
    return isset($catalogo[$k]);
}));
if (!$campos) {
    denegar(400, 'Elija al menos un campo');
}
$filtros = [
    'anio' => entero_post('anio', 1, 'año escolar'),
    'nivel' => (int) ($_POST['nivel'] ?? 0),
    'aula' => (int) ($_POST['aula'] ?? 0),
    'sexo' => in_array($_POST['sexo'] ?? '', ['MASCULINO', 'FEMENINO'], true) ? $_POST['sexo'] : '',
    'tipo' => in_array($_POST['tipo'] ?? '', ['NUEVO', 'ANTIGUO'], true) ? $_POST['tipo'] : '',
    'con_deuda' => !empty($_POST['con_deuda']),
    'buscar' => texto_post('buscar', 100),
];
responder_json([
    'columnas' => array_map(function ($k) use ($catalogo) {
        return ['clave' => $k, 'titulo' => $catalogo[$k][1], 'tipo' => $catalogo[$k][3]];
    }, $campos),
    'filas' => $MR->Generar($campos, $filtros),
]);
