<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../koneksi.php';

function safeHtml($data) {
    return $data !== null ? htmlspecialchars($data) : '';
}

$username = $_SESSION['username'];
$query = mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username'");
$user = mysqli_fetch_assoc($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $no_telepon = mysqli_real_escape_string($koneksi, $_POST['no_telepon'] ?? '');
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
    
    $update_query = "UPDATE user SET 
                    nama_lengkap = '$nama', 
                    email = '$email', 
                    no_hp = '$no_telepon', 
                    alamat = '$alamat' 
                    WHERE username = '$username'";
    
    if (!empty($_POST['password_baru']) && $_POST['password_baru'] == $_POST['konfirmasi_password']) {
        $password = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
        $update_query = "UPDATE user SET 
                        nama_lengkap = '$nama', 
                        email = '$email', 
                        no_hp = '$no_telepon', 
                        alamat = '$alamat',
                        password = '$password' 
                        WHERE username = '$username'";
    }
    
    if (mysqli_query($koneksi, $update_query)) {
        $_SESSION['success_message'] = "Profil berhasil diupdate!";
        $query = mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username'");
        $user = mysqli_fetch_assoc($query);
    } else {
        $error_message = "Gagal mengupdate profil: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Rumah Sulam Sefni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            min-height: 100vh;
        }
        
        .profile-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .profile-icon {
            width: 50px;
            height: 50px;
            background-color: #5D4037;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .profile-title {
            font-size: 22px;
            color: #333;
        }
        
        .profile-description {
            color: #666;
            font-size: 14px;
        }
        
        .form-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #f8a100;
            outline: none;
        }
        
        .form-full-width {
            grid-column: span 2;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 12px 20px;
            background-color: #5D4037;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #6D4C41;
        }
        
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-full-width {
                grid-column: span 1;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Rumah Sulam Sefni</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="katalog_produk.php">Katalog Produk</a>
    <a href="kelola_pesanan.php">Kelola Pesanan</a>
    <a href="kelola_retur.php">Kelola Retur Produk</a>
    <a href="kelola_galeri.php">Kelola Galeri</a>
    <a href="kelola_user.php">Kelola User</a>
    <a href="profil.php" class="active">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-icon">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <h2 class="profile-title">Profil Pengguna</h2>
                <p class="profile-description">Kelola informasi akun Anda</p>
            </div>
        </div>
    </div>
    
    <div class="form-card">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($user): ?>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" value="<?php echo safeHtml($user['username']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?php echo safeHtml($user['nama_lengkap']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo safeHtml($user['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="no_telepon">Nomor Telepon</label>
                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="<?php echo safeHtml($user['no_hp']); ?>">
                </div>
                
                <div class="form-group form-full-width">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat"><?php echo safeHtml($user['alamat']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Kosongkan jika tidak ingin mengubah">
                </div>
                
                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Konfirmasi password baru">
                </div>
                
                <div class="form-group form-full-width">
                    <button type="submit" class="btn">Update Profil</button>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Tidak dapat mengambil data profil. Silakan coba lagi atau hubungi administrator.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
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