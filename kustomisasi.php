<?php
session_start();
require_once '../koneksi.php';

// Periksa login
if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: ../login.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];

// Ambil data pelanggan
$query_pelanggan = $koneksi->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$query_pelanggan->bind_param("i", $id_pelanggan);
$query_pelanggan->execute();
$pelanggan = $query_pelanggan->get_result()->fetch_assoc();

// Ambil alamat pelanggan
$query_alamat = $koneksi->prepare("SELECT 
    id_alamat, 
    id_pelanggan, 
    nama_penerima, 
    no_hp, 
    provinsi, 
    kota, 
    kecamatan, 
    kode_pos, 
    alamat_lengkap, 
    is_utama 
    FROM alamat_pelanggan 
    WHERE id_pelanggan = ? 
    ORDER BY is_utama DESC");
$query_alamat->bind_param("i", $id_pelanggan);
$query_alamat->execute();
$addresses = $query_alamat->get_result()->fetch_all(MYSQLI_ASSOC);

// Data berat produk (dalam gram)
$berat_produk = [
    'baju-kurung' => 500,
    'kebaya' => 600,
    'kemeja' => 400,
    'selendang' => 300,
    'sendal' => 700,
    'jilbab' => 200,
    'tas' => 800,
    'sarung-bantal' => 400
];

// Data motif sulaman
$motifs = [
    [
        'id' => 'KapaloSamek',
        'name' => 'Motif Kapalo Samek',
        'image' => 'kapalosamek.jpg',
        'desc' => 'Sulaman kapalo samek dari Kota Gadang dengan teknik mengait dan menarik benang hingga ujung peniti'
    ],
    [
        'id' => 'Pucuak Rabuang',
        'name' => 'Motif Pucuak Rabuang',
        'image' => 'pucuak-rabuang.jpg',
        'desc' => 'Simbol pertumbuhan dan harapan; terinspirasi dari tunas bambu'
    ],
    [
        'id' => 'Kaluak Paku',
        'name' => 'Motif Kaluak Paku',
        'image' => 'kaluak-paku.jpg',
        'desc' => 'Lambang ketahanan dan kekuatan; dari tumbuhan pakis'
    ],
    [
        'id' => 'Itiak Pulang Patang',
        'name' => 'Motif Itiak Pulang Patang',
        'image' => 'itiak-pulang-patang.jpg',
        'desc' => 'Motif menyerupai bebek pulang di sore hari'
    ],
    [
        'id' => 'Siriah Gadang',
        'name' => 'Motif Siriah Gadang',
        'image' => 'siriah-gadang.jpg',
        'desc' => 'Filosofi penghormatan dan kemuliaan'
    ],
    [
        'id' => 'Aka Cino',
        'name' => 'Motif Aka Cino',
        'image' => 'aka-cino.jpg',
        'desc' => 'Melambangkan keceriaan dan semangat hidup'
    ]
];

// Fungsi hitung ongkir yang sama dengan checkout.php
function hitungOngkir($kotaTujuan, $kecamatanTujuan, $beratTotal, $metode) {
    // Lokasi toko
    $kotaPengirim = 'Tanah Datar';
    $kecamatanPengirim = 'Salimpaung';
    
    // Tarif dasar per km
    $tarifPerKm = 150;
    
    // Data jarak dari Salimpaung ke berbagai kota (dalam km)
    $jarakData = [
        // Dalam kota Tanah Datar
        'Tanah Datar' => [
            'Salimpaung' => 0, // Lokasi toko
            'Batipuh' => 25,
            'Pariangan' => 30,
            'Rambatan' => 20,
            'Lima Kaum' => 15
        ],
        // Kota lain di Sumbar
        'Padang Panjang' => 40,
        'Bukittinggi' => 60,
        'Padang' => 100,
        'Solok' => 80,
        'Pesisir Selatan' => 120,
        // Kota lain di Indonesia
        'Pekanbaru' => 300,
        'Medan' => 350,
        'Jambi' => 400,
        'Palembang' => 600,
        'Bengkulu' => 500,
        'Bandar Lampung' => 700,
        'Jakarta' => 1200,
        'Bandung' => 1100,
        'Semarang' => 1300,
        'Yogyakarta' => 1350,
        'Surabaya' => 1500,
        'Malang' => 1550,
        'Denpasar' => 1700,
        'Makassar' => 2200,
        'Manado' => 2400
    ];
    
    // Hitung jarak
    $jarak = 100; // default 100 km jika tidak ditemukan
    
    if ($kotaTujuan == $kotaPengirim) {
        // Jika dalam kota yang sama
        if (isset($jarakData[$kotaPengirim][$kecamatanTujuan])) {
            $jarak = $jarakData[$kotaPengirim][$kecamatanTujuan];
        } else {
            $jarak = 30; // default untuk dalam kota
        }
    } else {
        // Jika beda kota
        if (isset($jarakData[$kotaTujuan])) {
            $jarak = $jarakData[$kotaTujuan];
        }
    }
    
    // Ongkir dasar berdasarkan jarak
    $ongkirDasar = $jarak * $tarifPerKm;
    
    // Tambahan biaya untuk express (+50%)
    if ($metode === 'Express') {
        $ongkirDasar *= 1.5;
    }
    
    // Biaya berat: Rp 10.000 untuk 1 kg pertama, +Rp 5.000/kg berikutnya
    $beratKg = ceil($beratTotal / 1000);
    $biayaBerat = 10000 + (max(0, $beratKg - 1) * 5000);
    
    // Total ongkir (minimum Rp 15.000)
    return max(15000, $ongkirDasar + $biayaBerat);
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $required_fields = [
        'selected_product' => 'Silakan pilih produk',
        'warna_kain' => 'Silakan pilih warna kain',
        'warna_benang' => 'Silakan pilih warna benang',
        'motif' => 'Silakan pilih motif sulaman',
        'alamat_id' => 'Silakan pilih alamat pengiriman',
        'pengiriman' => 'Silakan pilih metode pengiriman'
    ];
    
    foreach ($required_fields as $field => $message) {
        if (empty($_POST[$field])) {
            $errors[] = $message;
        }
    }
    
    if (empty($errors)) {
        $beratTotal = $berat_produk[$_POST['selected_product']];
        
        // Hitung ongkir
        $selectedAddressId = $_POST['alamat_id'];
        $selectedAddress = array_filter($addresses, function($addr) use ($selectedAddressId) {
            return $addr['id_alamat'] == $selectedAddressId;
        });
        $selectedAddress = reset($selectedAddress);
        
        $ongkir = hitungOngkir(
            $selectedAddress['kota'],
            $selectedAddress['kecamatan'],
            $beratTotal,
            $_POST['pengiriman']
        );
        
        $_SESSION['custom_order'] = [
            'product' => $_POST['selected_product'],
            'product_price' => $_POST['product_price'],
            'berat' => $beratTotal,
            'warna_kain' => $_POST['warna_kain'],
            'warna_benang' => $_POST['warna_benang'],
            'motif' => $_POST['motif'],
            'alamat_id' => $_POST['alamat_id'],
            'pengiriman' => $_POST['pengiriman'],
            'shipping_cost' => $ongkir,
            'catatan' => $_POST['catatan'] ?? ''
        ];
        
        header('Location: kustom_checkout.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kustomisasi Sulaman - Rumah Sulam Sefni</title>
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
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background-color:rgb(255, 255, 255);
        }

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
        }

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

        .custom-step {
            margin-bottom: 40px;
            background-color: var(--white-color);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .custom-step h2 {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--primary-color);
        }

        .product-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
        }

        .product-option {
            width: calc(25% - 15px);
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-option:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }

        .product-option.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .product-img {
            height: 180px;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
            text-align: center;
        }

        .product-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .product-price {
            font-size: 14px;
            color: #666;
        }

        .color-section {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }

        .color-column {
            flex: 1;
        }

        .color-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .color-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .color-option {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #eee;
            transition: all 0.2s;
        }

        .color-option:hover {
            transform: scale(1.1);
        }

        .color-option.selected {
            border: 2px solid var(--primary-color);
            box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary-color);
        }

        .motif-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .motif-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
        }

        .motif-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .motif-image-container {
            height: 200px;
            overflow: hidden;
        }

        .motif-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .motif-card:hover .motif-image {
            transform: scale(1.05);
        }

        .motif-content {
            padding: 20px;
        }

        .motif-content h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 18px;
        }

        .motif-content p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .motif-card input[type="radio"] {
            display: none;
        }

        .motif-card.selected {
            border: 2px solid var(--primary-color);
            background-color: rgba(109, 76, 65, 0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .shipping-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .shipping-option {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .shipping-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(109, 76, 65, 0.05);
        }

        .shipping-option input {
            margin-right: 10px;
            margin-top: 3px;
        }

        .shipping-details {
            flex: 1;
        }

        .shipping-type {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .shipping-price {
            font-weight: 500;
            color: var(--primary-color);
        }

        .shipping-est {
            font-size: 13px;
            color: #666;
        }

        .order-summary {
            background-color: var(--light-color);
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table tr {
            border-bottom: 1px solid #ddd;
        }

        .summary-table td {
            padding: 10px 0;
        }

        .summary-table td:last-child {
            text-align: right;
            font-weight: 500;
        }

        .total-row {
            font-weight: bold;
            font-size: 18px;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        @media (max-width: 992px) {
            .product-option {
                width: calc(33.33% - 13.33px);
            }
        }

        @media (max-width: 768px) {
            .product-option {
                width: calc(50% - 10px);
            }
            
            .color-section {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .product-option {
                width: 100%;
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
            <?php else: ?>
            <div class="auth-buttons">
                <a href="../daftar.php" class="btn btn-outline-secondary">Daftar</a>
                <a href="../masuk.php" class="btn btn-primary">Masuk</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-title">
            <h1>Kustomisasi Sulaman</h1>
            <p>Buat pesanan sulaman sesuai keinginan Anda dengan memilih kategori produk, warna, dan motif khas Minangkabau.</p>
        </div>

        <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul>
                <?php foreach($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="kustomisasi.php" method="post" id="customForm">
            <!-- Langkah 1: Pilih Produk -->
            <div class="custom-step">
                <h2>1. Pilih Produk</h2>
                <div class="product-grid">
                    <?php
                    $products = [
                        ['id' => 'baju-kurung', 'name' => 'Baju Kurung', 'price' => 900000, 'image' => 'baju-kurung.jpg'],
                        ['id' => 'kebaya', 'name' => 'Kebaya', 'price' => 850000, 'image' => 'Kebaya.jpg'],
                        ['id' => 'kemeja', 'name' => 'Kemeja', 'price' => 450000, 'image' => 'Kemeja.jpg'],
                        ['id' => 'selendang', 'name' => 'Selendang', 'price' => 1200000, 'image' => 'Selendang.jpg'],
                        ['id' => 'sendal', 'name' => 'Sendal', 'price' => 300000, 'image' => 'Sendal.jpg'],
                        ['id' => 'jilbab', 'name' => 'Jilbab', 'price' => 85000, 'image' => 'jilbab.jpg'],
                        ['id' => 'tas', 'name' => 'Tas', 'price' => 120000, 'image' => 'tas.jpg'],
                        ['id' => 'sarung-bantal', 'name' => 'Sarung Bantal', 'price' => 65000, 'image' => 'Sarung-Bantal.jpg']
                    ];
                    
                    foreach ($products as $product): 
                        $weight = $berat_produk[$product['id']] ?? 500;
                    ?>
                    <div class="product-option" 
                         data-product="<?= $product['id'] ?>" 
                         data-price="<?= $product['price'] ?>" 
                         data-weight="<?= $weight ?>">
                        <div class="product-img">
                            <img src="../uploads/kustom/<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                        </div>
                        <div class="product-info">
                            <h3><?= $product['name'] ?></h3>
                            <p class="product-price">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="selected_product" id="selectedProduct">
                <input type="hidden" name="product_price" id="productPrice">
            </div>

            <!-- Langkah 2: Pilih Warna -->
            <div class="custom-step">
                <h2>2. Pilih Warna</h2>
                <div class="color-section">
                    <div class="color-column">
                        <div class="color-title">Warna Kain</div>
                        <div class="color-grid">
                            <div class="color-option" style="background-color: #ffffff;" data-color="Putih"></div>
                            <div class="color-option" style="background-color: #000000;" data-color="Hitam"></div>
                            <div class="color-option" style="background-color: #1d4e89;" data-color="Biru Dongker"></div>
                            <div class="color-option" style="background-color: #17a398;" data-color="Tosca"></div>
                            <div class="color-option" style="background-color: #ffd966;" data-color="Kuning"></div>
                            <div class="color-option" style="background-color: #ff9966;" data-color="Peach"></div>
                            <div class="color-option" style="background-color: #e06666;" data-color="Merah Muda"></div>
                            <div class="color-option" style="background-color: #5c7e32;" data-color="Hijau"></div>
                            <div class="color-option" style="background-color: #b45f06;" data-color="Coklat"></div>
                            <div class="color-option" style="background-color: #deb887;" data-color="Khaki"></div>
                        </div>
                        <input type="hidden" name="warna_kain" id="warnaKain">
                    </div>
                    <div class="color-column">
                        <div class="color-title">Warna Benang</div>
                        <div class="color-grid">
                            <div class="color-option" style="background-color: #000000;" data-color="Hitam"></div>
                            <div class="color-option" style="background-color: #172983;" data-color="Biru Tua"></div>
                            <div class="color-option" style="background-color: #ff9900;" data-color="Oranye"></div>
                            <div class="color-option" style="background-color: #e51e25;" data-color="Merah"></div>
                            <div class="color-option" style="background-color: #a8e6cf;" data-color="Mint"></div>
                            <div class="color-option" style="background-color: #4682b4;" data-color="Biru Muda"></div>
                            <div class="color-option" style="background-color: #483d8b;" data-color="Biru Keunguan"></div>
                            <div class="color-option" style="background-color: #808080;" data-color="Abu-abu"></div>
                            <div class="color-option" style="background-color: #f0f0f0;" data-color="Putih"></div>
                            <div class="color-option" style="background-color: #ffcc00;" data-color="Kuning"></div>
                            <div class="color-option" style="background-color: #ff6600;" data-color="Oranye Tua"></div>
                            <div class="color-option" style="background-color: #a0d6b4;" data-color="Hijau Muda"></div>
                            <div class="color-option" style="background-color: #5f8a8b;" data-color="Hijau Kebiruan"></div>
                            <div class="color-option" style="background-color: #2c4a52;" data-color="Hijau Tua"></div>
                        </div>
                        <input type="hidden" name="warna_benang" id="warnaBenang">
                    </div>
                </div>
            </div>

            <!-- Langkah 3: Pilih Motif -->
            <div class="custom-step">
                <h2>3. Pilih Motif Sulaman</h2>
                <div class="motif-container">
                    <?php foreach ($motifs as $motif): ?>
                    <div class="motif-card" data-motif="<?= $motif['id'] ?>">
                        <div class="motif-image-container">
                            <img src="../uploads/motif/<?= $motif['image'] ?>" alt="<?= $motif['name'] ?>" class="motif-image">
                        </div>
                        <div class="motif-content">
                            <h3><?= $motif['name'] ?></h3>
                            <p><?= $motif['desc'] ?></p>
                            <input type="radio" name="motif" value="<?= $motif['id'] ?>" id="motif_<?= $motif['id'] ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Langkah 4: Informasi Pengiriman -->
            <div class="custom-step">
                <h2>4. Informasi Pengiriman</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="selectAddress">Pilih Alamat</label>
                            <select name="alamat_id" id="selectAddress" class="form-control" required>
                                <option value="">-- Pilih Alamat --</option>
                                <?php foreach($addresses as $address): ?>
                                <option value="<?= $address['id_alamat'] ?>" 
                                    data-kota="<?= htmlspecialchars($address['kota']) ?>"
                                    data-kecamatan="<?= htmlspecialchars($address['kecamatan']) ?>"
                                    <?= $address['is_utama'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($address['nama_penerima']) ?> - 
                                    <?= htmlspecialchars($address['alamat_lengkap']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <a href="alamat.php" class="btn btn-sm btn-outline-primary">Kelola Alamat</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Metode Pengiriman</label>
                            <div class="shipping-options">
                                <div class="shipping-option selected" data-shipping="regular">
                                    <input type="radio" name="pengiriman" value="Regular" id="shippingRegular" checked>
                                    <div class="shipping-details">
                                        <div class="shipping-type">Regular</div>
                                        <div class="shipping-price" id="shippingCostDisplay">Rp 15.000</div>
                                        <div class="shipping-est">Estimasi 3-5 hari</div>
                                    </div>
                                </div>
                                <div class="shipping-option" data-shipping="express">
                                    <input type="radio" name="pengiriman" value="Express" id="shippingExpress">
                                    <div class="shipping-details">
                                        <div class="shipping-type">Express</div>
                                        <div class="shipping-price" id="shippingExpressCost">Rp 30.000</div>
                                        <div class="shipping-est">Estimasi 1-2 hari</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="shipping_cost" id="shippingCost" value="15000">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="catatan">Catatan (Opsional)</label>
                    <textarea name="catatan" id="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan khusus untuk pesanan Anda"></textarea>
                </div>
            </div>

            <!-- Langkah 5: Ringkasan Pesanan -->
            <div class="custom-step">
                <h2>5. Ringkasan Pesanan</h2>
                <div class="order-summary">
                    <table class="summary-table">
                        <tr>
                            <td>Produk</td>
                            <td id="summaryProduct">-</td>
                        </tr>
                        <tr>
                            <td>Harga Produk</td>
                            <td id="summaryPrice">Rp 0</td>
                        </tr>
                        <tr>
                            <td>Warna Kain</td>
                            <td id="summaryFabricColor">-</td>
                        </tr>
                        <tr>
                            <td>Warna Benang</td>
                            <td id="summaryThreadColor">-</td>
                        </tr>
                        <tr>
                            <td>Motif</td>
                            <td id="summaryMotif">-</td>
                        </tr>
                        <tr>
                            <td>Biaya Pengiriman</td>
                            <td id="summaryShipping">Rp 0</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total</td>
                            <td id="summaryTotal">Rp 0</td>
                        </tr>
                    </table>
                </div>
                <button type="submit" class="submit-btn" id="submitOrder" disabled>Lanjutkan ke Pembayaran</button>
            </div>
        </form>
    </div>

    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container text-center">
            <div class="social-contact mb-3">
                <span class="me-3">Temukan kami di:</span>
                <a href="https://www.instagram.com/rumahsulam_sefni/" target="_blank" class="text-dark mx-2"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/sefni.akhirda.3" target="_blank" class="text-dark mx-2"><i class="fab fa-facebook"></i></a>
                <a href="https://wa.me/6281234567890" target="_blank" class="text-dark mx-2"><i class="fab fa-whatsapp"></i></a>
            </div>
            <div class="copyright text-muted small">
                &copy; 2025 Rumah Sulam Sefni. Hak Cipta Dilindungi
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variabel global
            let productPrice = 0;
            let productWeight = 0;
            let shippingCost = 15000;
            
            // Fungsi untuk format rupiah
            function formatRupiah(angka) {
                return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }
            
            // Fungsi hitung ongkir di JavaScript (sama dengan PHP)
            function hitungOngkir(kotaTujuan, kecamatanTujuan, beratTotal, metode) {
                // Lokasi toko
                const kotaPengirim = 'Tanah Datar';
                const kecamatanPengirim = 'Salimpaung';
                
                // Tarif dasar per km
                const tarifPerKm = 150;
                
                // Data jarak dari Salimpaung ke berbagai kota (dalam km)
                const jarakData = {
                    // Dalam kota Tanah Datar
                    'Tanah Datar': {
                        'Salimpaung': 0, // Lokasi toko
                        'Batipuh': 25,
                        'Pariangan': 30,
                        'Rambatan': 20,
                        'Lima Kaum': 15
                    },
                    // Kota lain di Sumbar
                    'Padang Panjang': 40,
                    'Bukittinggi': 60,
                    'Padang': 100,
                    'Solok': 80,
                    'Pesisir Selatan': 120,
                    // Kota lain di Indonesia
                    'Pekanbaru': 300,
                    'Medan': 350,
                    'Jambi': 400,
                    'Palembang': 600,
                    'Bengkulu': 500,
                    'Bandar Lampung': 700,
                    'Jakarta': 1200,
                    'Bandung': 1100,
                    'Semarang': 1300,
                    'Yogyakarta': 1350,
                    'Surabaya': 1500,
                    'Malang': 1550,
                    'Denpasar': 1700,
                    'Makassar': 2200,
                    'Manado': 2400
                };
                
                // Hitung jarak (default 100 km jika tidak ditemukan)
                let jarak = 100;
                
                if (kotaTujuan == kotaPengirim) {
                    // Jika dalam kota yang sama
                    if (jarakData[kotaPengirim] && jarakData[kotaPengirim][kecamatanTujuan]) {
                        jarak = jarakData[kotaPengirim][kecamatanTujuan];
                    } else {
                        jarak = 30; // default untuk dalam kota
                    }
                } else {
                    // Jika beda kota
                    if (jarakData[kotaTujuan]) {
                        jarak = jarakData[kotaTujuan];
                    }
                }
                
                // Ongkir dasar berdasarkan jarak
                let ongkirDasar = jarak * tarifPerKm;
                
                // Tambahan biaya untuk express (+50%)
                if (metode === 'Express') {
                    ongkirDasar *= 1.5;
                }
                
                // Biaya berat: Rp 10.000 untuk 1 kg pertama, +Rp 5.000/kg berikutnya
                const beratKg = Math.ceil(beratTotal / 1000);
                const ongkirBerat = 10000 + (Math.max(0, beratKg - 1)) * 5000;
                
                // Total ongkir (minimum Rp 15.000)
                return Math.max(15000, ongkirDasar + ongkirBerat);
            }
            
            // Pilih Produk
            const productOptions = document.querySelectorAll('.product-option');
            productOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Reset semua pilihan
                    productOptions.forEach(po => po.classList.remove('selected'));
                    
                    // Set pilihan aktif
                    this.classList.add('selected');
                    
                    // Simpan data produk
                    const productName = this.dataset.product;
                    productPrice = parseInt(this.dataset.price);
                    productWeight = parseInt(this.dataset.weight);
                    
                    // Update input hidden
                    document.getElementById('selectedProduct').value = productName;
                    document.getElementById('productPrice').value = productPrice;
                    
                    // Hitung ulang ongkir jika alamat sudah dipilih
                    const selectedAddress = document.getElementById('selectAddress');
                    if (selectedAddress.value) {
                        const selectedOption = selectedAddress.options[selectedAddress.selectedIndex];
                        const kotaTujuan = selectedOption.dataset.kota;
                        const kecamatanTujuan = selectedOption.dataset.kecamatan;
                        const metode = document.querySelector('input[name="pengiriman"]:checked').value;
                        
                        const ongkir = hitungOngkir(kotaTujuan, kecamatanTujuan, productWeight, metode);
                        
                        shippingCost = ongkir;
                        document.getElementById('shippingCost').value = shippingCost;
                        
                        if (metode === 'Regular') {
                            document.getElementById('shippingCostDisplay').textContent = formatRupiah(ongkir);
                        } else {
                            document.getElementById('shippingExpressCost').textContent = formatRupiah(ongkir);
                        }
                    }
                    
                    // Update ringkasan
                    updateOrderSummary();
                });
            });
            
            // Pilih Warna Kain dan Benang
            document.querySelectorAll('.color-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Cari parent color-column
                    const colorColumn = this.closest('.color-column');
                    
                    // Hapus selected dari semua option dalam column yang sama
                    colorColumn.querySelectorAll('.color-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Tambahkan selected ke option yang diklik
                    this.classList.add('selected');
                    
                    // Update input hidden yang sesuai
                    const isWarnaKain = colorColumn.querySelector('.color-title').textContent.includes('Kain');
                    const inputId = isWarnaKain ? 'warnaKain' : 'warnaBenang';
                    document.getElementById(inputId).value = this.dataset.color;
                    
                    updateOrderSummary();
                });
            });
            
            // Pilih Motif
            document.querySelectorAll('.motif-card').forEach(card => {
                card.addEventListener('click', function() {
                    // Hapus selected dari semua card
                    document.querySelectorAll('.motif-card').forEach(c => {
                        c.classList.remove('selected');
                    });
                    
                    // Set pilihan aktif
                    this.classList.add('selected');
                    
                    // Check radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    updateOrderSummary();
                });
            });
            
            // Pilih Pengiriman
            document.querySelectorAll('.shipping-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Reset semua pilihan
                    document.querySelectorAll('.shipping-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Set pilihan aktif
                    this.classList.add('selected');
                    
                    // Check radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Hitung ulang ongkir jika alamat dan produk sudah dipilih
                    const selectedAddress = document.getElementById('selectAddress');
                    if (selectedAddress.value && productWeight > 0) {
                        const selectedOption = selectedAddress.options[selectedAddress.selectedIndex];
                        const kotaTujuan = selectedOption.dataset.kota;
                        const kecamatanTujuan = selectedOption.dataset.kecamatan;
                        const metode = radio.value;
                        
                        const ongkir = hitungOngkir(kotaTujuan, kecamatanTujuan, productWeight, metode);
                        
                        shippingCost = ongkir;
                        document.getElementById('shippingCost').value = shippingCost;
                        
                        if (metode === 'Regular') {
                            document.getElementById('shippingCostDisplay').textContent = formatRupiah(ongkir);
                        } else {
                            document.getElementById('shippingExpressCost').textContent = formatRupiah(ongkir);
                        }
                    }
                    
                    updateOrderSummary();
                });
            });
            
            // Hitung ongkir saat alamat berubah
            document.getElementById('selectAddress').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const kotaTujuan = selectedOption.dataset.kota;
                const kecamatanTujuan = selectedOption.dataset.kecamatan;
                const metode = document.querySelector('input[name="pengiriman"]:checked').value;
                
                if (kotaTujuan && productWeight > 0) {
                    const ongkir = hitungOngkir(kotaTujuan, kecamatanTujuan, productWeight, metode);
                    
                    shippingCost = ongkir;
                    document.getElementById('shippingCost').value = shippingCost;
                    
                    if (metode === 'Regular') {
                        document.getElementById('shippingCostDisplay').textContent = formatRupiah(ongkir);
                    } else {
                        document.getElementById('shippingExpressCost').textContent = formatRupiah(ongkir);
                    }
                    
                    updateOrderSummary();
                }
            });
            
            // Update ringkasan pesanan
            function updateOrderSummary() {
                // Produk
                const selectedProduct = document.querySelector('.product-option.selected');
                const productName = selectedProduct ? selectedProduct.querySelector('h3').textContent : '-';
                document.getElementById('summaryProduct').textContent = productName;
                
                // Harga
                document.getElementById('summaryPrice').textContent = formatRupiah(productPrice || 0);
                
                // Warna Kain
                const selectedFabricColor = document.querySelector('.color-column:first-child .color-option.selected');
                const fabricColor = selectedFabricColor ? selectedFabricColor.dataset.color : '-';
                document.getElementById('summaryFabricColor').textContent = fabricColor;
                
                // Warna Benang
                const selectedThreadColor = document.querySelector('.color-column:last-child .color-option.selected');
                const threadColor = selectedThreadColor ? selectedThreadColor.dataset.color : '-';
                document.getElementById('summaryThreadColor').textContent = threadColor;
                
                // Motif
                const selectedMotif = document.querySelector('.motif-card.selected');
                const motif = selectedMotif ? selectedMotif.dataset.motif : '-';
                document.getElementById('summaryMotif').textContent = motif;
                
                // Biaya Pengiriman
                document.getElementById('summaryShipping').textContent = formatRupiah(shippingCost || 0);
                
                // Total
                const total = productPrice + shippingCost;
                document.getElementById('summaryTotal').textContent = formatRupiah(total);
                
                // Validasi form sebelum submit
                validateForm();
            }
            
            // Validasi form sebelum submit
            function validateForm() {
                const submitBtn = document.getElementById('submitOrder');
                const isValid = document.querySelector('.product-option.selected') &&
                               document.querySelector('.color-column:first-child .color-option.selected') &&
                               document.querySelector('.color-column:last-child .color-option.selected') &&
                               document.querySelector('.motif-card.selected') &&
                               document.getElementById('selectAddress').value;
                
                submitBtn.disabled = !isValid;
            }
            
            // Panggil validasi saat pertama kali load
            validateForm();
            
            // Validasi saat ada perubahan
            document.getElementById('customForm').addEventListener('change', validateForm);
        });
    </script>
</body>
</html>