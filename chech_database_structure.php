<?php
// Script untuk memeriksa struktur database - simpan dan jalankan file ini sekali untuk debugging
include '../koneksi.php';

echo "<h1>Database Structure Checker</h1>";

// Fungsi untuk menampilkan kolom dari tabel
function describeTable($conn, $tableName) {
    echo "<h2>Struktur Tabel: $tableName</h2>";
    
    $query = "DESCRIBE $tableName";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo "<p>Error: " . mysqli_error($conn) . "</p>";
        return;
    }
    
    if (mysqli_num_rows($result) === 0) {
        echo "<p>Tabel tidak memiliki kolom atau tidak ada.</p>";
        return;
    }
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] === NULL ? 'NULL' : $row['Default']) . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Dapatkan daftar semua tabel di database
$tablesQuery = "SHOW TABLES";
$tablesResult = mysqli_query($conn, $tablesQuery);

if (!$tablesResult) {
    echo "<p>Error mendapatkan daftar tabel: " . mysqli_error($conn) . "</p>";
    exit;
}

echo "<h2>Daftar Tabel</h2>";
echo "<ul>";
while ($table = mysqli_fetch_array($tablesResult)) {
    $tableName = $table[0];
    echo "<li>$tableName</li>";
}
echo "</ul>";

// Cek secara spesifik tabel retur
$tables = array("retur", "retur_produk");
foreach ($tables as $table) {
    // Cek apakah tabel ada
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        // Tampilkan struktur tabel
        describeTable($conn, $table);
        
        // Tampilkan contoh data
        echo "<h3>Contoh Data dari $table</h3>";
        $dataQuery = "SELECT * FROM $table LIMIT 3";
        $dataResult = mysqli_query($conn, $dataQuery);
        
        if (!$dataResult) {
            echo "<p>Error: " . mysqli_error($conn) . "</p>";
            continue;
        }
        
        if (mysqli_num_rows($dataResult) === 0) {
            echo "<p>Tidak ada data.</p>";
            continue;
        }
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        
        // Header
        echo "<tr>";
        $fields = mysqli_fetch_fields($dataResult);
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Data
        mysqli_data_seek($dataResult, 0);
        while ($row = mysqli_fetch_assoc($dataResult)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . ($value === NULL ? 'NULL' : $value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<h2>Tabel $table tidak ditemukan di database</h2>";
    }
}
?>