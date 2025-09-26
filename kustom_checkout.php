<?php
session_start();
require_once '../koneksi.php';

// Periksa login
if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: masuk.php');
    exit();
}

// Periksa apakah ada data custom order
if (!isset($_SESSION['custom_order'])) {
    header('Location: kustomisasi.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];
$custom_order = $_SESSION['custom_order'];

// Ambil data pelanggan
$query_pelanggan = $koneksi->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$query_pelanggan->bind_param("i", $id_pelanggan);
$query_pelanggan->execute();
$pelanggan = $query_pelanggan->get_result()->fetch_assoc();

// Ambil alamat yang dipilih
$alamat_id = $custom_order['alamat_id'];
$query_alamat = $koneksi->prepare("SELECT * FROM alamat_pelanggan WHERE id_alamat = ? AND id_pelanggan = ?");
$query_alamat->bind_param("ii", $alamat_id, $id_pelanggan);
$query_alamat->execute();
$alamat = $query_alamat->get_result()->fetch_assoc();

// Kalkulasi total
$product_price = intval($custom_order['product_price']);
$shipping_cost = intval($custom_order['shipping_cost']);
$total = $product_price + $shipping_cost;

// Proses pembayaran jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['metode_pembayaran'])) {
        $metode_pembayaran = $_POST['metode_pembayaran'];
        $tanggal_order = date('Y-m-d H:i:s');
        $status_pembayaran = 'Menunggu Pembayaran';
        $status_pesanan = 'menunggu';
        
        // Buat pesanan kustom baru
        $query_order = $koneksi->prepare("INSERT INTO pesanan (
    id_pelanggan, tanggal_pesan, total_harga, status, metode_pembayaran, id_alamat
) VALUES (
    ?, ?, ?, ?, ?, ?
)");
       $query_order->bind_param("isdssi", $id_pelanggan, $tanggal_order, $total, $status_pesanan, $metode_pembayaran, $alamat_id);
        $query_order->execute();
        
        $id_pesanan = $koneksi->insert_id;
        
        // Tambahkan data kustomisasi
        $product_name = $custom_order['product'];
        $warna_kain = $custom_order['warna_kain'];
        $warna_benang = $custom_order['warna_benang'];
        $motif = $custom_order['motif'];
        $catatan = $custom_order['catatan'];
        $is_custom = 1;
        
        $query_detail = $koneksi->prepare("INSERT INTO detail_pesanan_kustom (id_pesanan, nama_produk, harga, warna_kain, warna_benang, motif, catatan, is_custom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $query_detail->bind_param("isdssssi", $id_pesanan, $product_name, $product_price, $warna_kain, $warna_benang, $motif, $catatan, $is_custom);
        $query_detail->execute();
        
        // Tambahkan biaya pengiriman
        $query_shipping = $koneksi->prepare("INSERT INTO pengiriman (id_pesanan, jasa_pengiriman) VALUES (?, ?)");
        $query_shipping->bind_param("is", $id_pesanan, $custom_order['pengiriman']);
        $query_shipping->execute();
        
        // Hapus session custom order
        unset($_SESSION['custom_order']);
        
        // PERUBAHAN: Langsung redirect ke halaman pembayaran dengan ID pesanan
        header("Location: pembayaran.php?order=$id_pesanan");
        exit();
    }
}

// Format mata uang Indonesia
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pesanan Kustom - Rumah Sulam Sefni</title>
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
            padding-left: 0;
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

        .user-icons a {
            margin-left: 15px;
            font-size: 20px;
            color: var(--text-color);
            text-decoration: none;
        }
        
        .user-icons a:hover {
            color: var(--primary-color);
        }
        
        /* Footer (Dari kode yang dikirimkan) */
        footer {
            padding: 30px 50px;
            background-color: white;
            text-align: center;
            border-top: 1px solid #eee;
            margin-top: 40px;
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

        /* Main content */
        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 32px;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .page-title p {
            font-size: 14px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Checkout Content */
        .checkout-container {
            display: flex;
            gap: 30px;
        }

        .checkout-left {
            flex: 2;
        }

        .checkout-right {
            flex: 1;
        }

        .checkout-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .checkout-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            font-weight: 500;
        }

        .checkout-card-body {
            padding: 20px;
        }

        /* Custom product details */
        .custom-product {
            display: flex;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .custom-product-image {
            width: 120px;
            height: 120px;
            background-color: var(--light-color);
            border-radius: 5px;
            overflow: hidden;
            margin-right: 20px;
        }

        .custom-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .custom-product-details {
            flex: 1;
        }

        .custom-product-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .custom-product-price {
            font-size: 16px;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .custom-product-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .custom-option {
            font-size: 14px;
        }

        .custom-option strong {
            color: var(--dark-color);
        }

        /* Shipping and Address */
        .shipping-details {
            margin-bottom: 15px;
        }

        .address-details {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .address-details p {
            margin-bottom: 5px;
        }

        .shipping-method {
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
        }

        /* Payment Methods */
        .payment-methods {
            margin-top: 20px;
        }

        .payment-option {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
            background-color: rgba(109, 76, 65, 0.05);
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(109, 76, 65, 0.1);
        }

        .payment-option input {
            margin-right: 15px;
        }

        .payment-icon {
            margin-right: 15px;
            font-size: 24px;
            color: var(--dark-color);
        }

        .payment-label {
            flex: 1;
        }

        .payment-title {
            font-weight: 500;
            margin-bottom: 3px;
            font-size: 16px;
        }

        .payment-description {
            font-size: 13px;
            color: #666;
        }

        /* Order Summary */
        .order-summary {
            background-color: var(--light-color);
            padding: 20px;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: 500;
            color: var(--dark-color);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            margin-top: 20px;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: var(--text-color);
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            display: block;
            font-weight: 500;
            margin-top: 10px;
        }

        .btn-outline:hover {
            background-color: var(--light-color);
            text-decoration: none;
        }

        /* Color indicators */
        .color-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .checkout-container {
                flex-direction: column;
            }
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
            
            .custom-product {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .custom-product-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .custom-product-options {
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
                <li><a href="kustomisasi.php" class="active">Kustomisasi Sulaman</a></li>
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="tentang_kami.php">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
            <?php if(isset($_SESSION['username'])): ?>
            <div class="user-icons">
                <a href="keranjang.php"><i class="fas fa-shopping-cart"></i></a>
                <a href="profil.php"><i class="fas fa-user"></i></a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-title">
            <h1>Checkout Pesanan Kustom</h1>
            <p>Tinjau pesanan kustomisasi sulaman Anda dan lanjutkan ke pembayaran.</p>
        </div>

        <form action="kustom_checkout.php" method="post" id="checkoutForm">
            <div class="checkout-container">
                <div class="checkout-left">
                    <!-- Detail Produk Kustom -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            Detail Produk Kustom
                        </div>
                        <div class="checkout-card-body">
                            <div class="custom-product">
                                <?php
                                $product = $custom_order['product'];
                                $image_path = "../uploads/kustom/{$product}.jpg";
                                
                                if (!file_exists($image_path)) {
                                    $image_path = "../uploads/kustom/default.jpg";
                                }
                                ?>
                                <div class="custom-product-image">
                                    <img src="<?= $image_path ?>" alt="<?= ucwords(str_replace('-', ' ', $product)) ?>">
                                </div>
                                <div class="custom-product-details">
                                    <div class="custom-product-title"><?= ucwords(str_replace('-', ' ', $product)) ?></div>
                                    <div class="custom-product-price"><?= formatRupiah($product_price) ?></div>
                                    <div class="custom-product-options">
                                        <div class="custom-option">
                                            <strong>Warna Kain:</strong> 
                                            <?php
                                            // Kode warna berdasarkan nama warna
                                            $colorCodes = [
                                                'Putih' => '#ffffff',
                                                'Hitam' => '#000000',
                                                'Biru Dongker' => '#1d4e89',
                                                'Tosca' => '#17a398',
                                                'Kuning' => '#ffd966',
                                                'Peach' => '#ff9966',
                                                'Merah Muda' => '#e06666',
                                                'Hijau' => '#5c7e32',
                                                'Coklat' => '#b45f06',
                                                'Khaki' => '#deb887',
                                                'Biru Tua' => '#172983',
                                                'Oranye' => '#ff9900',
                                                'Merah' => '#e51e25',
                                                'Mint' => '#a8e6cf',
                                                'Biru Muda' => '#4682b4',
                                                'Biru Keunguan' => '#483d8b',
                                                'Abu-abu' => '#808080',
                                                'Oranye Tua' => '#ff6600',
                                                'Hijau Muda' => '#a0d6b4',
                                                'Hijau Kebiruan' => '#5f8a8b',
                                                'Hijau Tua' => '#2c4a52'
                                            ];
                                            $colorCode = $colorCodes[$custom_order['warna_kain']] ?? '#cccccc';
                                            ?>
                                            <span class="color-indicator" style="background-color: <?= $colorCode ?>"></span>
                                            <?= $custom_order['warna_kain'] ?>
                                        </div>
                                        <div class="custom-option">
                                            <strong>Warna Benang:</strong> 
                                            <?php
                                            $colorCode = $colorCodes[$custom_order['warna_benang']] ?? '#cccccc';
                                            ?>
                                            <span class="color-indicator" style="background-color: <?= $colorCode ?>"></span>
                                            <?= $custom_order['warna_benang'] ?>
                                        </div>
                                        <div class="custom-option">
                                            <strong>Motif:</strong> <?= $custom_order['motif'] ?>
                                        </div>
                                        <div class="custom-option">
                                            <strong>Catatan:</strong> <?= empty($custom_order['catatan']) ? 'Tidak ada' : $custom_order['catatan'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="shipping-details">
                                <div class="address-details">
                                    <h5>Alamat Pengiriman</h5>
                                    <p><strong><?= $alamat['nama_penerima'] ?></strong></p>
                                    <p><?= $alamat['alamat_lengkap'] ?></p>
                                    <p><?= $alamat['kecamatan'] ?>, <?= $alamat['kota'] ?></p>
                                    <p><?= $alamat['provinsi'] ?>, <?= $alamat['kode_pos'] ?></p>
                                    <p>Telepon: <?= $alamat['no_hp'] ?></p>
                                </div>
                                <div class="shipping-method">
                                    <div>
                                        <strong>Metode Pengiriman:</strong> <?= $custom_order['pengiriman'] ?>
                                        <p class="text-muted small">
                                            <?php if($custom_order['pengiriman'] === 'Regular'): ?>
                                            Estimasi 3-5 hari kerja
                                            <?php else: ?>
                                            Estimasi 1-2 hari kerja
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <strong><?= formatRupiah($shipping_cost) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            Metode Pembayaran
                        </div>
                        <div class="checkout-card-body">
                            <div class="payment-methods">
                                <div class="payment-option selected" data-payment="bank_transfer">
                                    <input type="radio" name="metode_pembayaran" value="Bank Transfer" id="bankTransfer" checked>
                                    <div class="payment-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-label">
                                        <div class="payment-title">Transfer Bank</div>
                                        <div class="payment-description">Lakukan pembayaran langsung ke rekening bank kami.</div>
                                    </div>
                                </div>
                                <div class="payment-option" data-payment="ewallet">
                                    <input type="radio" name="metode_pembayaran" value="E-Wallet" id="ewalet">
                                    <div class="payment-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="payment-label">
                                        <div class="payment-title">Ewallet</div>
                                        <div class="payment-description">Bayar dengan nomor dari berbagai e-wallet (OVO, GoPay, Dana, dll).</div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-right">
                    <!-- Ringkasan Pesanan -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            Ringkasan Pesanan
                        </div>
                        <div class="checkout-card-body">
                            <div class="order-summary">
                                <div class="summary-row">
                                    <div>Subtotal</div>
                                    <div><?= formatRupiah($product_price) ?></div>
                                </div>
                                <div class="summary-row">
                                    <div>Pengiriman</div>
                                    <div><?= formatRupiah($shipping_cost) ?></div>
                                </div>
                                <div class="summary-row total">
                                    <div>Total</div>
                                    <div><?= formatRupiah($total) ?></div>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">Bayar Sekarang</button>
                            <a href="kustomisasi.php" class="btn-outline">Kembali ke Kustomisasi</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pilih metode pembayaran
            const paymentOptions = document.querySelectorAll('.payment-option');
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Reset semua pilihan
                    paymentOptions.forEach(po => po.classList.remove('selected'));
                    
                    // Set pilihan aktif
                    this.classList.add('selected');
                    
                    // Check radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
            });
        });
    </script>
</body>
</html>