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
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM transactions WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_to_edit, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($result);
    if (!$edit_data) {
        $pesan_error = "Data transaksi tidak ditemukan.";
        $form_mode = 'tambah';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    mysqli_begin_transaction($koneksi);
    try {
        if ($_POST['action'] == 'tambah') {
            $account_id = (int)$_POST['account_id'];
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $transaction_type = $_POST['transaction_type'];
            $amount = (float)($_POST['amount'] ?? 0);
            $description = mysqli_real_escape_string($koneksi, $_POST['description']);
            $transaction_date = $_POST['transaction_date'];

            $stmt1 = mysqli_prepare($koneksi, "INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt1, "iiisdss", $user_id, $account_id, $category_id, $transaction_type, $amount, $description, $transaction_date);
            mysqli_stmt_execute($stmt1);

            if ($transaction_type == 'Koreksi Saldo') {
                $stmt2 = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = ? WHERE id = ? AND user_id = ?");
                mysqli_stmt_bind_param($stmt2, "dii", $amount, $account_id, $user_id);
            } else {
                $update_amount = ($transaction_type == 'Pemasukan') ? $amount : -$amount;
                $stmt2 = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
                mysqli_stmt_bind_param($stmt2, "dii", $update_amount, $account_id, $user_id);
            }
            mysqli_stmt_execute($stmt2);
            $pesan_sukses = "Transaksi berhasil ditambahkan.";

        } elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id'];
            
            $stmt_old = mysqli_prepare($koneksi, "SELECT account_id, transaction_type, amount FROM transactions WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt_old, "ii", $id, $user_id);
            mysqli_stmt_execute($stmt_old);
            $old_trx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old));
            
            if ($old_trx) {
                if ($old_trx['transaction_type'] != 'Koreksi Saldo') {
                    $revert_amount = ($old_trx['transaction_type'] == 'Pemasukan') ? -$old_trx['amount'] : $old_trx['amount'];
                    $stmt_revert = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_revert, "di", $revert_amount, $old_trx['account_id']);
                    mysqli_stmt_execute($stmt_revert);
                }
            } else { throw new Exception("Transaksi lama tidak ditemukan."); }

            $account_id_new = (int)$_POST['account_id'];
            $category_id_new = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $transaction_type_new = $_POST['transaction_type'];
            $amount_new = (float)($_POST['amount'] ?? 0);
            $description_new = mysqli_real_escape_string($koneksi, $_POST['description']);
            $transaction_date_new = $_POST['transaction_date'];

            $stmt_update_trx = mysqli_prepare($koneksi, "UPDATE transactions SET account_id=?, category_id=?, transaction_type=?, amount=?, description=?, transaction_date=? WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt_update_trx, "iisdssii", $account_id_new, $category_id_new, $transaction_type_new, $amount_new, $description_new, $transaction_date_new, $id, $user_id);
            mysqli_stmt_execute($stmt_update_trx);
            
            if ($transaction_type_new == 'Koreksi Saldo') {
                $stmt_apply_new = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_apply_new, "di", $amount_new, $account_id_new);
            } else {
                $apply_amount = ($transaction_type_new == 'Pemasukan') ? $amount_new : -$amount_new;
                $stmt_apply_new = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_apply_new, "di", $apply_amount, $account_id_new);
            }
            mysqli_stmt_execute($stmt_apply_new);
            $pesan_sukses = "Transaksi berhasil diperbarui. Mengalihkan...";
            echo "<meta http-equiv='refresh' content='2;url=transactions.php'>";

        } elseif ($_POST['action'] == 'hapus') {
            $id = (int)$_POST['id'];

            $stmt_old = mysqli_prepare($koneksi, "SELECT account_id, transaction_type, amount FROM transactions WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt_old, "ii", $id, $user_id);
            mysqli_stmt_execute($stmt_old);
            $old_trx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old));

            if ($old_trx) {
                 if ($old_trx['transaction_type'] != 'Koreksi Saldo') {
                    $revert_amount = ($old_trx['transaction_type'] == 'Pemasukan') ? -$old_trx['amount'] : $old_trx['amount'];
                    $stmt_revert = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_revert, "di", $revert_amount, $old_trx['account_id']);
                    mysqli_stmt_execute($stmt_revert);
                }
            } else { throw new Exception("Transaksi yang akan dihapus tidak ditemukan."); }

            $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM transactions WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt_delete, "ii", $id, $user_id);
            mysqli_stmt_execute($stmt_delete);
            $pesan_sukses = "Transaksi berhasil dihapus.";
        }
        mysqli_commit($koneksi);

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $pesan_error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

