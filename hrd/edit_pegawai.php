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

// Fungsi untuk mencatat perubahan ke history
function logChanges($conn, $pegawai_id, $field_name, $old_value, $new_value, $user_id) {
    if ($old_value != $new_value) {
        $query = "INSERT INTO history_pegawai (pegawai_id, field_name, old_value, new_value, changed_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssi", $pegawai_id, $field_name, $old_value, $new_value, $user_id);
        $stmt->execute();
    }
}

// Ambil data pegawai yang akan diedit
$query = "SELECT * FROM pegawai WHERE id = ?";
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

// Ambil data untuk dropdown
$jabatanData = getDropdownData($conn, 'jabatan', 'id', 'nama_jabatan');
$unitKerjaData = getDropdownData($conn, 'unit_kerja', 'id', 'nama_unit');
$pangkatGolonganData = getDropdownData($conn, 'pangkat_golongan', 'id', 'nama_pangkat');
$eselonData = getDropdownData($conn, 'eselon', 'id', 'nama_eselon');
$lokasiData = getDropdownData($conn, 'lokasi', 'id', 'nama_lokasi');
$kelasJabatanData = getDropdownData($conn, 'kelas_jabatan', 'id', 'nama_kelas');

// Status pegawai (nilai statis)
$statusOptions = ['PNS', 'PPPK', 'TENAGA KONTRAK'];

