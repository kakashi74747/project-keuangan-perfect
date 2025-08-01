-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2025 at 12:26 PM
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
-- Database: `uangmu_app_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Tabungan','Investasi','E-wallet','Kas','Lainnya') NOT NULL,
  `initial_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `user_id`, `account_name`, `account_type`, `initial_balance`, `current_balance`, `created_at`, `updated_at`) VALUES
(2, 2, 'bva', 'Tabungan', 10000.00, 10000.00, '2025-08-01 03:37:55', '2025-08-01 03:37:55'),
(3, 3, 'BCA', 'Investasi', 100000.00, 72000.00, '2025-08-01 03:43:37', '2025-08-01 10:06:18');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_type` varchar(100) NOT NULL,
  `quantity` varchar(50) NOT NULL,
  `average_buy_price` decimal(15,2) NOT NULL,
  `current_price` decimal(15,2) DEFAULT 0.00,
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `user_id`, `asset_name`, `asset_type`, `quantity`, `average_buy_price`, `current_price`, `purchase_date`, `created_at`, `updated_at`) VALUES
(4, 3, 'EMAS Kuning', 'Emas', '1.00000000', 1950000.00, 2125000.00, NULL, '2025-08-01 06:04:11', '2025-08-01 09:32:27'),
(5, 3, 'er', 'e', '0.7', 10000.00, 874000.00, '2025-08-01', '2025-08-01 09:16:54', '2025-08-01 09:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `asset_price_history`
--

CREATE TABLE `asset_price_history` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `update_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_price_history`
--

INSERT INTO `asset_price_history` (`id`, `asset_id`, `price`, `update_date`) VALUES
(4, 4, 2000000.00, '2025-08-01 06:04:33'),
(5, 4, 95000.00, '2025-08-01 06:04:49'),
(6, 4, 95000.00, '2025-08-01 06:09:38'),
(7, 4, 45000.00, '2025-08-01 06:10:14'),
(8, 4, 2045000.00, '2025-08-01 06:10:25'),
(9, 4, 2095000.00, '2025-08-01 06:13:17'),
(10, 4, 2045000.00, '2025-08-01 06:13:25'),
(11, 4, 2145000.00, '2025-08-01 07:15:01'),
(12, 4, 2115000.00, '2025-08-01 07:15:09'),
(13, 5, 20000.00, '2025-08-01 09:21:04'),
(14, 5, 30000.00, '2025-08-01 09:21:13'),
(15, 5, 40000.00, '2025-08-01 09:25:21'),
(16, 5, 120000.00, '2025-08-01 09:26:03'),
(17, 4, 2125000.00, '2025-08-01 09:32:27'),
(18, 5, 94000.00, '2025-08-01 09:32:35'),
(19, 5, 74000.00, '2025-08-01 09:32:43'),
(20, 5, -126000.00, '2025-08-01 09:32:51'),
(21, 5, 874000.00, '2025-08-01 09:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_type` enum('Pemasukan','Pengeluaran') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `category_name`, `category_type`, `created_at`) VALUES
(3, 3, 'Gaji', 'Pemasukan', '2025-08-01 03:42:58'),
(4, 3, 'Maling', 'Pemasukan', '2025-08-01 03:43:07'),
(5, 3, 'Makan', 'Pengeluaran', '2025-08-01 03:43:13');

-- --------------------------------------------------------

--
-- Table structure for table `debts`
--

