<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}

include '../koneksi.php';

if (!$koneksi) {
    die("Database connection failed: " . mysqli_connect_error());
}

$query = "
    SELECT r.*, p.nama_pelanggan, ps.status as status_pesanan
    FROM retur r
    JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    JOIN pesanan ps ON r.id_pesanan = ps.id_pesanan
    ORDER BY r.tanggal_pengajuan DESC, r.id_retur DESC
";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Error retrieving return data: " . mysqli_error($koneksi));
}

$returData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['status'] = !empty($row['status']) ? $row['status'] : 'Menunggu Konfirmasi';
        $row['tanggal_retur'] = $row['tanggal_retur'] ?? $row['tanggal_pengajuan'] ?? date('Y-m-d H:i:s');

        if (!empty($row['refund_data']) && is_string($row['refund_data'])) {
            $row['refund_data'] = json_decode($row['refund_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $row['refund_data'] = null;
            }
        }

        $pesananQuery = "SELECT * FROM pesanan WHERE id_pesanan = " . $row['id_pesanan'];
        $pesananResult = mysqli_query($koneksi, $pesananQuery);
        $pesanan = mysqli_fetch_assoc($pesananResult);
        
        // Get items for return
        $itemsQuery = "SELECT ip.*, p.nama_produk, p.gambar FROM item_pesanan ip
                      JOIN produk p ON ip.id_produk = p.id_produk
                      WHERE ip.id_pesanan = " . $row['id_pesanan'];
        $itemsResult = mysqli_query($koneksi, $itemsQuery);

        $items = [];
        if ($itemsResult) {
            while ($item = mysqli_fetch_assoc($itemsResult)) {
                $itemId = 'item_' . $item['id'];
                $isInRetur = false;
                if (!empty($row['item_retur'])) {
                    $returItems = json_decode($row['item_retur'], true);
                    $isInRetur = is_array($returItems) && in_array($itemId, $returItems);
                }
                
                if ($isInRetur) {
                    $items[] = [
                        'nama_produk' => $item['nama_produk'],
                        'harga' => $item['harga'],
                        'qty' => $item['jumlah'],
                        'subtotal' => $item['harga'] * $item['jumlah'],
                        'kondisi' => $item['kondisi'] ?? 'Baik',
                        'gambar' => $item['gambar']
                    ];
                }
            }
        }

        // Get custom items for return
        $customItemsQuery = "SELECT * FROM detail_pesanan_kustom WHERE id_pesanan = " . $row['id_pesanan'];
        $customItemsResult = mysqli_query($koneksi, $customItemsQuery);

        if ($customItemsResult) {
            while ($customItem = mysqli_fetch_assoc($customItemsResult)) {
                $itemId = 'custom_' . $customItem['id_detail_kustom'];
                $isInRetur = false;
                if (!empty($row['item_retur'])) {
                    $returItems = json_decode($row['item_retur'], true);
                    $isInRetur = is_array($returItems) && in_array($itemId, $returItems);
                }
                
                if ($isInRetur) {
                    $items[] = [
                        'nama_produk' => 'Custom: ' . $customItem['nama_produk'],
                        'harga' => $customItem['harga'],
                        'qty' => 1,
                        'subtotal' => $customItem['harga'],
                        'kondisi' => $customItem['kondisi'] ?? 'Baik',
                        'custom_details' => [
                            'warna_kain' => $customItem['warna_kain'],
                            'warna_benang' => $customItem['warna_benang'],
                            'motif' => $customItem['motif'],
                            'catatan' => $customItem['catatan']
                        ]
                    ];
                }
            }
        }

        $row['items'] = $items;
        $row['pesanan'] = $pesanan;
        $returData[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Retur Produk - Rumah Sulam Sefni</title>
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
        
        p {
            margin-bottom: 15px;
            color: #555;
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
        
        .welcome-box {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .welcome-box h2 {
            margin-bottom: 10px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8a100;
        }
        
        .section-title {
            margin: 5px 0 20px;
            color: #333;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .action-link {
            color: #f8a100;
            text-decoration: none;
            font-weight: 500;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .btn-detail {
            background-color: #795548;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }
        
        .btn-detail:hover {
            background-color: #5D4037;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            cursor: pointer;
            z-index: 1000;
            color: #555;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .modal-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #444;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
        }
        
        .attachment-preview {
            margin-top: 10px;
        }
        
        .attachment-preview img {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .attachment-preview video {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .attachment-caption {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .no-attachment {
            color: #999;
            font-style: italic;
        }
        
        .order-details-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .produk-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .produk-table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
        }
        
        .produk-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
        }
        
        .btn-decline {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .btn-decline:hover {
            background-color: #f1b0b7;
        }
        
        .btn-approve {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .btn-approve:hover {
            background-color: #b1dfbb;
        }
        
        .btn-cancel {
            background-color: #f8f9fa;
            color: #333;
            border-color: #ddd;
        }
        
        .btn-cancel:hover {
            background-color: #e2e6ea;
        }
        
        .btn-confirm {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .btn-confirm:hover {
            background-color: #0069d9;
        }
        
        .status-diproses {
            color: #2196F3;
            font-weight: 600;
        }
        
        .status-diterima {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-ditolak {
            color: #dc3545;
            font-weight: 600;
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
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 180px;
            }
            
            .content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 15px;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            
            .modal-content-grid {
                grid-template-columns: 1fr;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="sidebar">
    <h3>Rumah Sulam Sefni</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="katalog_produk.php">Katalog Produk</a>
    <a href="kelola_pesanan.php">Kelola Pesanan</a>
    <a href="kelola_retur.php" class="active">Kelola Retur Produk</a>
    <a href="kelola_galeri.php">Kelola Galeri</a>
    <a href="kelola_user.php">Kelola User</a>
    <a href="profil.php">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="welcome-box">
        <h2>Kelola Retur Produk</h2>
        <p>Kelola semua permintaan retur dari pelanggan di sini.</p>
    </div>
    
    <div id="status-message">
        <?php if (isset($_SESSION['retur_message'])): ?>
            <div class="alert alert-<?= $_SESSION['retur_message_type'] ?>">
                <?= $_SESSION['retur_message'] ?>
            </div>
            <?php unset($_SESSION['retur_message']); ?>
            <?php unset($_SESSION['retur_message_type']); ?>
        <?php endif; ?>
    </div>
    
    <div class="table-container">
        <h3 class="section-title">Daftar Retur</h3>
        <table>
            <tr>
                <th>No</th>
                <th>ID Pesanan</th>
                <th>Pelanggan</th>
                <th>Status</th>
                <th>Tanggal Pengajuan</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($returData as $i => $retur): 
                $statusClass = '';
                if ($retur['status'] == 'Menunggu Konfirmasi' || $retur['status'] == '') {
                    $statusClass = 'status-diproses';
                } elseif ($retur['status'] === 'Diterima') {
                    $statusClass = 'status-diterima';
                } elseif ($retur['status'] === 'Ditolak') {
                    $statusClass = 'status-ditolak';
                }
            ?>
            <tr id="row_<?= $retur['id_retur']; ?>">
                <td><?= $i + 1; ?></td>
                <td><?= $retur['id_pesanan']; ?></td>
                <td><?= $retur['nama_pelanggan']; ?></td>
                <td id="status_<?= $retur['id_retur']; ?>" class="<?= $statusClass ?>"><?= $retur['status']; ?></td>
                <td><?= date('d/m/Y H:i', strtotime($retur['tanggal_pengajuan'])); ?></td>
                <td>
                    <button type="button" class="btn-detail" data-index="<?= $i; ?>">Lihat Detail</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="modal" id="popupModal">
    <div class="modal-content" id="modalContent">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<!-- Pindahkan script ke sini sebelum </body> -->
<script>
    const returData = <?= json_encode($returData); ?>;

    // Attach event listener after DOM is ready
    $(document).ready(function() {
        $('.btn-detail').on('click', function() {
            const index = $(this).data('index');
            openModal(index);
        });
    });
    
    function openModal(index) {
        const retur = returData[index];
        const modalBody = document.getElementById("modalBody");
        
        let html = `
            <div class="modal-container">
                <div class="modal-header">
                    <h2>Detail Permintaan Retur</h2>
                    <p class="retur-id">ID Retur: ${retur.id_retur}</p>
                </div>
                
                <div class="modal-content-grid">
                    <div class="modal-column">
                        <div class="info-section">
                            <h3>Informasi Retur</h3>
                            <div class="info-row">
                                <span class="info-label">ID Pesanan:</span>
                                <span class="info-value">${retur.id_pesanan}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Pelanggan:</span>
                                <span class="info-value">${retur.nama_pelanggan}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tanggal Pengajuan:</span>
                                <span class="info-value">${new Date(retur.tanggal_pengajuan).toLocaleString('id-ID')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value ${getStatusClass(retur.status)}">${retur.status || 'Menunggu Konfirmasi'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jenis Retur:</span>
                                <span class="info-value">${retur.jenis_retur === 'pengembalian_uang' ? 'Pengembalian Uang' : 'Pengembalian Barang'}</span>
                            </div>
                            ${retur.status === 'Ditolak' && retur.alasan_penolakan ? `
                            <div class="info-row">
                                <span class="info-label">Alasan Penolakan:</span>
                                <span class="info-value">${retur.alasan_penolakan}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div class="info-section">
                            <h3>Alasan Retur</h3>
                            <div class="info-row">
                                <span class="info-label">Alasan:</span>
                                <span class="info-value">${retur.alasan || 'Tidak disebutkan'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Keterangan:</span>
                                <span class="info-value">${retur.keterangan ? retur.keterangan.replace(/\n/g, '<br>') : '(Tidak ada keterangan)'}</span>
                            </div>
                        </div>
                        
                        ${retur.jenis_retur === 'pengembalian_uang' ? `
                        <div class="info-section">
                            <h3>Informasi Rekening</h3>
                            <div class="info-row">
                                <span class="info-label">Nama Bank:</span>
                                <span class="info-value">${retur.nama_bank || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nomor Rekening:</span>
                                <span class="info-value">${retur.nomor_rekening || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nama Pemilik:</span>
                                <span class="info-value">${retur.nama_pemilik_rekening || '-'}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${retur.jenis_retur === 'pengembalian_barang' ? `
                        <div class="info-section">
                            <h3>Informasi Pengembalian Barang</h3>
                            <div class="info-row">
                                <span class="info-label">Nomor Resi:</span>
                                <span class="info-value">${retur.nomor_resi_pengembalian || 'Belum diisi'}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${retur.status === 'Diterima' && retur.jenis_retur === 'pengembalian_uang' && retur.refund_data ? `
                        <div class="info-section">
                            <h3>Detail Pengembalian Dana</h3>
                            <div class="info-row">
                                <span class="info-label">Jumlah:</span>
                                <span class="info-value">Rp ${Number(retur.refund_data.amount).toLocaleString('id-ID')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Metode:</span>
                                <span class="info-value">${retur.refund_data.method || 'Transfer Bank'}</span>
                            </div>
                            ${retur.refund_data.note ? `
                            <div class="info-row">
                                <span class="info-label">Catatan:</span>
                                <span class="info-value">${retur.refund_data.note}</span>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        ${retur.status === 'Diterima' && retur.jenis_retur === 'pengembalian_barang' && retur.nomor_resi_pengembalian ? `
                        <div class="info-section">
                            <h3>Detail Pengembalian Barang</h3>
                            <div class="info-row">
                                <span class="info-label">Nomor Resi:</span>
                                <span class="info-value">${retur.nomor_resi_pengembalian}</span>
                            </div>
                            ${retur.catatan_refund ? `
                            <div class="info-row">
                                <span class="info-label">Catatan:</span>
                                <span class="info-value">${retur.catatan_refund}</span>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="modal-column">
                        <div class="info-section">
                            <h3>Item yang Dikembalikan</h3>
                            ${retur.items && retur.items.length > 0 ? `
                                <div class="table-responsive">
                                    <table class="produk-table">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Harga</th>
                                                <th>Jumlah</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${retur.items.map(item => `
                                                <tr>
                                                    <td>${item.nama_produk}</td>
                                                    <td>Rp ${Number(item.harga).toLocaleString('id-ID')}</td>
                                                    <td>${item.qty || 1}</td>
                                                    <td>Rp ${Number(item.subtotal).toLocaleString('id-ID')}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            ` : '<p class="no-attachment">Tidak ada item yang dikembalikan</p>'}
                        </div>
                        
                        <div class="info-section">
                            <h3>Bukti Retur</h3>
                            ${retur.bukti_foto ? `
                                <div class="attachment-preview">
                                    <img src="../uploads/retur/${retur.bukti_foto}" alt="Bukti Foto Retur">
                                    <p class="attachment-caption">Foto Bukti</p>
                                </div>
                            ` : '<p class="no-attachment">(Tidak ada foto bukti)</p>'}
                            
                            ${retur.bukti_video ? `
                                <div class="attachment-preview">
                                    <video controls style="max-width: 100%;">
                                        <source src="../uploads/retur/${retur.bukti_video}" type="video/mp4">
                                        Browser tidak mendukung video.
                                    </video>
                                    <p class="attachment-caption">Video Bukti</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>`;
        
        if (retur.status === 'Menunggu Konfirmasi' || !retur.status) {
            html += `
                <div class="action-buttons">
                    <button type="button" class="btn btn-decline" onclick="showRejectForm(${retur.id_retur})">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                    <button type="button" class="btn btn-approve" onclick="showApproveForm(${retur.id_retur}, '${retur.jenis_retur}')">
                        <i class="fas fa-check"></i> Terima
                    </button>
                </div>
                
                <div id="rejectForm_${retur.id_retur}" style="display:none; margin-top:20px; padding:20px; background:#f8f9fa; border-radius:5px;">
                    <h4>Alasan Penolakan</h4>
                    <textarea id="rejectReason_${retur.id_retur}" class="form-control" rows="3" placeholder="Berikan alasan penolakan retur"></textarea>
                    <div class="form-footer">
                        <button type="button" class="btn btn-cancel" onclick="cancelReject(${retur.id_retur})">
                            Batal
                        </button>
                        <button type="button" class="btn btn-confirm" onclick="submitReject(${retur.id_retur})">
                            Submit Penolakan
                        </button>
                    </div>
                </div>
                
                <div id="approveForm_${retur.id_retur}" style="display:none; margin-top:20px; padding:20px; background:#f8f9fa; border-radius:5px;">
                    <h4>Proses Persetujuan Retur</h4>
                    ${retur.jenis_retur === 'pengembalian_uang' ? `
                        <div class="form-group">
                            <label>Jumlah Pengembalian (Rp)</label>
                            <input type="number" id="refundAmount_${retur.id_retur}" class="form-control" value="${calculateTotal(retur.items)}">
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea id="refundNote_${retur.id_retur}" class="form-control" rows="2" placeholder="Catatan untuk pelanggan"></textarea>
                        </div>
                    ` : `
                        <p>Konfirmasi penerimaan barang pengganti:</p>
                        <div class="form-group">
                            <label>Nomor Resi Pengiriman Barang Pengganti</label>
                            <input type="text" id="resiPengganti_${retur.id_retur}" class="form-control" placeholder="Masukkan nomor resi">
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea id="exchangeNote_${retur.id_retur}" class="form-control" rows="2" placeholder="Catatan untuk pelanggan"></textarea>
                        </div>
                    `}
                    <div class="form-footer">
                        <button type="button" class="btn btn-cancel" onclick="cancelApprove(${retur.id_retur})">
                            Batal
                        </button>
                        <button type="button" class="btn btn-confirm" onclick="submitApprove(${retur.id_retur}, '${retur.jenis_retur}')">
                            Proses Persetujuan
                        </button>
                    </div>
                </div>
            `;
        }
        
        modalBody.innerHTML = html + `</div>`;
        document.getElementById("popupModal").style.display = "block";
        document.body.style.overflow = "hidden";
    }
    document.body.style.overflow = "hidden";

    function getStatusClass(status) {
        if (!status || status === 'Menunggu Konfirmasi') return 'status-diproses';
        if (status === 'Diterima') return 'status-diterima';
        if (status === 'Ditolak') return 'status-ditolak';
        return '';
    }

    function calculateTotal(items) {
        if (!items) return 0;
        return items.reduce((total, item) => total + (item.harga * item.qty), 0);
    }

    function closeModal() {
        document.getElementById("popupModal").style.display = "none";
        document.body.style.overflow = "auto";
    }
    
    function showRejectForm(idRetur) {
        document.querySelector(`#rejectForm_${idRetur}`).style.display = 'block';
        document.querySelector(`.action-buttons`).style.display = 'none';
    }

    function cancelReject(idRetur) {
        document.querySelector(`#rejectForm_${idRetur}`).style.display = 'none';
        document.querySelector(`.action-buttons`).style.display = 'flex';
    }

    function submitReject(idRetur) {
        const reason = document.querySelector(`#rejectReason_${idRetur}`).value;
        if (!reason) {
            alert('Harap isi alasan penolakan');
            return;
        }
        
        if (confirm('Anda yakin ingin menolak retur ini?')) {
            updateStatus(idRetur, 'Ditolak', { alasan_penolakan: reason });
            
            // Update pesanan status to selesai
            const idPesanan = returData.find(retur => retur.id_retur === idRetur).id_pesanan;
            // NOTE: Tidak bisa update database dari JS, harus lewat AJAX ke PHP!
            // const updatePesanan = $koneksi->prepare("UPDATE pesanan SET status = 'selesai' WHERE id_pesanan = ?");
            // updatePesanan.bind_param("i", idPesanan);
            // updatePesanan.execute();
        }
    }

    function showApproveForm(idRetur, jenisRetur) {
        document.querySelector(`#approveForm_${idRetur}`).style.display = 'block';
        document.querySelector(`.action-buttons`).style.display = 'none';
    }

    function cancelApprove(idRetur) {
        document.querySelector(`#approveForm_${idRetur}`).style.display = 'none';
        document.querySelector(`.action-buttons`).style.display = 'flex';
    }

    function submitApprove(idRetur, jenisRetur) {
        let data = {};
        
        if (jenisRetur === 'pengembalian_uang') {
            const amount = document.querySelector(`#refundAmount_${idRetur}`).value;
            const note = document.querySelector(`#refundNote_${idRetur}`).value;
            
            if (!amount || isNaN(amount)) {
                alert('Jumlah pengembalian tidak valid');
                return;
            }
            
            data = {
                method: 'transfer_bank',
                amount: amount,
                note: note,
                bank_details: {
                    bank_name: document.querySelector(`#bankName_${idRetur}`)?.value || '',
                    account_number: document.querySelector(`#accountNumber_${idRetur}`)?.value || '',
                    account_name: document.querySelector(`#accountName_${idRetur}`)?.value || ''
                }
            };
        } else {
            const resi = document.querySelector(`#resiPengganti_${idRetur}`).value;
            const note = document.querySelector(`#exchangeNote_${idRetur}`).value;
            
            if (!resi) {
                alert('Harap masukkan nomor resi pengiriman barang pengganti');
                return;
            }
            
            data = {
                method: 'pengembalian_barang',
                resi_pengganti: resi,
                note: note
            };
        }
        
        if (confirm('Anda yakin ingin menyetujui retur ini?')) {
            updateStatus(idRetur, 'Diterima', data);
        }
    }

    function updateStatus(idRetur, status, additionalData) {
        const row = $('#row_' + idRetur);
        row.addClass('loading');
        
        $.ajax({
            url: 'update_status_retur.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id_retur: idRetur,
                status: status,
                additional_data: additionalData ? JSON.stringify(additionalData) : null
            },
            success: function(response) {
                row.removeClass('loading');
                if (response.success) {
                    const statusCell = $('#status_' + idRetur);
                    statusCell.text(status).removeClass().addClass(getStatusClass(status));
                    
                    $('#status-message').html(`
                        <div class="alert alert-success">
                            Status retur #${idRetur} berhasil diperbarui menjadi "${status}"
                        </div>
                    `);
                    
                    for (let i = 0; i < returData.length; i++) {
                        if (returData[i].id_retur == idRetur) {
                            returData[i].status = status;
                            if (additionalData) {
                                if (status === 'Ditolak') {
                                    returData[i].alasan_penolakan = additionalData.alasan_penolakan;
                                } else {
                                    returData[i].refund_data = additionalData;
                                    if (additionalData.resi_pengganti) {
                                        returData[i].nomor_resi_pengembalian = additionalData.resi_pengganti;
                                    }
                                }
                            }
                            break;
                        }
                    }
                    
                    setTimeout(closeModal, 2000);
                } else {
                    alert('Gagal memperbarui status: ' + response.message);
                }
            },
            error: function(xhr) {
                row.removeClass('loading');
                alert('Error: ' + xhr.responseText);
                console.error(xhr);
            }
        });
    }

    window.onclick = function(event) {
        const modal = document.getElementById('popupModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>