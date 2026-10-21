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
$sukses = '';

if (isset($_POST['register'])) {
    $username          = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap      = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $password          = $_POST['password'];
    $konfirmasi_pass   = $_POST['konfirmasi_password'];

    // Validasi konfirmasi password
    if ($password !== $konfirmasi_pass) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek ketersediaan username
        $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek_user) > 0) {
            $error = "Username sudah terdaftar, silakan gunakan username lain!";
        } else {
            // Role otomatis diset sebagai 'peminjam'
            $query_reg = "INSERT INTO users (username, password, nama_lengkap, role) 
                          VALUES ('$username', SHA2('$password', 256), '$nama_lengkap', 'peminjam')";
            
            if (mysqli_query($koneksi, $query_reg)) {
                $id_baru = mysqli_insert_id($koneksi);
                if (function_exists('catat_log')) {
                    catat_log($koneksi, $id_baru, "Mendaftar akun peminjam baru: $username");
                }
                header("Location: index.php?pesan=reg_sukses");
                exit();
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sistem Peminjaman Alat</title>
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
        .card-register {
            background-color: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            width: 100%;
            max-width: 420px;
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

<div class="card card-register p-4 my-4">
    <div class="text-center mb-3">
        <h4 class="fw-bold" style="color: #622B14;">Daftar Akun Peminjam</h4>
        <p class="text-muted small mb-0">Lengkapi data di bawah untuk membuat akun baru</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <small><?= $error; ?></small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color: #622B14;">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color: #622B14;">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color: #622B14;">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color: #622B14;">Konfirmasi Password</label>
            <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" required>
        </div>
        <button type="submit" name="register" class="btn btn-custom w-100 py-2 mt-2 fw-bold">Daftar Sekarang</button>
    </form>

    <div class="text-center mt-3">
        <small class="text-muted">Sudah punya akun? <a href="index.php" class="fw-bold" style="color: #622B14; text-decoration: none;">Login di sini</a></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>