$queryTransactions = mysqli_prepare($koneksi, "SELECT t.*, a.account_name, c.category_name FROM transactions t JOIN accounts a ON t.account_id = a.id LEFT JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? ORDER BY t.transaction_date DESC, t.id DESC");
mysqli_stmt_bind_param($queryTransactions, "i", $user_id);
mysqli_stmt_execute($queryTransactions);
$resultTransactions = mysqli_stmt_get_result($queryTransactions);

$queryAccounts = mysqli_query($koneksi, "SELECT id, account_name FROM accounts WHERE user_id = $user_id");
$queryCategories = mysqli_query($koneksi, "SELECT id, category_name, category_type FROM categories WHERE user_id = $user_id");

$categories = [];
while ($row = mysqli_fetch_assoc($queryCategories)) {
    $categories[$row['category_type']][] = $row;
}

$page_title = "Kelola Transaksi - Uangmu App";
$active_page = 'transactions';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Kelola Transaksi</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Transaksi</li>
        </ol>

        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-plus me-1"></i><?= $form_mode == 'edit' ? 'Edit Transaksi' : 'Tambah Transaksi Baru'; ?></div>
            <div class="card-body">
                <form method="POST" action="transactions.php">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?><input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']); ?>"><?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipe Transaksi</label>
                            <div>
                                <?php 
                                $tipe_transaksi = ['Pengeluaran', 'Pemasukan', 'Koreksi Saldo'];
                                $selected_tipe = $edit_data['transaction_type'] ?? 'Pengeluaran';
                                foreach($tipe_transaksi as $tipe):
                                    $checked = ($selected_tipe == $tipe) ? 'checked' : '';
                                    
                                    // *** INI BAGIAN YANG DIUBAH ***
                                    $btn_class = 'btn-outline-secondary'; // Warna default
                                    if ($tipe == 'Pemasukan') $btn_class = 'btn-outline-success';
                                    if ($tipe == 'Pengeluaran') $btn_class = 'btn-outline-danger';
                                    if ($tipe == 'Koreksi Saldo') $btn_class = 'btn-outline-warning';
                                ?>
                                    <input type="radio" class="btn-check" name="transaction_type" id="tipe_<?= str_replace(' ', '_', $tipe); ?>" value="<?= $tipe; ?>" autocomplete="off" <?= $checked; ?>>
                                    <label class="btn <?= $btn_class; ?>" for="tipe_<?= str_replace(' ', '_', $tipe); ?>"><?= $tipe; ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="transaction_date" value="<?= $edit_data['transaction_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" id="amountLabel">Jumlah</label>
                            <input type="hidden" name="amount" id="amount_hidden" value="<?= $edit_data['amount'] ?? '0'; ?>">
                            <input type="text" class="form-control" id="amount_formatted" placeholder="0" data-target="amount_hidden">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Akun</label>
                            <select class="form-select" name="account_id" required>
                                <?php mysqli_data_seek($queryAccounts, 0); while($acc = mysqli_fetch_assoc($queryAccounts)): ?>
                                    <option value="<?= $acc['id']; ?>" <?= (($edit_data['account_id'] ?? '') == $acc['id']) ? 'selected' : ''; ?>><?= htmlspecialchars($acc['account_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="categoryField">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" data-selected-id="<?= $edit_data['category_id'] ?? '' ?>"></select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if($form_mode == 'edit'): ?>
                            <a href="transactions.php" class="btn btn-secondary">Batal Edit</a>
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>Riwayat Transaksi</div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped table-hover">
                    <thead><tr><th>Tanggal</th><th>Akun</th><th>Tipe</th><th>Kategori</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php mysqli_data_seek($resultTransactions, 0); while ($trx = mysqli_fetch_assoc($resultTransactions)) { ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($trx['transaction_date'])); ?></td>
                            <td><?= htmlspecialchars($trx['account_name']); ?></td>
                            <td>
                                <?php 
                                if ($trx['transaction_type'] == 'Pemasukan') echo '<span class="badge bg-success">Pemasukan</span>';
                                elseif ($trx['transaction_type'] == 'Pengeluaran') echo '<span class="badge bg-danger">Pengeluaran</span>';
                                else echo '<span class="badge bg-info">Koreksi Saldo</span>';
                                ?>
                            </td>
                            <td><?= htmlspecialchars($trx['category_name'] ?? 'N/A'); ?></td>
                            <td class="text-end fw-bold <?= $trx['transaction_type'] == 'Pengeluaran' ? 'text-danger' : 'text-success'; ?>">Rp <?= number_format($trx['amount'], 2, ',', '.'); ?></td>
                            <td>
                                <a href="transactions.php?action=edit&id=<?= $trx['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $trx['id']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php mysqli_data_seek($resultTransactions, 0); while ($trx = mysqli_fetch_assoc($resultTransactions)) { ?>
<div class="modal fade" id="hapusModal<?= $trx['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="transactions.php">
                <input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $trx['id']; ?>">
                <div class="modal-body"><p>Yakin ingin menghapus transaksi ini?</p><p class="text-danger small">Tindakan ini akan mengembalikan saldo akun terkait.</p></div>
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

        const categories = '.json_encode($categories).';
        const transactionTypeRadios = document.querySelectorAll(".btn-check[name=\'transaction_type\']");
        const categorySelect = document.querySelector("select[name=\'category_id\']");
        const categoryField = document.getElementById("categoryField");
        const amountLabel = document.getElementById("amountLabel");

        function updateCategoryOptions() {
            let selectedType = document.querySelector(".btn-check[name=\'transaction_type\']:checked").value;
            const selectedCategoryId = categorySelect.getAttribute("data-selected-id");
            categorySelect.innerHTML = \'<option value="">-- Tanpa Kategori --</option>\'; 

            if (selectedType === "Pemasukan" || selectedType === "Pengeluaran") {
                categoryField.style.display = "block";
                amountLabel.textContent = "Jumlah " + selectedType;
                categorySelect.required = true;
                if (categories[selectedType]) {
                    categories[selectedType].forEach(cat => {
                        const option = document.createElement("option");
                        option.value = cat.id;
                        option.textContent = cat.category_name;
                        if (cat.id == selectedCategoryId) {
                            option.selected = true;
                        }
                        categorySelect.appendChild(option);
                    });
                }
            } else { // Koreksi Saldo
                categoryField.style.display = "none";
                amountLabel.textContent = "Jumlah Saldo Baru";
                categorySelect.required = false;
            }
        }

        transactionTypeRadios.forEach(radio => {
            radio.addEventListener("change", updateCategoryOptions);
        });
        
        updateCategoryOptions();

        function formatRupiah(angka) {
            let number_string = angka.replace(/[^,\d]/g, \'\').toString();
            return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        const formattedInput = document.getElementById(\'amount_formatted\');
        const hiddenInput = document.getElementById(\'amount_hidden\');

        let initialValue = hiddenInput.value;
        formattedInput.value = formatRupiah(initialValue.toString());
        
        formattedInput.addEventListener(\'keyup\', function(e){
            let unformattedValue = this.value.replace(/\./g, \'\');
            hiddenInput.value = unformattedValue;
            this.value = formatRupiah(this.value);
        });
    });
</script>';

include 'includes/footer.php';
echo $additional_scripts;
?>