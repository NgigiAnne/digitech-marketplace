-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 07:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `digitech_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(50) DEFAULT '1',
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `shipping_address` varchar(255) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `email`, `total`, `created_at`, `updated_at`, `status`, `shipping_address`, `phone_number`, `payment_method`) VALUES
(3, 4, '1', 0.00, '2025-05-26 18:06:50', '2025-06-06 13:52:13', 'delivered', 'nairobi', NULL, 'mpesa'),
(5, 4, '1', 0.00, '2025-05-26 18:27:29', '2025-06-06 13:52:13', 'shipped', 'nairobi', NULL, 'cash_on_delivery'),
(6, 4, '1', 0.00, '2025-05-26 18:27:42', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'cash_on_delivery'),
(7, 4, '1', 0.00, '2025-05-26 18:31:29', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'cash_on_delivery'),
(8, 4, '1', 0.00, '2025-05-26 18:42:13', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(9, 4, '1', 0.00, '2025-05-26 18:42:15', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(10, 4, '1', 0.00, '2025-05-26 18:42:17', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(11, 4, '1', 0.00, '2025-05-26 18:42:22', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(12, 4, '1', 0.00, '2025-05-26 18:42:23', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(13, 4, '1', 0.00, '2025-05-26 18:42:23', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(14, 4, '1', 0.00, '2025-05-27 17:17:04', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'mpesa'),
(15, 4, '1', 0.00, '2025-05-27 18:36:20', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(16, 4, '1', 0.00, '2025-05-27 18:36:21', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(17, 4, '1', 0.00, '2025-05-27 18:36:22', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(18, 4, '1', 0.00, '2025-05-27 18:36:24', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(19, 4, '1', 0.00, '2025-05-27 18:36:24', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(20, 4, '1', 0.00, '2025-05-27 18:36:24', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(21, 4, '1', 0.00, '2025-05-27 18:36:24', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(22, 4, '1', 0.00, '2025-05-27 18:36:25', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(23, 4, '1', 0.00, '2025-05-27 18:36:25', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(24, 4, '1', 0.00, '2025-05-27 18:36:25', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(25, 4, '1', 0.00, '2025-05-27 18:36:25', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(26, 4, '1', 0.00, '2025-05-27 18:36:26', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(27, 4, '1', 0.00, '2025-05-27 18:36:26', '2025-06-06 13:52:13', 'pending', 'b', NULL, 'mpesa'),
(28, 4, '1', 0.00, '2025-05-28 04:29:01', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(29, 4, '1', 0.00, '2025-05-28 04:29:02', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(30, 4, '1', 0.00, '2025-05-28 04:29:04', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(31, 4, '1', 0.00, '2025-05-28 04:29:04', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(32, 4, '1', 0.00, '2025-05-28 04:29:13', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(33, 4, '1', 0.00, '2025-05-28 04:29:13', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(34, 4, '1', 0.00, '2025-05-28 04:29:13', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(35, 4, '1', 0.00, '2025-05-28 04:29:16', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(36, 4, '1', 0.00, '2025-05-28 04:29:16', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(37, 4, '1', 0.00, '2025-05-28 04:29:20', '2025-06-06 13:52:13', 'pending', 'nairobi', NULL, 'credit_card'),
(38, 4, '1', 89999.00, '2025-05-28 05:18:31', '2025-06-07 07:34:15', 'completed', 'nairobi', NULL, 'mpesa'),
(41, 6, 'ngigianne02@gmail.com', 2999.00, '2025-06-06 14:13:10', '2025-06-06 14:13:10', 'pending', 'Nairobi', '0798432987', 'mpesa'),
(42, 6, 'ngigianne02@gmail.com', 179998.00, '2025-06-07 07:38:53', '2025-06-07 07:38:53', 'pending', 'Nairobi', '0798432987', 'card'),
(43, 6, 'ngigianne02@gmail.com', 89999.00, '2025-06-07 07:41:26', '2025-06-07 07:41:26', 'pending', 'Thika', '0798432987', 'mpesa'),
(44, 6, 'ddd@gmail.com', 12999.00, '2025-06-07 07:47:21', '2025-06-07 07:47:21', 'pending', 'Thika', '0766666666', 'paypal'),
(45, 6, 'ddd@gmail.com', 12999.00, '2025-06-08 15:03:33', '2025-06-08 15:03:33', 'pending', 'Mombasa', '0766666666', 'card'),
(46, 6, 'ngigianne02@gmail.com', 3999.00, '2025-06-15 05:43:20', '2025-06-15 05:43:20', 'pending', 'Narok', '0798432987', 'paypal'),
(47, 4, 'ddd@gmail.com', 12999.00, '2025-06-15 18:13:34', '2025-06-15 18:13:34', 'pending', 'nairobi', '0798432987', 'mpesa'),
(48, 4, 'ddd@gmail.com', 12999.00, '2025-06-15 18:23:18', '2025-06-15 18:23:18', 'pending', 'nairobi', '0766666666', 'mpesa'),
(49, 4, 'ddd@gmail.com', 12999.00, '2025-06-15 18:24:29', '2025-06-15 18:24:29', 'pending', 'kampala', '0798432987', 'mpesa');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`, `image_url`) VALUES
(1, 38, 3, 1, 89999.00, '2025-05-28 05:18:31', NULL),
(2, 41, 7, 1, 2999.00, '2025-06-06 14:13:10', 'https://images.unsplash.com/photo-1527814050087-3793815479db?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(3, 42, 3, 2, 89999.00, '2025-06-07 07:38:53', 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(4, 43, 3, 1, 89999.00, '2025-06-07 07:41:26', 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(5, 44, 4, 1, 12999.00, '2025-06-07 07:47:21', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(6, 45, 4, 1, 12999.00, '2025-06-08 15:03:33', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(7, 46, 11, 1, 3999.00, '2025-06-15 05:43:20', 'https://i.postimg.cc/3wQT5Sbd/powerbanks.png'),
(8, 47, 4, 1, 12999.00, '2025-06-15 18:13:34', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(9, 48, 4, 1, 12999.00, '2025-06-15 18:23:18', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'),
(10, 49, 4, 1, 12999.00, '2025-06-15 18:24:29', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `original_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `stock`, `image_url`, `created_at`, `original_price`) VALUES
(2, 'power bank', 'High Capacity: 10,000mAh / 20,000mAh (depending on model), enough to fully charge most smartphones 2-4 times.\r\n\r\nDual USB Outputs: Charge two devices simultaneously.\r\n\r\nFast Charging Technology: Supports Quick Charge and Power Delivery for faster, more efficient charging.', 5000.00, 'Electronics', 11, 'https://i.postimg.cc/QMzD5t2H/Anker-737-Powerbank-01-jpg.webp', '2025-05-29 13:01:57', 6999.00),
(3, 'bluetooth speaker', 'Wireless Connectivity: Bluetooth 5.0 ensures fast and stable connections up to 10 meters away.\r\n\r\nRich Sound Quality: Delivers clear highs, detailed mids, and enhanced bass for all types of music.\r\n\r\nPortable Design: Lightweight and compact with a built-in strap or handle for easy carrying.', 4999.00, 'Accessories', 6, 'https://i.postimg.cc/G2yFjQQ0/35059173.jpg', '2025-05-30 09:18:46', 6000.00),
(4, 'wireless earbuds pro', 'Premium wireless earbuds with active noise cancellation and 30-hour battery life. Features Bluetooth 5.0 and IPX4 water resistance.', 5999.00, 'Electronics', 15, 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80', '2025-06-15 05:58:56', 7999.00),
(5, 'smartphonex', 'Flagship smartphone with 6.7\\\" AMOLED display, triple camera system, and 128GB storage. Powered by the latest Snapdragon processor', 35999.00, 'Electronics', 8, '        https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80', '2025-06-15 06:18:17', 42999.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `last_login` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token_hash` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `last_login`, `created_at`, `updated_at`, `reset_token_hash`, `reset_token_expires_at`) VALUES
(3, 'Admin User', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-06-15 21:07:20', '2025-05-20 19:34:17', '2025-06-15 18:07:20', NULL, NULL),
(4, 'anne', 'ddd@gmail.com', '$2y$10$cx3z/fzAIhrfHII7CjXlyODD5oV2l5v.NURmn6Tvdriq3xAH32Yq.', 'user', '2025-06-15 21:12:23', '2025-05-20 19:34:17', '2025-06-15 18:12:23', NULL, NULL),
(5, 'Elsie', 'elsie@gmail.com', '$2y$10$K1g29GgH6Fv4S3CHb5ZvtuuCtUxJ.P/IeWcYkBH.CXYUy5UM1Sske', 'user', NULL, '2025-05-29 07:44:09', '2025-05-29 07:44:09', NULL, NULL),
(6, 'Anne', 'ngigianne02@gmail.com', '$2y$10$X5dHmvDcuCiQs5ws4sUlgOyGW7pNhrniYY//bngCK97OdCaSKbLa6', 'user', NULL, '2025-06-04 08:27:54', '2025-06-09 05:19:29', '5ff939d59ab14510f21e2f8f59f10e532dda36559d18190fea52fa5f2e4a8275', '2025-06-09 12:19:29'),
(7, 'boba', 'boba@gmail.com', '$2y$10$Ily7XSROxrFHQyUQxF3m2e2fPmcvjgr2mE.QOoip8xoSt2LvguuO.', 'user', NULL, '2025-06-15 13:25:24', '2025-06-15 13:25:24', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token_hash` (`token_hash`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
