<?php
include '../koneksi.php';
include '../includes/header.php';

// Cek hak akses admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$id_admin    = $_SESSION['id_user'];
$pesan_sukses = '';
$pesan_gagal  = '';

// Filter Tanggal & Status (Default: Hari Ini)
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');
$status_fltr = isset($_GET['status']) ? $_GET['status'] : 'semua';

// PROSES UBAH STATUS OLEH ADMIN
if (isset($_POST['update_status'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    $status_baru   = mysqli_real_escape_string($koneksi, $_POST['status']);

    $q_lama = mysqli_query($koneksi, "SELECT status FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");
    $d_lama = mysqli_fetch_assoc($q_lama);
    $status_lama = $d_lama['status'];

    if ($status_lama != $status_baru) {
        if (($status_lama == 'diajukan' || $status_lama == 'ditolak') && ($status_baru == 'dipinjam' || $status_baru == 'disetujui')) {
            $q_det = mysqli_query($koneksi, "SELECT id_alat, jumlah_pinjam FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
            while ($det = mysqli_fetch_assoc($q_det)) {
                $id_a = $det['id_alat'];
                $jml  = $det['jumlah_pinjam'];
                mysqli_query($koneksi, "UPDATE alat SET stok = stok - $jml WHERE id_alat = '$id_a'");
            }
        }

        if (($status_lama == 'dipinjam' || $status_lama == 'disetujui') && $status_baru == 'dikembalikan') {
            $q_det = mysqli_query($koneksi, "SELECT id_alat, jumlah_pinjam FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
            while ($det = mysqli_fetch_assoc($q_det)) {
                $id_a = $det['id_alat'];
                $jml  = $det['jumlah_pinjam'];
                mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jml WHERE id_alat = '$id_a'");
            }

            $cek_p = mysqli_query($koneksi, "SELECT * FROM pengembalian WHERE id_peminjaman = '$id_peminjaman'");
            if (mysqli_num_rows($cek_p) == 0) {
                $tgl_now = date('Y-m-d H:i:s');
                mysqli_query($koneksi, "INSERT INTO pengembalian (id_peminjaman, tanggal_kembali_aktual, denda, id_petugas) VALUES ('$id_peminjaman', '$tgl_now', 0, '$id_admin')");
            }
        }

        $q_up = "UPDATE peminjaman SET status = '$status_baru', id_petugas = '$id_admin' WHERE id_peminjaman = '$id_peminjaman'";
        if (mysqli_query($koneksi, $q_up)) {
            if (function_exists('catat_log')) {
                catat_log($koneksi, $id_admin, "Admin mengubah status peminjaman #$id_peminjaman dari '$status_lama' menjadi '$status_baru'");
            }
            $pesan_sukses = "Status peminjaman #$id_peminjaman berhasil diperbarui menjadi <b>$status_baru</b>.";
        } else {
            $pesan_gagal = "Gagal memperbarui status: " . mysqli_error($koneksi);
        }
    }
}

// Query dengan Filter Tanggal & Status
$query_peminjaman = "
    SELECT p.*, u.nama_lengkap, u.username, pet.nama_lengkap AS nama_petugas
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN users pet ON p.id_petugas = pet.id_user
    WHERE DATE(p.tanggal_pinjam) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
";

if ($status_fltr != 'semua') {
    $status_fltr_clean = mysqli_real_escape_string($koneksi, $status_fltr);
    $query_peminjaman .= " AND p.status = '$status_fltr_clean'";
}

$query_peminjaman .= " ORDER BY p.tanggal_pinjam DESC";
$res_peminjaman = mysqli_query($koneksi, $query_peminjaman);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0" style="color: #622B14;">📋 Kelola & Monitoring Seluruh Peminjaman</h3>
        <p class="text-muted small mb-0">Panel kontrol Admin untuk memantau dan menyesuaikan seluruh transaksi peminjaman</p>
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

<!-- Form Filter Tanggal & Status -->
<div class="card card-custom shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="data_peminjaman.php" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color: #622B14;">Dari Tanggal</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color: #622B14;">Sampai Tanggal</label>
                <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color: #622B14;">Status Transaksi</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= ($status_fltr == 'semua') ? 'selected' : ''; ?>>-- Semua Status --</option>
                    <option value="diajukan" <?= ($status_fltr == 'diajukan') ? 'selected' : ''; ?>>Diajukan</option>
                    <option value="dipinjam" <?= ($status_fltr == 'dipinjam') ? 'selected' : ''; ?>>Dipinjam</option>
                    <option value="dikembalikan" <?= ($status_fltr == 'dikembalikan') ? 'selected' : ''; ?>>Dikembalikan</option>
                    <option value="ditolak" <?= ($status_fltr == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-custom w-100">Filter Data</button>
                <a href="data_peminjaman.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Data Peminjaman -->
<div class="card card-custom shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #622B14; color: #ffffff;">
                    <tr>
                        <th class="ps-3">ID & Waktu</th>
                        <th>Peminjam</th>
                        <th>Daftar Barang Multi-Item</th>
                        <th>Tgl Rencana Kembali</th>
                        <th class="text-center">Status</th>
                        <th>Petugas Penanggung Jawab</th>
                        <th class="text-center">Aksi Admin</th>
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
                                        <?= date('d M Y', strtotime($p['tanggal_kembali_rencana'])); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $st = $p['status'];
                                    if ($st == 'diajukan') echo '<span class="badge bg-warning text-dark">Diajukan</span>';
                                    elseif ($st == 'dipinjam') echo '<span class="badge bg-primary">Dipinjam</span>';
                                    elseif ($st == 'dikembalikan') echo '<span class="badge bg-success">Dikembalikan</span>';
                                    elseif ($st == 'ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                    else echo '<span class="badge bg-secondary">' . $st . '</span>';
                                    ?>
                                </td>
                                <td class="small">
                                    <?= !empty($p['nama_petugas']) ? htmlspecialchars($p['nama_petugas']) : '<em>Belum Diverifikasi</em>'; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $p['id_peminjaman']; ?>">
                                        Edit Status
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Edit Status oleh Admin -->
                            <div class="modal fade" id="modalStatus<?= $p['id_peminjaman']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background-color: #622B14; color: #ffffff;">
                                            <h5 class="modal-title fw-bold">Ubah Status Transaksi #<?= $p['id_peminjaman']; ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="data_peminjaman.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold" style="color: #622B14;">Status Peminjaman</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="diajukan" <?= ($p['status'] == 'diajukan') ? 'selected' : ''; ?>>Diajukan</option>
                                                        <option value="dipinjam" <?= ($p['status'] == 'dipinjam') ? 'selected' : ''; ?>>Dipinjam (Potong Stok)</option>
                                                        <option value="dikembalikan" <?= ($p['status'] == 'dikembalikan') ? 'selected' : ''; ?>>Dikembalikan (Tambah Stok)</option>
                                                        <option value="ditolak" <?= ($p['status'] == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update_status" class="btn btn-custom">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data peminjaman pada periode tanggal/status yang dipilih.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>