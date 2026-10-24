<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    // Guarda la asistencia de un día: filas = [{docente, estado, horas, minutos, observacion}, ...]
    $fecha = fecha_post('fecha', 'fecha');
    $filas = json_decode($_POST['filas'] ?? '', true);
    if (!is_array($filas) || !$filas || count($filas) > 500) {
        denegar(400, 'No hay registros de asistencia para guardar');
    }
    $guardadas = 0;
    $errores = [];
    foreach ($filas as $f) {
        $doc = filter_var($f['docente'] ?? null, FILTER_VALIDATE_INT);
        $horas = str_replace(',', '.', trim((string) ($f['horas'] ?? '0')));
        $minutos = filter_var($f['minutos'] ?? 0, FILTER_VALIDATE_INT);
        if (!$doc || !is_numeric($horas) || $minutos === false) {
            $errores[] = 'Fila con datos inválidos';
            continue;
        }
        $obs = mb_substr(trim(strip_tags((string) ($f['observacion'] ?? ''))), 0, 255, 'UTF-8');
        $r = $MP->Guardar_Asistencia($doc, $fecha, (string) ($f['estado'] ?? ''), round((float) $horas, 2), $minutos, $obs, usuario_actual_id());
        if ((int) $r['codigo'] === 1) {
            $guardadas++;
        } else {
            $errores[] = ($f['nombre'] ?? "Docente $doc") . ': ' . $r['mensaje'];
            if ((int) $r['codigo'] === 2) { break; }   // planilla aprobada: no tiene sentido seguir
        }
    }
    responder_json(['ok' => !$errores, 'guardadas' => $guardadas, 'errores' => $errores]);
?>
