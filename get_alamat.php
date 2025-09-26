<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID alamat tidak valid']);
    exit();
}

$id_alamat = $_GET['id'];
$id_pelanggan = $_SESSION['id_pelanggan'];

$query = $koneksi->prepare("SELECT * FROM alamat_pelanggan WHERE id_alamat = ? AND id_pelanggan = ?");
$query->bind_param("ii", $id_alamat, $id_pelanggan);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $alamat = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'id_alamat' => $alamat['id_alamat'],
        'nama_penerima' => $alamat['nama_penerima'],
        'no_hp' => $alamat['no_hp'],
        'provinsi' => $alamat['provinsi'],
        'kota' => $alamat['kota'],
        'kecamatan' => $alamat['kecamatan'],
        'kode_pos' => $alamat['kode_pos'],
        'alamat_lengkap' => $alamat['alamat_lengkap'],
        'is_utama' => $alamat['is_utama']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Alamat tidak ditemukan']);
}
?>