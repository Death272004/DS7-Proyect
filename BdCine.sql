CREATE DATABASE IF NOT EXISTS bd_cine
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE bd_cine;

SET FOREIGN_KEY_CHECKS = 0;
DROP PROCEDURE IF EXISTS sp_buscar_contenidos;
DROP PROCEDURE IF EXISTS sp_contenidos_similares;
DROP VIEW IF EXISTS vista_catalogo;
DROP TABLE IF EXISTS historial_visualizaciones;
DROP TABLE IF EXISTS lista_personal;
DROP TABLE IF EXISTS calificaciones_usuarios;
DROP TABLE IF EXISTS usuarios_generos;
DROP TABLE IF EXISTS contenidos_generos;
DROP TABLE IF EXISTS contenidos;
DROP TABLE IF EXISTS generos;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  email VARCHAR(254) NOT NULL,
  contrasena_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin', 'estandar') NOT NULL DEFAULT 'estandar',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  ultimo_acceso DATETIME NULL,
  CONSTRAINT uq_usuarios_email UNIQUE (email),
  INDEX idx_usuarios_rol (rol),
  INDEX idx_usuarios_activo (activo)
) ENGINE=InnoDB;

CREATE TABLE generos (
  id_genero INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  slug VARCHAR(70) NOT NULL,
  emoji VARCHAR(10) NOT NULL DEFAULT '',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT uq_generos_nombre UNIQUE (nombre),
  CONSTRAINT uq_generos_slug UNIQUE (slug)
) ENGINE=InnoDB;

CREATE TABLE contenidos (
  id_contenido INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  tipo ENUM('pelicula', 'serie') NOT NULL,
  anio SMALLINT UNSIGNED NULL,
  duracion_minutos SMALLINT UNSIGNED NULL,
  calificacion DECIMAL(3,1) NOT NULL DEFAULT 0.0,
  sinopsis TEXT NOT NULL,
  imagen_url VARCHAR(500) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_contenidos_titulo (titulo),
  INDEX idx_contenidos_tipo (tipo),
  INDEX idx_contenidos_activo (activo),
  INDEX idx_contenidos_destacado (destacado),
  CONSTRAINT chk_contenidos_anio CHECK (anio IS NULL OR anio BETWEEN 1888 AND 2100),
  CONSTRAINT chk_contenidos_duracion CHECK (duracion_minutos IS NULL OR duracion_minutos BETWEEN 1 AND 999),
  CONSTRAINT chk_contenidos_calificacion CHECK (calificacion BETWEEN 0 AND 10)
) ENGINE=InnoDB;

CREATE TABLE contenidos_generos (
  id_contenido INT UNSIGNED NOT NULL,
  id_genero INT UNSIGNED NOT NULL,
  es_principal TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_contenido, id_genero),
  INDEX idx_contenidos_generos_genero (id_genero),
  CONSTRAINT fk_contenidos_generos_contenido
    FOREIGN KEY (id_contenido) REFERENCES contenidos(id_contenido)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_contenidos_generos_genero
    FOREIGN KEY (id_genero) REFERENCES generos(id_genero)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE usuarios_generos (
  id_usuario INT UNSIGNED NOT NULL,
  id_genero INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_genero),
  INDEX idx_usuarios_generos_genero (id_genero),
  CONSTRAINT fk_usuarios_generos_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_usuarios_generos_genero
    FOREIGN KEY (id_genero) REFERENCES generos(id_genero)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE calificaciones_usuarios (
  id_usuario INT UNSIGNED NOT NULL,
  id_contenido INT UNSIGNED NOT NULL,
  puntuacion DECIMAL(3,1) NOT NULL,
  comentario VARCHAR(500) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_contenido),
  INDEX idx_calificaciones_contenido (id_contenido),
  CONSTRAINT fk_calificaciones_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_calificaciones_contenido
    FOREIGN KEY (id_contenido) REFERENCES contenidos(id_contenido)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_calificaciones_puntuacion CHECK (puntuacion BETWEEN 0 AND 10)
) ENGINE=InnoDB;

