<?php
// Límites del material de tareas (documentos, videos y enlaces) para los formularios del docente y del administrador.
require_once __DIR__ . '/../../utilitario/vista_segura.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
define('ACEPTA_MATERIAL', '.' . implode(',.', array_merge(DOCUMENTO_EXTENSIONES, VIDEO_EXTENSIONES)));
define('AYUDA_MATERIAL', 'Documentos (pdf, word, excel, powerpoint, imágenes, zip) hasta ' . limite_documento_mb()
    . ' MB y videos (mp4, webm, mov) hasta ' . limite_video_mb() . ' MB.');
?>
<script>
  window.LIMITES_TAREA = <?php echo json_encode(['documento_mb' => limite_documento_mb(), 'video_mb' => limite_video_mb(), 'enlaces_max' => ENLACES_VIDEO_MAX]); ?>;
</script>
<script src="../js/material_tarea.js?rev=<?php echo time(); ?>"></script>
