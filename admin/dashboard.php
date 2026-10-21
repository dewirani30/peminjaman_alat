<?php 
// 1. Hubungkan ke database
include '../koneksi.php';

// 2. Panggil header & navbar
include '../includes/header.php'; 
?>

<!-- Card Banner Selamat Datang -->
<div class="card card-custom shadow-sm mb-4">
    <div class="card-body text-center py-4">
        <h2 class="mb-2" style="color: #622B14;">Selamat Datang di Panel Admin</h2>
        <p class="lead text-muted mb-0">
            Halo <b><?= $_SESSION['nama_lengkap']; ?></b>, Anda berhasil login sebagai Admin.<br>
            Silakan gunakan menu navigasi di atas untuk mengelola data sistem peminjaman.
        </p>
    </div>
</div>

<!-- Card Tabel Log Aktivitas (10 Terakhir) -->
<div class="card card-custom shadow-sm">
    <div class="card-header bg-white py-3" style="border-bottom: 2px solid #978F66;">
        <h5 class="mb-0 fw-bold" style="color: #622B14;">
            📋 Riwayat Log Aktivitas
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead style="background-color: #995F2F; color: #ffffff;">
                    <tr>
                        <th class="text-center" width="60">No</th>
                        <th width="200">Pengguna</th>
                        <th>Aktivitas</th>
                        <th width="180">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Query mengambil 10 aktivitas terbaru dan gabungkan dengan tabel users untuk mengambil nama
                    $query_log = "SELECT log_aktivitas.*, users.nama_lengkap 
                                  FROM log_aktivitas 
                                  LEFT JOIN users ON log_aktivitas.id_user = users.id_user 
                                  ORDER BY log_aktivitas.waktu DESC 
                                  LIMIT 10";
                    
                    $result_log = mysqli_query($koneksi, $query_log);

                    // Cek jika ada data log
                    if ($result_log && mysqli_num_rows($result_log) > 0) {
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result_log)) {
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><b><?= htmlspecialchars($row['nama_lengkap'] ?? 'Sistem'); ?></b></td>
                            <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($row['waktu'])); ?> WIB
                                </small>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i>Belum ada riwayat aktivitas tercatat.</i>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Panggil footer
include '../includes/footer.php'; 
?>