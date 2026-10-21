<?php
include '../koneksi.php';
include '../includes/header.php';

// Cek hak akses peminjam
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'peminjam') {
    header("Location: ../index.php");
    exit();
}

$id_user_login = $_SESSION['id_user'];
$pesan_sukses   = '';

// Aksi Peminjam Mengajukan Pengembalian Barang
if (isset($_POST['ajukan_kembali'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    
    $q_up = "UPDATE peminjaman SET status = 'menunggu pengecekan petugas' WHERE id_peminjaman = '$id_peminjaman' AND id_user = '$id_user_login'";
    if (mysqli_query($koneksi, $q_up)) {
        if (function_exists('catat_log')) {
            catat_log($koneksi, $id_user_login, "Mengajukan pengembalian alat untuk peminjaman #$id_peminjaman");
        }
        $pesan_sukses = "Pengajuan pengembalian berhasil dikirim! Silakan serahkan fisik barang ke Petugas Desa.";
    }
}

// Query Riwayat Peminjaman User yang Sedang Login
$query_riwayat = "
    SELECT p.*, pg.tanggal_kembali_aktual, pg.denda
    FROM peminjaman p
    LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
    WHERE p.id_user = '$id_user_login'
    ORDER BY p.tanggal_pinjam DESC
";
$res_riwayat = mysqli_query($koneksi, $query_riwayat);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0" style="color: #622B14;">📋 Riwayat Peminjaman Saya</h3>
        <p class="text-muted small mb-0">Pantau status pengajuan dan lakukan pengajuan pengembalian alat</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-custom fw-semibold">
            📦 + Ajukan Peminjaman
        </a>
    </div>
</div>

<?php if (!empty($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $pesan_sukses; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Tabel Riwayat Peminjaman -->
<div class="card card-custom shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #622B14; color: #ffffff;">
                    <tr>
                        <th class="ps-3">ID & Tgl Pengajuan</th>
                        <th>Daftar Barang & Jumlah</th>
                        <th>Rencana Kembali</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi Peminjam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_riwayat) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($res_riwayat)): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold" style="color: #622B14;">#<?= $r['id_peminjaman']; ?></span><br>
                                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($r['tanggal_pinjam'])); ?> WIB</small>
                                </td>
                                <td>
                                    <ul class="mb-0 ps-3 small">
                                        <?php 
                                        $id_p = $r['id_peminjaman'];
                                        $q_det = mysqli_query($koneksi, "
                                            SELECT dp.jumlah_pinjam, a.nama_alat 
                                            FROM detail_peminjaman dp 
                                            JOIN alat a ON dp.id_alat = a.id_alat 
                                            WHERE dp.id_peminjaman = '$id_p'
                                        ");
                                        while ($det = mysqli_fetch_assoc($q_det)):
                                        ?>
                                            <li><b><?= htmlspecialchars($det['nama_alat']); ?></b> (<?= $det['jumlah_pinjam']; ?> unit)</li>
                                        <?php endwhile; ?>
                                    </ul>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= date('d M Y', strtotime($r['tanggal_kembali_rencana'])); ?>
                                    </span>
                                    <?php if (!empty($r['tanggal_kembali_aktual'])): ?>
                                        <br><small class="text-success">Dikembalikan: <?= date('d/m/Y H:i', strtotime($r['tanggal_kembali_aktual'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $st = $r['status'];
                                    if ($st == 'diajukan') echo '<span class="badge bg-warning text-dark">Diajukan</span>';
                                    elseif ($st == 'dipinjam') echo '<span class="badge bg-primary">Sedang Dipinjam</span>';
                                    elseif ($st == 'menunggu pengecekan petugas') echo '<span class="badge bg-info text-dark">Menunggu Pengecekan Petugas</span>';
                                    elseif ($st == 'dikembalikan') echo '<span class="badge bg-success">Dikembalikan</span>';
                                    elseif ($st == 'ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                    else echo '<span class="badge bg-secondary">' . $st . '</span>';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['status'] == 'dipinjam'): ?>
                                        <form action="riwayat.php" method="POST" onsubmit="return confirm('Apakah Anda ingin mengajukan pengembalian alat ini ke Petugas?');">
                                            <input type="hidden" name="id_peminjaman" value="<?= $r['id_peminjaman']; ?>">
                                            <button type="submit" name="ajukan_kembali" class="btn btn-outline-custom btn-sm fw-bold">
                                                🔄 Kembalikan Alat
                                            </button>
                                        </form>
                                    <?php elseif ($r['status'] == 'menunggu pengecekan petugas'): ?>
                                        <small class="text-muted">Proses Verifikasi Petugas</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Anda belum memiliki riwayat peminjaman barang.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>