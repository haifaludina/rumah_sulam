<?php
require_once '../koneksi.php';
session_start();

if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: login.php?redirect=checkout');
    exit;
}

$id_pelanggan = $_SESSION['id_pelanggan'];

// Get customer data
$customer_result = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE id_pelanggan = $id_pelanggan");
$customer = mysqli_fetch_assoc($customer_result);

// Get addresses
$alamat_query = mysqli_query($koneksi, "SELECT * FROM alamat_pelanggan WHERE id_pelanggan = $id_pelanggan ORDER BY is_utama DESC, id_alamat DESC");
$alamat_list = [];
while ($alamat = mysqli_fetch_assoc($alamat_query)) {
    $alamat_list[] = $alamat;
}

$alamat_utama = null;
foreach ($alamat_list as $alamat) {
    if ($alamat['is_utama'] == 1) {
        $alamat_utama = $alamat;
        break;
    }
}

// Get cart/checkout items
$direct_checkout = isset($_GET['direct']) && $_GET['direct'] == 1 && isset($_SESSION['direct_checkout']) && $_SESSION['direct_checkout'] === true;

if ($direct_checkout) {
    $items_query = "SELECT tc.jumlah, p.* FROM temp_checkout tc JOIN produk p ON tc.id_produk = p.id_produk WHERE tc.id_pelanggan = $id_pelanggan";
} else {
    $items_query = "SELECT k.jumlah, p.* FROM keranjang k JOIN produk p ON k.id_produk = p.id_produk WHERE k.id_pelanggan = $id_pelanggan";
}

$items_result = mysqli_query($koneksi, $items_query);
if (mysqli_num_rows($items_result) == 0) {
    header('Location: keranjang.php?error=empty');
    exit;
}

$subtotal = 0;
$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $subtotal += ($item['harga'] * $item['jumlah']);
    $items[] = $item;
}

// Fungsi hitung ongkir berdasarkan lokasi
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

// Hitung berat total
$beratTotal = 0;
foreach ($items as $item) {
    // Default berat produk 500 gram jika tidak ada data
    $beratProduk = 500;
    
    // Anda bisa menambahkan kolom berat di tabel produk atau menggunakan mapping
    $beratMapping = [
        'baju-kurung' => 500,
        'kebaya' => 600,
        'kemeja' => 400,
        'selendang' => 300,
        'sendal' => 700,
        'jilbab' => 200,
        'tas' => 800,
        'sarung-bantal' => 400
    ];
    
    if (isset($beratMapping[$item['nama_produk']])) {
        $beratProduk = $beratMapping[$item['nama_produk']];
    }
    
    $beratTotal += $beratProduk * $item['jumlah'];
}

