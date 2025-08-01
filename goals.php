<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// --- FUNGSI UNTUK UPLOAD GAMBAR ---
function uploadGoalImage($file) {
    if (!isset($file) || $file['error'] === 4) { return null; } // Tidak ada file diupload, bukan error
    $namaFile = $file['name']; $tmpName = $file['tmp_name']; $error = $file['error'];
    if ($error !== 0) { return false; } // Ada error saat proses upload
    $ekstensiValid = ['jpg', 'jpeg', 'png', 'gif'];
    $ekstensiFile = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
    if (!in_array($ekstensiFile, $ekstensiValid)) { return false; } // Ekstensi tidak valid
    if (!is_dir('uploads')) { mkdir('uploads'); } // Buat folder 'uploads' jika belum ada
    $namaFileBaru = uniqid('goal_') . '.' . $ekstensiFile;
    if (move_uploaded_file($tmpName, 'uploads/' . $namaFileBaru)) {
        return $namaFileBaru;
    }
    return false; // Gagal memindahkan file
}

// --- LOGIKA FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    mysqli_begin_transaction($koneksi);
    try {
        if ($_POST['action'] == 'tambah') {
            $goal_name = mysqli_real_escape_string($koneksi, $_POST['goal_name']);
            $target_amount = (float)str_replace('.', '', $_POST['target_amount'] ?? 0);
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $goal_image = uploadGoalImage($_FILES['goal_image']);
            if ($goal_image === false) { throw new Exception("Gagal mengupload gambar. Pastikan formatnya benar (JPG, PNG, GIF)."); }
            $stmt = mysqli_prepare($koneksi, "INSERT INTO savings_goals (user_id, goal_name, goal_image, target_amount, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issdss", $user_id, $goal_name, $goal_image, $target_amount, $start_date, $end_date);
            if(!mysqli_stmt_execute($stmt)) { throw new Exception("Gagal membuat target."); }
            $_SESSION['pesan_sukses'] = "Target baru berhasil dibuat.";

        } elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id'];
            $goal_name = mysqli_real_escape_string($koneksi, $_POST['goal_name']);
            $target_amount = (float)str_replace('.', '', $_POST['target_amount'] ?? 0);
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $gambar_lama = $_POST['gambar_lama'];
            $goal_image = $gambar_lama;
            if (isset($_FILES['goal_image']) && $_FILES['goal_image']['error'] !== 4) {
                $gambar_baru = uploadGoalImage($_FILES['goal_image']);
                if ($gambar_baru === false) { throw new Exception("Gagal mengupload gambar baru."); }
                if ($gambar_baru) {
                    $goal_image = $gambar_baru;
                    if (!empty($gambar_lama) && file_exists('uploads/' . $gambar_lama)) { unlink('uploads/' . $gambar_lama); }
                }
            }
            $stmt = mysqli_prepare($koneksi, "UPDATE savings_goals SET goal_name=?, goal_image=?, target_amount=?, start_date=?, end_date=? WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, "ssdssii", $goal_name, $goal_image, $target_amount, $start_date, $end_date, $id, $user_id);
            if(!mysqli_stmt_execute($stmt)) { throw new Exception("Gagal memperbarui target."); }
            $_SESSION['pesan_sukses'] = "Target berhasil diperbarui.";

        } elseif ($_POST['action'] == 'add_savings') {
            $goal_id = (int)$_POST['goal_id'];
            $account_id = (int)$_POST['sumber_dana'];
            $amount = (float)str_replace('.', '', $_POST['amount'] ?? 0);
            $stmt_cek = mysqli_prepare($koneksi, "SELECT current_balance FROM accounts WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_cek, "ii", $account_id, $user_id);
            mysqli_stmt_execute($stmt_cek);
            $akun = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
            if (!$akun || $akun['current_balance'] < $amount) { throw new Exception("Saldo tidak mencukupi!"); }
            $stmt_upd_acc = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_upd_acc, "di", $amount, $account_id);
            mysqli_stmt_execute($stmt_upd_acc);
            $stmt_upd_goal = mysqli_prepare($koneksi, "UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_upd_goal, "di", $amount, $goal_id);
            mysqli_stmt_execute($stmt_upd_goal);
            $stmt_hist = mysqli_prepare($koneksi, "INSERT INTO goal_transactions (goal_id, account_id, amount, transaction_type) VALUES (?, ?, ?, 'Menabung')");
            mysqli_stmt_bind_param($stmt_hist, "iid", $goal_id, $account_id, $amount);
            mysqli_stmt_execute($stmt_hist);
            $q_kat = mysqli_query($koneksi, "SELECT id FROM categories WHERE user_id = $user_id AND (category_name = 'Tabungan' OR category_name = 'Investasi') AND category_type = 'Pengeluaran'");
            $kat_id = mysqli_fetch_assoc($q_kat)['id'] ?? null;
            $goal_name_q = mysqli_query($koneksi, "SELECT goal_name FROM savings_goals WHERE id=$goal_id");
            $desc = "Menabung untuk target: " . mysqli_fetch_assoc($goal_name_q)['goal_name'];
            $stmt_trx = mysqli_prepare($koneksi, "INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) VALUES (?, ?, ?, 'Pengeluaran', ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt_trx, "iiids", $user_id, $account_id, $kat_id, $amount, $desc);
            mysqli_stmt_execute($stmt_trx);
            $_SESSION['pesan_sukses'] = "Berhasil menabung ke target Anda!";
        
        } elseif ($_POST['action'] == 'withdraw_savings') {
            $goal_id = (int)$_POST['goal_id'];
            $account_id = (int)$_POST['tujuan_dana'];
            $amount = (float)str_replace('.', '', $_POST['amount'] ?? 0);
            $stmt_cek = mysqli_prepare($koneksi, "SELECT current_amount, goal_name FROM savings_goals WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_cek, "ii", $goal_id, $user_id);
            mysqli_stmt_execute($stmt_cek);
            $goal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
            if (!$goal || $goal['current_amount'] < $amount) { throw new Exception("Dana yang terkumpul tidak mencukupi untuk ditarik!"); }
            $stmt_upd_acc = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_upd_acc, "di", $amount, $account_id);
            mysqli_stmt_execute($stmt_upd_acc);
            $stmt_upd_goal = mysqli_prepare($koneksi, "UPDATE savings_goals SET current_amount = current_amount - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_upd_goal, "di", $amount, $goal_id);
            mysqli_stmt_execute($stmt_upd_goal);
            $stmt_hist = mysqli_prepare($koneksi, "INSERT INTO goal_transactions (goal_id, account_id, amount, transaction_type) VALUES (?, ?, ?, 'Menarik')");
            mysqli_stmt_bind_param($stmt_hist, "iid", $goal_id, $account_id, $amount);
            mysqli_stmt_execute($stmt_hist);
            $desc = "Tarik dana dari target: " . $goal['goal_name'];
            $stmt_trx = mysqli_prepare($koneksi, "INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) VALUES (?, ?, NULL, 'Pemasukan', ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt_trx, "iids", $user_id, $account_id, $amount, $desc);
            mysqli_stmt_execute($stmt_trx);
            $_SESSION['pesan_sukses'] = "Berhasil menarik dana dari target!";

        } elseif ($_POST['action'] == 'hapus') {
            $id = (int)$_POST['id'];
            $q_img = mysqli_query($koneksi, "SELECT goal_image FROM savings_goals WHERE id=$id AND user_id=$user_id");
            if($img = mysqli_fetch_assoc($q_img)){
                if(!empty($img['goal_image']) && file_exists('uploads/' . $img['goal_image'])){ unlink('uploads/' . $img['goal_image']); }
            }
            $stmt = mysqli_prepare($koneksi, "DELETE FROM savings_goals WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
            if(mysqli_stmt_execute($stmt)) { $_SESSION['pesan_sukses'] = "Target berhasil dihapus."; } else { throw new Exception("Gagal menghapus target."); }
        }
        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['pesan_error'] = $e->getMessage();
    }
    header("Location: goals.php");
    exit();
}

if (isset($_SESSION['pesan_sukses'])) { $pesan_sukses = $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); }
if (isset($_SESSION['pesan_error'])) { $pesan_error = $_SESSION['pesan_error']; unset($_SESSION['pesan_error']); }
$queryGoals = mysqli_prepare($koneksi, "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($queryGoals, "i", $user_id);
mysqli_stmt_execute($queryGoals);
$resultGoals = mysqli_stmt_get_result($queryGoals);
$goals_data = [];
while($row = mysqli_fetch_assoc($resultGoals)){ $goals_data[] = $row; }
$queryAccounts = mysqli_query($koneksi, "SELECT * FROM accounts WHERE user_id = $user_id ORDER BY account_name ASC");

$chart_labels = []; $chart_data = []; $chart_colors = ['#0d6efd', '#dc3545', '#ffc107', '#198754', '#6c757d', '#6f42c1'];
$alokasi_data = [];
foreach($goals_data as $goal) { $alokasi_data[$goal['goal_name']] = $goal['current_amount']; }
arsort($alokasi_data);
foreach($alokasi_data as $nama => $total) { $chart_labels[] = $nama; $chart_data[] = $total; }

$form_mode = 'tambah';
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $form_mode = 'edit';
    $id_to_edit = (int)$_GET['id'];
    foreach($goals_data as $goal) { if($goal['id'] == $id_to_edit) { $edit_data = $goal; break; } }
}

$page_title = "Target Tabungan - Uangmu App"; $active_page = 'goals';
include 'includes/header.php'; include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Target Tabungan (Goals)</h1>
        <ol class="breadcrumb mb-4"><li class="breadcrumb-item"><a href="index.php">Dashboard</a></li><li class="breadcrumb-item active">Target Tabungan</li></ol>
        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-plus me-1"></i><?= $form_mode == 'edit' ? 'Edit Target: ' . htmlspecialchars($edit_data['goal_name']) : 'Buat Target Baru'; ?></div>
            <div class="card-body">
                <form method="POST" action="goals.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?><input type="hidden" name="id" value="<?= $edit_data['id']; ?>"><input type="hidden" name="gambar_lama" value="<?= $edit_data['goal_image']; ?>"><?php endif; ?>
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Nama Target</label><input type="text" class="form-control" name="goal_name" value="<?= htmlspecialchars($edit_data['goal_name'] ?? ''); ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Jumlah Target (Rp)</label><input type="text" class="form-control price-format" name="target_amount" value="<?= $edit_data['target_amount'] ?? ''; ?>" required></div></div>
                    <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Tanggal Mulai</label><input type="date" class="form-control" name="start_date" value="<?= $edit_data['start_date'] ?? date('Y-m-d'); ?>"></div><div class="col-md-4 mb-3"><label class="form-label">Tanggal Target</label><input type="date" class="form-control" name="end_date" value="<?= $edit_data['end_date'] ?? ''; ?>"></div><div class="col-md-4 mb-3"><label class="form-label">Gambar (<?= $form_mode == 'edit' ? 'Ganti jika perlu' : 'Opsional'; ?>)</label><input type="file" class="form-control" name="goal_image"></div></div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end"><?php if($form_mode == 'edit'): ?><a href="goals.php" class="btn btn-secondary">Batal Edit</a><button type="submit" class="btn btn-warning">Simpan Perubahan</button><?php else: ?><button type="submit" class="btn btn-primary">Simpan Target</button><?php endif; ?></div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-4"><div class="card mb-4"><div class="card-header"><i class="fas fa-chart-pie me-1"></i>Distribusi Dana Tabungan</div><div class="card-body"><canvas id="myPieChart" width="100%" height="40"></canvas></div></div></div>
            <div class="col-xl-8">
                <?php if (empty($goals_data)): ?>
                    <div class="alert alert-info">Anda belum memiliki target tabungan. Silakan buat satu menggunakan form di atas.</div>
                <?php else: ?>
                <?php foreach($goals_data as $goal): 
                    $progress = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                    $progress = min($progress, 100);
                    $image_path = !empty($goal['goal_image']) && file_exists('uploads/' . $goal['goal_image']) ? 'uploads/' . $goal['goal_image'] : 'https://via.placeholder.com/400x300.png/007bff/FFFFFF?text=UangmuApp';
                    $days_left = 'N/A';
                    if (!empty($goal['end_date'])) {
                        $today = new DateTime(); $target_date = new DateTime($goal['end_date']);
                        if ($today > $target_date) { $days_left = '<span class="text-danger">Terlewat</span>'; } 
                        else { $interval = $today->diff($target_date); $days_left = $interval->days . ' hari lagi'; }
                    }
                ?>
                <div class="card mb-3">
                    <div class="row g-0">
                        <div class="col-md-4"><img src="<?= $image_path; ?>" class="img-fluid rounded-start" style="height:100%; object-fit: cover;" alt="<?= htmlspecialchars($goal['goal_name']); ?>"></div>
                        <div class="col-md-8"><div class="card-body d-flex flex-column h-100">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><?= htmlspecialchars($goal['goal_name']); ?></h5>
                                <div class="dropdown"><button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="goals.php?action=edit&id=<?= $goal['id']; ?>">Edit</a></li><li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $goal['id']; ?>">Hapus</button></li></ul></div>
                            </div>
                            <small>Terkumpul: <strong>Rp <?= number_format($goal['current_amount'], 0, ',', '.'); ?></strong> dari Rp <?= number_format($goal['target_amount'], 0, ',', '.'); ?></small>
                            <div class="progress mt-2 mb-2"><div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress; ?>%;"></div></div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted"><?= number_format($progress, 1); ?>% Tercapai</small>
                                <span class="badge bg-warning text-dark"><?= $days_left; ?></span>
                            </div>
                            <div class="mt-auto">
                                <div class="btn-group w-100"><button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSavingsModal<?= $goal['id']; ?>"><i class="fas fa-plus"></i> Menabung</button><button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawSavingsModal<?= $goal['id']; ?>"><i class="fas fa-minus"></i> Tarik Dana</button></div>
                            </div>
                        </div></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
    
<?php foreach ($goals_data as $goal) { ?>
<div class="modal fade" id="addSavingsModal<?= $goal['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-success text-white"><h5 class="modal-title">Menabung untuk: <?= htmlspecialchars($goal['goal_name']); ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST" action="goals.php"><input type="hidden" name="action" value="add_savings"><input type="hidden" name="goal_id" value="<?= $goal['id']; ?>"><div class="modal-body"><div class="mb-3"><label class="form-label">Sumber Dana</label><select class="form-select" name="sumber_dana" required><option value="">-- Pilih Akun --</option><?php mysqli_data_seek($queryAccounts, 0); while($acc = mysqli_fetch_assoc($queryAccounts)) { echo "<option value='{$acc['id']}'>".htmlspecialchars($acc['account_name'])." (Rp ".number_format($acc['current_balance'], 0, ',', '.').")</option>"; } ?></select></div><div class="mb-3"><label class="form-label">Jumlah (Rp)</label><input type="text" class="form-control price-format" name="amount" placeholder="0" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div></form></div></div></div>
<div class="modal fade" id="withdrawSavingsModal<?= $goal['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-danger text-white"><h5 class="modal-title">Tarik Dana dari: <?= htmlspecialchars($goal['goal_name']); ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST" action="goals.php"><input type="hidden" name="action" value="withdraw_savings"><input type="hidden" name="goal_id" value="<?= $goal['id']; ?>"><div class="modal-body"><div class="mb-3"><label class="form-label">Dana Masuk ke Akun</label><select class="form-select" name="tujuan_dana" required><option value="">-- Pilih Akun --</option><?php mysqli_data_seek($queryAccounts, 0); while($acc = mysqli_fetch_assoc($queryAccounts)) { echo "<option value='{$acc['id']}'>".htmlspecialchars($acc['account_name'])."</option>"; } ?></select></div><div class="mb-3"><label class="form-label">Jumlah Penarikan (Rp)</label><input type="text" class="form-control price-format" name="amount" placeholder="0" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Tarik Dana</button></div></form></div></div></div>
<div class="modal fade" id="hapusModal<?= $goal['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="goals.php"><input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $goal['id']; ?>"><div class="modal-body"><p>Yakin ingin menghapus target <strong><?= htmlspecialchars($goal['goal_name']); ?></strong>?</p><p class="text-danger small">Tindakan ini tidak bisa dibatalkan.</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div></form></div></div></div>
<?php } ?>
    
<?php
$additional_scripts = '
<style>
.goal-card .card-img-top { height: 180px; object-fit: cover; }
.progress { height: 8px; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        function formatRupiah(angkaStr) { let number_string = angkaStr.replace(/[^0-9]/g, \'\'); return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
        function unformatRupiah(str) { return str.replace(/\./g, \'\'); }
        document.querySelectorAll(\'.price-format\').forEach(input => {
            let hiddenInput = document.querySelector(`[name="${input.name.replace(\'_formatted\', \'\')}"][type="hidden"]`);
            if (hiddenInput) {
                let initialValue = hiddenInput.value;
                input.value = formatRupiah(initialValue.toString());
            }
            input.addEventListener(\'keyup\', function(e){ 
                let cleanValue = unformatRupiah(this.value);
                if (hiddenInput) { hiddenInput.value = cleanValue; }
                this.value = formatRupiah(cleanValue);
            });
        });
        var ctx = document.getElementById("myPieChart");
        if (ctx) {
            var myPieChart = new Chart(ctx, { type: \'pie\', data: { labels: '.json_encode($chart_labels).', datasets: [{ data: '.json_encode($chart_data).', backgroundColor: '.json_encode(array_slice($chart_colors, 0, count($chart_labels))).', }], }, });
        }
    });
</script>';
include 'includes/footer.php';
?>