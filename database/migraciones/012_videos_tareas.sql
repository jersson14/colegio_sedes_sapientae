-- =====================================================================
-- Videos en las tareas
--   * El docente o el administrador puede subir videos (MP4/WebM) junto con los documentos de la tarea:
--     se guardan en la misma carpeta de la tarea y se ven en la plataforma.
--   * También puede pegar enlaces de video (YouTube, Google Drive, Vimeo u otro sitio). Se guardan aquí
--     como lista JSON [{"url": "..."}] y los de YouTube/Drive/Vimeo se reproducen dentro de la plataforma.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE tareas
  ADD COLUMN enlaces_video TEXT NULL DEFAULT NULL AFTER archivo_tarea;
