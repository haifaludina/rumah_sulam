<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Cek session dan level admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = "Anda harus login sebagai admin";
    header("Location: kelola_user.php");
    exit();
}

require_once '../koneksi.php';

// Ambil ID user dari parameter GET dan pastikan itu integer
$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID user
if ($id_user <= 0) {
    $_SESSION['error_message'] = "ID user tidak valid";
    header("Location: kelola_user.php");
    exit();
}

// Cek apakah user mencoba menghapus dirinya sendiri
if ($id_user == $_SESSION['id_user']) {
    $_SESSION['error_message'] = "Anda tidak dapat menghapus akun sendiri";
    header("Location: kelola_user.php");
    exit();
}

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {
    // 1. Dapatkan data user untuk mengetahui role dan id terkait
    $stmt = mysqli_prepare($koneksi, "SELECT id_admin, id_pelanggan, role FROM user WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt); // Get the result set
    $user = mysqli_fetch_assoc($result); // Fetch from the result set, not the statement
    
    if (!$user) {
        throw new Exception("User dengan ID tersebut tidak ditemukan");
    }
    
    // 2. Hapus dari tabel sesuai role
    if ($user['role'] == 'admin' && $user['id_admin']) {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM admin WHERE id_admin = ?");
        mysqli_stmt_bind_param($stmt, "i", $user['id_admin']);
        mysqli_stmt_execute($stmt);
    } 
    elseif ($user['role'] == 'pelanggan' && $user['id_pelanggan']) {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM pelanggan WHERE id_pelanggan = ?");
        mysqli_stmt_bind_param($stmt, "i", $user['id_pelanggan']);
        mysqli_stmt_execute($stmt);
    }
    
    // 3. Hapus dari tabel user
    $stmt = mysqli_prepare($koneksi, "DELETE FROM user WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus user: " . mysqli_error($koneksi));
    }
    
    // Commit transaksi jika semua berhasil
    mysqli_commit($koneksi);
    $_SESSION['success_message'] = "User berhasil dihapus";
} catch (Exception $e) {
    // Rollback transaksi jika ada error
    mysqli_rollback($koneksi);
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect kembali ke halaman kelola user
header("Location: kelola_user.php");
exit();
?>