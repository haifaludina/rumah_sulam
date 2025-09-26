<?php
session_start();
require_once 'koneksi.php';
$username = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Cek di tabel admin
    $queryAdmin = "SELECT * FROM admin WHERE username = ?";
    $stmtAdmin = $koneksi->prepare($queryAdmin);
    $stmtAdmin->bind_param("s", $username);
    $stmtAdmin->execute();
    $resultAdmin = $stmtAdmin->get_result();

    if ($resultAdmin->num_rows == 1) {
        $admin = $resultAdmin->fetch_assoc();

        if ($password == $admin['password']) {
            $_SESSION['id_admin'] = $admin['id_admin'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['nama_admin'] = $admin['nama_admin'];
            $_SESSION['role'] = 'admin';

            header('Location: admin/dashboard.php');
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        // Cek di tabel user untuk role pemilik
        $queryUser = "SELECT * FROM user WHERE username = ?";
        $stmtUser = $koneksi->prepare($queryUser);
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows == 1) {
            $user = $resultUser->fetch_assoc();

            if ($password == $user['password'] && $user['role'] == 'pemilik') {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_user'] = $user['nama_user'];
                $_SESSION['role'] = 'pemilik';

                header('Location: admin/dashboard.php');
                exit();
            }
        }

        // Cek di tabel pelanggan
        $queryPelanggan = "SELECT * FROM pelanggan WHERE username = ? OR email = ? OR no_hp = ?";
        $stmtPelanggan = $koneksi->prepare($queryPelanggan);
        $stmtPelanggan->bind_param("sss", $username, $username, $username);
        $stmtPelanggan->execute();
        $resultPelanggan = $stmtPelanggan->get_result();

        if ($resultPelanggan->num_rows == 1) {
            $pelanggan = $resultPelanggan->fetch_assoc();

            if ($password == $pelanggan['password']) {
                $_SESSION['id_pelanggan'] = $pelanggan['id_pelanggan'];
                $_SESSION['username'] = $pelanggan['username'];
                $_SESSION['nama_pelanggan'] = $pelanggan['nama_pelanggan'];
                $_SESSION['email'] = $pelanggan['email'];
                $_SESSION['no_hp'] = $pelanggan['no_hp'];
                $_SESSION['role'] = $pelanggan['role'];

                if ($pelanggan['role'] == 'pemilik') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: pelanggan/index.php');
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username/Email/No HP tidak ditemukan!";
        }
        
        if (isset($stmtPelanggan)) $stmtPelanggan->close();
        if (isset($stmtUser)) $stmtUser->close();
    }
    
    $stmtAdmin->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rumah Sulam Sefni</title>
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
    <div class="login-wrapper">
        <a href="http://localhost/rumah_sulam/pelanggan/index.php" class="back-link">
            &lt; Kembali ke Beranda
        </a>
        
        <div class="login-card">
            
            <div class="logo-container">
               <img src="uploads/logo-sefni.png" alt="Rumah Sulam Sefni">
            </div>
            
            <div class="login-body">
                <div class="login-header">
                    <h3>Masuk ke Akun Anda</h3>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <input type="text" class="form-control" name="username" placeholder="Username, Email, atau No HP" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="Kata Sandi" required>
                    </div>
                    <button type="submit" class="btn-login">Masuk</button>
                </form>
                
                <div class="register-text">
                    Belum punya akun? <a href="daftar.php">Daftar Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>