-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 08:53 PM
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
-- Database: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `address`, `created_at`) VALUES
(1, 4, 1999.00, 'Delivered', 'hhhhhh', '2026-09-13 18:17:02'),
(2, 4, 23988.00, 'Shipped', 'lknon', '2026-09-13 20:34:57'),
(3, 6, 37485.00, 'Pending', 'np', '2026-09-15 11:26:25'),
(4, 1, 1998.00, 'Pending', 'oin', '2026-09-15 11:27:41'),
(5, 1, 999.00, 'Pending', 'll', '2026-09-15 11:35:46'),
(6, 6, 21989.00, 'Confirmed', 'eklnfoe', '2026-09-15 11:56:40'),
(7, 9, 999.00, 'Pending', 'gujarat india', '2026-09-16 16:55:48'),
(8, 10, 100000.00, 'Delivered', 'delhi india', '2026-09-16 17:15:15'),
(9, 10, 3997.00, 'Pending', 'kolkata india', '2026-09-16 18:20:47'),
(10, 10, 50000.00, 'Pending', 'noida deklhi', '2026-09-16 18:23:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 1999.00),
(2, 2, 3, 12, 1999.00),
(3, 3, 2, 15, 2499.00),
(4, 4, 4, 2, 999.00),
(5, 5, 4, 1, 999.00),
(6, 6, 3, 11, 1999.00),
(7, 7, 4, 1, 999.00),
(8, 8, 5, 2, 50000.00),
(9, 9, 4, 2, 999.00),
(10, 9, 3, 1, 1999.00),
(11, 10, 5, 1, 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `stock`, `created_at`) VALUES
(1, 'Wireless Headphones', 'Comfortable Bluetooth headphones with clear sound.', 1499.00, 'product_1789582739_6aaadd939c60c.jpg', 20, '2026-09-13 13:25:30'),
(2, 'Smart Watch', 'Modern smart watch with fitness tracking.', 2499.00, 'product_1789582521_6aaadcb9b415a.jfif', 8, '2026-09-13 13:25:30'),
(3, 'Running Shoes', 'Lightweight shoes for daily running and exercise.', 1999.00, 'product_1789582496_6aaadca0ca79a.jfif', 0, '2026-09-13 13:25:30'),
(4, 'Laptop Backpack', 'Durable backpack with a padded laptop compartment.', 999.00, 'product_1789582158_6aaadb4e460f4.jpg', 29, '2026-09-13 13:25:30'),
(5, 'iphone 11', 'branded new iphone', 50000.00, 'product_1789582714_6aaadd7af1f3b.jpg', 7, '2026-09-16 17:06:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@shop.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', '2026-09-13 13:25:30'),
(2, 'sahil', 'sahilravate979@gmail.com', '24a15c8e6b6613267e240395780469e41c60d18bd3c9f36abca79880a7a9da42', 'user', '2026-09-13 13:40:23'),
(4, 'sahil123', 'sahil@123gmail.com', 'f446c20f74875ab4d9dbdf7cac50bfae5f03a1b4f84bbccbbc3672b2f95d3fc8', 'user', '2026-09-13 13:42:56'),
(5, 'om patil', 'om@1234gmail.com', 'a12a8ff6d6818d8f25636055cbb58f1c4e3e3c00882aed7871bee087d30e6d0f', 'user', '2026-09-15 10:17:19'),
(6, 'om patil', 'om@321gmail.com', '67f14e27ad07884d435520b253e1e769881a3865d3ba2ef02dad9f8e0e3b505b', 'user', '2026-09-15 10:18:44'),
(8, 'geeta', 'geeta@123gmail.com', 'baf023ebc08b5f8cf8d02e57e23fd1cdda76a63ea5a8228e883b7727e337a6ee', 'user', '2026-09-15 18:46:44'),
(9, 'Sahil', 'sahil@test.com', '$2y$10$UTVelxVDW5rvgsPMvZzz9OtmfOZ2nw0zhJ6e6uaSYtIFpJcODavA2', 'admin', '2026-09-16 14:38:43'),
(10, 'anna', 'anna@gmail.com', '$2y$10$r2XdO2aKeOnvwQXR88NpcudCsgXKYOcx8ZhXL85jBX6yt0dMLTWu2', 'user', '2026-09-16 17:14:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
