<?php
include '../koneksi.php';
include '../includes/header.php';

// Cek hak akses peminjam
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'peminjam') {
    header("Location: ../index.php");
    exit();
}

$id_user_login =$_SESSION['id_user'];
$pesan_sukses   = '';$pesan_gagal    = '';

// Inisialisasi Keranjang Sesi jika belum ada
if (!isset($_SESSION['keranjang'])) {$_SESSION['keranjang'] = [];
}

// 1. Aksi: Tambah Alat ke Keranjang
if (isset($_POST['tambah_keranjang'])) {
    $id_alat = (int)$_POST['id_alat'];
    $jumlah  = (int)$_POST['jumlah'];

    // Cek stok alat
    $q_alat = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat = '$id_alat'");
    $d_alat = mysqli_fetch_assoc($q_alat);

    if ($d_alat) {$stok_tersedia = (int)$d_alat['stok'];$jumlah_lama   = isset($_SESSION['keranjang'][$id_alat]) ? $_SESSION['keranjang'][$id_alat] : 0;
        $total_baru    = $jumlah_lama +$jumlah;

        if ($total_baru > $stok_tersedia) {$pesan_gagal = "Jumlah yang ingin dipinjam (" . $total_baru . ") melebihi sisa stok (" . $stok_tersedia . ")!";
        } else {
            $_SESSION['keranjang'][$id_alat] =$total_baru;
            $pesan_sukses = "<b>" . htmlspecialchars($d_alat['nama_alat']) . "</b> (" . $jumlah . " unit) berhasil ditambahkan ke keranjang!";
        }
    }
}

// 2. Aksi: Hapus Item dari Keranjang
if (isset($_POST['hapus_item'])) {
    $id_alat_hapus = (int)$_POST['id_alat'];
    unset($_SESSION['keranjang'][$id_alat_hapus]);$pesan_sukses = "Item berhasil dihapus dari keranjang.";
}

// 3. Aksi: Kosongkan Keranjang
if (isset($_POST['kosongkan_keranjang'])) {
    $_SESSION['keranjang'] = [];$pesan_sukses = "Keranjang peminjaman telah dikosongkan.";
}

