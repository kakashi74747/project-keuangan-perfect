<?php
require 'includes/koneksi.php';

// Jika user sudah login, redirect ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';

// LOGIKA PROSES FORM REGISTRASI (Digabungkan di sini)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Mengambil password apa adanya

    // Validasi dasar
    if (empty($first_name) || empty($username) || empty($password)) {
        $error_message = "Nama Awal, Username, dan Password wajib diisi.";
    } else {
        // Cek duplikasi username
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error_message = "Username sudah terdaftar. Silakan gunakan username lain.";
        } else {
            // Simpan pengguna baru dengan password teks biasa
            $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO users (username, first_name, last_name, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "ssss", $username, $first_name, $last_name, $password);

            if (mysqli_stmt_execute($stmt_insert)) {
                // Jika berhasil, redirect ke login dengan pesan sukses
                header("Location: login.php?success=Registrasi berhasil! Silakan login.");
                exit();
            } else {
                $error_message = "Terjadi kesalahan pada database. Silakan coba lagi.";
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Register - Uangmu App</title>
    <link href="<?php echo BASE_URL; ?>css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Buat Akun Baru</h3></div>
                                <div class="card-body">
                                    <?php if (!empty($error_message)) { ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </div>
                                    <?php } ?>
                                    <form action="register.php" method="POST">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputFirstName" name="first_name" type="text" placeholder="Nama Awal" required />
                                                    <label for="inputFirstName">Nama Awal</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputLastName" name="last_name" type="text" placeholder="Nama Akhir" />
                                                    <label for="inputLastName">Nama Akhir (Opsional)</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                            <label for="inputUsername">Username</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" required />
                                            <label for="inputPassword">Password</label>
                                        </div>
                                        <div class="mt-4 mb-0">
                                            <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Buat Akun</button></div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small"><a href="login.php">Sudah punya akun? Login</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?php echo BASE_URL; ?>js/scripts.js"></script>
</body>
</html>