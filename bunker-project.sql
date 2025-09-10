-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2025 at 05:19 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bunker-project`
--

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `products_id` int(11) NOT NULL,
  `Kode_Plants` int(11) DEFAULT NULL,
  `nama_Plants` varchar(255) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `po`
--

CREATE TABLE `po` (
  `id_Po` int(50) NOT NULL,
  `Tanggal` date NOT NULL DEFAULT current_timestamp(),
  `No_Po` varchar(255) NOT NULL,
  `Kode_Shiptos` varchar(255) DEFAULT NULL,
  `nama_Shiptos` varchar(255) DEFAULT NULL,
  `Kode_Plants` varchar(255) DEFAULT NULL,
  `nama_Plants` varchar(255) DEFAULT NULL,
  `Kode_Products` varchar(255) DEFAULT NULL,
  `nama_Products` varchar(255) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Qty_Sisa` int(15) DEFAULT NULL,
  `Link` text DEFAULT NULL,
  `Status` varchar(12) DEFAULT 'OPEN',
  `date_created` timestamp NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `po`
--

INSERT INTO `po` (`id_Po`, `Tanggal`, `No_Po`, `Kode_Shiptos`, `nama_Shiptos`, `Kode_Plants`, `nama_Plants`, `Kode_Products`, `nama_Products`, `Quantity`, `Qty_Sisa`, `Link`, `Status`, `date_created`, `date_updated`) VALUES
(1, '2025-03-02', 'PB000775/02/25', '0000884030', 'PT. SALAM PACIFIC INDONESIA LINES', '1306', 'Inst. Surabaya', 'A040900204', 'BIOSOLAR B40', 75, 25, '', 'OPEN', '2025-03-03 07:56:38', '2025-03-03 07:56:38'),
(2, '2025-03-04', 'PB000001/04/03/25', 'SP1L', 'SPIL 1', NULL, NULL, NULL, NULL, 100, 88, '', 'OPEN', '2025-03-04 02:52:18', '2025-03-04 02:52:18'),
(3, '2025-03-04', 'PB000002/04/03/25', 'SP1L', 'SRIL', NULL, NULL, NULL, NULL, 100, NULL, '', 'OPEN', '2025-03-04 02:54:52', '2025-03-04 02:54:52'),
(4, '2025-03-04', 'PB000003/04/03/25', 'SP3L', 'SPIL 3', NULL, NULL, NULL, NULL, 100, NULL, '', 'OPEN', '2025-03-04 02:55:36', '2025-03-04 02:55:36'),
(5, '2025-03-04', 'PB000004/04/03/25', 'TANTO', 'TANTO', NULL, NULL, NULL, NULL, 200, 200, '', 'OPEN', '2025-03-04 03:59:13', '2025-03-04 03:59:13'),
(6, '2025-03-04', 'PB000006/04/03/25', 'SRIL', 'Meratus', NULL, NULL, NULL, NULL, 200, 200, '', 'OPEN', '2025-03-04 04:00:38', '2025-03-04 04:00:38'),
(22, '2025-03-04', 'PB000768/02/25', '1080662', 'MV. MERATUS SURABAYA', '1419', 'Depot Bitung', NULL, NULL, 5, 0, NULL, 'CLOSE', '2025-03-04 08:07:52', '2025-03-04 08:07:52'),
(23, '2025-03-04', 'PB000768/02/26', '1080662', 'MV. MERATUS JAKARTA', '1423', 'Depot Donggala', NULL, NULL, 5, 1, NULL, 'OPEN', '2025-03-04 08:07:52', '2025-03-04 08:07:52'),
(24, '2025-03-04', 'PB000768/02/27', '1080662', 'MV. MERATUS SEMARANG', '1423', 'Depot Donggala', NULL, NULL, 5, 0, NULL, 'CLOSE', '2025-03-04 08:07:52', '2025-03-04 08:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `products_id` int(11) NOT NULL,
  `kode_products` int(11) DEFAULT NULL,
  `nama_products` varchar(255) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ships`
--

CREATE TABLE `ships` (
  `products_id` int(11) NOT NULL,
  `Kode_Shiptos` int(11) DEFAULT NULL,
  `nama_Shiptos` varchar(255) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`products_id`);

--
-- Indexes for table `po`
--
ALTER TABLE `po`
  ADD PRIMARY KEY (`id_Po`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`products_id`);

--
-- Indexes for table `ships`
--
ALTER TABLE `ships`
  ADD PRIMARY KEY (`products_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `po`
--
ALTER TABLE `po`
  MODIFY `id_Po` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
