<?php
include '../koneksi.php';
include '../includes/header.php';

// Cek hak akses petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../index.php");
    exit();
}

$id_petugas  =$_SESSION['id_user'];
$pesan_sukses = '';$pesan_gagal  = '';

// PROSES KEMBALIKAN BARANG & HITUNG DENDA
if (isset($_POST['proses_kembali'])) {$id_peminjaman = (int)$_POST['id_peminjaman'];$denda         = (int)$_POST['denda'];$tgl_aktual    = date('Y-m-d H:i:s');

    // 1. Simpan ke tabel `pengembalian`
    $q_pengembalian = "INSERT INTO pengembalian (id_peminjaman, tanggal_kembali_aktual, denda, id_petugas) 
                       VALUES ('$id_peminjaman', '$tgl_aktual', '$denda', '$id_petugas')";
    
    if (mysqli_query($koneksi,$q_pengembalian)) {
        
        // 2. Kembalikan Stok Alat
        $q_detail = mysqli_query($koneksi, "SELECT id_alat, jumlah_pinjam FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
        while ($item = mysqli_fetch_assoc($q_detail)) {
            $id_a =$item['id_alat'];
            $jml  =$item['jumlah_pinjam'];
            mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jml WHERE id_alat = '$id_a'");
        }

        // 3. Update status peminjaman menjadi 'dikembalikan'
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'dikembalikan' WHERE id_peminjaman = '$id_peminjaman'");

        // Catat Log
        if (function_exists('catat_log')) {
            catat_log($koneksi,$id_petugas, "Menerima pengembalian fisik alat ID #$id_peminjaman (Denda: Rp " . number_format($denda, 0, ',', '.') . ")");
        }

        $pesan_sukses = "Pengembalian peminjaman #$id_peminjaman <b>berhasil dikonfirmasi!</b> Stok barang di database telah bertambah kembali.";
    } else {
        $pesan_gagal = "Gagal memproses pengembalian: " . mysqli_error($koneksi);
    }
}

// Query Khusus Pengajuan Pengembalian (Status 'menunggu pengecekan petugas')
$query_aktif = "
    SELECT p.*, u.nama_lengkap, u.username
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    WHERE p.status = 'menunggu pengecekan petugas'
    ORDER BY p.tanggal_pinjam ASC
";
$res_aktif = mysqli_query($koneksi,$query_aktif);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0" style="color: #622B14;">🔄 Verifikasi Pengembalian Alat</h3>
        <p class="text-muted small mb-0">Konfirmasi pengajuan pengembalian fisik barang dari peminjam</p>
    </div>
</div>

<!-- Alert Pesan Sukses / Gagal -->
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

<!-- Table Peminjaman yang Mengajukan Pengembalian -->
<div class="card card-custom shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #622B14; color: #ffffff;">
                    <tr>
                        <th class="ps-3">ID & Peminjam</th>
                        <th>Daftar Barang</th>
                        <th>Tgl Pinjam & Batas Kembali</th>
                        <th>Status Terlambat</th>
                        <th class="text-center">Aksi Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_aktif) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($res_aktif)): 
                            $tgl_rencana  = new DateTime($p['tanggal_kembali_rencana']);
                            $tgl_sekarang = new DateTime(date('Y-m-d'));$terlambat_hari = 0;
                            $estimasi_denda = 0;

                            if ($tgl_sekarang > $tgl_rencana) {$diff = $tgl_sekarang->diff($tgl_rencana);
                                $terlambat_hari =$diff->days;
                                $estimasi_denda =$terlambat_hari * 5000;
                            }
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold" style="color: #622B14;">#<?= $p['id_peminjaman']; ?></span><br>
                                    <b><?= htmlspecialchars($p['nama_lengkap']); ?></b>
                                </td>
                                <td>
                                    <ul class="mb-0 ps-3 small">
                                        <?php 
                                        $id_p =$p['id_peminjaman'];
                                        $q_det = mysqli_query($koneksi, "
                                            SELECT dp.jumlah_pinjam, a.nama_alat 
                                            FROM detail_peminjaman dp 
                                            JOIN alat a ON dp.id_alat = a.id_alat 
                                            WHERE dp.id_peminjaman = '$id_p'
                                        ");
                                        while ($det = mysqli_fetch_assoc($q_det)):
                                        ?>
                                            <li><b><?= htmlspecialchars($det['nama_alat']); ?></b> (<?=$det['jumlah_pinjam']; ?> unit)</li>
                                        <?php endwhile; ?>
                                    </ul>
                                </td>
                                <td class="small">
                                    Pinjam: <?= date('d/m/Y', strtotime($p['tanggal_pinjam'])); ?><br>
                                    Kembali: <b><?= date('d/m/Y', strtotime($p['tanggal_kembali_rencana'])); ?></b>
                                </td>
                                <td>
                                    <?php if ($terlambat_hari > 0): ?>
                                        <span class="badge bg-danger">Terlambat <?= $terlambat_hari; ?> Hari</span><br>
                                        <small class="text-danger fw-bold">Est. Denda: Rp <?= number_format($estimasi_denda, 0, ',', '.'); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-success">Tepat Waktu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-custom btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalKembali<?= $p['id_peminjaman']; ?>">
                                        Konfirmasi Pengembalian
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Konfirmasi Pengembalian -->
                            <div class="modal fade" id="modalKembali<?= $p['id_peminjaman']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background-color: #622B14; color: #ffffff;">
                                            <h5 class="modal-title fw-bold">Verifikasi Pengembalian #<?= $p['id_peminjaman']; ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="pengembalian.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold" style="color: #622B14;">Peminjam</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($p['nama_lengkap']); ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold" style="color: #622B14;">Nominal Denda (Rp)</label>
                                                    <input type="number" name="denda" class="form-control" value="<?= $estimasi_denda; ?>" min="0" required>
                                                    <small class="text-muted">* Terhitung Rp 5.000/hari terlambat. Bebas diubah jika ada denda kerusakan fisik.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="proses_kembali" class="btn btn-custom">Konfirmasi Selesai</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Belum ada warga yang mengajukan pengembalian alat saat ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>