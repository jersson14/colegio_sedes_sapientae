<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_registro_tareas.php';
require_once __DIR__ . '/../../model/model_aula_virtual.php';
// Tareas y exámenes de mis cursos (docente): accion=tareas | examenes. Lista por curso con su avance.
// Crear, editar, cerrar y eliminar usan los controladores de tareas y exámenes, que revisan la propiedad del curso.
$anios = (new Modelo_Registro_Asistencia())->Anios();
$anio = (int) ($_POST['anio'] ?? 0);
if (!$anio) {
    foreach ($anios as $a) {
        if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
    }
}
$docente = rol_actual() === 'DOCENTE' ? usuario_actual_id() : null;
$cursos = array_values(array_filter((new Modelo_Registro_Tareas())->Cursos($anio, $docente), function ($c) { return stripos($c['asignatura'], 'RECREO') !== 0; }));
$ids = array_map('intval', array_column($cursos, 'id'));
$items = [];
if ($ids) {
    if (($_POST['accion'] ?? 'tareas') === 'examenes') {
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $q = (new conexionBD())->conexionPDO()->prepare("SELECT id_examen AS id, id_detalle_asignatura AS curso, tema_examen AS tema, descripcion,
                                                                fecha_examen AS fecha, estado, created_at AS publicada
                                                         FROM examen WHERE id_detalle_asignatura IN ($marcas) ORDER BY fecha_examen DESC, id_examen DESC");
        $q->execute($ids);
        $items = $q->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $M = new Modelo_Aula_Virtual();
        foreach ($ids as $curso) {
            foreach ($M->Tareas($curso) as $t) { $items[] = ['curso' => $curso] + $t; }
        }
    }
}
responder_json(['ok' => true, 'anios' => $anios, 'anio' => $anio, 'cursos' => $cursos, 'items' => $items, 'ahora' => date('Y-m-d H:i:s')]);
