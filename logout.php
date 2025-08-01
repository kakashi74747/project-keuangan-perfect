<?php
// Memanggil file koneksi sudah cukup untuk memulai session
require 'includes/koneksi.php';

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Redirect ke halaman login menggunakan BASE_URL
header("location: " . BASE_URL . "login.php");
exit();
?>