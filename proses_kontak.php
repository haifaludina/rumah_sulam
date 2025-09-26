<?php
// Start or resume the session
session_start();

// Include database connection
include '../koneksi.php';

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $nama = sanitize_input($_POST["nama"]);
    $email = sanitize_input($_POST["email"]);
    $telepon = isset($_POST["telepon"]) ? sanitize_input($_POST["telepon"]) : "";
    $subjek = sanitize_input($_POST["subjek"]);
    $pesan = sanitize_input($_POST["pesan"]);
    $tanggal = date("Y-m-d H:i:s"); // Current date and time
    
    // Validate input
    $errors = [];
    
    // Validate name
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    // Validate subject
    if (empty($subjek)) {
        $errors[] = "Subjek harus diisi";
    }
    
    // Validate message
    if (empty($pesan)) {
        $errors[] = "Pesan harus diisi";
    }
    
    // If there are no errors, insert data into database
    if (empty($errors)) {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $koneksi->prepare("INSERT INTO kontak (nama, email, telepon, subjek, pesan, tanggal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nama, $email, $telepon, $subjek, $pesan, $tanggal);
        
        // Execute the statement
        if ($stmt->execute()) {
            // Set success message
            $_SESSION['success_message'] = "Pesan Anda telah berhasil dikirim. Terima kasih telah menghubungi kami!";
        } else {
            // Set error message with specific database error
            $_SESSION['error_message'] = "Maaf, terjadi kesalahan dalam mengirim pesan Anda: " . $stmt->error;
        }
        
        // Close statement
        $stmt->close();
    } else {
        // Set error messages
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
    
    // Close connection
    $koneksi->close();
    
    // Redirect back to contact form
    header("Location: kontak.php");
    exit();
} else {
    // If not a POST request, redirect to contact form
    header("Location: kontak.php");
    exit();
}
?>