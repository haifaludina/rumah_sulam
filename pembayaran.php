<?php
// Include database connection
require_once '../koneksi.php';

// Start session for login status
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: masuk.php?redirect=pembayaran');
    exit;
}

// Get customer ID from session
$username = $_SESSION['username'];
$id_pelanggan = $_SESSION['id_pelanggan'] ?? null;

// If id_pelanggan is not in session, get it from database
if (!$id_pelanggan) {
    $get_id_query = "SELECT id_pelanggan FROM pelanggan WHERE username = '$username'";
    $result = mysqli_query($koneksi, $get_id_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id_pelanggan = $row['id_pelanggan'];
        $_SESSION['id_pelanggan'] = $id_pelanggan;
    } else {
        // Handle error: username tidak ditemukan di tabel pelanggan
        echo "Error: Username tidak ditemukan.";
        exit;
    }
}

if (!isset($_GET['id'])) {
    header('Location: pesanan_saya.php');
    exit;
}
$order_id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Get order details from database
$order_query = "SELECT * FROM pesanan WHERE id_pesanan = '$order_id' AND id_pelanggan = $id_pelanggan";
$order_result = mysqli_query($koneksi, $order_query);

// Check if order exists
if (mysqli_num_rows($order_result) == 0) {
    header('Location: pesanan_saya.php?error=invalid_order');
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Process payment confirmation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $payment_method = mysqli_real_escape_string($koneksi, $_POST['payment_method'] ?? 'bank_transfer');
    
    // Check if file was uploaded
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validate file
        if (in_array($_FILES['bukti_transfer']['type'], $allowed_types) && $_FILES['bukti_transfer']['size'] <= $max_size) {
            // Generate unique filename
            $file_extension = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
            $new_filename = 'payment_' . $order_id . '_' . time() . '.' . $file_extension;
            $upload_path = '../uploads/payments/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../uploads/payments/')) {
                mkdir('../uploads/payments/', 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_path)) {
                // Update order status to 'diproses' dan status pembayaran ke 'lunas'
                $update_query = "UPDATE pesanan SET 
                                status = 'diproses', 
                                bukti_pembayaran = '$new_filename', 
                                tanggal_pembayaran = NOW(),
                                status_pembayaran = 'lunas',
                                metode_pembayaran = '$payment_method'
                                WHERE id_pesanan = '$order_id'";
                
                if (mysqli_query($koneksi, $update_query)) {
                    // Redirect to order page with success message
                    header('Location: pesanan_saya.php?success=payment_confirmed');
                    exit;
                } else {
                    $error = "Gagal memperbarui status pesanan: " . mysqli_error($koneksi);
                }
            } else {
                $error = "Gagal mengunggah file bukti transfer.";
            }
        } else {
            $error = "File tidak valid. Gunakan format JPG, JPEG atau PNG dengan ukuran maksimal 2MB.";
        }
    } else {
        $error = "Silakan pilih file bukti transfer.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - Rumah Sulam Sefni</title>
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

        /* Breadcrumb */
        .breadcrumb {
            padding: 20px 50px;
            background-color: #f0f0f0;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb span {
            margin: 0 5px;
            color: #777;
        }

        /* Main Container */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            font-size: 28px;
            text-align: center;
            margin: 20px 0;
            color: var(--primary-color);
        }

        .payment-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .payment-info {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .bank-details {
            background-color: var(--grey-color);
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .bank-details h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .bank-details p {
            margin: 5px 0;
        }

        .bank-account {
            font-weight: bold;
        }

        .amount {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }

        .instructions {
            margin-top: 25px;
        }

        .instructions h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .instructions ol {
            margin-left: 20px;
            margin-bottom: 20px;
        }

        .instructions li {
            margin-bottom: 10px;
        }

        .upload-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .submit-btn {
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
            text-align: center;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .error-message {
            color: #d9534f;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9eaea;
            border-radius: 4px;
        }

        /* Payment Methods */
        .payment-methods {
            margin-bottom: 25px;
        }

        .payment-methods h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .method-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .method-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            background: #f5f5f5;
            border-radius: 5px 5px 0 0;
        }

        .method-tab.active {
            background: white;
            border-color: #ddd;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .method-content {
            display: none;
        }

        .method-content.active {
            display: block;
        }

        .wallet-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .wallet-option:hover {
            border-color: var(--primary-color);
            background-color: #f9f5f2;
        }

        .wallet-option input {
            margin-right: 10px;
        }

        .wallet-logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            object-fit: contain;
        }

        .wallet-details {
            flex-grow: 1;
        }

        .wallet-name {
            font-weight: bold;
        }

        .wallet-number {
            font-size: 14px;
            color: #666;
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

        .contact-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .contact-info a {
            color: var(--text-color);
            text-decoration: none;
            margin-left: 10px;
        }

        .copyright {
            font-size: 12px;
            color: #666;
        }

        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
            }
            
            nav ul {
                display: none;
            }
            
            .breadcrumb {
                padding: 15px 20px;
            }
            
            .container {
                padding: 15px;
            }
            
            .payment-container {
                padding: 20px;
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

    <div class="breadcrumb">
        <a href="profil.php">Profil Saya</a> <span>></span> 
        <a href="pesanan_saya.php">Pesanan Saya</a> <span>></span> 
        <span>Bayar</span>
    </div>

    <div class="container">
        <h1>Konfirmasi Pembayaran</h1>

        <div class="payment-container">
            <div class="payment-info">
                <p>ID Pesanan: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
                <p>Status: <strong><?php echo htmlspecialchars($order['status']); ?></strong></p>
                <p>Tanggal Pesanan: <strong><?php echo date('d-m-Y H:i', strtotime($order['tanggal_pesan'])); ?></strong></p>
            </div>

            <div class="payment-methods">
                <h3>Metode Pembayaran</h3>
                
                <div class="method-tabs">
                    <div class="method-tab active" data-tab="bank-transfer">Transfer Bank</div>
                    <div class="method-tab" data-tab="e-wallet">E-Wallet</div>
                </div>
                
                <div id="bank-transfer" class="method-content active">
                    <div class="bank-details">
                        <h3>Silakan transfer ke rekening bank:</h3>
                        <p class="bank-account">Rumah Sulam Sefni</p>
                        <p>Bank BCA</p>
                        <p>Nomor Rekening: 1234-5678-9012</p>
                        <p>Bank Mandiri</p>
                        <p>Nomor Rekening: 9876-5432-1098</p>
                        <p class="amount">Jumlah: Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></p>
                    </div>
                </div>
                
                <div id="e-wallet" class="method-content">
                    <div class="bank-details">
                        <h3>Silakan transfer ke e-wallet:</h3>
                        
                        <div class="wallet-option">
                            <input type="radio" name="payment_method" value="ovo" id="ovo" checked>
                            <img src="../uploads/ovo.jpg" alt="OVO" class="wallet-logo">
                            <div class="wallet-details">
                                <div class="wallet-name">OVO</div>
                                <div class="wallet-number">0811 6632 626 (Rumah Sulam Sefni)</div>
                            </div>
                        </div>
                        
                        <div class="wallet-option">
                            <input type="radio" name="payment_method" value="gopay" id="gopay">
                            <img src="../uploads/gopay.png" alt="GoPay" class="wallet-logo">
                            <div class="wallet-details">
                                <div class="wallet-name">GoPay</div>
                                <div class="wallet-number">0811 6632 626 (Rumah Sulam Sefni)</div>
                            </div>
                        </div>
                        
                        <div class="wallet-option">
                            <input type="radio" name="payment_method" value="dana" id="dana">
                            <img src="../uploads/dana.jpg" alt="DANA" class="wallet-logo">
                            <div class="wallet-details">
                                <div class="wallet-name">DANA</div>
                                <div class="wallet-number">0811 6632 626 (Rumah Sulam Sefni)</div>
                            </div>
                        </div>
                        
                        <p class="amount">Jumlah: Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h3>Petunjuk Pembayaran:</h3>
                <ol>
                    <li>Transfer sesuai jumlah yang tertera ke rekening/e-wallet yang dipilih</li>
                    <li>Simpan bukti transfer sebagai referensi</li>
                    <li>Upload bukti transfer pada formulir di bawah</li>
                    <li>Pembayaran akan diverifikasi dalam 1×24 jam kerja</li>
                    <li>Pesanan Anda akan diproses setelah pembayaran terverifikasi</li>
                </ol>
            </div>

            <form method="post" action="" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="payment_method" value="bank_transfer" id="selected-payment-method">
                <div class="form-group">
                    <label for="bukti_transfer">Bukti Transfer/Pembayaran</label>
                    <input type="file" id="bukti_transfer" name="bukti_transfer" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                    <small>Format: JPG, PNG (Maks. 2MB)</small>
                </div>
                <button type="submit" name="submit_payment" class="submit-btn">Kirim Konfirmasi</button>
            </form>
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
        <div class="contact-info">
            <i class="fas fa-phone"></i>
            <a href="tel:+628116632626">+62 811 6632 626</a>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Rumah Sulam Sefni. Hak Cipta Dilindungi
        </div>
    </footer>

    <script>
        // Tab functionality
        document.querySelectorAll('.method-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.method-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Update the hidden payment method field
                if (tabId === 'bank-transfer') {
                    document.getElementById('selected-payment-method').value = 'bank_transfer';
                } else {
                    // Get the selected e-wallet option
                    const selectedWallet = document.querySelector('input[name="payment_method"]:checked').value;
                    document.getElementById('selected-payment-method').value = selectedWallet;
                }
            });
        });

        // Update payment method when e-wallet option changes
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', () => {
                if (document.getElementById('e-wallet').classList.contains('active')) {
                    document.getElementById('selected-payment-method').value = radio.value;
                }
            });
        });
    </script>
</body>
</html>