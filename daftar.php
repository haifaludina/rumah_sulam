<?php
session_start();
require_once 'koneksi.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $role = 'pelanggan';
    
    if ($password !== $konfirmasi_password) {
        $error = "Konfirmasi kata sandi tidak cocok!";
    } else {
        // Check if username already exists - separate queries
        $check_username_pelanggan = "SELECT username FROM pelanggan WHERE username = '$username'";
        $check_username_user = "SELECT username FROM user WHERE username = '$username'";
        
        $result_username_pelanggan = mysqli_query($koneksi, $check_username_pelanggan);
        $result_username_user = mysqli_query($koneksi, $check_username_user);
        
        // Check if email already exists - separate queries
        $check_email_pelanggan = "SELECT email FROM pelanggan WHERE email = '$email'";
        $check_email_user = "SELECT email FROM user WHERE email = '$email'";
        
        $result_email_pelanggan = mysqli_query($koneksi, $check_email_pelanggan);
        $result_email_user = mysqli_query($koneksi, $check_email_user);
        
        if (!$result_username_pelanggan || !$result_username_user || !$result_email_pelanggan || !$result_email_user) {
            $error = "Error checking data: " . mysqli_error($koneksi);
        } else if (mysqli_num_rows($result_username_pelanggan) > 0 || mysqli_num_rows($result_username_user) > 0) {
            $error = "Username sudah digunakan!";
        } else if (mysqli_num_rows($result_email_pelanggan) > 0 || mysqli_num_rows($result_email_user) > 0) {
            $error = "Email sudah digunakan!";
        } else {
            // Debug: Show what data we're trying to insert
            echo "<!-- Debug Info: 
            Nama: $nama_lengkap
            Username: $username  
            Email: $email
            Telepon: $telepon
            -->";
            
            // Begin transaction
            mysqli_begin_transaction($koneksi);
            
            try {
                // Debug: Check table structure
                error_log("Attempting to insert into pelanggan table");
                
                // 1. Insert into pelanggan table - Try different possible column names
                $query_pelanggan = "INSERT INTO pelanggan (nama_pelanggan, username, email, password, no_hp) 
                                   VALUES ('$nama_lengkap', '$username', '$email', '$password', '$telepon')";
                
                error_log("Query pelanggan: " . $query_pelanggan);
                $result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
                
                if (!$result_pelanggan) {
                    error_log("Error inserting into pelanggan: " . mysqli_error($koneksi));
                    
                    // Try alternative column names if the first attempt fails
                    $query_pelanggan_alt = "INSERT INTO pelanggan (nama, username, email, password, telepon) 
                                           VALUES ('$nama_lengkap', '$username', '$email', '$password', '$telepon')";
                    $result_pelanggan = mysqli_query($koneksi, $query_pelanggan_alt);
                    
                    if (!$result_pelanggan) {
                        throw new Exception("Gagal insert ke tabel pelanggan: " . mysqli_error($koneksi));
                    }
                }
                
                error_log("Successfully inserted into pelanggan table");
                
                // Get the newly inserted pelanggan ID
                $pelanggan_id = mysqli_insert_id($koneksi);
                
                // 2. Insert into user table
                $query_user = "INSERT INTO user (nama_lengkap, username, email, password, role, no_hp, status) 
                              VALUES ('$nama_lengkap', '$username', '$email', '$password', '$role', '$telepon', 1)";
                $result_user = mysqli_query($koneksi, $query_user);
                
                if (!$result_user) {
                    throw new Exception("Gagal insert ke tabel user: " . mysqli_error($koneksi));
                }
                
                // Get the newly inserted user ID
                $user_id = mysqli_insert_id($koneksi);
                
                // 3. Update user table with pelanggan_id
                $query_update = "UPDATE user SET id_pelanggan = $pelanggan_id WHERE id_user = $user_id";
                $result_update = mysqli_query($koneksi, $query_update);
                
                if (!$result_update) {
                    throw new Exception("Gagal mengupdate user: " . mysqli_error($koneksi));
                }
                
                // If all operations successful, commit transaction
                mysqli_commit($koneksi);
                
                // Set session
                $_SESSION['id_pelanggan'] = $pelanggan_id;
                $_SESSION['username'] = $username;
                $_SESSION['nama_pelanggan'] = $nama_lengkap;
                $_SESSION['email'] = $email;
                $_SESSION['no_hp'] = $telepon;
                $_SESSION['role'] = $role;
                $_SESSION['login_time'] = time();
                
                // Redirect ke halaman pelanggan
                header("Location: http://localhost/rumah_sulam/pelanggan/index.php");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction if any query fails
                mysqli_rollback($koneksi);
                $error = "Terjadi kesalahan saat mendaftar: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - Rumah Sulam Sefni</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
        }
        .logo-container {
            text-align: center;
            padding: 30px 20px 20px;
            background-color: #fff;
        }
        .logo-container img {
            max-width: 150px;
            height: auto;
        }
        .login-header {
            text-align: center;
            padding: 0 20px;
            margin-bottom: 25px;
        }
        .login-body {
            padding: 0 30px 30px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-control {
            height: 45px;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            width: 100%;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #212529;
            box-shadow: none;
        }
        .btn-login {
            background-color: #212529;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background-color: #343a40;
            transform: translateY(-2px);
        }
        .register-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .register-text a {
            color: #212529;
            font-weight: 500;
            text-decoration: none;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
        }
        .back-link {
            position: fixed;
            top: 20px;
            left: 20px;
            color: #000;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            z-index: 1000;
        }
        .back-link:hover {
            color: #343a40;
        }
    </style>
</head>
<body>
    <a href="http://localhost/rumah_sulam/pelanggan/index.php" class="back-link">
        &lt; Kembali ke Beranda
    </a>
    
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-container">
                <img src="uploads/logo-sefni.png" alt="Rumah Sulam Sefni">
            </div>
            
            <div class="login-body">
                <div class="login-header">
                    <h3>Pendaftaran Akun</h3>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" name="nama_lengkap" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="telepon" placeholder="Nomor Telepon" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="Kata Sandi" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="konfirmasi_password" placeholder="Konfirmasi Kata Sandi" required>
                    </div>
                    <button type="submit" class="btn-login">Daftar</button>
                </form>
                
                <div class="register-text">
                    Sudah Punya akun? <a href="login.php">Masuk sekarang</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>