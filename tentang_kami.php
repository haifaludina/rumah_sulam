<?php
session_start();
$logged_in = isset($_SESSION['username']);

// Koneksi database
include '../koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Rumah Sulam Sefni</title>
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

        /* Page Header */
        .page-header {
            padding: 40px 50px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 14px;
            max-width: 800px;
            margin: 0 auto;
            color: #666;
        }

        /* About Content */
        .about-content {
            padding: 50px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .about-section {
            margin-bottom: 60px;
        }

        .about-intro {
            display: flex;
            gap: 40px;
            align-items: center;
            margin-bottom: 40px;
        }

        .about-image {
            flex: 0 0 300px;
            height: 300px;
            background-color: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .about-text {
            flex: 1;
        }

        .about-text h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .about-text p {
            margin-bottom: 15px;
            font-size: 15px;
        }

        /* Features */
        .features {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 60px;
        }

        .feature-item {
            flex: 1;
            text-align: center;
            padding: 20px;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-icon img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .feature-item h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .feature-item p {
            font-size: 14px;
            color: #666;
        }

        /* Certificates */
        .certificates-section h2 {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
            color: var(--primary-color);
        }

        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .certificate-item {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .certificate-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* CSS untuk slideshow */
.slideshow-container {
    position: relative;
    max-width: 100%;
    height: 250px;
    overflow: hidden;
    border-radius: 8px;
}

.mySlides {
    display: none;
    width: 100%;
    height: 100%;
}

.mySlides.active {
    display: block;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    width: auto;
    margin-top: -22px;
    padding: 16px;
    color: white;
    font-weight: bold;
    font-size: 18px;
    transition: 0.6s ease;
    border-radius: 0 3px 3px 0;
    user-select: none;
    background-color: rgba(0,0,0,0.3);
}

.next {
    right: 0;
    border-radius: 3px 0 0 3px;
}

.prev:hover, .next:hover {
    background-color: rgba(0,0,0,0.8);
}

.dots-container {
    position: absolute;
    bottom: 10px;
    width: 100%;
    text-align: center;
}

.dot {
    cursor: pointer;
    height: 10px;
    width: 10px;
    margin: 0 2px;
    background-color: #bbb;
    border-radius: 50%;
    display: inline-block;
    transition: background-color 0.6s ease;
}

.dot.active {
    background-color: #717171;
}

.view-all {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 4px;
}
        /* Prev and Next buttons */
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            margin-top: -22px;
            padding: 10px;
            color: white;
            font-weight: bold;
            font-size: 18px;
            transition: 0.6s ease;
            border-radius: 0 3px 3px 0;
            user-select: none;
            background-color: rgba(0,0,0,0.3);
            z-index: 10;
        }

        .next {
            right: 0;
            border-radius: 3px 0 0 3px;
        }

        .prev {
            left: 0;
        }

        .prev:hover, .next:hover {
            background-color: rgba(0,0,0,0.6);
        }

        /* View All button */
        .view-all {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            z-index: 10;
        }

        /* Modal Gallery */
       .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.9);
}

.modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    width: 80%;
    max-width: 1200px;
    border-radius: 8px;
}

.close-modal {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: black;
}

.gallery-content {
    display: none;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.gallery-grid img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
}

