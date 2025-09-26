<?php
session_start();
require_once '../koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: ../login.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];
$logged_in = true;

// Make sure the connection is working
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Check if the user exists in the database
$query = "SELECT * FROM pelanggan WHERE id_pelanggan = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pelanggan);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // User not found - clear session and redirect to login
    session_unset();
    session_destroy();
    header('Location: ../login.php?error=invalid_user');
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Rumah Sulam Sefni</title>
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

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
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
                <a href="../masuk.php" class="auth-btn login-btn">Masuk</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container profile-container">
        <div class="row">
            <div class="col-md-3">
                <div class="profile-sidebar">
                    <div class="text-center">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['nama_pelanggan']) ?>&background=6d4c41&color=fff" alt="Profil" class="profile-avatar">
                        <h5 class="mt-2"><?= htmlspecialchars($user['nama_pelanggan']) ?></h5>
                    </div>
                    <ul class="profile-menu mt-4">
                        <li><a href="profil.php" class="active"><i class="fas fa-user"></i> Profil Saya</a></li>
                        <li><a href="pesanan_saya.php"><i class="fas fa-shopping-bag"></i> Pesanan Saya</a></li>
                        <li><a href="alamat.php"><i class="fas fa-map-marker-alt"></i> Alamat Saya</a></li>
                        <li><a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="profile-content">
                    <div class="profile-header">
                        <h3 class="mb-0">Profil Saya</h3>
                        <p class="text-muted mb-0">Kelola informasi profil Anda</p>
                    </div>
                    <form action="update_profil.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($user['nama_pelanggan']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                       <div class="row mb-3">
    <div class="col-md-6">
        <label for="telepon" class="form-label">Nomor Telepon</label>
        <input type="tel" class="form-control" id="telepon" name="telepon" value="<?= htmlspecialchars($user['no_hp']) ?>">
    </div>
</div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
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