-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2025 at 04:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fashionshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(191) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `line1` varchar(255) NOT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `district` varchar(191) NOT NULL,
  `province` varchar(191) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'admin@example.com', '223344', '2025-10-06 16:16:17'),
(2, 'admin2@example.com', '445566', '2025-10-06 16:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(2, 'Hit'),
(1, 'New'),
(6, 'กางเกง'),
(7, 'นิต/ถัก'),
(3, 'เดรส'),
(4, 'เสื้อยืด'),
(5, 'เสื้อเชิ้ต');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  `failed_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `email`, `password_hash`, `name`, `created_at`, `last_login_at`, `failed_attempts`, `locked_until`, `phone`, `address`) VALUES
(1, 'ppnaka@gmail.com', '$2y$10$0YH0y8uQibX0jAATxeS64.ISakSz/XIUJdsK.V9xffbZYH314.3UK', 'pp naka', '2025-10-22 02:29:34', NULL, 0, NULL, NULL, NULL),
(2, 'nnn@gmail.com', '$2y$10$ghXM6RsHZp7eLPOBkuU9O.K2Yst/PjBBRzLcEhxuFBZH32EfqPj7K', 'nnn naka', '2025-10-22 17:22:39', NULL, 0, NULL, NULL, NULL),
(3, 'anon@gmail.com', '$2y$10$px5MYRV4q3PelOMam/0LVu/jQDHLdcquQuQUCIPDInCA1pW6j9hqK', 'Anon', '2025-10-22 23:46:36', NULL, 0, NULL, NULL, NULL),
(5, 'yongyong@gmail.com', '$2y$10$56FIK4273kHTtJX0RihTGeouVJd2CkvlCwbPbSf./WORflAwhNkiC', 'yongyong', '2025-10-29 16:39:32', NULL, 0, NULL, NULL, NULL),
(6, 'thipnaree@gmail.com', '$2y$10$Bp.gn8h9nWAyxd1QZwmFteZR/r1g4OtCDZu0mx2SXY2F6JRcVBUzi', 'thipnaree', '2025-10-29 17:56:12', NULL, 0, NULL, NULL, NULL),
(7, 'pam@gmail.com', '$2y$10$VR2HA79vxXjvxqR5IlI2IuGEinBIbuBVcDYU0kONi4O82lnJnzdeG', 'เเพม', '2025-10-29 20:05:47', NULL, 0, NULL, NULL, NULL),
(8, 'noon@gmail.com', '$2y$10$O6Z8QtZLGlVQ13zAx.MRqOnrbJX4QmHGtCW658KP1hwjXqD631bDm', 'นุ่นนิ่น', '2025-10-29 20:12:12', NULL, 0, NULL, NULL, NULL),
(9, 'patong@gmail.com', '$2y$10$J65R4dBHHtRz8hc0/WJc6Oz8jwU6KHoO7Gm/8TAuwS.tbJkO1dEuG', 'patong', '2025-10-29 20:19:22', NULL, 0, NULL, NULL, NULL),
(10, 'happy@gmail.com', '$2y$10$Idt9GTsyrS.Iu8PUOvrcjOzFPPZ4k3iUw2fzxnZxFA214DQcYg09S', 'เเฮปปี้', '2025-10-30 00:11:38', NULL, 0, NULL, NULL, NULL),
(11, 'juthamaspromwong@gmail.com', '$2y$10$wko6zcNdur59VKIXRF1O2OkFuPjYYTr4nGUxAME1wHun/Lj.uKR4G', 'juthamas', '2025-10-30 14:06:39', NULL, 0, NULL, NULL, NULL),
(12, '65010914605@msu.ac.th', '$2y$10$mRvxcaecpPZ4G0Gez3Eyn.6U0x5egYy4XR3mcrNrZP.Td9CoIThbC', 'namทร', '2025-10-31 09:51:01', NULL, 0, NULL, NULL, NULL),
(13, 'pp@gmil.com', '$2y$10$SJsOgHVrgmW/GnAvbR2OtuV4cpxDxFH8Alg7WauK3sCF3sOr0y456', 'จุฑามาศ', '2025-10-31 15:14:16', NULL, 0, NULL, NULL, NULL),
(14, 'pppp@gmail.com', '$2y$10$BhJlERx4OOxZABaNZ6Wbee6ih3dLs18l5en3z6POeOtjU6wwGyEcS', 'pp', '2025-10-31 19:17:35', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `product_id` varchar(191) NOT NULL,
  `color` varchar(64) NOT NULL DEFAULT '',
  `size` varchar(8) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`product_id`, `color`, `size`, `qty`, `created_at`) VALUES
('10', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('10', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('10', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('10', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('10', 'ดำ', 'XL', 15, '2025-10-19 11:46:27'),
('11', 'ขาว', '', 0, '2025-10-19 12:17:51'),
('11', 'ขาว', 'L', 15, '2025-10-19 11:46:27'),
('11', 'ขาว', 'M', 15, '2025-10-19 11:46:27'),
('11', 'ขาว', 'S', 15, '2025-10-19 11:46:27'),
('11', 'ชมพู', '', 0, '2025-10-19 12:17:51'),
('11', 'ชมพู', 'L', 15, '2025-10-19 11:46:27'),
('11', 'ชมพู', 'M', 15, '2025-10-19 11:46:27'),
('11', 'ชมพู', 'S', 15, '2025-10-19 11:46:27'),
('11', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('11', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('11', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('11', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('12', 'กรม', '', 0, '2025-10-19 12:17:51'),
('12', 'กรม', 'L', 15, '2025-10-19 11:46:27'),
('12', 'กรม', 'M', 15, '2025-10-19 11:46:27'),
('12', 'กรม', 'S', 15, '2025-10-19 11:46:27'),
('12', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('12', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('12', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('12', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('13', 'กรม', '', 0, '2025-10-19 12:17:51'),
('13', 'กรม', 'L', 12, '2025-10-19 11:46:27'),
('13', 'กรม', 'M', 15, '2025-10-19 11:46:27'),
('13', 'กรม', 'S', 15, '2025-10-19 11:46:27'),
('13', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('13', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('13', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('13', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('13', 'เทา', '', 0, '2025-10-19 12:17:51'),
('13', 'เทา', 'L', 15, '2025-10-19 11:46:27'),
('13', 'เทา', 'M', 15, '2025-10-19 11:46:27'),
('13', 'เทา', 'S', 15, '2025-10-19 11:46:27'),
('13', 'เทา กรม ดำ', 'L', 0, '2025-10-29 13:14:13'),
('13', 'เทา กรม ดำ', 'S', 0, '2025-10-29 12:58:01'),
('4', 'ขาว', '', 0, '2025-10-19 12:17:50'),
('4', 'ขาว', 'L', 15, '2025-10-19 11:46:26'),
('4', 'ขาว', 'M', 15, '2025-10-19 11:46:26'),
('4', 'ขาว', 'S', 15, '2025-10-19 11:46:26'),
('4', 'ชมพู', '', 0, '2025-10-19 12:17:50'),
('4', 'ชมพู', 'L', 15, '2025-10-19 11:46:26'),
('4', 'ชมพู', 'M', 15, '2025-10-19 11:46:26'),
('4', 'ชมพู', 'S', 15, '2025-10-19 11:46:26'),
('4', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('4', 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
('4', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('4', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('5', 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', 'XL', 15, '2025-10-19 11:46:26'),
('5', 'แดง', '', 0, '2025-10-19 12:17:50'),
('5', 'แดง', 'L', 15, '2025-10-19 11:46:26'),
('5', 'แดง', 'M', 15, '2025-10-19 11:46:26'),
('5', 'แดง', 'S', 15, '2025-10-19 11:46:26'),
('5', 'แดง', 'XL', 15, '2025-10-19 11:46:26'),
('6', 'กรม', 'L', 0, '2025-10-21 11:41:27'),
('6', 'กรม', 'M', 0, '2025-10-21 11:40:03'),
('6', 'กรม', 'S', 0, '2025-10-21 11:40:53'),
('6', 'ดำ', 'L', 0, '2025-10-21 11:42:50'),
('6', 'ดำ', 'M', 0, '2025-10-21 11:42:32'),
('6', 'ดำ', 'S', 0, '2025-10-21 11:42:15'),
('6', 'ดำ กรม', 'XL', 0, '2025-10-29 17:14:03'),
('7', 'ขาว', '', 0, '2025-10-19 12:17:50'),
('7', 'ขาว', 'L', 15, '2025-10-19 11:46:26'),
('7', 'ขาว', 'M', 15, '2025-10-19 11:46:26'),
('7', 'ขาว', 'S', 15, '2025-10-19 11:46:26'),
('7', 'ขาว ดำ', 'S', -1, '2025-10-29 12:57:55'),
('7', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('7', 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
('7', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('7', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('8', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('8', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('8', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('8', 'น้ำตาล', '', 0, '2025-10-19 12:17:50'),
('8', 'น้ำตาล', 'M', 15, '2025-10-19 11:46:26'),
('8', 'น้ำตาล', 'S', 15, '2025-10-19 11:46:26'),
('9', 'ขาว', '', 0, '2025-10-19 12:17:50'),
('9', 'ขาว', 'L', 15, '2025-10-19 11:46:27'),
('9', 'ขาว', 'M', 15, '2025-10-19 11:46:27'),
('9', 'ขาว', 'S', 15, '2025-10-19 11:46:27'),
('9', 'ขาว', 'XL', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('9', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', 'XL', 15, '2025-10-19 11:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_backup`
--

CREATE TABLE `inventory_backup` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `qty` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_backup`
--

INSERT INTO `inventory_backup` (`id`, `product_id`, `color`, `size`, `qty`, `created_at`) VALUES
(1, 4, 'ดำ', '', 0, '2025-10-19 12:17:50'),
(2, 4, 'ขาว', '', 0, '2025-10-19 12:17:50'),
(3, 4, 'ชมพู', '', 0, '2025-10-19 12:17:50'),
(4, 5, 'ดำ', '', 0, '2025-10-19 12:17:50'),
(5, 5, 'แดง', '', 0, '2025-10-19 12:17:50'),
(6, 7, 'ดำ', '', 0, '2025-10-19 12:17:50'),
(7, 7, 'ขาว', '', 0, '2025-10-19 12:17:50'),
(8, 8, 'น้ำตาล', '', 0, '2025-10-19 12:17:50'),
(9, 8, 'ดำ', '', 0, '2025-10-19 12:17:50'),
(10, 9, 'ขาว', '', 0, '2025-10-19 12:17:50'),
(11, 9, 'ดำ', '', 0, '2025-10-19 12:17:50'),
(12, 10, 'ดำ', '', 0, '2025-10-19 12:17:51'),
(13, 11, 'ขาว', '', 0, '2025-10-19 12:17:51'),
(14, 11, 'ดำ', '', 0, '2025-10-19 12:17:51'),
(15, 11, 'ชมพู', '', 0, '2025-10-19 12:17:51'),
(16, 12, 'ดำ', '', 0, '2025-10-19 12:17:51'),
(17, 12, 'กรม', '', 0, '2025-10-19 12:17:51'),
(18, 13, 'ดำ', '', 0, '2025-10-19 12:17:51'),
(19, 13, 'กรม', '', 0, '2025-10-19 12:17:51'),
(20, 13, 'เทา', '', 0, '2025-10-19 12:17:51'),
(25, 4, 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
(26, 4, 'ขาว', 'S', 15, '2025-10-19 11:46:26'),
(27, 4, 'ชมพู', 'S', 15, '2025-10-19 11:46:26'),
(28, 4, 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
(29, 4, 'ขาว', 'M', 15, '2025-10-19 11:46:26'),
(30, 4, 'ชมพู', 'M', 15, '2025-10-19 11:46:26'),
(31, 4, 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
(32, 4, 'ขาว', 'L', 15, '2025-10-19 11:46:26'),
(33, 4, 'ชมพู', 'L', 15, '2025-10-19 11:46:26'),
(40, 5, 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
(41, 5, 'แดง', 'S', 15, '2025-10-19 11:46:26'),
(42, 5, 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
(43, 5, 'แดง', 'M', 15, '2025-10-19 11:46:26'),
(44, 5, 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
(45, 5, 'แดง', 'L', 15, '2025-10-19 11:46:26'),
(46, 5, 'ดำ', 'XL', 15, '2025-10-19 11:46:26'),
(47, 5, 'แดง', 'XL', 15, '2025-10-19 11:46:26'),
(55, 7, 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
(56, 7, 'ขาว', 'S', 15, '2025-10-19 11:46:26'),
(57, 7, 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
(58, 7, 'ขาว', 'M', 15, '2025-10-19 11:46:26'),
(59, 7, 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
(60, 7, 'ขาว', 'L', 15, '2025-10-19 11:46:26'),
(62, 8, 'น้ำตาล', 'S', 15, '2025-10-19 11:46:26'),
(63, 8, 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
(64, 8, 'น้ำตาล', 'M', 15, '2025-10-19 11:46:26'),
(65, 8, 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
(69, 9, 'ขาว', 'S', 15, '2025-10-19 11:46:27'),
(70, 9, 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
(71, 9, 'ขาว', 'M', 15, '2025-10-19 11:46:27'),
(72, 9, 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
(73, 9, 'ขาว', 'L', 15, '2025-10-19 11:46:27'),
(74, 9, 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
(75, 9, 'ขาว', 'XL', 15, '2025-10-19 11:46:27'),
(76, 9, 'ดำ', 'XL', 15, '2025-10-19 11:46:27'),
(84, 10, 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
(85, 10, 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
(86, 10, 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
(87, 10, 'ดำ', 'XL', 15, '2025-10-19 11:46:27'),
(91, 11, 'ขาว', 'S', 15, '2025-10-19 11:46:27'),
(92, 11, 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
(93, 11, 'ชมพู', 'S', 15, '2025-10-19 11:46:27'),
(94, 11, 'ขาว', 'M', 15, '2025-10-19 11:46:27'),
(95, 11, 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
(96, 11, 'ชมพู', 'M', 15, '2025-10-19 11:46:27'),
(97, 11, 'ขาว', 'L', 15, '2025-10-19 11:46:27'),
(98, 11, 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
(99, 11, 'ชมพู', 'L', 15, '2025-10-19 11:46:27'),
(106, 12, 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
(107, 12, 'กรม', 'S', 15, '2025-10-19 11:46:27'),
(108, 12, 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
(109, 12, 'กรม', 'M', 15, '2025-10-19 11:46:27'),
(110, 12, 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
(111, 12, 'กรม', 'L', 15, '2025-10-19 11:46:27'),
(113, 13, 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
(114, 13, 'กรม', 'S', 15, '2025-10-19 11:46:27'),
(115, 13, 'เทา', 'S', 15, '2025-10-19 11:46:27'),
(116, 13, 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
(117, 13, 'กรม', 'M', 15, '2025-10-19 11:46:27'),
(118, 13, 'เทา', 'M', 15, '2025-10-19 11:46:27'),
(119, 13, 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
(120, 13, 'กรม', 'L', 12, '2025-10-19 11:46:27'),
(121, 13, 'เทา', 'L', 15, '2025-10-19 11:46:27'),
(132, 6, 'กรม', 'M', 0, '2025-10-21 11:40:03'),
(133, 6, 'กรม', 'S', 0, '2025-10-21 11:40:53'),
(137, 6, 'กรม', 'L', 0, '2025-10-21 11:41:27'),
(141, 6, 'ดำ', 'S', 0, '2025-10-21 11:42:15'),
(146, 6, 'ดำ', 'M', 0, '2025-10-21 11:42:32'),
(152, 6, 'ดำ', 'L', 0, '2025-10-21 11:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `order_id` int(11) NOT NULL,
  `applied` tinyint(1) NOT NULL DEFAULT 0,
  `reversed` tinyint(1) NOT NULL DEFAULT 0,
  `applied_at` timestamp NULL DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`order_id`, `applied`, `reversed`, `applied_at`, `reversed_at`) VALUES
(9, 1, 0, '2025-10-29 13:09:49', NULL),
(10, 1, 0, '2025-10-29 13:14:13', NULL),
(11, 1, 0, '2025-10-29 17:10:05', NULL),
(12, 1, 0, '2025-10-29 17:14:04', NULL),
(13, 1, 0, '2025-10-30 01:56:52', NULL),
(14, 1, 0, '2025-10-30 02:39:27', NULL),
(15, 1, 0, '2025-10-30 03:52:47', NULL),
(16, 1, 0, '2025-10-30 03:54:09', NULL),
(17, 1, 0, '2025-10-30 07:23:05', NULL),
(18, 1, 0, '2025-10-30 07:23:54', NULL),
(19, 1, 0, '2025-10-30 07:25:54', NULL),
(20, 1, 0, '2025-10-30 15:15:30', NULL),
(22, 1, 0, '2025-10-31 05:43:43', NULL),
(24, 1, 0, '2025-10-31 06:06:36', NULL),
(25, 1, 0, '2025-10-31 08:20:05', NULL),
(26, 1, 0, '2025-10-31 08:20:18', NULL),
(27, 1, 0, '2025-10-31 12:19:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_old`
--

CREATE TABLE `inventory_old` (
  `product_id` varchar(191) NOT NULL,
  `color` varchar(64) NOT NULL DEFAULT '',
  `size` varchar(8) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_old`
--

INSERT INTO `inventory_old` (`product_id`, `color`, `size`, `qty`, `created_at`) VALUES
('10', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('10', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('10', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('10', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('10', 'ดำ', 'XL', 15, '2025-10-19 11:46:27'),
('11', 'ขาว', '', 0, '2025-10-19 12:17:51'),
('11', 'ขาว', 'L', 15, '2025-10-19 11:46:27'),
('11', 'ขาว', 'M', 15, '2025-10-19 11:46:27'),
('11', 'ขาว', 'S', 15, '2025-10-19 11:46:27'),
('11', 'ชมพู', '', 0, '2025-10-19 12:17:51'),
('11', 'ชมพู', 'L', 15, '2025-10-19 11:46:27'),
('11', 'ชมพู', 'M', 15, '2025-10-19 11:46:27'),
('11', 'ชมพู', 'S', 15, '2025-10-19 11:46:27'),
('11', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('11', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('11', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('11', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('12', 'กรม', '', 0, '2025-10-19 12:17:51'),
('12', 'กรม', 'L', 15, '2025-10-19 11:46:27'),
('12', 'กรม', 'M', 15, '2025-10-19 11:46:27'),
('12', 'กรม', 'S', 15, '2025-10-19 11:46:27'),
('12', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('12', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('12', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('12', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('13', 'กรม', '', 0, '2025-10-19 12:17:51'),
('13', 'กรม', 'L', 12, '2025-10-19 11:46:27'),
('13', 'กรม', 'M', 15, '2025-10-19 11:46:27'),
('13', 'กรม', 'S', 15, '2025-10-19 11:46:27'),
('13', 'ดำ', '', 0, '2025-10-19 12:17:51'),
('13', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('13', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('13', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('13', 'เทา', '', 0, '2025-10-19 12:17:51'),
('13', 'เทา', 'L', 15, '2025-10-19 11:46:27'),
('13', 'เทา', 'M', 15, '2025-10-19 11:46:27'),
('13', 'เทา', 'S', 15, '2025-10-19 11:46:27'),
('4', 'ขาว', '', 0, '2025-10-19 12:17:50'),
('4', 'ขาว', 'L', 15, '2025-10-19 11:46:26'),
('4', 'ขาว', 'M', 15, '2025-10-19 11:46:26'),
('4', 'ขาว', 'S', 15, '2025-10-19 11:46:26'),
('4', 'ชมพู', '', 0, '2025-10-19 12:17:50'),
('4', 'ชมพู', 'L', 15, '2025-10-19 11:46:26'),
('4', 'ชมพู', 'M', 15, '2025-10-19 11:46:26'),
('4', 'ชมพู', 'S', 15, '2025-10-19 11:46:26'),
('4', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('4', 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
('4', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('4', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('5', 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('5', 'ดำ', 'XL', 15, '2025-10-19 11:46:26'),
('5', 'แดง', '', 0, '2025-10-19 12:17:50'),
('5', 'แดง', 'L', 15, '2025-10-19 11:46:26'),
('5', 'แดง', 'M', 15, '2025-10-19 11:46:26'),
('5', 'แดง', 'S', 15, '2025-10-19 11:46:26'),
('5', 'แดง', 'XL', 15, '2025-10-19 11:46:26'),
('6', 'กรม', 'L', 0, '2025-10-21 11:41:27'),
('6', 'กรม', 'M', 0, '2025-10-21 11:40:03'),
('6', 'กรม', 'S', 0, '2025-10-21 11:40:53'),
('6', 'ดำ', 'L', 0, '2025-10-21 11:42:50'),
('6', 'ดำ', 'M', 0, '2025-10-21 11:42:32'),
('6', 'ดำ', 'S', 0, '2025-10-21 11:42:15'),
('7', 'ขาว', '', 0, '2025-10-19 12:17:50'),
('7', 'ขาว', 'L', 15, '2025-10-19 11:46:26'),
('7', 'ขาว', 'M', 15, '2025-10-19 11:46:26'),
('7', 'ขาว', 'S', 15, '2025-10-19 11:46:26'),
('7', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('7', 'ดำ', 'L', 15, '2025-10-19 11:46:26'),
('7', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('7', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('8', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('8', 'ดำ', 'M', 15, '2025-10-19 11:46:26'),
('8', 'ดำ', 'S', 15, '2025-10-19 11:46:26'),
('8', 'น้ำตาล', '', 0, '2025-10-19 12:17:50'),
('8', 'น้ำตาล', 'M', 15, '2025-10-19 11:46:26'),
('8', 'น้ำตาล', 'S', 15, '2025-10-19 11:46:26'),
('9', 'ขาว', '', 0, '2025-10-19 12:17:50'),
('9', 'ขาว', 'L', 15, '2025-10-19 11:46:27'),
('9', 'ขาว', 'M', 15, '2025-10-19 11:46:27'),
('9', 'ขาว', 'S', 15, '2025-10-19 11:46:27'),
('9', 'ขาว', 'XL', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', '', 0, '2025-10-19 12:17:50'),
('9', 'ดำ', 'L', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', 'M', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', 'S', 15, '2025-10-19 11:46:27'),
('9', 'ดำ', 'XL', 15, '2025-10-19 11:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_no` varchar(32) DEFAULT NULL,
  `customer_name` varchar(191) DEFAULT NULL,
  `customer_email` varchar(191) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `address_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`address_json`)),
  `coupon_code` varchar(64) DEFAULT NULL,
  `subtotal` int(11) NOT NULL,
  `discount` int(11) NOT NULL,
  `shipping` int(11) NOT NULL,
  `vat` int(11) NOT NULL,
  `grand` int(11) NOT NULL,
  `payment_method` enum('COD','CARD') NOT NULL,
  `payment_status` enum('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',
  `status` enum('NEW','PROCESSING','SHIPPED','DONE','CANCELLED') NOT NULL DEFAULT 'NEW',
  `slip_path` varchar(255) DEFAULT NULL,
  `slip_uploaded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_no`, `customer_name`, `customer_email`, `customer_phone`, `address`, `user_id`, `address_json`, `coupon_code`, `subtotal`, `discount`, `shipping`, `vat`, `grand`, `payment_method`, `payment_status`, `status`, `slip_path`, `slip_uploaded_at`, `created_at`) VALUES
(2, NULL, 'ORD-3977', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 299, 0, 0, 0, 0, 'CARD', 'FAILED', 'CANCELLED', NULL, NULL, '2025-10-28 17:35:27'),
(3, NULL, 'ORD-3271', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 490, 0, 0, 0, 0, 'CARD', 'PAID', 'CANCELLED', 'uploads/slips/3/slip_20251028_114741_0ef767.jpg', '2025-10-28 17:47:41', '2025-10-28 17:47:28'),
(4, NULL, 'ORD-1843', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 299, 0, 0, 0, 0, 'CARD', 'PAID', 'NEW', 'uploads/slips/4/slip_20251028_115747_303bd7.jpg', '2025-10-28 17:57:47', '2025-10-28 17:57:33'),
(5, NULL, 'ORD-7131', 'Anon', 'anon@gmail.com', '0987654321', '432/3 มหาสารคาม 44510', NULL, '{\"name\":\"Anon\",\"email\":\"anon@gmail.com\",\"phone\":\"0987654321\",\"address\":\"432\\/3 มหาสารคาม 44510\"}', NULL, 490, 0, 0, 0, 490, '', 'PAID', 'NEW', '/fashionshop/wed_fashion/uploads/slips/5/slip_20251028_132924_69784f.jpg', NULL, '2025-10-28 19:29:01'),
(6, NULL, 'ORD-4227', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 490, 0, 0, 0, 490, '', 'PAID', 'NEW', 'uploads/slips/6/SLIP-6-20251028_163815-0da1f8.jpg', NULL, '2025-10-28 22:37:54'),
(7, NULL, 'ORD-4414', 'yongyong', 'yongyong@gmail.com', '0986742375', 'หอพักบุญยะพรเพลส 437/1 ต.ท่าขอนยาง อ.กันทรวิชัย 44150', NULL, '{\"name\":\"yongyong\",\"email\":\"yongyong@gmail.com\",\"phone\":\"0986742375\",\"address\":\"หอพักบุญยะพรเพลส 437\\/1 ต.ท่าขอนยาง อ.กันทรวิชัย 44150\"}', NULL, 490, 0, 0, 0, 490, 'COD', 'PAID', 'NEW', NULL, NULL, '2025-10-29 17:53:32'),
(8, NULL, 'ORD-4678', 'thipnaree', 'thipnaree@gmail.com', '0987564675', 'หอพักพัชริน หมู่1 ต.ท่าขอนยาง อ.กันทรวิชัย จ.มหาสารคาม 44150', NULL, '{\"name\":\"thipnaree\",\"email\":\"thipnaree@gmail.com\",\"phone\":\"0987564675\",\"address\":\"หอพักพัชริน หมู่1 ต.ท่าขอนยาง อ.กันทรวิชัย จ.มหาสารคาม 44150\"}', NULL, 1580, 0, 0, 0, 1580, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-29 17:58:44'),
(9, NULL, 'ORD-9719', 'pam', 'pam@gmail.com', '0931257502', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"pam\",\"email\":\"pam@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 1470, 0, 0, 0, 1470, '', 'PAID', 'NEW', 'uploads/slips/9/SLIP-9-20251029_140825-a405c6.jpg', NULL, '2025-10-29 20:07:58'),
(10, NULL, 'ORD-5336', 'นุ่นนิ่น', 'noon@gmail.com', '0987654321', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"นุ่นนิ่น\",\"email\":\"noon@gmail.com\",\"phone\":\"0987654321\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 1960, 0, 0, 0, 1960, 'COD', 'PAID', 'NEW', NULL, NULL, '2025-10-29 20:13:40'),
(11, NULL, 'ORD-7310', 'patong', 'patong@gmail.com', '0986742375', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"patong\",\"email\":\"patong@gmail.com\",\"phone\":\"0986742375\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 1470, 0, 0, 0, 1470, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-30 00:09:01'),
(12, NULL, 'ORD-5646', 'Happy', 'happy@gmail.com', '0897643560', '75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170', NULL, '{\"name\":\"Happy\",\"email\":\"happy@gmail.com\",\"phone\":\"0897643560\",\"address\":\"75 หมู่3 ต.หนองฮะ อ.สำโรงทาบ จ.สุรินทร์ 32170\"}', NULL, 1180, 0, 0, 0, 1180, '', 'PAID', 'PROCESSING', 'uploads/slips/12/SLIP-12-20251029_181312-d3a9c7.jpg', NULL, '2025-10-30 00:12:53'),
(13, NULL, 'ORD-2496', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75/3 surin 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75\\/3 surin 32170\"}', NULL, 1960, 0, 0, 0, 1960, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-30 08:55:57'),
(14, NULL, 'ORD-3166', 'pp naka', 'ppnaka@gmail.com', '0987654321', '75/3 surin 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0987654321\",\"address\":\"75\\/3 surin 32170\"}', NULL, 1770, 0, 0, 0, 1770, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-30 09:38:58'),
(15, NULL, 'ORD-3984', 'pp naka', 'ppnaka@gmail.com', '0987654321', '75/3 surin 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0987654321\",\"address\":\"75\\/3 surin 32170\"}', NULL, 1180, 0, 0, 0, 1180, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-30 10:42:35'),
(16, NULL, 'ORD-2054', 'pp naka', 'ppnaka@gmail.com', '0987654321', '75/3 surin 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0987654321\",\"address\":\"75\\/3 surin 32170\"}', NULL, 590, 0, 0, 0, 590, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-30 10:53:42'),
(17, NULL, 'ORD-5373', 'นุ่นนิ่น', 'juthamaspromwong@gmail.com', '000', '0000', NULL, '{\"name\":\"นุ่นนิ่น\",\"email\":\"juthamaspromwong@gmail.com\",\"phone\":\"000\",\"address\":\"0000\"}', NULL, 490, 0, 0, 0, 490, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-30 14:09:29'),
(18, NULL, 'ORD-2485', 'นุ่นนิ่น', 'admin@gmail.com', '000', '00', NULL, '{\"name\":\"นุ่นนิ่น\",\"email\":\"admin@gmail.com\",\"phone\":\"000\",\"address\":\"00\"}', NULL, 490, 0, 0, 0, 490, '', 'PAID', 'DONE', 'uploads/slips/18/SLIP-18-20251030_081023-f25c27.jpg', NULL, '2025-10-30 14:10:02'),
(19, NULL, 'ORD-9481', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75/3 surin 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75\\/3 surin 32170\"}', NULL, 690, 0, 0, 0, 690, '', 'PAID', 'DONE', 'uploads/slips/19/SLIP-19-20251030_081545-aff749.jpg', NULL, '2025-10-30 14:14:12'),
(20, NULL, 'ORD-4770', 'pp naka', 'ppnaka@gmail.com', '0931257502', '0000', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"0000\"}', NULL, 1470, 0, 0, 0, 1470, '', 'PAID', 'DONE', NULL, NULL, '2025-10-30 22:13:22'),
(21, NULL, 'ORD-2355', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75/3 surin 32170', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75\\/3 surin 32170\"}', NULL, 690, 0, 0, 0, 690, '', '', 'NEW', NULL, NULL, '2025-10-31 10:55:30'),
(22, NULL, 'ORD-8820', 'pp naka', 'ppnaka@gmail.com', '0931257502', 'จจจจจ', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"จจจจจ\"}', NULL, 690, 0, 0, 0, 690, '', 'PAID', 'DONE', NULL, NULL, '2025-10-31 12:42:32'),
(23, NULL, 'ORD-2889', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75/3', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75\\/3\"}', NULL, 390, 0, 0, 0, 390, 'COD', '', 'CANCELLED', NULL, NULL, '2025-10-31 13:02:06'),
(24, NULL, 'ORD-7434', 'pp naka', 'ppnaka@gmail.com', '0931257502', '75/3', NULL, '{\"name\":\"pp naka\",\"email\":\"ppnaka@gmail.com\",\"phone\":\"0931257502\",\"address\":\"75\\/3\"}', NULL, 690, 0, 0, 0, 690, '', 'PAID', 'DONE', 'uploads/slips/24/SLIP-24-20251031_070309-42bb04.jpg', NULL, '2025-10-31 13:02:52'),
(25, NULL, 'ORD-6337', 'จุฑามาศ', 'pp@gmil.com', '0321555255', '75/3 32170', NULL, '{\"name\":\"จุฑามาศ\",\"email\":\"pp@gmil.com\",\"phone\":\"0321555255\",\"address\":\"75\\/3 32170\"}', NULL, 490, 0, 0, 0, 490, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-31 15:15:05'),
(26, NULL, 'ORD-0580', 'จุฑามาศ', 'pp@gmil.com', '0321555255', '75/3 32170', NULL, '{\"name\":\"จุฑามาศ\",\"email\":\"pp@gmil.com\",\"phone\":\"0321555255\",\"address\":\"75\\/3 32170\"}', NULL, 1470, 0, 0, 0, 1470, 'COD', 'PAID', 'DONE', 'uploads/slips/26/SLIP-26-20251031_091806-b058f2.webp', NULL, '2025-10-31 15:16:57'),
(27, NULL, 'ORD-1347', 'Anon', 'anon@gmail.com', '0986742375', '75/3 surin 32170', NULL, '{\"name\":\"Anon\",\"email\":\"anon@gmail.com\",\"phone\":\"0986742375\",\"address\":\"75\\/3 surin 32170\"}', NULL, 1960, 0, 0, 0, 1960, 'COD', 'PAID', 'DONE', NULL, NULL, '2025-10-31 19:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(16) NOT NULL,
  `name` varchar(255) NOT NULL,
  `size` varchar(16) NOT NULL,
  `price` int(11) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `line_total` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `name`, `size`, `price`, `subtotal`, `qty`, `line_total`) VALUES
(1, 2, '7', 'โอเวอร์เชิ้ตซอฟท์คอตตอน', 'S', 299, NULL, 1, 0),
(2, 3, '13', 'คาร์ดิแกนผ้านุ่ม', 'S', 490, NULL, 1, 0),
(3, 4, '7', 'โอเวอร์เชิ้ตซอฟท์คอตตอน', 'S', 299, NULL, 1, 0),
(4, 5, '13', 'คาร์ดิแกนผ้านุ่ม', 'L', 490, NULL, 1, 0),
(5, 6, '13', 'คาร์ดิแกนผ้านุ่ม', 'S', 490, NULL, 1, 0),
(6, 7, '13', 'คาร์ดิแกนผ้านุ่ม', 'S', 490, NULL, 1, 0),
(7, 8, '11', 'เดรสสลิปซาติน', 'L', 790, NULL, 2, 0),
(8, 9, '13', 'คาร์ดิแกนผ้านุ่ม', 'S', 490, NULL, 3, 0),
(9, 10, '13', 'คาร์ดิแกนผ้านุ่ม', 'L', 490, NULL, 4, 0),
(10, 11, '13', 'คาร์ดิแกนผ้านุ่ม', 'L', 490, NULL, 3, 0),
(11, 12, '6', 'กางเกงสแลค Tapered', 'XL', 590, NULL, 1, 0),
(12, 12, '6', 'กางเกงสแลค Tapered', 'XL', 590, NULL, 1, 0),
(13, 13, '13', 'คาร์ดิแกนผ้านุ่ม', 'L', 490, NULL, 4, 0),
(14, 14, '6', 'กางเกงสแลค Tapered', 'XL', 590, NULL, 3, 0),
(15, 15, '6', 'กางเกงสแลค Tapered', 'XL', 590, NULL, 2, 0),
(16, 16, '6', 'กางเกงสแลค Tapered', 'XL', 590, NULL, 1, 0),
(17, 17, '13', 'คาร์ดิแกนผ้านุ่ม', 'S', 490, NULL, 1, 0),
(18, 18, '13', 'คาร์ดิแกนผ้านุ่ม', 'S', 490, NULL, 1, 0),
(19, 19, '4', 'เดรสผ้าลินิน A-line', 'M', 690, NULL, 1, 0),
(20, 20, '13', 'คาร์ดิแกนผ้านุ่ม', 'M', 490, NULL, 3, 0),
(21, 21, '4', 'เดรสผ้าลินิน A-line', 'L', 690, NULL, 1, 0),
(22, 22, '4', 'เดรสผ้าลินิน A-line', 'M', 690, NULL, 1, 0),
(23, 23, '10', 'กางเกงวอร์ม Jogger', 'XL', 390, NULL, 1, 0),
(24, 24, '4', 'เดรสผ้าลินิน A-line', 'L', 690, NULL, 1, 0),
(25, 25, '13', 'คาร์ดิแกนผ้านุ่ม', 'M', 490, NULL, 1, 0),
(26, 26, '13', 'คาร์ดิแกนผ้านุ่ม', 'M', 490, NULL, 3, 0),
(27, 27, '13', 'คาร์ดิแกนผ้านุ่ม', 'M', 490, NULL, 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(100) DEFAULT NULL,
  `colors` text DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `stock_xs` int(11) NOT NULL DEFAULT 0,
  `stock_s` int(11) NOT NULL DEFAULT 0,
  `stock_m` int(11) NOT NULL DEFAULT 0,
  `stock_l` int(11) NOT NULL DEFAULT 0,
  `stock_xl` int(11) NOT NULL DEFAULT 0,
  `stock_xxl` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `color` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_url`, `stock`, `created_at`, `category`, `colors`, `badge`, `stock_xs`, `stock_s`, `stock_m`, `stock_l`, `stock_xl`, `stock_xxl`, `category_id`, `color`) VALUES
(4, 'เดรสผ้าลินิน A-line', 'เดรสผ้าลินินทรง A ใส่สบาย สไตล์มินิมอล', 690.00, 'https://picsum.photos/seed/D001/600/600', 0, '2025-09-19 17:00:00', 'เดรส', NULL, 'Hit', 0, 20, 18, 19, 0, 0, 3, 'ดำ ขาว ชมพู'),
(5, 'เสื้อยืด Oversize Essential', 'เสื้อยืดโอเวอร์ไซซ์ ผ้านุ่ม ระบายอากาศดี', 259.00, 'https://picsum.photos/seed/T101/600/600', 0, '2025-09-09 17:00:00', NULL, NULL, 'New', 0, 15, 20, 30, 20, 0, 4, 'ดำ เเดง'),
(6, 'กางเกงสแลค Tapered', 'สแลคทรงเทเปอร์พอดีตัว ใส่ทำงานได้', 590.00, 'https://picsum.photos/seed/S501/600/600', 0, '2025-09-11 17:00:00', NULL, NULL, 'Hit', 0, 21, 20, 20, 27, 0, 6, 'ดำ กรม'),
(7, 'โอเวอร์เชิ้ตซอฟท์คอตตอน', 'เชิ้ตผ้าคอตตอนนุ่ม โอเวอร์ใส่ทับง่าย', 299.00, 'https://picsum.photos/seed/O301/600/600', 0, '2025-09-22 17:00:00', NULL, NULL, 'Hit', 0, 20, 20, 20, 0, 0, 5, 'ขาว ดำ'),
(8, 'เสื้อถักริบครอป', 'เสื้อถักริบยืดหยุ่น ทรงครอป', 490.00, 'https://picsum.photos/seed/K221/600/600', 0, '2025-08-27 17:00:00', '', NULL, 'New', 0, 20, 20, 0, 0, 0, 7, 'ดำ น้ำตาล'),
(9, 'เสื้อยืดคอกลม Premium', 'คอตตอนพรีเมียม ผิวสัมผัสดี', 200.00, 'https://picsum.photos/seed/T102/600/600', 0, '2025-09-01 17:00:00', NULL, NULL, 'New', 0, 10, 20, 20, 20, 0, 4, 'ขาว ดำ'),
(10, 'กางเกงวอร์ม Jogger', 'จ๊อกเกอร์ลำลอง เอวยางยืด มีกระเป๋า', 390.00, 'https://picsum.photos/seed/P777/600/600', 0, '2025-09-17 17:00:00', NULL, NULL, 'New', 0, 15, 16, 15, 15, 0, 6, 'ดำ'),
(11, 'เดรสสลิปซาติน', 'เดรสซาตินลื่นเงา เหมาะกับงานปาร์ตี้', 790.00, 'https://picsum.photos/seed/D009/600/600', 0, '2025-09-04 17:00:00', 'เดรส', NULL, 'Hit', 0, 45, 45, 40, 0, 0, 3, 'ดำ ขาว ชมพู'),
(12, 'เบลเซอร์โครงสวย', 'เบลเซอร์โครงเป๊ะ ใส่ทำงานหรือออกงาน', 890.00, 'https://picsum.photos/seed/O302/600/600', 0, '2025-09-14 17:00:00', NULL, NULL, 'New', 0, 20, 20, 20, 0, 0, NULL, 'ดำ กรม'),
(13, 'คาร์ดิแกนผ้านุ่ม', 'คาร์ดิแกนผ้านุ่ม อุ่นสบาย', 490.00, 'https://picsum.photos/seed/K301/600/600', 0, '2025-09-25 17:00:00', '', NULL, 'Hit', 0, 38, 35, 42, 0, 0, 7, 'เทา กรม ดำ');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `url`, `sort_order`, `created_at`) VALUES
(11, 7, '/fashionshop/uploads/7/20251021133436_เสื้อเชิ้ตสีดำ.jpg', 0, '2025-10-21 11:34:36'),
(12, 7, '/fashionshop/uploads/7/20251021133524_เสื้อเชิ้ตสีขาว.jpg', 1, '2025-10-21 11:35:24'),
(13, 4, '/fashionshop/uploads/4/20251021133548_เดรสผ้าลินินสีดำ.jpg', 0, '2025-10-21 11:35:48'),
(14, 4, '/fashionshop/uploads/4/20251021133620_เดรสผ้าลินินสีขาว.jpg', 1, '2025-10-21 11:36:20'),
(15, 4, '/fashionshop/uploads/4/20251021133642_เดรสผ้าลินินสีชมพู.jpg', 2, '2025-10-21 11:36:42'),
(16, 10, '/fashionshop/uploads/10/20251021133711_กางเกงวอม.jpg', 0, '2025-10-21 11:37:11'),
(17, 12, '/fashionshop/uploads/12/20251021133737_เบลเซอร์สีกรม.jpg', 0, '2025-10-21 11:37:37'),
(18, 12, '/fashionshop/uploads/12/20251021133801_เบลเซอร์สีดำ.jpg', 1, '2025-10-21 11:38:01'),
(19, 6, '/fashionshop/uploads/6/20251021133836_กางเกงสเเลคสีกรม.jpg', 0, '2025-10-21 11:38:36'),
(20, 6, '/fashionshop/uploads/6/20251021133904_กางเกงสเเลคสีดำ.jpg', 1, '2025-10-21 11:39:04'),
(22, 5, '/fashionshop/uploads/5/20251021134408_เสื้อโอเวอร์ไซร้สีดำ.jpg', 1, '2025-10-21 11:44:08'),
(23, 5, '/fashionshop/uploads/5/20251021134440_เสื้อโอเวอร์ไซร้สีเเดง.jpg', 2, '2025-10-21 11:44:40'),
(25, 11, '/fashionshop/uploads/11/20251021134613_เดรสซาตินสีดำ.jpg', 0, '2025-10-21 11:46:13'),
(26, 11, '/fashionshop/uploads/11/20251021134640_เดรสซาตินสีขาว.jpg', 1, '2025-10-21 11:46:40'),
(27, 11, '/fashionshop/uploads/11/20251021134701_เดรสซาตินสีชมพู.jpg', 2, '2025-10-21 11:47:01'),
(28, 9, '/fashionshop/uploads/9/20251021134733_เสื้อยืดสีดำ.jpg', 0, '2025-10-21 11:47:33'),
(29, 9, '/fashionshop/uploads/9/20251021134804_เสื้อยืดสีขาว.jpg', 1, '2025-10-21 11:48:04'),
(31, 8, '/fashionshop/uploads/8/20251021140256_เสื้อถักริบครอปสีน้ำตาล.jpg', 0, '2025-10-21 12:02:56'),
(32, 8, '/fashionshop/uploads/8/20251021140319_เสื้อถักริบครอปสีดำ.jpg', 1, '2025-10-21 12:03:19'),
(38, 13, '/fashionshop/uploads/13/p_13_1761644356_c08e47b2.jpg', 1, '2025-10-28 09:39:16'),
(39, 13, '/fashionshop/uploads/13/p_13_1761644385_98a7feac.jpg', 2, '2025-10-28 09:39:45'),
(40, 13, '/fashionshop/uploads/13/p_13_1761644399_d30b8dcf.jpg', 3, '2025-10-28 09:39:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(191) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_2` (`email`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`product_id`,`color`,`size`),
  ADD KEY `idx_inventory_pid` (`product_id`);

--
-- Indexes for table `inventory_backup`
--
ALTER TABLE `inventory_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_prod_color_size` (`product_id`,`color`,`size`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `inventory_old`
--
ALTER TABLE `inventory_old`
  ADD PRIMARY KEY (`product_id`,`color`,`size`),
  ADD KEY `idx_inventory_pid` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `inventory_backup`
--
ALTER TABLE `inventory_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1112;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_backup`
--
ALTER TABLE `inventory_backup`
  ADD CONSTRAINT `inventory_backup_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
