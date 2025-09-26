<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header('Location: login.php');
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];
$logged_in = true;

// Ambil data user pelanggan (email untuk update user.alamat)
$query = $koneksi->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$query->bind_param("i", $id_pelanggan);
$query->execute();
$user = $query->get_result()->fetch_assoc();

// === HANDLE SEMUA AKSI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    $nama_penerima  = $_POST['nama_penerima'] ?? '';
    $no_hp          = $_POST['no_hp'] ?? '';
    $provinsi       = $_POST['provinsi'] ?? '';
    $kota           = $_POST['kota'] ?? '';
    $kecamatan      = $_POST['kecamatan'] ?? '';
    $kode_pos       = $_POST['kode_pos'] ?? '';
    $alamat_lengkap = $_POST['alamat_lengkap'] ?? '';
    $is_utama       = isset($_POST['is_utama']) ? 1 : 0;

    if ($is_utama == 1) {
        $koneksi->query("UPDATE alamat_pelanggan SET is_utama = 0 WHERE id_pelanggan = $id_pelanggan");
    }

    if ($aksi === 'tambah') {
        $stmt = $koneksi->prepare("INSERT INTO alamat_pelanggan (id_pelanggan, nama_penerima, no_hp, provinsi, kota, kecamatan, kode_pos, alamat_lengkap, is_utama) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssi", $id_pelanggan, $nama_penerima, $no_hp, $provinsi, $kota, $kecamatan, $kode_pos, $alamat_lengkap, $is_utama);
        if (!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }

        if ($is_utama == 1) {
            $alamat_utama = $alamat_lengkap . ', ' . $kecamatan . ', ' . $kota . ', ' . $provinsi . ', ' . $kode_pos;

            // Update tabel pelanggan
            $stmt2 = $koneksi->prepare("UPDATE pelanggan SET alamat = ? WHERE id_pelanggan = ?");
            $stmt2->bind_param("si", $alamat_utama, $id_pelanggan);
            $stmt2->execute();

            // Update tabel user
            $stmt3 = $koneksi->prepare("UPDATE user SET alamat = ? WHERE email = ? AND role = 'pelanggan'");
            $stmt3->bind_param("ss", $alamat_utama, $user['email']);
            $stmt3->execute();
        }

    } elseif ($aksi === 'edit') {
        $id_alamat = $_POST['id_alamat'];
        $stmt = $koneksi->prepare("UPDATE alamat_pelanggan SET nama_penerima=?, no_hp=?, provinsi=?, kota=?, kecamatan=?, kode_pos=?, alamat_lengkap=?, is_utama=? WHERE id_alamat=? AND id_pelanggan=?");
        $stmt->bind_param("sssssssiii", $nama_penerima, $no_hp, $provinsi, $kota, $kecamatan, $kode_pos, $alamat_lengkap, $is_utama, $id_alamat, $id_pelanggan);
        if (!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }

        if ($is_utama == 1) {
            $alamat_utama = $alamat_lengkap . ', ' . $kecamatan . ', ' . $kota . ', ' . $provinsi . ', ' . $kode_pos;

            // Update tabel pelanggan
            $stmt2 = $koneksi->prepare("UPDATE pelanggan SET alamat = ? WHERE id_pelanggan = ?");
            $stmt2->bind_param("si", $alamat_utama, $id_pelanggan);
            $stmt2->execute();

            // Update tabel user
            $stmt3 = $koneksi->prepare("UPDATE user SET alamat = ? WHERE email = ? AND role = 'pelanggan'");
            $stmt3->bind_param("ss", $alamat_utama, $user['email']);
            $stmt3->execute();
        }
    }

    header("Location: alamat.php?status=success");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aksi'])) {
    $id_alamat = $_GET['id'] ?? 0;
    if ($_GET['aksi'] === 'hapus') {
        $stmt = $koneksi->prepare("DELETE FROM alamat_pelanggan WHERE id_alamat = ? AND id_pelanggan = ?");
        $stmt->bind_param("ii", $id_alamat, $id_pelanggan);
        $stmt->execute();
    } elseif ($_GET['aksi'] === 'utama') {
        $koneksi->query("UPDATE alamat_pelanggan SET is_utama = 0 WHERE id_pelanggan = $id_pelanggan");
        $stmt = $koneksi->prepare("UPDATE alamat_pelanggan SET is_utama = 1 WHERE id_alamat = ? AND id_pelanggan = ?");
        $stmt->bind_param("ii", $id_alamat, $id_pelanggan);
        $stmt->execute();

        // Ambil data alamat untuk update ke pelanggan & user
        $stmt2 = $koneksi->prepare("SELECT alamat_lengkap, kecamatan, kota, provinsi, kode_pos FROM alamat_pelanggan WHERE id_alamat = ? AND id_pelanggan = ?");
        $stmt2->bind_param("ii", $id_alamat, $id_pelanggan);
        $stmt2->execute();
        $result = $stmt2->get_result();
        if ($row = $result->fetch_assoc()) {
            $alamat_utama = $row['alamat_lengkap'] . ', ' . $row['kecamatan'] . ', ' . $row['kota'] . ', ' . $row['provinsi'] . ', ' . $row['kode_pos'];

            // Update tabel pelanggan
            $stmt3 = $koneksi->prepare("UPDATE pelanggan SET alamat = ? WHERE id_pelanggan = ?");
            $stmt3->bind_param("si", $alamat_utama, $id_pelanggan);
            $stmt3->execute();

            // Update tabel user
            $stmt4 = $koneksi->prepare("UPDATE user SET alamat = ? WHERE email = ? AND role = 'pelanggan'");
            $stmt4->bind_param("ss", $alamat_utama, $user['email']);
            $stmt4->execute();
        }
    }

    header("Location: alamat.php");
    exit();
}

