<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// === LOGIKA PENGAMBILAN DATA UNTUK KARTU RINGKASAN ===

// 1. Total Saldo Semua Akun (Uang Cair)
$query_total_saldo = mysqli_query($koneksi, "SELECT SUM(current_balance) AS total FROM accounts WHERE user_id = $user_id");
$total_saldo = mysqli_fetch_assoc($query_total_saldo)['total'] ?? 0;

// 2. Total Nilai Aset (Investasi)
$query_total_aset = mysqli_query($koneksi, "SELECT SUM(current_price) AS total FROM assets WHERE user_id = $user_id");
$total_aset = mysqli_fetch_assoc($query_total_aset)['total'] ?? 0;

// 3. Kekayaan Bersih (Saldo + Aset)
$kekayaan_bersih = $total_saldo + $total_aset;

// 4. Total Pemasukan & Pengeluaran Bulan Ini
$bulan_ini = date('Y-m');
$query_pemasukan = mysqli_query($koneksi, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND transaction_type = 'Pemasukan' AND DATE_FORMAT(transaction_date, '%Y-%m') = '$bulan_ini'");
$pemasukan_bulan_ini = mysqli_fetch_assoc($query_pemasukan)['total'] ?? 0;

$query_pengeluaran = mysqli_query($koneksi, "SELECT SUM(amount) AS total FROM transactions WHERE user_id = $user_id AND transaction_type = 'Pengeluaran' AND DATE_FORMAT(transaction_date, '%Y-%m') = '$bulan_ini'");
$pengeluaran_bulan_ini = mysqli_fetch_assoc($query_pengeluaran)['total'] ?? 0;


// === LOGIKA PENGAMBILAN DATA UNTUK GRAFIK (30 HARI TERAKHIR) ===
$tanggal_mulai_chart = date('Y-m-d', strtotime('-29 days'));
$tanggal_selesai_chart = date('Y-m-d');
$chart_labels = [];
$current_date = new DateTime($tanggal_mulai_chart);
$end_date = new DateTime($tanggal_selesai_chart);
while ($current_date <= $end_date) {
    $chart_labels[] = $current_date->format('d M');
    $chart_data_pemasukan[$current_date->format('Y-m-d')] = 0;
    $chart_data_pengeluaran[$current_date->format('Y-m-d')] = 0;
    $current_date->modify('+1 day');
}
$query_chart_in = mysqli_query($koneksi, "SELECT DATE(transaction_date) as tanggal, SUM(amount) as total FROM transactions WHERE user_id = $user_id AND transaction_type = 'Pemasukan' AND transaction_date BETWEEN '$tanggal_mulai_chart' AND '$tanggal_selesai_chart' GROUP BY tanggal");
while($row = mysqli_fetch_assoc($query_chart_in)) { $chart_data_pemasukan[$row['tanggal']] = $row['total']; }
$query_chart_out = mysqli_query($koneksi, "SELECT DATE(transaction_date) as tanggal, SUM(amount) as total FROM transactions WHERE user_id = $user_id AND transaction_type = 'Pengeluaran' AND transaction_date BETWEEN '$tanggal_mulai_chart' AND '$tanggal_selesai_chart' GROUP BY tanggal");
while($row = mysqli_fetch_assoc($query_chart_out)) { $chart_data_pengeluaran[$row['tanggal']] = $row['total']; }


$page_title = 'Dashboard - Uangmu App';
$active_page = 'dashboard';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Dashboard</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</li>
        </ol>
        
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-secondary text-white mb-4">
                    <div class="card-body">
                        <h6><i class="fas fa-landmark me-2"></i>Kekayaan Bersih</h6>
                        <h4 class="display-6">Rp <?= number_format($kekayaan_bersih, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-secondary text-white mb-4">
                    <div class="card-body">
                        <h6><i class="fas fa-wallet me-2"></i>Total Saldo Akun</h6>
                        <h4 class="display-6">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <h6><i class="fas fa-arrow-down me-2"></i>Pemasukan Bulan Ini</h6>
                        <h4 class="display-6">Rp <?= number_format($pemasukan_bulan_ini, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-danger text-white mb-4">
                    <div class="card-body">
                        <h6><i class="fas fa-arrow-up me-2"></i>Pengeluaran Bulan Ini</h6>
                        <h4 class="display-6">Rp <?= number_format($pengeluaran_bulan_ini, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-chart-area me-1"></i>Grafik Arus Kas (30 Hari Terakhir)</div>
            <div class="card-body"><canvas id="myAreaChart" width="100%" height="40"></canvas></div>
        </div>

    </div>
</main>

<?php
$additional_scripts = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script>
    Chart.defaults.global.defaultFontFamily = \'-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif\';
    Chart.defaults.global.defaultFontColor = \'#292b2c\';
    var ctx = document.getElementById("myAreaChart");
    var myLineChart = new Chart(ctx, {
      type: \'line\',
      data: {
        labels: '.json_encode($chart_labels).',
        datasets: [{
          label: "Pemasukan",
          lineTension: 0.3, backgroundColor: "rgba(2,117,216,0.2)", borderColor: "rgba(2,117,216,1)",
          pointRadius: 5, pointBackgroundColor: "rgba(2,117,216,1)", pointBorderColor: "rgba(255,255,255,0.8)",
          pointHoverRadius: 5, pointHoverBackgroundColor: "rgba(2,117,216,1)", pointHitRadius: 50, pointBorderWidth: 2,
          data: '.json_encode(array_values($chart_data_pemasukan)).',
        },
        {
          label: "Pengeluaran",
          lineTension: 0.3, backgroundColor: "rgba(220,53,69,0.2)", borderColor: "rgba(220,53,69,1)",
          pointRadius: 5, pointBackgroundColor: "rgba(220,53,69,1)", pointBorderColor: "rgba(255,255,255,0.8)",
          pointHoverRadius: 5, pointHoverBackgroundColor: "rgba(220,53,69,1)", pointHitRadius: 50, pointBorderWidth: 2,
          data: '.json_encode(array_values($chart_data_pengeluaran)).',
        }],
      },
      options: {
        scales: {
          xAxes: [{ time: { unit: \'date\' }, gridLines: { display: false }, ticks: { maxTicksLimit: 7 } }],
          yAxes: [{
            ticks: { min: 0, maxTicksLimit: 5, callback: function(value) { return \'Rp \' + new Intl.NumberFormat(\'id-ID\').format(value); } },
            gridLines: { color: "rgba(0, 0, 0, .125)" }
          }],
        },
        legend: { display: true }
      }
    });
</script>';

include 'includes/footer.php';
echo $additional_scripts;
?>