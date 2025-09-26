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
    <title>Kontak - Rumah Sulam Sefni</title>
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
            background-color: white;
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

        /* Map container */
        .map-container {
            width: 100%;
            height: 400px;
            background-color: var(--grey-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        /* Contact Content */
        .contact-content {
            padding: 50px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .contact-section h3 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .contact-row {
            display: flex;
            gap: 40px;
            margin-top: 30px;
        }
        
        .contact-info {
            flex: 1;
            margin-bottom: 30px;
        }
        
        .info-item {
            margin-bottom: 20px;
        }
        
        .info-item i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .social-media {
            margin-top: 30px;
        }
        
        .social-media h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .social-media a {
            display: inline-block;
            margin-right: 15px;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .social-media a:hover {
            color: var(--primary-color);
        }
        
        .operating-hours {
            margin-top: 30px;
        }
        
        .operating-hours h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .contact-form {
            flex: 1;
            padding: 30px;
            background-color: var(--light-color);
            border-radius: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea.form-control {
            height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
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
            
            .contact-row {
                flex-direction: column;
            }
            
            .page-header,
            .contact-content {
                padding: 30px 20px;
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
                <li><a href="tentang_kami.php">Tentang Kami</a></li>
                <li><a href="kontak.php" class="active">Kontak</a></li>
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
        <h1>Kontak</h1>
        <p>Hubungi kami untuk informasi lebih lanjut tentang produk sulaman atau kerjasama</p>
    </section>

    <div class="contact-content">
        <div class="contact-section">
            <h3>Lokasi RUMAH SULAM "SEFNI"</h3>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7402968606907!2d100.57305910000001!3d-0.35832279999999206!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2fd5357ebd0c1af5%3A0xc29cf07cfcb90377!2sRUMAH%20SULAM%22SEFNI%22!5e0!3m2!1sid!2sid!4v1747561669906!5m2!1sid!2sid"
                    width="100%"
                    height="400"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <div class="contact-row">
                <div class="contact-info">
                    <h3>Informasi Kontak</h3>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Situmbuk, Salimpaung, Tanah Datar Regency, West Sumatra 27263</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span>+62 811 6632 826</span>
                    </div>
                    
                    <div class="social-media">
                        <h4>Media Sosial:</h4>
                        <a href="https://www.instagram.com/rumahsulam_sefni/" target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
                        <a href="https://www.facebook.com/sefni.akhirda.3" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
                        <a href="https://wa.me/6281166326266" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    </div>
                    
                    <div class="operating-hours">
                        <h4><i class="far fa-clock"></i> Jam Operasional:</h4>
                        <p>Senin - Jumat: 09.00 - 17.00 WIB</p>
                        <p>Sabtu: 09.00 - 15.00 WIB</p>
                        <p>Minggu: Tutup</p>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h3>Kirim Pesan</h3>
                    
                    <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="proses_kontak.php" method="post" id="kontakForm">
                        <div class="form-group">
                            <label for="nama">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="telepon">Nomor Telepon</label>
                            <input type="tel" class="form-control" id="telepon" name="telepon">
                        </div>
                        <div class="form-group">
                            <label for="subjek">Subjek</label>
                            <input type="text" class="form-control" id="subjek" name="subjek" required>
                        </div>
                        <div class="form-group">
                            <label for="pesan">Pesan</label>
                            <textarea class="form-control" id="pesan" name="pesan" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Kirim Pesan</button>
                    </form>
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
                <a href="https://wa.me/6281166326266" target="_blank"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="copyright">
            &copy; 2025 Rumah Sulam Sefni. Hak Cipta Dilindungi
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil referensi form berdasarkan ID yang sudah diberikan
            const form = document.getElementById('kontakForm');
            
            if(form) {
                form.addEventListener('submit', function(event) {
                    // Validasi form dasar
                    let nama = document.getElementById('nama').value.trim();
                    let email = document.getElementById('email').value.trim();
                    let subjek = document.getElementById('subjek').value.trim();
                    let pesan = document.getElementById('pesan').value.trim();
                    
                    if (nama === '' || email === '' || subjek === '' || pesan === '') {
                        alert('Mohon lengkapi semua field yang diperlukan');
                        event.preventDefault(); // Mencegah pengiriman jika validasi gagal
                        return false;
                    }
                    
                    // Validasi format email
                    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email)) {
                        alert('Format email tidak valid');
                        event.preventDefault();
                        return false;
                    }
                    
                    // Tampilkan log untuk debugging
                    console.log('Form disubmit ke: ' + form.action);
                    // Form akan dikirim secara normal jika validasi berhasil
                });
            } else {
                console.error('Form dengan ID kontakForm tidak ditemukan!');
            }
        });
    </script>
</body>
</html>