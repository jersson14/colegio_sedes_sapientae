<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/tema.php';
// Apariencia del colegio (solo administrador). accion=cargar | previsualizar | guardar
$accion = $_POST['accion'] ?? 'cargar';

if ($accion === 'cargar') {
    responder_json(['tema' => tema_actual(), 'presets' => TEMA_PRESETS]);
}

$tema = tema_actual();
$tema['primario'] = strtoupper($_POST['primario'] ?? '');
$tema['acento'] = strtoupper($_POST['acento'] ?? '');
$tema['estilo_menu'] = $_POST['estilo_menu'] ?? '';
if (!tema_color_valido($tema['primario']) || !tema_color_valido($tema['acento'])) {
    denegar(400, 'Elija colores válidos');
}
if (!in_array($tema['estilo_menu'], ['COLOR', 'OSCURO', 'CLARO'], true)) {
    denegar(400, 'Estilo de menú inválido');
}
// Aviso de legibilidad: el acento se usa como texto en el menú y en etiquetas
$aviso = tema_contraste($tema['primario'], '#FFFFFF') < 3 ? 'El color principal es muy claro: los títulos se verán con un tono más oscuro para que se lean bien.' : '';

if ($accion === 'previsualizar') {
    responder_json(['variables' => tema_variables($tema), 'aviso' => $aviso]);
}

if ($accion === 'guardar') {
    $lema = texto_post('lema', 150);
    $c = (new conexionBD())->conexionPDO();
    $q = $c->prepare("UPDATE empresa SET color_primario = ?, color_acento = ?, estilo_menu = ?, lema = ?, updated_at = NOW()
                      ORDER BY empresa_id LIMIT 1");
    $q->execute([$tema['primario'], $tema['acento'], $tema['estilo_menu'], $lema !== '' ? $lema : null]);
    responder_json(['ok' => true, 'aviso' => $aviso]);
}

denegar(400, 'Acción inválida');