// Ambil semua alamat pelanggan
$query_alamat = $koneksi->prepare("SELECT * FROM alamat_pelanggan WHERE id_pelanggan = ? ORDER BY is_utama DESC, id_alamat DESC");
$query_alamat->bind_param("i", $id_pelanggan);
$query_alamat->execute();
$alamat_list = $query_alamat->get_result()->fetch_all(MYSQLI_ASSOC);
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alamat Saya - Rumah Sulam Sefni</title>
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

        /* Address cards styling */
        .address-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .address-card.primary {
            border-color: var(--primary-color);
            background-color: rgba(109, 76, 65, 0.05);
        }

        .primary-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .address-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-outline-danger {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .add-address-card {
            border: 1px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-address-card:hover {
            background-color: rgba(109, 76, 65, 0.05);
            border-color: var(--primary-color);
        }

        .add-address-card i {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close-modal:hover {
            color: var(--dark-color);
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h4 {
            margin: 0;
            color: var(--dark-color);
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
            
            .social-contact {
                flex-direction: column;
                gap: 10px;
            }
            
            .contact-number {
                border-left: none;
                padding-left: 0;
                margin-left: 0;
                margin-top: 10px;
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
                        <li><a href="profil.php"><i class="fas fa-user"></i> Profil Saya</a></li>
                        <li><a href="pesanan_saya.php"><i class="fas fa-shopping-bag"></i> Pesanan Saya</a></li>
                        <li><a href="alamat.php" class="active"><i class="fas fa-map-marker-alt"></i> Alamat Saya</a></li>
                        <li><a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="profile-content">
                    <?php if(isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Alamat berhasil disimpan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-header">
                        <h3 class="mb-0">Alamat Saya</h3>
                        <p class="text-muted mb-0">Kelola alamat pengiriman Anda</p>
                    </div>
                    
                    <div class="row">
                        <?php if(count($alamat_list) > 0): ?>
                            <?php foreach($alamat_list as $alamat): ?>
                                <div class="col-md-6">
                                    <div class="address-card <?= $alamat['is_utama'] ? 'primary' : '' ?>">
                                        <?php if($alamat['is_utama']): ?>
                                            <span class="primary-badge">Utama</span>
                                        <?php endif; ?>
                                        <h5><?= htmlspecialchars($alamat['nama_penerima']) ?></h5>
                                        <p class="mb-1"><?= htmlspecialchars($alamat['no_hp']) ?></p>
                                        <p class="mb-1"><?= htmlspecialchars($alamat['alamat_lengkap']) ?></p>
                                        <p class="mb-1"><?= htmlspecialchars($alamat['kecamatan']) ?>, <?= htmlspecialchars($alamat['kota']) ?></p>
                                        <p class="mb-1"><?= htmlspecialchars($alamat['provinsi']) ?>, <?= htmlspecialchars($alamat['kode_pos']) ?></p>
                                        <div class="address-actions">
                                            <?php if(!$alamat['is_utama']): ?>
                                                <a href="alamat.php?aksi=utama&id=<?= $alamat['id_alamat'] ?>" class="btn btn-sm btn-outline-primary">Jadikan Utama</a>
                                            <?php endif; ?>
                                            <a href="#" class="btn btn-sm btn-outline-primary edit-address" data-id="<?= $alamat['id_alamat'] ?>">Edit</a>
                                            <a href="alamat.php?aksi=hapus&id=<?= $alamat['id_alamat'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus alamat ini?')">Hapus</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Add New Address Card -->
                        <div class="col-md-6">
                            <div class="add-address-card" id="btnTambahAlamat">
                                <i class="fas fa-plus-circle"></i>
                                <h5>Tambah Alamat Baru</h5>
                                <p class="text-muted">Klik untuk menambahkan alamat pengiriman baru</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div id="tambahAlamatModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h4>Tambah Alamat</h4>
            </div>
            <form id="formTambahAlamat" action="alamat.php" method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="mb-3">
                    <label for="nama_penerima" class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control" id="nama_penerima" name="nama_penerima" required>
                </div>
                <div class="mb-3">
                    <label for="no_hp" class="form-label">Nomor Telepon</label>
                    <input type="text" class="form-control" id="no_hp" name="no_hp" required>
                </div>
                <div class="mb-3">
                    <label for="provinsi" class="form-label">Provinsi</label>
                    <input type="text" class="form-control" id="provinsi" name="provinsi" required>
                </div>
                <div class="mb-3">
                    <label for="kota" class="form-label">Kota/Kabupaten</label>
                    <input type="text" class="form-control" id="kota" name="kota" required>
                </div>
                <div class="mb-3">
                    <label for="kecamatan" class="form-label">Kecamatan</label>
                    <input type="text" class="form-control" id="kecamatan" name="kecamatan" required>
                </div>
                <div class="mb-3">
                    <label for="kode_pos" class="form-label">Kode Pos</label>
                    <input type="text" class="form-control" id="kode_pos" name="kode_pos" required>
                </div>
                <div class="mb-3">
                    <label for="alamat_lengkap" class="form-label">Alamat Lengkap (Nama Jalan, Gedung, No. Rumah)</label>
                    <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="3" required></textarea>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_utama" name="is_utama" value="1">
                    <label class="form-check-label" for="is_utama">Jadikan sebagai alamat utama</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Simpan Alamat</button>
            </form>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div id="editAlamatModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h4>Edit Alamat</h4>
            </div>
            <form id="formEditAlamat" action="alamat.php" method="POST">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" id="edit_id_alamat" name="id_alamat">
                <div class="mb-3">
                    <label for="edit_nama_penerima" class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control" id="edit_nama_penerima" name="nama_penerima" required>
                </div>
                <div class="mb-3">
                    <label for="edit_no_hp" class="form-label">Nomor Telepon</label>
                    <input type="text" class="form-control" id="edit_no_hp" name="no_hp" required>
                </div>
                <div class="mb-3">
                    <label for="edit_provinsi" class="form-label">Provinsi</label>
                    <input type="text" class="form-control" id="edit_provinsi" name="provinsi" required>
                </div>
                <div class="mb-3">
                    <label for="edit_kota" class="form-label">Kota/Kabupaten</label>
                    <input type="text" class="form-control" id="edit_kota" name="kota" required>
                </div>
                <div class="mb-3">
                    <label for="edit_kecamatan" class="form-label">Kecamatan</label>
                    <input type="text" class="form-control" id="edit_kecamatan" name="kecamatan" required>
                </div>
                <div class="mb-3">
                    <label for="edit_kode_pos" class="form-label">Kode Pos</label>
                    <input type="text" class="form-control" id="edit_kode_pos" name="kode_pos" required>
                </div>
                <div class="mb-3">
                    <label for="edit_alamat_lengkap" class="form-label">Alamat Lengkap (Nama Jalan, Gedung, No. Rumah)</label>
                    <textarea class="form-control" id="edit_alamat_lengkap" name="alamat_lengkap" rows="3" required></textarea>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="edit_is_utama" name="is_utama" value="1">
                    <label class="form-check-label" for="edit_is_utama">Jadikan sebagai alamat utama</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
            </form>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get modal elements
            const tambahAlamatModal = document.getElementById('tambahAlamatModal');
            const editAlamatModal = document.getElementById('editAlamatModal');
            const btnTambahAlamat = document.getElementById('btnTambahAlamat');
            const closeButtons = document.querySelectorAll('.close-modal');
            
            // Open Add Address Modal
            btnTambahAlamat.addEventListener('click', function() {
                tambahAlamatModal.style.display = 'block';
            });
            
            // Close modals when clicking X
            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    tambahAlamatModal.style.display = 'none';
                    editAlamatModal.style.display = 'none';
                });
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === tambahAlamatModal) {
                    tambahAlamatModal.style.display = 'none';
                }
                if (event.target === editAlamatModal) {
                    editAlamatModal.style.display = 'none';
                }
            });
            
            // Edit Address functionality
            const editButtons = document.querySelectorAll('.edit-address');
            editButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const addressId = this.getAttribute('data-id');
                    
                    // Here you would typically fetch address data via AJAX
                    // For demo purposes, we're just showing the modal
                    document.getElementById('edit_id_alamat').value = addressId;
                    
                    // In a real implementation, you would populate the form fields with the address data
                    // fetchAddressData(addressId).then(data => {
                    //     document.getElementById('edit_nama_penerima').value = data.nama_penerima;
                    //     // ... and so on for other fields
                    // });
                    
                    editAlamatModal.style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>