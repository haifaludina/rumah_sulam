<?php
require_once '../koneksi.php';
session_start();
$logged_in = isset($_SESSION['username']);

// Periksa apakah user sudah login
if (!$logged_in && (isset($_POST['checkout_now']) || isset($_POST['add_to_cart']))) {
    // Redirect ke halaman login dengan parameter redirect
    header('Location: ../login.php?redirect=detail_produk.php&id=' . $_GET['id']);
    exit;
}

// Pastikan user yang login memiliki data di tabel pelanggan
if ($logged_in) {
    $username = $_SESSION['username'];
    
    // Cek apakah data pelanggan sudah ada di session
    if (!isset($_SESSION['id_pelanggan'])) {
        // Cari ID pelanggan dari database jika belum ada di session
        $query_pelanggan = "SELECT id_pelanggan FROM pelanggan WHERE username = ?";
        $stmt = mysqli_prepare($koneksi, $query_pelanggan);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result_pelanggan = mysqli_stmt_get_result($stmt);
        
        if ($result_pelanggan && mysqli_num_rows($result_pelanggan) > 0) {
            $row = mysqli_fetch_assoc($result_pelanggan);
            $_SESSION['id_pelanggan'] = $row['id_pelanggan'];
        } else {
            // Jika tidak ada di tabel pelanggan, periksa tabel admin (mungkin admin yang login)
            $query_admin = "SELECT id_admin FROM admin WHERE username = ?";
            $stmt = mysqli_prepare($koneksi, $query_admin);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result_admin = mysqli_stmt_get_result($stmt);
            
            if ($result_admin && mysqli_num_rows($result_admin) > 0) {
                // Ini admin, tidak perlu diproses lebih lanjut
            } else {
                // Coba cari di tabel user dan buat entri baru di tabel pelanggan
                $query_user = "SELECT * FROM user WHERE username = ? LIMIT 1";
                $stmt = mysqli_prepare($koneksi, $query_user);
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result_user = mysqli_stmt_get_result($stmt);
                
                if ($result_user && mysqli_num_rows($result_user) > 0) {
                    $user_data = mysqli_fetch_assoc($result_user);
                    $nama = mysqli_real_escape_string($koneksi, $user_data['nama_lengkap'] ?? $username);
                    $email = mysqli_real_escape_string($koneksi, $user_data['email'] ?? '');
                    $password = mysqli_real_escape_string($koneksi, $user_data['password'] ?? '');
                    $no_hp = mysqli_real_escape_string($koneksi, $user_data['no_hp'] ?? '');
                    
                    $insert_query = "INSERT INTO pelanggan (nama_pelanggan, username, email, password, no_hp) 
                                    VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($koneksi, $insert_query);
                    mysqli_stmt_bind_param($stmt, "sssss", $nama, $username, $email, $password, $no_hp);
                    $insert_result = mysqli_stmt_execute($stmt);
                    
                    if ($insert_result) {
                        $_SESSION['id_pelanggan'] = mysqli_insert_id($koneksi);
                    } else {
                        // Log error untuk debugging
                        error_log("Error creating pelanggan record: " . mysqli_error($koneksi));
                    }
                }
            }
        }
    }
}

// Pastikan parameter ID produk ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: katalog_produk.php');
    exit;
}

$id_produk = intval($_GET['id']);

// Query untuk detail produk
$query = "SELECT p.*, k.nama as kategori_nama, s.nama as subkategori_nama 
          FROM produk p
          LEFT JOIN kategori k ON p.id_kategori = k.id
          LEFT JOIN subkategori s ON p.id_subkategori = s.id
          WHERE p.id_produk = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
} else {
    header('Location: katalog_produk.php');
    exit;
}

// Query untuk produk terkait
$related_query = "SELECT * FROM produk 
                  WHERE id_subkategori = ? 
                  AND id_produk != ? 
                  LIMIT 4";
$stmt = mysqli_prepare($koneksi, $related_query);
mysqli_stmt_bind_param($stmt, "ii", $product['id_subkategori'], $id_produk);
mysqli_stmt_execute($stmt);
$related_result = mysqli_stmt_get_result($stmt);
$related_products = [];

