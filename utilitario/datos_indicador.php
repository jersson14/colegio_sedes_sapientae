<?php
// Datos comunes del formulario de categoría (registrar / modificar). Se valida aquí y se escapa al mostrar.
function datos_indicador_post() {
    $tipo = $_POST['tipo'] ?? '';
    if (!in_array($tipo, ['INGRESOS', 'GASTOS'], true)) {
        denegar(400, 'Tipo de categoría inválido');
    }
    $nombre = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $_POST['nombre'] ?? '')), 'UTF-8');
    if (!preg_match('/^[\p{L}\p{N} .,()\/&-]{2,100}$/u', $nombre)) {
        denegar(400, 'El nombre debe tener entre 2 y 100 letras, números o signos . , ( ) / & -');
    }
    $descrip = mb_substr(trim(strip_tags($_POST['descrip'] ?? '')), 0, 255, 'UTF-8');
    return [$tipo, $nombre, mb_strtoupper($descrip, 'UTF-8'), entero_post('padre', 1, 'grupo')];
}
