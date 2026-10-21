<?php 
include '../koneksi.php';
include '../includes/header.php'; 

// ==========================================
// 1. PROSES KATEGORI (TAMBAH, EDIT, HAPUS)
// ==========================================
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    $query = "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')";
    if (mysqli_query($koneksi, $query)) {
        catat_log($koneksi, $_SESSION['id_user'], "Menambahkan kategori baru: $nama_kategori");
        $_SESSION['pesan_sukses'] = "Kategori berhasil ditambahkan!";
        header("Location: data_alat.php");
        exit();
    } else {
        $pesan_error = "Gagal menambah kategori: " . mysqli_error($koneksi);
    }
}

if (isset($_POST['edit_kategori'])) {
    $id_kategori   = $_POST['id_kategori'];
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    $query = "UPDATE kategori SET nama_kategori = '$nama_kategori' WHERE id_kategori = '$id_kategori'";
    if (mysqli_query($koneksi, $query)) {
        catat_log($koneksi, $_SESSION['id_user'], "Mengubah kategori: $nama_kategori");
        $_SESSION['pesan_sukses'] = "Kategori berhasil diperbarui!";
        header("Location: data_alat.php");
        exit();
    } else {
        $pesan_error = "Gagal memperbarui kategori: " . mysqli_error($koneksi);
    }
}

if (isset($_GET['hapus_kategori'])) {
    $id_hapus = $_GET['hapus_kategori'];
    $q_k = mysqli_query($koneksi, "SELECT nama_kategori FROM kategori WHERE id_kategori = '$id_hapus'");
    $d_k = mysqli_fetch_assoc($q_k);
    $namakat = $d_k['nama_kategori'] ?? "ID $id_hapus";

    $query = "DELETE FROM kategori WHERE id_kategori = '$id_hapus'";
    if (mysqli_query($koneksi, $query)) {
        catat_log($koneksi, $_SESSION['id_user'], "Menghapus kategori: $namakat");
        $_SESSION['pesan_sukses'] = "Kategori berhasil dihapus!";
        header("Location: data_alat.php");
        exit();
    } else {
        $pesan_error = "Gagal menghapus kategori: " . mysqli_error($koneksi);
    }
}

// ==========================================
// 2. PROSES ALAT (TAMBAH, EDIT, HAPUS)
// ==========================================
if (isset($_POST['tambah_alat'])) {
    $nama_alat   = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $id_kategori = $_POST['id_kategori'];
    $deskripsi   = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $stok        = (int)$_POST['stok'];

    $query = "INSERT INTO alat (nama_alat, id_kategori, deskripsi, stok) 
              VALUES ('$nama_alat', '$id_kategori', '$deskripsi', '$stok')";
    if (mysqli_query($koneksi, $query)) {
        catat_log($koneksi, $_SESSION['id_user'], "Menambahkan alat baru: $nama_alat (Stok: $stok)");
        $_SESSION['pesan_sukses'] = "Data alat berhasil ditambahkan!";
        header("Location: data_alat.php");
        exit();
    } else {
        $pesan_error = "Gagal menambah alat: " . mysqli_error($koneksi);
    }
}

if (isset($_POST['edit_alat'])) {
    $id_alat     = $_POST['id_alat'];
    $nama_alat   = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $id_kategori = $_POST['id_kategori'];
    $deskripsi   = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $stok        = (int)$_POST['stok'];

    $query = "UPDATE alat SET 
                nama_alat = '$nama_alat', 
                id_kategori = '$id_kategori', 
                deskripsi = '$deskripsi', 
                stok = '$stok' 
              WHERE id_alat = '$id_alat'";
    if (mysqli_query($koneksi, $query)) {
        catat_log($koneksi, $_SESSION['id_user'], "Mengubah data alat: $nama_alat");
        $_SESSION['pesan_sukses'] = "Data alat berhasil diperbarui!";
        header("Location: data_alat.php");
        exit();
    } else {
        $pesan_error = "Gagal memperbarui alat: " . mysqli_error($koneksi);
    }
}

if (isset($_GET['hapus_alat'])) {
    $id_hapus = $_GET['hapus_alat'];
    $q_a = mysqli_query($koneksi, "SELECT nama_alat FROM alat WHERE id_alat = '$id_hapus'");
    $d_a = mysqli_fetch_assoc($q_a);
    $namaalat = $d_a['nama_alat'] ?? "ID $id_hapus";

    $query = "DELETE FROM alat WHERE id_alat = '$id_hapus'";
    if (mysqli_query($koneksi, $query)) {
        catat_log($koneksi, $_SESSION['id_user'], "Menghapus alat: $namaalat");
        $_SESSION['pesan_sukses'] = "Data alat berhasil dihapus!";
        header("Location: data_alat.php");
        exit();
    } else {
        $pesan_error = "Gagal menghapus alat: " . mysqli_error($koneksi);
    }
}
?>

