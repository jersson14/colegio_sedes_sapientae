<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_auditoria.php';
    $MA = new Modelo_Auditoria();
    // Solo ADMINISTRADOR (no figura en controller/_permisos.php). Máximo 1000 cambios por consulta.
    [$desde, $hasta] = rango_fechas_post();
    $tabla = $_POST['tabla'] ?? '';
    if ($tabla !== '' && !isset(Modelo_Auditoria::TABLAS[$tabla])) { denegar(400, 'Tabla no válida'); }
    $registro = preg_replace('/[^0-9A-Za-z_-]/', '', (string) ($_POST['registro'] ?? ''));
    $usuario = isset($_POST['usuario']) && $_POST['usuario'] !== '' ? entero_post('usuario', 0, 'usuario') : 0;
    responder_json(['ok' => true, 'tablas' => Modelo_Auditoria::TABLAS, 'data' => $MA->Listar($tabla, $desde, $hasta, $usuario, $registro)]);
?>
