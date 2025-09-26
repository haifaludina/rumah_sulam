<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = "Anda harus login sebagai admin untuk mengakses halaman ini";
    header("Location: login.php");
    exit();
}

require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $required_fields = ['username', 'password', 'nama_lengkap', 'email', 'role'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error_message'] = "Semua field harus diisi";
            header("Location: kelola_user.php");
            exit();
        }
    }
    
    $username = trim(mysqli_real_escape_string($koneksi, $_POST['username']));
    $nama_lengkap = trim(mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']));
    $email = trim(mysqli_real_escape_string($koneksi, $_POST['email']));
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    if (!in_array($role, ['admin', 'pelanggan', 'pemilik'])) {
        $_SESSION['error_message'] = "Role tidak valid";
        header("Location: kelola_user.php");
        exit();
    }
    
    if (strlen($_POST['password']) < 8) {
        $_SESSION['error_message'] = "Password minimal 8 karakter";
        header("Location: kelola_user.php");
        exit();
    }
    $password = $_POST['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Format email tidak valid";
        header("Location: kelola_user.php");
        exit();
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $_SESSION['error_message'] = "Username hanya boleh mengandung huruf, angka, dan underscore";
        header("Location: kelola_user.php");
        exit();
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM user WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['error_message'] = "Username sudah digunakan";
        header("Location: kelola_user.php");
        exit();
    }
    
    $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM user WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['error_message'] = "Email sudah digunakan";
        header("Location: kelola_user.php");
        exit();
    }

    mysqli_begin_transaction($koneksi);
    
    try {
        $stmt = mysqli_prepare($koneksi, 
            "INSERT INTO user (username, password, nama_lengkap, email, role, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "sssss", $username, $password, $nama_lengkap, $email, $role);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menambahkan user: " . mysqli_error($koneksi));
        }
        
        $id_user = mysqli_insert_id($koneksi);
        
        if ($role == 'admin') {
            $stmt_admin = mysqli_prepare($koneksi, 
                "INSERT INTO admin (username, password, nama_lengkap, email) 
                 VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_admin, "ssss", $username, $password, $nama_lengkap, $email);
            
            if (!mysqli_stmt_execute($stmt_admin)) {
                throw new Exception("Gagal menambahkan admin: " . mysqli_error($koneksi));
            }
            
            $id_admin = mysqli_insert_id($koneksi);
            $stmt_update = mysqli_prepare($koneksi, "UPDATE user SET id_admin = ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_update, "ii", $id_admin, $id_user);
            
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal mengupdate user: " . mysqli_error($koneksi));
            }
        } 
        elseif ($role == 'pelanggan') {
            $stmt_pelanggan = mysqli_prepare($koneksi, 
                "INSERT INTO pelanggan (nama_pelanggan, username, email, password) 
                 VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_pelanggan, "ssss", $nama_lengkap, $username, $email, $password);
            
            if (!mysqli_stmt_execute($stmt_pelanggan)) {
                throw new Exception("Gagal menambahkan pelanggan: " . mysqli_error($koneksi));
            }
            
            $id_pelanggan = mysqli_insert_id($koneksi);
            $stmt_update = mysqli_prepare($koneksi, "UPDATE user SET id_pelanggan = ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_update, "ii", $id_pelanggan, $id_user);
            
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal mengupdate user: " . mysqli_error($koneksi));
            }
        }
        elseif ($role == 'pemilik') {
            $stmt_update = mysqli_prepare($koneksi, "UPDATE user SET role = 'pemilik' WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_update, "i", $id_user);
            
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal mengupdate role pemilik: " . mysqli_error($koneksi));
            }
        }
        
        mysqli_commit($koneksi);
        $_SESSION['success_message'] = "User berhasil ditambahkan";
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['error_message'] = $e->getMessage();
    }

    header("Location: kelola_user.php");
    exit();
} else {
    $_SESSION['error_message'] = "Metode request tidak valid";
    header("Location: kelola_user.php");
    exit();
}