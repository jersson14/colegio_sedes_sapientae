-- Fase 13: material de apoyo del aula virtual. El docente (o la dirección) publica en un curso archivos, videos y enlaces
-- que no son tareas; los estudiantes del aula los ven en la pestaña "Material".
-- Los archivos se guardan en controller/aula_virtual/material/<carpeta> y solo se entregan por controlador_material.php.
CREATE TABLE IF NOT EXISTS material_curso (
  id_material INT NOT NULL AUTO_INCREMENT,
  id_detalle_asig_docente INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  carpeta VARCHAR(60) NULL,
  enlaces TEXT NULL,
  usu_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_material),
  KEY idx_material_curso (id_detalle_asig_docente, created_at),
  CONSTRAINT fk_material_curso FOREIGN KEY (id_detalle_asig_docente) REFERENCES detalle_asignatura_docente (Id_detalle_asig_docente)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
