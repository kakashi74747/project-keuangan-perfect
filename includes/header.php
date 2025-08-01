<?php
if (!isset($active_page)) {
    $active_page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Uangmu App'; ?></title>
    <link href="<?php echo BASE_URL; ?>css/styles.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>css/custom.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body>
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-money-check-alt me-2"></i>Uangmu App</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'transactions') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>transactions.php">Transaksi</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'assets') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>assets.php">Aset</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'goals') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>goals.php">Target</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'emergency_fund') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>emergency_fund.php">Dana Darurat</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'reports') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports.php">Laporan</a></li>
                <li class="nav-item nav-item-separator"><a class="nav-link <?php echo ($active_page == 'accounts') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>accounts.php">Kelola Akun</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($active_page == 'categories') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>categories.php">Kelola Kategori</a></li>
            </ul>
        </div>
        <ul class="navbar-nav ms-auto me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i> 
                    <?php if (isset($_SESSION['full_name'])) { echo htmlspecialchars($_SESSION['full_name']); } ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown"><li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li></ul>
            </li>
        </ul>
    </nav>