<?php
session_start();
$logged_in = isset($_SESSION['username']);

// Koneksi database
include '../koneksi.php';

// Ambil semua data galeri
$query_gallery = "SELECT * FROM galeri ORDER BY id DESC";
$result_gallery = mysqli_query($koneksi, $query_gallery);

// Definisi path upload - pastikan ini sama dengan yang di admin
$upload_dir = '../uploads/galeri/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri - Rumah Sulam Sefni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Style tetap sama */
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        /* Variabel Warna */
        :root {
            --primary-color: #6B4226;
            --secondary-color: #8B5A2B;
            --light-color: #F5F5F5;
            --dark-color: #333333;
            --grey-color: #EEEEEE;
            --text-color: #333333;
        }

        body {
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Header & Navigasi */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
            margin-right: 20px;
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

        nav ul li a:hover {
            color: var(--primary-color);
        }

        nav ul li a.active {
            color: var(--primary-color);
            font-weight: bold;
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .auth-btn {
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .signup-btn {
            background-color: var(--light-color);
            color: var(--text-color);
            border: 1px solid #ddd;
        }

        .login-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
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

        /* Page Header */
        .page-header {
            margin-top: 25px;
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 32px;
           color: #333;;
            margin-bottom: 10px;
        }

        .page-header p {
            ont-size: 14px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Galeri Content */
        .gallery-content {
            padding: 50px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }

        .gallery-item {
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: white;
        }

        .gallery-image {
            height: 250px;
            overflow: hidden;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover .gallery-image img {
            transform: scale(1.05);
        }

        .gallery-info {
            padding: 15px;
        }

        .gallery-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .gallery-info p {
            font-size: 14px;
            color: #666;
        }

        .gallery-date {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 4px;
            background-color: var(--light-color);
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a.active {
            background-color: var(--primary-color);
            color: white;
        }

        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }

        /* Footer */
        footer {
            padding: 30px 50px;
            background-color: white;
            text-align: center;
            border-top: 1px solid #eee;
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

        /* Responsive */
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
            
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header,
            .gallery-content {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Updated Navbar to match index.php -->
    <header>
        <a href="index.php" class="logo-container">
            <img src="../uploads/logo-sefni.png" alt="Logo" class="logo">
            <span class="logo-text">Rumah Sulam Sefni</span>
        </a>
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="katalog_produk.php">Katalog Produk</a></li>
                <li><a href="kustomisasi.php">Kustomisasi Sulaman</a></li>
                <li><a href="galeri.php" class="active">Galeri</a></li>
                <li><a href="tentang_kami.php">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
            <?php if($logged_in): ?>
            <div class="user-icons">
                <a href="keranjang.php"><i class="fas fa-shopping-cart"></i></a>
                <a href="profil.php"><i class="fas fa-user"></i></a>
            </div>
            <?php else: ?>
            <div class="auth-buttons">
                <a href="../daftar.php" class="auth-btn signup-btn">Daftar</a>
                <a href="../login.php" class="auth-btn login-btn">Masuk</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <section class="page-header">
        <h1>Galeri Karya</h1>
        <p>Kumpulan karya sulaman tradisional Minangkabau dari Rumah Sulam Sefni</p>
    </section>

    <section class="gallery-content">
        <div class="gallery-grid">
            <?php 
            if ($result_gallery && mysqli_num_rows($result_gallery) > 0): 
                while ($row = mysqli_fetch_assoc($result_gallery)):
                    // PENTING: Menggunakan nama kolom yang benar dan menambahkan path folder
                    $gambar_path = $upload_dir . $row['foto']; // Ganti 'gambar' menjadi 'foto'
                    $judul = htmlspecialchars($row['judul'] ?? 'Karya Sulaman');
                    $deskripsi = htmlspecialchars($row['deskripsi'] ?? 'Sulaman tradisional dari Rumah Sulam Sefni');
                    $tanggal = !empty($row['tanggal']) ? date('d F Y', strtotime($row['tanggal'])) : date('d F Y');
            ?>
            <div class="gallery-item">
                <div class="gallery-image">
                    <?php if (!empty($row['foto'])): // Ganti 'gambar' menjadi 'foto' ?>
                        <img src="<?= $gambar_path; ?>" alt="<?= $judul; ?>" onerror="this.src='../uploads/galeri/default.jpg'">
                    <?php else: ?>
                        <img src="../uploads/galeri/default.jpg" alt="Default Gallery Image">
                    <?php endif; ?>
                </div>
                <div class="gallery-info">
                    <h3><?= $judul; ?></h3>
                    <p><?= $deskripsi; ?></p>
                    <div class="gallery-date"><?= $tanggal; ?></div>
                </div>
            </div>
            <?php 
                endwhile; 
            else: 
                // Jika tidak ada data galeri di database, tampilkan hardcoded placeholders
                for($i = 1; $i <= 9; $i++):
            ?>
            <div class="gallery-item">
                <div class="gallery-image">
                    <img src="../uploads/galeri/default.jpg" alt="Galeri <?= $i; ?>">
                </div>
                <div class="gallery-info">
                    <h3>Karya Sulaman #<?= $i; ?></h3>
                    <p>Sulaman tradisional Minangkabau dengan motif etnik khas.</p>
                    <div class="gallery-date"><?= date('d F Y'); ?></div>
                </div>
            </div>
            <?php 
                endfor;
            endif; 
            ?>
        </div>

        <div class="pagination">
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#">&raquo;</a>
        </div>
    </section>

    <!-- Updated Footer -->
    <footer>
        <div class="social-contact">
            <div class="find-us">Temukan kami di:</div>
            <div class="social-icons">
                <a href="https://www.instagram.com/rumahsulam_sefni/" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/sefni.akhirda.3" target="_blank"><i class="fab fa-facebook"></i></a>
                <a href="https://wa.me/6281234567890" target="_blank"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="copyright">
            &copy; 2025 Rumah Sulam Sefni. Hak Cipta Dilindungi
        </div>
    </footer>
</body>
</html>