.gallery-grid img:hover {
    opacity: 0.8;
}


        /* Slide indicators */
        .dots-container {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            justify-content: center;
        }

        .dot {
            height: 10px;
            width: 10px;
            margin: 0 2px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.6s ease;
            cursor: pointer;
        }

        .active-dot, .dot:hover {
            background-color: var(--primary-color);
        }

        .certificate-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .certificate-info .year {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }

        .certificate-info p {
            font-size: 14px;
            color: #666;
        }

        /* Footer (Dari kode yang dikirimkan) */
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
            
            .about-intro {
                flex-direction: column;
            }
            
            .features {
                flex-direction: column;
            }
            
            .certificates-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header,
            .about-content {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .certificates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="tentang_kami.php" class="active">Tentang Kami</a></li>
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
        <h1>Tentang Kami</h1>
        <p>Mengenal lebih dekat Rumah Sulam Sefni dan perjalanan kami melestarikan warisan sulaman tradisional</p>
    </section>

    <div class="about-content">
        <div class="about-section">
            <div class="about-intro">
                <div class="about-image">
                    <img src="../uploads/tentang/pemilik.jpg" alt="Rumah Sulam Sefni Workshop">
                </div>
                <div class="about-text">
                    <h2>Tentang Rumah Sulam Sefni</h2>
                    <p>Selamat datang di Rumah Sulam Sefni, tempat di mana tradisi sulaman Indonesia dipertahankan dengan penuh kebanggaan dan keindahan. Berdiri sejak tahun 2010, kami telah berkomitmen untuk melestarikan seni sulam tradisional sekaligus mengembangkan inovasi yang menjadikan sulaman sebagai bagian dari gaya hidup modern.</p>
                    <p>Didirikan oleh Ibu Sefni Akhirda, seorang pecinta seni sulam yang berpengalaman lebih dari 20 tahun, Rumah Sulam Sefni berawal dari sebuah mimpi sederhana untuk berbagi keindahan sulaman dengan dunia. Dari workshop kecil di rumah, kini kami telah berkembang menjadi pusat sulaman yang dikenal akan kualitas dan keunikannya.</p>
                    <p>Setiap produk sulaman kami dikerjakan dengan ketelitian dan kesabaran oleh tangan-tangan terampil pengrajin lokal yang telah dilatih secara khusus. Kami percaya bahwa setiap sulaman tidak hanya sekadar aksesori atau hiasan, tetapi merupakan sebuah karya seni yang menceritakan kisah dan mewariskan tradisi.</p>
                </div>
            </div>

            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="../uploads/tentang/1.png" alt="Kualitas Prima">
                    </div>
                    <h3>Kualitas Prima</h3>
                    <p>Kami berkomitmen untuk menghasilkan sulaman dengan kualitas terbaik menggunakan bahan pilihan dan teknik yang telah disempurnakan selama bertahun-tahun.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="../uploads/tentang/2.png" alt="Keberlanjutan">
                    </div>
                    <h3>Keberlanjutan</h3>
                    <p>Setiap produk kami dibuat dengan memperhatikan dampak lingkungan, mendukung pengrajin lokal, dan melestarikan teknik sulam tradisional.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="../uploads/tentang/3.png" alt="Inovasi">
                    </div>
                    <h3>Inovasi</h3>
                    <p>Kami terus mengembangkan desain dan teknik baru, mengadaptasi seni sulam tradisional ke dalam konteks modern tanpa menghilangkan esensinya.</p>
                </div>
            </div>
        </div>

<div class="certificates-section">
    <h2>Prestasi & Rekognisi </h2>
    <div class="certificates-grid">
        <!-- Certificate 1 -->
        <div class="certificate-item">
            <div class="certificate-slideshow">
                <div class="slideshow-container" data-id="slideshow-1">
                    <div class="mySlides active">
                        <img src="../uploads/tentang/juara1.jpg" alt="Juara PKK 1">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/juara1-2.jpg" alt="Juara PKK 2">
                    </div>
                    <a class="prev" onclick="plusSlides(-1, 'slideshow-1')">&#10094;</a>
                    <a class="next" onclick="plusSlides(1, 'slideshow-1')">&#10095;</a>
                    <div class="dots-container">
                        <span class="dot" onclick="currentSlide(0, 'slideshow-1')"></span>
                        <span class="dot" onclick="currentSlide(1, 'slideshow-1')"></span>
                    </div>
                    <button class="view-all" onclick="openModal('gallery-1')">Lihat Semua</button>
                </div>
            </div>
            <div class="certificate-info">
                <h3>Juara 1 HKG PKK Sumatra Barat</h3>
                <div class="year">2022</div>
                <p>Baju Kurung Basiba karya Rumah Sulam Sefni meraih Juara I pada fashion show Jambore PKK Sumbar. Karya ini kemudian ditetapkan sebagai seragam resmi TP PKK Provinsi Sumatera Barat.</p>
            </div>
        </div>

        <!-- Certificate 2 -->
        <div class="certificate-item">
            <div class="certificate-slideshow">
                <div class="slideshow-container" data-id="slideshow-2">
                    <div class="mySlides active">
                        <img src="../uploads/tentang/inacraft.jpg" alt="INACRAFT 1">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/inacraft-2.jpg" alt="INACRAFT 2">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/inacraft-3.jpg" alt="INACRAFT 3">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/inacraft-4.jpg" alt="INACRAFT 4">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/inacraft-5.jpg" alt="INACRAFT 5">
                    </div>
                    <a class="prev" onclick="plusSlides(-1, 'slideshow-2')">&#10094;</a>
                    <a class="next" onclick="plusSlides(1, 'slideshow-2')">&#10095;</a>
                    <div class="dots-container">
                        <span class="dot" onclick="currentSlide(0, 'slideshow-2')"></span>
                        <span class="dot" onclick="currentSlide(1, 'slideshow-2')"></span>
                        <span class="dot" onclick="currentSlide(2, 'slideshow-2')"></span>
                        <span class="dot" onclick="currentSlide(3, 'slideshow-2')"></span>
                        <span class="dot" onclick="currentSlide(4, 'slideshow-2')"></span>
                    </div>
                    <button class="view-all" onclick="openModal('gallery-2')">Lihat Semua</button>
                </div>
            </div>
            <div class="certificate-info">
                <h3>The Jakarta International Handicraft Trade Fair</h3>
                <div class="year">2021-2025</div>
                <p>Rumah Sulam Sefni secara konsisten mengikuti pameran stand INACRAFT setiap tahun dari 2021 hingga 2025</p>
            </div>
        </div>

        <!-- Certificate 3 -->
        <div class="certificate-item">
            <div class="certificate-slideshow">
                <div class="slideshow-container" data-id="slideshow-3">
                    <div class="mySlides active">
                        <img src="../uploads/tentang/bsnp.jpg" alt="Sertifikat BSNP 1">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/bsnp-2.jpg" alt="Sertifikat BSNP 2">
                    </div>
                    <a class="prev" onclick="plusSlides(-1, 'slideshow-3')">&#10094;</a>
                    <a class="next" onclick="plusSlides(1, 'slideshow-3')">&#10095;</a>
                    <div class="dots-container">
                        <span class="dot" onclick="currentSlide(0, 'slideshow-3')"></span>
                        <span class="dot" onclick="currentSlide(1, 'slideshow-3')"></span>
                    </div>
                    <button class="view-all" onclick="openModal('gallery-3')">Lihat Semua</button>
                </div>
            </div>
            <div class="certificate-info">
                <h3>Sertifikat Kompetensi Metodologi Pelatihan</h3>
                <div class="year">2023</div>
                <p>Telah memperoleh sertifikasi dari BSNP sebagai penyelenggara pelatihan sulam yang terakreditasi</p>
            </div>
        </div>

        <!-- Certificate 4 -->
        <div class="certificate-item">
            <div class="certificate-slideshow">
                <div class="slideshow-container" data-id="slideshow-4">
                    <div class="mySlides active">
                        <img src="../uploads/tentang/pameran.jpg" alt="Pameran 1">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/pameran-2.jpg" alt="Pameran 2">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/pameran-3.jpg" alt="Pameran 3">
                    </div>
                    <a class="prev" onclick="plusSlides(-1, 'slideshow-4')">&#10094;</a>
                    <a class="next" onclick="plusSlides(1, 'slideshow-4')">&#10095;</a>
                    <div class="dots-container">
                        <span class="dot" onclick="currentSlide(0, 'slideshow-4')"></span>
                        <span class="dot" onclick="currentSlide(1, 'slideshow-4')"></span>
                        <span class="dot" onclick="currentSlide(2, 'slideshow-4')"></span>
                    </div>
                    <button class="view-all" onclick="openModal('gallery-4')">Lihat Semua</button>
                </div>
            </div>
            <div class="certificate-info">
                <h3>Pameran Festival Pagaruyung</h3>
                <div class="year">2019-Sekarang</div>
                <p>Aktif mengikuti pameran-pameran lokal guna meningkatkan promosi dan penguasaan pasar produk sulaman</p>
            </div>
        </div>

        <!-- Certificate 5 -->
        <div class="certificate-item">
            <div class="certificate-slideshow">
                <div class="slideshow-container" data-id="slideshow-5">
                    <div class="mySlides active">
                        <img src="../uploads/tentang/pelatihan.jpg" alt="Pelatihan 1">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/pelatihan-2.jpg" alt="Pelatihan 2">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/pelatihan-3.jpg" alt="Pelatihan 3">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/pelatihan-4.jpg" alt="Pelatihan 4">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/pelatihan-5.jpg" alt="Pelatihan 5">
                    </div>
                     <div class="mySlides">
                        <img src="../uploads/tentang/pelatihan-6.jpg" alt="Pelatihan 6">
                    </div>
                     <div class="mySlides">
                        <img src="../uploads/tentang/pelatihan-7.jpg" alt="Pelatihan 7">
                    </div>
                    

                    <a class="prev" onclick="plusSlides(-1, 'slideshow-5')">&#10094;</a>
                    <a class="next" onclick="plusSlides(1, 'slideshow-5')">&#10095;</a>
                    <div class="dots-container">
                        <span class="dot" onclick="currentSlide(0, 'slideshow-5')"></span>
                        <span class="dot" onclick="currentSlide(1, 'slideshow-5')"></span>
                        <span class="dot" onclick="currentSlide(2, 'slideshow-5')"></span>
                    </div>
                    <button class="view-all" onclick="openModal('gallery-5')">Lihat Semua</button>
                </div>
            </div>
            <div class="certificate-info">
                <h3>50++ Instruktur Pelatihan Tersertifikasi</h3>
                <div class="year">2018-2023</div>
                <p>Sebanyak 50 pelatihan telah dilaksanakan dengan sertifikasi resmi bagi para peserta di Sumatra Barat</p>
            </div>
        </div>

        <!-- Certificate 6 -->
        <div class="certificate-item">
            <div class="certificate-slideshow">
                <div class="slideshow-container" data-id="slideshow-6">
                    <div class="mySlides active">
                        <img src="../uploads/tentang/apkasi.jpg" alt="APKASI 1">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/apkasi-2.jpg" alt="APKASI 2">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/apkasi-3.jpg" alt="APKASI 3">
                    </div>
                    <div class="mySlides">
                        <img src="../uploads/tentang/apkasi-4.jpg" alt="APKASI 4">
                    </div>
                    <a class="prev" onclick="plusSlides(-1, 'slideshow-6')">&#10094;</a>
                    <a class="next" onclick="plusSlides(1, 'slideshow-6')">&#10095;</a>
                    <div class="dots-container">
                        <span class="dot" onclick="currentSlide(0, 'slideshow-6')"></span>
                        <span class="dot" onclick="currentSlide(1, 'slideshow-6')"></span>
                        <span class="dot" onclick="currentSlide(2, 'slideshow-6')"></span>
                    </div>
                    <button class="view-all" onclick="openModal('gallery-6')">Lihat Semua</button>
                </div>
            </div>
            <div class="certificate-info">
                <h3>International Meeting on Best Practices of Ulayat Land Registration</h3>
                <div class="year">2018-2023</div>
                <p>Berpartisipasi dalam pameran International Meeting on Best Practices of Ulayat Land Registration di Bandung, menampilkan karya sulam tradisional sebagai bagian dari warisan budaya lokal yang terikat erat dengan identitas dan tanah ulayat masyarakat.</p>
            </div>
        </div>
    </div>
</div>

<!-- Galeri Modal -->
<div id="galleryModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div id="gallery-1" class="gallery-content">
            <h3>Juara 1 HKG PKK Sumatra Barat</h3>
            <div class="gallery-grid">
                <img src="../uploads/tentang/juara1.jpg" alt="Juara PKK 1">
                <img src="../uploads/tentang/juara1-2.jpg" alt="Juara PKK 2">
                <!-- Tambahkan gambar lain jika ada -->
            </div>
        </div>
        
        <div id="gallery-2" class="gallery-content">
            <h3>The Jakarta International Handicraft Trade Fair</h3>
            <div class="gallery-grid">
                <img src="../uploads/tentang/inacraft.jpg" alt="INACRAFT 1">
                <img src="../uploads/tentang/inacraft-2.jpg" alt="INACRAFT 2">
                <img src="../uploads/tentang/inacraft-3.jpg" alt="INACRAFT 3">
                <img src="../uploads/tentang/inacraft-4.jpg" alt="INACRAFT 4">
                <img src="../uploads/tentang/inacraft-5.jpg" alt="INACRAFT 5">
                <!-- Tambahkan gambar lain jika ada -->
            </div>
        </div>
        
        <div id="gallery-3" class="gallery-content">
            <h3>Sertifikat Kompetensi Metodologi Pelatihan</h3>
            <div class="gallery-grid">
                <img src="../uploads/tentang/bsnp.jpg" alt="Sertifikat BSNP 1">
                <img src="../uploads/tentang/bsnp-2.jpg" alt="Sertifikat BSNP 2">
                <!-- Tambahkan gambar lain jika ada -->
            </div>
        </div>
        
        <div id="gallery-4" class="gallery-content">
            <h3>Pameran Festival Pagaruyung</h3>
            <div class="gallery-grid">
                <img src="../uploads/tentang/pameran.jpg" alt="Pameran 1">
                <img src="../uploads/tentang/pameran-2.jpg" alt="Pameran 2">
                <img src="../uploads/tentang/pameran-3.jpg" alt="Pameran 3">
                <!-- Tambahkan gambar lain jika ada -->
            </div>
        </div>
        
        <div id="gallery-5" class="gallery-content">
            <h3>50++ Instruktur Pelatihan Tersertifikasi</h3>
            <div class="gallery-grid">
                <img src="../uploads/tentang/pelatihan.jpg" alt="Pelatihan 1">
                <img src="../uploads/tentang/pelatihan-2.jpg" alt="Pelatihan 2">
                <img src="../uploads/tentang/pelatihan-3.jpg" alt="Pelatihan 3">
                <img src="../uploads/tentang/pelatihan-4.jpg" alt="Pelatihan 4">
                <img src="../uploads/tentang/pelatihan-5.jpg" alt="Pelatihan 5">
                <img src="../uploads/tentang/pelatihan-6.jpg" alt="Pelatihan 6">
                <img src="../uploads/tentang/pelatihan-7.jpg" alt="Pelatihan 7">

                <!-- Tambahkan gambar lain jika ada -->
            </div>
        </div>
        
        <div id="gallery-6" class="gallery-content">
            <h3>International Meeting on Best Practices of Ulayat Land Registration</h3>
            <div class="gallery-grid">
                <img src="../uploads/tentang/apkasi.jpg" alt="APKASI 1">
                <img src="../uploads/tentang/apkasi-2.jpg" alt="APKASI 2">
                <img src="../uploads/tentang/apkasi-3.jpg" alt="APKASI 3">
                <img src="../uploads/tentang/apkasi-4.jpg" alt="APKASI 3">
                <!-- Tambahkan gambar lain jika ada -->
            </div>
        </div>
    </div>
</div>


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
let slideshowIntervals = {};

// Inisialisasi semua slideshow
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi slideshow
    const slideshows = document.querySelectorAll('.slideshow-container');
    slideshows.forEach(container => {
        const id = container.getAttribute('data-id');
        showSlides(0, id);
        
        // Aktifkan dot pertama pada setiap slideshow
        const dots = container.querySelectorAll('.dot');
        if (dots.length > 0) {
            dots[0].classList.add('active');
        }
    });
});

