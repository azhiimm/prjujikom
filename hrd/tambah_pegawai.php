<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';

// Definisikan fungsi helper jika belum ada
if (!function_exists('clean')) {
    function clean($conn, $data) {
        return mysqli_real_escape_string($conn, trim($data));
    }
}

if (!function_exists('getDropdownData')) {
    function getDropdownData($conn, $table, $id_field, $name_field) {
        $result = [];
        $query = "SELECT $id_field, $name_field FROM $table ORDER BY $name_field";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->execute();
            $queryResult = $stmt->get_result();
            while ($row = $queryResult->fetch_assoc()) {
                $result[] = $row;
            }
            $stmt->close();
        }
        return $result;
    }
}

if (!function_exists('getNamaFromId')) {
    function getNamaFromId($conn, $table, $id, $id_field, $name_field) {
        $query = "SELECT $name_field FROM $table WHERE $id_field = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return $row[$name_field];
            }
            $stmt->close();
        }
        return '';
    }
}

if (!function_exists('logChange')) {
    function logChange($conn, $pegawai_id, $field_name, $old_value, $new_value, $user_id) {
        $query = "INSERT INTO pegawai_history (pegawai_id, field_name, old_value, new_value, changed_by, changed_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("isssi", $pegawai_id, $field_name, $old_value, $new_value, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

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

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug - tampilkan data yang diterima
    // echo "<pre>"; print_r($_POST); echo "</pre>"; // Uncomment untuk debugging
    
    // Validasi input
    $nama = clean($conn, $_POST['nama']);
    $nip = clean($conn, $_POST['nip']);
    $status = clean($conn, $_POST['status']);
    $jabatan_id = (int)$_POST['jabatan_id'];
    $unit_kerja_id = (int)$_POST['unit_kerja_id'];
    $pangkat_golongan_id = (int)$_POST['pangkat_golongan_id'];
    $eselon_id = (int)$_POST['eselon_id'];
    $jenis_kelamin = clean($conn, $_POST['jenis_kelamin']);
    $pendidikan = clean($conn, $_POST['pendidikan']);
    $lokasi_id = (int)$_POST['lokasi_id'];
    $kelas_jabatan_id = (int)$_POST['kelas_jabatan_id'];
    $alamat = clean($conn, $_POST['alamat']);
    
    // Debug - tampilkan data setelah pembersihan
    // echo "Data setelah dibersihkan:<br>";
    // echo "Nama: $nama, NIP: $nip, Status: $status, Jabatan ID: $jabatan_id<br>";
    // echo "Unit Kerja ID: $unit_kerja_id, Pangkat ID: $pangkat_golongan_id, Eselon ID: $eselon_id<br>";
    // echo "Jenis Kelamin: $jenis_kelamin, Pendidikan: $pendidikan, Lokasi ID: $lokasi_id<br>";
    // echo "Kelas Jabatan ID: $kelas_jabatan_id, Alamat: $alamat<br>";
    
    // Validasi NIP unik
    $checkNipQuery = "SELECT id FROM pegawai WHERE nip = ?";
    $checkStmt = $conn->prepare($checkNipQuery);
    
    if (!$checkStmt) {
        // Debug pesan error
        $_SESSION['error_message'] = "Error preparing statement (check NIP): " . $conn->error;
    } else {
        $checkStmt->bind_param("s", $nip);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $_SESSION['error_message'] = "NIP sudah digunakan oleh pegawai lain!";
        } else {
            // Query untuk insert data pegawai baru
            $query = "INSERT INTO pegawai (nama, nip, pangkat_golongan_id, unit_kerja_id, status, jabatan_id, 
                     eselon_id, jenis_kelamin, pendidikan, lokasi_id, kelas_jabatan_id, alamat, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                // Debug pesan error
                $_SESSION['error_message'] = "Error preparing statement (insert): " . $conn->error;
            } else {
                // Debug tipe data
                // echo "Tipe data: nama(".gettype($nama)."), nip(".gettype($nip)."), pangkat_golongan_id(".gettype($pangkat_golongan_id)."), unit_kerja_id(".gettype($unit_kerja_id).")";
                // echo "status(".gettype($status)."), jabatan_id(".gettype($jabatan_id)."), eselon_id(".gettype($eselon_id)."), jenis_kelamin(".gettype($jenis_kelamin).")";
                // echo "pendidikan(".gettype($pendidikan)."), lokasi_id(".gettype($lokasi_id)."), kelas_jabatan_id(".gettype($kelas_jabatan_id)."), alamat(".gettype($alamat)."), user_id(".gettype($_SESSION['user_id']).")";
                
                // FIX: Removed the created_at parameter from bind_param as we're using NOW() in the SQL query
                $stmt->bind_param("ssiissssssis", $nama, $nip, $pangkat_golongan_id, $unit_kerja_id, $status, $jabatan_id, 
                                $eselon_id, $jenis_kelamin, $pendidikan, $lokasi_id, $kelas_jabatan_id, $alamat);
                
                if ($stmt->execute()) {
                    $pegawai_id = $stmt->insert_id;
                    
                    // Log semua field sebagai history baru
                    logChange($conn, $pegawai_id, 'nama', '', $nama, $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'nip', '', $nip, $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'pangkat_golongan_id', '', getNamaFromId($conn, 'pangkat_golongan', $pangkat_golongan_id, 'id', 'nama_pangkat'), $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'unit_kerja_id', '', getNamaFromId($conn, 'unit_kerja', $unit_kerja_id, 'id', 'nama_unit'), $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'status', '', $status, $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'jabatan_id', '', getNamaFromId($conn, 'jabatan', $jabatan_id, 'id', 'nama_jabatan'), $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'eselon_id', '', getNamaFromId($conn, 'eselon', $eselon_id, 'id', 'nama_eselon'), $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'jenis_kelamin', '', $jenis_kelamin, $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'pendidikan', '', $pendidikan, $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'lokasi_id', '', getNamaFromId($conn, 'lokasi', $lokasi_id, 'id', 'nama_lokasi'), $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'kelas_jabatan_id', '', getNamaFromId($conn, 'kelas_jabatan', $kelas_jabatan_id, 'id', 'nama_kelas'), $_SESSION['user_id']);
                    logChange($conn, $pegawai_id, 'alamat', '', $alamat, $_SESSION['user_id']);
                    
                    $_SESSION['success_message'] = "Data pegawai berhasil ditambahkan!";
                    header("Location: pegawai.php");
                    exit();
                } else {
                    $_SESSION['error_message'] = "Gagal menambahkan data pegawai: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pegawai - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            
            <main>
                <h1>Tambah Data Pegawai</h1>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Form Tambah Pegawai</h2>
                    </div>
                    <div class="card-body">
                        <form action="tambah_pegawai.php" method="post" id="form-pegawai">
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="nama">Nama Lengkap <span class="required">*</span></label>
                                    <input type="text" id="nama" name="nama" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="nip">NIP <span class="required">*</span></label>
                                    <input type="text" id="nip" name="nip" required>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="status">Status Kepegawaian <span class="required">*</span></label>
                                    <select id="status" name="status" required>
                                        <option value="">Pilih Status</option>
                                        <?php foreach ($statusOptions as $option): ?>
                                            <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="jenis_kelamin">Jenis Kelamin <span class="required">*</span></label>
                                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <?php foreach ($jenisKelaminOptions as $option): ?>
                                            <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group-row">
                                <div class="form-group col-md-6">
                                    <label for="pangkat_golongan_id">Pangkat/Golongan <span class="required">*</span></label>
                                    <select id="pangkat_golongan_id" name="pangkat_golongan_id" required>
                                        <option value="">Pilih Pangkat/Golongan</option>
                                        <?php foreach ($pangkatGolonganData as $pangkat): ?>
                                            <option value="<?php echo $pangkat['id']; ?>"><?php echo $pangkat['nama_pangkat']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="pendidikan">Pendidikan Terakhir <span class="required">*</span></label>
                                    <select id="pendidikan" name="pendidikan" required>
                                        <option value="">Pilih Pendidikan</option>
                                        <?php foreach ($pendidikanOptions as $option): ?>
                                            <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
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
                                            <option value="<?php echo $jabatan['id']; ?>"><?php echo $jabatan['nama_jabatan']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="unit_kerja_id">Unit Kerja <span class="required">*</span></label>
                                    <select id="unit_kerja_id" name="unit_kerja_id" required>
                                        <option value="">Pilih Unit Kerja</option>
                                        <?php foreach ($unitKerjaData as $unit): ?>
                                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['nama_unit']; ?></option>
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
                                            <option value="<?php echo $eselon['id']; ?>"><?php echo $eselon['nama_eselon']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="kelas_jabatan_id">Kelas Jabatan <span class="required">*</span></label>
                                    <select id="kelas_jabatan_id" name="kelas_jabatan_id" required>
                                        <option value="">Pilih Kelas Jabatan</option>
                                        <?php foreach ($kelasJabatanData as $kelas): ?>
                                            <option value="<?php echo $kelas['id']; ?>"><?php echo $kelas['nama_kelas']; ?></option>
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
                                            <option value="<?php echo $lokasi['id']; ?>"><?php echo $lokasi['nama_lokasi']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="alamat">Alamat <span class="required">*</span></label>
                                <textarea id="alamat" name="alamat" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="pegawai.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
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