<?php
session_start();
$logged_in = isset($_SESSION['username']);

// Koneksi database
include '../koneksi.php';

// Ambil produk unggulan (4 produk teratas)
$query_featured = "SELECT * FROM produk ORDER BY id_produk DESC LIMIT 4";
$result_featured = mysqli_query($koneksi, $query_featured);

// Ambil semua data galeri untuk slider
$query_gallery = "SELECT id, judul, foto AS gambar FROM galeri ORDER BY id DESC";
$result_gallery = mysqli_query($koneksi, $query_gallery);

// Simpan semua data galeri dalam array
$gallery_items = array();
if ($result_gallery && mysqli_num_rows($result_gallery) > 0) {
    while ($row = mysqli_fetch_assoc($result_gallery)) {
        $gallery_items[] = $row;
    }
}
?> 

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rumah Sulam Sefni - Sulaman Tradisional Minang Kabau</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .hero {
            position: relative;
            background-image: url('../uploads/rumah-gadang.jpg');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #333;
            padding: 60px 50px;
            text-align: left;
            color: white;
            z-index: 1;
            overflow: hidden;
            min-height: 400px;
        }

        /* Overlay hitam transparan */
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: hsla(0, 1.80%, 32.70%, 0.40);
            z-index: 0;
        }

        .hero * {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .hero p {
            font-size: 14px;
            max-width: 600px;
            margin-bottom: 20px;
            color:rgb(240, 236, 233);
        }

        .hero-buttons {
            display: flex;
            gap: 10px;
        }

        .hero-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .outline-btn {
            background-color: transparent;
            border: 1px solid rgb(240, 222, 201);
            color: rgb(240, 222, 201);
        }

        /* Featured Products */
        .featured-products {
            padding: 50px;
            text-align: center;
        }

        .featured-products h2 {
            font-size: 24px;
            margin-bottom: 30px;
            color: var(--text-color);
        }

        .product-grid {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .product-card {
            width: calc(25% - 20px);
            background-color: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .product-image {
            height: 220px;
            background-color: var(--grey-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 15px;
            text-align: left;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-info {
            background-color: #f9f5f0;
        }

        .product-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .product-price {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .product-btn {
            display: inline-block;
            padding: 6px 12px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-btn {
            background-color: var(--secondary-color);
        }

        /* About Section */
        .about-section {
            display: flex;
            padding: 50px;
            background-color: white;
            gap: 30px;
        }

        .about-image {
            flex: 1;
            height: 250px;
            background-color: var(--grey-color);
            overflow: hidden;
        }

        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .about-content {
            flex: 2;
        }

        .about-content h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .about-content p {
            margin-bottom: 15px;
            font-size: 14px;
            color: #555;
        }

        .read-more {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        /* Customization Section */
        .custom-section {
            padding: 50px;
            background-color: rgb(216, 200, 172);
            text-align: center;
        }

        .custom-section h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .custom-section p {
            max-width: 800px;
            margin: 0 auto 20px;
            font-size: 14px;
        }

        .custom-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Modern Gallery Section */
        .gallery-section {
            padding: 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
            background-color: #f9f5f0;
        }

        .gallery-section h2 {
            font-size: 24px;
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .gallery-container {
            width: 100%;
            overflow: hidden;
            position: relative;
            margin-bottom: 30px;
        }

        .gallery-slider {
            display: flex;
            transition: transform 0.5s ease;
            padding: 20px 0;
        }

        .gallery-item {
            min-width: calc(33.333% - 40px);
            height: 300px;
            background-color: white;
            overflow: hidden;
            border-radius: 8px;
            margin: 0 20px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .gallery-image {
            height: 70%;
            overflow: hidden;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
        }
        
        .gallery-item:hover .gallery-image img {
            transform: scale(1.1);
        }

        .gallery-caption {
            height: 30%;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: white;
        }

        .gallery-caption h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .gallery-caption p {
            font-size: 14px;
            color: #666;
            display: none;
        }

        .gallery-item:hover .gallery-caption p {
            display: block;
        }

        .slider-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .slider-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .slider-btn:hover {
            background-color: var(--secondary-color);
        }

        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ccc;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dot.active {
            background-color: var(--primary-color);
        }

        .view-all {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 30px;
        }

        /* Call to Action */
        .cta-section {
            padding: 70px 50px;
            background-color: rgb(211, 186, 141);
            text-align: center;
        }

        .cta-section h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .cta-section p {
            max-width: 800px;
            margin: 0 auto 30px;
            font-size: 14px;
        }

        .shop-now {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Header & Navigasi */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
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

        /* Responsive for Navbar and Footer */
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
            
            /* Responsive for Gallery */
            .gallery-item {
                min-width: calc(50% - 30px);
            }

            .product-card {
                width: calc(50% - 20px);
            }
        }

        @media (max-width: 480px) {
            .gallery-item {
                min-width: calc(100% - 40px);
            }

            .product-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar dari file pertama -->
    <header>
        <a href="index.php" class="logo-container">
            <img src="../uploads/logo-sefni.png" alt="Logo" class="logo">
            <span class="logo-text">Rumah Sulam Sefni</span>
        </a>
        <nav>
            <ul>
                <li><a href="index.php" class="active">Beranda</a></li>
                <li><a href="katalog_produk.php">Katalog Produk</a></li>
                <li><a href="kustomisasi.php">Kustomisasi Sulaman</a></li>
                <li><a href="galeri.php">Galeri</a></li>
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

    <section class="hero">
        <h1>Sulaman Tradisional<br>Minang Kabau by Rumah Sulam Sefni</h1>
        <p>Keindahan budaya Minangkabau dalam setiap jahitan.<br>Melestarikan tradisi, mengembangkan kreasi, membangun kualitas</p>
        <div class="hero-buttons">
            <a href="katalog_produk.php" class="hero-btn">Jelajahi Katalog</a>
            <a href="kustomisasi.php" class="hero-btn outline-btn">Kustomisasi Sulaman</a>
        </div>
    </section>

    <section class="featured-products">
        <h2>Produk Unggulan Kami</h2>
        <div class="product-grid">
            <?php 
            if ($result_featured && mysqli_num_rows($result_featured) > 0): 
                while ($row = mysqli_fetch_assoc($result_featured)):
            ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if (!empty($row['gambar'])): ?>
                        <img src="<?= $row['gambar']; ?>" alt="<?= htmlspecialchars($row['nama_produk']); ?>">
                    <?php else: ?>
                        <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($row['nama_produk']); ?></h3>
                    <p class="product-price">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></p>
                    <a href="detail_produk.php?id=<?= $row['id_produk']; ?>" class="product-btn">Lihat Detail</a>
                </div>
            </div>
            <?php 
                endwhile; 
            else: 
                // Jika tidak ada produk di database, tampilkan hardcoded placeholders
            ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                </div>
                <div class="product-info">
                    <h3>Kabaya Sulaman Kaluak Paku</h3>
                    <p class="product-price">Rp 650.000</p>
                    <a href="detail_produk.php?id=1" class="product-btn">Lihat Detail</a>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                </div>
                <div class="product-info">
                    <h3>Selendang Sulaman Itik Pulang Patang</h3>
                    <p class="product-price">Rp 350.000</p>
                    <a href="detail_produk.php?id=2" class="product-btn">Lihat Detail</a>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                </div>
                <div class="product-info">
                    <h3>Baju Kurung Sulam Floral</h3>
                    <p class="product-price">Rp 850.000</p>
                    <a href="detail_produk.php?id=3" class="product-btn">Lihat Detail</a>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                </div>
                <div class="product-info">
                    <h3>Mukena Sulam Premium</h3>
                    <p class="product-price">Rp 950.000</p>
                    <a href="detail_produk.php?id=4" class="product-btn">Lihat Detail</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="about-section">
        <div class="about-image">
            <img src="../uploads/tentang-kami.jpg" alt="Rumah Sulam Sefni">
        </div>
        <div class="about-content">
            <h2>Tentang Rumah Sulam Sefni</h2>
            <p>Rumah Sulam Sefni merupakan usaha mikro kecil menengah (UMKM) yang bergerak di bidang produksi dan penjualan sulaman tradisional etnis Minangkabau. Berdiri pada 1 September 2010 di Situmbuk, Kecamatan Salimpaung, Kabupaten Tanah Datar, Sumatra Barat, Rumah Sulam Sefni hadir sebagai wujud keresahan terhadap seni budaya lokal yang dianggap selalu kalah bersaing.</p>
            <p>Kami bekerja sama dengan sejumlah penjahit dan pengrajin untuk membantu melestarikan nilai-nilai budaya yang berharga. Melalui keterampilan para pengrajin lokal, kami menghadirkan berbagai produk sulaman berkualitas yang dipadukan dengan sentuhan modern. Selain itu, kami juga berperan aktif dalam memberikan pelatihan masyarakat, khususnya perempuan, agar dapat mandiri secara ekonomis melalui kerajinan sulaman.</p>
            <a href="tentang_kami.php" class="custom-btn">Baca Lebih Lanjut</a>
        </div>
    </section>

    <section class="custom-section">
        <h2>Buat Sulaman Sesuai Keinginan Anda</h2>
        <p>Kami menyediakan layanan kustomisasi produk sulaman yang memungkinkan Anda memilih jenis produk, warna bahan, hiasan, dan desain motif yang sesuai dengan keinginan Anda.</p>
        <a href="kustomisasi.php" class="custom-btn">Desain Kustomisasi</a>
    </section>

    <section class="gallery-section">
        <h2>Galeri Karya</h2>
        <div class="gallery-container">
            <div class="gallery-slider" id="gallerySlider">
                <?php 
                if (!empty($gallery_items)):
                    foreach ($gallery_items as $item):
                        $path_gambar = '../uploads/galeri/' . $item['gambar'];
                ?>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <?php if (!empty($item['gambar']) && file_exists($path_gambar)): ?>
                            <img src="<?= $path_gambar; ?>" alt="<?= htmlspecialchars($item['judul'] ?? 'Galeri Rumah Sulam Sefni'); ?>">
                        <?php else: ?>
                            <img src="../uploads/galeri/default.jpg" alt="Default Gallery Image">
                        <?php endif; ?>
                    </div>
                    <div class="gallery-caption">
                        <h3><?= htmlspecialchars($item['judul'] ?? 'Karya Sulaman'); ?></h3>
                        <p>Karya sulaman tradisional Minangkabau</p>
                    </div>
                </div>
                <?php 
                    endforeach;
                else:
                ?>
                <!-- Placeholder items if gallery is empty -->
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../uploads/galeri/galeri-1.jpg" alt="Galeri 1">
                    </div>
                    <div class="gallery-caption">
                        <h3>Kabaya Sulam Tradisional</h3>
                        <p>Dibuat dengan teknik sulaman tangan</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../uploads/galeri/galeri-2.jpg" alt="Galeri 2">
                    </div>
                    <div class="gallery-caption">
                        <h3>Selendang Sulam Modern</h3>
                        <p>Perpaduan tradisional dan kontemporer</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../uploads/galeri/galeri-3.jpg" alt="Galeri 3">
                    </div>
                    <div class="gallery-caption">
                        <h3>Mukena Sulam Premium</h3>
                        <p>Bahan katun berkualitas tinggi</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../uploads/galeri/galeri-4.jpg" alt="Galeri 4">
                    </div>
                    <div class="gallery-caption">
                        <h3>Tas Sulam Etnik</h3>
                        <p>Fungsional dengan nilai seni tinggi</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../uploads/galeri/galeri-5.jpg" alt="Galeri 5">
                    </div>
                    <div class="gallery-caption">
                        <h3>Baju Kurung Sulam</h3>
                        <p>Motif khas Minangkabau</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../uploads/galeri/galeri-6.jpg" alt="Galeri 6">
                    </div>
                    <div class="gallery-caption">
                        <h3>Sarung Bantal Sulam</h3>
                        <p>Mempercantik interior rumah</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="slider-controls">
            <button class="slider-btn" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
            <button class="slider-btn" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
        </div>
        
        <div class="slider-dots" id="sliderDots"></div>
        
        <a href="galeri.php" class="view-all">Lihat Semua Galeri</a>
    </section>

    <section class="cta-section">
        <h2>Dapatkan Produk Sulaman Berkualitas</h2>
        <p>Nikmati koleksi produk sulaman tradisional Minangkabau dari Rumah Sulam Sefni dan temukan keindahan budaya dalam setiap jahitan.</p>
        <a href="katalog_produk.php" class="shop-now">Belanja Sekarang</a>
    </section>

    <!-- Footer dari file pertama -->
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('gallerySlider');
            const items = document.querySelectorAll('.gallery-item');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const dotsContainer = document.getElementById('sliderDots');
            
            let currentIndex = 0;
            const itemsPerPage = 3;
            const itemWidth = items[0].offsetWidth + 40; // Lebar item + margin
            const totalItems = items.length;
            
            // Buat dots untuk slider
            for (let i = 0; i < Math.ceil(totalItems / itemsPerPage); i++) {
                const dot = document.createElement('div');
                dot.classList.add('slider-dot');
                if (i === 0) dot.classList.add('active');
                dot.addEventListener('click', () => {
                    goToSlide(i);
                });
                dotsContainer.appendChild(dot);
            }
            
            const dots = document.querySelectorAll('.slider-dot');
            
            // Fungsi untuk pindah slide
            function goToSlide(index) {
                currentIndex = index;
                const offset = -currentIndex * itemsPerPage * itemWidth;
                slider.style.transform = `translateX(${offset}px)`;
                
                // Update active dot
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            }
            
            // Tombol next
            nextBtn.addEventListener('click', () => {
                if (currentIndex < Math.ceil(totalItems / itemsPerPage) - 1) {
                    currentIndex++;
                    goToSlide(currentIndex);
                } else {
                    // Kembali ke slide pertama
                    currentIndex = 0;
                    goToSlide(currentIndex);
                }
            });
            
            // Tombol previous
            prevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    goToSlide(currentIndex);
                } else {
                    // Pindah ke slide terakhir
                    currentIndex = Math.ceil(totalItems / itemsPerPage) - 1;
                    goToSlide(currentIndex);
                }
            });
            
            // Auto slide setiap 5 detik
            let slideInterval = setInterval(() => {
                if (currentIndex < Math.ceil(totalItems / itemsPerPage) - 1) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
                goToSlide(currentIndex);
            }, 5000);
            
            // Hentikan auto slide saat hover
            slider.addEventListener('mouseenter', () => {
                clearInterval(slideInterval);
            });
            
            // Lanjutkan auto slide saat mouse leave
            slider.addEventListener('mouseleave', () => {
                slideInterval = setInterval(() => {
                    if (currentIndex < Math.ceil(totalItems / itemsPerPage) - 1) {
                        currentIndex++;
                    } else {
                        currentIndex = 0;
                    }
                    goToSlide(currentIndex);
                }, 5000);
            });
        });
    </script>
</body>
</html>