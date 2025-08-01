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
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM categories WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_to_edit, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($result);
    if (!$edit_data) {
        $pesan_error = "Data kategori tidak ditemukan.";
        $form_mode = 'tambah';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'tambah') {
        $category_name = mysqli_real_escape_string($koneksi, $_POST['category_name']);
        $category_type = mysqli_real_escape_string($koneksi, $_POST['category_type']);

        $stmt = mysqli_prepare($koneksi, "INSERT INTO categories (user_id, category_name, category_type) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $category_name, $category_type);
        if(mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil ditambahkan.";
        } else {
            $pesan_error = "Gagal menambahkan kategori.";
        }
    }
    
    elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        $category_name = mysqli_real_escape_string($koneksi, $_POST['category_name']);
        $category_type = mysqli_real_escape_string($koneksi, $_POST['category_type']);

        $stmt = mysqli_prepare($koneksi, "UPDATE categories SET category_name=?, category_type=? WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ssii", $category_name, $category_type, $id, $user_id);
        if(mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil diperbarui. Mengalihkan...";
            echo "<meta http-equiv='refresh' content='2;url=categories.php'>";
        } else {
            $pesan_error = "Gagal memperbarui kategori.";
            $edit_data = $_POST;
        }
    }
    
    elseif ($_POST['action'] == 'hapus') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($koneksi, "DELETE FROM categories WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        if(mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil dihapus.";
        } else {
            $pesan_error = "Gagal menghapus kategori.";
        }
    }
}

$queryCategories = mysqli_prepare($koneksi, "SELECT * FROM categories WHERE user_id = ? ORDER BY category_type, category_name ASC");
mysqli_stmt_bind_param($queryCategories, "i", $user_id);
mysqli_stmt_execute($queryCategories);
$resultCategories = mysqli_stmt_get_result($queryCategories);

$page_title = "Kelola Kategori - Uangmu App";
$active_page = 'categories';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Kelola Kategori</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kategori</li>
        </ol>

        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus me-1"></i>
                <?= $form_mode == 'edit' ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="categories.php">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']); ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" name="category_name" placeholder="Contoh: Gaji, Makanan, Transportasi" value="<?= htmlspecialchars($edit_data['category_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe Kategori</label>
                        <div>
                            <?php 
                            $tipe_kategori = ['Pengeluaran', 'Pemasukan'];
                            $selected_tipe = $edit_data['category_type'] ?? 'Pengeluaran';
                            foreach($tipe_kategori as $tipe):
                                $checked = ($selected_tipe == $tipe) ? 'checked' : '';
                                $btn_class = ($tipe == 'Pemasukan') ? 'btn-outline-success' : 'btn-outline-danger';
                            ?>
                                <input type="radio" class="btn-check" name="category_type" id="tipe_<?= $tipe; ?>" value="<?= $tipe; ?>" autocomplete="off" <?= $checked; ?>>
                                <label class="btn <?= $btn_class; ?> mb-1" for="tipe_<?= $tipe; ?>"><?= $tipe; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if($form_mode == 'edit'): ?>
                            <a href="categories.php" class="btn btn-secondary">Batal Edit</a>
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>Daftar Kategori Anda</div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped table-hover">
                    <thead>
                        <tr><th>Nama Kategori</th><th>Tipe</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($cat = mysqli_fetch_assoc($resultCategories)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['category_name']); ?></td>
                            <td>
                                <?php if ($cat['category_type'] == 'Pemasukan'): ?>
                                    <span class="badge bg-success">Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="categories.php?action=edit&id=<?= $cat['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $cat['id']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php mysqli_data_seek($resultCategories, 0); while ($cat = mysqli_fetch_assoc($resultCategories)) { ?>
<div class="modal fade" id="hapusModal<?= $cat['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $cat['id']; ?>">
                <div class="modal-body">
                    <p>Yakin ingin menghapus kategori <strong><?= htmlspecialchars($cat['category_name']); ?></strong>?</p>
                    <p class="text-info small">Transaksi yang ada dengan kategori ini tidak akan terhapus.</p>
                </div>
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
    });
</script>';

include 'includes/footer.php';
echo $additional_scripts;
?>