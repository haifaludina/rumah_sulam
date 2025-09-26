<?php
// Start session (if not already started)
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include '../koneksi.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'ID pesanan tidak diberikan']);
    exit();
}

$id_pesanan = mysqli_real_escape_string($koneksi, $_GET['id']);

// Get pesanan and related data with improved joins
$query = "SELECT p.*, pel.nama_pelanggan, pel.alamat, pel.no_hp, pel.email
          FROM pesanan p 
          LEFT JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
          WHERE p.id_pesanan = '$id_pesanan'";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    echo json_encode(['error' => 'Database error: ' . mysqli_error($koneksi)]);
    exit();
}

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['error' => 'Pesanan tidak ditemukan']);
    exit();
}

$pesanan = mysqli_fetch_assoc($result);

// Get detailed alamat from alamat_pelanggan if id_alamat is present
$alamat_lengkap = $pesanan['alamat'];
if (!empty($pesanan['id_alamat']) && $pesanan['id_alamat'] > 0) {
    $query_alamat = "SELECT * FROM alamat_pelanggan WHERE id_alamat = '{$pesanan['id_alamat']}'";
    $result_alamat = mysqli_query($koneksi, $query_alamat);
    if ($result_alamat && mysqli_num_rows($result_alamat) > 0) {
        $alamat_data = mysqli_fetch_assoc($result_alamat);
        $alamat_lengkap = $alamat_data['alamat_lengkap'] . ", " . 
                        $alamat_data['kecamatan'] . ", " . 
                        $alamat_data['kota'] . ", " . 
                        $alamat_data['provinsi'] . ", " . 
                        $alamat_data['kode_pos'];
    }
}

// Get produk details of this pesanan - check for both regular and custom products
// First check for regular products
$query_detail = "SELECT dp.*, p.nama_produk 
                FROM detail_pesanan dp
                JOIN produk p ON dp.id_produk = p.id_produk
                WHERE dp.id_pesanan = '$id_pesanan'";

$result_detail = mysqli_query($koneksi, $query_detail);

$produk_regular = [];
if ($result_detail && mysqli_num_rows($result_detail) > 0) {
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $produk_regular[] = $row;
    }
}

// Check for item_pesanan table as alternative (seems the app uses two different tables)
$query_item = "SELECT ip.*, p.nama_produk 
              FROM item_pesanan ip
              JOIN produk p ON ip.id_produk = p.id_produk
              WHERE ip.id_pesanan = '$id_pesanan'";

$result_item = mysqli_query($koneksi, $query_item);

if ($result_item && mysqli_num_rows($result_item) > 0) {
    while ($row = mysqli_fetch_assoc($result_item)) {
        $produk_regular[] = $row;
    }
}

// Check for custom products
$query_custom = "SELECT * FROM detail_pesanan_kustom WHERE id_pesanan = '$id_pesanan'";
$result_custom = mysqli_query($koneksi, $query_custom);

$produk_custom = [];
if ($result_custom && mysqli_num_rows($result_custom) > 0) {
    while ($row = mysqli_fetch_assoc($result_custom)) {
        $produk_custom[] = $row;
    }
}

// Prepare the response
$response = [
    'id_pesanan' => $pesanan['id_pesanan'],
    'nama_pelanggan' => $pesanan['nama_pelanggan'] ?? 'Tidak tersedia',
    'tanggal' => $pesanan['tanggal_pesan'],
    'alamat' => $alamat_lengkap ?? $pesanan['alamat'] ?? 'Tidak tersedia',
    'status' => $pesanan['status'],
    'catatan' => $pesanan['catatan'] ?? '',
    'no_resi' => $pesanan['no_resi'] ?? '',
    'nama_kurir' => $pesanan['nama_kurir'] ?? '',
'telepon_kurir' => $pesanan['telepon_kurir'] ?? '',
    'bukti_pembayaran' => $pesanan['bukti_pembayaran'] ?? '',
    'total_harga' => $pesanan['total_harga'] ?? 0,
    'produk_regular' => $produk_regular,
    'produk_custom' => $produk_custom
];

echo json_encode($response);