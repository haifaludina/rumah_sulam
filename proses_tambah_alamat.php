<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelanggan = $_SESSION['id_pelanggan'];
    $alamat_baru = trim($_POST['alamat']);

    if (!empty($alamat_baru)) {
        $stmt = $conn->prepare("UPDATE pelanggan SET alamat = ? WHERE id_pelanggan = ?");
        $stmt->bind_param("si", $alamat_baru, $id_pelanggan);

        if ($stmt->execute()) {
            header("Location: alamat.php?status=sukses");
            exit;
        } else {
            header("Location: alamat.php?status=gagal");
            exit;
        }
    } else {
        header("Location: alamat.php?status=kosong");
        exit;
    }
} else {
    header("Location: alamat.php");
    exit;
}
?>
