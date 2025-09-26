<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../koneksi.php';

function safeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        echo "<!-- Error MySQL: " . mysqli_error($conn) . " -->";
        return false;
    }
    return $result;
}

// Query untuk statistik dashboard
$total_pesanan = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as total FROM pesanan"))['total'];
$pesanan_baru = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as baru FROM pesanan WHERE status = 'Menunggu'"))['baru'];
$pesanan_dikirim = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as dikirim FROM pesanan WHERE status = 'Dikirim'"))['dikirim'];
$pesanan_diproses = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as diproses FROM pesanan WHERE status = 'Diproses'"))['diproses'];
$pesanan_selesai = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as selesai FROM pesanan WHERE status = 'Selesai'"))['selesai'];
$pesanan_retur = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as retur FROM retur"))['retur'];
$total_user = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as total_user FROM user"))['total_user'];
$total_produk = mysqli_fetch_assoc(safeQuery($koneksi, "SELECT COUNT(*) as total_produk FROM produk"))['total_produk'];

// Query untuk chart penjualan bulanan
$chart_data_rupiah = [];
$chart_data_jumlah = [];
$chart_labels = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_rupiah = mysqli_fetch_assoc(safeQuery($koneksi, 
        "SELECT SUM(total_harga) as total_rupiah FROM pesanan 
         WHERE DATE_FORMAT(tanggal_pesan, '%Y-%m') = '$month' 
         AND status != 'Dibatalkan' AND status != 'Retur'"))['total_rupiah'] ?? 0;
    $monthly_jumlah = mysqli_fetch_assoc(safeQuery($koneksi, 
        "SELECT COUNT(*) as total FROM pesanan 
         WHERE DATE_FORMAT(tanggal_pesan, '%Y-%m') = '$month' 
         AND status != 'Dibatalkan' AND status != 'Retur'"))['total'] ?? 0;
    
    $chart_data_rupiah[] = (int)$monthly_rupiah;
    $chart_data_jumlah[] = $monthly_jumlah;
    $chart_labels[] = date('M Y', strtotime($month . '-01'));
}

// Query untuk chart status pesanan
$status_labels = ['Menunggu', 'Diproses', 'Dikirim', 'Diterima', 'Selesai', 'Dibatalkan'];
$status_data = [];
foreach ($status_labels as $status) {
    $status_count = mysqli_fetch_assoc(safeQuery($koneksi, 
        "SELECT COUNT(*) as total FROM pesanan WHERE status = '$status'"))['total'] ?? 0;
    $status_data[] = $status_count;
}

function getStatusClass($status) {
    switch ($status) {
        case 'Menunggu': return 'status-waiting';
        case 'Diproses': return 'status-process';
        case 'Dikirim': return 'status-shipped';
        case 'Selesai': return 'status-completed';
        case 'Dibatalkan': return 'status-cancelled';
        case 'Retur': return 'status-retur';
        default: return '';
    }
}

// Fungsi untuk menampilkan tabel
function displayTable($query, $columns, $isOrderTable = true) {
    if ($query && mysqli_num_rows($query) > 0) {
        echo '<table><thead><tr>';
        foreach ($columns as $column) {
            echo '<th>'.$column['title'].'</th>';
        }
        echo '</tr></thead><tbody>';
        
        while($row = mysqli_fetch_assoc($query)) {
            echo '<tr>';
            foreach ($columns as $column) {
                echo '<td>';
                if (isset($column['format'])) {
                    switch ($column['format']) {
                        case 'date':
                            // Handle null dates
                            if (!empty($row[$column['field']])) {
                                echo date('d/m/Y H:i', strtotime($row[$column['field']]));
                            } else {
                                echo '-';
                            }
                            break;
                        case 'currency':
                            echo 'Rp '.number_format($row[$column['field']], 0, ',', '.');
                            break;
                        case 'status':
                            echo '<span class="'.getStatusClass($row[$column['field']]).'">'.$row[$column['field']].'</span>';
                            break;
                        default:
                            echo htmlspecialchars($row[$column['field']] ?? '-');
                    }
                } else {
                    echo htmlspecialchars($row[$column['field']] ?? '-');
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="no-data">Tidak ada data</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Rumah Sulam Sefni</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        
        .dashboard-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 30px 0;
            justify-content: center;
        }
        
        .stat-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 220px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #555;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #111;
            margin-bottom: 10px;
        }
        
        .stat-card a.toggle-table {
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
            color: #f8a100;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .stat-card a.toggle-table:hover {
            color: #e69100;
            text-decoration: underline;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        
        .chart-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chart-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .table-container {
            margin: 30px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-x: auto;
        }
        
        .hidden-table {
            display: none;
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
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .status-waiting {
            color: #FF9800;
            font-weight: bold;
        }
        
        .status-process {
            color: #2196F3;
            font-weight: bold;
        }
        
        .status-shipped {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .status-completed {
            color: #607D8B;
            font-weight: bold;
        }
        
        .status-cancelled {
            color: #F44336;
            font-weight: bold;
        }
        
        .status-retur {
            color: #9C27B0;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 40px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                flex-direction: column;
            }
            
            .stat-card {
                min-width: auto;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Rumah Sulam Sefni</h3>
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="katalog_produk.php">Katalog Produk</a>
    <a href="kelola_pesanan.php">Kelola Pesanan</a>
    <a href="kelola_retur.php">Kelola Retur Produk</a>
    <a href="kelola_galeri.php">Kelola Galeri</a>
    <a href="kelola_user.php">Kelola User</a>
    <a href="profil.php">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="welcome-box">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Selamat Datang di Dashboard Admin Rumah Sulam Sefni.</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Total Pesanan</h3>
            <div class="number"><?php echo $total_pesanan; ?></div>
            <a href="#" class="toggle-table" data-target="all-orders">Lihat Semua Pesanan</a>
        </div>
        
        <div class="stat-card">
            <h3>Pesanan Baru</h3>
            <div class="number"><?php echo $pesanan_baru; ?></div>
            <a href="#" class="toggle-table" data-target="waiting-orders">Lihat Pesanan Menunggu</a>
        </div>
        
        <div class="stat-card">
            <h3>Sedang Diproses</h3>
            <div class="number"><?php echo $pesanan_diproses; ?></div>
            <a href="#" class="toggle-table" data-target="process-orders">Lihat Pesanan Diproses</a>
        </div>
        
        <div class="stat-card">
            <h3>Sedang Dikirim</h3>
            <div class="number"><?php echo $pesanan_dikirim; ?></div>
            <a href="#" class="toggle-table" data-target="shipped-orders">Lihat Pesanan Dikirim</a>
        </div>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Pesanan Selesai</h3>
            <div class="number"><?php echo $pesanan_selesai; ?></div>
            <a href="#" class="toggle-table" data-target="completed-orders">Lihat Pesanan Selesai</a>
        </div>
        
        <div class="stat-card">
            <h3>Pesanan Retur</h3>
            <div class="number"><?php echo $pesanan_retur; ?></div>
            <a href="#" class="toggle-table" data-target="return-orders">Lihat Pesanan Retur</a>
        </div>
        
        <div class="stat-card">
            <h3>Total User</h3>
            <div class="number"><?php echo $total_user; ?></div>
            <a href="#" class="toggle-table" data-target="user-list">Lihat Daftar User</a>
        </div>
        
        <div class="stat-card">
            <h3>Total Produk</h3>
            <div class="number"><?php echo $total_produk; ?></div>
            <a href="#" class="toggle-table" data-target="product-list">Lihat Daftar Produk</a>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-card">
            <h3>Grafik Penjualan 12 Bulan Terakhir</h3>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <h3>Status Pesanan</h3>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Hidden Tables Section -->
    <div id="all-orders" class="table-container hidden-table">
        <h3>Semua Pesanan</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT p.*, pl.nama_pelanggan FROM pesanan p LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan ORDER BY p.tanggal_pesan DESC LIMIT 10");
        $columns = [
            ['title' => 'ID Pesanan', 'field' => 'id_pesanan'],
            ['title' => 'Nama Pelanggan', 'field' => 'nama_pelanggan'],
            ['title' => 'Tanggal Pesan', 'field' => 'tanggal_pesan', 'format' => 'date'],
            ['title' => 'Total Harga', 'field' => 'total_harga', 'format' => 'currency'],
            ['title' => 'Status', 'field' => 'status', 'format' => 'status']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="waiting-orders" class="table-container hidden-table">
        <h3>Pesanan Menunggu</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT p.*, pl.nama_pelanggan FROM pesanan p LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan WHERE p.status = 'Menunggu' ORDER BY p.tanggal_pesan DESC");
        $columns = [
            ['title' => 'ID Pesanan', 'field' => 'id_pesanan'],
            ['title' => 'Nama Pelanggan', 'field' => 'nama_pelanggan'],
            ['title' => 'Tanggal Pesan', 'field' => 'tanggal_pesan', 'format' => 'date'],
            ['title' => 'Total Harga', 'field' => 'total_harga', 'format' => 'currency'],
            ['title' => 'Status', 'field' => 'status', 'format' => 'status']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="process-orders" class="table-container hidden-table">
        <h3>Pesanan Diproses</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT p.*, pl.nama_pelanggan FROM pesanan p LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan WHERE p.status = 'Diproses' ORDER BY p.tanggal_pesan DESC");
        $columns = [
            ['title' => 'ID Pesanan', 'field' => 'id_pesanan'],
            ['title' => 'Nama Pelanggan', 'field' => 'nama_pelanggan'],
            ['title' => 'Tanggal Pesan', 'field' => 'tanggal_pesan', 'format' => 'date'],
            ['title' => 'Total Harga', 'field' => 'total_harga', 'format' => 'currency'],
            ['title' => 'Status', 'field' => 'status', 'format' => 'status']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="shipped-orders" class="table-container hidden-table">
        <h3>Pesanan Dikirim</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT p.*, pl.nama_pelanggan FROM pesanan p LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan WHERE p.status = 'Dikirim' ORDER BY p.tanggal_pesan DESC");
        $columns = [
            ['title' => 'ID Pesanan', 'field' => 'id_pesanan'],
            ['title' => 'Nama Pelanggan', 'field' => 'nama_pelanggan'],
            ['title' => 'Tanggal Pesan', 'field' => 'tanggal_pesan', 'format' => 'date'],
            ['title' => 'Total Harga', 'field' => 'total_harga', 'format' => 'currency'],
            ['title' => 'Status', 'field' => 'status', 'format' => 'status']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="completed-orders" class="table-container hidden-table">
        <h3>Pesanan Selesai</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT p.*, pl.nama_pelanggan FROM pesanan p LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan WHERE p.status = 'Selesai' ORDER BY p.tanggal_pesan DESC");
        $columns = [
            ['title' => 'ID Pesanan', 'field' => 'id_pesanan'],
            ['title' => 'Nama Pelanggan', 'field' => 'nama_pelanggan'],
            ['title' => 'Tanggal Pesan', 'field' => 'tanggal_pesan', 'format' => 'date'],
            ['title' => 'Total Harga', 'field' => 'total_harga', 'format' => 'currency'],
            ['title' => 'Status', 'field' => 'status', 'format' => 'status']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="return-orders" class="table-container hidden-table">
        <h3>Pesanan Retur</h3>
        <?php 
        $query = safeQuery($koneksi, "
            SELECT 
                r.id_retur,
                r.id_pesanan,
                r.tanggal_pengajuan,
                r.alasan,
                r.status,
                pl.nama_pelanggan,
                p.tanggal_pesan
            FROM retur r
            LEFT JOIN pesanan p ON r.id_pesanan = p.id_pesanan
            LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
            ORDER BY r.tanggal_pengajuan DESC
        ");
        $columns = [
            ['title' => 'ID Retur', 'field' => 'id_retur'],
            ['title' => 'ID Pesanan', 'field' => 'id_pesanan'],
            ['title' => 'Nama Pelanggan', 'field' => 'nama_pelanggan'],
            ['title' => 'Tanggal Pengajuan', 'field' => 'tanggal_pengajuan', 'format' => 'date'],
            ['title' => 'Alasan', 'field' => 'alasan'],
            ['title' => 'Status', 'field' => 'status', 'format' => 'status']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="user-list" class="table-container hidden-table">
        <h3>Daftar User</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT * FROM user ORDER BY id_user DESC LIMIT 10");
        $columns = [
            ['title' => 'ID User', 'field' => 'id_user'],
            ['title' => 'Username', 'field' => 'username'],
            ['title' => 'Email', 'field' => 'email'],
            ['title' => 'Role', 'field' => 'role']
        ];
        displayTable($query, $columns);
        ?>
    </div>

    <div id="product-list" class="table-container hidden-table">
        <h3>Daftar Produk</h3>
        <?php 
        $query = safeQuery($koneksi, "SELECT * FROM produk ORDER BY id_produk DESC LIMIT 10");
        $columns = [
            ['title' => 'ID Produk', 'field' => 'id_produk'],
            ['title' => 'Nama Produk', 'field' => 'nama_produk'],
            ['title' => 'Harga', 'field' => 'harga', 'format' => 'currency'],
            ['title' => 'Stok', 'field' => 'stok']
        ];
        displayTable($query, $columns);
        ?>
    </div>
</div>

<script>
// Format angka ke Rupiah
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);
}

// Chart untuk penjualan bulanan
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Penjualan (Rp)',
            data: <?php echo json_encode($chart_data_rupiah); ?>,
            borderColor: '#f8a100',
            backgroundColor: 'rgba(248, 161, 0, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#f8a100',
            pointBorderColor: '#e69100',
            pointBorderWidth: 2,
            pointRadius: 5,
            yAxisID: 'y'
        }, {
            label: 'Jumlah Pesanan',
            data: <?php echo json_encode($chart_data_jumlah); ?>,
            borderColor: '#2196F3',
            backgroundColor: 'rgba(33, 150, 243, 0.1)',
            borderWidth: 2,
            fill: false,
            tension: 0.4,
            pointBackgroundColor: '#2196F3',
            pointBorderColor: '#1976D2',
            pointBorderWidth: 2,
            pointRadius: 4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.datasetIndex === 0) {
                            return 'Penjualan: ' + formatRupiah(context.parsed.y);
                        } else {
                            return 'Jumlah Pesanan: ' + context.parsed.y;
                        }
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Bulan'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Penjualan (Rp)',
                    color: '#f8a100'
                },
                ticks: {
                    callback: function(value) {
                        return formatRupiah(value);
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Jumlah Pesanan',
                    color: '#2196F3'
                },
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Chart untuk status pesanan
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_data); ?>,
            backgroundColor: [
                '#FF9800', // Menunggu
                '#2196F3', // Diproses
                '#4CAF50', // Dikirim
                '#20c997', // Diterima (tambahkan warna hijau tosca)
                '#607D8B', // Selesai
                '#F44336'  // Dibatalkan
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
    }
});

// Toggle table functionality
$(document).ready(function() {
    $('.toggle-table').click(function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        
        // Hide all tables first
        $('.table-container').addClass('hidden-table');
        
        // Show selected table
        $('#' + target).toggleClass('hidden-table');
        
        // Scroll to the table
        $('html, body').animate({
            scrollTop: $('#' + target).offset().top - 20
        }, 500);
    });
});
</script>

</body>
</html>