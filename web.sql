-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2025 at 08:34 AM
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
-- Database: `art_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Paints', '2025-03-02 11:45:30'),
(2, 'Brushes', '2025-03-02 11:45:30'),
(3, 'Canvas', '2025-03-02 11:45:30'),
(4, 'Easels', '2025-03-02 11:45:30'),
(5, 'Sketchbooks', '2025-03-02 11:45:30'),
(6, 'Water Color Skecthbooks', '2025-03-03 08:18:40'),
(11, 'Artist Palette', '2025-03-15 05:03:39'),
(12, 'Pencils', '2025-03-15 10:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `order_status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_address`, `city`, `postal_code`, `phone`, `payment_method`, `order_status`, `created_at`, `updated_at`) VALUES
(1, 13, 45.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Cash on Delivery', 'completed', '2025-03-08 07:16:09', 0),
(2, 13, 4567.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Cash on Delivery', 'Completed', '2025-03-07 06:08:43', 0),
(3, 13, 4567.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Cash on Delivery', 'completed', '2025-03-07 06:18:18', 2147483647),
(4, 13, 45957.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Cash on Delivery', 'Completed', '2025-03-07 06:19:45', 0),
(5, 13, 2345.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'GCash', 'completed', '2025-03-07 08:36:48', 2147483647),
(6, 13, 78.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Credit Card', 'completed', '2025-03-08 07:58:07', 0),
(7, 13, 234.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Bank Transfer', 'Completed', '2025-03-08 07:30:01', 0),
(8, 13, 1213.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Credit Card', 'Completed', '2025-03-08 08:10:44', 2147483647),
(9, 13, 234.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'GCash', 'Completed', '2025-03-08 08:53:39', 0),
(10, 13, 350479.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'cod', 'Completed', '2025-03-12 11:26:48', 2147483647),
(11, 13, 4567.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'cod', 'Completed', '2025-03-12 12:33:12', 2147483647),
(12, 13, 500.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', 'Cash on Delivery', 'Completed', '2025-03-13 00:00:11', 2147483647),
(13, 13, 400.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', 'cod', 'Completed', '2025-03-13 00:45:38', 2147483647),
(14, 13, 400.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', 'cod', 'completed', '2025-03-13 02:15:38', 2147483647),
(15, 13, 300.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', 'cod', 'Completed', '2025-03-13 02:45:06', 2147483647),
(16, 13, 180.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', 'cod', 'Completed', '2025-03-13 02:47:38', 2147483647),
(17, 13, 400.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', 'cod', 'placed', '2025-03-15 03:27:08', 2147483647),
(18, 13, 300.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Paid', '2025-03-15 03:35:35', 0),
(19, 13, 500.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Paid', '2025-03-15 03:36:17', 0),
(20, 13, 250.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 03:37:28', 0),
(21, 13, 500.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 03:39:32', 0),
(22, 13, 180.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 03:41:06', 0),
(23, 18, 400.00, 'Bokidnon', 'cagayan de oro', '9000', '09878657928', '', 'Completed', '2025-03-15 05:50:11', 2147483647),
(24, 17, 1180.00, 'tipolohon', 'cagayan de oro', '9000', '09099992187', '', 'Completed', '2025-03-15 06:11:23', 0),
(25, 13, 160.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 07:41:00', 0),
(26, 13, 320.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 07:46:37', 0),
(27, 13, 450.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 07:53:39', 0),
(28, 13, 970.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 08:20:00', 0),
(29, 13, 960.00, 'Tipolohon, camaman-an', 'cagayan de oro', '9000', '2147483647', '', 'Completed', '2025-03-15 10:19:14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 2, 'dam', 45.00, 1, 45.00),
(2, 2, 6, 'ertyj', 4567.00, 1, 4567.00),
(3, 3, 6, 'ertyj', 4567.00, 1, 4567.00),
(4, 4, 2, 'dam', 45.00, 1, 45.00),
(5, 4, 11, 'dsfasdfsdg', 234.00, 1, 234.00),
(6, 4, 4, 'Horse That shine', 45678.00, 1, 45678.00),
(7, 5, 8, 'Johanie', 2345.00, 1, 2345.00),
(8, 6, 5, 'as', 78.00, 1, 78.00),
(9, 7, 11, 'dsfasdfsdg', 234.00, 1, 234.00),
(10, 8, 1, 'ronald', 1213.00, 1, 1213.00),
(11, 9, 11, 'dsfasdfsdg', 234.00, 1, 234.00),
(12, 10, 6, 'ertyj', 4567.00, 1, 4567.00),
(13, 10, 11, 'dsfasdfsdg', 234.00, 1, 234.00),
(14, 10, 9, 'opo', 345678.00, 1, 345678.00),
(15, 11, 6, 'ertyj', 4567.00, 1, 4567.00),
(16, 12, 15, 'Himi miya goauche', 500.00, 1, 500.00),
(17, 13, 14, 'Fabriano Sketchbook', 400.00, 1, 400.00),
(18, 14, 14, 'Fabriano Sketchbook', 400.00, 1, 400.00),
(19, 15, 13, 'Water Color Sketchbook', 300.00, 1, 300.00),
(20, 16, 17, 'da Vinci Maestro Acrylic Paint Brush', 180.00, 1, 180.00),
(21, 17, 14, 'Fabriano Sketchbook', 400.00, 1, 400.00),
(22, 18, 13, 'Water Color Sketchbook', 300.00, 1, 300.00),
(23, 19, 15, 'Himi miya goauche', 500.00, 1, 500.00),
(24, 20, 16, 'Gouache paint Set', 250.00, 1, 250.00),
(25, 21, 15, 'Himi miya goauche', 500.00, 1, 500.00),
(26, 22, 17, 'da Vinci Maestro Acrylic Paint Brush', 180.00, 1, 180.00),
(27, 23, 14, 'Fabriano Sketchbook', 400.00, 1, 400.00),
(28, 24, 14, 'Fabriano Sketchbook', 400.00, 1, 400.00),
(29, 24, 13, 'Water Color Sketchbook', 300.00, 1, 300.00),
(30, 24, 18, 'easel', 480.00, 1, 480.00),
(31, 25, 22, 'Plastic Artist Palette', 160.00, 1, 160.00),
(32, 26, 22, 'Plastic Artist Palette', 160.00, 2, 320.00),
(33, 27, 20, 'HB acrylic paint', 450.00, 1, 450.00),
(34, 28, 14, 'Fabriano Sketchbook', 400.00, 1, 400.00),
(35, 28, 22, 'Plastic Artist Palette', 160.00, 2, 320.00),
(36, 28, 16, 'Gouache paint Set', 250.00, 1, 250.00),
(37, 29, 22, 'Plastic Artist Palette', 160.00, 1, 160.00),
(38, 29, 14, 'Fabriano Sketchbook', 400.00, 2, 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_details` text DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `payment_status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `transaction_id`, `amount`, `payment_method`, `payment_details`, `payment_date`, `payment_status`) VALUES
(1, 11, 'TXN17418200436004', 4567.00, 'cod', '', '2025-03-12 23:54:03', 'completed'),
(2, 10, 'TXN17418205313514', 350479.00, 'cod', '', '2025-03-13 00:02:11', 'completed'),
(3, 13, 'TXN17418267524324', 400.00, 'cod', '', '2025-03-13 01:45:52', 'completed'),
(4, 14, 'TXN17418326462749', 400.00, 'cod', '', '2025-03-13 03:24:06', 'completed'),
(5, 15, 'TXN17418339172360', 300.00, 'cod', '', '2025-03-13 03:45:17', 'completed'),
(6, 16, 'TXN17418340728814', 180.00, 'cod', '', '2025-03-13 03:47:52', 'completed'),
(7, 17, 'TXN17420094682193', 400.00, 'cod', '', '2025-03-15 04:31:08', 'completed'),
(8, 21, '', 500.00, 'Placeholder', NULL, '2025-03-15 11:39:32', 'Completed'),
(9, 22, '', 180.00, 'Placeholder', NULL, '2025-03-15 11:41:06', 'Completed'),
(10, 23, '', 400.00, 'Placeholder', NULL, '2025-03-15 13:50:11', 'Completed'),
(11, 24, '', 1180.00, 'Placeholder', NULL, '2025-03-15 14:11:23', 'Completed'),
(12, 25, '', 160.00, 'Placeholder', NULL, '2025-03-15 15:41:00', 'Completed'),
(13, 26, '', 320.00, 'Placeholder', NULL, '2025-03-15 15:46:37', 'Completed'),
(14, 27, '', 450.00, 'Placeholder', NULL, '2025-03-15 15:53:39', 'Completed'),
(15, 28, '', 970.00, 'Placeholder', NULL, '2025-03-15 16:20:00', 'Completed'),
(16, 29, '', 960.00, 'Placeholder', NULL, '2025-03-15 18:19:14', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `stock` int(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `stock`, `description`, `price`, `category_id`, `image_path`, `created_at`, `category`) VALUES
(13, 'Water Color Sketchbook', 16, 'This sketchbook provide ample gms to make sure that it doesn\'t bleed to the other page', 300.00, 6, 'uploads/67d21afde4ab1.jpg', '2025-03-12 23:38:37', '11'),
(14, 'Fabriano Sketchbook', 17, 'This sketchbook is good for sketching using dry medium, such as charcoal, graphite, or crayons', 400.00, 5, 'uploads/67d21b68e75a0.jpg', '2025-03-12 23:40:24', '11'),
(15, 'Himi miya goauche', 56, 'This paint combines water color and acrylic', 500.00, 1, 'uploads/67d21ba9da7f3.jpg', '2025-03-12 23:41:29', '11'),
(16, 'Gouache paint Set', 13, 'These paint brush is optically equipped to handle gouache paints', 250.00, 2, 'uploads/67d21c05278a4.jpg', '2025-03-12 23:43:01', '11'),
(17, 'da Vinci Maestro Acrylic Paint Brush', 25, 'The hair bristles of this brush is perfect for acrylic paintings', 180.00, 2, 'uploads/67d21c56d1368.jpg', '2025-03-12 23:44:22', '11'),
(18, 'easel', 30, 'Paint like a maestro with this easel.', 480.00, 4, 'uploads/67d21cb78576c.jpg', '2025-03-12 23:45:59', '11'),
(19, 'Kremer watercolor', 18, 'This highly pigmented watercolor will elevate the colors of your drawing.', 500.00, 1, 'uploads/67d21cf0bba22.jpg', '2025-03-12 23:46:56', '11'),
(20, 'HB acrylic paint', 18, 'This paint is very pigmented and will surely elevate your drawing', 450.00, 1, 'uploads/67d21d599dac4.jpg', '2025-03-12 23:48:41', '11'),
(21, 'Canvas', 40, 'This canvas frame is made out of high quality canvas cloth', 340.00, 3, 'uploads/67d21d8c635bf.jpg', '2025-03-12 23:49:32', '11'),
(22, 'Plastic Artist Palette', 25, 'An artist\'s palette is a flat, typically wooden or plastic surface used for mixing and holding paint while working on a painting. It often has a thumb hole for easy handling and comes in various shapes, such as oval or rectangular. Palettes can be used with different types of paint, including acrylics, oils, and watercolors, allowing artists to blend colors seamlessly for their artwork.', 160.00, 11, 'uploads/67d50a675b76d.jpg', '2025-03-15 05:04:39', NULL),
(23, 'Pencil Buddies - Sketching Pencils', 60, 'A high-quality sketching pencil designed for artists, offering smooth, precise lines with rich graphite for effortless shading and detailing. Ideal for sketching, shading, and fine art.', 150.00, 12, 'uploads/67d55539d1b73.jpg', '2025-03-15 10:23:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password`, `phone`, `email`, `address`, `photo`, `role`) VALUES
(12, 'Anderson', 'Cadena', 'andy', '$2y$10$Xgsil87bAnIeD7JX/tJL7eVGyUgTW3I5xV2vvMltQYC75kmhxmgYq', '09587567893', 'Anderson.cadena@gmil.com', 'Japan', 'admin_12_67d52acea7754.jpg', 'admin'),
(13, 'Jessa', 'Alamat', 'customer', '$2y$10$4RMdJE76Zels3M2z1Rn6LeMbchiRh31tmKzshUfH/vnAkqJqcHxES', '2147483647', 'jesssa.alamat@gmail.com', 'Tipolohon, camaman-an', 'user_13_67d2233a9ed95.jpg', NULL),
(15, 'Cyra', 'Samillano', 'cyco', '$2y$10$y3sc1y2x8X530Hz1v/5FteSTpXeWqTzVI8KMqS3QGMAfPOlzoThPe', '09915186758', 'cyco.samillano.coc@phinmaed.com', 'Carmen', 'user_15_67d510018ae7b.jpg', NULL),
(16, 'Lenard', 'James', 'Nard', '$2y$10$50gz6C/QeqHppcUxETgQH.gwT58d9m1y0AvEu2jXfWjs1taofy8Vy', '', '', '', 'user_16_67d511f6b33c7.jpg', NULL),
(17, 'Ronald', 'Damo', 'ron', '$2y$10$Zr1LLtmghmscPF3uWCfu1uJXGKK/7cdeOnm/6/nzMfTEkfaRKdH5a', '', 'ronalddamo24@gmail.com', '', NULL, NULL),
(18, 'John', 'Philip', 'philip', '$2y$10$P5YesGLn3OJ8jyFsSURFde04RdI2YBbkmerqY6hqxN.508bN6Q.xm', '09878657928', 'Philip.john@gmail.com', 'Bokidnon', 'user_18_67d513abddc9f.jpg', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
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
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
