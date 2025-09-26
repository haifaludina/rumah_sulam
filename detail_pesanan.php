<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: login.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: pesanan_saya.php');
    exit();
}

$id_pesanan = $_GET['id'];

// Query data pesanan
$query_pesanan = "SELECT * FROM pesanan WHERE id_pesanan = ? AND id_pelanggan = ?";
$stmt_pesanan = $koneksi->prepare($query_pesanan);
$stmt_pesanan->bind_param("ii", $id_pesanan, $id_pelanggan);
$stmt_pesanan->execute();
$result_pesanan = $stmt_pesanan->get_result();

if ($result_pesanan->num_rows === 0) {
    header('Location: pesanan_saya.php');
    exit();
}

$pesanan = $result_pesanan->fetch_assoc();

// Query item pesanan
$query_items = "SELECT ip.*, p.nama_produk, p.gambar FROM item_pesanan ip
                JOIN produk p ON ip.id_produk = p.id_produk 
                WHERE ip.id_pesanan = ?";
$stmt_items = $koneksi->prepare($query_items);
$stmt_items->bind_param("i", $id_pesanan);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$items = $result_items->fetch_all(MYSQLI_ASSOC);

// Query item kustom
$query_custom_items = "SELECT * FROM detail_pesanan_kustom WHERE id_pesanan = ?";
$stmt_custom_items = $koneksi->prepare($query_custom_items);
$stmt_custom_items->bind_param("i", $id_pesanan);
$stmt_custom_items->execute();
$result_custom_items = $stmt_custom_items->get_result();
$custom_items = $result_custom_items->fetch_all(MYSQLI_ASSOC);

$all_items = array_merge($items, $custom_items);

// Query data retur
$query_retur = "SELECT r.*, p.nama_pelanggan 
                FROM retur r
                JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
                WHERE r.id_pesanan = ?";
$stmt_retur = $koneksi->prepare($query_retur);
$stmt_retur->bind_param("i", $id_pesanan);
$stmt_retur->execute();
$result_retur = $stmt_retur->get_result();
$has_retur = $result_retur->num_rows > 0;
$retur = $has_retur ? $result_retur->fetch_assoc() : null;

// Decode data JSON jika ada
if ($has_retur && !empty($retur['item_retur'])) {
    $retur['item_retur'] = json_decode($retur['item_retur'], true);
}
if ($has_retur && !empty($retur['refund_data'])) {
    $retur['refund_data'] = json_decode($retur['refund_data'], true);
}

$status_order = [
    'menunggu' => 1,
    'diproses' => 2,
    'dikirim' => 3,
    'diterima' => 4,
    'selesai' => 5,
    'dibatalkan' => 0
];

$current_status = strtolower($pesanan['status']);
$current_step = isset($status_order[$current_status]) ? $status_order[$current_status] : 1;

$tanggal_pesan = isset($pesanan['tanggal_pesan']) ? $pesanan['tanggal_pesan'] : 
                 (isset($pesanan['tanggal_pesanan']) ? $pesanan['tanggal_pesanan'] : date('Y-m-d'));

// Query data pelanggan
$query_pelanggan = "SELECT * FROM pelanggan WHERE id_pelanggan = ?";
$stmt_pelanggan = $koneksi->prepare($query_pelanggan);
$stmt_pelanggan->bind_param("i", $id_pelanggan);
$stmt_pelanggan->execute();
$result_pelanggan = $stmt_pelanggan->get_result();
$pelanggan = $result_pelanggan->fetch_assoc();

// Query alamat pengiriman
$alamat_pengiriman = null;
if (isset($pesanan['id_alamat'])) {
    $query_alamat = "SELECT * FROM alamat_pelanggan WHERE id_alamat = ?";
    $stmt_alamat = $koneksi->prepare($query_alamat);
    $stmt_alamat->bind_param("i", $pesanan['id_alamat']);
    $stmt_alamat->execute();
    $result_alamat = $stmt_alamat->get_result();
    $alamat_pengiriman = $result_alamat->fetch_assoc();
}

