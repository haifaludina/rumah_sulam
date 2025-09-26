<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: login.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];
$logged_in = true;

// Ensure the session contains the user's name
if (!isset($_SESSION['nama_pelanggan'])) {
    // Fetch user data if nama is not in session
    $query_user = "SELECT nama_pelanggan FROM pelanggan WHERE id_pelanggan = ?";
    $stmt_user = $koneksi->prepare($query_user);
    $stmt_user->bind_param("i", $id_pelanggan);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user_data = $result_user->fetch_assoc()) {
        $_SESSION['nama_pelanggan'] = $user_data['nama_pelanggan'];
    } else {
        $_SESSION['nama_pelanggan'] = "Pelanggan"; // Default name if not found
    }
}

$query = "SELECT * FROM pesanan WHERE id_pelanggan = ? ORDER BY tanggal_pesan DESC";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pelanggan);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Rumah Sulam Sefni</title>
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

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 15px 50px;
            background-color: var(--white-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            margin: 0;
            padding: 0;
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

        nav ul li a:hover,
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

        .profile-container {
            margin-top: 30px;
            margin-bottom: 50px;
        }

        .profile-sidebar {
            background: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--light-color);
            margin-bottom: 15px;
        }

        .profile-menu {
            list-style: none;
            padding: 0;
        }

        .profile-menu li {
            margin-bottom: 10px;
        }

        .profile-menu a {
            display: block;
            padding: 10px 15px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .profile-menu a:hover,
        .profile-menu a.active {
            background-color: rgba(109, 76, 65, 0.1);
            color: var(--primary-color);
        }

        .profile-menu i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }

        .profile-content {
            background: var(--white-color);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .profile-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Status Badge Colors */
        .status-pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .status-diproses {
            background-color: #17a2b8 !important;
            color: #fff !important;
        }

        .status-diterima {
    background-color: #20c997 !important;
    color: #fff !important;
}

        .status-selesai {
            background-color: #28a745 !important;
            color: #fff !important;
        }

        .status-dibatalkan {
            background-color: #dc3545 !important;
            color: #fff !important;
        }

        .status-menunggu {
            background-color: #6c757d !important;
            color: #fff !important;
        }

        .status-dikirim {
            background-color: #fd7e14 !important;
            color: #fff !important;
        }

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
            header {
                padding: 15px 20px;
                flex-direction: column;
            }

            nav ul {
                margin: 15px 0;
                flex-wrap: wrap;
                justify-content: center;
            }

            .profile-container {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
    <div class="d-flex align-items-center">
        <a href="index.php" class="logo-container">
            <img src="../uploads/logo-sefni.png" alt="Logo" class="logo">
            <span class="logo-text">Rumah Sulam Sefni</span>
        </a>
    </div>
    <div class="d-flex align-items-center">
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="katalog_produk.php">Katalog Produk</a></li>
                <li><a href="kustomisasi.php">Kustomisasi Sulaman</a></li>
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="tentang_kami.php">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
        </nav>
        <?php if($logged_in): ?>
        <div class="user-icons">
            <a href="keranjang.php"><i class="fas fa-shopping-cart"></i></a>
            <a href="profil.php"><i class="fas fa-user"></i></a>
        </div>
        <?php else: ?>
        <div class="auth-buttons">
            <a href="../daftar.php" class="auth-btn signup-btn">Daftar</a>
            <a href="../masuk.php" class="auth-btn login-btn">Masuk</a>
        </div>
        <?php endif; ?>
    </div>
</header>

    <div class="container profile-container">
        <div class="row">
            <div class="col-md-3">
                <div class="profile-sidebar">
                    <div class="text-center">
                        <?php if(isset($_SESSION['nama_pelanggan'])): ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama_pelanggan']) ?>&background=6d4c41&color=fff" alt="Profil" class="profile-avatar">
                            <h5 class="mt-2"><?= htmlspecialchars($_SESSION['nama_pelanggan']) ?></h5>
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=User&background=6d4c41&color=fff" alt="Profil" class="profile-avatar">
                            <h5 class="mt-2">Pelanggan</h5>
                        <?php endif; ?>
                    </div>
                    <ul class="profile-menu mt-4">
                        <li><a href="profil.php"><i class="fas fa-user"></i> Profil Saya</a></li>
                        <li><a href="pesanan_saya.php" class="active"><i class="fas fa-shopping-bag"></i> Pesanan Saya</a></li>
                        <li><a href="alamat.php"><i class="fas fa-map-marker-alt"></i> Alamat Saya</a></li>
                        <li><a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="profile-content">
                    <div class="profile-header">
                        <h3 class="mb-0">Pesanan Saya</h3>
                        <p class="text-muted mb-0">Riwayat pesanan Anda</p>
                    </div>
                    
                    <?php if (count($pesanan) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Tanggal</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pesanan as $p): ?>
                                        <tr>
                                            <td>#<?= $p['id_pesanan'] ?></td>
                                            <td>
                                                <?php 
                                                // Check if tanggal_pesan exists, otherwise fall back to tanggal_pesanan
                                                if(isset($p['tanggal_pesan'])) {
                                                    echo date('d M Y', strtotime($p['tanggal_pesan']));
                                                } elseif(isset($p['tanggal_pesanan'])) {
                                                    echo date('d M Y', strtotime($p['tanggal_pesanan']));
                                                } else {
                                                    echo 'Tanggal tidak tersedia';
                                                }
                                                ?>
                                            </td>
                                            <td>Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></td>
                                            <td>
                                                <?php
                                                $status = strtolower($p['status']);
                                                $status_class = 'status-' . $status;
                                                $status_text = ucfirst($p['status']);
                                                
                                                // Map different status variations
                                                // Around line 300, update the status mapping:
switch($status) {
    case 'pending':
    case 'menunggu':
    case 'menunggu pembayaran':
        $status_class = 'status-pending';
        $status_text = 'Menunggu';
        break;
    case 'diproses':
    case 'proses':
        $status_class = 'status-diproses';
        $status_text = 'Diproses';
        break;
    case 'dikirim':
    case 'shipped':
    case 'pengiriman':
        $status_class = 'status-dikirim';
        $status_text = 'Dikirim';
        break;
    case 'diterima':
    case 'received':
        $status_class = 'status-diterima';
        $status_text = 'Diterima';
        break;
    case 'selesai':
    case 'completed':
    case 'done':
        $status_class = 'status-selesai';
        $status_text = 'Selesai';
        break;
    case 'dibatalkan':
    case 'cancelled':
    case 'batal':
        $status_class = 'status-dibatalkan';
        $status_text = 'Dibatalkan';
        break;
    default:
        $status_class = 'status-menunggu';
        break;
}

                                                ?>
                                                <span class="badge <?= $status_class ?>">
                                                    <?= $status_text ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="detail_pesanan.php?id=<?= $p['id_pesanan'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-4x text-muted mb-4"></i>
                            <h5>Anda belum memiliki pesanan</h5>
                            <p class="text-muted">Mulai berbelanja dan temukan produk menarik di toko kami</p>
                            <a href="katalog_produk.php" class="btn btn-primary mt-3">Belanja Sekarang</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="social-contact">
            <span class="find-us">Temukan Kami:</span>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
            <div class="contact-number">
                <i class="fas fa-phone-alt"></i>
                <span>+62 123 4567 890</span>
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date('Y') ?> Rumah Sulam Sefni. All Rights Reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>