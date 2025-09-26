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

$query_pesanan = "SELECT * FROM pesanan WHERE id_pesanan = ? AND id_pelanggan = ? AND (status = 'dikirim' OR status = 'selesai')";
$stmt_pesanan = $koneksi->prepare($query_pesanan);
$stmt_pesanan->bind_param("ii", $id_pesanan, $id_pelanggan);
$stmt_pesanan->execute();
$result_pesanan = $stmt_pesanan->get_result();

if ($result_pesanan->num_rows === 0) {
    header('Location: pesanan_saya.php');
    exit();
}

$pesanan = $result_pesanan->fetch_assoc();

$query_items = "SELECT ip.*, p.nama_produk, p.gambar FROM item_pesanan ip
                JOIN produk p ON ip.id_produk = p.id_produk 
                WHERE ip.id_pesanan = ?";
$stmt_items = $koneksi->prepare($query_items);
$stmt_items->bind_param("i", $id_pesanan);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$items = $result_items->fetch_all(MYSQLI_ASSOC);

$query_custom_items = "SELECT * FROM detail_pesanan_kustom WHERE id_pesanan = ?";
$stmt_custom_items = $koneksi->prepare($query_custom_items);
$stmt_custom_items->bind_param("i", $id_pesanan);
$stmt_custom_items->execute();
$result_custom_items = $stmt_custom_items->get_result();
$custom_items = $result_custom_items->fetch_all(MYSQLI_ASSOC);

$all_items = array_merge($items, $custom_items);

