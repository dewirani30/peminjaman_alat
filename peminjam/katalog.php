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
$pesan_gagal    = '';

// Proses Pengajuan Peminjaman Alat
if (isset($_POST['ajukan_pinjam'])) {
    $id_alat     = (int)$_POST['id_alat'];
    $jumlah      = (int)$_POST['jumlah'];
    $tgl_pinjam  = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $tgl_kembali = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);

    // Validasi stok alat
    $q_alat   = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat = '$id_alat'");
    $d_alat   = mysqli_fetch_assoc($q_alat);
    $nama_alat = $d_alat['nama_alat'];

    if ($jumlah <= 0) {
        $pesan_gagal = "Jumlah pinjam harus lebih dari 0!";
    } elseif ($jumlah > $d_alat['stok']) {
        $pesan_gagal = "Jumlah yang dipinjam melebihi stok yang tersedia (Sisa: {$d_alat['stok']})!";
    } elseif (strtotime($tgl_kembali) < strtotime($tgl_pinjam)) {
        $pesan_gagal = "Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman!";
    } else {
        // Status awal peminjaman: 'Menunggu'
        $query_pinjam = "INSERT INTO peminjaman (id_user, id_alat, jumlah, tgl_pinjam, tgl_kembali, status, denda) 
                         VALUES ('$id_user_login', '$id_alat', '$jumlah', '$tgl_pinjam', '$tgl_kembali', 'Menunggu', 0)";

        if (mysqli_query($koneksi, $query_pinjam)) {
            if (function_exists('catat_log')) {
                catat_log($koneksi, $id_user_login, "Mengajukan peminjaman alat: $nama_alat ($jumlah unit)");
            }
            $pesan_sukses = "Pengajuan peminjaman <b>" . htmlspecialchars($nama_alat) . "</b> berhasil dikirim! Silakan tunggu konfirmasi Petugas Desa.";
        } else {
            $pesan_gagal = "Gagal mengirim pengajuan: " . mysqli_error($koneksi);
        }
    }
}

// Filter & Pencarian
$keyword  = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, $_GET['keyword']) : '';
$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$query_sql = "SELECT a.*, k.nama_kategori 
              FROM alat a 
              LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
              WHERE 1=1";

if (!empty($keyword)) {
    $query_sql .= " AND (a.nama_alat LIKE '%$keyword%' OR a.deskripsi LIKE '%$keyword%')";
}
if ($kategori > 0) {
    $query_sql .= " AND a.id_kategori = '$kategori'";
}

$query_sql .= " ORDER BY a.nama_alat ASC";
$res_alat   = mysqli_query($koneksi, $query_sql);
$q_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0" style="color: #622B14;">📦 Katalog Inventaris Desa</h3>
        <p class="text-muted small mb-0">Pilih alat atau sarana yang ingin Anda pinjam untuk keperluan kegiatan desa</p>
    </div>
</div>

<!-- Alert Pesan Sukses / Gagal -->
<?php if (!empty($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $pesan_sukses; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($pesan_gagal)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $pesan_gagal; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter & Form Pencarian -->
<div class="card card-custom shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="katalog.php" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="keyword" class="form-control" placeholder="Cari nama alat / barang..." value="<?= htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-4">
                <select name="kategori" class="form-select">
                    <option value="0">-- Semua Kategori --</option>
                    <?php while ($kat = mysqli_fetch_assoc($q_kategori)): ?>
                        <option value="<?= $kat['id_kategori']; ?>" <?= ($kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($kat['nama_kategori']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-custom w-100">Cari</button>
                <a href="katalog.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Kartu Alat -->
<div class="row g-3">
    <?php if (mysqli_num_rows($res_alat) > 0): ?>
        <?php while ($alat = mysqli_fetch_assoc($res_alat)): ?>
            <div class="col-md-4 col_sm-6">
                <div class="card card-custom shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-secondary"><?= htmlspecialchars($alat['nama_kategori'] ?? 'Tanpa Kategori'); ?></span>
                                <?php if ($alat['stok'] > 0): ?>
                                    <span class="badge bg-success">Tersedia: <?= $alat['stok']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Stok Habis</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold mb-2" style="color: #622B14;"><?= htmlspecialchars($alat['nama_alat']); ?></h5>
                            <p class="text-muted small mb-2">
                                <?= !empty($alat['deskripsi']) ? htmlspecialchars($alat['deskripsi']) : '<em>Tidak ada deskripsi.</em>'; ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($alat['stok'] > 0): ?>
                                <button type="button" class="btn btn-custom w-100" data-bs-toggle="modal" data-bs-target="#modalPinjam<?= $alat['id_alat']; ?>">
                                    Ajukan Pinjam
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>Tidak Dapat Dipinjam</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Ajukan Peminjaman -->
            <div class="modal fade" id="modalPinjam<?= $alat['id_alat']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color: #622B14; color: #ffffff;">
                            <h5 class="modal-title fw-bold">Ajukan Pinjam: <?= htmlspecialchars($alat['nama_alat']); ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="katalog.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_alat" value="<?= $alat['id_alat']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: #622B14;">Jumlah Dipinjam (Maks: <?= $alat['stok']; ?>)</label>
                                    <input type="number" name="jumlah" class="form-control" min="1" max="<?= $alat['stok']; ?>" value="1" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #622B14;">Tanggal Pinjam</label>
                                        <input type="date" name="tgl_pinjam" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #622B14;">Tanggal Kembali</label>
                                        <input type="date" name="tgl_kembali" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')); ?>" required>
                                    </div>
                                </div>
                                <small class="text-muted d-block">
                                    * Catatan: Bawa KTP/identitas diri saat mengambil fisik barang di kantor desa.
                                </small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="ajukan_pinjam" class="btn btn-custom">Kirim Pengajuan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card card-custom shadow-sm text-center py-5">
                <p class="text-muted mb-0">Tidak ada alat atau barang yang ditemukan.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>