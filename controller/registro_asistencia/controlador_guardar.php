<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/planilla_asistencia.php';
// Guarda los cambios de la planilla: [{matricula, fecha, codigo, obs?}] con código P, T, F, J o vacío (borra)
$anio = entero_post('anio', 1, 'año escolar');
$aula = entero_post('aula', 1, 'aula');
$M = new Modelo_Registro_Asistencia();
$anio_escolar = $M->Anio($anio);
if (!$anio_escolar) {
    denegar(400, 'Año escolar no encontrado');
}
if (!$M->Aula_Permitida($anio, $aula, planilla_usuario_docente())) {
    registrar_acceso_denegado('controller/registro_asistencia/controlador_guardar.php', 'AULA_AJENA');
    denegar(403, 'No tiene asignada esa aula');
}
$cambios = json_decode($_POST['cambios'] ?? '', true);
if (!is_array($cambios) || !$cambios || count($cambios) > 5000) {
    denegar(400, 'No hay cambios para guardar');
}
$hoy = date('Y-m-d');
$limpios = [];
foreach ($cambios as $x) {
    $fecha = (string) ($x['fecha'] ?? '');
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    $codigo = strtoupper((string) ($x['codigo'] ?? ''));
    if (!$d || $d->format('Y-m-d') !== $fecha || (int) $d->format('N') > planilla_ultimo_dia()) {
        denegar(400, 'Fecha inválida: ' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'));
    }
    if ($fecha > $hoy) {
        denegar(400, 'No se puede registrar asistencia de días futuros');
    }
    if ($codigo !== '' && !isset(Modelo_Registro_Asistencia::CODIGOS[$codigo])) {
        denegar(400, 'Código de asistencia inválido (use P, T, F o J)');
    }
    $limpio = ['matricula' => (int) ($x['matricula'] ?? 0), 'fecha' => $fecha, 'codigo' => $codigo];
    // Observación opcional del día (p. ej. "llegó con su mamá", "cita médica"); sin marca no se guarda
    if (array_key_exists('obs', $x) && $codigo !== '') {
        $obs = trim((string) $x['obs']);
        if (mb_strlen($obs, 'UTF-8') > 255) {
            denegar(400, 'La observación admite hasta 255 caracteres');
        }
        $limpio['obs'] = $obs;
    }
    $limpios[] = $limpio;
}
try {
    responder_json(['ok' => true, 'guardados' => $M->Guardar($anio, $aula, $limpios)]);
} catch (InvalidArgumentException $e) {
    denegar(400, $e->getMessage());
}
