<?php
include '../koneksi.php';
include '../includes/header.php';

// Cek hak akses petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../index.php");
    exit();
}

$id_petugas  = $_SESSION['id_user'];
$pesan_sukses = '';
$pesan_gagal  = '';

// 1. Proses SETUJUI / SERAHKAN BARANG
if (isset($_POST['setujui'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];

    // Cek stok alat
    $q_detail = mysqli_query($koneksi, "
        SELECT dp.*, a.nama_alat, a.stok 
        FROM detail_peminjaman dp 
        JOIN alat a ON dp.id_alat = a.id_alat 
        WHERE dp.id_peminjaman = '$id_peminjaman'
    ");

    $stok_cukup = true;
    $nama_alat_kurang = '';

    while ($item = mysqli_fetch_assoc($q_detail)) {
        if ($item['jumlah_pinjam'] > $item['stok']) {
            $stok_cukup = false;
            $nama_alat_kurang = $item['nama_alat'];
            break;
        }
    }

    if (!$stok_cukup) {
        $pesan_gagal = "Gagal menyetujui! Stok alat <b>" . htmlspecialchars($nama_alat_kurang) . "</b> tidak mencukupi di database.";
    } else {
        // A. Potong Stok Alat
        mysqli_data_seek($q_detail, 0);
        while ($item = mysqli_fetch_assoc($q_detail)) {
            $id_a = $item['id_alat'];
            $jml  = $item['jumlah_pinjam'];
            mysqli_query($koneksi, "UPDATE alat SET stok = stok - $jml WHERE id_alat = '$id_a'");
        }

        // B. Update Status Peminjaman menjadi 'dipinjam'
        $q_update = "UPDATE peminjaman 
                    SET status = 'dipinjam', id_petugas = '$id_petugas' 
                    WHERE id_peminjaman = '$id_peminjaman'";
        
        if (mysqli_query($koneksi, $q_update)) {
            if (function_exists('catat_log')) {
                catat_log($koneksi, $id_petugas, "Menyetujui & menyerahkan barang peminjaman ID #$id_peminjaman");
            }
            $pesan_sukses = "Pengajuan peminjaman #$id_peminjaman <b>berhasil disetujui!</b> Stok barang di database telah otomatis berkurang.";
        } else {
            $pesan_gagal = "Gagal memperbarui status: " . mysqli_error($koneksi);
        }
    }
}

// 2. Proses TOLAK PENGAJUAN
if (isset($_POST['tolak'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];

    $q_update = "UPDATE peminjaman 
                SET status = 'ditolak', id_petugas = '$id_petugas' 
                WHERE id_peminjaman = '$id_peminjaman'";
    
    if (mysqli_query($koneksi, $q_update)) {
        if (function_exists('catat_log')) {
            catat_log($koneksi, $id_petugas, "Menolak pengajuan peminjaman ID #$id_peminjaman");
        }
        $pesan_sukses = "Pengajuan peminjaman #$id_peminjaman telah <b>ditolak</b>.";
    } else {
        $pesan_gagal = "Gagal menolak pengajuan: " . mysqli_error($koneksi);
    }
}

// Query Pengajuan Masuk Status 'diajukan'
$query_peminjaman = "
    SELECT p.*, u.nama_lengkap, u.username
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    WHERE p.status = 'diajukan'
    ORDER BY p.tanggal_pinjam DESC
";
$res_peminjaman = mysqli_query($koneksi, $query_peminjaman);
?>

<!-- Banner Selamat Datang Petugas -->
<div class="card card-custom shadow-sm mb-4">
    <div class="card-body py-3">
        <h4 class="fw-bold mb-1" style="color: #622B14;">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>! 👋</h4>
        <p class="text-muted small mb-0">Panel Petugas: Verifikasi pengajuan peminjaman barang warga secara langsung di bawah ini.</p>
    </div>
</div>

<!-- Alert Pesan -->
<?php if (!empty($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $pesan_sukses; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($pesan_gagal)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $pesan_gagal; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Table Persetujuan Pengajuan Baru -->
<div class="card card-custom shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0" style="color: #622B14;">📥 Pengajuan Peminjaman Baru (Perlu Verifikasi)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #622B14; color: #ffffff;">
                    <tr>
                        <th class="ps-3">ID & Tanggal</th>
                        <th>Peminjam</th>
                        <th>Daftar Barang & Jumlah</th>
                        <th>Rencana Kembali</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_peminjaman) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($res_peminjaman)): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold" style="color: #622B14;">#<?= $p['id_peminjaman']; ?></span><br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($p['tanggal_pinjam'])); ?></small>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($p['nama_lengkap']); ?></b><br>
                                    <small class="text-muted">@<?= htmlspecialchars($p['username']); ?></small>
                                </td>
                                <td>
                                    <ul class="mb-0 ps-3 small">
                                        <?php 
                                        $id_p = $p['id_peminjaman'];
                                        $q_det = mysqli_query($koneksi, "
                                            SELECT dp.jumlah_pinjam, a.nama_alat, a.stok 
                                            FROM detail_peminjaman dp 
                                            JOIN alat a ON dp.id_alat = a.id_alat 
                                            WHERE dp.id_peminjaman = '$id_p'
                                        ");
                                        while ($det = mysqli_fetch_assoc($q_det)):
                                        ?>
                                            <li>
                                                <b><?= htmlspecialchars($det['nama_alat']); ?></b> (<?= $det['jumlah_pinjam']; ?> unit)
                                                <span class="badge bg-light text-dark border ms-1">Stok: <?= $det['stok']; ?></span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <?= date('d M Y', strtotime($p['tanggal_kembali_rencana'])); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <form action="dashboard.php" method="POST" onsubmit="return confirm('Setujui pengajuan ini? Stok barang akan berkurang.');">
                                            <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman']; ?>">
                                            <button type="submit" name="setujui" class="btn btn-success btn-sm fw-bold">Setujui & Serahkan</button>
                                        </form>
                                        
                                        <form action="dashboard.php" method="POST" onsubmit="return confirm('Tolak pengajuan peminjaman ini?');">
                                            <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman']; ?>">
                                            <button type="submit" name="tolak" class="btn btn-danger btn-sm">Tolak</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Tidak ada pengajuan peminjaman baru yang perlu disetujui saat ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>