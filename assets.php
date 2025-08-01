<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    mysqli_begin_transaction($koneksi);
    try {
        if ($_POST['action'] == 'tambah') {
            $account_id_sumber = (int)$_POST['sumber_dana'];
            $asset_name = mysqli_real_escape_string($koneksi, $_POST['asset_name']);
            $asset_type = mysqli_real_escape_string($koneksi, $_POST['asset_type']);
            $quantity = mysqli_real_escape_string($koneksi, $_POST['quantity']);
            $average_buy_price = (float)str_replace('.', '', $_POST['average_buy_price'] ?? 0);
            $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
            $total_modal = floatval($quantity) * $average_buy_price;

            $stmt_cek = mysqli_prepare($koneksi, "SELECT current_balance FROM accounts WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_cek, "ii", $account_id_sumber, $user_id);
            mysqli_stmt_execute($stmt_cek);
            $akun_sumber = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
            if (!$akun_sumber || $akun_sumber['current_balance'] < $total_modal) { throw new Exception("Saldo di akun sumber dana tidak mencukupi!"); }

            $stmt_kurangi = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_kurangi, "di", $total_modal, $account_id_sumber);
            mysqli_stmt_execute($stmt_kurangi);

            $stmt_tambah_aset = mysqli_prepare($koneksi, "INSERT INTO assets (user_id, asset_name, asset_type, quantity, average_buy_price, current_price, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_tambah_aset, "isssdds", $user_id, $asset_name, $asset_type, $quantity, $average_buy_price, $total_modal, $purchase_date);
            mysqli_stmt_execute($stmt_tambah_aset);

            $q_kat = mysqli_query($koneksi, "SELECT id FROM categories WHERE user_id = $user_id AND category_name = 'Investasi' AND category_type = 'Pengeluaran'");
            $kat_id = mysqli_fetch_assoc($q_kat)['id'] ?? null;
            $desc = "Beli aset: " . $asset_name;
            $stmt_trx = mysqli_prepare($koneksi, "INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) VALUES (?, ?, ?, 'Pengeluaran', ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_trx, "iiidss", $user_id, $account_id_sumber, $kat_id, $total_modal, $desc, $purchase_date);
            mysqli_stmt_execute($stmt_trx);
            $_SESSION['pesan_sukses'] = "Pembelian aset berhasil dicatat.";
        
        } elseif ($_POST['action'] == 'jual_aset') {
            $asset_id = (int)$_POST['asset_id'];
            $quantity_to_sell_str = $_POST['quantity_to_sell'];
            $destination_account_id = (int)$_POST['destination_account'];

            $stmt_get = mysqli_prepare($koneksi, "SELECT * FROM assets WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_get, "ii", $asset_id, $user_id);
            mysqli_stmt_execute($stmt_get);
            $asset = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
            
            $current_quantity_num = floatval($asset['quantity']);
            $quantity_to_sell_num = floatval($quantity_to_sell_str);
            $price_per_unit = ($current_quantity_num > 0) ? ($asset['current_price'] / $current_quantity_num) : 0;

            if (!$asset || $quantity_to_sell_num > $current_quantity_num || $quantity_to_sell_num <= 0) { throw new Exception("Kuantitas aset yang dijual tidak valid."); }

            $total_proceeds = $quantity_to_sell_num * $price_per_unit;

            if ($quantity_to_sell_num == $current_quantity_num) {
                $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM assets WHERE id = ?");
                mysqli_stmt_bind_param($stmt_delete, "i", $asset_id);
                mysqli_stmt_execute($stmt_delete);
            } else {
                $new_quantity_num = $current_quantity_num - $quantity_to_sell_num;
                preg_match('/[a-zA-Z]+/', $asset['quantity'], $matches);
                $unit_text = isset($matches[0]) ? ' ' . $matches[0] : '';
                $new_quantity_str = $new_quantity_num . $unit_text;
                
                $new_current_price = $new_quantity_num * $price_per_unit;
                $stmt_update = mysqli_prepare($koneksi, "UPDATE assets SET quantity = ?, current_price = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_update, "sdi", $new_quantity_str, $new_current_price, $asset_id);
                mysqli_stmt_execute($stmt_update);
            }

            $stmt_add_balance = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_add_balance, "di", $total_proceeds, $destination_account_id);
            mysqli_stmt_execute($stmt_add_balance);

            $q_kat = mysqli_query($koneksi, "SELECT id FROM categories WHERE user_id = $user_id AND category_name = 'Penjualan Aset' AND category_type = 'Pemasukan'");
            $kat_id = mysqli_fetch_assoc($q_kat)['id'] ?? null;
            $desc = "Jual aset: " . $asset['asset_name'];
            
            $stmt_trx = mysqli_prepare($koneksi, "INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) VALUES (?, ?, ?, 'Pemasukan', ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt_trx, "iiids", $user_id, $destination_account_id, $kat_id, $total_proceeds, $desc);
            mysqli_stmt_execute($stmt_trx);
            $_SESSION['pesan_sukses'] = "Penjualan aset berhasil dicatat.";
            
        } elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id']; $asset_name = mysqli_real_escape_string($koneksi, $_POST['asset_name']); $asset_type = mysqli_real_escape_string($koneksi, $_POST['asset_type']); $quantity = mysqli_real_escape_string($koneksi, $_POST['quantity']); $average_buy_price = (float)str_replace('.', '', $_POST['average_buy_price'] ?? 0); $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
            $stmt = mysqli_prepare($koneksi, "UPDATE assets SET asset_name=?, asset_type=?, quantity=?, average_buy_price=?, purchase_date=? WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, "sssdsii", $asset_name, $asset_type, $quantity, $average_buy_price, $purchase_date, $id, $user_id);
            if(mysqli_stmt_execute($stmt)) { $_SESSION['pesan_sukses'] = "Aset berhasil diperbarui."; } else { throw new Exception("Gagal memperbarui aset."); }
        
        } elseif ($_POST['action'] == 'adjust_value') {
            $asset_id = (int)$_POST['asset_id']; $adjustment_amount = (float)str_replace('.', '', $_POST['adjustment_amount'] ?? 0); $adjustment_type = $_POST['adjustment_type'];
            $stmt_get = mysqli_prepare($koneksi, "SELECT current_price FROM assets WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_get, "ii", $asset_id, $user_id); mysqli_stmt_execute($stmt_get);
            $asset_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
            if ($asset_data) {
                $current_total_value = $asset_data['current_price'];
                $new_total_value = ($adjustment_type == 'add') ? $current_total_value + $adjustment_amount : $current_total_value - $adjustment_amount;
                $stmt1 = mysqli_prepare($koneksi, "UPDATE assets SET current_price = ? WHERE id = ? AND user_id = ?");
                mysqli_stmt_bind_param($stmt1, "dii", $new_total_value, $asset_id, $user_id); mysqli_stmt_execute($stmt1);
                $stmt2 = mysqli_prepare($koneksi, "INSERT INTO asset_price_history (asset_id, price) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt2, "id", $asset_id, $new_total_value); mysqli_stmt_execute($stmt2);
                $_SESSION['pesan_sukses'] = "Nilai pasar berhasil disesuaikan.";
            } else { throw new Exception("Aset tidak ditemukan."); }
        
        } elseif ($_POST['action'] == 'hapus') {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($koneksi, "DELETE FROM assets WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
            if(mysqli_stmt_execute($stmt)) { $_SESSION['pesan_sukses'] = "Aset berhasil dihapus."; } else { throw new Exception("Gagal menghapus aset."); }
        }
        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['pesan_error'] = $e->getMessage();
    }
    header("Location: assets.php");
    exit();
}

if (isset($_SESSION['pesan_sukses'])) { $pesan_sukses = $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); }
if (isset($_SESSION['pesan_error'])) { $pesan_error = $_SESSION['pesan_error']; unset($_SESSION['pesan_error']); }
$queryAssets = mysqli_prepare($koneksi, "SELECT * FROM assets WHERE user_id = ? ORDER BY asset_name ASC");
mysqli_stmt_bind_param($queryAssets, "i", $user_id);
mysqli_stmt_execute($queryAssets);
$resultAssets = mysqli_stmt_get_result($queryAssets);
$assets_data = [];
while($row = mysqli_fetch_assoc($resultAssets)){ $assets_data[] = $row; }
$queryAccounts = mysqli_query($koneksi, "SELECT * FROM accounts WHERE user_id = $user_id ORDER BY account_name ASC");
$chart_labels = []; $chart_data = []; $chart_colors = ['#0d6efd', '#dc3545', '#ffc107', '#198754', '#6c757d', '#6f42c1'];
$alokasi_data = [];
foreach($assets_data as $asset) {
    $total_nilai = floatval($asset['quantity']) * $asset['average_buy_price'];
    if (isset($alokasi_data[$asset['asset_type']])) { $alokasi_data[$asset['asset_type']] += $total_nilai; } else { $alokasi_data[$asset['asset_type']] = $total_nilai; }
}
arsort($alokasi_data);
foreach($alokasi_data as $tipe => $total) { $chart_labels[] = $tipe; $chart_data[] = $total; }
$form_mode = 'tambah';
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $form_mode = 'edit';
    $id_to_edit = (int)$_GET['id'];
    foreach($assets_data as $asset) { if($asset['id'] == $id_to_edit) { $edit_data = $asset; break; } }
}

$page_title = "Alokasi Aset - Uangmu App"; $active_page = 'assets';
include 'includes/header.php'; include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Alokasi Aset & Portofolio</h1>
        <ol class="breadcrumb mb-4"><li class="breadcrumb-item"><a href="index.php">Dashboard</a></li><li class="breadcrumb-item active">Alokasi Aset</li></ol>
        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        <div class="row">
            <div class="col-lg-5"><div class="card mb-4"><div class="card-header"><i class="fas fa-chart-pie me-1"></i>Diagram Alokasi Aset</div><div class="card-body"><canvas id="myPieChart" width="100%" height="40"></canvas></div></div></div>
            <div class="col-lg-7"><div class="card mb-4"><div class="card-header"><i class="fas fa-plus me-1"></i><?= $form_mode == 'edit' ? 'Edit Aset' : 'Beli Aset Baru'; ?></div><div class="card-body">
                <form method="POST" action="assets.php">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?><input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']); ?>"><?php endif; ?>
                    <?php if($form_mode == 'tambah'): ?><div class="mb-3"><label class="form-label">Sumber Dana</label><select class="form-select" name="sumber_dana" required><option value="">-- Pilih Akun --</option><?php mysqli_data_seek($queryAccounts, 0); while($acc = mysqli_fetch_assoc($queryAccounts)): ?><option value="<?= $acc['id']; ?>"><?= htmlspecialchars($acc['account_name']); ?> (Saldo: Rp <?= number_format($acc['current_balance'], 0, ',', '.'); ?>)</option><?php endwhile; ?></select></div><?php endif; ?>
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Nama Aset</label><input type="text" class="form-control" name="asset_name" placeholder="Cth: Emas Antam" value="<?= htmlspecialchars($edit_data['asset_name'] ?? ''); ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Tipe Aset</label><input type="text" class="form-control" name="asset_type" placeholder="Cth: Saham, Emas" value="<?= htmlspecialchars($edit_data['asset_type'] ?? ''); ?>" required></div></div>
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Kuantitas</label><input type="text" class="form-control" name="quantity" placeholder="Cth: 1.5 gram atau 10 lot" value="<?= htmlspecialchars($edit_data['quantity'] ?? ''); ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Harga Beli Rata-Rata per Unit</label><input type="hidden" name="average_buy_price" id="price_hidden" value="<?= $edit_data['average_buy_price'] ?? '0'; ?>"><input type="text" class="form-control" id="price_formatted" placeholder="0" data-target="price_hidden"></div></div>
                    <div class="mb-3"><label class="form-label">Tanggal Pembelian</label><input type="date" class="form-control" name="purchase_date" value="<?= $edit_data['purchase_date'] ?? date('Y-m-d'); ?>"></div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end"><?php if($form_mode == 'edit'): ?><a href="assets.php" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-warning">Simpan</button><?php else: ?><button type="submit" class="btn btn-primary">Beli & Catat Aset</button><?php endif; ?></div>
                </form>
            </div></div></div>
        </div>
        <div class="card mb-4"><div class="card-header"><i class="fas fa-table me-1"></i>Portofolio Aset Anda</div><div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-hover">
                <thead><tr><th>Nama Aset</th><th>Total Modal</th><th>Total Nilai Pasar</th><th>Untung/Rugi</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($assets_data as $asset) { 
                        $quantity_num = floatval($asset['quantity']); $total_modal = $asset['average_buy_price'] * $quantity_num; $total_nilai_pasar = $asset['current_price']; $profit_loss_rp = $total_nilai_pasar - $total_modal; $profit_loss_percent = ($total_modal > 0) ? ($profit_loss_rp / $total_modal) * 100 : 0;
                        $pl_class = $profit_loss_rp > 0 ? 'text-success' : ($profit_loss_rp < 0 ? 'text-danger' : 'text-muted'); $pl_icon = $profit_loss_rp > 0 ? 'fa-arrow-up' : ($profit_loss_rp < 0 ? 'fa-arrow-down' : '');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($asset['asset_name']); ?></strong><br><small class="text-muted"><?= htmlspecialchars($asset['quantity']); ?></small></td>
                        <td>Rp <?= number_format($total_modal, 0, ',', '.'); ?></td>
                        <td>Rp <?= number_format($total_nilai_pasar, 0, ',', '.'); ?></td>
                        <td class="fw-bold <?= $pl_class; ?>"><div>Rp <?= number_format($profit_loss_rp, 0, ',', '.'); ?></div><small><i class="fas <?= $pl_icon; ?>"></i> <?= number_format($profit_loss_percent, 2, ',', '.'); ?>%</small></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#adjustPriceModal<?= $asset['id']; ?>" title="Update Harga Pasar"><i class="fas fa-dollar-sign"></i></button>
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#sellAssetModal<?= $asset['id']; ?>" title="Jual Aset"><i class="fas fa-sign-out-alt"></i></button>
                                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#historyModal<?= $asset['id']; ?>" title="Lihat Riwayat"><i class="fas fa-history"></i></button>
                                <a href="assets.php?action=edit&id=<?= $asset['id']; ?>" class="btn btn-warning btn-sm" title="Edit Aset"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $asset['id']; ?>" title="Hapus Aset"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div></div>
</main>

<?php foreach ($assets_data as $asset) { 
    $history_query = mysqli_prepare($koneksi, "SELECT price, update_date FROM asset_price_history WHERE asset_id = ? ORDER BY update_date DESC");
    mysqli_stmt_bind_param($history_query, "i", $asset['id']); mysqli_stmt_execute($history_query); $history_result = mysqli_stmt_get_result($history_query);
?>

<div class="modal fade" id="adjustPriceModal<?= $asset['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Update Harga Pasar: <?= htmlspecialchars($asset['asset_name']); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="assets.php">
                <input type="hidden" name="action" value="adjust_value">
                <input type="hidden" name="asset_id" value="<?= $asset['id']; ?>">
                <div class="modal-body">
                    <p class="small text-muted">Nilai pasar saat ini: <strong>Rp <?= number_format($asset['current_price'], 0, ',', '.'); ?></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Jenis Penyesuaian</label>
                        <select class="form-select" name="adjustment_type" required>
                            <option value="add">Kenaikan</option>
                            <option value="subtract">Penurunan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Penyesuaian (Rp)</label>
                        <input type="text" class="form-control price-format" name="adjustment_amount" placeholder="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="sellAssetModal<?= $asset['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-success text-white"><h5 class="modal-title">Jual Aset: <?= htmlspecialchars($asset['asset_name']); ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST" action="assets.php"><input type="hidden" name="action" value="jual_aset"><input type="hidden" name="asset_id" value="<?= $asset['id']; ?>"><div class="modal-body"><div class="mb-3"><p class="small text-muted">Harga jual akan dihitung berdasarkan nilai pasar saat ini.</p><label class="form-label">Kuantitas Dijual</label><input type="text" class="form-control" name="quantity_to_sell" placeholder="Contoh: 0.5 gram" required></div><div class="mb-3"><label class="form-label">Dana Masuk ke Akun</label><select class="form-select" name="destination_account" required><option value="">-- Pilih Akun --</option><?php mysqli_data_seek($queryAccounts, 0); while($acc = mysqli_fetch_assoc($queryAccounts)) { echo "<option value='{$acc['id']}'>".htmlspecialchars($acc['account_name'])."</option>"; } ?></select></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Konfirmasi Penjualan</button></div></form></div></div></div>
<div class="modal fade" id="historyModal<?= $asset['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Riwayat Nilai Pasar: <?= htmlspecialchars($asset['asset_name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><table class="table table-sm"><thead><tr><th>Tanggal Update</th><th>Total Nilai</th></tr></thead><tbody><?php while($history = mysqli_fetch_assoc($history_result)): ?><tr><td><?= date('d M Y, H:i', strtotime($history['update_date'])); ?></td><td>Rp <?= number_format($history['price'], 0, ',', '.'); ?></td></tr><?php endwhile; ?></tbody></table></div></div></div></div>
<div class="modal fade" id="hapusModal<?= $asset['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="assets.php"><input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $asset['id']; ?>"><div class="modal-body"><p>Yakin ingin menghapus aset <strong><?= htmlspecialchars($asset['asset_name']); ?></strong>?</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div></form></div></div></div>
<?php } ?>

<?php
$additional_scripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        const datatablesSimple = document.getElementById(\'datatablesSimple\');
        if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }
        function formatRupiah(angkaStr) { let number_string = angkaStr.replace(/[^0-9]/g, \'\'); return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
        function unformatRupiah(str) { return str.replace(/\./g, \'\'); }
        function setupFormattedInput(visibleInputId, hiddenInputId) {
            const visibleInput = document.getElementById(visibleInputId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!visibleInput || !hiddenInput) return;
            let initialValue = hiddenInput.value;
            visibleInput.value = formatRupiah(initialValue.toString());
            visibleInput.addEventListener(\'keyup\', function(e) {
                hiddenInput.value = unformatRupiah(this.value);
                this.value = formatRupiah(this.value);
            });
        }
        setupFormattedInput(\'price_formatted\', \'price_hidden\');
        document.querySelectorAll(\'.price-format\').forEach(input => {
            input.addEventListener(\'keyup\', function(e){ this.value = formatRupiah(unformatRupiah(this.value)); });
        });
        var ctx = document.getElementById("myPieChart");
        var myPieChart = new Chart(ctx, { type: \'pie\', data: { labels: '.json_encode($chart_labels).', datasets: [{ data: '.json_encode($chart_data).', backgroundColor: '.json_encode(array_slice($chart_colors, 0, count($chart_labels))).', }], }, });
    });
</script>';
include 'includes/footer.php';
echo $additional_scripts;
?>