<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

$form_mode = 'tambah';
$edit_data = null;

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $form_mode = 'edit';
    $id_to_edit = (int)$_GET['id'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM accounts WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_to_edit, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($result);
    if (!$edit_data) {
        $pesan_error = "Data akun tidak ditemukan.";
        $form_mode = 'tambah';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'tambah') {
        $account_name = mysqli_real_escape_string($koneksi, $_POST['account_name']);
        $account_type = mysqli_real_escape_string($koneksi, $_POST['account_type']);
        $initial_balance = (float)($_POST['initial_balance'] ?? 0);

        $stmt = mysqli_prepare($koneksi, "INSERT INTO accounts (user_id, account_name, account_type, initial_balance, current_balance) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issdd", $user_id, $account_name, $account_type, $initial_balance, $initial_balance);
        if(mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Akun berhasil ditambahkan.";
        } else {
            $pesan_error = "Gagal menambahkan akun.";
        }
    }
    
    elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        $account_name = mysqli_real_escape_string($koneksi, $_POST['account_name']);
        $account_type = mysqli_real_escape_string($koneksi, $_POST['account_type']);
        $current_balance = (float)($_POST['current_balance'] ?? 0);

        $stmt = mysqli_prepare($koneksi, "UPDATE accounts SET account_name=?, account_type=?, current_balance=? WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ssdii", $account_name, $account_type, $current_balance, $id, $user_id);
        if(mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Akun berhasil diperbarui. Mengalihkan...";
            echo "<meta http-equiv='refresh' content='2;url=accounts.php'>";
        } else {
            $pesan_error = "Gagal memperbarui akun.";
            $edit_data = $_POST;
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'hapus') {
    $id = (int)$_POST['id'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM accounts WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    if(mysqli_stmt_execute($stmt)) {
        $pesan_sukses = "Akun berhasil dihapus.";
    } else {
        $pesan_error = "Gagal menghapus akun.";
    }
}

$queryAccounts = mysqli_prepare($koneksi, "SELECT * FROM accounts WHERE user_id = ? ORDER BY account_name ASC");
mysqli_stmt_bind_param($queryAccounts, "i", $user_id);
mysqli_stmt_execute($queryAccounts);
$resultAccounts = mysqli_stmt_get_result($queryAccounts);

$page_title = "Kelola Akun - Uangmu App";
$active_page = 'accounts';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Kelola Akun</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Akun</li>
        </ol>

        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus me-1"></i>
                <?= $form_mode == 'edit' ? 'Edit Akun' : 'Tambah Akun Baru'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="accounts.php">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']); ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Akun</label>
                        <input type="text" class="form-control" name="account_name" placeholder="Contoh: Dompet Utama, Rekening BCA" value="<?= htmlspecialchars($edit_data['account_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe Akun</label>
                        <div>
                            <?php 
                            $tipe_akun = ['Tabungan', 'E-wallet', 'Kas', 'Investasi', 'Lainnya'];
                            $selected_tipe = $edit_data['account_type'] ?? 'Tabungan';
                            foreach($tipe_akun as $tipe):
                                $checked = ($selected_tipe == $tipe) ? 'checked' : '';
                            ?>
                                <input type="radio" class="btn-check" name="account_type" id="tipe_<?= $tipe; ?>" value="<?= $tipe; ?>" autocomplete="off" <?= $checked; ?>>
                                <label class="btn btn-outline-secondary mb-1" for="tipe_<?= $tipe; ?>"><?= $tipe; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <?php if($form_mode == 'edit'): ?>
                            <label class="form-label">Saldo Saat Ini</label>
                            <input type="hidden" name="current_balance" id="current_balance_hidden" value="<?= $edit_data['current_balance'] ?? '0'; ?>">
                            <input type="text" class="form-control" id="current_balance_formatted" placeholder="0" data-target="current_balance_hidden">
                        <?php else: ?>
                            <label class="form-label">Saldo Awal</label>
                            <input type="hidden" name="initial_balance" id="initial_balance_hidden" value="0">
                            <input type="text" class="form-control" id="initial_balance_formatted" placeholder="0" data-target="initial_balance_hidden">
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if($form_mode == 'edit'): ?>
                            <a href="accounts.php" class="btn btn-secondary">Batal Edit</a>
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Simpan Akun</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>Daftar Akun Anda</div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped table-hover">
                    <thead>
                        <tr><th>Nama Akun</th><th>Tipe</th><th>Saldo</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($acc = mysqli_fetch_assoc($resultAccounts)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($acc['account_name']); ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($acc['account_type']); ?></span></td>
                            <td>Rp <?= number_format($acc['current_balance'], 2, ',', '.'); ?></td>
                            <td>
                                <a href="accounts.php?action=edit&id=<?= $acc['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $acc['id']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php mysqli_data_seek($resultAccounts, 0); while ($acc = mysqli_fetch_assoc($resultAccounts)) { ?>
<div class="modal fade" id="hapusModal<?= $acc['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="accounts.php">
                <input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $acc['id']; ?>">
                <div class="modal-body"><p>Yakin ingin menghapus akun <strong><?= htmlspecialchars($acc['account_name']); ?></strong>?</p><p class="text-danger">Semua transaksi terkait akan ikut terhapus.</p></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php
$additional_scripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        const datatablesSimple = document.getElementById(\'datatablesSimple\');
        if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }

        // --- SCRIPT UNTUK FORMAT ANGKA OTOMATIS ---
        function formatRupiah(angka, prefix){
            let number_string = angka.replace(/[^,\d]/g, \'\').toString(),
            split   		= number_string.split(\',\'),
            sisa     		= split[0].length % 3,
            rupiah     		= split[0].substr(0, sisa),
            ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

            if(ribuan){
                separator = sisa ? \'.\' : \'\';
                rupiah += separator + ribuan.join(\'.\');
            }

            rupiah = split[1] != undefined ? rupiah + \',\' + split[1] : rupiah;
            return prefix == undefined ? rupiah : (rupiah ? \'Rp. \' + rupiah : \'\');
        }

        function setupFormattedInput(visibleInputId) {
            const formattedInput = document.getElementById(visibleInputId);
            if (!formattedInput) return;

            const targetHiddenInputId = formattedInput.getAttribute(\'data-target\');
            const hiddenInput = document.getElementById(targetHiddenInputId);
            
            // Saat halaman load (untuk mode edit), format angka yang sudah ada
            let initialValue = hiddenInput.value;
            formattedInput.value = formatRupiah(initialValue.toString());

            // Saat pengguna mengetik
            formattedInput.addEventListener(\'keyup\', function(e){
                let unformattedValue = this.value.replace(/\./g, \'\');
                hiddenInput.value = unformattedValue;
                this.value = formatRupiah(this.value);
            });
        }

        // Terapkan ke semua input yang relevan
        setupFormattedInput(\'initial_balance_formatted\');
        setupFormattedInput(\'current_balance_formatted\');
    });
</script>';

include 'includes/footer.php';
echo $additional_scripts;
?>