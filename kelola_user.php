<?php
// Tambahkan kode debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Pertama load koneksi database
require_once '../koneksi.php';

// SEMENTARA: Set role admin untuk debugging
$_SESSION['role'] = 'admin'; // Force role admin untuk debugging

// Pastikan id_user juga ada di session
if (!isset($_SESSION['id_user'])) {
    // Ambil id_user dari database berdasarkan username yang login
    $username = $_SESSION['username'];
    $query = mysqli_query($koneksi, "SELECT id_user FROM user WHERE username = '$username'");
    if ($query && mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_assoc($query);
        $_SESSION['id_user'] = $user_data['id_user'];
    }
}

// Fungsi untuk menangani error MySQL
function safeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        echo "<!-- Error MySQL: " . mysqli_error($conn) . " -->";
        return false;
    }
    return $result;
}

// Query untuk mendapatkan daftar user
$query_users = safeQuery($koneksi, "SELECT * FROM user ORDER BY id_user DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Rumah Sulam Sefni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            display: flex;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        /* Style Sidebar */
        .sidebar {
            height: 100vh;
            width: 220px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #795548;
            padding-top: 20px;
            overflow-y: auto;
        }
        
        .sidebar h3 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #6D4C41;
        }
        
        .sidebar a {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 16px;
            color: white;
            display: block;
            transition: 0.3s;
            margin: 5px 10px;
            border-radius: 4px;
        }
        
        .sidebar a:hover {
            background-color: #6D4C41;
        }
        
        .sidebar a.active {
            background-color: #5D4037;
            border-left: 4px solid #977E50;
        }
        
        /* Style Konten */
        .content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            min-height: 100vh;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8a100;
        }
        
        p {
            margin-bottom: 15px;
            color: #555;
        }
        
        /* Tabel */
        .table-container {
            margin: 30px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }
        
        th, td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
        }
        
        th {
            background-color: #111;
            color: white;
            font-weight: normal;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        /* Welcome Box */
        .welcome-box {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-box h2 {
            border-bottom: none;
            margin-bottom: 10px;
            padding-bottom: 0;
            color: #333;
        }
        
        /* Tombol */
        button, .btn {
            background-color: #5D4037;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-right: 5px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        button:hover, .btn:hover {
            background-color: #6D4C41;
        }
        
        .btn-primary {
            background-color: #f8a100;
        }
        
        .btn-primary:hover {
            background-color: #e69100;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        /* Form */
        .form-container {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        /* Alert */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-dismissible {
            position: relative;
            padding-right: 35px;
        }
        
        .alert-dismissible .close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: inherit;
            font-size: 20px;
        }
        
        /* Section Title */
        .section-title {
            margin: 30px 0 20px;
            color: #333;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .sidebar {
                width: 0;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h3>Rumah Sulam Sefni</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="katalog_produk.php">Katalog Produk</a>
    <a href="kelola_pesanan.php">Kelola Pesanan</a>
    <a href="kelola_retur.php">Kelola Retur Produk</a>
    <a href="kelola_galeri.php">Kelola Galeri</a>
    <a href="kelola_user.php" class="active">Kelola User</a>
    <a href="profil.php">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<!-- Konten Halaman -->
<div class="content">
    <div class="welcome-box">
        <h2>Kelola User</h2>
        <p>Selamat datang di manajemen user Rumah Sulam Sefni.</p>
    </div>

    <!-- Menampilkan Pesan Error/Success -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible">
            <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
            ?>
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>

    <!-- Form Tambah User -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
    <div class="form-container">
        <h3 class="section-title"><i class="fas fa-user-plus"></i>Tambah User Baru</h3>
        <form action="proses_tambah_user.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Role User</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="pelanggan" selected>pelanggan</option>
                     <option value="pemilik">pemilik</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Tambah User</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Tabel Daftar User -->
    <div class="table-container">
        <h3 class="section-title"><i class="fas fa-users"></i>Daftar User</h3>
        <?php if ($query_users && mysqli_num_rows($query_users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Tanggal Daftar</th>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($query_users)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id_user']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <td>
                        <a href="edit_user.php?id=<?php echo $user['id_user']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                        
                        <?php if (isset($_SESSION['id_user']) && $user['id_user'] != $_SESSION['id_user']): ?>
                        <a href="hapus_user.php?id=<?php echo $user['id_user']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')"><i class="fas fa-trash-alt"></i> Hapus</a>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Tidak ada data user yang ditemukan.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto close alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
</body>
</html>