$query_pelanggan = "SELECT * FROM pelanggan WHERE id_pelanggan = ?";
$stmt_pelanggan = $koneksi->prepare($query_pelanggan);
$stmt_pelanggan->bind_param("i", $id_pelanggan);
$stmt_pelanggan->execute();
$result_pelanggan = $stmt_pelanggan->get_result();
$pelanggan = $result_pelanggan->fetch_assoc();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alasan = $_POST['alasan'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    $jenis_retur = $_POST['jenis_retur'] ?? '';
    $nama_bank = $_POST['nama_bank'] ?? '';
    $nomor_rekening = $_POST['nomor_rekening'] ?? '';
    $nama_pemilik_rekening = $_POST['nama_pemilik_rekening'] ?? '';
    $nomor_resi_pengembalian = $_POST['nomor_resi_pengembalian'] ?? '';
    $tanggal_pengajuan = date('Y-m-d H:i:s');
    
    if (!isset($_POST['item_retur'])) {
        $error = "Pilih minimal satu item untuk diretur";
    } else {
        $item_retur = $_POST['item_retur'];
        $item_retur_json = json_encode($item_retur);
    }

    $bukti_foto = null;
    $bukti_video = null;
    
    if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/retur/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['bukti_foto']['name'], PATHINFO_EXTENSION);
        $filename = 'retur_foto_' . $id_pesanan . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['bukti_foto']['tmp_name'], $target_file)) {
            $bukti_foto = $filename;
        } else {
            $error = "Gagal mengunggah foto bukti retur.";
        }
    }

    if (isset($_FILES['bukti_video']) && $_FILES['bukti_video']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/retur/";
        $file_extension = pathinfo($_FILES['bukti_video']['name'], PATHINFO_EXTENSION);
        $filename = 'retur_video_' . $id_pesanan . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['bukti_video']['tmp_name'], $target_file)) {
            $bukti_video = $filename;
        } else {
            $error = "Gagal mengunggah video bukti retur.";
        }
    }

    if (!$error) {
        $query_insert = "INSERT INTO retur (id_pesanan, id_pelanggan, alasan, keterangan, bukti_foto, bukti_video, tanggal_pengajuan, nama_bank, nomor_rekening, nama_pemilik_rekening, nomor_resi_pengembalian, jenis_retur, item_retur, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Konfirmasi')";
        $stmt = $koneksi->prepare($query_insert);
        $stmt->bind_param(
            'iisssssssssss',
            $id_pesanan,
            $id_pelanggan,
            $alasan,
            $keterangan,
            $bukti_foto,
            $bukti_video,
            $tanggal_pengajuan,
            $nama_bank,
            $nomor_rekening,
            $nama_pemilik_rekening,
            $nomor_resi_pengembalian,
            $jenis_retur,
            $item_retur_json
        );

        if ($stmt->execute()) {
            // Setelah insert retur
            $update = $koneksi->prepare("UPDATE pesanan SET status = 'diterima' WHERE id_pesanan = ?");
            $update->bind_param("i", $id_pesanan);
            $update->execute();

            $_SESSION['success_message'] = "Pengajuan retur berhasil dikirim.";
            header("Location: pesanan_saya.php");
            exit();
        } else {
            $error = "Terjadi kesalahan saat menyimpan data retur. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Retur - Rumah Sulam Sefni</title>
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

        .retur-container {
            background-color: var(--white-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .retur-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .retur-form label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }

        .preview-video {
            max-width: 300px;
            margin-top: 10px;
            display: none;
        }

        .bank-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
            border: 1px solid #eee;
        }

        .item-retur-container {
            margin-bottom: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
        }

        .item-retur-checkbox {
            margin-right: 10px;
        }

        .item-retur-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }

        .item-retur-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
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
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($pelanggan['nama_pelanggan']) ?>&background=6d4c41&color=fff" alt="Profil" class="profile-avatar">
                        <h4 class="profile-name"><?= htmlspecialchars($pelanggan['nama_pelanggan']) ?></h4>
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
                            <li class="breadcrumb-item"><a href="detail_pesanan.php?id=<?= $id_pesanan ?>">Detail Pesanan</a></li>
                            <li class="breadcrumb-item active">Ajukan Retur</li>
                        </ol>
                    </nav>
                    
                    <div class="profile-header">
                        <h3>Ajukan Retur untuk Pesanan #<?= $id_pesanan ?></h3>
                        <p class="text-muted">Silakan isi form berikut untuk mengajukan retur produk</p>
                    </div>
                    
                    <div class="retur-container">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="retur-form">
                            <div class="mb-4">
                                <h5>Pilih Item yang Ingin Dikembalikan</h5>
                                <div class="item-retur-container">
                                    <?php if (count($all_items) > 0): ?>
                                        <?php foreach ($all_items as $item): ?>
                                            <div class="item-retur-info">
                                                <input type="checkbox" class="item-retur-checkbox" 
                                                       name="item_retur[]" 
                                                       value="<?php 
                                                           if (isset($item['id'])) {
                                                               echo 'item_'.$item['id'];
                                                           } else {
                                                               echo 'custom_'.$item['id_detail_kustom'];
                                                           }
                                                       ?>"
                                                       id="item_<?php 
                                                           if (isset($item['id'])) {
                                                               echo $item['id'];
                                                           } else {
                                                               echo $item['id_detail_kustom'];
                                                           }
                                                       ?>">
                                                <?php if (isset($item['gambar'])): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" class="item-retur-image">
                                                <?php else: ?>
                                                    <img src="../uploads/product-placeholder.jpg" alt="Product Image" class="item-retur-image">
                                                <?php endif; ?>
                                                <div>
                                                    <label for="item_<?php 
                                                        if (isset($item['id'])) {
                                                            echo $item['id'];
                                                        } else {
                                                            echo $item['id_detail_kustom'];
                                                        }
                                                    ?>">
                                                        <strong><?php echo htmlspecialchars($item['nama_produk']); ?></strong><br>
                                                        <small>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></small>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Tidak ada item dalam pesanan ini</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="jenis_retur" class="form-label">Jenis Retur</label>
                                <select class="form-select" id="jenis_retur" name="jenis_retur" required>
                                    <option value="" selected disabled>Pilih jenis retur</option>
                                    <option value="pengembalian_uang">Pengembalian Uang</option>
                                    <option value="pengembalian_barang">Pengembalian Barang (Penggantian)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="alasan" class="form-label">Alasan Retur</label>
                                <select class="form-select" id="alasan" name="alasan" required>
                                    <option value="" selected disabled>Pilih alasan retur</option>
                                    <option value="Produk Rusak">Produk Rusak</option>
                                    <option value="Produk Tidak Sesuai">Produk Tidak Sesuai</option>
                                    <option value="Pengiriman Salah">Pengiriman Salah</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan Tambahan</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="4" placeholder="Jelaskan detail alasan retur Anda" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="bukti_foto" class="form-label">Bukti Foto (Wajib)</label>
                                <input type="file" class="form-control" id="bukti_foto" name="bukti_foto" accept="image/*" required>
                                <small class="text-muted">Upload foto produk yang ingin diretur (maks. 2MB)</small>
                                <img id="imagePreview" src="#" alt="Preview Gambar" class="preview-image img-thumbnail">
                            </div>

                            <div class="mb-3">
                                <label for="bukti_video" class="form-label">Bukti Video (Wajib)</label>
                                <input type="file" class="form-control" id="bukti_video" name="bukti_video" accept="video/*">
                                <small class="text-muted">Upload video produk yang ingin diretur (maks. 10MB)</small>
                                <video id="videoPreview" src="#" controls class="preview-video img-thumbnail"></video>
                            </div>
                            
                            <div id="pengembalian_uang_form" class="bank-details">
                                <h5 class="mb-3">Informasi Rekening Pengembalian Dana</h5>
                                <div class="mb-3">
                                    <label for="nama_bank" class="form-label">Nama Bank</label>
                                    <input type="text" class="form-control" id="nama_bank" name="nama_bank" placeholder="Contoh: BCA, BNI, Mandiri">
                                </div>
                                <div class="mb-3">
                                    <label for="nomor_rekening" class="form-label">Nomor Rekening</label>
                                    <input type="text" class="form-control" id="nomor_rekening" name="nomor_rekening" placeholder="Masukkan nomor rekening Anda">
                                </div>
                                <div class="mb-3">
                                    <label for="nama_pemilik_rekening" class="form-label">Nama Pemilik Rekening</label>
                                    <input type="text" class="form-control" id="nama_pemilik_rekening" name="nama_pemilik_rekening" placeholder="Masukkan nama pemilik rekening">
                                </div>
                            </div>

                            <div id="pengembalian_barang_form" class="bank-details" style="display: none;">
                                <h5 class="mb-3">Informasi Pengembalian Barang</h5>
                                <div class="mb-3">
                                    <label for="nomor_resi_pengembalian" class="form-label">Nomor Resi Pengembalian</label>
                                    <input type="text" class="form-control" id="nomor_resi_pengembalian" name="nomor_resi_pengembalian" placeholder="Masukkan nomor resi pengiriman barang kembali">
                                    <small class="text-muted">Harap simpan bukti pengiriman barang kembali</small>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="detail_pesanan.php?id=<?= $id_pesanan ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i> Ajukan Retur
                                </button>
                            </div>
                        </form>
                    </div>
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
    <script>
        document.getElementById('bukti_foto').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        document.getElementById('bukti_video').addEventListener('change', function(e) {
            const preview = document.getElementById('videoPreview');
            const file = e.target.files[0];
            
            if (file) {
                const videoURL = URL.createObjectURL(file);
                preview.src = videoURL;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        document.getElementById('jenis_retur').addEventListener('change', function() {
            const jenisRetur = this.value;
            const uangForm = document.getElementById('pengembalian_uang_form');
            const barangForm = document.getElementById('pengembalian_barang_form');
            
            if (jenisRetur === 'pengembalian_uang') {
                uangForm.style.display = 'block';
                barangForm.style.display = 'none';
                
                document.getElementById('nama_bank').required = true;
                document.getElementById('nomor_rekening').required = true;
                document.getElementById('nama_pemilik_rekening').required = true;
                document.getElementById('nomor_resi_pengembalian').required = false;
            } else if (jenisRetur === 'pengembalian_barang') {
                uangForm.style.display = 'none';
                barangForm.style.display = 'block';
                
                document.getElementById('nama_bank').required = false;
                document.getElementById('nomor_rekening').required = false;
                document.getElementById('nama_pemilik_rekening').required = false;
                document.getElementById('nomor_resi_pengembalian').required = true;
            } else {
                uangForm.style.display = 'none';
                barangForm.style.display = 'none';
            }
        });
    </script>
</body>
</html>