CREATE TABLE lista_personal (
  id_usuario INT UNSIGNED NOT NULL,
  id_contenido INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_contenido),
  INDEX idx_lista_contenido (id_contenido),
  CONSTRAINT fk_lista_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_lista_contenido
    FOREIGN KEY (id_contenido) REFERENCES contenidos(id_contenido)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE historial_visualizaciones (
  id_historial BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_contenido INT UNSIGNED NOT NULL,
  visto_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_historial_usuario_fecha (id_usuario, visto_en),
  INDEX idx_historial_contenido (id_contenido),
  CONSTRAINT fk_historial_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_historial_contenido
    FOREIGN KEY (id_contenido) REFERENCES contenidos(id_contenido)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO usuarios (nombre, email, contrasena_hash, rol, activo) VALUES
('Administrador Cine', 'admin@cinematch.test', '$2y$10$z6QQVSnX/ibSjeWhYjgpteeS7wdZpacaY/4tcBcoXmotSS1yCKQMa', 'admin', 1),
('Usuario Demo', 'usuario@cinematch.test', '$2y$10$kJzhx6Stae84cd7uP3cNoOe7lGfjZ0zpdOUva3/eoHBTipE/6p3nW', 'estandar', 1);

INSERT INTO generos (nombre, slug, emoji) VALUES
('Accion', 'accion', '💥'),
('Comedia', 'comedia', '😂'),
('Drama', 'drama', '🎭'),
('Terror', 'terror', '👻'),
('Sci-Fi', 'sci-fi', '🚀'),
('Romance', 'romance', '💕'),
('Thriller', 'thriller', '🔪'),
('Animacion', 'animacion', '🎨'),
('Documental', 'documental', '🎥'),
('Fantasia', 'fantasia', '🧙'),
('Aventura', 'aventura', '🗺️'),
('Misterio', 'misterio', '🔍');

INSERT INTO contenidos
  (titulo, tipo, anio, duracion_minutos, calificacion, sinopsis, imagen_url, activo, destacado)
VALUES
('Interstellar', 'pelicula', 2014, 169, 8.7, 'Un grupo de exploradores viaja por el espacio para buscar un nuevo hogar para la humanidad.', NULL, 1, 1),
('Breaking Bad', 'serie', 2008, 47, 9.5, 'Un profesor de quimica se transforma en fabricante de metanfetamina tras recibir un diagnostico grave.', NULL, 1, 1),
('El Senor de los Anillos', 'pelicula', 2001, 178, 8.9, 'Un joven hobbit inicia una aventura para destruir un anillo capaz de dominar la Tierra Media.', NULL, 1, 1),
('Stranger Things', 'serie', 2016, 50, 8.7, 'Un grupo de amigos descubre sucesos sobrenaturales y secretos de laboratorio en su pequeno pueblo.', NULL, 1, 1),
('The Dark Knight', 'pelicula', 2008, 152, 9.0, 'Batman enfrenta al Joker, un criminal que busca llevar Ciudad Gotica al caos.', NULL, 1, 1),
('Friends', 'serie', 1994, 22, 8.9, 'Seis amigos viven relaciones, trabajos y momentos comicos en Nueva York.', NULL, 1, 1),
('Coco', 'pelicula', 2017, 105, 8.4, 'Miguel viaja al mundo de los muertos para descubrir la historia de su familia.', NULL, 1, 0),
('The Social Dilemma', 'pelicula', 2020, 94, 7.6, 'Especialistas explican como las redes sociales influyen en la conducta de sus usuarios.', NULL, 1, 0);

INSERT INTO contenidos_generos (id_contenido, id_genero, es_principal) VALUES
(1, 5, 1), (1, 3, 0), (1, 11, 0),
(2, 3, 1), (2, 7, 0),
(3, 10, 1), (3, 11, 0),
(4, 5, 1), (4, 4, 0), (4, 12, 0),
(5, 1, 1), (5, 7, 0), (5, 3, 0),
(6, 2, 1), (6, 6, 0),
(7, 8, 1), (7, 10, 0), (7, 11, 0),
(8, 9, 1);

INSERT INTO usuarios_generos (id_usuario, id_genero) VALUES
(2, 2), (2, 3), (2, 5), (2, 10);

INSERT INTO historial_visualizaciones (id_usuario, id_contenido, visto_en) VALUES
(2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 4, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 6, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO calificaciones_usuarios (id_usuario, id_contenido, puntuacion, comentario) VALUES
(2, 1, 9.0, 'Excelente recomendacion'),
(2, 4, 8.5, 'Muy buena serie');

CREATE VIEW vista_catalogo AS
SELECT
  c.id_contenido AS id,
  c.titulo,
  c.tipo,
  c.anio,
  c.duracion_minutos AS duracion,
  c.calificacion,
  c.sinopsis,
  COALESCE(c.imagen_url, '') AS imagen_url,
  c.activo,
  c.destacado,
  GROUP_CONCAT(g.nombre ORDER BY cg.es_principal DESC, g.nombre SEPARATOR ',') AS generos
FROM contenidos c
LEFT JOIN contenidos_generos cg ON cg.id_contenido = c.id_contenido
LEFT JOIN generos g ON g.id_genero = cg.id_genero
GROUP BY
  c.id_contenido, c.titulo, c.tipo, c.anio, c.duracion_minutos,
  c.calificacion, c.sinopsis, c.imagen_url, c.activo, c.destacado;

DELIMITER $$

CREATE PROCEDURE sp_buscar_contenidos(
  IN p_buscar VARCHAR(100),
  IN p_tipo VARCHAR(20),
  IN p_genero INT UNSIGNED
)
BEGIN
  SELECT vc.*
  FROM vista_catalogo vc
  WHERE vc.activo = 1
    AND (p_buscar IS NULL OR p_buscar = '' OR vc.titulo LIKE CONCAT('%', p_buscar, '%'))
    AND (p_tipo IS NULL OR p_tipo = '' OR p_tipo = 'todos' OR vc.tipo = p_tipo)
    AND (
      p_genero IS NULL OR p_genero = 0 OR EXISTS (
        SELECT 1
        FROM contenidos_generos cg
        WHERE cg.id_contenido = vc.id
          AND cg.id_genero = p_genero
      )
    )
  ORDER BY vc.destacado DESC, vc.calificacion DESC, vc.titulo ASC;
END$$

CREATE PROCEDURE sp_contenidos_similares(
  IN p_id_contenido INT UNSIGNED,
  IN p_limite INT UNSIGNED
)
BEGIN
  SELECT DISTINCT vc.*
  FROM vista_catalogo vc
  INNER JOIN contenidos_generos cg ON cg.id_contenido = vc.id
  WHERE vc.activo = 1
    AND vc.id <> p_id_contenido
    AND cg.id_genero IN (
      SELECT id_genero
      FROM contenidos_generos
      WHERE id_contenido = p_id_contenido
    )
  ORDER BY vc.calificacion DESC, vc.titulo ASC
  LIMIT p_limite;
END$$

DELIMITER ;
