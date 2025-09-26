<?php
// Tambahkan debugging untuk memeriksa lokasi file dan direktori
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tampilkan informasi direktori sebelum menghapus session (untuk debugging)
echo "<!-- Current directory: " . getcwd() . " -->";
echo "<!-- Document root: " . $_SERVER['DOCUMENT_ROOT'] . " -->";
echo "<!-- Script filename: " . $_SERVER['SCRIPT_FILENAME'] . " -->";

// Mulai session (harus selalu dijalankan sebelum berinteraksi dengan session)
session_start();

// Simpan status login untuk debugging
$was_logged_in = isset($_SESSION['username']) ? true : false;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'tidak ada';
echo "<!-- User sebelum logout: $username -->";

// Hapus semua data session
$_SESSION = array();

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Hancurkan session
session_destroy();

// Jalur relatif ke login.php (karena logout.php di dalam folder admin dan login.php di root)
$login_path = '../login.php';

// Pastikan file login.php benar-benar ada
if (!file_exists($login_path)) {
    echo "<!-- Warning: File $login_path tidak ditemukan -->";
    
    // Coba beberapa kemungkinan lokasi lain jika perlu
    if (file_exists($_SERVER['DOCUMENT_ROOT'].'/rumah_sulam/login.php')) {
        $login_path = '/rumah_sulam/login.php';
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'].'/login.php')) {
        $login_path = '/login.php';
    } else {
        // Log jika tidak menemukan file login.php
        error_log("Tidak dapat menemukan file login.php");
        $login_path = '../index.php'; // Default fallback jika login.php tidak ditemukan
    }
}

echo "<!-- Logout selesai. Redirect ke: $login_path -->";

// Redirect ke halaman login
header("Location: $login_path");
exit();
?>