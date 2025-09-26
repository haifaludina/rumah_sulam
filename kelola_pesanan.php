<?php 
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}

include '../koneksi.php';

// Handle filter and pagination
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Build query
$query_where = '';
if (!empty($status_filter) && $status_filter != 'all') {
    $query_where = "WHERE p.status = '" . mysqli_real_escape_string($koneksi, $status_filter) . "'";
}

// Get total records for pagination
$total_query = "SELECT COUNT(*) as total FROM pesanan p $query_where";
$total_result = mysqli_query($koneksi, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $per_page);

// Get orders data
$query = "SELECT p.*, pl.nama_pelanggan 
          FROM pesanan p 
          LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
          $query_where
          ORDER BY p.tanggal_pesan DESC 
          LIMIT $offset, $per_page";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Rumah Sulam Sefni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            display: flex;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .sidebar {
            height: 100vh;
            width: 220px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #795548;
            padding-top: 20px;
            overflow-y: auto;
        }
        
        .sidebar h3 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #6D4C41;
        }
        
        .sidebar a {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 16px;
            color: white;
            display: block;
            transition: 0.3s;
            margin: 5px 10px;
            border-radius: 4px;
        }
        
        .sidebar a:hover {
            background-color: #6D4C41;
        }
        
        .sidebar a.active {
            background-color: #5D4037;
            border-left: 4px solid #977E50;
        }
        
        .content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            min-height: 100vh;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8a100;
        }
        
        .table-container {
            margin: 30px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }
        
        th, td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
        }
        
        th {
            background-color: #5D4037;
            color: white;
            font-weight: normal;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        button {
            background-color: #5D4037;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-right: 5px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #6D4C41;
        }
        
        button.btn-danger {
            background-color: #dc3545;
        }
        
        button.btn-danger:hover {
            background-color: #c82333;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from {opacity: 0}
            to {opacity: 1}
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.4s;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            transition: 0.3s;
        }
        
        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"], 
        input[type="number"],
        select, 
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        button[type="submit"] {
            background-color: #111;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button[type="submit"]:hover {
            background-color: #333;
        }
        
        
        .status-new,
        .status-menunggu {
            color: #ff9800; /* orange */
            font-weight: bold;
        }

        .status-diproses {
        color:rgb(255, 225, 55); /* biru */
        font-weight: bold;
        }
        
        .status-shipped {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .status-return {
            color: #F44336;
            font-weight: bold;
        }
        
        .status-diterima {
            color: #20c997;
            font-weight: bold;
        }
        
        .status-selesai {
            color:rgb(11, 68, 114);
            font-weight: bold;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .welcome-box {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .info-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            min-width: 120px;
            display: inline-block;
        }
        
        .info-value {
            color: #333;
        }
        
        .info-address {
            line-height: 1.6;
            color: #333;
        }
        
        .fas {
            color:rgb(252, 252, 252);
            margin-right: 8px;
        }
        
        /* Modern Filter and Pagination Styles */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .table-title {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
        }

        .filter-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 8px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .filter-group label {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: #555;
        }

        .filter-select {
            padding: 8px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background-color: white;
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-select:hover {
            border-color: #5D4037;
        }

        .filter-select:focus {
            outline: none;
            border-color: #5D4037;
            box-shadow: 0 0 0 2px rgba(93, 64, 55, 0.2);
        }

        /* Modern Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination a, 
        .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s;
            background: white;
            border: 1px solid #e0e0e0;
        }

        .pagination a:hover {
            background-color: #f5f5f5;
            border-color: #ddd;
        }

        .pagination .active {
            background-color: #5D4037;
            color: white;
            border-color: #5D4037;
        }

        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
            background: #f9f9f9;
        }

        .pagination .gap {
            border: none;
            background: transparent;
            pointer-events: none;
        }

        .pagination .page-nav {
            padding: 0 15px;
            width: auto;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .modal-content {
                width: 95%;
                padding: 15px;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-container {
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-group {
                flex-grow: 1;
            }
            
            .pagination a, 
            .pagination span {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .pagination .page-nav {
                padding: 0 10px;
            }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h3>Rumah Sulam Sefni</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="katalog_produk.php">Katalog Produk</a>
    <a href="kelola_pesanan.php" class="active">Kelola Pesanan</a>
    <a href="kelola_retur.php">Kelola Retur Produk</a>
    <a href="kelola_galeri.php">Kelola Galeri</a>
    <a href="kelola_user.php">Kelola User</a>
    <a href="profil.php">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="welcome-box">
        <h2>Kelola Pesanan</h2>
        <p>Selamat datang di manajemen pesanan Rumah Sulam Sefni.</p>
    </div>
    
    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible">' . $_SESSION['success'] . 
             '<span class="close" onclick="this.parentElement.style.display=\'none\'">&times;</span></div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible">' . $_SESSION['error'] . 
             '<span class="close" onclick="this.parentElement.style.display=\'none\'">&times;</span></div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Daftar Pesanan</h3>
            <div class="filter-container">
                <div class="filter-group">
                    <label for="per_page">Tampilkan:</label>
                    <select id="per_page" class="filter-select" onchange="applyFilter()">
                        <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status_filter">Status:</label>
                    <select id="status_filter" class="filter-select" onchange="applyFilter()">
                        <option value="all" <?= $status_filter == 'all' || empty($status_filter) ? 'selected' : '' ?>>Semua</option>
                        <option value="Menunggu" <?= $status_filter == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="Diproses" <?= $status_filter == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="Dikirim" <?= $status_filter == 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="Diterima" <?= $status_filter == 'Diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="Selesai" <?= $status_filter == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="Dibatalkan" <?= $status_filter == 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Pesanan</th>
                    <th>Nama Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!$result) {
                    echo '<tr><td colspan="7">Error: ' . mysqli_error($koneksi) . '</td></tr>';
                } else if (mysqli_num_rows($result) == 0) {
                    echo '<tr><td colspan="7">Tidak ada pesanan ditemukan</td></tr>';
                } else {
                    $no = $offset + 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $statusClass = '';
                        switch(strtolower($row['status'])) {
                            case 'menunggu': $statusClass = 'status-menunggu'; break;
                            case 'diproses': $statusClass = 'status-diproses'; break;
                            case 'baru': $statusClass = 'status-new'; break;
                            case 'dikirim': $statusClass = 'status-shipped'; break;
                            case 'diterima': $statusClass = 'status-diterima'; break;
                            case 'selesai': $statusClass = 'status-selesai'; break;
                            case 'retur':
                            case 'dibatalkan': $statusClass = 'status-return'; break;
                            default: $statusClass = '';
                        }
                        
                        echo "<tr>
                            <td>{$no}</td>
                            <td>#{$row['id_pesanan']}</td>
                            <td>" . ($row['nama_pelanggan'] ?? 'Tidak tersedia') . "</td>
                            <td>" . date('d M Y H:i', strtotime($row['tanggal_pesan'])) . "</td>
                            <td>Rp" . number_format($row['total_harga'], 0, ',', '.') . "</td>
                            <td class='{$statusClass}'>{$row['status']}</td>
                            <td>
                                <button onclick='lihatDetail({$row['id_pesanan']})'><i class='fas fa-eye'></i></button>
                                <button onclick='ubahStatus({$row['id_pesanan']})'><i class='fas fa-edit'></i></button>
                            </td>
                        </tr>";
                        $no++;
                    }
                }
                ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?status=<?= $status_filter ?>&per_page=<?= $per_page ?>&page=<?= $page-1 ?>" class="page-nav">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled page-nav"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>
                
                <?php if ($page > 3): ?>
                    <a href="?status=<?= $status_filter ?>&per_page=<?= $per_page ?>&page=1">1</a>
                    <?php if ($page > 4): ?>
                        <span class="gap">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?status=<?= $status_filter ?>&per_page=<?= $per_page ?>&page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages - 2): ?>
                    <?php if ($page < $total_pages - 3): ?>
                        <span class="gap">...</span>
                    <?php endif; ?>
                    <a href="?status=<?= $status_filter ?>&per_page=<?= $per_page ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?status=<?= $status_filter ?>&per_page=<?= $per_page ?>&page=<?= $page+1 ?>" class="page-nav">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled page-nav"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail Pesanan -->
<div id="modalDetail" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('modalDetail')">&times;</span>
        <h3>Detail Pesanan #<span id="detailId"></span></h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;" class="info-grid">
            <div class="info-card">
                <h4><i class="fas fa-user"></i>Informasi Pelanggan</h4>
                <div class="info-row">
                    <span class="info-label">Nama:</span>
                    <span class="info-value" id="detailNama">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value" id="detailEmail">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Telepon:</span>
                    <span class="info-value" id="detailTelepon">-</span>
                </div>
                <div class="info-row">
                <span class="info-label">Nama Kurir:</span>
                <span class="info-value" id="detailKurir">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Telp Kurir:</span>
                <span class="info-value" id="detailTelpKurir">-</span>
</div>
            </div>
            
            <div class="info-card">
                <h4><i class="fas fa-shopping-cart"></i>Informasi Pesanan</h4>
                <div class="info-row">
                    <span class="info-label">Tanggal:</span>
                    <span class="info-value" id="detailTanggal">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" id="detailStatus">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pembayaran:</span>
                    <span class="info-value" id="detailPembayaran">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pengiriman:</span>
                    <span class="info-value" id="detailPengiriman">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Biaya Kirim:</span>
                    <span class="info-value" id="detailBiayaKirim">-</span>
                </div>
            </div>
            
            <div class="info-card">
                <h4><i class="fas fa-truck"></i>Alamat Pengiriman</h4>
                <div class="info-address">
                    <span id="detailAlamat">-</span><br>
                    <span id="detailKota">-</span><br>
                    <span id="detailKodePos">-</span>
                </div>
            </div>
            
            <div class="info-card">
                <h4><i class="fas fa-receipt"></i>Bukti Pembayaran</h4>
                <div id="buktiPembayaranContainer" style="text-align: center;">
                    <p id="noBuktiMessage" style="color: #777;">Belum ada bukti pembayaran</p>
                    <img id="buktiPembayaranImg" style="max-width: 100%; max-height: 200px; display: none; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;" alt="Bukti Pembayaran">
                    <a id="downloadBukti" href="#" style="display: none; margin-top: 10px; color: #5D4037; text-decoration: none;">
                        <i class="fas fa-download"></i> Download Bukti
                    </a>
                </div>
            </div>
        </div>
        
        <div class="info-card" style="grid-column: span 2;">
            <h4><i class="fas fa-box-open"></i>Produk Dipesan</h4>
            <div style="overflow-x: auto;">
                <table id="tabelProduk" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Tipe</th>
                        </tr>
                    </thead>
                    <tbody id="tabelProdukBody"></tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Total Produk:</td>
                            <td id="totalProduk">Rp0</td>
                            <td></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Biaya Pengiriman:</td>
                            <td id="biayaPengiriman">Rp0</td>
                            <td></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right; font-weight: bold;">Total Keseluruhan:</td>
                            <td id="totalKeseluruhan" style="font-weight: bold;">Rp0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="info-card" style="grid-column: span 2;">
            <h4><i class="fas fa-sticky-note"></i>Catatan Pesanan</h4>
            <div id="catatanPesanan" style="background-color: #f9f9f9; padding: 15px; border-radius: 4px;">
                <p id="detailCatatan" style="margin: 0; color: #555; font-style: italic;">Tidak ada catatan</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ubah Status -->
<div id="modalStatus" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('modalStatus')">&times;</span>
        <h3>Ubah Status Pesanan #<span id="statusId"></span></h3>
        <form id="formStatus" action="ubah_status.php" method="POST">
            <input type="hidden" id="id_pesanan_status" name="id_pesanan">
            
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label>Status Saat Ini:</label>
                    <input type="text" id="status_sekarang" readonly style="background-color: #f5f5f5;">
                </div>
                <div style="flex: 1;">
                    <label>Ubah ke Status:</label>
                    <select name="status" id="status_pesanan" required>
                        <option value="">Pilih Status</option>
                        <option value="Menunggu">Menunggu</option>
                        <option value="Diproses">Diproses</option>
                        <option value="Dikirim">Dikirim</option>
                        <option value="Diterima">Diterima</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                </div>
            </div>
            
            <div id="resiContainer" style="display: none;">
                <label>Nomor Resi:</label>
                <input type="text" name="no_resi" id="no_resi" placeholder="Masukkan nomor resi">
            </div>
            
            <label>Catatan:</label>
            <textarea name="catatan" id="catatan" placeholder="Tambahkan catatan untuk pelanggan"></textarea>
            
            <div id="kurirContainer" style="display: none;">
            <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
            <label>Nama Kurir:</label>
            <input type="text" name="nama_kurir" id="nama_kurir" placeholder="Masukkan nama kurir">
             </div>
            <div style="flex: 1;">
            <label>Telepon Kurir:</label>
            <input type="text" name="telepon_kurir" id="telepon_kurir" placeholder="Masukkan nomor telepon">
            </div> 
                </div>
                </div>

            <div style="margin-top: 20px; text-align: right;">
                <button type="button" onclick="tutupModal('modalStatus')" class="btn-danger">Batal</button>
                <button type="submit">Simpan Perubahan</button>
            </div>  
    </div>
</div>
        </form>
    </div>
</div>

<script>
// Fungsi untuk menerapkan filter
function applyFilter() {
    const status = document.getElementById('status_filter').value;
    const perPage = document.getElementById('per_page').value;
    window.location.href = `?status=${status}&per_page=${perPage}&page=1`;
}

// Fungsi untuk menampilkan detail pesanan
function lihatDetail(id) {
    document.getElementById('modalDetail').style.display = 'block';
    document.getElementById('detailId').textContent = id;
    
    document.querySelectorAll('.info-value').forEach(el => el.textContent = 'Loading...');
    document.getElementById('tabelProdukBody').innerHTML = '<tr><td colspan="5" style="text-align: center;">Memuat data...</td></tr>';
    document.getElementById('buktiPembayaranImg').style.display = 'none';
    document.getElementById('noBuktiMessage').style.display = 'block';
    document.getElementById('downloadBukti').style.display = 'none';
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_detail_pesanan.php?id=' + id, true);
    
    xhr.onload = function() {
        if (this.status == 200) {
            try {
                const data = JSON.parse(this.responseText);
                
                let alamat = 'Tidak tersedia';
                let kota = '';
                let kodePos = '';
                
                if (data.alamat) {
                    alamat = data.alamat.alamat_lengkap || data.pesanan.alamat_pengiriman || 'Tidak tersedia';
                    kota = `${data.alamat.kota || ''}, ${data.alamat.provinsi || ''}`;
                    kodePos = data.alamat.kode_pos || '';
                } else if (data.pesanan.alamat_pengiriman) {
                    alamat = data.pesanan.alamat_pengiriman;
                }
                
                document.getElementById('detailNama').textContent = data.pesanan.nama_penerima || data.pelanggan.nama_pelanggan || 'Tidak tersedia';
                document.getElementById('detailEmail').textContent = data.pesanan.email_penerima || data.pelanggan.email || 'Tidak tersedia';
                document.getElementById('detailTelepon').textContent = data.pesanan.telepon_penerima || data.pelanggan.no_hp || 'Tidak tersedia';
                document.getElementById('detailTanggal').textContent = formatTanggal(data.pesanan.tanggal_pesan);
                document.getElementById('detailStatus').textContent = data.pesanan.status || 'Tidak tersedia';
                document.getElementById('detailPembayaran').textContent = data.pesanan.metode_pembayaran || 'Tidak tersedia';
                document.getElementById('detailPengiriman').textContent = data.pesanan.metode_pengiriman || 'Tidak tersedia';
                document.getElementById('detailBiayaKirim').textContent = formatRupiah(data.pesanan.biaya_pengiriman || 0);
                document.getElementById('detailAlamat').textContent = alamat;
                document.getElementById('detailKota').textContent = kota;
                document.getElementById('detailKodePos').textContent = kodePos;
                document.getElementById('detailCatatan').textContent = data.pesanan.catatan || 'Tidak ada catatan';
                document.getElementById('detailKurir').textContent = data.pesanan.nama_kurir || 'Tidak tersedia';
                document.getElementById('detailTelpKurir').textContent = data.pesanan.telepon_kurir || 'Tidak tersedia';
                const buktiImg = document.getElementById('buktiPembayaranImg');
                const noMessage = document.getElementById('noBuktiMessage');
                const downloadLink = document.getElementById('downloadBukti');
                
                if (data.pesanan.bukti_pembayaran) {
                    const buktiUrl = '../uploads/payments/' + data.pesanan.bukti_pembayaran;
                    buktiImg.src = buktiUrl;
                    buktiImg.style.display = 'block';
                    noMessage.style.display = 'none';
                    downloadLink.href = buktiUrl;
                    downloadLink.style.display = 'inline-block';
                    
                    buktiImg.onerror = function() {
                        buktiImg.style.display = 'none';
                        noMessage.textContent = 'Gagal memuat bukti pembayaran';
                        noMessage.style.display = 'block';
                        downloadLink.style.display = 'none';
                    };
                } else {
                    buktiImg.style.display = 'none';
                    noMessage.style.display = 'block';
                    downloadLink.style.display = 'none';
                }
                
                const tbody = document.getElementById('tabelProdukBody');
                tbody.innerHTML = '';
                
                let totalProduk = 0;
                
                if (data.produk_ready && data.produk_ready.length > 0) {
                    data.produk_ready.forEach(p => {
                        const row = document.createElement('tr');
                        const harga = parseInt(p.harga || 0);
                        const jumlah = parseInt(p.jumlah || 0);
                        const subtotal = harga * jumlah;
                        totalProduk += subtotal;
                        
                        row.innerHTML = `
                            <td>${p.nama_produk || 'Produk tidak tersedia'}</td>
                            <td>${formatRupiah(harga)}</td>
                            <td>${jumlah}</td>
                            <td>${formatRupiah(subtotal)}</td>
                            <td>Ready</td>
                        `;
                        tbody.appendChild(row);
                    });
                }
                
                if (data.produk_custom && data.produk_custom.length > 0) {
                    data.produk_custom.forEach(p => {
                        const row = document.createElement('tr');
                        row.className = 'custom-product';
                        const harga = parseInt(p.harga || 0);
                        totalProduk += harga;
                        
                        let customDesc = `<strong>${p.nama_produk}</strong><br>`;
                        if (p.warna_kain) customDesc += `Warna Kain: ${p.warna_kain}<br>`;
                        if (p.warna_benang) customDesc += `Warna Benang: ${p.warna_benang}<br>`;
                        if (p.motif) customDesc += `Motif: ${p.motif}<br>`;
                        if (p.catatan) customDesc += `Catatan: ${p.catatan}`;
                        
                        row.innerHTML = `
                            <td>${customDesc}</td>
                            <td>${formatRupiah(harga)}</td>
                            <td>1</td>
                            <td>${formatRupiah(harga)}</td>
                            <td>Custom</td>
                        `;
                        tbody.appendChild(row);
                    });
                }
                
                if ((!data.produk_ready || data.produk_ready.length === 0) && 
                    (!data.produk_custom || data.produk_custom.length === 0)) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="5">Tidak ada produk ditemukan</td>';
                    tbody.appendChild(row);
                }
                
                const biayaKirim = parseInt(data.pesanan.biaya_pengiriman || 0);
                const totalKeseluruhan = totalProduk + biayaKirim;
                
                document.getElementById('totalProduk').textContent = formatRupiah(totalProduk);
                document.getElementById('biayaPengiriman').textContent = formatRupiah(biayaKirim);
                document.getElementById('totalKeseluruhan').textContent = formatRupiah(totalKeseluruhan);
                
            } catch (e) {
                console.error('Error parsing JSON:', e);
                document.getElementById('detailNama').textContent = 'Error: Gagal memproses data';
                document.getElementById('tabelProdukBody').innerHTML = '<tr><td colspan="5">Gagal memuat data produk</td></tr>';
            }
        } else {
            console.error('Server returned status:', this.status);
            document.getElementById('detailNama').textContent = 'Error: Gagal mengambil data';
            document.getElementById('tabelProdukBody').innerHTML = '<tr><td colspan="5">Gagal memuat data dari server</td></tr>';
        }
    };
    
    xhr.onerror = function() {
        console.error('Network error occurred');
        document.getElementById('detailNama').textContent = 'Error: Koneksi gagal';
        document.getElementById('tabelProdukBody').innerHTML = '<tr><td colspan="5">Gagal terhubung ke server</td></tr>';
    };
    
    xhr.send();
}

// Fungsi untuk mengubah status pesanan
function ubahStatus(id) {
    document.getElementById('id_pesanan_status').value = id;
    document.getElementById('statusId').textContent = id;
    document.getElementById('modalStatus').style.display = 'block';
    document.getElementById('status_sekarang').value = 'Loading...';
    document.getElementById('status_pesanan').value = '';
    document.getElementById('catatan').value = '';
    document.getElementById('no_resi').value = '';
    document.getElementById('resiContainer').style.display = 'none';
    document.getElementById('nama_kurir').value = '';
document.getElementById('telepon_kurir').value = '';
document.getElementById('kurirContainer').style.display = 'none';

    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_pesanan_status.php?id=' + id, true);
    
    xhr.onload = function() {
        if (this.status == 200) {
            try {
                const data = JSON.parse(this.responseText);
                
                document.getElementById('status_sekarang').value = data.status;
                document.getElementById('status_pesanan').value = data.status;
                document.getElementById('catatan').value = data.catatan || '';
                
                if (data.status === 'Dikirim') {
                    document.getElementById('resiContainer').style.display = 'block';
                    document.getElementById('no_resi').value = data.no_resi || '';
                } else {
                    document.getElementById('resiContainer').style.display = 'none';
                }
                
            } catch (e) {
                console.error('Error parsing JSON:', e);
                alert('Gagal memproses data status. Error: ' + e.message);
            }
        } else {
            console.error('Server returned status:', this.status);
            alert('Gagal mengambil data status. Error: Status ' + this.status);
        }
    };
    
    xhr.onerror = function() {
        console.error('Network error occurred');
        alert('Gagal mengambil data status. Error: Koneksi gagal. Periksa koneksi internet Anda.');
    };
    
    xhr.send();
}

document.getElementById('status_pesanan').addEventListener('change', function() {
    const resiContainer = document.getElementById('resiContainer');
    const kurirContainer = document.getElementById('kurirContainer');
    
    if (this.value === 'Dikirim') {
        resiContainer.style.display = 'block';
        kurirContainer.style.display = 'block';
    } else {
        resiContainer.style.display = 'none';
        kurirContainer.style.display = 'none';
    }
});

function tutupModal(id) {
    document.getElementById(id).style.display = 'none';
}

function formatTanggal(tanggalStr) {
    if (!tanggalStr) return '';
    
    const tanggal = new Date(tanggalStr);
    if (isNaN(tanggal.getTime())) return tanggalStr;
    
    const options = { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return tanggal.toLocaleDateString('id-ID', options);
}

function formatRupiah(angka) {
    if (angka === null || angka === undefined) return 'Rp0';
    return 'Rp' + parseInt(angka).toLocaleString('id-ID');
}


window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}

setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
</body>
</html>