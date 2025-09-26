<?php
session_start();

// Make sure there's no output before headers
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}

include '../koneksi.php';

// Set Content-Type header early to avoid header issues
header('Content-Type: application/json');

// Handle PHP errors better
try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID pesanan tidak ditemukan');
    }

    $id_pesanan = mysqli_real_escape_string($koneksi, $_GET['id']);

    // Check database connection
    if (mysqli_connect_errno()) {
        throw new Exception("Koneksi database gagal: " . mysqli_connect_error());
    }

    // Get order information
    $query_pesanan = "SELECT * FROM pesanan WHERE id_pesanan = '$id_pesanan'";
    $result_pesanan = mysqli_query($koneksi, $query_pesanan);

    if (!$result_pesanan) {
        throw new Exception('Query error: ' . mysqli_error($koneksi));
    }

    if (mysqli_num_rows($result_pesanan) == 0) {
        throw new Exception('Pesanan tidak ditemukan');
    }

    $pesanan = mysqli_fetch_assoc($result_pesanan);

    // Get customer information
    $id_pelanggan = $pesanan['id_pelanggan'];
    $pelanggan = [];

    if ($id_pelanggan) {
        $query_pelanggan = "SELECT * FROM pelanggan WHERE id_pelanggan = '$id_pelanggan'";
        $result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
        
        if ($result_pelanggan && mysqli_num_rows($result_pelanggan) > 0) {
            $pelanggan = mysqli_fetch_assoc($result_pelanggan);
        }
    }

    // Get shipping address if available
    $alamat = [];
    if (!empty($pesanan['id_alamat']) && $pesanan['id_alamat'] > 0) {
        $query_alamat = "SELECT * FROM alamat_pelanggan WHERE id_alamat = '{$pesanan['id_alamat']}'";
        $result_alamat = mysqli_query($koneksi, $query_alamat);
        
        if ($result_alamat && mysqli_num_rows($result_alamat) > 0) {
            $alamat = mysqli_fetch_assoc($result_alamat);
        }
    }

    // Get regular product details
    $produk_ready = [];
    
    // Try detail_pesanan table first
    $query_produk = "SELECT dp.*, p.nama_produk 
                    FROM detail_pesanan dp 
                    JOIN produk p ON dp.id_produk = p.id_produk 
                    WHERE dp.id_pesanan = '$id_pesanan'";
    $result_produk = mysqli_query($koneksi, $query_produk);

    if ($result_produk && mysqli_num_rows($result_produk) > 0) {
        while ($row = mysqli_fetch_assoc($result_produk)) {
            $produk_ready[] = $row;
        }
    } else {
        // If detail_pesanan failed, try item_pesanan table
        $query_item = "SELECT ip.*, p.nama_produk 
                      FROM item_pesanan ip
                      JOIN produk p ON ip.id_produk = p.id_produk
                      WHERE ip.id_pesanan = '$id_pesanan'";
        $result_item = mysqli_query($koneksi, $query_item);
        
        if ($result_item && mysqli_num_rows($result_item) > 0) {
            while ($row = mysqli_fetch_assoc($result_item)) {
                $produk_ready[] = $row;
            }
        }
    }

    // Get custom product details
    $produk_custom = [];
    $query_custom = "SELECT * FROM detail_pesanan_kustom WHERE id_pesanan = '$id_pesanan'";
    $result_custom = mysqli_query($koneksi, $query_custom);

    if ($result_custom && mysqli_num_rows($result_custom) > 0) {
        while ($row = mysqli_fetch_assoc($result_custom)) {
            $produk_custom[] = $row;
        }
    }

    // Get shipping info if available
    $pengiriman = [];
    $query_pengiriman = "SELECT * FROM pengiriman WHERE id_pesanan = '$id_pesanan'";
    $result_pengiriman = mysqli_query($koneksi, $query_pengiriman);
    
    if ($result_pengiriman && mysqli_num_rows($result_pengiriman) > 0) {
        $pengiriman = mysqli_fetch_assoc($result_pengiriman);
    }

    // Prepare response
    $response = [
        'pesanan' => $pesanan,
        'pelanggan' => $pelanggan,
        'alamat' => $alamat,
        'produk_ready' => $produk_ready,
        'produk_custom' => $produk_custom,
        'pengiriman' => $pengiriman
    ];

    // Output JSON
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Handle error and return as JSON
    echo json_encode(['error' => $e->getMessage()]);
}