<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/importar_alumnos.php';
// Importar estudiantes desde Excel (Fase 13): ?plantilla=1 descarga la plantilla; POST modo=revisar|grabar con el archivo.
// "grabar" vuelve a revisar el mismo archivo y graba solo las filas sin errores.
if (!empty($_GET['plantilla'])) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="plantilla_estudiantes.xlsx"');
    echo importar_plantilla();
    exit;
}
$f = $_FILES['archivo'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
    denegar(400, 'Elija el archivo de Excel');
}
if ($f['size'] > 3 * 1024 * 1024) {
    denegar(400, 'El archivo pesa más de 3 MB');
}
try {
    $revision = importar_revisar(hoja_leer($f['tmp_name'], $f['name']));
} catch (InvalidArgumentException $e) {
    denegar(400, $e->getMessage());
}
if (($_POST['modo'] ?? 'revisar') === 'grabar') {
    if ($revision['faltan_columnas']) {
        denegar(400, 'Faltan columnas: ' . implode(', ', $revision['faltan_columnas']));
    }
    responder_json(['ok' => true] + importar_grabar($revision) + ['con_error' => $revision['con_error']]);
}
responder_json(['ok' => true] + $revision);
