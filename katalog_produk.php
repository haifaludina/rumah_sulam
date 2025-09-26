<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

include '../koneksi.php';

$nama_produk = "";
$deskripsi = "";
$harga = "";
$stok = "";
$kategori = "";
$subkategori = "";
$gambar = "";
$id_produk = "";
$error = "";

function uploadGambar() {
    $targetDir = "../uploads/produk/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($_FILES["gambar"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (in_array($fileType, $allowTypes)) {
        if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFilePath)) {
            return $targetFilePath;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

if (isset($_POST['tambah_produk'])) {
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $subkategori = mysqli_real_escape_string($koneksi, $_POST['subkategori']);
    
    $gambar_path = "";
    if (!empty($_FILES["gambar"]["name"])) {
        $gambar_path = uploadGambar();
        if (!$gambar_path) {
            $error = "Format file tidak didukung atau terjadi kesalahan saat upload.";
        }
    }
    
    if (!isset($error) || $error === "") {
        $query = "INSERT INTO produk (nama_produk, deskripsi, harga, stok, gambar, id_kategori, id_subkategori) 
                  VALUES ('$nama_produk', '$deskripsi', '$harga', '$stok', '$gambar_path', '$kategori', '$subkategori')";
        
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success'] = "Produk berhasil ditambahkan!";
            header("Location: katalog_produk.php");
            exit();
        } else {
            $error = "Error: " . $query . "<br>" . mysqli_error($koneksi);
        }
    }
}

if (isset($_POST['edit_produk'])) {
    $id_produk = mysqli_real_escape_string($koneksi, $_POST['id_produk']);
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $subkategori = mysqli_real_escape_string($koneksi, $_POST['subkategori']);
    
    if (!empty($_FILES["gambar"]["name"])) {
        $gambar_path = uploadGambar();
        if (!$gambar_path) {
            $error = "Format file tidak didukung atau terjadi kesalahan saat upload.";
        } else {
            $query = "UPDATE produk SET 
                      nama_produk = '$nama_produk', 
                      deskripsi = '$deskripsi', 
                      harga = '$harga', 
                      stok = '$stok',
                      id_kategori = '$kategori',
                      id_subkategori = '$subkategori',
                      gambar = '$gambar_path' 
                      WHERE id_produk = '$id_produk'";
        }
    } else {
        $query = "UPDATE produk SET 
                  nama_produk = '$nama_produk', 
                  deskripsi = '$deskripsi', 
                  harga = '$harga', 
                  stok = '$stok',
                  id_kategori = '$kategori',
                  id_subkategori = '$subkategori'
                  WHERE id_produk = '$id_produk'";
    }
    
    if (!isset($error) || $error === "") {
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success'] = "Data produk berhasil diperbarui!";
            header("Location: katalog_produk.php");
            exit();
        } else {
            $error = "Error: " . $query . "<br>" . mysqli_error($koneksi);
        }
    }
}

if (isset($_POST['hapus_produk'])) {
    $id_produk = mysqli_real_escape_string($koneksi, $_POST['id_produk']);
    
    $query_gambar = "SELECT gambar FROM produk WHERE id_produk = '$id_produk'";
    $result_gambar = mysqli_query($koneksi, $query_gambar);
    
    if ($result_gambar && mysqli_num_rows($result_gambar) > 0) {
        $row = mysqli_fetch_assoc($result_gambar);
        $gambar_path = $row['gambar'];
        
        if (!empty($gambar_path) && file_exists($gambar_path)) {
            unlink($gambar_path);
        }
    }
    
    $query = "DELETE FROM produk WHERE id_produk = '$id_produk'";
    
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['success'] = "Produk berhasil dihapus!";
        header("Location: katalog_produk.php");
        exit();
    } else {
        $error = "Error: " . $query . "<br>" . mysqli_error($koneksi);
    }
}

$query_kategori = "SELECT * FROM kategori ORDER BY nama";
$result_kategori = mysqli_query($koneksi, $query_kategori);

$query_subkategori = "SELECT * FROM subkategori ORDER BY nama";
$result_subkategori = mysqli_query($koneksi, $query_subkategori);

$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

$query_produk = "SELECT p.*, k.nama as nama_kategori, s.nama as nama_subkategori 
                FROM produk p 
                LEFT JOIN kategori k ON p.id_kategori = k.id 
                LEFT JOIN subkategori s ON p.id_subkategori = s.id";
                
if (!empty($filter_kategori)) {
    $query_produk .= " WHERE p.id_kategori = '$filter_kategori'";
}

