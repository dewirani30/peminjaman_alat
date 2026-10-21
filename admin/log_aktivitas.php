<?php 
include '../koneksi.php';
include '../includes/header.php'; 

// Tanggal hari ini sebagai nilai default
$today = date('Y-m-d');

// Ambil tanggal dari URL, jika tidak ada default ke hari ini
$tgl_mulai   = $_GET['tgl_mulai'] ?? $today;
$tgl_selesai = $_GET['tgl_selesai'] ?? $today;

// Sanitasi untuk query
$tgl_m = mysqli_real_escape_string($koneksi, $tgl_mulai);
$tgl_s = mysqli_real_escape_string($koneksi, $tgl_selesai);

$where_clause = " WHERE DATE(log_aktivitas.waktu) BETWEEN '$tgl_m' AND '$tgl_s' ";

// Cek apakah sedang memfilter tanggal yang berbeda dari hari ini
$is_filtered = ($tgl_mulai != $today || $tgl_selesai != $today);
?>

<!-- Card Log Aktivitas -->
<div class="card card-custom shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3" style="border-bottom: 2px solid #978F66;">
        <h5 class="mb-0 fw-bold" style="color: #622B14;">📜 Riwayat Log Aktivitas</h5>
        
        <!-- Form Filter Tanggal -->
        <form action="log_aktivitas.php" method="GET" class="d-flex align-items-center gap-2">
            <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai); ?>" required>
            <span class="text-muted">s/d</span>
            <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai); ?>" required>
            <button type="submit" class="btn btn-custom btn-sm">Filter</button>
            
            <?php if ($is_filtered): ?>
                <a href="log_aktivitas.php" class="btn btn-outline-secondary btn-sm" title="Kembali ke Hari Ini">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead style="background-color: #995F2F; color: #ffffff;">
                    <tr>
                        <th class="text-center" width="60">No</th>
                        <th width="220">Pengguna</th>
                        <th>Aktivitas</th>
                        <th width="200" class="text-center">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $query_log = "SELECT log_aktivitas.*, users.nama_lengkap, users.role 
                                  FROM log_aktivitas 
                                  LEFT JOIN users ON log_aktivitas.id_user = users.id_user 
                                  $where_clause
                                  ORDER BY log_aktivitas.waktu DESC";
                    
                    $result_log = mysqli_query($koneksi, $query_log);

                    if ($result_log && mysqli_num_rows($result_log) > 0) {
                        while ($row = mysqli_fetch_assoc($result_log)) {
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td>
                                <b><?= htmlspecialchars($row['nama_lengkap'] ?? 'Sistem'); ?></b><br>
                                <small class="text-muted">(Role: <?= ucfirst($row['role'] ?? 'System'); ?>)</small>
                            </td>
                            <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                            <td class="text-center">
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i:s', strtotime($row['waktu'])); ?> WIB
                                </small>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i>Belum ada log aktivitas yang tercatat untuk tanggal ini.</i>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>