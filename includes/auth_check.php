<?php
// koneksi.php sudah memanggil config.php dan session_start()

if (!isset($_SESSION['user_id'])) {
    // Jika session user_id tidak ada, redirect ke halaman login
    // Menggunakan BASE_URL untuk path yang pasti benar
    header("Location: " . BASE_URL . "login.php");
    exit();
}
?>