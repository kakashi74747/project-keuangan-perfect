<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$tanggal_mulai = $_POST['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_POST['tanggal_selesai'] ?? date('Y-m-t');

// 1. Data untuk Kartu Ringkasan
$stmt_summary = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Pemasukan' THEN amount ELSE 0 END), 0) as total_pemasukan, COALESCE(SUM(CASE WHEN transaction_type = 'Pengeluaran' THEN amount ELSE 0 END), 0) as total_pengeluaran FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt_summary, "iss", $user_id, $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt_summary);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_summary));
$total_pemasukan = $summary['total_pemasukan']; $total_pengeluaran = $summary['total_pengeluaran']; $selisih = $total_pemasukan - $total_pengeluaran;

// 2. Data untuk Tabel Rincian Transaksi
$stmt_transactions = mysqli_prepare($koneksi, "SELECT t.*, a.account_name, c.category_name FROM transactions t JOIN accounts a ON t.account_id = a.id LEFT JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ? ORDER BY t.transaction_date DESC");
mysqli_stmt_bind_param($stmt_transactions, "iss", $user_id, $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt_transactions);
$transactions = mysqli_stmt_get_result($stmt_transactions);

// 3. Data untuk Pie Chart & Tabel Ringkasan Kategori (BARU)
$stmt_categories = mysqli_prepare($koneksi, "SELECT c.category_name, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.transaction_type = 'Pengeluaran' AND t.transaction_date BETWEEN ? AND ? GROUP BY t.category_id ORDER BY total DESC");
mysqli_stmt_bind_param($stmt_categories, "iss", $user_id, $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt_categories);
$category_summary = mysqli_stmt_get_result($stmt_categories);
$chart_labels = []; $chart_data = []; $chart_colors = ['#0d6efd', '#dc3545', '#ffc107', '#198754', '#6c757d', '#6f42c1', '#20c997', '#fd7e14'];

$page_title = "Laporan Keuangan - Uangmu App"; $active_page = 'reports';
include 'includes/header.php'; include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Laporan Keuangan</h1>
        <ol class="breadcrumb mb-4"><li class="breadcrumb-item"><a href="index.php">Dashboard</a></li><li class="breadcrumb-item active">Laporan</li></ol>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Periode Laporan</div>
            <div class="card-body">
                <form method="POST" action="reports.php" class="row g-3 align-items-end">
                    <div class="col-md-4"><label class="form-label">Dari Tanggal</label><input type="date" class="form-control" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai); ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Sampai Tanggal</label><input type="date" class="form-control" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai); ?>" required></div>
                    <div class="col-md-2"><button type="submit" name="filter_tanggal" class="btn btn-primary w-100">Tampilkan</button></div>
                    <div class="col-md-2"><a href="export_reports.php?start=<?= $tanggal_mulai; ?>&end=<?= $tanggal_selesai; ?>" class="btn btn-success w-100"><i class="fas fa-file-csv me-2"></i>Ekspor</a></div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body"><h5>Total Pemasukan</h5><h4 class="display-6">Rp <?= number_format($total_pemasukan, 0, ',', '.'); ?></h4></div></div></div>
            <div class="col-xl-4 col-md-6"><div class="card bg-danger text-white mb-4"><div class="card-body"><h5>Total Pengeluaran</h5><h4 class="display-6">Rp <?= number_format($total_pengeluaran, 0, ',', '.'); ?></h4></div></div></div>
            <div class="col-xl-4 col-md-6"><div class="card <?= $selisih >= 0 ? 'bg-primary' : 'bg-warning text-dark'; ?> text-white mb-4"><div class="card-body"><h5><?= $selisih >= 0 ? 'Laba (Surplus)' : 'Rugi (Defisit)'; ?></h5><h4 class="display-6">Rp <?= number_format($selisih, 0, ',', '.'); ?></h4></div></div></div>
        </div>
        
        <div class="row">
            <div class="col-lg-5">
                <div class="card mb-4"><div class="card-header"><i class="fas fa-chart-pie me-1"></i>Diagram Pengeluaran per Kategori</div><div class="card-body"><canvas id="myPieChart" width="100%" height="40"></canvas></div></div>
            </div>
            <div class="col-lg-7">
                <div class="card mb-4"><div class="card-header"><i class="fas fa-list-alt me-1"></i>Ringkasan Pengeluaran per Kategori</div><div class="card-body">
                    <table class="table table-striped table-sm">
                        <thead><tr><th>Kategori</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php while($cat = mysqli_fetch_assoc($category_summary)): 
                                $chart_labels[] = $cat['category_name'];
                                $chart_data[] = $cat['total'];
                            ?>
                            <tr><td><?= htmlspecialchars($cat['category_name']); ?></td><td class="text-end">Rp <?= number_format($cat['total'], 0, ',', '.'); ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>Rincian Transaksi Periode</div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped table-hover">
                    <thead><tr><th>Tanggal</th><th>Tipe</th><th>Kategori</th><th>Akun</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        <?php mysqli_data_seek($transactions, 0); while($trx = mysqli_fetch_assoc($transactions)): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($trx['transaction_date'])); ?></td>
                            <td><?php if ($trx['transaction_type'] == 'Pemasukan') echo '<span class="badge bg-success">Pemasukan</span>'; elseif ($trx['transaction_type'] == 'Pengeluaran') echo '<span class="badge bg-danger">Pengeluaran</span>'; else echo '<span class="badge bg-info">Koreksi Saldo</span>'; ?></td>
                            <td><?= htmlspecialchars($trx['category_name'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($trx['account_name']); ?></td>
                            <td class="text-end fw-bold <?= $trx['transaction_type'] == 'Pengeluaran' ? 'text-danger' : 'text-success'; ?>">Rp <?= number_format($trx['amount'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
$additional_scripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        const datatablesSimple = document.getElementById(\'datatablesSimple\');
        if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }

        var ctx = document.getElementById("myPieChart");
        var myPieChart = new Chart(ctx, {
          type: \'pie\',
          data: {
            labels: '.json_encode($chart_labels).',
            datasets: [{ data: '.json_encode($chart_data).', backgroundColor: '.json_encode(array_slice($chart_colors, 0, count($chart_labels))).', }],
          },
        });
    });
</script>';
include 'includes/footer.php';
echo $additional_scripts;
?>