$is_custom_order = count($custom_items) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $id_pesanan ?> - Rumah Sulam Sefni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6d4c41;
            --secondary-color: #8d6e63;
            --light-color: #f5f5f5;
            --dark-color: #333;
            --text-color: #555;
            --white-color: #fff;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196F3;
            --gray-color: #9e9e9e;
            --received-color: #20c997;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background-color: #f9f9f9;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 15px 50px;
            background-color: var(--white-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-container {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo {
            width: 40px;
            height: auto;
            margin-right: 10px;
        }

        .logo-text {
            font-weight: 600;
            font-size: 18px;
            color: var(--primary-color);
        }

        nav {
            display: flex;
            align-items: center;
        }

        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin: 0 15px;
        }

        nav ul li a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        nav ul li a:hover,
        nav ul li a.active {
            color: var(--primary-color);
            font-weight: bold;
        }

        .user-icons a {
            margin-left: 15px;
            font-size: 20px;
            color: var(--text-color);
            text-decoration: none;
        }

        .user-icons a:hover {
            color: var(--primary-color);
        }

        .profile-container {
            margin-top: 30px;
            margin-bottom: 50px;
        }

        .profile-sidebar {
            background: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            height: 100%;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--light-color);
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-name {
            text-align: center;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .profile-menu {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .profile-menu li {
            margin-bottom: 10px;
        }

        .profile-menu a {
            display: block;
            padding: 10px 15px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .profile-menu a:hover,
        .profile-menu a.active {
            background-color: rgba(109, 76, 65, 0.1);
            color: var(--primary-color);
        }

        .profile-menu i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }

        .profile-content {
            background: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .profile-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .status-badge {
            font-size: 14px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .bg-success {
            background-color: var(--success-color) !important;
        }

        .bg-warning {
            background-color: var(--warning-color) !important;
        }

        .bg-danger {
            background-color: var(--danger-color) !important;
        }

        .bg-info {
            background-color: var(--info-color) !important;
        }

        .bg-secondary {
            background-color: var(--gray-color) !important;
        }

        .bg-received {
            background-color: var(--received-color) !important;
        }

        .order-status {
            margin: 30px 0;
        }

        .status-track {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .status-step {
            text-align: center;
            flex: 1;
            position: relative;
        }

        .status-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 14px;
            right: -50%;
            width: 100%;
            height: 4px;
            background-color: #ddd;
            z-index: 0;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            color: white;
            margin: auto;
            z-index: 1;
            position: relative;
            font-weight: bold;
        }

        .step-icon.completed {
            background-color: var(--primary-color);
        }

        .step-icon.active {
            background-color: var(--warning-color);
        }

        .step-label {
            margin-top: 8px;
            font-size: 13px;
            color: var(--text-color);
        }

        .detail-section {
            background-color: var(--white-color);
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .item-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }

        .item-table td {
            vertical-align: middle;
        }

        .order-summary {
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .summary-item.total {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }

        footer {
            padding: 30px 50px;
            background-color: white;
            text-align: center;
            border-top: 1px solid #eee;
            margin-top: 50px;
        }

        .social-contact {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .find-us {
            margin-right: 20px;
            font-size: 14px;
        }

        .social-icons {
            display: flex;
            gap: 15px;
        }

        .social-icons a {
            color: var(--text-color);
            font-size: 18px;
            text-decoration: none;
        }

        .contact-number {
            border-left: 1px solid #ddd;
            padding-left: 15px;
            margin-left: 15px;
            display: flex;
            align-items: center;
        }

        .contact-number i {
            margin-right: 5px;
        }

        .copyright {
            font-size: 12px;
            color: #666;
        }

        .custom-item-details {
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin-top: 10px;
        }

        .custom-item-detail {
            display: flex;
            margin-bottom: 5px;
        }

        .custom-item-label {
            font-weight: 500;
            min-width: 120px;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        /* Retur Section Styles */
        .retur-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }
        
        .retur-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .retur-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-waiting {
            background-color: #FFC107;
            color: #000;
        }
        
        .status-approved {
            background-color: #28A745;
            color: #fff;
        }
        
        .status-rejected {
            background-color: #DC3545;
            color: #fff;
        }
        
        .retur-details {
            margin-top: 15px;
        }
        
        .retur-detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .retur-detail-label {
            font-weight: 500;
            min-width: 150px;
        }
        
        .retur-items {
            margin-top: 15px;
        }
        
        .retur-item {
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .retur-proof {
            margin-top: 15px;
        }
        
        .retur-proof img {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .refund-details {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
                flex-direction: column;
            }

            nav ul {
                margin: 15px 0;
                flex-wrap: wrap;
                justify-content: center;
            }

            .profile-container {
                margin-top: 20px;
            }

            .retur-detail-row {
                flex-direction: column;
            }

            .retur-detail-label {
                min-width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="../index.php" class="logo-container">
            <img src="../uploads/logo-sefni.png" alt="Logo" class="logo">
            <span class="logo-text">Rumah Sulam Sefni</span>
        </a>
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="katalog_produk.php">Katalog Produk</a></li>
                <li><a href="kustomisasi.php">Kustomisasi Sulaman</a></li>
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="tentang_kami.php">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
        </nav>
        <div class="user-icons">
            <a href="keranjang.php"><i class="fas fa-shopping-cart"></i></a>
            <a href="profil.php"><i class="fas fa-user"></i></a>
        </div>
    </header>

    <div class="container profile-container">
        <div class="row">
            <div class="col-md-3">
                <div class="profile-sidebar">
                    <div class="text-center mb-4">
                        <div class="profile-avatar-container">
                            <?php if(isset($_SESSION['nama_pelanggan'])): ?>
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama_pelanggan']) ?>&background=6d4c41&color=fff" alt="Profil" class="profile-avatar">
                                <h4 class="profile-name mt-3"><?= htmlspecialchars($_SESSION['nama_pelanggan']) ?></h4>
                            <?php else: ?>
                                <div style="width: 120px; height: 120px; background-color: #6d4c41; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto;">
                                    P
                                </div>
                                <h4 class="profile-name mt-3">Pelanggan</h4>
                            <?php endif; ?>
                        </div>
                    </div>
                    <ul class="profile-menu">
                        <li>
                            <a href="profil.php">
                                <i class="fas fa-user"></i> Profil Saya
                            </a>
                        </li>
                        <li>
                            <a href="pesanan_saya.php" class="active">
                                <i class="fas fa-shopping-bag"></i> Pesanan Saya
                            </a>
                        </li>
                        <li>
                            <a href="alamat_saya.php">
                                <i class="fas fa-map-marker-alt"></i> Alamat Saya
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="text-danger">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="profile-content">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="pesanan_saya.php">Pesanan Saya</a></li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </nav>
                    
                    <div class="profile-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">Detail Pesanan #<?= $id_pesanan ?></h3>
                            <p class="text-muted mb-0">Tanggal Pemesanan: <?= date('d M Y', strtotime($tanggal_pesan)) ?></p>
                        </div>
                        <div>
                            <?php
                            $status_class = 'bg-warning';
                            if (strtolower($pesanan['status']) == 'selesai') {
                                $status_class = 'bg-success';
                            } elseif (strtolower($pesanan['status']) == 'dibatalkan') {
                                $status_class = 'bg-danger';
                            } elseif (strtolower($pesanan['status']) == 'diproses') {
                                $status_class = 'bg-info';
                            } elseif (strtolower($pesanan['status']) == 'dikirim') {
                                $status_class = 'bg-warning';
                            } elseif (strtolower($pesanan['status']) == 'menunggu') {
                                $status_class = 'bg-secondary';
                            } elseif (strtolower($pesanan['status']) == 'diterima') {
                                $status_class = 'bg-received';
                            }
                            ?>
                            <span class="status-badge <?= $status_class ?> text-white"><?= strtoupper($pesanan['status']) ?></span>
                        
                        </div>
                    </div>
                    
                    <div class="order-status">
                        <div class="status-track">
                            <div class="status-step">
                                <div class="step-icon <?= $current_step >= 1 ? 'completed' : '' ?>">1</div>
                                <div class="step-label">Pesanan Diterima</div>
                            </div>
                            <div class="status-step">
                                <div class="step-icon <?= $current_step > 2 ? 'completed' : ($current_step == 2 ? 'active' : '') ?>">2</div>
                                <div class="step-label">Diproses</div>
                            </div>
                            <div class="status-step">
                                <div class="step-icon <?= $current_step > 3 ? 'completed' : ($current_step == 3 ? 'active' : '') ?>">3</div>
                                <div class="step-label">Pengiriman</div>
                            </div>
                            <div class="status-step">
                                <div class="step-icon <?= $current_step > 4 ? 'completed' : ($current_step == 4 ? 'active' : '') ?>">4</div>
                                <div class="step-label">Diterima</div>
                            </div>
                            <div class="status-step">
                                <div class="step-icon <?= $current_step >= 5 ? 'completed' : '' ?>">5</div>
                                <div class="step-label">Selesai</div>
                            </div>
                        </div>
                        <?php if ($has_retur): ?>
                        <div class="text-center mt-3">
                            <span class="badge bg-info">
                                <i class="fas fa-info-circle me-1"></i> Status Retur: <?= $retur['status'] ?? 'Menunggu Konfirmasi' ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h5>Informasi Pelanggan</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Nama:</td>
                                        <td><?= htmlspecialchars($pelanggan['nama_pelanggan']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Email:</td>
                                        <td><?= htmlspecialchars($pelanggan['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">No. Telepon:</td>
                                        <td><?= htmlspecialchars($pelanggan['no_hp']) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h5>Informasi Pengiriman</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Alamat:</td>
                                        <td>
                                            <?php if ($alamat_pengiriman): ?>
                                                <?= htmlspecialchars($alamat_pengiriman['alamat_lengkap']) ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($pesanan['alamat_pengiriman'] ?? 'Tidak tersedia') ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Metode:</td>
                                        <td><?= htmlspecialchars($pesanan['metode_pengiriman'] ?? 'Belum ditentukan') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">No. Resi:</td>
                                        <td><?= !empty($pesanan['no_resi']) ? htmlspecialchars($pesanan['no_resi']) : '-' ?></td>
                                    </tr>
                                    <?php if (!empty($pesanan['nama_kurir'])): ?>
                                    <tr>
                                        <td class="fw-bold">Nama Kurir:</td>
                                        <td><?= htmlspecialchars($pesanan['nama_kurir']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($pesanan['telepon_kurir'])): ?>
                                    <tr>
                                        <td class="fw-bold">Telp Kurir:</td>
                                        <td><?= htmlspecialchars($pesanan['telepon_kurir']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h5>Informasi Pembayaran</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Status:</td>
                                        <td>
                                            <?php
                                            $status_pembayaran = 'Menunggu Pembayaran';
                                            if (!empty($pesanan['tanggal_pembayaran']) || 
                                                strtolower($pesanan['status_pembayaran'] ?? '') == 'lunas' || 
                                                strtolower($pesanan['status_pembayaran'] ?? '') == 'dibayar') {
                                                $status_pembayaran = 'Lunas';
                                            }
                                            echo $status_pembayaran;
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tanggal:</td>
                                        <td><?= !empty($pesanan['tanggal_pembayaran']) ? date('d M Y', strtotime($pesanan['tanggal_pembayaran'])) : '-' ?></td>
                                    </tr>
                                    <?php if (!empty($pesanan['bukti_pembayaran'])): ?>
                                    <tr>
                                        <td class="fw-bold">Bukti:</td>
                                        <td>
                                            <a href="../uploads/payments/<?= htmlspecialchars($pesanan['bukti_pembayaran']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                Lihat Bukti Pembayaran
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h5>Estimasi Pengerjaan</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Mulai:</td>
                                        <td><?= !empty($pesanan['tanggal_mulai_pengerjaan']) ? date('d M Y', strtotime($pesanan['tanggal_mulai_pengerjaan'])) : date('d M Y', strtotime($tanggal_pesan . ' +2 day')) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Selesai:</td>
                                        <td>
                                            <?php 
                                            if (!empty($pesanan['estimasi_selesai'])) {
                                                echo date('d M Y', strtotime($pesanan['estimasi_selesai']));
                                            } else {
                                                if ($is_custom_order) {
                                                    echo date('d M Y', strtotime($tanggal_pesan . ' +20 days'));
                                                } else {
                                                    echo date('d M Y', strtotime($tanggal_pesan . ' +5 days'));
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Pengiriman:</td>
                                        <td>
                                            <?php 
                                            if (!empty($pesanan['estimasi_pengiriman'])) {
                                                echo date('d M Y', strtotime($pesanan['estimasi_pengiriman']));
                                            } else {
                                                if ($is_custom_order) {
                                                    echo date('d M Y', strtotime($tanggal_pesan . ' +23 days'));
                                                } else {
                                                    echo date('d M Y', strtotime($tanggal_pesan . ' +8 days'));
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                                <?php if ($is_custom_order): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Pesanan Kustom:</strong> Memerlukan waktu pengerjaan lebih lama (20 hari) karena proses pembuatan khusus sesuai permintaan Anda.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($has_retur): ?>
                    <div class="retur-section">
                        <div class="retur-header">
                            <h5>Informasi Retur</h5>
                            <span class="retur-status 
                                <?= $retur['status'] === 'Diterima' ? 'status-approved' : 
                                   ($retur['status'] === 'Ditolak' ? 'status-rejected' : 'status-waiting') ?>">
                                <?= $retur['status'] ?: 'Menunggu Konfirmasi' ?>
                            </span>
                        </div>
                        
                        <div class="retur-details">
                            <div class="retur-detail-row">
                                <span class="retur-detail-label">Tanggal Pengajuan:</span>
                                <span><?= date('d M Y H:i', strtotime($retur['tanggal_pengajuan'])) ?></span>
                            </div>
                            <div class="retur-detail-row">
                                <span class="retur-detail-label">Jenis Retur:</span>
                                <span><?= $retur['jenis_retur'] === 'pengembalian_uang' ? 'Pengembalian Uang' : 'Pengembalian Barang' ?></span>
                            </div>
                            <div class="retur-detail-row">
                                <span class="retur-detail-label">Alasan Retur:</span>
                                <span><?= htmlspecialchars($retur['alasan']) ?></span>
                            </div>
                            <?php if (!empty($retur['keterangan'])): ?>
                            <div class="retur-detail-row">
                                <span class="retur-detail-label">Keterangan:</span>
                                <span><?= nl2br(htmlspecialchars($retur['keterangan'])) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($retur['status'] === 'Ditolak' && !empty($retur['alasan_penolakan'])): ?>
                            <div class="retur-detail-row">
                                <span class="retur-detail-label">Alasan Penolakan:</span>
                                <span class="text-danger"><?= htmlspecialchars($retur['alasan_penolakan']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($retur['status'] === 'Diterima'): ?>
                            <div class="refund-details">
                                <h6>Detail Persetujuan Retur</h6>
                                <?php if ($retur['jenis_retur'] === 'pengembalian_uang' && !empty($retur['refund_data'])): ?>
                                    <div class="retur-detail-row">
                                        <span class="retur-detail-label">Jumlah Pengembalian:</span>
                                        <span>Rp <?= number_format($retur['refund_data']['amount'], 0, ',', '.') ?></span>
                                    </div>
                                    <div class="retur-detail-row">
                                        <span class="retur-detail-label">Metode Pengembalian:</span>
                                        <span><?= $retur['refund_data']['method'] ?? 'Transfer Bank' ?></span>
                                    </div>
                                    <?php if (!empty($retur['refund_data']['note'])): ?>
                                    <div class="retur-detail-row">
                                        <span class="retur-detail-label">Catatan:</span>
                                        <span><?= htmlspecialchars($retur['refund_data']['note']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                <?php elseif ($retur['jenis_retur'] === 'pengembalian_barang' && !empty($retur['nomor_resi_pengembalian'])): ?>
                                    <div class="retur-detail-row">
                                        <span class="retur-detail-label">Nomor Resi Barang Pengganti:</span>
                                        <span><?= htmlspecialchars($retur['nomor_resi_pengembalian']) ?></span>
                                    </div>
                                    <?php if (!empty($retur['catatan_refund'])): ?>
                                    <div class="retur-detail-row">
                                        <span class="retur-detail-label">Catatan:</span>
                                        <span><?= htmlspecialchars($retur['catatan_refund']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="retur-items">
                            <h6>Item yang Dikembalikan</h6>
                            <?php 
                            $retur_items = [];
                            
                            foreach ($items as $item) {
                                $item_id = 'item_' . $item['id'];
                                if (is_array($retur['item_retur']) && in_array($item_id, $retur['item_retur'])) {
                                    $retur_items[] = [
                                        'type' => 'regular',
                                        'data' => $item
                                    ];
                                }
                            }
                            
                            foreach ($custom_items as $item) {
                                $item_id = 'custom_' . $item['id_detail_kustom'];
                                if (is_array($retur['item_retur']) && in_array($item_id, $retur['item_retur'])) {
                                    $retur_items[] = [
                                        'type' => 'custom',
                                        'data' => $item
                                    ];
                                }
                            }
                            
                            if (count($retur_items) > 0): 
                                foreach ($retur_items as $item): 
                            ?>
                                <div class="retur-item">
                                    <?php if ($item['type'] === 'regular'): ?>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['data']['gambar'])): ?>
                                                <img src="../uploads/<?= htmlspecialchars($item['data']['gambar']) ?>" 
                                                     alt="<?= htmlspecialchars($item['data']['nama_produk']) ?>" 
                                                     class="img-fluid me-3" style="max-width: 80px;">
                                            <?php else: ?>
                                                <img src="../uploads/product-placeholder.jpg" alt="Product Image" 
                                                     class="img-fluid me-3" style="max-width: 80px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($item['data']['nama_produk']) ?></strong>
                                                <?php if (!empty($item['data']['variasi'])): ?>
                                                    <br><small class="text-muted">Variasi: <?= htmlspecialchars($item['data']['variasi']) ?></small>
                                                <?php endif; ?>
                                                <br><small>Jumlah: <?= $item['data']['jumlah'] ?></small>
                                                <br><small>Harga: Rp <?= number_format($item['data']['harga'], 0, ',', '.') ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <strong><?= htmlspecialchars($item['data']['nama_produk']) ?></strong>
                                            <div class="custom-item-details">
                                                <?php if (!empty($item['data']['warna_kain'])): ?>
                                                <div class="custom-item-detail">
                                                    <span class="custom-item-label">Warna Kain:</span>
                                                    <span><?= htmlspecialchars($item['data']['warna_kain']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['data']['warna_benang'])): ?>
                                                <div class="custom-item-detail">
                                                    <span class="custom-item-label">Warna Benang:</span>
                                                    <span><?= htmlspecialchars($item['data']['warna_benang']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['data']['motif'])): ?>
                                                <div class="custom-item-detail">
                                                    <span class="custom-item-label">Motif:</span>
                                                    <span><?= htmlspecialchars($item['data']['motif']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['data']['catatan'])): ?>
                                                <div class="custom-item-detail">
                                                    <span class="custom-item-label">Catatan:</span>
                                                    <span><?= htmlspecialchars($item['data']['catatan']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <small>Harga: Rp <?= number_format($item['data']['harga'], 0, ',', '.') ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <p class="text-muted">Tidak ada item yang dikembalikan</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($retur['bukti_foto'])): ?>
                        <div class="retur-proof">
                            <h6>Bukti Foto</h6>
                            <img src="../uploads/retur/<?= htmlspecialchars($retur['bukti_foto']) ?>" 
                                 alt="Bukti Foto Retur">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-section">
                        <h5>Item Pesanan</h5>
                        <div class="table-responsive">
                            <table class="table item-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Jumlah</th>
                                        <th>Harga</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($all_items) > 0): ?>
                                        <?php foreach ($all_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <?php if (isset($item['id_produk'])): ?>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($item['gambar'])): ?>
                                                                <img src="../uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_produk']) ?>" class="img-fluid me-3" style="max-width: 80px;">
                                                            <?php else: ?>
                                                                <img src="../uploads/product-placeholder.jpg" alt="Product Image" class="img-fluid me-3" style="max-width: 80px;">
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?= htmlspecialchars($item['nama_produk']) ?></strong>
                                                                <?php if (!empty($item['variasi'])): ?>
                                                                    <br><small class="text-muted">Variasi: <?= htmlspecialchars($item['variasi']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <strong><?= htmlspecialchars($item['nama_produk']) ?></strong>
                                                            <div class="custom-item-details">
                                                                <div class="custom-item-detail">
                                                                    <span class="custom-item-label">Warna Kain:</span>
                                                                    <span><?= htmlspecialchars($item['warna_kain']) ?></span>
                                                                </div>
                                                                <div class="custom-item-detail">
                                                                    <span class="custom-item-label">Warna Benang:</span>
                                                                    <span><?= htmlspecialchars($item['warna_benang']) ?></span>
                                                                </div>
                                                                <div class="custom-item-detail">
                                                                    <span class="custom-item-label">Motif:</span>
                                                                    <span><?= htmlspecialchars($item['motif']) ?></span>
                                                                </div>
                                                                <?php if (!empty($item['catatan'])): ?>
                                                                    <div class="custom-item-detail">
                                                                        <span class="custom-item-label">Catatan:</span>
                                                                        <span><?= htmlspecialchars($item['catatan']) ?></span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= isset($item['jumlah']) ? $item['jumlah'] : 1 ?></td>
                                                <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($item['harga'] * (isset($item['jumlah']) ? $item['jumlah'] : 1), 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                                    <p>Detail item pesanan tidak tersedia saat ini.</p>
                                                    <p class="small text-muted">Silakan hubungi admin untuk informasi lebih lanjut.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h5>Ringkasan Pesanan</h5>
                        <div class="order-summary">
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span>Rp <?= number_format(($pesanan['total_harga'] ?? 0) - ($pesanan['biaya_pengiriman'] ?? 0), 0, ',', '.') ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Biaya Pengiriman</span>
                                <span>Rp <?= number_format($pesanan['biaya_pengiriman'] ?? 0, 0, ',', '.') ?></span>
                            </div>
                            <div class="summary-item total">
                                <span>Total</span>
                                <span>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="pesanan_saya.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Kembali ke Pesanan Saya
                        </a>
                        
                        <?php if (in_array(strtolower($pesanan['status']), ['menunggu', 'menunggu pembayaran'])): ?>
                            <a href="pembayaran.php?id=<?= $id_pesanan ?>" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i> Lakukan Pembayaran
                            </a>
                        <?php elseif (strtolower($pesanan['status']) == 'dikirim'): ?>
                            <div class="button-group">
                                <?php if (!$has_retur): ?>
                                    <a href="ajukan_retur.php?id=<?= $id_pesanan ?>" class="btn btn-warning">
                                        <i class="fas fa-undo me-2"></i> Ajukan Retur
                                    </a>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmReceiptModal">
                                        <i class="fas fa-check me-2"></i> Konfirmasi Pesanan Diterima
                                    </button>
                                <?php else: ?>
                                    <?php if ($retur['status'] === 'Menunggu Konfirmasi'): ?>
                                        <button type="button" class="btn btn-secondary" disabled>
                                            <i class="fas fa-clock me-2"></i> Retur Diajukan - Menunggu Konfirmasi
                                        </button>
                                    <?php elseif ($retur['status'] === 'Diterima'): ?>
                                        <form action="proses_selesaikan_pesanan.php" method="post" class="d-inline">
                                            <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-check-circle me-2"></i> Selesaikan Pesanan
                                            </button>
                                        </form>
                                    <?php elseif ($retur['status'] === 'Ditolak'): ?>
                                        <form action="proses_selesaikan_pesanan.php" method="post" class="d-inline">
                                            <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-check-circle me-2"></i> Selesaikan Pesanan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif (strtolower($pesanan['status']) == 'diterima'): ?>
                            <div class="button-group">
                                <?php if (!$has_retur): ?>
                                    <a href="ajukan_retur.php?id=<?= $id_pesanan ?>" class="btn btn-warning">
                                        <i class="fas fa-undo me-2"></i> Ajukan Retur
                                    </a>
                                    <form action="proses_selesaikan_pesanan.php" method="post" class="d-inline">
                                        <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check-circle me-2"></i> Selesaikan Pesanan
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($retur['status'] === 'Menunggu Konfirmasi'): ?>
                                        <button type="button" class="btn btn-secondary" disabled>
                                            <i class="fas fa-clock me-2"></i> Retur Diajukan - Menunggu Konfirmasi
                                        </button>
                                    <?php elseif ($retur['status'] === 'Diterima'): ?>
                                        <form action="proses_selesaikan_pesanan.php" method="post" class="d-inline">
                                            <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-check-circle me-2"></i> Selesaikan Pesanan
                                            </button>
                                        </form>
                                    <?php elseif ($retur['status'] === 'Ditolak'): ?>
                                        <form action="proses_selesaikan_pesanan.php" method="post" class="d-inline">
                                            <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-check-circle me-2"></i> Selesaikan Pesanan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif (strtolower($pesanan['status']) == 'selesai'): ?>
                            <?php if ($has_retur): ?>
                                <?php if ($retur['status'] === 'Menunggu Konfirmasi'): ?>
                                    <button type="button" class="btn btn-secondary" disabled>
                                        <i class="fas fa-clock me-2"></i> Retur Diajukan - Menunggu Konfirmasi
                                    </button>
                                <?php elseif ($retur['status'] === 'Diterima'): ?>
                                    <button type="button" class="btn btn-success" disabled>
                                        <i class="fas fa-check me-2"></i> Retur Diterima
                                    </button>
                                <?php elseif ($retur['status'] === 'Ditolak'): ?>
                                    <button type="button" class="btn btn-danger" disabled>
                                        <i class="fas fa-times me-2"></i> Retur Ditolak
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmReceiptModal" tabindex="-1" aria-labelledby="confirmReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmReceiptModalLabel">Konfirmasi Penerimaan Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin telah menerima pesanan ini?</p>
                    <p class="text-muted small">Dengan mengkonfirmasi penerimaan pesanan, Anda menyatakan bahwa pesanan telah sampai dengan baik dan sesuai.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form action="proses_konfirmasi_pesanan.php" method="post">
                        <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                        <button type="submit" class="btn btn-success">Ya, Saya Sudah Menerima Pesanan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="social-contact">
            <div class="find-us">
                <strong>Temukan Kami</strong>
            </div>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
            <div class="contact-number">
                <i class="fas fa-phone"></i> +62 812 3456 7890
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date('Y') ?> Rumah Sulam Sefni. All Rights Reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>