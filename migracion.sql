-- ============================================================
-- MIGRACIÓN match_sport
-- Ejecutar UNA sola vez en phpMyAdmin (pestaña SQL) sobre la BD `match_sport`
-- que ya tienes importada con match_sport.sql.
--
-- Qué hace:
--   * Crea la tabla `categorias` (edad -> categoría) y `noticias` (blog).
--   * Relaciona con llaves foráneas: usuarios / horarios / calendario_clases
--     apuntan a `deportes` y `categorias` por id (antes eran textos sueltos).
--   * Limpia deportes duplicados y datos de plantilla del calendario.
-- ============================================================

USE match_sport;

-- ---------- 1. Categorías por edad ----------
CREATE TABLE categorias (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(50) NOT NULL,
  edad_min int(11) NOT NULL,
  edad_max int(11) DEFAULT NULL COMMENT 'NULL = sin límite superior',
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menores de 3 años no tienen categoría y la inscripción se rechaza.
INSERT INTO categorias (id, nombre, edad_min, edad_max) VALUES
(1, 'Iniciación / Baby', 3, 5),
(2, 'Pre-Benjamín', 6, 7),
(3, 'Benjamín', 8, 9),
(4, 'Pre-Infantil', 10, 11),
(5, 'Infantil', 12, 13),
(6, 'Menores', 14, 15),
(7, 'Juvenil', 16, 18),
(8, 'Mayores / Senior', 19, NULL);

-- ---------- 2. Deportes: quitar duplicados y evitar que vuelvan ----------
DELETE FROM deportes WHERE id IN (5, 6, 7, 8);
UPDATE deportes SET nombre = 'Voleibol' WHERE id = 2;
ALTER TABLE deportes ADD UNIQUE KEY uq_deportes_nombre (nombre);

-- ---------- 3. Usuarios (deportistas): deporte y categoría por id ----------
ALTER TABLE usuarios
  ADD COLUMN deporte_id int(11) NULL AFTER direccion,
  ADD COLUMN categoria_id int(11) NULL AFTER deporte_id;

UPDATE usuarios u JOIN deportes d ON d.nombre = u.deporte SET u.deporte_id = d.id;
UPDATE usuarios SET edad = TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) WHERE fecha_nacimiento IS NOT NULL;
UPDATE usuarios u JOIN categorias c ON u.edad >= c.edad_min AND (c.edad_max IS NULL OR u.edad <= c.edad_max)
  SET u.categoria_id = c.id;

ALTER TABLE usuarios
  DROP COLUMN deporte,
  MODIFY deporte_id int(11) NOT NULL,
  MODIFY categoria_id int(11) NOT NULL,
  ADD KEY deporte_id (deporte_id),
  ADD KEY categoria_id (categoria_id),
  ADD CONSTRAINT usuarios_ibfk_deporte FOREIGN KEY (deporte_id) REFERENCES deportes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT usuarios_ibfk_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- ---------- 4. Horarios por deporte + categoría ----------
ALTER TABLE horarios
  ADD COLUMN deporte_id int(11) NULL AFTER titulo,
  ADD COLUMN categoria_id int(11) NULL AFTER deporte_id;

UPDATE horarios h JOIN deportes d ON d.nombre = h.deporte SET h.deporte_id = d.id;
UPDATE horarios h JOIN categorias c ON c.nombre = h.categoria SET h.categoria_id = c.id;
DELETE FROM horarios WHERE deporte_id IS NULL OR categoria_id IS NULL;

ALTER TABLE horarios
  DROP COLUMN deporte,
  DROP COLUMN categoria,
  MODIFY deporte_id int(11) NOT NULL,
  MODIFY categoria_id int(11) NOT NULL,
  ADD KEY deporte_id (deporte_id),
  ADD KEY categoria_id (categoria_id),
  ADD CONSTRAINT horarios_ibfk_deporte FOREIGN KEY (deporte_id) REFERENCES deportes (id) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT horarios_ibfk_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE CASCADE;

-- ---------- 5. Calendario de clases ----------
-- Se borran las filas de plantilla (Danza, Yoga, Arte... duplicadas); se conservan las 2 de fútbol.
DELETE FROM calendario_clases WHERE id NOT IN (52, 53);

ALTER TABLE calendario_clases
  ADD COLUMN hora_t time NULL,
  ADD COLUMN deporte_id int(11) NULL COMMENT 'NULL = todos los deportes',
  ADD COLUMN categoria_id int(11) NULL COMMENT 'NULL = todas las categorías',
  ADD COLUMN profesor_id int(11) NULL;

UPDATE calendario_clases SET hora_t = '19:00:00', deporte_id = (SELECT id FROM deportes WHERE nombre = 'Futbol') WHERE id = 52;
UPDATE calendario_clases SET hora_t = '06:00:00', deporte_id = (SELECT id FROM deportes WHERE nombre = 'Futbol') WHERE id = 53;

ALTER TABLE calendario_clases DROP COLUMN hora, DROP COLUMN profesor;
ALTER TABLE calendario_clases
  CHANGE hora_t hora time NOT NULL,
  ADD KEY deporte_id (deporte_id),
  ADD KEY categoria_id (categoria_id),
  ADD KEY profesor_id (profesor_id),
  ADD CONSTRAINT calendario_ibfk_deporte FOREIGN KEY (deporte_id) REFERENCES deportes (id) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT calendario_ibfk_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT calendario_ibfk_profesor FOREIGN KEY (profesor_id) REFERENCES profesores (id) ON UPDATE CASCADE ON DELETE SET NULL;

-- ---------- 6. Profesores y avances ----------
ALTER TABLE profesores DROP COLUMN deporte;   -- no se usaba: los profesores ven todos los deportes

ALTER TABLE avances
  ADD COLUMN profesor_id int(11) NULL,
  DROP COLUMN creado_por,
  ADD KEY profesor_id (profesor_id),
  ADD CONSTRAINT avances_ibfk_profesor FOREIGN KEY (profesor_id) REFERENCES profesores (id) ON UPDATE CASCADE ON DELETE SET NULL;

-- ---------- 7. Noticias (blog) ----------
CREATE TABLE noticias (
  id int(11) NOT NULL AUTO_INCREMENT,
  titulo varchar(200) NOT NULL,
  contenido text NOT NULL,
  imagen varchar(255) DEFAULT '',
  admin_id int(11) DEFAULT NULL,
  fecha_publicacion timestamp NOT NULL DEFAULT current_timestamp(),
  fecha_actualizacion timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY admin_id (admin_id),
  CONSTRAINT noticias_ibfk_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 8. Recuperación de contraseña ----------
CREATE TABLE IF NOT EXISTS recuperacion_password (
  id int(11) NOT NULL AUTO_INCREMENT,
  rol enum('deportista','profesor','admin') NOT NULL,
  correo varchar(150) NOT NULL,
  token_hash char(64) NOT NULL,
  expira datetime NOT NULL,
  creado timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY token_hash (token_hash),
  KEY cuenta (rol, correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
