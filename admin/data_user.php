<?php 
include '../koneksi.php';
include '../includes/header.php'; 

// 1. PROSES TAMBAH USER
if (isset($_POST['tambah_user'])) {
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = $_POST['password'];
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $role         = $_POST['role'];

    $cek_username = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek_username) > 0) {
        $pesan_error = "Username sudah digunakan, cari username lain!";
    } else {
        $query_tambah = "INSERT INTO users (username, password, nama_lengkap, role) 
                        VALUES ('$username', SHA2('$password', 256), '$nama_lengkap', '$role')";
        if (mysqli_query($koneksi, $query_tambah)) {
            $pesan_sukses = "Data user berhasil ditambahkan!";
            catat_log($koneksi, $_SESSION['id_user'], "Menambahkan user baru: $username ($role)");
        } else {
            $pesan_error = "Gagal menambah user: " . mysqli_error($koneksi);
        }
    }
}

// 2. PROSES EDIT USER
if (isset($_POST['edit_user'])) {
    $id_user      = $_POST['id_user'];
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $role         = $_POST['role'];
    $password_baru= $_POST['password'];

    if (!empty($password_baru)) {
        $query_edit = "UPDATE users SET 
                        username = '$username', 
                        password = SHA2('$password_baru', 256), 
                        nama_lengkap = '$nama_lengkap', 
                        role = '$role' 
                       WHERE id_user = '$id_user'";
    } else {
        $query_edit = "UPDATE users SET 
                        username = '$username', 
                        nama_lengkap = '$nama_lengkap', 
                        role = '$role' 
                       WHERE id_user = '$id_user'";
    }

    if (mysqli_query($koneksi, $query_edit)) {
        $pesan_sukses = "Data user berhasil diperbarui!";
        catat_log($koneksi, $_SESSION['id_user'], "Mengubah data user: $username");
    } else {
        $pesan_error = "Gagal memperbarui user: " . mysqli_error($koneksi);
    }
}

// 3. PROSES HAPUS USER
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    if ($id_hapus == $_SESSION['id_user']) {
        $pesan_error = "Anda tidak dapat menghapus akun Anda sendiri!";
    } else {
        // Ambil nama user sebelum dihapus untuk dicatat di log
        $q_u = mysqli_query($koneksi, "SELECT username FROM users WHERE id_user = '$id_hapus'");
        $d_u = mysqli_fetch_assoc($q_u);
        $uname = $d_u['username'] ?? "ID $id_hapus";

        $query_hapus = "DELETE FROM users WHERE id_user = '$id_hapus'";
        if (mysqli_query($koneksi, $query_hapus)) {
            $pesan_sukses = "User berhasil dihapus!";
            catat_log($koneksi, $_SESSION['id_user'], "Menghapus user: $uname");
        } else {
            $pesan_error = "Gagal menghapus user: " . mysqli_error($koneksi);
        }
    }
}
?>

<!-- Alert Notifikasi -->
<?php if (isset($pesan_sukses)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <?= $pesan_sukses; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($pesan_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <?= $pesan_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Header Halaman & Tombol Tambah -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold" style="color: #622B14;">Data Pengguna (User)</h3>
    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
        + Tambah User Baru
    </button>
</div>

<!-- Tabel Data User -->
<div class="card card-custom shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead style="background-color: #995F2F; color: #ffffff;">
                    <tr>
                        <th class="text-center" width="60">No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th class="text-center" width="120">Role</th>
                        <th class="text-center" width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $query_users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id_user DESC");
                    while ($user = mysqli_fetch_assoc($query_users)):
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td><b><?= htmlspecialchars($user['username']); ?></b></td>
                        <td><?= htmlspecialchars($user['nama_lengkap']); ?></td>
                        <td class="text-center">
                            <?php 
                            if ($user['role'] == 'admin') echo '<span class="badge bg-danger">Admin</span>';
                            else if ($user['role'] == 'petugas') echo '<span class="badge bg-warning text-dark">Petugas</span>';
                            else echo '<span class="badge bg-info text-dark">Peminjam</span>';
                            ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $user['id_user']; ?>">Edit</button>
                            <a href="data_user.php?hapus=<?= $user['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                        </td>
                    </tr>

                    <!-- MODAL EDIT USER -->
                    <div class="modal fade" id="modalEdit<?= $user['id_user']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="data_user.php" method="POST">
                                    <div class="modal-header" style="background-color: #622B14; color: white;">
                                        <h5 class="modal-title">Edit Data User</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id_user" value="<?= $user['id_user']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password Baru <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select name="role" class="form-select" required>
                                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                <option value="petugas" <?= $user['role'] == 'petugas' ? 'selected' : ''; ?>>Petugas</option>
                                                <option value="peminjam" <?= $user['role'] == 'peminjam' ? 'selected' : ''; ?>>Peminjam</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="edit_user" class="btn btn-custom">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH USER -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="data_user.php" method="POST">
                <div class="modal-header" style="background-color: #622B14; color: white;">
                    <h5 class="modal-title">+ Tambah User Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin">Admin</option>
                            <option value="petugas">Petugas</option>
                            <option value="peminjam">Peminjam</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-custom">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>