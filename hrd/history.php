<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';

// Filter default - hari ini
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$pegawai_id = isset($_GET['pegawai_id']) ? intval($_GET['pegawai_id']) : 0;
$field_name = isset($_GET['field_name']) ? $_GET['field_name'] : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Query untuk mendapatkan daftar pegawai untuk filter
$pegawaiQuery = "SELECT id, nama FROM pegawai ORDER BY nama";
$pegawaiResult = $conn->query($pegawaiQuery);

// Query untuk mendapatkan daftar user untuk filter
$userQuery = "SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap";
$userResult = $conn->query($userQuery);

// Membangun query untuk mendapatkan history yang dikelompokkan
$historyQuery = "SELECT 
                    DATE(h.changed_at) as tanggal,
                    MAX(h.changed_at) as max_timestamp,
                    h.changed_by,
                    h.pegawai_id,
                    p.nama as nama_pegawai,
                    u.nama_lengkap as nama_user,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            CASE
                                WHEN h.field_name = 'pangkat_golongan_id' THEN 'Pangkat/Golongan'
                                WHEN h.field_name = 'unit_kerja_id' THEN 'Unit Kerja'
                                WHEN h.field_name = 'jabatan_id' THEN 'Jabatan'
                                WHEN h.field_name = 'eselon_id' THEN 'Eselon'
                                WHEN h.field_name = 'lokasi_id' THEN 'Lokasi'
                                WHEN h.field_name = 'kelas_jabatan_id' THEN 'Kelas Jabatan'
                                WHEN h.field_name = 'jenis_kelamin' THEN 'Jenis Kelamin'
                                ELSE h.field_name
                            END
                        ) SEPARATOR ', '
                    ) as fields_changed
                FROM history_pegawai h
                JOIN pegawai p ON h.pegawai_id = p.id
                JOIN users u ON h.changed_by = u.id
                WHERE 1=1";

$params = array();
$types = "";

if (!empty($start_date) && !empty($end_date)) {
    $historyQuery .= " AND DATE(h.changed_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($pegawai_id > 0) {
    $historyQuery .= " AND h.pegawai_id = ?";
    $params[] = $pegawai_id;
    $types .= "i";
}

if (!empty($field_name)) {
    $historyQuery .= " AND h.field_name = ?";
    $params[] = $field_name;
    $types .= "s";
}

if ($user_id > 0) {
    $historyQuery .= " AND h.changed_by = ?";
    $params[] = $user_id;
    $types .= "i";
}

$historyQuery .= " GROUP BY DATE(h.changed_at), h.pegawai_id, h.changed_by
                   ORDER BY max_timestamp DESC";

// Mempersiapkan statement
$stmt = $conn->prepare($historyQuery);

// Bind parameter jika ada parameter
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$historyResult = $stmt->get_result();

