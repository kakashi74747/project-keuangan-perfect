<?php
require 'includes/koneksi.php';

// Jika user sudah login, langsung redirect ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$error_message = '';

// LOGIKA PROSES LOGIN (Digabungkan di sini)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Mengambil password apa adanya

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password wajib diisi.";
    } else {
        // Ambil data user dari database
        $stmt = mysqli_prepare($koneksi, "SELECT id, username, password, first_name, last_name FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Bandingkan password teks biasa secara langsung
            if ($password === $user['password']) {
                session_regenerate_id(true);

                // Simpan semua data yang perlu ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

                header("Location: " . BASE_URL . "index.php");
                exit();
            } else {
                // Password tidak cocok
                $error_message = "Username atau password salah.";
            }
        } else {
            // User tidak ditemukan
            $error_message = "Username atau password salah.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Login - Uangmu App</title>
    <link href="<?php echo BASE_URL; ?>css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Login Uangmu App</h3></div>
                                <div class="card-body">
                                    <?php if (!empty($error_message)) { ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </div>
                                    <?php } ?>
                                    <?php if (isset($_GET['success'])) { ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo htmlspecialchars($_GET['success']); ?>
                                        </div>
                                    <?php } ?>
                                    <form action="login.php" method="POST">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                            <label for="inputUsername">Username</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" required />
                                            <label for="inputPassword">Password</label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <button type="submit" class="btn btn-primary w-100">Login</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small"><a href="register.php">Belum punya akun? Daftar!</a></div>
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