// Hitung ongkir default (akan diupdate jika alamat dipilih)
$shipping_cost = 15000;
$tax = ceil($subtotal * 0.01);
$total = $subtotal + $shipping_cost + $tax;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $id_alamat = isset($_POST['id_alamat']) ? intval($_POST['id_alamat']) : 0;
    $alamat_query = mysqli_query($koneksi, "SELECT * FROM alamat_pelanggan WHERE id_alamat = $id_alamat AND id_pelanggan = $id_pelanggan");

    if ($alamat_data = mysqli_fetch_assoc($alamat_query)) {
        $nama_penerima = $alamat_data['nama_penerima'];
        $alamat_pengiriman = $alamat_data['alamat_lengkap'] . ', ' . $alamat_data['kecamatan'] . ', ' . $alamat_data['kota'] . ', ' . $alamat_data['provinsi'] . ', ' . $alamat_data['kode_pos'];
        $email_penerima = $customer['email'];
        $telepon_penerima = $alamat_data['no_hp'];
        
        // Hitung ulang ongkir berdasarkan alamat yang dipilih
        $metode_pengiriman = mysqli_real_escape_string($koneksi, $_POST['metode_pengiriman']);
        $shipping_cost = hitungOngkir($alamat_data['kota'], $alamat_data['kecamatan'], $beratTotal, $metode_pengiriman);
        $total = $subtotal + $shipping_cost + $tax;
    } else {
        $error = "Alamat tidak valid.";
    }

    $metode_pengiriman = mysqli_real_escape_string($koneksi, $_POST['metode_pengiriman']);
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    $metode_pembayaran = mysqli_real_escape_string($koneksi, $_POST['metode_pembayaran']);

    if (!isset($error)) {
        $order_query = "INSERT INTO pesanan (id_pelanggan, tanggal_pesan, total_harga, status, nama_penerima, alamat_pengiriman, telepon_penerima, email_penerima, metode_pengiriman, biaya_pengiriman, metode_pembayaran, catatan) VALUES ($id_pelanggan, NOW(), $total, 'Menunggu', '$nama_penerima', '$alamat_pengiriman', '$telepon_penerima', '$email_penerima', '$metode_pengiriman', $shipping_cost, '$metode_pembayaran', '$catatan')";

        if (mysqli_query($koneksi, $order_query)) {
            $id_pesanan_baru = mysqli_insert_id($koneksi);
            foreach ($items as $item) {
                $item_query = "INSERT INTO item_pesanan (id_pesanan, id_produk, jumlah, harga) VALUES ($id_pesanan_baru, {$item['id_produk']}, {$item['jumlah']}, {$item['harga']})";
                mysqli_query($koneksi, $item_query);
            }
            if ($direct_checkout) {
                mysqli_query($koneksi, "DELETE FROM temp_checkout WHERE id_pelanggan = $id_pelanggan");
                unset($_SESSION['direct_checkout']);
            } else {
                mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_pelanggan = $id_pelanggan");
            }
            header("Location: pembayaran.php?order=$id_pesanan_baru");
            exit;
        } else {
            $error = "Gagal membuat pesanan: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Rumah Sulam Sefni</title>
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

        .user-icons a {
            margin-left: 15px;
            font-size: 20px;
            color: var(--text-color);
            text-decoration: none;
        }
        
        .user-icons a:hover {
            color: var(--primary-color);
        }

        /* Checkout Page Styles */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            font-size: 32px;
            text-align: center;
            margin: 30px 0;
            color: var(--primary-color);
        }

        .checkout-container {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .checkout-form {
            flex: 2;
        }

        .order-summary {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            align-self: flex-start;
        }

        .form-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
        }

        .radio-option input {
            margin-right: 5px;
        }

        .summary-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .product-list {
            margin-bottom: 20px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .product-name {
            flex: 1;
        }

        .product-quantity {
            width: 50px;
            text-align: center;
        }

        .product-price {
            width: 100px;
            text-align: right;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-weight: bold;
            font-size: 16px;
        }

        .checkout-button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 20px;
            text-align: center;
        }

        .checkout-button:hover {
            background-color: var(--secondary-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .address-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .address-card:hover {
            border-color: var(--primary-color);
            background-color: rgba(107, 66, 38, 0.05);
        }
        
        .address-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(107, 66, 38, 0.1);
        }
        
        .add-address-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .add-address-link:hover {
            text-decoration: underline;
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
            .checkout-container {
                flex-direction: column;
            }
            
            .order-summary {
                position: static;
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
            <div class="user-icons">
                <a href="keranjang.php"><i class="fas fa-shopping-cart"></i></a>
                <a href="profil.php"><i class="fas fa-user"></i></a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Checkout</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="checkout-container">
                <div class="checkout-form">
                    <div class="form-section">
                        <h2>Alamat Pengiriman</h2>
                        
                        <?php if (count($alamat_list) > 0): ?>
                            <div class="form-group">
                                <?php foreach ($alamat_list as $index => $alamat): ?>
                                    <div class="address-card <?php echo ($alamat['is_utama'] == 1) ? 'selected' : ''; ?>" 
                                         onclick="selectAddress(this, <?php echo $alamat['id_alamat']; ?>)">
                                        <input type="radio" name="id_alamat" id="alamat_<?php echo $alamat['id_alamat']; ?>" 
                                               value="<?php echo $alamat['id_alamat']; ?>" 
                                               <?php echo ($alamat['is_utama'] == 1) ? 'checked' : ''; ?> 
                                               style="display:none;">
                                        <strong><?php echo htmlspecialchars($alamat['nama_penerima']); ?></strong> 
                                        (<?php echo htmlspecialchars($alamat['no_hp']); ?>)
                                        <p><?php echo htmlspecialchars($alamat['alamat_lengkap']); ?>, 
                                           <?php echo htmlspecialchars($alamat['kecamatan']); ?>, 
                                           <?php echo htmlspecialchars($alamat['kota']); ?>, 
                                           <?php echo htmlspecialchars($alamat['provinsi']); ?>, 
                                           <?php echo htmlspecialchars($alamat['kode_pos']); ?>
                                        </p>
                                        <?php if ($alamat['is_utama'] == 1): ?>
                                            <span style="color: var(--primary-color); font-size: 12px; margin-top: 5px; display: block;">Alamat Utama</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <a href="alamat.php" class="add-address-link">+ Tambah Alamat Baru</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                Anda belum memiliki alamat pengiriman. <a href="alamat.php">Tambah alamat baru</a> terlebih dahulu.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-section">
                        <h2>Metode Pengiriman</h2>
                        <div class="form-group">
                            <label for="metode_pengiriman">Pilih Kurir</label>
                            <select id="metode_pengiriman" name="metode_pengiriman" required>
                                <option value="JNE Regular (2-3 hari)">JNE Regular (2-3 hari)</option>
                                <option value="J&T Express (2-3 hari)">J&T Express (2-3 hari)</option>
                                <option value="SiCepat Reguler (2-3 hari)">SiCepat Reguler (2-3 hari)</option>
                                <option value="AnterAja (2-3 hari)">AnterAja (2-3 hari)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="catatan">Catatan Pengiriman (Opsional)</label>
                            <textarea id="catatan" name="catatan" placeholder="Tambahkan catatan khusus untuk pengiriman"></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Metode Pembayaran</h2>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="transfer_bank" name="metode_pembayaran" value="Transfer Bank" checked>
                                <label for="transfer_bank">Transfer Bank</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="e_wallet" name="metode_pembayaran" value="E-Wallet">
                                <label for="e_wallet">E-Wallet (OVO, GoPay, Dana)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-summary">
                    <h2 class="summary-title">Ringkasan Pesanan</h2>
                    
                    <div class="product-list">
                        <?php foreach ($items as $item): ?>
                            <div class="product-item">
                                <div class="product-name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                <div class="product-quantity">(<?php echo $item['jumlah']; ?>)</div>
                                <div class="product-price">Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <div>Subtotal</div>
                        <div>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Pengiriman</div>
                        <div id="shippingCostDisplay">Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Pajak</div>
                        <div>Rp <?php echo number_format($tax, 0, ',', '.'); ?></div>
                    </div>
                    <div class="total-row">
                        <div>Total</div>
                        <div id="totalDisplay">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                    </div>
                    
                    <button type="submit" name="checkout" class="checkout-button" <?php echo (count($alamat_list) == 0) ? 'disabled' : ''; ?>>
                        Selesaikan Pembayaran
                    </button>
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

    <script>
        function selectAddress(element, id) {
            // Remove selected class from all cards
            const cards = document.querySelectorAll('.address-card');
            cards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById('alamat_' + id).checked = true;
            
            // Hitung ulang ongkir
            updateShippingCost();
        }
        
        function updateShippingCost() {
            const selectedAddress = document.querySelector('input[name="id_alamat"]:checked');
            if (!selectedAddress) return;
            
            const addressId = selectedAddress.value;
            
            // Kirim permintaan AJAX untuk menghitung ongkir
            fetch('hitung_ongkir.php?id=' + addressId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('shippingCostDisplay').textContent = 'Rp ' + data.shipping_cost.toLocaleString('id-ID');
                        document.getElementById('totalDisplay').textContent = 'Rp ' + data.total.toLocaleString('id-ID');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Update ongkir saat metode pengiriman berubah
        document.getElementById('metode_pengiriman').addEventListener('change', updateShippingCost);
    </script>
</body>
</html>