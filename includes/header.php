<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Sisa kode HTML header & navbar kamu di bawahnya... -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Peminjaman Alat</title>
    <!-- Bootstrap CSS -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Warna Earth Tone -->
    <style>
        :root {
            --color-dark: #622B14;
            --color-medium: #995F2F;
            --color-taupe: #978F66;
            --color-cream: #E4D6A9;
        }

        body {
            background-color: var(--color-cream) !important;
            color: #2D2727;
        }

        .navbar-custom {
            background-color: var(--color-dark) !important;
        }

        .btn-custom {
            background-color: var(--color-medium) !important;
            color: #ffffff !important;
            border: none;
        }

        .btn-custom:hover {
            background-color: var(--color-dark) !important;
        }

        .card-custom {
            border: 1px solid var(--color-taupe) !important;
            border-radius: 10px;
            background-color: #ffffff;
        }
    </style>
</head>
<body>

<!-- Navbar dengan warna cokelat tua (#622B14) -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 shadow">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">Sistem Peminjaman</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Dashboard</a>
        </li>
        
        <?php if($_SESSION['role'] == 'admin'){ ?>
            <li class="nav-item"><a class="nav-link" href="data_user.php">Data User</a></li>
            <li class="nav-item"><a class="nav-link" href="data_alat.php">Data Alat & Kategori</a></li>
            <li class="nav-item"><a class="nav-link" href="data_peminjaman.php">Transaksi</a></li>
            <li class="nav-item"><a class="nav-link" href="log_aktivitas.php">Log Aktivitas</a></li>
        <?php } else if($_SESSION['role'] == 'petugas'){ ?>
            <li class="nav-item"><a class="nav-link" href="pengembalian.php">Pengembalian</a></li>
            <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
        <?php } else if($_SESSION['role'] == 'peminjam'){ ?>
            <li class="nav-item"><a class="nav-link" href="riwayat.php">Peminjaman Saya</a></li>
        <?php } ?>
      </ul>
      
      <span class="navbar-text me-3 text-white">
        Halo, <b><?= $_SESSION['nama_lengkap']; ?></b> 
      </span>
      <a href="../logout.php" class="btn btn-outline-light btn-sm" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
    </div>
  </div>
</nav>

<div class="container">