-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 29, 2016 at 05:39 AM
-- Server version: 5.5.49-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `detonando`
--

-- --------------------------------------------------------

--
-- Table structure for table `familiares`
--

CREATE TABLE IF NOT EXISTS `familiares` (
  `user_id` varchar(16) COLLATE utf8_bin NOT NULL,
  `nombre` varchar(255) COLLATE utf8_bin NOT NULL,
  `parentesco` varchar(45) COLLATE utf8_bin NOT NULL,
  `sexo` enum('M','F') COLLATE utf8_bin NOT NULL DEFAULT 'M',
  PRIMARY KEY (`user_id`,`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `familiares`
--

INSERT INTO `familiares` (`user_id`, `nombre`, `parentesco`, `sexo`) VALUES
('00001', 'Hijo 1 1', 'Hijo', 'M'),
('00001', 'Madre 1 1', 'Madre', 'F');

-- --------------------------------------------------------

--
-- Table structure for table `kiosco`
--

CREATE TABLE IF NOT EXISTS `kiosco` (
  `id` varchar(128) COLLATE utf8_bin NOT NULL,
  `llave` varchar(512) COLLATE utf8_bin NOT NULL DEFAULT '',
  `descripcion` text COLLATE utf8_bin,
  `isAdmin` tinyint(1) DEFAULT '0' COMMENT 'Si es admin o solo para transacciones ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `kiosco`
--

INSERT INTO `kiosco` (`id`, `llave`, `descripcion`, `isAdmin`) VALUES
('00001', '4c68cea7e58591b579fd074bcdaff740', 'kiosco de prueba', 1);

-- --------------------------------------------------------

--
-- Table structure for table `oficios`
--

CREATE TABLE IF NOT EXISTS `oficios` (
  `oficio` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_id` varchar(16) COLLATE utf8_bin NOT NULL,
  KEY `fk_oficios_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `transacciones`
--

CREATE TABLE IF NOT EXISTS `transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concepto` varchar(45) COLLATE utf8_bin NOT NULL,
  `monto` varchar(45) COLLATE utf8_bin NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `kiosco_id` varchar(128) COLLATE utf8_bin NOT NULL,
  `emisor_id` varchar(16) COLLATE utf8_bin NOT NULL,
  `receptor_id` varchar(16) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_table1_user_idx` (`emisor_id`),
  KEY `fk_table1_user1_idx` (`receptor_id`),
  KEY `fk_transacciones_table11_idx` (`kiosco_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=4 ;

--
-- Dumping data for table `transacciones`
--

INSERT INTO `transacciones` (`id`, `concepto`, `monto`, `fecha`, `kiosco_id`, `emisor_id`, `receptor_id`) VALUES
(1, 'Servicios', '500', '2016-06-29 08:01:26', '00001', '00001', '00002'),
(2, 'Servicios', '250', '2016-06-29 08:04:58', '00001', '00002', '00001'),
(3, 'Servicios', '250', '2016-06-29 08:06:30', '00001', '00002', '00001');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` varchar(16) COLLATE utf8_bin NOT NULL,
  `tablet_id` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `password` varchar(32) COLLATE utf8_bin NOT NULL,
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(45) COLLATE utf8_bin NOT NULL,
  `ap_paterno` varchar(45) COLLATE utf8_bin NOT NULL,
  `ap_materno` varchar(45) COLLATE utf8_bin NOT NULL,
  `saldo` decimal(10,0) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `tablet_id`, `password`, `create_time`, `nombre`, `ap_paterno`, `ap_materno`, `saldo`) VALUES
('00001', NULL, 'd375227be8392b5736c9611fb2dc544b', '2016-06-29 00:51:33', 'Oscar', 'Escamilla', 'González', 1000),
('00002', NULL, 'd375227be8392b5736c9611fb2dc544b', '2016-06-29 00:51:33', 'Oscar22', 'Escamilla22', 'González22', 1000);

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
--

CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int(11) NOT NULL,
  `password` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `nombre` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `ap_paterno` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `ap_materno` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `saldo` decimal(10,0) DEFAULT '1000',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `familiares`
--
ALTER TABLE `familiares`
  ADD CONSTRAINT `fk_familiares_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `oficios`
--
ALTER TABLE `oficios`
  ADD CONSTRAINT `fk_oficios_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `fk_table1_user` FOREIGN KEY (`emisor_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_table1_user1` FOREIGN KEY (`receptor_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_transacciones_table11` FOREIGN KEY (`kiosco_id`) REFERENCES `kiosco` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