// Query untuk mendapatkan detail perubahan jika diperlukan
function getDetailPerubahan($conn, $tanggal, $pegawai_id, $user_id) {
    $query = "SELECT h.*, 
              CASE
                WHEN h.field_name = 'pangkat_golongan_id' THEN 'Pangkat/Golongan'
                WHEN h.field_name = 'unit_kerja_id' THEN 'Unit Kerja'
                WHEN h.field_name = 'jabatan_id' THEN 'Jabatan'
                WHEN h.field_name = 'eselon_id' THEN 'Eselon'
                WHEN h.field_name = 'lokasi_id' THEN 'Lokasi'
                WHEN h.field_name = 'kelas_jabatan_id' THEN 'Kelas Jabatan'
                WHEN h.field_name = 'jenis_kelamin' THEN 'Jenis Kelamin'
                ELSE h.field_name
              END as field_display_name
              FROM history_pegawai h
              WHERE DATE(h.changed_at) = ? 
              AND h.pegawai_id = ? 
              AND h.changed_by = ?
              ORDER BY h.changed_at";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $tanggal, $pegawai_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Perubahan - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .detail-row {
            display: none;
            background-color: #f9f9f9;
        }
        .detail-row td {
            padding: 10px 20px;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table th, .detail-table td {
            padding: 5px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .toggle-details {
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            
            <main>
                <h1>History Perubahan Data Pegawai</h1>
                
                <div class="filter-section">
                    <h2>Filter History</h2>
                    <form class="filter-form" method="GET" action="history.php">
                        <div class="filter-group">
                            <label for="start_date">Dari Tanggal</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="end_date">Sampai Tanggal</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id">
                                <option value="0">-- Semua Pegawai --</option>
                                <?php
                                if ($pegawaiResult->num_rows > 0) {
                                    while ($row = $pegawaiResult->fetch_assoc()) {
                                        $selected = ($pegawai_id == $row['id']) ? 'selected' : '';
                                        echo "<option value='" . $row['id'] . "' $selected>" . $row['nama'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="field_name">Field yang Diubah</label>
                            <select id="field_name" name="field_name">
                                <option value="">-- Semua Field --</option>
                                <option value="nip" <?php echo ($field_name == 'nip') ? 'selected' : ''; ?>>NIP</option>
                                <option value="nama" <?php echo ($field_name == 'nama') ? 'selected' : ''; ?>>Nama</option>
                                <option value="pangkat_golongan_id" <?php echo ($field_name == 'pangkat_golongan_id') ? 'selected' : ''; ?>>Pangkat/Golongan</option>
                                <option value="unit_kerja_id" <?php echo ($field_name == 'unit_kerja_id') ? 'selected' : ''; ?>>Unit Kerja</option>
                                <option value="status" <?php echo ($field_name == 'status') ? 'selected' : ''; ?>>Status</option>
                                <option value="jabatan_id" <?php echo ($field_name == 'jabatan_id') ? 'selected' : ''; ?>>Jabatan</option>
                                <option value="eselon_id" <?php echo ($field_name == 'eselon_id') ? 'selected' : ''; ?>>Eselon</option>
                                <option value="jenis_kelamin" <?php echo ($field_name == 'jenis_kelamin') ? 'selected' : ''; ?>>Jenis Kelamin</option>
                                <option value="pendidikan" <?php echo ($field_name == 'pendidikan') ? 'selected' : ''; ?>>Pendidikan</option>
                                <option value="lokasi_id" <?php echo ($field_name == 'lokasi_id') ? 'selected' : ''; ?>>Lokasi</option>
                                <option value="kelas_jabatan_id" <?php echo ($field_name == 'kelas_jabatan_id') ? 'selected' : ''; ?>>Kelas Jabatan</option>
                                <option value="alamat" <?php echo ($field_name == 'alamat') ? 'selected' : ''; ?>>Alamat</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="user_id">Diubah Oleh</label>
                            <select id="user_id" name="user_id">
                                <option value="0">-- Semua User --</option>
                                <?php
                                if ($userResult->num_rows > 0) {
                                    while ($row = $userResult->fetch_assoc()) {
                                        $selected = ($user_id == $row['id']) ? 'selected' : '';
                                        echo "<option value='" . $row['id'] . "' $selected>" . $row['nama_lengkap'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="filter-buttons">
                            <button type="submit" class="btn">Filter</button>
                            <a href="history.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
                
                <div class="data-table-container">
                    <h2>Daftar History Perubahan</h2>
                    
                    <?php if ($historyResult->num_rows == 0): ?>
                        <div class="alert alert-info">
                            Tidak ada data history perubahan yang sesuai dengan filter yang dipilih.
                        </div>
                    <?php else: ?>
                        <div class="activity-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal & Waktu</th>
                                        <th>Nama Pegawai</th>
                                        <th>Field yang Diubah</th>
                                        <th>Diubah Oleh</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = $historyResult->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?php echo $no; ?></td>
                                        <td><?php echo date("d-m-Y H:i", strtotime($row['max_timestamp'])); ?></td>
                                        <td><?php echo $row['nama_pegawai']; ?></td>
                                        <td><?php echo $row['fields_changed']; ?></td>
                                        <td><?php echo $row['nama_user']; ?></td>
                                        <td>
                                            <span class="toggle-details" onclick="toggleDetails('details-<?php echo $no; ?>')">
                                                Lihat Detail
                                            </span>
                                        </td>
                                    </tr>
                                    <tr id="details-<?php echo $no; ?>" class="detail-row">
                                        <td colspan="6">
                                            <table class="detail-table">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Nilai Lama</th>
                                                        <th>Nilai Baru</th>
                                                        <th>Waktu</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Get details for this group
                                                    $details = getDetailPerubahan($conn, $row['tanggal'], $row['pegawai_id'], $row['changed_by']);
                                                    while ($detail = $details->fetch_assoc()) {
                                                        // Format nilai berdasarkan jenis field
                                                        $old_value = $detail['old_value'];
                                                        $new_value = $detail['new_value'];
                                                        
                                                        // Konversi ID ke nama jika diperlukan
                                                        switch($detail['field_name']) {
                                                            case 'pangkat_golongan_id':
                                                                if (is_numeric($old_value)) {
                                                                    $old_value = getNamaFromId($conn, 'pangkat_golongan', $old_value, 'id', 'nama_pangkat');
                                                                }
                                                                if (is_numeric($new_value)) {
                                                                    $new_value = getNamaFromId($conn, 'pangkat_golongan', $new_value, 'id', 'nama_pangkat');
                                                                }
                                                                break;
                                                            case 'unit_kerja_id':
                                                                if (is_numeric($old_value)) {
                                                                    $old_value = getNamaFromId($conn, 'unit_kerja', $old_value, 'id', 'nama_unit');
                                                                }
                                                                if (is_numeric($new_value)) {
                                                                    $new_value = getNamaFromId($conn, 'unit_kerja', $new_value, 'id', 'nama_unit');
                                                                }
                                                                break;
                                                            case 'jabatan_id':
                                                                if (is_numeric($old_value)) {
                                                                    $old_value = getNamaFromId($conn, 'jabatan', $old_value, 'id', 'nama_jabatan');
                                                                }
                                                                if (is_numeric($new_value)) {
                                                                    $new_value = getNamaFromId($conn, 'jabatan', $new_value, 'id', 'nama_jabatan');
                                                                }
                                                                break;
                                                            case 'eselon_id':
                                                                if (is_numeric($old_value)) {
                                                                    $old_value = getNamaFromId($conn, 'eselon', $old_value, 'id', 'nama_eselon');
                                                                }
                                                                if (is_numeric($new_value)) {
                                                                    $new_value = getNamaFromId($conn, 'eselon', $new_value, 'id', 'nama_eselon');
                                                                }
                                                                break;
                                                            case 'lokasi_id':
                                                                if (is_numeric($old_value)) {
                                                                    $old_value = getNamaFromId($conn, 'lokasi', $old_value, 'id', 'nama_lokasi');
                                                                }
                                                                if (is_numeric($new_value)) {
                                                                    $new_value = getNamaFromId($conn, 'lokasi', $new_value, 'id', 'nama_lokasi');
                                                                }
                                                                break;
                                                            case 'kelas_jabatan_id':
                                                                if (is_numeric($old_value)) {
                                                                    $old_value = getNamaFromId($conn, 'kelas_jabatan', $old_value, 'id', 'nama_kelas');
                                                                }
                                                                if (is_numeric($new_value)) {
                                                                    $new_value = getNamaFromId($conn, 'kelas_jabatan', $new_value, 'id', 'nama_kelas');
                                                                }
                                                                break;
                                                            case 'jenis_kelamin':
                                                                $old_value = ($old_value == 'L') ? 'Laki-laki' : 'Perempuan';
                                                                $new_value = ($new_value == 'L') ? 'Laki-laki' : 'Perempuan';
                                                                break;
                                                        }
                                                        
                                                        echo "<tr>";
                                                        echo "<td>" . $detail['field_display_name'] . "</td>";
                                                        echo "<td class='history-old'>" . ($old_value ?: '-') . "</td>";
                                                        echo "<td class='history-new'>" . ($new_value ?: '-') . "</td>";
                                                        echo "<td>" . date("H:i:s", strtotime($detail['changed_at'])) . "</td>";
                                                        echo "</tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <?php 
                                    $no++;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <?php include 'templates/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
    <script>
        function toggleDetails(id) {
            var detailRow = document.getElementById(id);
            if (detailRow.style.display === "table-row") {
                detailRow.style.display = "none";
            } else {
                detailRow.style.display = "table-row";
            }
        }
    </script>
</body>
</html>