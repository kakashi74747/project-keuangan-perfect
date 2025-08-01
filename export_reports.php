<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Ambil rentang tanggal dari URL
$tanggal_mulai = $_GET['start'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['end'] ?? date('Y-m-t');

// Siapkan nama file dan header untuk download
$nama_file = "Laporan_UangmuApp_" . $tanggal_mulai . "_-_" . $tanggal_selesai . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $nama_file);

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis header CSV
fputcsv($output, ['Tanggal', 'Tipe', 'Kategori', 'Akun', 'Deskripsi', 'Jumlah']);

// Query untuk mengambil data transaksi
$stmt_transactions = mysqli_prepare($koneksi, "
    SELECT t.transaction_date, t.transaction_type, c.category_name, a.account_name, t.description, t.amount 
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
    ORDER BY t.transaction_date ASC
");
mysqli_stmt_bind_param($stmt_transactions, "iss", $user_id, $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt_transactions);
$transactions = mysqli_stmt_get_result($stmt_transactions);

// Tulis setiap baris transaksi ke file CSV
while ($trx = mysqli_fetch_assoc($transactions)) {
    fputcsv($output, [
        $trx['transaction_date'],
        $trx['transaction_type'],
        $trx['category_name'] ?? 'N/A',
        $trx['account_name'],
        $trx['description'],
        $trx['amount']
    ]);
}

fclose($output);
exit();
?>