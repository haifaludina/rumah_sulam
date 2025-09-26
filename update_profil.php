<?php
session_start();
require_once '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: ../login.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];

// Ambil data dari form
$nama = htmlspecialchars(trim($_POST['nama']));
$email = htmlspecialchars(trim($_POST['email']));
$telepon = htmlspecialchars(trim($_POST['telepon']));

// Validasi sederhana
if (empty($nama) || empty($email)) {
    header('Location: profil.php?error=empty_fields');
    exit();
}

// Update data profil (tanpa alamat)
$query = "UPDATE pelanggan SET nama_pelanggan = ?, email = ?, no_hp = ? WHERE id_pelanggan = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("sssi", $nama, $email, $telepon, $id_pelanggan);

if ($stmt->execute()) {
    header('Location: profil.php?success=updated');
} else {
    header('Location: profil.php?error=update_failed');
}
exit();
?>