CREATE TABLE `debts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `debt_type` enum('Utang','Piutang') NOT NULL,
  `person_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `remaining_amount` decimal(15,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Belum Lunas','Lunas') NOT NULL DEFAULT 'Belum Lunas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debt_payments`
--

CREATE TABLE `debt_payments` (
  `id` int(11) NOT NULL,
  `debt_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debt_transactions`
--

CREATE TABLE `debt_transactions` (
  `id` int(11) NOT NULL,
  `debt_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_fund`
--

CREATE TABLE `emergency_fund` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_fund`
--

INSERT INTO `emergency_fund` (`id`, `user_id`, `target_amount`, `current_amount`) VALUES
(1, 3, 100000.00, 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `emergency_fund_transactions`
--

CREATE TABLE `emergency_fund_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('Menabung','Menarik') NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_fund_transactions`
--

INSERT INTO `emergency_fund_transactions` (`id`, `user_id`, `account_id`, `amount`, `transaction_type`, `notes`, `transaction_date`) VALUES
(1, 3, 3, 10000.00, 'Menabung', '', '2025-08-01 09:59:56');

-- --------------------------------------------------------

--
-- Table structure for table `goal_transactions`
--

CREATE TABLE `goal_transactions` (
  `id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('Menabung','Menarik') NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goal_transactions`
--

INSERT INTO `goal_transactions` (`id`, `goal_id`, `account_id`, `amount`, `transaction_type`, `transaction_date`) VALUES
(1, 5, 3, 20000.00, 'Menabung', '2025-08-01 07:03:10'),
(2, 5, 3, 1000.00, 'Menabung', '2025-08-01 07:07:45'),
(3, 5, 3, 4000.00, 'Menabung', '2025-08-01 07:15:52'),
(4, 5, 3, 5000.00, 'Menarik', '2025-08-01 09:39:26'),
(5, 5, 3, 5000.00, 'Menarik', '2025-08-01 09:39:33'),
(6, 5, 3, 19000.00, 'Menabung', '2025-08-01 10:06:18');

-- --------------------------------------------------------

--
-- Table structure for table `savings_goals`
--

CREATE TABLE `savings_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(255) NOT NULL,
  `goal_image` varchar(255) DEFAULT NULL,
  `target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Aktif','Selesai') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `savings_goals`
--

INSERT INTO `savings_goals` (`id`, `user_id`, `goal_name`, `goal_image`, `target_amount`, `current_amount`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(5, 3, 'Laptop OP', '688c669cadd4f.jpg', 10000000.00, 34000.00, '2025-08-01', '2025-08-31', 'Aktif', '2025-08-01 07:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Pemasukan','Pengeluaran','Koreksi Saldo') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `account_id`, `category_id`, `transaction_type`, `amount`, `description`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 5, 'Pengeluaran', 10000.00, 'makan', '2025-08-01', '2025-08-01 03:48:17', '2025-08-01 03:48:17'),
(2, 3, 3, 5, 'Pengeluaran', 20000.00, 'MAKAN', '2025-08-01', '2025-08-01 06:33:46', '2025-08-01 06:33:46'),
(3, 3, 3, NULL, 'Pengeluaran', 20000.00, 'Menabung untuk target: Laptop OP', '2025-08-01', '2025-08-01 07:03:10', '2025-08-01 07:03:10'),
(4, 3, 3, NULL, 'Pengeluaran', 1000.00, 'Menabung untuk target: Laptop OP', '2025-08-01', '2025-08-01 07:07:45', '2025-08-01 07:07:45'),
(5, 3, 3, NULL, 'Pengeluaran', 4000.00, 'Menabung untuk target: Laptop OP', '2025-08-01', '2025-08-01 07:15:52', '2025-08-01 07:15:52'),
(6, 3, 3, NULL, 'Pengeluaran', 20000.00, 'Beli aset: er', '2025-08-01', '2025-08-01 09:16:54', '2025-08-01 09:16:54'),
(7, 3, 3, NULL, 'Pemasukan', 10000.00, 'Jual aset: er', '2025-08-01', '2025-08-01 09:17:05', '2025-08-01 09:17:05'),
(8, 3, 3, NULL, 'Pemasukan', 20000.00, 'Jual aset: er', '2025-08-01', '2025-08-01 09:25:40', '2025-08-01 09:25:40'),
(9, 3, 3, NULL, 'Pemasukan', 36000.00, 'Jual aset: er', '2025-08-01', '2025-08-01 09:26:55', '2025-08-01 09:26:55'),
(10, 3, 3, NULL, 'Pemasukan', 5000.00, 'Tarik dana dari target: Laptop OP', '2025-08-01', '2025-08-01 09:39:26', '2025-08-01 09:39:26'),
(11, 3, 3, NULL, 'Pemasukan', 5000.00, 'Tarik dana dari target: Laptop OP', '2025-08-01', '2025-08-01 09:39:33', '2025-08-01 09:39:33'),
(12, 3, 3, NULL, 'Pengeluaran', 19000.00, 'Menabung untuk target: Laptop OP', '2025-08-01', '2025-08-01 10:06:18', '2025-08-01 10:06:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `password`, `created_at`) VALUES
(1, 'kaka', 'kaka', 'mama', 'kaka123', '2025-08-01 03:32:29'),
(2, 'karim', 'karim', '', 'karim123', '2025-08-01 03:35:22'),
(3, 'mamat', 'Mamat', '', 'mamat123', '2025-08-01 03:40:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `debts`
--
ALTER TABLE `debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debt_id` (`debt_id`);

--
-- Indexes for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debt_id` (`debt_id`);

--
-- Indexes for table `emergency_fund`
--
ALTER TABLE `emergency_fund`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `emergency_fund_transactions`
--
ALTER TABLE `emergency_fund_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `goal_transactions`
--
ALTER TABLE `goal_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Indexes for table `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `debts`
--
ALTER TABLE `debts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_fund`
--
ALTER TABLE `emergency_fund`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `emergency_fund_transactions`
--
ALTER TABLE `emergency_fund_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `goal_transactions`
--
ALTER TABLE `goal_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `savings_goals`
--
ALTER TABLE `savings_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debts`
--
ALTER TABLE `debts`
  ADD CONSTRAINT `debts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  ADD CONSTRAINT `debt_trans_ibfk_1` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_fund`
--
ALTER TABLE `emergency_fund`
  ADD CONSTRAINT `ef_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_fund_transactions`
--
ALTER TABLE `emergency_fund_transactions`
  ADD CONSTRAINT `ef_trans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goal_transactions`
--
ALTER TABLE `goal_transactions`
  ADD CONSTRAINT `goal_trans_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
