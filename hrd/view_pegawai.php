<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';

// Cek apakah ada ID pegawai
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID pegawai tidak valid";
    header("Location: pegawai.php");
    exit();
}

$pegawai_id = (int)$_GET['id'];

// Query untuk mendapatkan data pegawai
$query = "SELECT p.*, 
          j.nama_jabatan, 
          u.nama_unit, 
          pg.nama_pangkat AS pangkat_golongan,
          e.nama_eselon AS eselon,
          l.nama_lokasi AS lokasi,
          k.nama_kelas AS kelas_jabatan
          FROM pegawai p
          LEFT JOIN jabatan j ON p.jabatan_id = j.id
          LEFT JOIN unit_kerja u ON p.unit_kerja_id = u.id
          LEFT JOIN pangkat_golongan pg ON p.pangkat_golongan_id = pg.id
          LEFT JOIN eselon e ON p.eselon_id = e.id
          LEFT JOIN lokasi l ON p.lokasi_id = l.id
          LEFT JOIN kelas_jabatan k ON p.kelas_jabatan_id = k.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pegawai_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Data pegawai tidak ditemukan";
    header("Location: pegawai.php");
    exit();
}

$pegawai = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pegawai - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            
            <main>
                <h1>Detail Pegawai</h1>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Informasi Pegawai</h2>
                    </div>
                    
                    <div class="card-body">
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Nama Lengkap</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['nama']); ?></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>NIP</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['nip']); ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Status Kepegawaian</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['status']); ?></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Jenis Kelamin</label>
                                <div class="detail-value"><?php echo ($pegawai['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Pangkat/Golongan</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['pangkat_golongan']); ?></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Pendidikan</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['pendidikan']); ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Jabatan</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['nama_jabatan']); ?></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Unit Kerja</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['nama_unit']); ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Eselon</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['eselon']); ?></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Kelas Jabatan</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['kelas_jabatan']); ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Lokasi</label>
                                <div class="detail-value"><?php echo htmlspecialchars($pegawai['lokasi']); ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Alamat</label>
                            <div class="detail-value"><?php echo htmlspecialchars($pegawai['alamat']); ?></div>
                        </div>
                        
                        <div class="form-group-row">
                            <div class="form-group col-md-6">
                                <label>Terdaftar Sejak</label>
                                <div class="detail-value"><?php echo date('d F Y', strtotime($pegawai['created_at'])); ?></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Terakhir Diperbarui</label>
                                <div class="detail-value"><?php echo date('d F Y H:i', strtotime($pegawai['updated_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="pegawai.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </main>
            
            <?php include 'templates/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>