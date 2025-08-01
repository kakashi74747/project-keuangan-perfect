<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// Cek apakah user sudah punya data dana darurat, jika tidak, buatkan.
$ef_data = mysqli_query($koneksi, "SELECT * FROM emergency_fund WHERE user_id = $user_id");
if (mysqli_num_rows($ef_data) == 0) {
    mysqli_query($koneksi, "INSERT INTO emergency_fund (user_id, target_amount, current_amount) VALUES ($user_id, 0, 0)");
    $ef_data = mysqli_query($koneksi, "SELECT * FROM emergency_fund WHERE user_id = $user_id");
}
$ef = mysqli_fetch_assoc($ef_data);

// --- LOGIKA FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    mysqli_begin_transaction($koneksi);
    try {
        if ($_POST['action'] == 'set_target') {
            $target_amount = (float)str_replace('.', '', $_POST['target_amount'] ?? 0);
            $stmt = mysqli_prepare($koneksi, "UPDATE emergency_fund SET target_amount = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "di", $target_amount, $user_id);
            mysqli_stmt_execute($stmt);
            $_SESSION['pesan_sukses'] = "Target dana darurat berhasil diatur.";
        }
        
        elseif ($_POST['action'] == 'adjust_fund') {
            $account_id = (int)$_POST['account_id'];
            $amount = (float)str_replace('.', '', $_POST['amount'] ?? 0);
            $type = $_POST['type'];
            $notes = mysqli_real_escape_string($koneksi, $_POST['notes']);

            if ($type == 'Menabung') {
                $stmt_cek = mysqli_prepare($koneksi, "SELECT current_balance FROM accounts WHERE id = ? AND user_id = ?");
                mysqli_stmt_bind_param($stmt_cek, "ii", $account_id, $user_id);
                mysqli_stmt_execute($stmt_cek);
                $akun = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
                if (!$akun || $akun['current_balance'] < $amount) { throw new Exception("Saldo tidak mencukupi!"); }

                $stmt_upd_acc = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_upd_acc, "di", $amount, $account_id);
                mysqli_stmt_execute($stmt_upd_acc);

                $stmt_upd_ef = mysqli_prepare($koneksi, "UPDATE emergency_fund SET current_amount = current_amount + ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt_upd_ef, "di", $amount, $user_id);
                mysqli_stmt_execute($stmt_upd_ef);
                $_SESSION['pesan_sukses'] = "Berhasil menambah dana darurat.";

            } elseif ($type == 'Menarik') {
                if ($ef['current_amount'] < $amount) { throw new Exception("Dana darurat tidak mencukupi!"); }

                $stmt_upd_acc = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_upd_acc, "di", $amount, $account_id);
                mysqli_stmt_execute($stmt_upd_acc);

                $stmt_upd_ef = mysqli_prepare($koneksi, "UPDATE emergency_fund SET current_amount = current_amount - ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt_upd_ef, "di", $amount, $user_id);
                mysqli_stmt_execute($stmt_upd_ef);
                $_SESSION['pesan_sukses'] = "Berhasil menarik dana darurat.";
            }

            // Catat di riwayat
            $stmt_hist = mysqli_prepare($koneksi, "INSERT INTO emergency_fund_transactions (user_id, account_id, amount, transaction_type, notes) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_hist, "iidss", $user_id, $account_id, $amount, $type, $notes);
            mysqli_stmt_execute($stmt_hist);
        }
        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['pesan_error'] = $e->getMessage();
    }
    header("Location: emergency_fund.php");
    exit();
}

if (isset($_SESSION['pesan_sukses'])) { $pesan_sukses = $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); }
if (isset($_SESSION['pesan_error'])) { $pesan_error = $_SESSION['pesan_error']; unset($_SESSION['pesan_error']); }

$queryAccounts = mysqli_query($koneksi, "SELECT * FROM accounts WHERE user_id = $user_id ORDER BY account_name ASC");
$queryHistory = mysqli_query($koneksi, "SELECT eft.*, a.account_name FROM emergency_fund_transactions eft JOIN accounts a ON eft.account_id = a.id WHERE eft.user_id = $user_id ORDER BY eft.transaction_date DESC");
$progress = ($ef['target_amount'] > 0) ? ($ef['current_amount'] / $ef['target_amount']) * 100 : 0;
$progress = min($progress, 100);

