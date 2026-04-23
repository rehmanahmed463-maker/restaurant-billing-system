-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2025 at 03:22 PM
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
-- Database: `hotel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `bill_id` int(11) NOT NULL,
  `bill_no` int(11) NOT NULL,
  `table_no` int(11) NOT NULL,
  `items` text NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `final_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bill_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`bill_id`, `bill_no`, `table_no`, `items`, `total`, `tax`, `final_amount`, `created_at`, `bill_date`) VALUES
(1, 8, 101, 'CHI NAWABI HALF x1 (₹330.00), ', 330.00, 16.50, 346.50, '2025-09-03 10:40:14', '2025-09-03 20:47:56'),
(2, 7, 3, 'Chicken Manchow Soup x1 (₹250.00), ', 250.00, 12.50, 262.50, '2025-09-03 10:40:27', '2025-09-03 20:47:56'),
(3, 9, 25, 'CHI NAWABI HALF x1 (₹330.00), ', 330.00, 16.50, 346.50, '2025-09-03 10:51:50', '2025-09-03 20:47:56'),
(6, 10, 101, 'CHI NAWABI HALF x1 (₹330.00), ', 330.00, 16.50, 346.50, '2025-09-03 11:09:44', '2025-09-03 20:47:56'),
(8, 10, 101, 'CHI NAWABI HALF x1 (₹330.00), ', 330.00, 16.50, 346.50, '2025-09-03 11:10:15', '2025-09-03 20:47:56'),
(9, 11, 5, 'Chicken Manchow Soup x1 (₹250.00), ', 250.00, 12.50, 262.50, '2025-09-03 14:47:54', '2025-09-03 20:47:56'),
(10, 12, 5, 'chi tandoori half x1 (₹220.00), ', 220.00, 11.00, 231.00, '2025-09-03 15:10:16', '2025-09-03 20:47:56'),
(11, 13, 6, 'chi tandoori half x1 (₹220.00), CHI NAWABI HALF x1 (₹330.00), ', 550.00, 27.50, 577.50, '2025-09-03 15:25:59', '2025-09-03 20:55:59'),
(12, 14, 5, 'chi tandoori half x6 (₹1320.00), ', 1320.00, 66.00, 1386.00, '2025-09-03 15:42:07', '2025-09-03 21:12:07'),
(13, 15, 101, 'CHI NAWABI HALF x1 (₹330.00), ', 330.00, 16.50, 346.50, '2025-09-03 15:47:44', '2025-09-03 21:17:44'),
(14, 16, 1, 'CHI NAWABI HALF x1 (₹330.00), ', 330.00, 16.50, 346.50, '2025-09-03 17:48:25', '2025-09-03 23:18:25'),
(15, 17, 1, 'CHICKEN HOT&SOUR SOUP x1 (₹260.00), ', 260.00, 13.00, 273.00, '2025-09-04 06:20:39', '2025-09-04 11:50:39');

-- --------------------------------------------------------

--
-- Table structure for table `menu_cards`
--

CREATE TABLE `menu_cards` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `group_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_cards`
--

INSERT INTO `menu_cards` (`id`, `item_code`, `group_id`, `item_name`, `price`) VALUES
(4, '2', 2, 'Chicken Manchow Soup', 250.00),
(5, '3', 2, 'CHICKEN HOT&SOUR SOUP', 260.00),
(6, '4', 2, 'CHICKEN CLEAR SOUP', 240.00),
(7, '5', 8, 'CHI NAWABI HALF', 350.00),
(8, '6', 6, 'chi tandoori half', 220.00),
(9, '7', 3, 'veg manchurian dry', 210.00),
(10, '8', 8, 'Chi nawabi full', 660.00),
(11, '9', 2, 'chi mughlai', 260.00),
(12, '10', 8, 'chi toofani', 350.00);

-- --------------------------------------------------------

--
-- Table structure for table `menu_groups`
--

CREATE TABLE `menu_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_groups`
--

INSERT INTO `menu_groups` (`id`, `group_name`) VALUES
(1, 'Veg Soup'),
(2, 'Non-Veg Soup'),
(3, 'Veg Chinese Starter'),
(4, 'Non-Veg Chinese Starter'),
(5, 'Veg Indian Starter'),
(6, 'Non-Veg Indian Starter'),
(7, 'Veg Main Course'),
(8, 'Non-Veg Main Course'),
(9, 'Breads'),
(10, 'Rice'),
(11, 'Beverages'),
(12, 'non veg');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `code` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `kot_no` int(11) DEFAULT NULL,
  `table_no` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `kot_no`, `table_no`, `item_code`, `item_name`, `qty`, `price`, `total`, `created_at`) VALUES
(13, NULL, 4, '5', 'CHI NAWABI HALF', 1, 330.00, 330.00, '2025-09-03 16:25:07'),
(25, NULL, 5, '2', 'Chicken Manchow Soup', 6, 250.00, 1500.00, '2025-09-04 15:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `qty` int(11) DEFAULT 0,
  `rate` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_tables`
--

CREATE TABLE `pos_tables` (
  `id` int(11) NOT NULL,
  `table_no` varchar(50) DEFAULT NULL,
  `status` enum('free','running') DEFAULT 'free',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `area` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `table_groups`
--

CREATE TABLE `table_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_number` int(11) NOT NULL,
  `end_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `table_groups`
--

INSERT INTO `table_groups` (`id`, `name`, `start_number`, `end_number`) VALUES
(1, 'Restaurant', 1, 10),
(2, 'Garden', 11, 20),
(3, 'Parcel', 101, 110),
(4, 'Zomato', 131, 140),
(5, 'Swiggy', 121, 129);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user1','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(3, 'user1', 'user123', 'user1'),
(4, 'admin', 'admin123', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`bill_id`);

--
-- Indexes for table `menu_cards`
--
ALTER TABLE `menu_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_menu_cards_item_code` (`item_code`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `menu_groups`
--
ALTER TABLE `menu_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_ibfk_1` (`order_id`);

--
-- Indexes for table `pos_tables`
--
ALTER TABLE `pos_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_no` (`table_no`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`number`);

--
-- Indexes for table `table_groups`
--
ALTER TABLE `table_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `menu_cards`
--
ALTER TABLE `menu_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `menu_groups`
--
ALTER TABLE `menu_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_tables`
--
ALTER TABLE `pos_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `table_groups`
--
ALTER TABLE `table_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu_cards`
--
ALTER TABLE `menu_cards`
  ADD CONSTRAINT `menu_cards_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `menu_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
