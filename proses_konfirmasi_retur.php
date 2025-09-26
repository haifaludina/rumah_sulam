<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['id_pesanan'])) {
    header('Location: pesanan_saya.php');
    exit();
}

$id_pesanan = $_POST['id_pesanan'];
$id_pelanggan = $_SESSION['id_pelanggan'];

// Verify the order belongs to the customer
$query = "SELECT * FROM pesanan WHERE id_pesanan = ? AND id_pelanggan = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("ii", $id_pesanan, $id_pelanggan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Pesanan tidak ditemukan";
    header('Location: pesanan_saya.php');
    exit();
}

$pesanan = $result->fetch_assoc();

// Only allow status change from "Dikirim" to "Diterima"
if (strtolower($pesanan['status']) != 'dikirim') {
    $_SESSION['error'] = "Status pesanan tidak valid untuk dikonfirmasi";
    header('Location: detail_pesanan.php?id=' . $id_pesanan);
    exit();
}

// Update the order status
$update_query = "UPDATE pesanan SET status = 'Diterima', tanggal_update = NOW() WHERE id_pesanan = ?";
$update_stmt = $koneksi->prepare($update_query);
$update_stmt->bind_param("i", $id_pesanan);

if ($update_stmt->execute()) {
    $_SESSION['success'] = "Pesanan berhasil dikonfirmasi sebagai diterima";
} else {
    $_SESSION['error'] = "Gagal mengupdate status pesanan";
}

header('Location: detail_pesanan.php?id=' . $id_pesanan);
exit();
?>