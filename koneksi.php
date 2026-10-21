<?php
$koneksi = mysqli_connect("localhost", "root", "", "peminjaman_alat");

// Atur zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');


if (mysqli_connect_errno()){
    echo "Koneksi gagal tersambung: " . mysqli_connect_error();
}

// Fungsi global untuk mencatat seluruh log aktivitas sistem
function catat_log($koneksi, $id_user, $aktivitas) {
    $id_user   = (int)$id_user;
    $aktivitas = mysqli_real_escape_string($koneksi, $aktivitas);
    $waktu     = date('Y-m-d H:i:s');

    $query = "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) 
              VALUES ('$id_user', '$aktivitas', '$waktu')";
    
    mysqli_query($koneksi, $query);
}
?>