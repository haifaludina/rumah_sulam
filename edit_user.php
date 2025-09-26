<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = "Anda harus login sebagai admin untuk mengakses halaman ini";
    header("Location: login.php");
    exit();
}

require_once '../koneksi.php';

$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_user <= 0) {
    $_SESSION['error_message'] = "ID User tidak valid";
    header("Location: kelola_user.php");
    exit();
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM user WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $_SESSION['error_message'] = "User tidak ditemukan";
    header("Location: kelola_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    if (!in_array($role, ['admin', 'pelanggan', 'pemilik'])) {
        $error_message = "Role tidak valid";
    } else {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Format email tidak valid";
        } else {
            $check_username = mysqli_prepare($koneksi, "SELECT id_user FROM user WHERE username = ? AND id_user != ?");
            mysqli_stmt_bind_param($check_username, "si", $username, $id_user);
            mysqli_stmt_execute($check_username);
            mysqli_stmt_store_result($check_username);
            
            if (mysqli_stmt_num_rows($check_username) > 0) {
                $error_message = "Username sudah digunakan oleh user lain";
            } else {
                $check_email = mysqli_prepare($koneksi, "SELECT id_user FROM user WHERE email = ? AND id_user != ?");
                mysqli_stmt_bind_param($check_email, "si", $email, $id_user);
                mysqli_stmt_execute($check_email);
                mysqli_stmt_store_result($check_email);
                
                if (mysqli_stmt_num_rows($check_email) > 0) {
                    $error_message = "Email sudah digunakan oleh user lain";
                } else {
                    mysqli_begin_transaction($koneksi);
                    
                    try {
                        if (!empty($_POST['password']) && trim($_POST['password']) != '') {
                            $password = trim($_POST['password']);
                            
                            if (strlen($password) < 8) {
                                throw new Exception("Password minimal 8 karakter");
                            }
                            
                            $update_sql = "UPDATE user SET username = ?, nama_lengkap = ?, email = ?, role = ?, password = ? WHERE id_user = ?";
                            $stmt = mysqli_prepare($koneksi, $update_sql);
                            mysqli_stmt_bind_param($stmt, "sssssi", $username, $nama_lengkap, $email, $role, $password, $id_user);
                        } else {
                            $update_sql = "UPDATE user SET username = ?, nama_lengkap = ?, email = ?, role = ? WHERE id_user = ?";
                            $stmt = mysqli_prepare($koneksi, $update_sql);
                            mysqli_stmt_bind_param($stmt, "ssssi", $username, $nama_lengkap, $email, $role, $id_user);
                        }
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Gagal memperbarui user: " . mysqli_error($koneksi));
                        }
                        
                        if ($role == 'admin') {
                            if ($user['id_admin']) {
                                if (!empty($password)) {
                                    $update_admin = "UPDATE admin SET username = ?, nama_lengkap = ?, email = ?, password = ? WHERE id_admin = ?";
                                    $stmt_admin = mysqli_prepare($koneksi, $update_admin);
                                    mysqli_stmt_bind_param($stmt_admin, "ssssi", $username, $nama_lengkap, $email, $password, $user['id_admin']);
                                } else {
                                    $update_admin = "UPDATE admin SET username = ?, nama_lengkap = ?, email = ? WHERE id_admin = ?";
                                    $stmt_admin = mysqli_prepare($koneksi, $update_admin);
                                    mysqli_stmt_bind_param($stmt_admin, "sssi", $username, $nama_lengkap, $email, $user['id_admin']);
                                }
                                
                                if (!mysqli_stmt_execute($stmt_admin)) {
                                    throw new Exception("Gagal memperbarui data admin: " . mysqli_error($koneksi));
                                }
                            } else {
                                $insert_admin = "INSERT INTO admin (username, password, nama_lengkap, email) VALUES (?, ?, ?, ?)";
                                $stmt_admin = mysqli_prepare($koneksi, $insert_admin);
                                $admin_password = !empty($password) ? $password : $user['password'];
                                mysqli_stmt_bind_param($stmt_admin, "ssss", $username, $admin_password, $nama_lengkap, $email);
                                
                                if (!mysqli_stmt_execute($stmt_admin)) {
                                    throw new Exception("Gagal menambahkan data admin: " . mysqli_error($koneksi));
                                }
                                
                                $id_admin = mysqli_insert_id($koneksi);
                                $update_user = "UPDATE user SET id_admin = ?, id_pelanggan = NULL WHERE id_user = ?";
                                $stmt_update = mysqli_prepare($koneksi, $update_user);
                                mysqli_stmt_bind_param($stmt_update, "ii", $id_admin, $id_user);
                                
                                if (!mysqli_stmt_execute($stmt_update)) {
                                    throw new Exception("Gagal mengupdate user: " . mysqli_error($koneksi));
                                }
                            }
                        } 
                        elseif ($role == 'pelanggan') {
                            if ($user['id_pelanggan']) {
                                if (!empty($password)) {
                                    $update_pelanggan = "UPDATE pelanggan SET username = ?, nama_pelanggan = ?, email = ?, password = ? WHERE id_pelanggan = ?";
                                    $stmt_pelanggan = mysqli_prepare($koneksi, $update_pelanggan);
                                    mysqli_stmt_bind_param($stmt_pelanggan, "ssssi", $username, $nama_lengkap, $email, $password, $user['id_pelanggan']);
                                } else {
                                    $update_pelanggan = "UPDATE pelanggan SET username = ?, nama_pelanggan = ?, email = ? WHERE id_pelanggan = ?";
                                    $stmt_pelanggan = mysqli_prepare($koneksi, $update_pelanggan);
                                    mysqli_stmt_bind_param($stmt_pelanggan, "sssi", $username, $nama_lengkap, $email, $user['id_pelanggan']);
                                }
                                
                                if (!mysqli_stmt_execute($stmt_pelanggan)) {
                                    throw new Exception("Gagal memperbarui data pelanggan: " . mysqli_error($koneksi));
                                }
                            } else {
                                $insert_pelanggan = "INSERT INTO pelanggan (username, nama_pelanggan, email, password) VALUES (?, ?, ?, ?)";
                                $stmt_pelanggan = mysqli_prepare($koneksi, $insert_pelanggan);
                                $pelanggan_password = !empty($password) ? $password : $user['password'];
                                mysqli_stmt_bind_param($stmt_pelanggan, "ssss", $username, $nama_lengkap, $email, $pelanggan_password);
                                
                                if (!mysqli_stmt_execute($stmt_pelanggan)) {
                                    throw new Exception("Gagal menambahkan data pelanggan: " . mysqli_error($koneksi));
                                }
                                
                                $id_pelanggan = mysqli_insert_id($koneksi);
                                $update_user = "UPDATE user SET id_pelanggan = ?, id_admin = NULL WHERE id_user = ?";
                                $stmt_update = mysqli_prepare($koneksi, $update_user);
                                mysqli_stmt_bind_param($stmt_update, "ii", $id_pelanggan, $id_user);
                                
                                if (!mysqli_stmt_execute($stmt_update)) {
                                    throw new Exception("Gagal mengupdate user: " . mysqli_error($koneksi));
                                }
                            }
                        }
                        elseif ($role == 'pemilik') {
                            $update_user = "UPDATE user SET id_admin = NULL, id_pelanggan = NULL WHERE id_user = ?";
                            $stmt_update = mysqli_prepare($koneksi, $update_user);
                            mysqli_stmt_bind_param($stmt_update, "i", $id_user);
                            
                            if (!mysqli_stmt_execute($stmt_update)) {
                                throw new Exception("Gagal mengupdate role pemilik: " . mysqli_error($koneksi));
                            }
                        }
                        
                        mysqli_commit($koneksi);
                        $_SESSION['success_message'] = "User berhasil diperbarui";
                        header("Location: kelola_user.php");
                        exit();
                        
                    } catch (Exception $e) {
                        mysqli_rollback($koneksi);
                        $error_message = $e->getMessage();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Rumah Sulam Sefni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #795548;
            padding-top: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-brand {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            list-style: none;
            padding-left: 0;
        }
        
        .sidebar-nav li a {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 16px;
            color: white;
            display: block;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-nav li a:hover {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid #f8a100;
        }
        
        .sidebar-nav li a.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid #f8a100;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #f8a100;
            box-shadow: 0 0 0 0.2rem rgba(248, 161, 0, 0.25);
        }
        
        .btn-primary {
            background-color: #f8a100;
            border-color: #f8a100;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #e69100;
            border-color: #e69100;
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .form-text {
            color: #6c757d;
            font-size: 14px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand">
        <h4>Rumah Sulam Sefni</h4>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="katalog_produk.php">Katalog Produk</a></li>
        <li><a href="kelola_pesanan.php">Kelola Pesanan</a></li>
        <li><a href="kelola_pengiriman.php">Kelola Pengiriman</a></li>
        <li><a href="kelola_retur.php">Kelola Retur Produk</a></li>
        <li><a href="kelola_galeri.php">Kelola Galeri</a></li>
        <li><a href="kelola_user.php" class="active">Kelola User</a></li>
        <li><a href="profil.php">Profil</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="container-fluid">
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                    echo htmlspecialchars($_SESSION['error_message']); 
                    unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form action="" method="POST" id="editUserForm">
                <div class="mb-4">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password Baru</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Kosongkan jika tidak ingin mengubah password">
                    <div class="form-text">
                        Minimal 8 karakter. Kosongkan field ini jika tidak ingin mengubah password.
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                           value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="mb-5">
                    <label for="role" class="form-label">Role User</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="pelanggan" <?php echo ($user['role'] == 'pelanggan') ? 'selected' : ''; ?>>Pelanggan</option>
                        <option value="pemilik" <?php echo ($user['role'] == 'pemilik') ? 'selected' : ''; ?>>Pemilik</option>
                    </select>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <a href="kelola_user.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editUserForm');
        let formChanged = false;
        
        form.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        const passwordField = document.getElementById('password');
        passwordField.addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 8) {
                this.setCustomValidity('Password minimal 8 karakter');
            } else {
                this.setCustomValidity('');
            }
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            }
        });
        
        form.addEventListener('submit', () => {
            formChanged = false;
        });
    });
</script>
</body>
</html>