// 4. Aksi: Checkout / Kirim Pengajuan Multi-Item
if (isset($_POST['proses_checkout'])) {
    $tgl_kembali  = mysqli_real_escape_string($koneksi, $_POST['tanggal_kembali_rencana']);$tgl_sekarang = date('Y-m-d H:i:s');

    if (empty($_SESSION['keranjang'])) {$pesan_gagal = "Keranjang peminjaman Anda masih kosong! Silakan pilih alat terlebih dahulu.";
    } elseif (strtotime($tgl_kembali) < strtotime(date('Y-m-d'))) {$pesan_gagal = "Tanggal rencana pengembalian tidak boleh lebih awal dari hari ini!";
    } else {
        // --- BATAS ATURAN SISTEM: Maksimal 3 Transaksi Aktif ---
        $q_cek_aktif = mysqli_query($koneksi, "SELECT COUNT(*) AS total_aktif FROM peminjaman WHERE id_user = '$id_user_login' AND status IN ('diajukan', 'disetujui', 'dipinjam', 'menunggu pengecekan petugas')");
        $d_cek_aktif = mysqli_fetch_assoc($q_cek_aktif);

        if ($d_cek_aktif['total_aktif'] >= 3) {$pesan_gagal = "<b>Aturan Sistem Terlampaui:</b> Anda memiliki " . $d_cek_aktif['total_aktif'] . " peminjaman aktif. Batas maksimal peminjaman aktif adalah 3 kali. Silakan selesaikan/kembalikan peminjaman sebelumnya terlebih dahulu!";
        } else {
            // Validasi Stok Sekali Lagi Sebelum Insert
            $stok_cukup = true;
            $nama_alat_bermasalah = '';

            foreach ($_SESSION['keranjang'] as $id_a =>$jml) {
                $q_stok = mysqli_query($koneksi, "SELECT nama_alat, stok FROM alat WHERE id_alat = '$id_a'");
                $d_stok = mysqli_fetch_assoc($q_stok);
                if ($jml > $d_stok['stok']) {$stok_cukup = false;
                    $nama_alat_bermasalah =$d_stok['nama_alat'];
                    break;
                }
            }

            if (!$stok_cukup) {$pesan_gagal = "Stok alat <b>" . htmlspecialchars($nama_alat_bermasalah) . "</b> tidak mencukupi untuk jumlah yang diminta!";
            } else {
                // 1. Simpan Header di tabel `peminjaman`
                $query_header = "INSERT INTO peminjaman (id_user, tanggal_pinjam, tanggal_kembali_rencana, status) 
                                VALUES ('$id_user_login', '$tgl_sekarang', '$tgl_kembali', 'diajukan')";
                
                if (mysqli_query($koneksi,$query_header)) {
                    $id_peminjaman_baru = mysqli_insert_id($koneksi);
                    $total_jenis_alat   = count($_SESSION['keranjang']);

                    // 2. Simpan Multi-Item Detail di tabel `detail_peminjaman`
                    foreach ($_SESSION['keranjang'] as $id_a =>$jml) {
                        $id_a = (int)$id_a;
                        $jml  = (int)$jml;
                        $query_detail = "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah_pinjam) 
                                         VALUES ('$id_peminjaman_baru', '$id_a', '$jml')";
                        mysqli_query($koneksi,$query_detail);
                    }

                    // Catat Log Aktivitas
                    if (function_exists('catat_log')) {
                        catat_log($koneksi,$id_user_login, "Mengajukan peminjaman multi-item ($total_jenis_alat jenis alat)");
                    }

                    // Kosongkan keranjang setelah checkout sukses
                    $_SESSION['keranjang'] = [];$pesan_sukses = "Pengajuan peminjaman multi-item berhasil dikirim! Silakan pantau status persetujuan di menu <b>Riwayat Saya</b>.";
                } else {
                    $pesan_gagal = "Gagal menyimpan pengajuan: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

// Filter & Pencarian Katalog
// Filter & Pencarian Katalog
$keyword  = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$query_sql = "SELECT a.*, k.nama_kategori 
              FROM alat a 
              LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
              WHERE 1=1";

if ($keyword !== '') {
    $keyword_clean = mysqli_real_escape_string($koneksi, $keyword);
    $query_sql .= " AND (a.nama_alat LIKE '%$keyword_clean%' OR a.deskripsi LIKE '%$keyword_clean%')";
}

if ($kategori > 0) {
    $query_sql .= " AND a.id_kategori = '$kategori'";
}

$query_sql .= " ORDER BY a.nama_alat ASC";

$res_alat = mysqli_query($koneksi, $query_sql);

// Cek jika ada error di query MySQL
if (!$res_alat) {
    echo "<div class='alert alert-danger my-3'><b>Error Query SQL:</b> " . mysqli_error($koneksi) . "</div>";
}

// Query daftar kategori untuk dropdown
$q_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Hitung total item keranjang
$total_item_keranjang = array_sum($_SESSION['keranjang']);

?>

<!-- Banner Selamat Datang Peminjam -->
<div class="card card-custom shadow-sm mb-4">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color: #622B14;">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>! 👋</h3>
            <p class="text-muted mb-0">Silakan pilih alat/sarana inventaris desa yang ingin Anda pinjam.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-custom position-relative fw-semibold" data-bs-toggle="modal" data-bs-target="#modalKeranjang">
                🛒 Keranjang
                <?php if ($total_item_keranjang > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $total_item_keranjang; ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>
    </div>
</div>

<!-- Alert Pesan Sukses / Gagal -->
<?php if (!empty($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $pesan_sukses; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($pesan_gagal)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $pesan_gagal; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter & Form Pencarian -->
<div class="card card-custom shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="dashboard.php" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="keyword" class="form-control" placeholder="Cari nama alat / barang..." value="<?= htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-4">
                <select name="kategori" class="form-select">
                    <option value="0">-- Semua Kategori --</option>
                    <?php if ($q_kategori): ?>
                        <?php while ($kat = mysqli_fetch_assoc($q_kategori)): ?>
                            <option value="<?= $kat['id_kategori']; ?>" <?= ($kategori ==$kat['id_kategori']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kat['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-custom w-100">Cari</button>
                <a href="dashboard.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Kartu Katalog Alat -->
<div class="row g-3">
    <?php if ($res_alat && mysqli_num_rows($res_alat) > 0): ?>
        <?php while ($alat = mysqli_fetch_assoc($res_alat)): ?>
            <div class="col-md-4 col-sm-6">
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
                            <p class="text-muted small mb-3">
                                <?= !empty($alat['deskripsi']) ? htmlspecialchars($alat['deskripsi']) : '<em>Tidak ada deskripsi.</em>'; ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($alat['stok'] > 0): ?>
                                <form action="dashboard.php" method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="id_alat" value="<?= $alat['id_alat']; ?>">
                                    <input type="number" name="jumlah" class="form-control form-control-sm text-center" style="width: 70px;" min="1" max="<?= $alat['stok']; ?>" value="1" required>
                                    <button type="submit" name="tambah_keranjang" class="btn btn-custom btn-sm w-100 fw-bold">
                                        + Keranjang
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm w-100" disabled>Stok Habis</button>
                            <?php endif; ?>
                        </div>
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

<!-- Modal Keranjang Peminjaman (Multi-Item Checkout) -->
<div class="modal fade" id="modalKeranjang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #622B14; color: #ffffff;">
                <h5 class="modal-title fw-bold">🛒 Keranjang Peminjaman Barang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (!empty($_SESSION['keranjang'])): ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Alat</th>
                                    <th class="text-center">Jumlah Dipinjam</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($_SESSION['keranjang'] as $id_a =>$jml): 
                                    $q_det = mysqli_query($koneksi, "SELECT nama_alat FROM alat WHERE id_alat = '$id_a'");
                                    $d_det = mysqli_fetch_assoc($q_det);
                                ?>
                                    <tr>
                                        <td class="fw-bold" style="color: #622B14;"><?= htmlspecialchars($d_det['nama_alat'] ?? 'Barang #'.$id_a); ?></td>
                                        <td class="text-center"><?= $jml; ?> unit</td>
                                        <td class="text-center">
                                            <form action="dashboard.php" method="POST">
                                                <input type="hidden" name="id_alat" value="<?= $id_a; ?>">
                                                <button type="submit" name="hapus_item" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <form action="dashboard.php" method="POST" class="border-top pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color: #622B14;">Rencana Tanggal Pengembalian Seluruh Barang</label>
                            <input type="date" name="tanggal_kembali_rencana" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')); ?>" required>
                            <small class="text-muted">Batas aturan sistem: Maksimal 3 transaksi peminjaman aktif per warga.</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" name="kosongkan_keranjang" class="btn btn-outline-secondary btn-sm">Kosongkan Keranjang</button>
                            <button type="submit" name="proses_checkout" class="btn btn-custom px-4 fw-bold">Kirim Pengajuan Peminjaman</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Keranjang peminjaman Anda masih kosong.</p>
                        <small class="text-secondary">Pilih alat dari katalog di bawah dan klik tombol <b>+ Keranjang</b>.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>