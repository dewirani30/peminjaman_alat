<?php
include '../koneksi.php';
include '../includes/header.php';

// Cek hak akses petugas
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'petugas' && $_SESSION['role'] != 'admin')) {
    header("Location: ../index.php");
    exit();
}

// Default filter: Tanggal Hari Ini
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';

// Query SQL Laporan
$query_sql = "
    SELECT p.*, u.nama_lengkap, u.username, 
           pg.tanggal_kembali_aktual, pg.denda,
           pet.nama_lengkap AS nama_petugas
    FROM peminjaman p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
    LEFT JOIN users pet ON p.id_petugas = pet.id_user
    WHERE DATE(p.tanggal_pinjam) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
";

if ($status_filter != 'semua') {
    $status_filter_clean = mysqli_real_escape_string($koneksi, $status_filter);
    $query_sql .= " AND p.status = '$status_filter_clean'";
}

$query_sql .= " ORDER BY p.tanggal_pinjam DESC";
$res_laporan = mysqli_query($koneksi, $query_sql);
?>

<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    body { background-color: #ffffff !important; font-size: 12pt; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h3 class="fw-bold mb-0" style="color: #622B14;">📊 Laporan Peminjaman Inventaris Desa</h3>
        <p class="text-muted small mb-0">Rekapitulasi riwayat transaksi peminjaman dan pengembalian barang</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-custom fw-bold">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>
</div>

<!-- Form Filter Tanggal & Status -->
<div class="card card-custom shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form action="laporan.php" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color: #622B14;">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color: #622B14;">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color: #622B14;">Filter Status</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= ($status_filter == 'semua') ? 'selected' : ''; ?>>-- Semua Status --</option>
                    <option value="diajukan" <?= ($status_filter == 'diajukan') ? 'selected' : ''; ?>>Diajukan</option>
                    <option value="dipinjam" <?= ($status_filter == 'dipinjam') ? 'selected' : ''; ?>>Dipinjam</option>
                    <option value="menunggu pengecekan petugas" <?= ($status_filter == 'menunggu pengecekan petugas') ? 'selected' : ''; ?>>Pengecekan Petugas</option>
                    <option value="dikembalikan" <?= ($status_filter == 'dikembalikan') ? 'selected' : ''; ?>>Dikembalikan</option>
                    <option value="ditolak" <?= ($status_filter == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-custom w-100">Filter Data</button>
                <a href="laporan.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Kop Surat Cetak -->
<div class="d-none d-print-block text-center mb-4 border-bottom pb-3">
    <h3 class="fw-bold mb-1">PEMERINTAH KABUPATEN / DESA</h3>
    <h4 class="fw-bold mb-1">LAPORAN PEMINJAMAN INVENTARIS SARANA & PRASARANA</h4>
    <p class="mb-0 text-muted small">Periode: <?= date('d M Y', strtotime($tgl_mulai)); ?> s/d <?= date('d M Y', strtotime($tgl_selesai)); ?></p>
</div>

<!-- Table Laporan Data -->
<div class="card card-custom shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead style="background-color: #622B14; color: #ffffff;" class="table-dark">
                    <tr>
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Peminjam</th>
                        <th>Daftar Barang & Jumlah</th>
                        <th>Tgl Pinjam / Kembali</th>
                        <th class="text-center">Status</th>
                        <th>Petugas</th>
                        <th class="text-end">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $total_denda_semua = 0;
                    if (mysqli_num_rows($res_laporan) > 0): 
                    ?>
                        <?php while ($row = mysqli_fetch_assoc($res_laporan)): 
                            $total_denda_semua += $row['denda'] ?? 0;
                        ?>
                            <tr>
                                <td class="text-center fw-bold"><?= $no++; ?></td>
                                <td>
                                    <b><?= htmlspecialchars($row['nama_lengkap']); ?></b><br>
                                    <small class="text-muted">@<?= htmlspecialchars($row['username']); ?></small>
                                </td>
                                <td>
                                    <ul class="mb-0 ps-3 small">
                                        <?php 
                                        $id_p = $row['id_peminjaman'];
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
                                <td class="small">
                                    Pinjam: <?= date('d/m/Y H:i', strtotime($row['tanggal_pinjam'])); ?><br>
                                    Rencana Kembali: <b><?= date('d/m/Y', strtotime($row['tanggal_kembali_rencana'])); ?></b><br>
                                    <?php if (!empty($row['tanggal_kembali_aktual'])): ?>
                                        <span class="text-success">Aktual: <?= date('d/m/Y H:i', strtotime($row['tanggal_kembali_aktual'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $st = $row['status'];
                                    if ($st == 'diajukan') echo '<span class="badge bg-warning text-dark">Diajukan</span>';
                                    elseif ($st == 'dipinjam') echo '<span class="badge bg-primary">Dipinjam</span>';
                                    elseif ($st == 'menunggu pengecekan petugas') echo '<span class="badge bg-info text-dark">Pengecekan Petugas</span>';
                                    elseif ($st == 'dikembalikan') echo '<span class="badge bg-success">Dikembalikan</span>';
                                    elseif ($st == 'ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                    else echo '<span class="badge bg-secondary">' . $st . '</span>';
                                    ?>
                                </td>
                                <td class="small">
                                    <?= !empty($row['nama_petugas']) ? htmlspecialchars($row['nama_petugas']) : '<em>-</em>'; ?>
                                </td>
                                <td class="text-end fw-bold">
                                    Rp <?= number_format($row['denda'] ?? 0, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <tr class="table-light fw-bold">
                            <td colspan="6" class="text-end">TOTAL DENDA TERKUMPUL:</td>
                            <td class="text-end" style="color: #622B14;">
                                Rp <?= number_format($total_denda_semua, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data peminjaman pada periode tanggal yang dipilih.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tanda Tangan Laporan -->
<div class="d-none d-print-block mt-5">
    <div class="row">
        <div class="col-8"></div>
        <div class="col-4 text-center">
            <p class="mb-1">Tercetak pada: <?= date('d F Y, H:i'); ?> WIB</p>
            <p class="mb-5">Petugas Desa Penanggung Jawab,</p>
            <br><br>
            <p class="fw-bold mb-0"><u><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></u></p>
            <small>ID Petugas: #<?= $_SESSION['id_user']; ?></small>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>