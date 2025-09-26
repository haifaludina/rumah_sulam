<?php
// Database connection configuration
$host = 'localhost';  // Database host
$user = 'root';       // Database username
$pass = '';           // Database password
$db   = 'rumah_sulam'; // Database name

// Create connection
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$koneksi) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set to utf8
mysqli_set_charset($koneksi, "utf8");
?>
