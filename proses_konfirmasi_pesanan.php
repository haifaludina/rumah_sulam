<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    $id_pelanggan = $_SESSION['id_pelanggan'];

    // Check if order exists and belongs to customer
    $cek = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND id_pelanggan = ?");
    $cek->bind_param("ii", $id_pesanan, $id_pelanggan);
    $cek->execute();
    $result = $cek->get_result();
    
    if ($result->num_rows > 0) {
        $pesanan = $result->fetch_assoc();
        
        // Check if there's an active return request
        $retur_query = "SELECT * FROM retur WHERE id_pesanan = ? AND (status = 'Menunggu Konfirmasi' OR status = 'Diterima')";
        $retur_stmt = $koneksi->prepare($retur_query);
        $retur_stmt->bind_param("i", $id_pesanan);
        $retur_stmt->execute();
        $retur_result = $retur_stmt->get_result();
        
        if ($retur_result->num_rows > 0) {
            // If there's an active return request, don't change status to 'selesai'
            $_SESSION['error'] = "Tidak dapat mengkonfirmasi pesanan karena ada permintaan retur yang aktif";
        } else {
            // Only update status if current status is 'dikirim'
            if (strtolower($pesanan['status']) == 'dikirim') {
                $update = $koneksi->prepare("UPDATE pesanan SET status = 'Diterima', tanggal_update = NOW() WHERE id_pesanan = ?");
                $update->bind_param("i", $id_pesanan);
                $update->execute();
                $_SESSION['success'] = "Pesanan berhasil dikonfirmasi sebagai diterima";
            } else {
                $_SESSION['error'] = "Status pesanan tidak valid untuk dikonfirmasi";
            }
        }
    }
}

header("Location: detail_pesanan.php?id=" . $id_pesanan);
exit();
?>