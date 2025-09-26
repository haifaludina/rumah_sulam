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

// Check if there's a return request
$retur_query = "SELECT * FROM retur WHERE id_pesanan = ?";
$retur_stmt = $koneksi->prepare($retur_query);
$retur_stmt->bind_param("i", $id_pesanan);
$retur_stmt->execute();
$retur_result = $retur_stmt->get_result();

if ($retur_result->num_rows > 0) {
    $retur = $retur_result->fetch_assoc();
    
    // If return is rejected, mark order as completed
    if ($retur['status'] === 'Ditolak') {
        $update_query = "UPDATE pesanan SET status = 'Selesai', tanggal_update = NOW() WHERE id_pesanan = ?";
        $update_stmt = $koneksi->prepare($update_query);
        $update_stmt->bind_param("i", $id_pesanan);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Pesanan berhasil diselesaikan";
        } else {
            $_SESSION['error'] = "Gagal mengupdate status pesanan";
        }
    } else {
        $_SESSION['error'] = "Tidak dapat menyelesaikan pesanan karena ada retur yang aktif";
    }
} else {
    // If no return request, only allow status change from "Diterima" to "Selesai"
    if (strtolower($pesanan['status']) == 'diterima') {
        $update_query = "UPDATE pesanan SET status = 'Selesai', tanggal_update = NOW() WHERE id_pesanan = ?";
        $update_stmt = $koneksi->prepare($update_query);
        $update_stmt->bind_param("i", $id_pesanan);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Pesanan berhasil diselesaikan";
        } else {
            $_SESSION['error'] = "Gagal mengupdate status pesanan";
        }
    } else {
        $_SESSION['error'] = "Status pesanan tidak valid untuk diselesaikan";
    }
}

header('Location: detail_pesanan.php?id=' . $id_pesanan);
exit();
?>