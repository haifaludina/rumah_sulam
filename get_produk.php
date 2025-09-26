<?php
// Mulai sesi
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Koneksi database
include '../koneksi.php';

// Memastikan parameter id ada
if (isset($_GET['id'])) {
    $id_produk = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Query untuk mendapatkan data produk
    $query = "SELECT p.*, k.nama as nama_kategori, s.nama as nama_subkategori 
              FROM produk p 
              LEFT JOIN kategori k ON p.id_kategori = k.id 
              LEFT JOIN subkategori s ON p.id_subkategori = s.id 
              WHERE p.id_produk = '$id_produk'";
              
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $produk = mysqli_fetch_assoc($result);
        
        // Mengirim data sebagai JSON
        header('Content-Type: application/json');
        echo json_encode($produk);
    } else {
        // Produk tidak ditemukan
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Produk tidak ditemukan']);
    }
} else {
    // Parameter id tidak ditemukan
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Parameter id diperlukan']);
}
?>