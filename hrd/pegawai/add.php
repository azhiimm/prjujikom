<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/connection.php';

// Mengambil data untuk dropdown
$pangkatGolongan = getDropdownData($conn, 'pangkat_golongan', 'id', 'nama_pangkat');
$unitKerja = getDropdownData($conn, 'unit_kerja', 'id', 'nama_unit');
$jabatan = getDropdownData($conn, 'jabatan', 'id', 'nama_jabatan');
$eselon = getDropdownData($conn, 'eselon', 'id', 'nama_eselon');
$lokasi = getDropdownData($conn, 'lokasi', 'id', 'nama_lokasi');
$kelasJabatan = getDropdownData($conn, 'kelas_jabatan', 'id', 'nama_kelas');

// Cek jika form disubmit
$success = $error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi input
    $nip = clean($conn, $_POST['nip']);
    $nama = clean($conn, $_POST['nama']);
    $pangkat_golongan_id = !empty($_POST['pangkat_golongan_id']) ? (int)$_POST['pangkat_golongan_id'] : null;
    $unit_kerja_id = !empty($_POST['unit_kerja_id']) ? (int)$_POST['unit_kerja_id'] : null;
    $status = clean($conn, $_POST['status']);
    $jabatan_id = !empty($_POST['jabatan_id']) ? (int)$_POST['jabatan_id'] : null;
    $eselon_id = !empty($_POST['eselon_id']) ? (int)$_POST['eselon_id'] : null;
    $jenis_kelamin = clean($conn, $_POST['jenis_kelamin']);
    $pendidikan = clean($conn, $_POST['pendidikan']);
    $lokasi_id = !empty($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : null;
    $kelas_jabatan_id = !empty($_POST['kelas_jabatan_id']) ? (int)$_POST['kelas_jabatan_id'] : null;
    $alamat = clean($conn, $_POST['alamat']);
    
    // Cek NIP tidak duplikat
    $checkQuery = "SELECT id FROM pegawai WHERE nip = ?";
    $stmtCheck = $conn->prepare($checkQuery);
    $stmtCheck->bind_param("s", $nip);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        $error = "NIP sudah terdaftar. Gunakan NIP lain.";
    } else {
        // Insert data pegawai
        $insertQuery = "INSERT INTO pegawai (nip, nama, pangkat_golongan_id, unit_kerja_id, status, jabatan_id, eselon_id, jenis_kelamin, pendidikan, lokasi_id, kelas_jabatan_id, alamat) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssiissiissis", $nip, $nama, $pangkat_golongan_id, $unit_kerja_id, $status, $jabatan_id, $eselon_id, $jenis_kelamin, $pendidikan, $lokasi_id, $kelas_jabatan_id, $alamat);
        
        if ($stmt->execute()) {
            $pegawai_id = $stmt->insert_id;
            $success = "Data pegawai berhasil ditambahkan.";
            
            // Log aktivitas penambahan pegawai
            $logQuery = "INSERT INTO history_pegawai (pegawai_id, field_name, old_value, new_value, changed_by) 
                       VALUES (?, 'create', 'new', 'pegawai baru', ?)";
            $stmtLog = $conn->prepare($logQuery);
            $stmtLog->bind_param("ii", $pegawai_id, $_SESSION['user_id']);
            $stmtLog->execute();
            
            // Redirect ke halaman daftar pegawai setelah berhasil
            header("Location: list.php?success=add");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pegawai - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include '../templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include '../templates/header.php'; ?>
            
            <main>
                <div class="page-header">
                    <h1>Tambah Pegawai</h1>
                    <div>
                        <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                </div>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-wide">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nip">NIP <span class="required">*</span></label>
                                <input type="text" id="nip" name="nip" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="nama">Nama <span class="required">*</span></label>
                                <input type="text" id="nama" name="nama" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pangkat_golongan_id">Pangkat & Golongan</label>
                                <select id="pangkat_golongan_id" name="pangkat_golongan_id">
                                    <option value="">-- Pilih Pangkat --</option>
                                    <?php foreach ($pangkatGolongan as $pg): ?>
                                        <option value="<?php echo $pg['id']; ?>"><?php echo $pg['nama_pangkat']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="unit_kerja_id">Unit Kerja</label>
                                <select id="unit_kerja_id" name="unit_kerja_id">
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    <?php foreach ($unitKerja as $uk): ?>
                                        <option value="<?php echo $uk['id']; ?>"><?php echo $uk['nama_unit']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select id="status" name="status" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="PNS">PNS</option>
                                    <option value="PPPK">PPPK</option>
                                    <option value="TENAGA KONTRAK">TENAGA KONTRAK</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="jabatan_id">Jabatan</label>
                                <select id="jabatan_id" name="jabatan_id" class="searchable">
                                    <option value="">-- Pilih Jabatan --</option>
                                    <?php foreach ($jabatan as $jab): ?>
                                        <option value="<?php echo $jab['id']; ?>"><?php echo $jab['nama_jabatan']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="eselon_id">Eselon</label>
                                <select id="eselon_id" name="eselon_id">
                                    <option value="">-- Pilih Eselon --</option>
                                    <?php foreach ($eselon as $es): ?>
                                        <option value="<?php echo $es['id']; ?>"><?php echo $es['nama_eselon']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="jenis_kelamin">Jenis Kelamin <span class="required">*</span></label>
                                <select id="jenis_kelamin" name="jenis_kelamin" required>
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pendidikan">Pendidikan</label>
                                <input type="text" id="pendidikan" name="pendidikan">
                            </div>
                            
                            <div class="form-group">
                                <label for="lokasi_id">Lokasi</label>
                                <select id="lokasi_id" name="lokasi_id">
                                    <option value="">-- Pilih Lokasi --</option>
                                    <?php foreach ($lokasi as $lok): ?>
                                        <option value="<?php echo $lok['id']; ?>"><?php echo $lok['nama_lokasi']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="kelas_jabatan_id">Kelas Jabatan</label>
                                <select id="kelas_jabatan_id" name="kelas_jabatan_id">
                                    <option value="">-- Pilih Kelas Jabatan --</option>
                                    <?php foreach ($kelasJabatan as $kj): ?>
                                        <option value="<?php echo $kj['id']; ?>"><?php echo $kj['nama_kelas']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" style="visibility: hidden;">
                                <!-- Placeholder untuk layout -->
                                <label for="placeholder">Placeholder</label>
                                <input type="text" id="placeholder" disabled>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" rows="4"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </form>
                </div>
            </main>
            
            <?php include '../templates/footer.php'; ?>
        </div>
    </div>
    
    <script>
    // Script untuk membuat dropdown searchable
    document.addEventListener('DOMContentLoaded', function() {
        const searchableSelects = document.querySelectorAll('.searchable');
        
        searchableSelects.forEach(select => {
            // Implementasi dropdown searchable dapat ditambahkan di sini
            // Contoh implementasi sederhana dengan placeholder
            select.addEventListener('focus', function() {
                console.log('Dropdown searchable activated');
            });
        });
    });
    </script>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>