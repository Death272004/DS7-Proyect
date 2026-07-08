-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-07-2026 a las 12:50:43
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bd_cine`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_buscar_contenidos` (IN `p_buscar` VARCHAR(100), IN `p_tipo` VARCHAR(20), IN `p_genero` INT UNSIGNED)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_contenidos_similares` (IN `p_id_contenido` INT UNSIGNED, IN `p_limite` INT UNSIGNED)   BEGIN
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_usuarios`
--

CREATE TABLE `calificaciones_usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_contenido` int(10) UNSIGNED NOT NULL,
  `puntuacion` decimal(3,1) NOT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `calificaciones_usuarios`
--

INSERT INTO `calificaciones_usuarios` (`id_usuario`, `id_contenido`, `puntuacion`, `comentario`, `creado_en`, `actualizado_en`) VALUES
(2, 1, 9.0, 'Excelente recomendacion', '2026-06-28 17:58:13', NULL),
(2, 4, 8.5, 'Muy buena serie', '2026-06-28 17:58:13', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenidos`
--

CREATE TABLE `contenidos` (
  `id_contenido` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `tipo` enum('pelicula','serie') NOT NULL,
  `anio` smallint(5) UNSIGNED DEFAULT NULL,
  `duracion_minutos` smallint(5) UNSIGNED DEFAULT NULL,
  `calificacion` decimal(3,1) NOT NULL DEFAULT 0.0,
  `sinopsis` text NOT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `trailer_url` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `contenidos`
--

INSERT INTO `contenidos` (`id_contenido`, `titulo`, `tipo`, `anio`, `duracion_minutos`, `calificacion`, `sinopsis`, `imagen_url`, `trailer_url`, `activo`, `destacado`, `creado_en`, `actualizado_en`) VALUES
(1, 'Interstellar', 'pelicula', 2014, 169, 8.7, 'Un grupo de exploradores viaja por el espacio para buscar un nuevo hogar para la humanidad.', 'https://m.media-amazon.com/images/S/pv-target-images/4a38198dbdc5535e124d063718b6610e103f6965b7984b6377cceeb9e5fe8046.jpg', 'https://www.youtube.com/watch?v=zSWdZVtXT7E', 1, 1, '2026-06-28 17:58:13', '2026-07-07 01:53:18'),
(2, 'Breaking Bad', 'serie', 2008, 47, 9.5, 'Un profesor de quimica se transforma en fabricante de metanfetamina tras recibir un diagnostico grave.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRT07dETeDU3LQHPqOd6iuOsllGaxTO7KaAllqYGicy5lT1985JnDt9N0g&s=10', 'https://www.youtube.com/watch?v=HhesaQXLuRY', 1, 1, '2026-06-28 17:58:13', '2026-07-07 02:01:05'),
(3, 'El Senor de los Anillos', 'pelicula', 2001, 178, 8.9, 'Un joven hobbit inicia una aventura para destruir un anillo capaz de dominar la Tierra Media.', 'https://es.web.img2.acsta.net/c_310_420/medias/nmedia/18/89/67/45/20061512.jpg', 'https://www.youtube.com/watch?v=V75dMMIW2B4', 1, 1, '2026-06-28 17:58:13', '2026-07-07 02:00:37'),
(4, 'Stranger Things', 'serie', 2016, 50, 8.7, 'Un grupo de amigos descubre sucesos sobrenaturales y secretos de laboratorio en su pequeno pueblo.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRJFjadYKCdJrJz37NBXNDeZ4qwe3jsdsfnnQ11WiYhAPLmTHw1vFF3owY&s=10', 'https://www.youtube.com/watch?v=b9EkMc79ZSU', 1, 1, '2026-06-28 17:58:13', '2026-07-07 01:53:18'),
(5, 'The Dark Knight', 'pelicula', 2008, 152, 9.0, 'Batman enfrenta al Joker, un criminal que busca llevar Ciudad Gotica al caos.', 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_.jpg', 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 1, 1, '2026-06-28 17:58:13', '2026-07-07 01:59:31'),
(6, 'Friends', 'serie', 1994, 22, 8.9, 'Seis amigos viven relaciones, trabajos y momentos comicos en Nueva York.', 'https://m.media-amazon.com/images/M/MV5BOTU2YmM5ZjctOGVlMC00YTczLTljM2MtYjhlNGI5YWMyZjFkXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', 'https://www.youtube.com/watch?v=hDNNmeeJs1Q', 1, 1, '2026-06-28 17:58:13', '2026-07-07 01:53:18'),
(7, 'Coco', 'pelicula', 2017, 105, 8.4, 'Miguel viaja al mundo de los muertos para descubrir la historia de su familia.', 'https://lumiere-a.akamaihd.net/v1/images/p_coco_19736_fd5fa537.jpeg?region=0,0,540,810', 'https://www.youtube.com/watch?v=Ga6RYejo6Hk', 1, 0, '2026-06-28 17:58:13', '2026-07-07 01:53:18'),
(8, 'The Social Dilemma', 'pelicula', 2020, 94, 7.6, 'Especialistas explican como las redes sociales influyen en la conducta de sus usuarios.', 'https://miro.medium.com/v2/resize:fit:1400/1*CkdmUtjqOvkVIfrCpHiivQ.jpeg', 'https://www.youtube.com/watch?v=uaaC57tcci0', 1, 0, '2026-06-28 17:58:13', '2026-07-07 01:53:18'),
(9, 'Inception', 'pelicula', 2010, 148, 8.8, 'Un ladron experto en entrar en los sueños recibe la mision de implantar una idea imposible en la mente de un heredero.', 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg', 'https://www.youtube.com/watch?v=YoHD9XEInc0', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(10, 'The Matrix', 'pelicula', 1999, 136, 8.7, 'Un programador descubre que la realidad que conoce es una simulacion y se une a una rebelion contra las maquinas.', 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 'https://www.youtube.com/watch?v=vKQi3bBA1y8', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(11, 'Titanic', 'pelicula', 1997, 195, 7.9, 'Dos jovenes de mundos distintos viven un romance inolvidable durante el tragico viaje inaugural del Titanic.', 'https://image.tmdb.org/t/p/w500/9xjZS2rlVxm8SFx8kPC3aIGCOYQ.jpg', 'https://www.youtube.com/watch?v=kVrqfYjkTdQ', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(12, 'Avatar', 'pelicula', 2009, 162, 7.9, 'Un exmarine llega a Pandora y se debate entre cumplir su mision o proteger el mundo que empieza a amar.', 'https://image.tmdb.org/t/p/w500/kyeqWdyUXW608qlYkRqosgbbJyK.jpg', 'https://www.youtube.com/watch?v=5PSNL1qE6VY', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(13, 'Avengers: Endgame', 'pelicula', 2019, 181, 8.4, 'Los heroes supervivientes intentan revertir las consecuencias del chasquido y enfrentarse una ultima vez a Thanos.', 'https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg', 'https://www.youtube.com/watch?v=TcMBFSGVi1c', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(14, 'Jurassic Park', 'pelicula', 1993, 127, 8.2, 'Un parque tematico con dinosaurios clonados se convierte en una lucha por sobrevivir cuando falla la seguridad.', 'https://image.tmdb.org/t/p/w500/b1xCNnyrPebIc7EWNZIa6jhb1Ww.jpg', 'https://www.youtube.com/watch?v=lc0UehYemQA', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(15, 'Gladiator', 'pelicula', 2000, 155, 8.5, 'Un general romano traicionado se convierte en gladiador y busca justicia frente al emperador que destruyo su vida.', 'https://image.tmdb.org/t/p/w500/ty8TGRuvJLPUmAR1H1nRIsgwvim.jpg', 'https://www.youtube.com/watch?v=P5ieIbInFpg', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(16, 'Pulp Fiction', 'pelicula', 1994, 154, 8.9, 'Historias criminales cruzadas revelan violencia, humor negro y decisiones inesperadas en Los Angeles.', 'https://image.tmdb.org/t/p/w500/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 'https://www.youtube.com/watch?v=s7EdQ4FqbhY', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(17, 'Forrest Gump', 'pelicula', 1994, 142, 8.8, 'Un hombre de corazon puro atraviesa momentos clave de la historia mientras busca el amor y su lugar en el mundo.', 'https://image.tmdb.org/t/p/w500/arw2vcBveWOVZr6pxd9XTd1TdQa.jpg', 'https://www.youtube.com/watch?v=bLvqoHBptjg', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(18, 'The Godfather', 'pelicula', 1972, 175, 9.2, 'La familia Corleone enfrenta lealtades, poder y violencia en el mundo del crimen organizado.', 'https://image.tmdb.org/t/p/w500/3bhkrj58Vtu7enYsRolD1fZdja1.jpg', 'https://www.youtube.com/watch?v=sY1S34973zA', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(19, 'Spider-Man: Into the Spider-Verse', 'pelicula', 2018, 117, 8.4, 'Miles Morales descubre sus poderes y se une a heroes de otros universos para salvar la realidad.', 'https://image.tmdb.org/t/p/w500/iiZZdoQBEYBv6id8su7ImL0oCbD.jpg', 'https://www.youtube.com/watch?v=g4Hbz2jLxvQ', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(20, 'Toy Story', 'pelicula', 1995, 81, 8.3, 'Los juguetes de Andy cobran vida cuando nadie los ve y aprenden el valor de la amistad.', 'https://image.tmdb.org/t/p/w500/uXDfjJbdP4ijW5hWSBrPrlKpxab.jpg', 'https://www.youtube.com/watch?v=v-PjgYDrg70', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(21, 'Finding Nemo', 'pelicula', 2003, 100, 8.2, 'Un pez payaso cruza el oceano para encontrar a su hijo perdido con ayuda de una olvidadiza compañera.', 'https://image.tmdb.org/t/p/w500/eHuGQ10FUzK1mdOY69wF5pGgEf5.jpg', 'https://www.youtube.com/watch?v=wZdpNglLbt8', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(22, 'Parasite', 'pelicula', 2019, 132, 8.5, 'Una familia humilde se infiltra en la vida de una familia rica y desata consecuencias inesperadas.', 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'https://www.youtube.com/watch?v=5xH0HfJHsaY', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(23, 'La La Land', 'pelicula', 2016, 128, 8.0, 'Una actriz y un pianista persiguen sus sueños en Los Angeles mientras su relacion cambia con sus ambiciones.', 'https://image.tmdb.org/t/p/w500/uDO8zWDhfWwoFdKS4fzkUJt0Rf0.jpg', 'https://www.youtube.com/watch?v=0pdqf4P9MB8', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(24, 'Joker', 'pelicula', 2019, 122, 8.4, 'Un comediante marginado cae en una espiral de violencia y se transforma en un simbolo del caos.', 'https://image.tmdb.org/t/p/w500/udDclJoHjfjb8Ekgsd4FDteOkCU.jpg', 'https://www.youtube.com/watch?v=zAGVQLHvwOY', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(25, 'Game of Thrones', 'serie', 2011, 55, 9.2, 'Nobles familias luchan por el Trono de Hierro mientras una amenaza antigua despierta al norte.', 'https://image.tmdb.org/t/p/w500/1XS1oqL89opfnbLl8WnZY1O1uJx.jpg', 'https://www.youtube.com/watch?v=KPLWWIOCOOQ', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(26, 'The Last of Us', 'serie', 2023, 55, 8.7, 'Un sobreviviente y una joven cruzan un mundo devastado por una infeccion que cambio a la humanidad.', 'https://image.tmdb.org/t/p/w500/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg', 'https://www.youtube.com/watch?v=uLtkt8BonwM', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(27, 'The Crown', 'serie', 2016, 58, 8.6, 'La vida politica y personal de la reina Isabel II revela tensiones familiares, poder y cambios historicos.', 'https://image.tmdb.org/t/p/w500/1M876KPjulVwppEpldhdc8V4o68.jpg', 'https://www.youtube.com/watch?v=JWtnJjn6ng0', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(28, 'The Mandalorian', 'serie', 2019, 40, 8.6, 'Un cazarrecompensas solitario protege a una criatura especial mientras viaja por la galaxia.', 'https://image.tmdb.org/t/p/w500/eU1i6eHXlzMOlEq0ku1Rzq7Y4wA.jpg', 'https://www.youtube.com/watch?v=aOC8E8z_ifw', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(29, 'Black Mirror', 'serie', 2011, 60, 8.7, 'Historias independientes exploran los lados oscuros de la tecnologia y su impacto en la sociedad.', 'https://m.media-amazon.com/images/M/MV5BMGRjZDBjODMtMWQ1Zi00MWRkLTk5YTMtMDU1NTNkMzhkM2QwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', 'https://www.youtube.com/watch?v=jDiYGjp5E6E', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(30, 'The Office', 'serie', 2005, 22, 9.0, 'Un grupo de empleados vive situaciones incomodas y comicas dentro de una oficina de venta de papel.', 'https://image.tmdb.org/t/p/w500/qWnJzyZhyy74gjpSjIXWmuk0ifX.jpg', 'https://www.youtube.com/watch?v=LHOtME2DL4g', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(31, 'Dark', 'serie', 2017, 53, 8.7, 'La desaparicion de un niño revela secretos familiares y viajes temporales en un pueblo aleman.', 'https://image.tmdb.org/t/p/w500/apbrbWs8M9lyOpJYU5WXrpFbk1Z.jpg', 'https://www.youtube.com/watch?v=rrwycJ08PSA', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(32, 'The Boys', 'serie', 2019, 60, 8.7, 'Un grupo intenta exponer a superheroes corruptos protegidos por fama, poder y corporaciones.', 'https://image.tmdb.org/t/p/w500/stTEycfG9928HYGEISBFaG1ngjM.jpg', 'https://www.youtube.com/watch?v=M1bhOaLV4FU', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(33, 'The Witcher', 'serie', 2019, 60, 8.0, 'Un cazador de monstruos, una hechicera y una princesa quedan unidos por el destino en un mundo fantastico.', 'https://m.media-amazon.com/images/M/MV5BOTQzMzNmMzUtODgwNS00YTdhLTg5N2MtOWU1YTc4YWY3NjRlXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', 'https://www.youtube.com/watch?v=ndl1W4ltcmg', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(34, 'House of the Dragon', 'serie', 2022, 60, 8.4, 'La casa Targaryen enfrenta ambicion, herencia y fuego en una guerra por el poder.', 'https://image.tmdb.org/t/p/w500/z2yahl2uefxDCl0nogcRBstwruJ.jpg', 'https://www.youtube.com/watch?v=DotnJ7tTA34', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(35, 'Wednesday', 'serie', 2022, 45, 8.1, 'Wednesday Addams investiga misterios sobrenaturales mientras intenta adaptarse a una academia peculiar.', 'https://image.tmdb.org/t/p/w500/9PFonBhy4cQy7Jz20NpMygczOkv.jpg', 'https://www.youtube.com/watch?v=Di310WS8zLk', 1, 1, '2026-07-06 23:47:09', '2026-07-07 01:53:18'),
(36, 'The Queen\'s Gambit', 'serie', 2020, 60, 8.5, 'Una joven prodigio del ajedrez lucha contra sus adicciones mientras asciende en torneos internacionales.', 'https://upload.wikimedia.org/wikipedia/en/1/12/The_Queen%27s_Gambit_%28miniseries%29.png', 'https://www.youtube.com/watch?v=CDrieqwSdgI', 1, 0, '2026-07-06 23:47:09', '2026-07-07 01:53:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenidos_generos`
--

CREATE TABLE `contenidos_generos` (
  `id_contenido` int(10) UNSIGNED NOT NULL,
  `id_genero` int(10) UNSIGNED NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `contenidos_generos`
--

INSERT INTO `contenidos_generos` (`id_contenido`, `id_genero`, `es_principal`) VALUES
(1, 5, 1),
(2, 3, 1),
(3, 10, 1),
(4, 5, 1),
(5, 1, 1),
(6, 2, 1),
(7, 8, 1),
(8, 9, 1),
(9, 1, 0),
(9, 5, 1),
(9, 7, 0),
(10, 1, 0),
(10, 5, 1),
(11, 3, 0),
(11, 6, 1),
(12, 1, 0),
(12, 5, 1),
(12, 11, 0),
(13, 1, 1),
(13, 5, 0),
(13, 11, 0),
(14, 5, 0),
(14, 7, 0),
(14, 11, 1),
(15, 1, 1),
(15, 3, 0),
(16, 3, 0),
(16, 7, 1),
(17, 3, 1),
(17, 6, 0),
(18, 3, 1),
(18, 7, 0),
(19, 1, 0),
(19, 8, 1),
(19, 11, 0),
(20, 2, 0),
(20, 8, 1),
(20, 11, 0),
(21, 2, 0),
(21, 8, 1),
(21, 11, 0),
(22, 3, 1),
(22, 7, 0),
(23, 3, 0),
(23, 6, 1),
(24, 3, 1),
(24, 7, 0),
(25, 3, 0),
(25, 10, 1),
(25, 11, 0),
(26, 3, 1),
(26, 7, 0),
(26, 11, 0),
(27, 3, 1),
(28, 1, 0),
(28, 5, 1),
(28, 11, 0),
(29, 5, 1),
(30, 2, 1),
(31, 3, 0),
(31, 5, 1),
(31, 12, 0),
(32, 1, 1),
(32, 2, 0),
(32, 3, 0),
(33, 10, 1),
(34, 1, 0),
(34, 3, 0),
(34, 10, 1),
(35, 2, 0),
(35, 10, 0),
(35, 12, 1),
(36, 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `generos`
--

CREATE TABLE `generos` (
  `id_genero` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `slug` varchar(70) NOT NULL,
  `emoji` varchar(10) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `generos`
--

INSERT INTO `generos` (`id_genero`, `nombre`, `slug`, `emoji`, `activo`) VALUES
(1, 'Accion', 'accion', '💥', 1),
(2, 'Comedia', 'comedia', '😂', 1),
(3, 'Drama', 'drama', '🎭', 1),
(4, 'Terror', 'terror', '👻', 1),
(5, 'Sci-Fi', 'sci-fi', '🚀', 1),
(6, 'Romance', 'romance', '💕', 1),
(7, 'Thriller', 'thriller', '🔪', 1),
(8, 'Animacion', 'animacion', '🎨', 1),
(9, 'Documental', 'documental', '🎥', 1),
(10, 'Fantasia', 'fantasia', '🧙', 1),
(11, 'Aventura', 'aventura', '🗺️', 1),
(12, 'Misterio', 'misterio', '🔍', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_visualizaciones`
--

CREATE TABLE `historial_visualizaciones` (
  `id_historial` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_contenido` int(10) UNSIGNED NOT NULL,
  `visto_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `historial_visualizaciones`
--

INSERT INTO `historial_visualizaciones` (`id_historial`, `id_usuario`, `id_contenido`, `visto_en`) VALUES
(1, 2, 1, '2026-06-27 17:58:13'),
(2, 2, 4, '2026-06-26 17:58:13'),
(3, 2, 6, '2026-06-25 17:58:13'),
(4, 2, 3, '2026-07-06 20:13:06'),
(5, 2, 5, '2026-07-06 21:44:07'),
(6, 1, 8, '2026-07-06 21:59:53'),
(7, 3, 5, '2026-07-06 23:30:45'),
(8, 3, 5, '2026-07-06 23:30:48'),
(9, 3, 1, '2026-07-06 23:33:35'),
(10, 1, 29, '2026-07-06 23:51:51'),
(11, 1, 2, '2026-07-06 23:51:59'),
(12, 1, 18, '2026-07-06 23:52:01'),
(13, 1, 5, '2026-07-06 23:52:04'),
(14, 1, 18, '2026-07-06 23:52:07'),
(15, 3, 25, '2026-07-06 23:57:40'),
(16, 3, 3, '2026-07-06 23:59:49'),
(17, 3, 35, '2026-07-06 23:59:53'),
(18, 3, 3, '2026-07-06 23:59:56'),
(19, 3, 17, '2026-07-06 23:59:59'),
(20, 3, 18, '2026-07-07 00:00:03'),
(21, 3, 18, '2026-07-07 00:00:08'),
(22, 3, 17, '2026-07-07 00:08:39'),
(23, 3, 25, '2026-07-07 00:08:42'),
(24, 3, 7, '2026-07-07 00:09:19'),
(25, 3, 21, '2026-07-07 00:15:07'),
(26, 3, 32, '2026-07-07 00:15:24'),
(27, 1, 2, '2026-07-07 01:29:56'),
(28, 1, 29, '2026-07-07 01:30:42'),
(29, 1, 25, '2026-07-07 01:30:46'),
(30, 1, 18, '2026-07-07 01:30:51'),
(31, 1, 29, '2026-07-07 01:32:01'),
(32, 1, 9, '2026-07-07 01:32:56'),
(33, 1, 9, '2026-07-07 01:33:21'),
(34, 1, 9, '2026-07-07 01:33:24'),
(35, 1, 5, '2026-07-07 01:39:40'),
(36, 1, 25, '2026-07-07 01:39:52'),
(37, 1, 5, '2026-07-07 01:40:03'),
(38, 1, 2, '2026-07-07 01:40:04'),
(39, 1, 32, '2026-07-07 01:40:29'),
(40, 1, 3, '2026-07-07 01:40:43'),
(41, 1, 34, '2026-07-07 02:01:25'),
(42, 4, 32, '2026-07-07 02:02:44'),
(43, 4, 18, '2026-07-07 02:02:49'),
(44, 4, 25, '2026-07-07 02:02:51'),
(45, 4, 3, '2026-07-07 02:02:53'),
(46, 4, 25, '2026-07-07 02:02:54'),
(47, 4, 6, '2026-07-07 02:02:57'),
(48, 4, 29, '2026-07-07 02:03:02'),
(49, 4, 1, '2026-07-07 02:03:03'),
(50, 4, 24, '2026-07-07 02:04:15'),
(51, 4, 5, '2026-07-07 02:04:21'),
(52, 4, 6, '2026-07-07 02:04:27'),
(53, 4, 5, '2026-07-07 02:04:33'),
(54, 4, 13, '2026-07-07 02:06:02'),
(55, 4, 20, '2026-07-07 02:06:17'),
(56, 1, 33, '2026-07-07 02:08:15'),
(57, 1, 27, '2026-07-07 02:10:14'),
(58, 4, 12, '2026-07-07 05:47:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_personal`
--

CREATE TABLE `lista_personal` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_contenido` int(10) UNSIGNED NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `email` varchar(254) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `rol` enum('admin','estandar') NOT NULL DEFAULT 'estandar',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `email`, `contrasena_hash`, `rol`, `activo`, `creado_en`, `actualizado_en`, `ultimo_acceso`) VALUES
(1, 'Framefy Admin', 'admin@framefy.test', '$2y$10$z6QQVSnX/ibSjeWhYjgpteeS7wdZpacaY/4tcBcoXmotSS1yCKQMa', 'admin', 1, '2026-06-28 17:58:13', '2026-07-07 02:08:04', '2026-07-07 02:08:04'),
(2, 'Usuario', 'usuario@framefy.test', '$2y$10$kJzhx6Stae84cd7uP3cNoOe7lGfjZ0zpdOUva3/eoHBTipE/6p3nW', 'estandar', 1, '2026-06-28 17:58:13', '2026-07-07 01:38:51', '2026-07-06 23:29:10'),
(3, 'José Sánchez', 'jose@framefy.test', '$2y$10$8lSMJruexYpXUjHsSPaCn.T.QXV0/TViK2l54YZWwmJKt2EXpfan.', 'estandar', 1, '2026-07-06 23:29:46', '2026-07-06 23:56:42', '2026-07-06 23:56:42'),
(4, 'Alexs', 'alexs@framefy.test', '$2y$10$qcrhW10uJeV.jtFBOg2I.u9V/JpE3uEuwJZFXdNcj6RlCOV9Ocsrm', 'estandar', 1, '2026-07-07 02:02:19', '2026-07-07 05:46:49', '2026-07-07 05:46:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_generos`
--

CREATE TABLE `usuarios_generos` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_genero` int(10) UNSIGNED NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios_generos`
--

INSERT INTO `usuarios_generos` (`id_usuario`, `id_genero`, `creado_en`) VALUES
(1, 5, '2026-07-07 02:35:03'),
(1, 7, '2026-07-07 02:35:03'),
(1, 10, '2026-07-07 02:35:03'),
(1, 12, '2026-07-07 02:35:03'),
(2, 1, '2026-07-06 23:29:20'),
(2, 7, '2026-07-06 23:29:20'),
(3, 8, '2026-07-07 00:08:49'),
(4, 11, '2026-07-07 02:03:25');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_catalogo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_catalogo` (
`id` int(10) unsigned
,`titulo` varchar(200)
,`tipo` enum('pelicula','serie')
,`anio` smallint(5) unsigned
,`duracion` smallint(5) unsigned
,`calificacion` decimal(3,1)
,`sinopsis` text
,`imagen_url` varchar(500)
,`trailer_url` varchar(500)
,`activo` tinyint(1)
,`destacado` tinyint(1)
,`generos` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_catalogo`
--
DROP TABLE IF EXISTS `vista_catalogo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_catalogo`  AS SELECT `c`.`id_contenido` AS `id`, `c`.`titulo` AS `titulo`, `c`.`tipo` AS `tipo`, `c`.`anio` AS `anio`, `c`.`duracion_minutos` AS `duracion`, `c`.`calificacion` AS `calificacion`, `c`.`sinopsis` AS `sinopsis`, coalesce(`c`.`imagen_url`,'') AS `imagen_url`, coalesce(`c`.`trailer_url`,'') AS `trailer_url`, `c`.`activo` AS `activo`, `c`.`destacado` AS `destacado`, group_concat(`g`.`nombre` order by `cg`.`es_principal` DESC,`g`.`nombre` ASC separator ',') AS `generos` FROM ((`contenidos` `c` left join `contenidos_generos` `cg` on(`cg`.`id_contenido` = `c`.`id_contenido`)) left join `generos` `g` on(`g`.`id_genero` = `cg`.`id_genero`)) GROUP BY `c`.`id_contenido`, `c`.`titulo`, `c`.`tipo`, `c`.`anio`, `c`.`duracion_minutos`, `c`.`calificacion`, `c`.`sinopsis`, `c`.`imagen_url`, `c`.`trailer_url`, `c`.`activo`, `c`.`destacado` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calificaciones_usuarios`
--
ALTER TABLE `calificaciones_usuarios`
  ADD PRIMARY KEY (`id_usuario`,`id_contenido`),
  ADD KEY `idx_calificaciones_contenido` (`id_contenido`);

--
-- Indices de la tabla `contenidos`
--
ALTER TABLE `contenidos`
  ADD PRIMARY KEY (`id_contenido`),
  ADD KEY `idx_contenidos_titulo` (`titulo`),
  ADD KEY `idx_contenidos_tipo` (`tipo`),
  ADD KEY `idx_contenidos_activo` (`activo`),
  ADD KEY `idx_contenidos_destacado` (`destacado`);

--
-- Indices de la tabla `contenidos_generos`
--
ALTER TABLE `contenidos_generos`
  ADD PRIMARY KEY (`id_contenido`,`id_genero`),
  ADD KEY `idx_contenidos_generos_genero` (`id_genero`);

--
-- Indices de la tabla `generos`
--
ALTER TABLE `generos`
  ADD PRIMARY KEY (`id_genero`),
  ADD UNIQUE KEY `uq_generos_nombre` (`nombre`),
  ADD UNIQUE KEY `uq_generos_slug` (`slug`);

--
-- Indices de la tabla `historial_visualizaciones`
--
ALTER TABLE `historial_visualizaciones`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `idx_historial_usuario_fecha` (`id_usuario`,`visto_en`),
  ADD KEY `idx_historial_contenido` (`id_contenido`);

--
-- Indices de la tabla `lista_personal`
--
ALTER TABLE `lista_personal`
  ADD PRIMARY KEY (`id_usuario`,`id_contenido`),
  ADD KEY `idx_lista_contenido` (`id_contenido`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_rol` (`rol`),
  ADD KEY `idx_usuarios_activo` (`activo`);

--
-- Indices de la tabla `usuarios_generos`
--
ALTER TABLE `usuarios_generos`
  ADD PRIMARY KEY (`id_usuario`,`id_genero`),
  ADD KEY `idx_usuarios_generos_genero` (`id_genero`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contenidos`
--
ALTER TABLE `contenidos`
  MODIFY `id_contenido` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `generos`
--
ALTER TABLE `generos`
  MODIFY `id_genero` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `historial_visualizaciones`
--
ALTER TABLE `historial_visualizaciones`
  MODIFY `id_historial` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calificaciones_usuarios`
--
ALTER TABLE `calificaciones_usuarios`
  ADD CONSTRAINT `fk_calificaciones_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_calificaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `contenidos_generos`
--
ALTER TABLE `contenidos_generos`
  ADD CONSTRAINT `fk_contenidos_generos_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contenidos_generos_genero` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_visualizaciones`
--
ALTER TABLE `historial_visualizaciones`
  ADD CONSTRAINT `fk_historial_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `lista_personal`
--
ALTER TABLE `lista_personal`
  ADD CONSTRAINT `fk_lista_contenido` FOREIGN KEY (`id_contenido`) REFERENCES `contenidos` (`id_contenido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lista_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios_generos`
--
ALTER TABLE `usuarios_generos`
  ADD CONSTRAINT `fk_usuarios_generos_genero` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_generos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
