<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../koneksi.php';

// Definisi path yang benar
$base_url = '/rumah_sulam/admin/';
$upload_dir = '../uploads/galeri/';

// Pastikan folder uploads ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

// Proses hapus foto
if (isset($_POST['hapus_foto']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Get the file name first using prepared statement
    $stmt = mysqli_prepare($koneksi, "SELECT foto FROM galeri WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $file_path = $upload_dir . $row['foto'];
        
        // Delete the file if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete the database record using prepared statement
        $delete_stmt = mysqli_prepare($koneksi, "DELETE FROM galeri WHERE id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $_SESSION['success'] = "Foto berhasil dihapus";
        } else {
            $_SESSION['error'] = "Gagal menghapus foto: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($delete_stmt);
    } else {
        $_SESSION['error'] = "Foto tidak ditemukan";
    }
    mysqli_stmt_close($stmt);
    header("Location: kelola_galeri.php");
    exit();
}

// Proses edit foto
if (isset($_POST['edit_foto']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    // Jika ada file foto baru yang diupload
    if (!empty($_FILES['foto']['name'])) {
        $nama_file = $_FILES['foto']['name'];
        $lokasi_tmp = $_FILES['foto']['tmp_name'];
        
        // Validasi file
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
            header("Location: kelola_galeri.php");
            exit();
        }
        
        // Generate unique filename
        $nama_file_unik = time() . '_' . $nama_file;
        $path = $upload_dir . $nama_file_unik;
        
        // Dapatkan nama file lama
        $stmt = mysqli_prepare($koneksi, "SELECT foto FROM galeri WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row_old = mysqli_fetch_assoc($result);
            $old_file = $upload_dir . $row_old['foto'];
            
            // Upload file baru
            if (move_uploaded_file($lokasi_tmp, $path)) {
                // Hapus file lama jika ada
                if (file_exists($old_file) && $row_old['foto'] != '') {
                    unlink($old_file);
                }
                
                // Update database dengan nama file baru menggunakan prepared statement
                $update_stmt = mysqli_prepare($koneksi, "UPDATE galeri SET judul = ?, deskripsi = ?, foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "sssi", $judul, $deskripsi, $nama_file_unik, $id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success'] = "Foto berhasil diperbarui";
                } else {
                    $_SESSION['error'] = "Gagal memperbarui foto: " . mysqli_error($koneksi);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['error'] = "Gagal mengunggah foto baru";
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        // Update hanya judul dan deskripsi
        $update_stmt = mysqli_prepare($koneksi, "UPDATE galeri SET judul = ?, deskripsi = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "ssi", $judul, $deskripsi, $id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success'] = "Foto berhasil diperbarui";
        } else {
            $_SESSION['error'] = "Gagal memperbarui foto: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($update_stmt);
    }
    
    header("Location: kelola_galeri.php");
    exit();
}

// Proses upload file baru
if (isset($_POST['tambah_foto'])) {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    // Check if file was uploaded
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $nama_file = $_FILES['foto']['name'];
        $lokasi_tmp = $_FILES['foto']['tmp_name'];
        
        // Validasi file
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
            header("Location: kelola_galeri.php");
            exit();
        }
        
        // Generate unique filename
        $nama_file_unik = time() . '_' . $nama_file;
        $path = $upload_dir . $nama_file_unik;
        
        if (move_uploaded_file($lokasi_tmp, $path)) {
            // Insert ke database menggunakan prepared statement
            $stmt = mysqli_prepare($koneksi, "INSERT INTO galeri (foto, judul, deskripsi) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $nama_file_unik, $judul, $deskripsi);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Foto berhasil ditambahkan";
            } else {
                $_SESSION['error'] = "Error database: " . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        } else {
            $upload_error = error_get_last();
            $_SESSION['error'] = "Gagal mengunggah foto. Error: " . ($upload_error ? $upload_error['message'] : 'Unknown error');
        }
    } else {
        $_SESSION['error'] = "Tidak ada file yang diunggah atau file error: " . $_FILES['foto']['error'];
    }
    header("Location: kelola_galeri.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri - Rumah Sulam Sefni</title>
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
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8a100;
        }
        
        .card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #5D4037;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4a3229;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #5D4037;
            color: #5D4037;
        }
        
        .btn-outline:hover {
            background-color: #5D4037;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .gallery-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .gallery-body {
            padding: 15px;
        }
        
        .gallery-title {
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .gallery-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .gallery-actions {
            display: flex;
            gap: 10px;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .close-btn {
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: #777;
        }
        
        .close-btn:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .preview-container {
            margin: 15px 0;
        }
        
        .preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }
        
        .current-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            margin-top: 10px;
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
    <a href="kelola_galeri.php" class="active">Kelola Galeri</a>
    <a href="kelola_user.php">Kelola User</a>
    <a href="profil.php">Profil</a>
    <a href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="card">
        <h2>Kelola Galeri</h2>
        
        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <button class="btn btn-primary" onclick="bukaModalTambah()">+ Tambah Foto</button>
    </div>

    <div class="gallery-grid">
        <?php
        $result = mysqli_query($koneksi, "SELECT * FROM galeri ORDER BY id DESC");
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $foto_path = $upload_dir . $row['foto'];
                $judul = htmlspecialchars($row['judul'] ?? 'Tanpa Judul');
                $deskripsi = htmlspecialchars($row['deskripsi'] ?? '-');
                $id = $row['id'];
                $foto = htmlspecialchars($row['foto']);
                
                echo "<div class='gallery-item'>
                        <img src='" . $foto_path . "' alt='Foto' class='gallery-img' onerror=\"this.src='../uploads/default.jpg'\">
                        <div class='gallery-body'>
                            <h4 class='gallery-title'>" . $judul . "</h4>
                            <p class='gallery-desc'>" . $deskripsi . "</p>
                            <div class='gallery-actions'>
                                <button class='btn btn-outline btn-edit' 
                                    data-id='" . $id . "' 
                                    data-judul='" . $judul . "' 
                                    data-deskripsi='" . $deskripsi . "' 
                                    data-foto='" . $foto . "'>Edit</button>
                                <button class='btn btn-danger btn-delete' data-id='" . $id . "'>Hapus</button>
                            </div>
                        </div>
                      </div>";
            }
        } else {
            echo '<div class="card"><p>Belum ada foto dalam galeri.</p></div>';
        }
        ?>
    </div>
</div>

<div class="modal-overlay" id="modalTambah">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Tambah Foto</div>
            <button class="close-btn" onclick="tutupModal('modalTambah')">&times;</button>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tambah_foto" value="1">
            <div class="form-group">
                <label for="judul">Judul</label>
                <input type="text" name="judul" id="judul" required>
            </div>
            
            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label for="foto">Foto</label>
                <input type="file" name="foto" id="foto" accept="image/*" required onchange="previewImage(this, 'preview_image')">
            </div>
            
            <div class="preview-container">
                <label>Preview:</label>
                <img id="preview_image" src="#" alt="Preview" style="display: none;">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="tutupModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEdit">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Edit Foto</div>
            <button class="close-btn" onclick="tutupModal('modalEdit')">&times;</button>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_foto" value="1">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="current-image">
                <label>Foto Saat Ini:</label>
                <img id="current_image" src="" alt="Foto Saat Ini">
            </div>
            
            <div class="form-group">
                <label for="edit_judul">Judul</label>
                <input type="text" name="judul" id="edit_judul" required>
            </div>
            
            <div class="form-group">
                <label for="edit_deskripsi">Deskripsi</label>
                <textarea name="deskripsi" id="edit_deskripsi" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_foto">Ganti Foto (opsional)</label>
                <input type="file" name="foto" id="edit_foto" accept="image/*" onchange="previewImage(this, 'edit_preview_image')">
                <small>Biarkan kosong jika tidak ingin mengganti foto</small>
            </div>
            
            <div class="preview-container">
                <label>Preview foto baru:</label>
                <img id="edit_preview_image" src="#" alt="Preview" style="display: none;">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="tutupModal('modalEdit')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalHapus">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Konfirmasi Hapus</div>
            <button class="close-btn" onclick="tutupModal('modalHapus')">&times;</button>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <input type="hidden" name="hapus_foto" value="1">
            <input type="hidden" name="id" id="hapus_id">
            
            <p style="margin-bottom: 20px;">Apakah Anda yakin ingin menghapus foto ini?</p>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="tutupModal('modalHapus')">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalTambah() {
        document.getElementById('modalTambah').style.display = 'block';
        document.getElementById('judul').value = '';
        document.getElementById('deskripsi').value = '';
        document.getElementById('foto').value = '';
        document.getElementById('preview_image').style.display = 'none';
    }

    function editFoto(id, judul, deskripsi, foto) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_judul').value = judul;
        document.getElementById('edit_deskripsi').value = deskripsi;
        document.getElementById('current_image').src = '<?php echo $upload_dir; ?>' + foto;
        
        document.getElementById('edit_preview_image').style.display = 'none';
        document.getElementById('edit_foto').value = '';
        
        document.getElementById('modalEdit').style.display = 'block';
    }

    function konfirmasiHapus(id) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('modalHapus').style.display = 'block';
    }

    function tutupModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var preview = document.getElementById(previewId);
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const judul = this.getAttribute('data-judul');
                const deskripsi = this.getAttribute('data-deskripsi');
                const foto = this.getAttribute('data-foto');
                
                editFoto(id, judul, deskripsi, foto);
            });
        });
        
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                konfirmasiHapus(id);
            });
        });
    });

    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            event.target.style.display = 'none';
        }
    }
</script>

</body>
</html>