$query_produk .= " ORDER BY p.id_produk";

$results_per_page = 10;
$result_count = mysqli_query($koneksi, str_replace('p.*', 'COUNT(*) as total', $query_produk));
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $results_per_page);

$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($current_page-1) * $results_per_page;

$query_produk .= " LIMIT $start_from, $results_per_page";
$result_produk = mysqli_query($koneksi, $query_produk);

if (!$result_produk || !$result_kategori || !$result_subkategori) {
    $error = "Error: " . mysqli_error($koneksi);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - Rumah Sulam Sefni</title>
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
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-box h2 {
            border-bottom: none;
            margin-bottom: 20px;
            padding-bottom: 10;
            color: #333;
            border-bottom: 2px solid #f8a100;
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
            width: 80%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.4s;
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
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
            font-weight: bold;
            vertical-align: middle;
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
        
        .product-image {
            max-width: 100px;
            max-height: 100px;
            border: 1px solid #ddd;
        }
        
        .img-thumbnail {
            max-height: 200px;
            border: 1px solid #ddd;
            padding: 3px;
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
        
        .alert-dismissible {
            position: relative;
            padding-right: 35px;
        }
        
        .alert-dismissible .close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: inherit;
            font-size: 20px;
        }
        
        .mb-3 {
            margin-bottom: 15px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .action-link {
            color: #f8a100;
            text-decoration: none;
            font-weight: 500;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .section-title {
            margin: 30px 0 20px;
            color: #333;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            color: #5D4037;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 4px;
        }
        
        .pagination a.active {
            background-color: #5D4037;
            color: white;
            border: 1px solid #5D4037;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        .combined-column {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .product-name {
            font-weight: bold;
            color: #5D4037;
        }
        
        .product-desc {
            font-size: 14px;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .product-image-container {
            margin-top: 8px;
        }
        
        .total-products {
            background-color: #5D4037;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Rumah Sulam Sefni</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="katalog_produk.php" class="active">Katalog Produk</a>
    <a href="kelola_pesanan.php">Kelola Pesanan</a>
    <a href="kelola_retur.php">Kelola Retur Produk</a>
    <a href="kelola_galeri.php">Kelola Galeri</a>
    <a href="kelola_user.php">Kelola User</a>
    <a href="profil.php">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="welcome-box">
        <h2>Katalog Produk</h2>
        <p>Selamat datang di manajemen katalog produk Rumah Sulam Sefni.</p>
        <button onclick="tampilModal('tambahProdukModal')" class="mb-3">Tambah Katalog</button>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible">
            <?= $_SESSION['success']; ?>
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($error) && !empty($error)): ?>
        <div class="alert alert-danger alert-dismissible">
            <?= $error; ?>
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <div class="filter-container">
            <div class="filter-group">
                <label for="filter-kategori">Urut Berdasarkan:</label>
                <select id="filter-kategori" onchange="filterByCategory()">
                    <option value="">Semua Kategori</option>
                    <?php 
                    if ($result_kategori && mysqli_num_rows($result_kategori) > 0): 
                        while ($row = mysqli_fetch_assoc($result_kategori)):
                    ?>
                    <option value="<?= $row['id']; ?>" <?= ($filter_kategori == $row['id']) ? 'selected' : ''; ?>>
                        <?= $row['nama']; ?>
                    </option>
                    <?php 
                        endwhile;
                        mysqli_data_seek($result_kategori, 0);
                    endif;
                    ?>
                </select>
            </div>
            
            <div class="total-products">
                Total Produk: <?= $total_records; ?>
            </div>
        </div>
        
        <h3 class="section-title">Daftar Produk</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Kategori</th>
                    <th>SubKategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = $start_from + 1;
                if ($result_produk && mysqli_num_rows($result_produk) > 0): 
                    while ($row = mysqli_fetch_assoc($result_produk)):
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td>
                        <div class="combined-column">
                            <span class="product-name"><?= htmlspecialchars($row['nama_produk']); ?></span>
                            <span class="product-desc"><?= htmlspecialchars($row['deskripsi']); ?></span>
                            <?php if (!empty($row['gambar'])): ?>
                            <div class="product-image-container">
                                <img src="<?= $row['gambar']; ?>" class="product-image" alt="<?= htmlspecialchars($row['nama_produk']); ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td><?= $row['stok']; ?></td>
                    <td><?= htmlspecialchars($row['nama_kategori'] ?? 'Tidak ada kategori'); ?></td>
                    <td><?= htmlspecialchars($row['nama_subkategori'] ?? 'Tidak ada subkategori'); ?></td>
                    <td>
                        <button onclick="editProduk(<?= $row['id_produk']; ?>)">Edit</button>
                        <button onclick="hapusProduk(<?= $row['id_produk']; ?>)" class="btn-danger">Delete</button>
                    </td>
                </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data produk</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=1<?= !empty($filter_kategori) ? '&kategori='.$filter_kategori : '' ?>">&laquo;</a>
                <a href="?page=<?= $current_page-1 ?><?= !empty($filter_kategori) ? '&kategori='.$filter_kategori : '' ?>">&lt;</a>
            <?php endif; ?>
            
            <?php 
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <a href="?page=<?= $i ?><?= !empty($filter_kategori) ? '&kategori='.$filter_kategori : '' ?>" <?= ($i == $current_page) ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?= $current_page+1 ?><?= !empty($filter_kategori) ? '&kategori='.$filter_kategori : '' ?>">&gt;</a>
                <a href="?page=<?= $total_pages ?><?= !empty($filter_kategori) ? '&kategori='.$filter_kategori : '' ?>">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="tambahProdukModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('tambahProdukModal')">&times;</span>
        <h3>Tambah Katalog Produk</h3>
        <form action="katalog_produk.php" method="post" enctype="multipart/form-data">
            <label>Nama Produk:</label>
            <input type="text" name="nama_produk" required>
            
            <label>Deskripsi:</label>
            <textarea name="deskripsi" rows="3"></textarea>
            
            <label>Harga:</label>
            <input type="number" name="harga" required>
            
            <label>Stok:</label>
            <input type="number" name="stok" required>
            
            <label>Kategori:</label>
            <select name="kategori" id="kategori" onchange="loadSubkategori(this.value)" required>
                <option value="">-- Pilih Kategori --</option>
                <?php 
                if ($result_kategori && mysqli_num_rows($result_kategori) > 0): 
                    while ($row = mysqli_fetch_assoc($result_kategori)):
                ?>
                <option value="<?= $row['id']; ?>"><?= $row['nama']; ?></option>
                <?php 
                    endwhile;
                endif;
                ?>
            </select>
            
            <label>Subkategori:</label>
            <select name="subkategori" id="subkategori" required>
                <option value="">-- Pilih Subkategori --</option>
                <?php 
                if ($result_subkategori && mysqli_num_rows($result_subkategori) > 0): 
                    while ($row = mysqli_fetch_assoc($result_subkategori)):
                ?>
                <option value="<?= $row['id']; ?>" data-kategori="<?= $row['id_kategori']; ?>" class="subkategori-option" style="display:none;"><?= $row['nama']; ?></option>
                <?php 
                    endwhile;
                endif;
                ?>
            </select>
            
            <label>Gambar:</label>
            <input type="file" name="gambar">
            
            <button type="submit" name="tambah_produk">Simpan</button>
        </form>
    </div>
</div>

<div id="editProdukModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('editProdukModal')">&times;</span>
        <h3>Edit Produk</h3>
        <form action="katalog_produk.php" method="post" enctype="multipart/form-data" id="formEditProduk">
            <input type="hidden" id="edit_id_produk" name="id_produk">
            
            <label>Nama Produk:</label>
            <input type="text" id="edit_nama_produk" name="nama_produk" required>
            
            <label>Deskripsi:</label>
            <textarea id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
            
            <label>Harga:</label>
            <input type="number" id="edit_harga" name="harga" required>
            
            <label>Stok:</label>
            <input type="number" id="edit_stok" name="stok" required>
            
            <label>Kategori:</label>
            <select name="kategori" id="edit_kategori" onchange="loadEditSubkategori(this.value)" required>
                <option value="">-- Pilih Kategori --</option>
                <?php 
                mysqli_data_seek($result_kategori, 0);
                if ($result_kategori && mysqli_num_rows($result_kategori) > 0): 
                    while ($row = mysqli_fetch_assoc($result_kategori)):
                ?>
                <option value="<?= $row['id']; ?>"><?= $row['nama']; ?></option>
                <?php 
                    endwhile;
                endif;
                ?>
            </select>
            
            <label>Subkategori:</label>
            <select name="subkategori" id="edit_subkategori" required>
                <option value="">-- Pilih Subkategori --</option>
                <?php 
                mysqli_data_seek($result_subkategori, 0);
                if ($result_subkategori && mysqli_num_rows($result_subkategori) > 0): 
                    while ($row = mysqli_fetch_assoc($result_subkategori)):
                ?>
                <option value="<?= $row['id']; ?>" data-kategori="<?= $row['id_kategori']; ?>" class="edit-subkategori-option" style="display:none;"><?= $row['nama']; ?></option>
                <?php 
                    endwhile;
                endif;
                ?>
            </select>
            
            <div id="current_image_container" class="mb-3"></div>
            
            <label>Gambar (Upload baru untuk mengubah):</label>
            <input type="file" name="gambar">
            
            <button type="submit" name="edit_produk">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div id="hapusProdukModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('hapusProdukModal')">&times;</span>
        <h3>Konfirmasi Hapus</h3>
        <p>Anda yakin ingin menghapus produk ini?</p>
        <form action="katalog_produk.php" method="post" id="formHapusProduk">
            <input type="hidden" id="hapus_id_produk" name="id_produk">
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="tutupModal('hapusProdukModal')">Batal</button>
                <button type="submit" name="hapus_produk" class="btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function tampilModal(id) {
    document.getElementById(id).style.display = 'block';
}

function tutupModal(id) {
    document.getElementById(id).style.display = 'none';
}

function loadSubkategori(kategoriId) {
    const subkategoriOptions = document.querySelectorAll('.subkategori-option');
    subkategoriOptions.forEach(option => {
        option.style.display = 'none';
    });
    
    if (kategoriId) {
        const matchingOptions = document.querySelectorAll(`.subkategori-option[data-kategori="${kategoriId}"]`);
        matchingOptions.forEach(option => {
            option.style.display = '';
        });
    }
    
    document.getElementById('subkategori').value = '';
}

function loadEditSubkategori(kategoriId) {
    const subkategoriOptions = document.querySelectorAll('.edit-subkategori-option');
    subkategoriOptions.forEach(option => {
        option.style.display = 'none';
    });
    
    if (kategoriId) {
        const matchingOptions = document.querySelectorAll(`.edit-subkategori-option[data-kategori="${kategoriId}"]`);
        matchingOptions.forEach(option => {
            option.style.display = '';
        });
    }
    
    document.getElementById('edit_subkategori').value = '';
}

function editProduk(id) {
    const loadingHTML = '<p>Mengambil data produk...</p>';
    document.getElementById('current_image_container').innerHTML = loadingHTML;
    
    tampilModal('editProdukModal');
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_produk.php?id=' + id, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    
                    document.getElementById('edit_id_produk').value = data.id_produk;
                    document.getElementById('edit_nama_produk').value = data.nama_produk;
                    document.getElementById('edit_deskripsi').value = data.deskripsi;
                    document.getElementById('edit_harga').value = data.harga;
                    document.getElementById('edit_stok').value = data.stok;
                    
                    if (data.id_kategori) {
                        document.getElementById('edit_kategori').value = data.id_kategori;
                        loadEditSubkategori(data.id_kategori);
                        
                        if (data.id_subkategori) {
                            document.getElementById('edit_subkategori').value = data.id_subkategori;
                        }
                    }
                    
                    const container = document.getElementById('current_image_container');
                    container.innerHTML = '';
                    if (data.gambar) {
                        const img = document.createElement('img');
                        img.src = data.gambar;
                        img.className = 'img-thumbnail';
                        img.style.maxHeight = '200px';
                        container.appendChild(img);
                        
                        const p = document.createElement('p');
                        p.className = 'text-muted';
                        p.textContent = 'Gambar saat ini';
                        container.prepend(p);
                    } else {
                        const p = document.createElement('p');
                        p.className = 'text-muted';
                        p.textContent = 'Tidak ada gambar untuk produk ini';
                        container.appendChild(p);
                    }
                    
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    document.getElementById('current_image_container').innerHTML = '<p class="text-danger">Gagal memuat data produk</p>';
                }
            } else {
                document.getElementById('current_image_container').innerHTML = '<p class="text-danger">Gagal memuat data produk: ' + xhr.status + '</p>';
            }
        }
    };
    
    xhr.send();
}

function hapusProduk(id) {
    document.getElementById('hapus_id_produk').value = id;
    tampilModal('hapusProdukModal');
}

function filterByCategory() {
    const categoryId = document.getElementById('filter-kategori').value;
    if (categoryId) {
        window.location.href = 'katalog_produk.php?kategori=' + categoryId;
    } else {
        window.location.href = 'katalog_produk.php';
    }
}

window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
            modals[i].style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.getElementsByClassName('alert');
    for (let i = 0; i < alerts.length; i++) {
        setTimeout(function() {
            alerts[i].style.display = 'none';
        }, 5000);
    }
});
</script>
</body>
</html>