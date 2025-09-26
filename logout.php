<?php
session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login utama
header("Location: ../login.php");
exit();
?>
