-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 12, 2018 at 04:22 PM
-- Server version: 5.5.58-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `KIPSTER`
--

-- --------------------------------------------------------

--
-- Table structure for table `ALERTS`
--

CREATE TABLE IF NOT EXISTS `ALERTS` (
  `HOSTSID` int(11) NOT NULL,
  `PEOPLESID` int(11) NOT NULL,
  `TEXT` tinyint(1) NOT NULL,
  PRIMARY KEY (`HOSTSID`,`PEOPLESID`),
  KEY `PEOPLESID` (`PEOPLESID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `CARRIERS`
--

CREATE TABLE IF NOT EXISTS `CARRIERS` (
  `CARRIERNAME` varchar(255) NOT NULL,
  `CARRIEREMAIL` varchar(255) NOT NULL,
  PRIMARY KEY (`CARRIERNAME`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `CARRIERS`
--

INSERT INTO `CARRIERS` (`CARRIERNAME`, `CARRIEREMAIL`) VALUES
('Alltel', '@message.alltel.com'),
('AT&T', '@txt.att.net'),
('Boost', '@myboostmobile.com'),
('Republic Wireless', '@text.republicwireless.com'),
('Sprint', '@messaging.sprintpcs.com'),
('T-Mobile', '@tmomail.net'),
('U.S. Cellular', '@email.uscc.net'),
('Verizon', '@vtext.com'),
('Virgin Mobile', '@vmobl.com');

-- --------------------------------------------------------

--
-- Table structure for table `HOSTRELATIONS`
--

CREATE TABLE IF NOT EXISTS `HOSTRELATIONS` (
  `ID` int(11) NOT NULL,
  `PARENT` int(11) NOT NULL,
  PRIMARY KEY (`ID`,`PARENT`),
  KEY `PARENT` (`PARENT`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `HOSTS`
--

CREATE TABLE IF NOT EXISTS `HOSTS` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `IPADDRESS` varchar(50) NOT NULL,
  `UP` tinyint(1) NOT NULL DEFAULT '1',
  `DOWNTIME` datetime DEFAULT NULL,
  `ALERTSENT` tinyint(1) NOT NULL DEFAULT '0',
  `ALERTTIME` int(3) DEFAULT '5',
  `SITE` int(11) NOT NULL,
  `TYPE` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `SITE` (`SITE`),
  KEY `TYPE` (`TYPE`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Table structure for table `HOSTTYPES`
--

CREATE TABLE IF NOT EXISTS `HOSTTYPES` (
  `TYPE_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `MEDIA` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`TYPE_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Dumping data for table `HOSTTYPES`
--

INSERT INTO `HOSTTYPES` (`TYPE_ID`, `NAME`, `MEDIA`) VALUES
(1, 'Access Point', 'accesspoint.png'),
(2, 'Router', 'router.png'),
(3, 'Firewall', 'firewall.png'),
(4, 'Server', 'server.png'),
(5, 'Switch', 'switch.png'),
(6, 'PC', 'computer.png'),
(7, 'Other', 'chip.png');

-- --------------------------------------------------------

--
-- Table structure for table `PEOPLES`
--

CREATE TABLE IF NOT EXISTS `PEOPLES` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `PHONENUMBER` bigint(20) DEFAULT NULL,
  `EMAIL` varchar(255) DEFAULT NULL,
  `CARRIER` varchar(255) DEFAULT NULL,
  `PASSWORD` text,
  `USERNAME` varchar(255) DEFAULT NULL,
  `LOGIN_ACTIVE` tinyint(1) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `USERNAME` (`USERNAME`),
  KEY `CARRIER` (`CARRIER`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Dumping data for table `HOSTTYPES`
--

INSERT INTO `PEOPLES` (`ID`, `NAME`, `LOGIN_ACTIVE`) VALUES
(0, 'admin', 0);

-- --------------------------------------------------------

--
-- Table structure for table `SITES`
--

CREATE TABLE IF NOT EXISTS `SITES` (
  `SITE_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  PRIMARY KEY (`SITE_ID`),
  UNIQUE KEY `NAME` (`NAME`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ALERTS`
--
ALTER TABLE `ALERTS`
  ADD CONSTRAINT `ALERTS_ibfk_1` FOREIGN KEY (`HOSTSID`) REFERENCES `HOSTS` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `ALERTS_ibfk_2` FOREIGN KEY (`PEOPLESID`) REFERENCES `PEOPLES` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `HOSTRELATIONS`
--
ALTER TABLE `HOSTRELATIONS`
  ADD CONSTRAINT `HOSTRELATIONS_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `HOSTS` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `HOSTRELATIONS_ibfk_2` FOREIGN KEY (`PARENT`) REFERENCES `HOSTS` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `HOSTS`
--
ALTER TABLE `HOSTS`
  ADD CONSTRAINT `HOSTS_ibfk_2` FOREIGN KEY (`TYPE`) REFERENCES `HOSTTYPES` (`TYPE_ID`),
  ADD CONSTRAINT `HOSTS_ibfk_1` FOREIGN KEY (`SITE`) REFERENCES `SITES` (`SITE_ID`);

--
-- Constraints for table `PEOPLES`
--
ALTER TABLE `PEOPLES`
  ADD CONSTRAINT `PEOPLES_ibfk_1` FOREIGN KEY (`CARRIER`) REFERENCES `CARRIERS` (`CARRIERNAME`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