// Pendidikan (nilai statis)
$pendidikanOptions = ['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

// Jenis kelamin (nilai statis)
$jenisKelaminOptions = ['Laki-laki', 'Perempuan'];

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil semua data form
    $nip = clean($conn, $_POST['nip']);
    $nama = clean($conn, $_POST['nama']);
    $status = clean($conn, $_POST['status']);
    $jenis_kelamin = clean($conn, $_POST['jenis_kelamin']);
    $pendidikan = clean($conn, $_POST['pendidikan']);
    $alamat = clean($conn, $_POST['alamat']);
    
    // Ambil nilai dropdown atau set NULL jika kosong
    $jabatan_id = !empty($_POST['jabatan_id']) ? (int)$_POST['jabatan_id'] : NULL;
    $unit_kerja_id = !empty($_POST['unit_kerja_id']) ? (int)$_POST['unit_kerja_id'] : NULL;
    $pangkat_golongan_id = !empty($_POST['pangkat_golongan_id']) ? (int)$_POST['pangkat_golongan_id'] : NULL;
    $eselon_id = !empty($_POST['eselon_id']) ? (int)$_POST['eselon_id'] : NULL;
    $lokasi_id = !empty($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : NULL;
    $kelas_jabatan_id = !empty($_POST['kelas_jabatan_id']) ? (int)$_POST['kelas_jabatan_id'] : NULL;
    
    // Validasi data
    $errors = [];
    
    if (empty($nip)) {
        $errors[] = "NIP harus diisi";
    }
    
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($status)) {
        $errors[] = "Status harus diisi";
    }
    
    // Cek apakah NIP sudah ada (selain pegawai yang sedang diedit)
    $checkNip = "SELECT id FROM pegawai WHERE nip = ? AND id != ?";
    $stmtCheck = $conn->prepare($checkNip);
    $stmtCheck->bind_param("si", $nip, $pegawai_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        $errors[] = "NIP sudah digunakan oleh pegawai lain";
    }
    
    if (empty($errors)) {
        // Update data pegawai
        $query = "UPDATE pegawai SET 
                  nip = ?, 
                  nama = ?, 
                  status = ?, 
                  jenis_kelamin = ?, 
                  pendidikan = ?, 
                  alamat = ?, 
                  jabatan_id = ?, 
                  unit_kerja_id = ?,
                  pangkat_golongan_id = ?,
                  eselon_id = ?,
                  lokasi_id = ?,
                  kelas_jabatan_id = ?
                  WHERE id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssiiiiiii", $nip, $nama, $status, $jenis_kelamin, $pendidikan, $alamat, 
                         $jabatan_id, $unit_kerja_id, $pangkat_golongan_id, $eselon_id, $lokasi_id, $kelas_jabatan_id, $pegawai_id);
        
        if ($stmt->execute()) {
            // Log perubahan ke history
            logChanges($conn, $pegawai_id, 'nip', $pegawai['nip'], $nip, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'nama', $pegawai['nama'], $nama, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'status', $pegawai['status'], $status, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'jenis_kelamin', $pegawai['jenis_kelamin'], $jenis_kelamin, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'pendidikan', $pegawai['pendidikan'], $pendidikan, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'alamat', $pegawai['alamat'], $alamat, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'jabatan_id', $pegawai['jabatan_id'], $jabatan_id, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'unit_kerja_id', $pegawai['unit_kerja_id'], $unit_kerja_id, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'pangkat_golongan_id', $pegawai['pangkat_golongan_id'], $pangkat_golongan_id, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'eselon_id', $pegawai['eselon_id'], $eselon_id, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'lokasi_id', $pegawai['lokasi_id'], $lokasi_id, $_SESSION['user_id']);
            logChanges($conn, $pegawai_id, 'kelas_jabatan_id', $pegawai['kelas_jabatan_id'], $kelas_jabatan_id, $_SESSION['user_id']);
            
            $_SESSION['success_message'] = "Data pegawai berhasil diperbarui";
            header("Location: view_pegawai.php?id=" . $pegawai_id);
            exit();
        } else {
            $errors[] = "Gagal memperbarui data pegawai: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pegawai - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            
            <main>
                <h1>Edit Data Pegawai</h1>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Form Edit Pegawai</h2>
                    </div>
                    
                    <div class="card-body">
                        <form action="edit_pegawai.php?id=<?php echo $pegawai_id; ?>" method="post" id="form-pegawai">
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="nama">Nama Lengkap <span class="required">*</span></label>
                                    <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($pegawai['nama']); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="nip">NIP <span class="required">*</span></label>
                                    <input type="text" id="nip" name="nip" value="<?php echo htmlspecialchars($pegawai['nip']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="status">Status Kepegawaian <span class="required">*</span></label>
                                    <select id="status" name="status" required>
                                        <option value="">Pilih Status</option>
                                        <?php foreach ($statusOptions as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo ($pegawai['status'] == $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="jenis_kelamin">Jenis Kelamin <span class="required">*</span></label>
                                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" <?php echo ($pegawai['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="P" <?php echo ($pegawai['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="pangkat_golongan_id">Pangkat/Golongan <span class="required">*</span></label>
                                    <select id="pangkat_golongan_id" name="pangkat_golongan_id" required>
                                        <option value="">Pilih Pangkat/Golongan</option>
                                        <?php foreach ($pangkatGolonganData as $pangkat): ?>
                                            <option value="<?php echo $pangkat['id']; ?>" <?php echo ($pegawai['pangkat_golongan_id'] == $pangkat['id']) ? 'selected' : ''; ?>><?php echo $pangkat['nama_pangkat']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="pendidikan">Pendidikan Terakhir <span class="required">*</span></label>
                                    <select id="pendidikan" name="pendidikan" required>
                                        <option value="">Pilih Pendidikan</option>
                                        <?php foreach ($pendidikanOptions as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo ($pegawai['pendidikan'] == $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="jabatan_id">Jabatan <span class="required">*</span></label>
                                    <select id="jabatan_id" name="jabatan_id" required>
                                        <option value="">Pilih Jabatan</option>
                                        <?php foreach ($jabatanData as $jabatan): ?>
                                            <option value="<?php echo $jabatan['id']; ?>" <?php echo ($pegawai['jabatan_id'] == $jabatan['id']) ? 'selected' : ''; ?>><?php echo $jabatan['nama_jabatan']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="unit_kerja_id">Unit Kerja <span class="required">*</span></label>
                                    <select id="unit_kerja_id" name="unit_kerja_id" required>
                                        <option value="">Pilih Unit Kerja</option>
                                        <?php foreach ($unitKerjaData as $unit): ?>
                                            <option value="<?php echo $unit['id']; ?>" <?php echo ($pegawai['unit_kerja_id'] == $unit['id']) ? 'selected' : ''; ?>><?php echo $unit['nama_unit']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="eselon_id">Eselon <span class="required">*</span></label>
                                    <select id="eselon_id" name="eselon_id" required>
                                        <option value="">Pilih Eselon</option>
                                        <?php foreach ($eselonData as $eselon): ?>
                                            <option value="<?php echo $eselon['id']; ?>" <?php echo ($pegawai['eselon_id'] == $eselon['id']) ? 'selected' : ''; ?>><?php echo $eselon['nama_eselon']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="kelas_jabatan_id">Kelas Jabatan <span class="required">*</span></label>
                                    <select id="kelas_jabatan_id" name="kelas_jabatan_id" required>
                                        <option value="">Pilih Kelas Jabatan</option>
                                        <?php foreach ($kelasJabatanData as $kelas): ?>
                                            <option value="<?php echo $kelas['id']; ?>" <?php echo ($pegawai['kelas_jabatan_id'] == $kelas['id']) ? 'selected' : ''; ?>><?php echo $kelas['nama_kelas']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="lokasi_id">Lokasi <span class="required">*</span></label>
                                    <select id="lokasi_id" name="lokasi_id" required>
                                        <option value="">Pilih Lokasi</option>
                                        <?php foreach ($lokasiData as $lokasi): ?>
                                            <option value="<?php echo $lokasi['id']; ?>" <?php echo ($pegawai['lokasi_id'] == $lokasi['id']) ? 'selected' : ''; ?>><?php echo $lokasi['nama_lokasi']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="alamat">Alamat <span class="required">*</span></label>
                                <textarea id="alamat" name="alamat" rows="3" required><?php echo htmlspecialchars($pegawai['alamat']); ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                                <a href="pegawai.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
            
            <?php include 'templates/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-pegawai');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                    } else {
                        field.classList.remove('error');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Mohon lengkapi semua field yang wajib diisi!');
                }
            });
            
            // Hapus class error saat input diubah
            form.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('change', function() {
                    this.classList.remove('error');
                });
            });
        });
    </script>
</body>
</html>