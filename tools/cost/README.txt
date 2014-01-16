
I started looking into how to optimally buy parts from bricklink stores to get them at the cheapest cost.
This was an attempt to complete sets where you own some portion of the parts already.
In the end I found it is easier/cheaper to just buy the set.


-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 05, 2013 at 11:13 AM
-- Server version: 5.5.29
-- PHP Version: 5.4.6-1ubuntu1.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dwalto76_lego`
--

-- --------------------------------------------------------

--
-- Table structure for table `bricklink_inventory`
--

CREATE TABLE IF NOT EXISTS `bricklink_inventory` (
  `id` varchar(32) NOT NULL,
  `brick_store_id` varchar(32) NOT NULL,
  `store` varchar(64) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `min_buy` int(10) unsigned NOT NULL,
  `new_or_used` char(4) NOT NULL,
  PRIMARY KEY (`id`,`store`,`new_or_used`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

