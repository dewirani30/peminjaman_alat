<?php
include 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else if ($_SESSION['role'] == 'petugas') header("Location: petugas/dashboard.php");
    else header("Location: peminjam/dashboard.php");
    exit();
}

$error = '';

// Proses Login
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Cocokkan password menggunakan SHA2 256-bit
    $query = "SELECT * FROM users WHERE username = '$username' AND password = SHA2('$password', 256)";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['id_user']      = $row['id_user'];
        $_SESSION['username']     = $row['username'];
        $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
        $_SESSION['role']         = $row['role'];

        // Catat log login
        if (function_exists('catat_log')) {
            catat_log($koneksi, $row['id_user'], "Berhasil login ke sistem");
        }

        if ($row['role'] == 'admin') header("Location: admin/dashboard.php");
        else if ($row['role'] == 'petugas') header("Location: petugas/dashboard.php");
        else header("Location: peminjam/dashboard.php");
        exit();
    } else {
        $error = "Username atau password yang Anda masukkan salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peminjaman Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #E4D6A9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-login {
            background-color: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            width: 100%;
            max-width: 400px;
        }
        .btn-custom {
            background-color: #622B14;
            color: #ffffff;
            border: none;
        }
        .btn-custom:hover {
            background-color: #995F2F;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="card card-login p-4">
    <div class="text-center mb-4">
        <h4 class="fw-bold" style="color: #622B14;">Sistem Peminjaman Alat</h4>
        <p class="text-muted small mb-0">Silakan login untuk mengakses akun Anda</p>
    </div>

    <!-- Alert Logout Berhasil -->
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'logout'): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <small>Anda telah berhasil logout.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Alert Registrasi Berhasil -->
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'reg_sukses'): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <small>Pendaftaran akun berhasil! Silakan login.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Alert Error Login -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <small><?= $error; ?></small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color: #622B14;">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color: #622B14;">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <button type="submit" name="login" class="btn btn-custom w-100 py-2 mt-2 fw-bold">Login</button>
    </form>

    <!-- Link ke Halaman Registrasi -->
    <div class="text-center mt-3">
        <small class="text-muted">Belum punya akun? <a href="register.php" class="fw-bold" style="color: #622B14; text-decoration: none;">Daftar di sini</a></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>