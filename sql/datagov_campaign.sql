-- phpMyAdmin SQL Dump
-- version 3.5.4
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2013 at 02:46 PM
-- Server version: 5.5.28
-- PHP Version: 5.3.26

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `farm`
--

-- --------------------------------------------------------

--
-- Table structure for table `datagov_campaign`
--

CREATE TABLE IF NOT EXISTS `datagov_campaign` (
  `office_id` int(10) NOT NULL,
  `contact_name` text,
  `contact_email` text,
  `datajson_status` longblob,
  `datapage_status` longblob,
  `digitalstrategy_status` longblob,
  `datagov_harvest` text,
  `inventory_posted` text,
  `inventory_superset` text,
  `datajson_posted` text,
  `datajson_slashdata` text,
  `feedback` text,
  `schedule_posted` text,
  `publication_process_posted` text,
  PRIMARY KEY (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