// Fungsi untuk menggeser slide ke depan atau belakang
function plusSlides(n, slideshowId) {
    const container = document.querySelector(`[data-id="${slideshowId}"]`);
    const slides = container.querySelectorAll('.mySlides');
    
    // Cari slide yang aktif saat ini
    let currentIndex = -1;
    for (let i = 0; i < slides.length; i++) {
        if (slides[i].classList.contains('active')) {
            currentIndex = i;
            break;
        }
    }
    
    // Hitung index slide berikutnya
    let nextIndex = currentIndex + n;
    if (nextIndex >= slides.length) {
        nextIndex = 0;
    } else if (nextIndex < 0) {
        nextIndex = slides.length - 1;
    }
    
    showSlides(nextIndex, slideshowId);
}

// Fungsi untuk menampilkan slide tertentu
function currentSlide(n, slideshowId) {
    showSlides(n, slideshowId);
}

// Fungsi untuk menampilkan slide
function showSlides(n, slideshowId) {
    const container = document.querySelector(`[data-id="${slideshowId}"]`);
    if (!container) return;
    
    const slides = container.querySelectorAll('.mySlides');
    const dots = container.querySelectorAll('.dot');
    
    // Sembunyikan semua slide
    for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove('active');
    }
    
    // Hapus status aktif dari semua dot
    for (let i = 0; i < dots.length; i++) {
        dots[i].classList.remove('active');
    }
    
    // Tampilkan slide dan aktifkan dot yang sesuai
    slides[n].classList.add('active');
    dots[n].classList.add('active');
}

// Fungsi untuk membuka modal galeri
function openModal(galleryId) {
    const modal = document.getElementById('galleryModal');
    const allGalleries = document.querySelectorAll('.gallery-content');
    
    // Sembunyikan semua galeri
    allGalleries.forEach(gallery => {
        gallery.style.display = 'none';
    });
    
    // Tampilkan galeri yang dipilih
    const selectedGallery = document.getElementById(galleryId);
    if (selectedGallery) {
        selectedGallery.style.display = 'block';
    }
    
    // Tampilkan modal
    modal.style.display = 'block';
}

// Fungsi untuk menutup modal
function closeModal() {
    const modal = document.getElementById('galleryModal');
    modal.style.display = 'none';
}

// Tutup modal jika pengguna mengklik di luar konten modal
window.onclick = function(event) {
    const modal = document.getElementById('galleryModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
};
</script>

</body>
</html>