$page_title = "Dana Darurat - Uangmu App";
$active_page = 'emergency_fund';
include 'includes/header.php'; include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Dana Darurat</h1>
        <ol class="breadcrumb mb-4"><li class="breadcrumb-item"><a href="index.php">Dashboard</a></li><li class="breadcrumb-item active">Dana Darurat</li></ol>
        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        
        <div class="row">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark"><i class="fas fa-shield-alt me-1"></i>Status Dana Darurat Anda</div>
                    <div class="card-body">
                        <div class="text-center">
                            <small class="text-muted">Dana Terkumpul</small>
                            <h2 class="display-5 fw-bold">Rp <?= number_format($ef['current_amount'], 0, ',', '.'); ?></h2>
                            <p class="text-muted">dari target Rp <?= number_format($ef['target_amount'], 0, ',', '.'); ?></p>
                        </div>
                        <div class="progress mt-3 mb-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress; ?>%;" aria-valuenow="<?= $progress; ?>"><?= number_format($progress, 1); ?>%</div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-center">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#adjustFundModal" data-type="Menabung"><i class="fas fa-plus me-2"></i>Tambah Dana</button>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#adjustFundModal" data-type="Menarik"><i class="fas fa-minus me-2"></i>Tarik Dana</button>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-history me-1"></i>Riwayat Transaksi Dana Darurat</div>
                    <div class="card-body">
                        <table class="table table-sm table-striped">
                            <tbody>
                                <?php while($hist = mysqli_fetch_assoc($queryHistory)): ?>
                                <tr>
                                    <td><?= date('d M Y, H:i', strtotime($hist['transaction_date'])); ?></td>
                                    <td>
                                        <?php if($hist['transaction_type'] == 'Menabung'): ?>
                                            <span class="badge bg-success">Menabung</span> dari Akun: <?= htmlspecialchars($hist['account_name']); ?>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Menarik</span> ke Akun: <?= htmlspecialchars($hist['account_name']); ?>
                                        <?php endif; ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($hist['notes']); ?></small>
                                    </td>
                                    <td class="text-end fw-bold <?= $hist['transaction_type'] == 'Menarik' ? 'text-success' : 'text-danger'; ?>">Rp <?= number_format($hist['amount'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-bullseye me-1"></i>Atur Target Anda</div>
                    <div class="card-body">
                        <form method="POST" action="emergency_fund.php">
                            <input type="hidden" name="action" value="set_target">
                            <div class="mb-3"><label class="form-label">Jumlah Target (Rp)</label><input type="text" class="form-control price-format" name="target_amount" value="<?= number_format($ef['target_amount'], 0, ',', ''); ?>"></div>
                            <button type="submit" class="btn btn-primary w-100">Simpan Target</button>
                        </form>
                    </div>
                </div>
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h5 class="card-title">Apa itu Dana Darurat?</h5>
                        <p class="small">Dana darurat adalah dana khusus yang Anda siapkan untuk kebutuhan tak terduga, seperti biaya medis, perbaikan mendadak, atau kehilangan pekerjaan.</p>
                        <p class="small">Idealnya, besarnya adalah **3-6 kali pengeluaran bulanan Anda**.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="adjustFundModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="adjustFundModalLabel"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="emergency_fund.php">
            <input type="hidden" name="action" value="adjust_fund">
            <input type="hidden" name="type" id="modal-type">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" id="modal-account-label"></label><select class="form-select" name="account_id" required><option value="">-- Pilih Akun --</option><?php mysqli_data_seek($queryAccounts, 0); while($acc = mysqli_fetch_assoc($queryAccounts)) { echo "<option value='{$acc['id']}'>".htmlspecialchars($acc['account_name'])." (Rp ".number_format($acc['current_balance'], 0, ',', '.').")</option>"; } ?></select></div>
                <div class="mb-3"><label class="form-label">Jumlah (Rp)</label><input type="text" class="form-control price-format" name="amount" placeholder="0" required></div>
                <div class="mb-3"><label class="form-label">Catatan (Opsional)</label><input type="text" class="form-control" name="notes" placeholder="Cth: Untuk biaya servis motor"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="modal-submit-button">Simpan</button></div>
        </form>
    </div></div>
</div>

<?php
$additional_scripts = '
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        function formatRupiah(angkaStr) { let number_string = angkaStr.replace(/[^0-9]/g, \'\'); return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
        document.querySelectorAll(\'.price-format\').forEach(input => {
            input.value = formatRupiah(input.value);
            input.addEventListener(\'keyup\', function(e){ this.value = formatRupiah(this.value.replace(/\./g, \'\')); });
        });

        const adjustFundModal = document.getElementById(\'adjustFundModal\');
        if(adjustFundModal) {
            adjustFundModal.addEventListener(\'show.bs.modal\', function (event) {
                const button = event.relatedTarget;
                const type = button.getAttribute(\'data-type\');
                
                const modalTitle = adjustFundModal.querySelector(\'.modal-title\');
                const modalAccountLabel = adjustFundModal.querySelector(\'#modal-account-label\');
                const modalTypeInput = adjustFundModal.querySelector(\'#modal-type\');
                const modalSubmitButton = adjustFundModal.querySelector(\'#modal-submit-button\');

                modalTypeInput.value = type;
                if (type === \'Menabung\') {
                    modalTitle.textContent = \'Tambah ke Dana Darurat\';
                    modalAccountLabel.textContent = \'Sumber Dana\';
                    modalSubmitButton.className = \'btn btn-success\';
                    modalSubmitButton.textContent = \'Tambah Dana\';
                } else {
                    modalTitle.textContent = \'Tarik dari Dana Darurat\';
                    modalAccountLabel.textContent = \'Dana Masuk ke Akun\';
                    modalSubmitButton.className = \'btn btn-danger\';
                    modalSubmitButton.textContent = \'Tarik Dana\';
                }
            });
        }
    });
</script>';
include 'includes/footer.php';
?>