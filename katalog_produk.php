<?php
// Include database connection
require_once '../koneksi.php';

// Start session for login status
session_start();
$logged_in = isset($_SESSION['username']);

// Function to get categories
function getCategories($koneksi) {
    $categories = array();
    
    // Get main categories
    $query_kategori = "SELECT * FROM kategori ORDER BY nama";
    $result_kategori = mysqli_query($koneksi, $query_kategori);
    
    if ($result_kategori) {
        while ($row = mysqli_fetch_assoc($result_kategori)) {
            $kategori_id = $row['id'];
            $kategori_nama = $row['nama'];
            
            // Get subcategories for this category
            $query_subkategori = "SELECT id, nama FROM subkategori WHERE id_kategori = $kategori_id ORDER BY nama";
            $result_subkategori = mysqli_query($koneksi, $query_subkategori);
            
            $subcategories = array();
            if ($result_subkategori) {
                while ($subrow = mysqli_fetch_assoc($result_subkategori)) {
                    // Count products in this subcategory
                    $subkat_id = $subrow['id'];
                    $count_query = "SELECT COUNT(*) as jumlah FROM produk WHERE id_subkategori = $subkat_id";
                    $count_result = mysqli_query($koneksi, $count_query);
                    $count_data = mysqli_fetch_assoc($count_result);
                    
                    $subrow['jumlah'] = $count_data['jumlah'];
                    $subcategories[] = $subrow;
                }
            }
            
            $categories[$kategori_nama] = $subcategories;
        }
    }
    
    return $categories;
}

// Function to get products with optional filter
function getProducts($koneksi, $subcategory_filter = null) {
    $where_clause = "";
    
    if ($subcategory_filter && !empty($subcategory_filter)) {
        $subcategory_ids = implode(',', array_map('intval', $subcategory_filter));
        $where_clause = "WHERE p.id_subkategori IN ($subcategory_ids)";
    }
    
    $query = "SELECT p.*, k.nama as kategori_nama, s.nama as subkategori_nama 
              FROM produk p
              LEFT JOIN kategori k ON p.id_kategori = k.id
              LEFT JOIN subkategori s ON p.id_subkategori = s.id
              $where_clause
              ORDER BY p.id_produk DESC";
    
    $result = mysqli_query($koneksi, $query);
    $products = array();
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Get selected subcategories from filter
$selected_subcategories = [];
if (isset($_GET['filter']) && is_array($_GET['filter'])) {
    $selected_subcategories = $_GET['filter'];
}

// Get categories and products
$categories = getCategories($koneksi);
$products = getProducts($koneksi, $selected_subcategories);

// Search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query = "SELECT p.*, k.nama as kategori_nama, s.nama as subkategori_nama
              FROM produk p
              LEFT JOIN kategori k ON p.id_kategori = k.id
              LEFT JOIN subkategori s ON p.id_subkategori = s.id
              WHERE p.nama_produk LIKE '%$search_term%' OR p.deskripsi LIKE '%$search_term%'
              ORDER BY p.id_produk DESC";
    
    $result = mysqli_query($koneksi, $query);
    $products = array();
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - Rumah Sulam Sefni</title>
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

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .page-title p {
            font-size: 14px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }


        /* Katalog Styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .page-title p {
            color: #666;
            font-size: 16px;
        }
        
        .content {
            display: flex;
            gap: 30px;
        }
        
        .sidebar {
            flex: 0 0 250px;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .sidebar h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .category-group {
            margin-bottom: 20px;
        }
        
        .category-group h4 {
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .checkbox-group {
            margin-bottom: 10px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #555;
        }
        
        .checkbox-group input {
            margin-right: 10px;
        }
        
        .filter-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .filter-button:hover {
            background-color: var(--secondary-color);
        }
        
        .product-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
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
        
        .product-image {
            height: 200px;
            background-color: var(--grey-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
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
            transition: background-color 0.3s;
        }
        
        .product-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .search-box {
            display: flex;
            margin-bottom: 30px;
            justify-content: flex-end;
        }
        
        .search-input {
            width: 300px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
            font-size: 14px;
        }
        
        .search-button {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            padding: 0 15px;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            cursor: pointer;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            
            .sidebar {
                flex: none;
                width: 100%;
                margin-bottom: 30px;
            }
            
            header {
                padding: 15px 20px;
                flex-direction: column;
            }
            
            nav ul {
                margin: 15px 0;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Updated Navbar -->
    <header>
        <a href="index.php" class="logo-container">
            <img src="../uploads/logo-sefni.png" alt="Logo" class="logo">
            <span class="logo-text">Rumah Sulam Sefni</span>
        </a>
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="katalog_produk.php" class="active">Katalog Produk</a></li>
                <li><a href="kustomisasi.php">Kustomisasi Sulaman</a></li>
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="tentang_kami.php">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
            <?php if(isset($_SESSION['username']) && $_SESSION['username']): ?>
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
        <div class="page-title">
            <h1>Katalog Produk</h1>
            <p>Keindahan Tradisi dalam Setiap Jahitan</p>
        </div>
        
        <div class="search-box">
            <form action="katalog_produk.php" method="GET">
                <input type="text" name="search" class="search-input" placeholder="Temukan produk..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div class="content">
            <div class="sidebar">
                <h3>Kategori</h3>
                <form action="katalog_produk.php" method="GET">
                    <?php foreach ($categories as $kategori_nama => $subcategories): ?>
                    <div class="category-group">
                        <h4><?php echo $kategori_nama; ?></h4>
                        <?php foreach ($subcategories as $subcategory): ?>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="filter[]" value="<?php echo $subcategory['id']; ?>" 
                                       <?php echo in_array($subcategory['id'], $selected_subcategories) ? 'checked' : ''; ?>>
                                <?php echo $subcategory['nama']; ?> 
                                <span>(<?php echo isset($subcategory['jumlah']) ? $subcategory['jumlah'] : 0; ?>)</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="filter-button">Terapkan Filter</button>
                </form>
            </div>
            
            <div class="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['gambar'])): ?>
                                <img src="<?= $product['gambar']; ?>" alt="<?= htmlspecialchars($product['nama_produk']); ?>">
                            <?php else: ?>
                                <img src="../uploads/produk/default.jpg" alt="Default Product Image">
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo $product['nama_produk']; ?></h3>
                            <p class="product-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></p>
                            <a href="detail_produk.php?id=<?php echo $product['id_produk']; ?>" class="product-btn">Lihat Detail</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px 0;">
                        <p>Tidak ada produk yang ditemukan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
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