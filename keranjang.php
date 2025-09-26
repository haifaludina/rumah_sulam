<?php
session_start();
require_once '../koneksi.php';

// Initialize id_pelanggan if not set (for testing or when not logged in)
if (!isset($_SESSION['id_pelanggan'])) {
    // Redirect to login page with a message
    $_SESSION['error'] = "Anda harus login terlebih dahulu untuk mengakses keranjang belanja.";
    //header("Location: login.php");
    //exit();
    
    // For testing purposes, use a default value instead of redirecting
    $_SESSION['id_pelanggan'] = 123; // Using a sample ID from your database
}

$id_pelanggan = $_SESSION['id_pelanggan'];

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $id_keranjang = $_POST['id_keranjang'];
    $new_quantity = $_POST['quantity'];
    
    // Update quantity in database
    $query = "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ? AND id_pelanggan = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("iii", $new_quantity, $id_keranjang, $id_pelanggan);
    $stmt->execute();
    
    // Redirect to refresh the page
    header("Location: keranjang.php");
    exit();
}

// Handle item removal
if (isset($_GET['remove'])) {
    $id_keranjang = $_GET['remove'];
    
    // Delete item from cart
    $query = "DELETE FROM keranjang WHERE id_keranjang = ? AND id_pelanggan = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ii", $id_keranjang, $id_pelanggan);
    $stmt->execute();
    
    // Redirect to refresh the page
    header("Location: keranjang.php");
    exit();
}

// Get cart items
$query = "SELECT k.id_keranjang, p.id_produk, p.nama_produk, p.harga, p.gambar, k.jumlah 
          FROM keranjang k 
          JOIN produk p ON k.id_produk = p.id_produk 
          WHERE k.id_pelanggan = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pelanggan);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['harga'] * $row['jumlah'];
}

// Calculate total (can add shipping costs or other fees here)
$total = $subtotal;

// Check if user is logged in
$logged_in = isset($_SESSION['id_pelanggan']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Rumah Sulam Sefni</title>
    
    <!-- Font Awesome Icons -->
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
            background-color: #f8f9fa;
        }

        /* Header & Navigasi */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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

        /* Cart Styles */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-family: 'Arial', sans-serif;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .table th {
            background-color: var(--grey-color);
            color: var(--primary-color);
            padding: 12px;
            text-align: left;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .ms-3 {
            margin-left: 15px;
        }

        .input-group {
            display: flex;
            width: 120px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--grey-color);
            border: none;
            cursor: pointer;
        }

        input[type="number"] {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 5px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            display: inline-block;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            border: none;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-outline-secondary {
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
            background-color: transparent;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: var(--grey-color);
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }

        .card-body {
            padding: 15px;
        }

        .justify-content-between {
            display: flex;
            justify-content: space-between;
        }

        .mb-3 {
            margin-bottom: 15px;
        }

        .fw-bold {
            font-weight: bold;
        }

        hr {
            border: 0;
            border-top: 1px solid #eee;
            margin: 15px 0;
        }

        .d-grid {
            display: grid;
            gap: 10px;
        }

        .text-center {
            text-align: center;
        }

        .py-5 {
            padding-top: 50px;
            padding-bottom: 50px;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .justify-content-end {
            justify-content: flex-end;
        }

        .col-md-5 {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }

        @media (min-width: 768px) {
            .col-md-5 {
                width: 41.666667%;
            }
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

        .social-icons a:hover {
            color: var(--primary-color);
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

            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                <a href="daftar.php" class="auth-btn signup-btn">Daftar</a>
                <a href="masuk.php" class="auth-btn login-btn">Masuk</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="container my-5">
        <h1 class="mb-4">Keranjang Belanja</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <h3>Keranjang Anda kosong</h3>
                <p class="mt-3">Silakan tambahkan produk ke keranjang Anda terlebih dahulu.</p>
                <a href="katalog_produk.php" class="btn btn-primary mt-3">Lihat Katalog</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $item['gambar']; ?>" alt="<?php echo $item['nama_produk']; ?>" class="product-img" onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                                        <div class="ms-3">
                                            <h6><?php echo $item['nama_produk']; ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <form method="post" action="keranjang.php" class="d-flex align-items-center">
                                        <input type="hidden" name="id_keranjang" value="<?php echo $item['id_keranjang']; ?>">
                                        <div class="input-group" style="width: 120px;">
                                            <button type="button" class="quantity-btn" data-action="decrease">-</button>
                                            <input type="number" name="quantity" class="form-control text-center" value="<?php echo $item['jumlah']; ?>" min="1">
                                            <button type="button" class="quantity-btn" data-action="increase">+</button>
                                        </div>
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-secondary ms-2">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></td>
                                <td>
                                    <a href="keranjang.php?remove=<?php echo $item['id_keranjang']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus item ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Ringkasan Belanja</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal (<?php echo count($cart_items); ?> item)</span>
                                <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="checkout.php" class="btn btn-primary">Buat Pesanan</a>
                                <a href="katalog_produk.php" class="btn btn-outline-secondary">Lanjutkan Belanja</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
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
            &copy; <?php echo date('Y'); ?> Rumah Sulam Sefni. Hak Cipta Dilindungi
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity buttons functionality
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const currentValue = parseInt(input.value);
                
                if (this.dataset.action === 'increase') {
                    input.value = currentValue + 1;
                } else if (this.dataset.action === 'decrease' && currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });
        });
    });
    </script>
</body>
</html>