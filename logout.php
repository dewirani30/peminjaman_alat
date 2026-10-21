<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: index.php?pesan=logout");
exit();
?>