<?php
session_start();
// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}

include '../koneksi.php';

// Cek apakah ID pesanan disediakan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'ID pesanan tidak ditemukan']);
    exit();
}

$id_pesanan = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil status pesanan
$query = "SELECT status, catatan, no_resi FROM pesanan WHERE id_pesanan = '$id_pesanan'";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    echo json_encode(['error' => 'Query error: ' . mysqli_error($koneksi)]);
    exit();
}

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['error' => 'Pesanan tidak ditemukan']);
    exit();
}

$data = mysqli_fetch_assoc($result);

// Kirim response sebagai JSON
header('Content-Type: application/json');
echo json_encode($data);
?>