<!-- Alert Notifikasi -->
<?php if (isset($_SESSION['pesan_sukses'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <?= $_SESSION['pesan_sukses']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['pesan_sukses']); ?>
<?php endif; ?>

<?php if (isset($pesan_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <?= $pesan_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Nav Tabs -->
<ul class="nav nav-tabs mb-3" id="alatTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="alat-tab" data-bs-toggle="tab" data-bs-target="#alat" type="button" role="tab" style="color: #622B14;">
            📦 Data Alat
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="kategori-tab" data-bs-toggle="tab" data-bs-target="#kategori" type="button" role="tab" style="color: #622B14;">
            🏷️ Kelola Kategori
        </button>
    </li>
</ul>

<div class="tab-content" id="alatTabContent">
    
    <!-- TAB 1: DATA ALAT -->
    <div class="tab-pane fade show active" id="alat" role="tabpanel">
        <div class="card card-custom shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3" style="border-bottom: 2px solid #978F66;">
                <h5 class="mb-0 fw-bold" style="color: #622B14;">Daftar Alat</h5>
                <button class="btn btn-custom btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahAlat">
                    + Tambah Alat Baru
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead style="background-color: #995F2F; color: #ffffff;">
                            <tr>
                                <th class="text-center" width="50">No</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center" width="100">Stok</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_alat = 1;
                            $query_alat = mysqli_query($koneksi, "
                                SELECT alat.*, kategori.nama_kategori 
                                FROM alat 
                                LEFT JOIN kategori ON alat.id_kategori = kategori.id_kategori 
                                ORDER BY alat.id_alat DESC
                            ");
                            if (mysqli_num_rows($query_alat) > 0):
                                while ($a = mysqli_fetch_assoc($query_alat)):
                            ?>
                            <tr>
                                <td class="text-center"><?= $no_alat++; ?></td>
                                <td><b><?= htmlspecialchars($a['nama_alat']); ?></b></td>
                                <td><span class="badge" style="background-color: #978F66;"><?= htmlspecialchars($a['nama_kategori'] ?? 'Tanpa Kategori'); ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars($a['deskripsi']); ?></small></td>
                                <td class="text-center">
                                    <span class="badge <?= $a['stok'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?= $a['stok']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditAlat<?= $a['id_alat']; ?>">Edit</button>
                                    <a href="data_alat.php?hapus_alat=<?= $a['id_alat']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus alat ini?')">Hapus</a>
                                </td>
                            </tr>

                            <!-- MODAL EDIT ALAT -->
                            <div class="modal fade" id="modalEditAlat<?= $a['id_alat']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="data_alat.php" method="POST">
                                            <div class="modal-header" style="background-color: #622B14; color: white;">
                                                <h5 class="modal-title">Edit Data Alat</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_alat" value="<?= $a['id_alat']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Alat</label>
                                                    <input type="text" name="nama_alat" class="form-control" value="<?= htmlspecialchars($a['nama_alat']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Kategori</label>
                                                    <select name="id_kategori" class="form-select" required>
                                                        <?php 
                                                        $kat_edit = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
                                                        while ($k = mysqli_fetch_assoc($kat_edit)):
                                                        ?>
                                                            <option value="<?= $k['id_kategori']; ?>" <?= $k['id_kategori'] == $a['id_kategori'] ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($k['nama_kategori']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Deskripsi</label>
                                                    <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($a['deskripsi']); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Stok Barang</label>
                                                    <input type="number" name="stok" class="form-control" value="<?= $a['stok']; ?>" min="0" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_alat" class="btn btn-custom">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data alat. Silakan tambah alat baru.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: KELOLA KATEGORI -->
    <div class="tab-pane fade" id="kategori" role="tabpanel">
        <div class="card card-custom shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3" style="border-bottom: 2px solid #978F66;">
                <h5 class="mb-0 fw-bold" style="color: #622B14;">Daftar Kategori Alat</h5>
                <button class="btn btn-custom btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                    + Tambah Kategori
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead style="background-color: #995F2F; color: #ffffff;">
                            <tr>
                                <th class="text-center" width="60">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-center" width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_kat = 1;
                            $query_kat = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC");
                            if (mysqli_num_rows($query_kat) > 0):
                                while ($k = mysqli_fetch_assoc($query_kat)):
                            ?>
                            <tr>
                                <td class="text-center"><?= $no_kat++; ?></td>
                                <td><b><?= htmlspecialchars($k['nama_kategori']); ?></b></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditKategori<?= $k['id_kategori']; ?>">Edit</button>
                                    <a href="data_alat.php?hapus_kategori=<?= $k['id_kategori']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini?')">Hapus</a>
                                </td>
                            </tr>

                            <!-- MODAL EDIT KATEGORI -->
                            <div class="modal fade" id="modalEditKategori<?= $k['id_kategori']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="data_alat.php" method="POST">
                                            <div class="modal-header" style="background-color: #622B14; color: white;">
                                                <h5 class="modal-title">Edit Kategori</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_kategori" value="<?= $k['id_kategori']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Kategori</label>
                                                    <input type="text" name="nama_kategori" class="form-control" value="<?= htmlspecialchars($k['nama_kategori']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_kategori" class="btn btn-custom">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Belum ada data kategori.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH ALAT -->
<div class="modal fade" id="modalTambahAlat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="data_alat.php" method="POST">
                <div class="modal-header" style="background-color: #622B14; color: white;">
                    <h5 class="modal-title">+ Tambah Alat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Alat</label>
                        <input type="text" name="nama_alat" class="form-control" placeholder="Misal: Proyektor EPSON" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php 
                            $kat_list = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
                            while ($kl = mysqli_fetch_assoc($kat_list)):
                            ?>
                                <option value="<?= $kl['id_kategori']; ?>"><?= htmlspecialchars($kl['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Kondisi atau spesifikasi singkat"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" name="stok" class="form-control" min="0" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_alat" class="btn btn-custom">Simpan Alat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH KATEGORI -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="data_alat.php" method="POST">
                <div class="modal-header" style="background-color: #622B14; color: white;">
                    <h5 class="modal-title">+ Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Misal: Elektronik / Pertanahan / Perbengkelan" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kategori" class="btn btn-custom">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>