-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tiempo de generación: 24-04-2011 a las 23:16:37
-- Versión del servidor: 5.1.36
-- Versión de PHP: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `goteo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `about` text COLLATE utf8_unicode_ci,
  `keywords` tinytext COLLATE utf8_unicode_ci,
  `active` tinyint(1) NOT NULL,
  `avatar` int(11) DEFAULT NULL,
  `contribution` text COLLATE utf8_unicode_ci,
  `twitter` tinytext COLLATE utf8_unicode_ci,
  `facebook` tinytext COLLATE utf8_unicode_ci,
  `linkedin` tinytext COLLATE utf8_unicode_ci,
  `worth` int(7) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_FK` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcar la base de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `role_id`, `name`, `email`, `password`, `about`, `keywords`, `active`, `avatar`, `contribution`, `twitter`, `facebook`, `linkedin`, `worth`, `created`, `modified`) VALUES
('pepa', 3, 'Pepa PÃ©rez', 'josefa@doukeshi.org', 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', 'Yo soy JOSEFAAAA!!!!!', NULL, 1, 0, 'mucho arte', '@josefa', 'feisbuc.com/josefaaaaa', 'ein?', NULL, '2011-03-19 00:00:00', '2011-04-03 01:43:01'),
('pepe', 2, 'pepe', 'asdf', 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2011-03-19 00:00:00', '2011-04-03 01:42:57'),
('pepo', 0, 'pepo', 'pepe@doukeshi.org', '51abb9636078defbf888d8457a7c76f85c8f114c', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', '2011-04-10 02:05:52'),
('root', 1, 'Super administradorrrrr', 'goteo@doukeshi.org', 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', 'Super administrador de la plataforma Goteo.org', NULL, 1, 2, 'Super administrador de la plataforma Goteo.org', 'asdasd', 'eres una cerda de mierda', 'asdasdasd', NULL, '2011-03-16 00:00:00', '2011-04-25 01:15:36');
