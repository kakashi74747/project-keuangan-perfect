<?php
require_once 'config.php';

// Konfigurasi Error & Database
ini_set('display_errors', 1);
error_reporting(E_ALL);
$host = "localhost";
$user = "root";
$pass = "";
$db   = "uangmu_app_db";

// Koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// =================================================================
//      DATABASE AUTO-HEALER & SETUP (VERSI FINAL KOMPREHENSIF)
// =================================================================

// --- BAGIAN UNTUK FITUR ASET ---
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'assets'")) > 0) {
    if (mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM `assets` LIKE 'api_symbol'")) > 0) {
        mysqli_query($koneksi, "ALTER TABLE `assets` DROP COLUMN `api_symbol`");
    }
    $result_check_quantity = mysqli_query($koneksi, "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '$db' AND table_name = 'assets' AND column_name = 'quantity'");
    if ($result_check_quantity && mysqli_fetch_assoc($result_check_quantity)['DATA_TYPE'] != 'varchar') {
        mysqli_query($koneksi, "ALTER TABLE `assets` MODIFY `quantity` VARCHAR(50) NOT NULL");
    }
    if (mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM `assets` LIKE 'current_price'")) == 0) {
        mysqli_query($koneksi, "ALTER TABLE `assets` ADD `current_price` DECIMAL(15,2) DEFAULT 0.00 AFTER `average_buy_price`");
    }
}
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'asset_price_history'")) == 0) {
    $sql_create_history = "CREATE TABLE `asset_price_history` ( `id` INT(11) NOT NULL AUTO_INCREMENT, `asset_id` INT(11) NOT NULL, `price` DECIMAL(15,2) NOT NULL, `update_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `asset_id` (`asset_id`), CONSTRAINT `history_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_history);
}

// --- BAGIAN UNTUK FITUR TARGET TABUNGAN (GOALS) ---
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'savings_goals'")) == 0) {
    $sql_create_goals = "CREATE TABLE `savings_goals` ( `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL, `goal_name` VARCHAR(255) NOT NULL, `target_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00, `current_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00, `status` ENUM('Aktif','Selesai') NOT NULL DEFAULT 'Aktif', `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`), CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_goals);
}
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'savings_goals'")) > 0) {
    if (mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM `savings_goals` LIKE 'goal_image'")) == 0) {
        mysqli_query($koneksi, "ALTER TABLE `savings_goals` ADD `goal_image` VARCHAR(255) NULL DEFAULT NULL AFTER `goal_name`, ADD `start_date` DATE NULL AFTER `current_amount`, ADD `end_date` DATE NULL AFTER `start_date`;");
    }
}
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'goal_transactions'")) == 0) {
    $sql_create_goal_trans = "CREATE TABLE `goal_transactions` ( `id` INT(11) NOT NULL AUTO_INCREMENT, `goal_id` INT(11) NOT NULL, `account_id` INT(11) NOT NULL, `amount` DECIMAL(15,2) NOT NULL, `transaction_type` ENUM('Menabung','Menarik') NOT NULL, `transaction_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `goal_id` (`goal_id`), CONSTRAINT `goal_trans_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_goal_trans);
}

// --- BAGIAN UNTUK FITUR DANA DARURAT ---
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'emergency_fund'")) == 0) {
    $sql_create_ef = "CREATE TABLE `emergency_fund` ( `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL, `target_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00, `current_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00, PRIMARY KEY (`id`), UNIQUE KEY `user_id` (`user_id`), CONSTRAINT `ef_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_ef);
}
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'emergency_fund_transactions'")) == 0) {
    $sql_create_ef_trans = "CREATE TABLE `emergency_fund_transactions` ( `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL, `account_id` INT(11) NOT NULL, `amount` DECIMAL(15,2) NOT NULL, `transaction_type` ENUM('Menabung','Menarik') NOT NULL, `notes` VARCHAR(255) NULL, `transaction_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`), CONSTRAINT `ef_trans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_ef_trans);
}

// --- BAGIAN UNTUK FITUR UTANG & PIUTANG (BARU) ---
// Periksa & Buat Tabel `debts`
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'debts'")) == 0) {
    $sql_create_debts = "
    CREATE TABLE `debts` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `type` ENUM('Utang','Piutang') NOT NULL,
      `person_name` VARCHAR(255) NOT NULL,
      `description` TEXT NULL,
      `total_amount` DECIMAL(15,2) NOT NULL,
      `remaining_amount` DECIMAL(15,2) NOT NULL,
      `due_date` DATE NULL,
      `status` ENUM('Belum Lunas','Lunas') NOT NULL DEFAULT 'Belum Lunas',
      `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `debts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_debts);
}

// Periksa & Buat Tabel `debt_transactions`
if (mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'debt_transactions'")) == 0) {
    $sql_create_debt_trans = "
    CREATE TABLE `debt_transactions` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `debt_id` INT(11) NOT NULL,
      `account_id` INT(11) NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `transaction_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `debt_id` (`debt_id`),
      CONSTRAINT `debt_trans_ibfk_1` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($koneksi, $sql_create_debt_trans);
}
// =================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>