if ($related_result) {
    while ($row = mysqli_fetch_assoc($related_result)) {
        $related_products[] = $row;
    }
}

// Proses checkout langsung
if (isset($_POST['checkout_now']) && $logged_in) {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if (!isset($_SESSION['id_pelanggan'])) {
        echo "Error: ID pelanggan tidak ditemukan dalam session.";
        exit;
    }
    
    $id_pelanggan = $_SESSION['id_pelanggan'];
    
    // Hapus data checkout sementara yang ada
    $delete_query = "DELETE FROM temp_checkout WHERE id_pelanggan = ?";
    $stmt = mysqli_prepare($koneksi, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
    mysqli_stmt_execute($stmt);
    
    // Tambah produk ke temp_checkout
    $insert_query = "INSERT INTO temp_checkout (id_pelanggan, id_produk, jumlah) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $insert_query);
    mysqli_stmt_bind_param($stmt, "iii", $id_pelanggan, $id_produk, $quantity);
    $insert_result = mysqli_stmt_execute($stmt);
    
    if ($insert_result) {
        $_SESSION['direct_checkout'] = true;
        header("Location: checkout.php?direct=1&product=$id_produk");
        exit;
    } else {
        echo "Error: Gagal menambahkan produk ke checkout. " . mysqli_error($koneksi);
        exit;
    }
}

// Proses tambah ke keranjang
if (isset($_POST['add_to_cart']) && $logged_in) {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if (!isset($_SESSION['id_pelanggan'])) {
        echo "Error: ID pelanggan tidak ditemukan dalam session.";
        exit;
    }
    
    $id_pelanggan = $_SESSION['id_pelanggan'];
    
    // Cek apakah produk sudah ada di keranjang
    $check_query = "SELECT * FROM keranjang WHERE id_pelanggan = ? AND id_produk = ?";
    $stmt = mysqli_prepare($koneksi, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $id_pelanggan, $id_produk);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update jumlah jika produk sudah ada
        $update_query = "UPDATE keranjang SET jumlah = jumlah + ? WHERE id_pelanggan = ? AND id_produk = ?";
        $stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $id_pelanggan, $id_produk);
        mysqli_stmt_execute($stmt);
    } else {
        // Tambah produk baru ke keranjang
        $username = $_SESSION['username'];
        $insert_query = "INSERT INTO keranjang (id_pelanggan, id_produk, jumlah, username) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $insert_query);
        mysqli_stmt_bind_param($stmt, "iiis", $id_pelanggan, $id_produk, $quantity, $username);
        mysqli_stmt_execute($stmt);
    }
    
    if (isset($_POST['go_to_cart'])) {
        header("Location: keranjang.php");
        exit;
    } else {
        header("Location: detail_produk.php?id=$id_produk&added=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['nama_produk']); ?> - Rumah Sulam Sefni</title>
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
            background-color: #f8f8f8;
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

        /* Detail Produk Styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .breadcrumb {
            display: flex;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #666;
            text-decoration: none;
            margin-right: 5px;
        }
        
        .breadcrumb a:hover {
            color: var(--primary-color);
        }
        
        .breadcrumb span {
            margin: 0 5px;
            color: #999;
        }
        
        .product-detail {
            display: flex;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .product-gallery {
            flex: 0 0 50%;
            background-color: var(--grey-color);
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }
        
        .product-info {
            flex: 0 0 50%;
            padding: 30px;
        }
        
        .product-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: #222;
        }
        
        .product-price {
            font-size: 24px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .product-description {
            margin-bottom: 30px;
            color: #666;
            line-height: 1.8;
        }
        
        .product-meta {
            margin-bottom: 30px;
        }
        
        .meta-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .meta-item i {
            color: var(--primary-color);
            margin-right: 10px;
            margin-top: 4px;
        }
        
        .add-to-cart {
            display: flex;
            margin-top: 30px;
        }
        
        .quantity {
            width: 70px;
            height: 45px;
            margin-right: 15px;
            padding: 0 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background-color: var(--light-color);
            color: var(--text-color);
            border: 1px solid #ddd;
            margin-left: 10px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        
        .related-products {
            margin-top: 50px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .card-image {
            height: 200px;
            background-color: var(--grey-color);
            overflow: hidden;
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .card-info {
            padding: 15px;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .card-price {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .card-btn {
            display: inline-block;
            padding: 6px 12px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            transition: background-color 0.3s;
        }
        
        .success-alert {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .success-alert i {
            margin-right: 10px;
        }
        
        /* Footer */
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
            .product-detail {
                flex-direction: column;
            }
            
            .product-gallery, .product-info {
                flex: none;
                width: 100%;
            }
            
            header {
                padding: 15px 20px;
            }
            
            nav ul {
                display: none;
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
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Beranda</a>
            <span>/</span>
            <a href="katalog_produk.php">Katalog Produk</a>
            <span>/</span>
            <?php echo htmlspecialchars($product['nama_produk']); ?>
        </div>
        
        <?php if(isset($_GET['added']) && $_GET['added'] == 1): ?>
        <div class="success-alert">
            <i class="fas fa-check-circle"></i>
            Produk berhasil ditambahkan ke keranjang belanja.
        </div>
        <?php endif; ?>
        
        <div class="product-detail">
            <div class="product-gallery">
                <?php if (!empty($product['gambar'])): ?>
                    <img src="<?= $product['gambar']; ?>" alt="<?= htmlspecialchars($product['nama_produk']); ?>" class="product-image">
                <?php else: ?>
                    <img src="../uploads/produk/default.jpg" alt="Default Product Image" class="product-image">
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['nama_produk']); ?></h1>
                <div class="product-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>
                
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['deskripsi'])); ?>
                </div>
                
                <div class="product-meta">
                    <?php if(!empty($product['ukuran'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-ruler"></i>
                        <div>
                            <strong>Ukuran:</strong> <?php echo htmlspecialchars($product['ukuran']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($product['bahan'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-tshirt"></i>
                        <div>
                            <strong>Bahan:</strong> <?php echo htmlspecialchars($product['bahan']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <i class="fas fa-tags"></i>
                        <div>
                            <strong>Kategori:</strong> 
                            <?php echo htmlspecialchars($product['kategori_nama']); ?> / 
                            <?php echo htmlspecialchars($product['subkategori_nama']); ?>
                        </div>
                    </div>
                    
                    <?php if(!empty($product['waktu_pengerjaan'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Waktu Pengerjaan:</strong> <?php echo htmlspecialchars($product['waktu_pengerjaan']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <form method="post" class="add-to-cart">
                    <input type="number" name="quantity" value="1" min="1" class="quantity">
                    <button type="submit" name="checkout_now" class="btn btn-primary">Beli Sekarang</button>
                    <button type="submit" name="add_to_cart" class="btn btn-secondary">+ Keranjang</button>
                </form>
            </div>
        </div>
        
        <?php if(!empty($related_products)): ?>
        <div class="related-products">
            <h2 class="section-title">Produk Terkait</h2>
            
            <div class="products-grid">
                <?php foreach($related_products as $related): ?>
                <div class="product-card">
                    <div class="card-image">
                        <?php if (!empty($related['gambar'])): ?>
                            <img src="<?= $related['gambar']; ?>" alt="<?= htmlspecialchars($related['nama_produk']); ?>">
                        <?php else: ?>
                            <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                        <?php endif; ?>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title"><?php echo htmlspecialchars($related['nama_produk']); ?></h3>
                        <p class="card-price">Rp <?php echo number_format($related['harga'], 0, ',', '.'); ?></p>
                        <a href="detail_produk.php?id=<?php echo $related['id_produk']; ?>" class="card-btn">Lihat Detail</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
            &copy; 2025 Rumah Sulam Sefni. All rights reserved.
        </div>
    </footer>
    
</body>
</html>