<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}

include '../koneksi.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (!isset($_POST['id_pesanan']) || empty($_POST['id_pesanan'])) {
        $_SESSION['error'] = "ID pesanan tidak valid";
        header("Location: kelola_pesanan.php");
        exit();
    }
    
    if (!isset($_POST['status']) || empty($_POST['status'])) {
        $_SESSION['error'] = "Status harus dipilih";
        header("Location: kelola_pesanan.php");
        exit();
    }
    
    // Get data from form
    $id_pesanan = mysqli_real_escape_string($koneksi, $_POST['id_pesanan']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($koneksi, $_POST['catatan']) : '';
    $no_resi = isset($_POST['no_resi']) ? mysqli_real_escape_string($koneksi, $_POST['no_resi']) : '';
    $nama_kurir = isset($_POST['nama_kurir']) ? mysqli_real_escape_string($koneksi, $_POST['nama_kurir']) : '';
    $telepon_kurir = isset($_POST['telepon_kurir']) ? mysqli_real_escape_string($koneksi, $_POST['telepon_kurir']) : '';
    
    // Start transaction for data consistency
    mysqli_begin_transaction($koneksi);
    
    try {
        // Update order status
        $query = "UPDATE pesanan SET 
                    status = '$status',
                    catatan = '$catatan'";
        
        // Add tracking number and courier info if status is shipped
        if ($status == 'Dikirim') {
            if (!empty($no_resi)) {
                $query .= ", no_resi = '$no_resi'";
            }
            
            if (!empty($nama_kurir)) {
                $query .= ", nama_kurir = '$nama_kurir'";
            }
            
            if (!empty($telepon_kurir)) {
                $query .= ", telepon_kurir = '$telepon_kurir'";
            }
            
            // Also update the pengiriman table if it exists
            $check_pengiriman = "SELECT * FROM pengiriman WHERE id_pesanan = '$id_pesanan'";
            $result_check = mysqli_query($koneksi, $check_pengiriman);
            
            if (mysqli_num_rows($result_check) > 0) {
                // Update existing pengiriman record
                $update_pengiriman = "UPDATE pengiriman SET 
                                      no_resi = '$no_resi',
                                      nama_kurir = '$nama_kurir',
                                      telepon_kurir = '$telepon_kurir',
                                      tanggal_kirim = CURRENT_DATE 
                                      WHERE id_pesanan = '$id_pesanan'";
                mysqli_query($koneksi, $update_pengiriman);
            } else {
                // Create new pengiriman record
                $jasa_pengiriman = isset($_POST['jasa_pengiriman']) ? 
                                  mysqli_real_escape_string($koneksi, $_POST['jasa_pengiriman']) : 'Regular';
                
                $insert_pengiriman = "INSERT INTO pengiriman 
                                     (id_pesanan, jasa_pengiriman, no_resi, nama_kurir, telepon_kurir, tanggal_kirim) 
                                     VALUES ('$id_pesanan', '$jasa_pengiriman', '$no_resi', '$nama_kurir', '$telepon_kurir', CURRENT_DATE)";
                mysqli_query($koneksi, $insert_pengiriman);
            }
        } else {
            // Clear courier info if status is not "Dikirim"
            $query .= ", no_resi = NULL, nama_kurir = NULL, telepon_kurir = NULL";
        }
        
        $query .= ", tanggal_update = NOW() WHERE id_pesanan = '$id_pesanan'";
        
        $result = mysqli_query($koneksi, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($koneksi));
        }
        
        // Log the status change
        $username = $_SESSION['username'];
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address) 
                      VALUES ('$username', 'Mengubah status pesanan #$id_pesanan menjadi $status', '{$_SERVER['REMOTE_ADDR']}')";
        mysqli_query($koneksi, $log_query);
        
        // Commit transaction
        mysqli_commit($koneksi);
        
        $_SESSION['success'] = "Status pesanan berhasil diperbarui";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($koneksi);
        $_SESSION['error'] = "Gagal memperbarui status pesanan: " . $e->getMessage();
    }
    
    header("Location: kelola_pesanan.php");
    exit();
} else {
    // If not through POST form, redirect to order management page
    header("Location: kelola_pesanan.php");
    exit();
}
?>