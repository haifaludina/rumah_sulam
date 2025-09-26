<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

include_once '../koneksi.php';

$debug = true;

function debug_log($message) {
    global $debug;
    if ($debug) {
        error_log($message);
    }
}

if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    debug_log("Database connection not established properly");
    
    $db_host = "localhost"; 
    $db_user = "root";
    $db_pass = "";
    $db_name = "rumah_sulam"; 
    
    $koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($koneksi->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $koneksi->connect_error]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit;
}

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

if (!isset($_POST['id_retur']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

$id_retur = mysqli_real_escape_string($koneksi, $_POST['id_retur']);
$status = mysqli_real_escape_string($koneksi, $_POST['status']);
$refund_data = null;

debug_log("Memproses update status retur: ID=$id_retur, Status=$status");

if (isset($_POST['additional_data']) && $_POST['additional_data'] !== 'null') {
    $additional_data = json_decode($_POST['additional_data'], true);
    debug_log("Additional data: " . print_r($additional_data, true));
    
    if ($status === 'Ditolak') {
        $alasan_penolakan = mysqli_real_escape_string($koneksi, $additional_data['alasan_penolakan'] ?? '');
    } elseif ($status === 'Diterima') {
        $refund_data_json = mysqli_real_escape_string($koneksi, json_encode($additional_data));
        
        if ($additional_data['method'] === 'pengembalian_barang') {
            $nomor_resi = mysqli_real_escape_string($koneksi, $additional_data['resi_pengganti'] ?? '');
            $catatan_refund = mysqli_real_escape_string($koneksi, $additional_data['note'] ?? '');
        } else {
            $jumlah_refund = floatval($additional_data['amount'] ?? 0);
            $metode_refund = mysqli_real_escape_string($koneksi, $additional_data['method'] ?? 'transfer_bank');
            $catatan_refund = mysqli_real_escape_string($koneksi, $additional_data['note'] ?? '');
        }
    }
} else {
    $refund_data_json = null;
    debug_log("Tidak ada data tambahan");
}

mysqli_begin_transaction($koneksi);

try {
    $update_query = "UPDATE retur SET status = ?";
    $params = [$status];
    $types = "s";
    
    if ($status === 'Ditolak' && isset($alasan_penolakan)) {
        $update_query .= ", alasan_penolakan = ?";
        $params[] = $alasan_penolakan;
        $types .= "s";
    }
    
    if ($status === 'Diterima' && isset($refund_data_json)) {
        $update_query .= ", refund_data = ?";
        $params[] = $refund_data_json;
        $types .= "s";
        
        if (isset($jumlah_refund)) {
            $update_query .= ", jumlah_refund = ?";
            $params[] = $jumlah_refund;
            $types .= "d";
        }
        
        if (isset($metode_refund)) {
            $update_query .= ", metode_refund = ?";
            $params[] = $metode_refund;
            $types .= "s";
        }
        
        if (isset($catatan_refund)) {
            $update_query .= ", catatan_refund = ?";
            $params[] = $catatan_refund;
            $types .= "s";
        }
        
        if (isset($nomor_resi)) {
            $update_query .= ", nomor_resi_pengembalian = ?";
            $params[] = $nomor_resi;
            $types .= "s";
        }
        
        $update_query .= ", tanggal_refund = NOW()";
    }
    
    $update_query .= " WHERE id_retur = ?";
    $params[] = $id_retur;
    $types .= "i";
    
    debug_log("SQL Query: $update_query");
    debug_log("Params: " . print_r($params, true));
    
    $stmt = mysqli_prepare($koneksi, $update_query);
    
    if ($stmt === false) {
        throw new Exception("Error preparing statement: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        throw new Exception("Gagal memperbarui status: " . mysqli_error($koneksi));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if ($affected_rows === 0) {
        $check_query = "SELECT id_retur FROM retur WHERE id_retur = ?";
        $check_stmt = mysqli_prepare($koneksi, $check_query);
        
        if ($check_stmt === false) {
            throw new Exception("Error preparing check statement: " . mysqli_error($koneksi));
        }
        
        mysqli_stmt_bind_param($check_stmt, "i", $id_retur);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) === 0) {
            mysqli_stmt_close($check_stmt);
            throw new Exception("ID Retur tidak ditemukan");
        }
        
        debug_log("Tidak ada perubahan status");
        mysqli_stmt_close($check_stmt);
    }
    
    // Log aktivitas
    $admin = $_SESSION['username'];
    $activity = "Admin $admin mengubah status retur #$id_retur menjadi $status";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $log_query = "INSERT INTO log_aktivitas (username, aktivitas, tanggal, ip_address) VALUES (?, ?, NOW(), ?)";
    $log_stmt = mysqli_prepare($koneksi, $log_query);
    
    if ($log_stmt === false) {
        throw new Exception("Error preparing log statement: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($log_stmt, "sss", $admin, $activity, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    mysqli_commit($koneksi);
    
    $_SESSION['retur_message'] = "Status retur #$id_retur berhasil diperbarui menjadi \"$status\"";
    $_SESSION['retur_message_type'] = "success";
    
    // Ambil id_pesanan dari id_retur
    $getPesanan = $koneksi->prepare("SELECT id_pesanan FROM retur WHERE id_retur = ?");
    $getPesanan->bind_param("i", $id_retur);
    $getPesanan->execute();
    $getPesanan->bind_result($id_pesanan);
    $getPesanan->fetch();
    $getPesanan->close();

    // Update status pesanan jika retur diterima
    if ($status === 'Diterima') {
        $updatePesanan = $koneksi->prepare("UPDATE pesanan SET status = 'selesai' WHERE id_pesanan = ?");
        $updatePesanan->bind_param("i", $id_pesanan);
        $updatePesanan->execute();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Status retur berhasil diperbarui",
        'data' => $additional_data ?? null
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    debug_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}