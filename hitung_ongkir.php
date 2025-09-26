<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID alamat tidak valid']);
    exit();
}

$id_alamat = $_GET['id'];
$id_pelanggan = $_SESSION['id_pelanggan'];
$metode_pengiriman = isset($_GET['method']) ? $_GET['method'] : 'Regular';

// Ambil data alamat
$query = $koneksi->prepare("SELECT * FROM alamat_pelanggan WHERE id_alamat = ? AND id_pelanggan = ?");
$query->bind_param("ii", $id_alamat, $id_pelanggan);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Alamat tidak ditemukan']);
    exit();
}

$alamat = $result->fetch_assoc();

// Cek apakah direct checkout atau tidak
$direct_checkout = isset($_SESSION['direct_checkout']) && $_SESSION['direct_checkout'] === true;

if ($direct_checkout) {
    $items_query = mysqli_query($koneksi, "SELECT tc.jumlah, p.* FROM temp_checkout tc JOIN produk p ON tc.id_produk = p.id_produk WHERE tc.id_pelanggan = $id_pelanggan");
} else {
    $items_query = mysqli_query($koneksi, "SELECT k.jumlah, p.* FROM keranjang k JOIN produk p ON k.id_produk = p.id_produk WHERE k.id_pelanggan = $id_pelanggan");
}

$subtotal = 0;
$beratTotal = 0;

// Mapping berat produk
$beratMapping = [
    'baju-kurung' => 500,
    'kebaya' => 600,
    'kemeja' => 400,
    'selendang' => 300,
    'sendal' => 700,
    'jilbab' => 200,
    'tas' => 800,
    'sarung-bantal' => 400
];

while ($item = mysqli_fetch_assoc($items_query)) {
    $subtotal += ($item['harga'] * $item['jumlah']);
    
    $beratProduk = 500; // default
    if (isset($beratMapping[$item['nama_produk']])) {
        $beratProduk = $beratMapping[$item['nama_produk']];
    }
    
    $beratTotal += $beratProduk * $item['jumlah'];
}

// Fungsi hitung ongkir
function hitungOngkir($kotaTujuan, $kecamatanTujuan, $beratTotal, $metode) {
    // Lokasi toko
    $kotaPengirim = 'Tanah Datar';
    $kecamatanPengirim = 'Salimpaung';
    
    // Tarif dasar per km
    $tarifPerKm = 150;
    
    // Data jarak dari Salimpaung ke berbagai kota (dalam km)
    $jarakData = [
        // Dalam kota Tanah Datar
        'Tanah Datar' => [
            'Salimpaung' => 0, // Lokasi toko
            'Batipuh' => 25,
            'Pariangan' => 30,
            'Rambatan' => 20,
            'Lima Kaum' => 15
        ],
        // Kota lain di Sumbar
        'Padang Panjang' => 40,
        'Bukittinggi' => 60,
        'Padang' => 100,
        'Solok' => 80,
        'Pesisir Selatan' => 120,
        // Kota lain di Indonesia
        'Pekanbaru' => 300,
        'Medan' => 350,
        'Jambi' => 400,
        'Palembang' => 600,
        'Bengkulu' => 500,
        'Bandar Lampung' => 700,
        'Jakarta' => 1200,
        'Bandung' => 1100,
        'Semarang' => 1300,
        'Yogyakarta' => 1350,
        'Surabaya' => 1500,
        'Malang' => 1550,
        'Denpasar' => 1700,
        'Makassar' => 2200,
        'Manado' => 2400
    ];
    
    // Hitung jarak
    $jarak = 100; // default 100 km jika tidak ditemukan
    
    if ($kotaTujuan == $kotaPengirim) {
        // Jika dalam kota yang sama
        if (isset($jarakData[$kotaPengirim][$kecamatanTujuan])) {
            $jarak = $jarakData[$kotaPengirim][$kecamatanTujuan];
        } else {
            $jarak = 30; // default untuk dalam kota
        }
    } else {
        // Jika beda kota
        if (isset($jarakData[$kotaTujuan])) {
            $jarak = $jarakData[$kotaTujuan];
        }
    }
    
    // Ongkir dasar berdasarkan jarak
    $ongkirDasar = $jarak * $tarifPerKm;
    
    // Tambahan biaya untuk express (+50%)
    if (strpos($metode, 'Express') !== false) {
        $ongkirDasar *= 1.5;
    }
    
    // Biaya berat: Rp 10.000 untuk 1 kg pertama, +Rp 5.000/kg berikutnya
    $beratKg = ceil($beratTotal / 1000);
    $biayaBerat = 10000 + (max(0, $beratKg - 1) * 5000);
    
    // Total ongkir (minimum Rp 15.000)
    return max(15000, $ongkirDasar + $biayaBerat);
}

// Hitung ongkir
$shipping_cost = hitungOngkir($alamat['kota'], $alamat['kecamatan'], $beratTotal, $metode_pengiriman);
$tax = ceil($subtotal * 0.01);
$total = $subtotal + $shipping_cost + $tax;

echo json_encode([
    'success' => true,
    'subtotal' => $subtotal,
    'shipping_cost' => $shipping_cost,
    'tax' => $tax,
    'total' => $total
]);
?>