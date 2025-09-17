<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/connection.php';

// Filter data
$where = "1=1";
$filterParams = [];

if (isset($_GET['nip']) && !empty($_GET['nip'])) {
    $where .= " AND p.nip LIKE ?";
    $filterParams[] = "%" . clean($conn, $_GET['nip']) . "%";
}

if (isset($_GET['nama']) && !empty($_GET['nama'])) {
    $where .= " AND p.nama LIKE ?";
    $filterParams[] = "%" . clean($conn, $_GET['nama']) . "%";
}

if (isset($_GET['pangkat_golongan']) && !empty($_GET['pangkat_golongan'])) {
    $where .= " AND p.pangkat_golongan_id = ?";
    $filterParams[] = (int)$_GET['pangkat_golongan'];
}

if (isset($_GET['unit_kerja']) && !empty($_GET['unit_kerja'])) {
    $where .= " AND p.unit_kerja_id = ?";
    $filterParams[] = (int)$_GET['unit_kerja'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where .= " AND p.status = ?";
    $filterParams[] = clean($conn, $_GET['status']);
}

if (isset($_GET['jabatan']) && !empty($_GET['jabatan'])) {
    $where .= " AND p.jabatan_id = ?";
    $filterParams[] = (int)$_GET['jabatan'];
}

if (isset($_GET['jenis_kelamin']) && !empty($_GET['jenis_kelamin'])) {
    $where .= " AND p.jenis_kelamin = ?";
    $filterParams[] = clean($conn, $_GET['jenis_kelamin']);
}

if (isset($_GET['lokasi']) && !empty($_GET['lokasi'])) {
    $where .= " AND p.lokasi_id = ?";
    $filterParams[] = (int)$_GET['lokasi'];
}

// Mengambil data filter untuk dropdown
$pangkatGolongan = getDropdownData($conn, 'pangkat_golongan', 'id', 'nama_pangkat');
$unitKerja = getDropdownData($conn, 'unit_kerja', 'id', 'nama_unit');
$jabatan = getDropdownData($conn, 'jabatan', 'id', 'nama_jabatan');
$eselon = getDropdownData($conn, 'eselon', 'id', 'nama_eselon');
$lokasi = getDropdownData($conn, 'lokasi', 'id', 'nama_lokasi');
$kelasJabatan = getDropdownData($conn, 'kelas_jabatan', 'id', 'nama_kelas');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Menghitung total data untuk pagination
$countQuery = "SELECT COUNT(*) as total FROM pegawai p WHERE $where";
$stmtCount = $conn->prepare($countQuery);

if (!empty($filterParams)) {
    $types = str_repeat('s', count($filterParams));
    $stmtCount->bind_param($types, ...$filterParams);
}

$stmtCount->execute();
$totalData = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);

// Query untuk mengambil data pegawai dengan join
$query = "SELECT p.*, 
            pg.nama_pangkat, 
            uk.nama_unit, 
            j.nama_jabatan, 
            e.nama_eselon,
            l.nama_lokasi,
            kj.nama_kelas
          FROM pegawai p
          LEFT JOIN pangkat_golongan pg ON p.pangkat_golongan_id = pg.id
          LEFT JOIN unit_kerja uk ON p.unit_kerja_id = uk.id
          LEFT JOIN jabatan j ON p.jabatan_id = j.id
          LEFT JOIN eselon e ON p.eselon_id = e.id
          LEFT JOIN lokasi l ON p.lokasi_id = l.id
          LEFT JOIN kelas_jabatan kj ON p.kelas_jabatan_id = kj.id
          WHERE $where
          ORDER BY p.nama
          LIMIT ?, ?";

$stmt = $conn->prepare($query);

// Binding parameter filter dan pagination
if (!empty($filterParams)) {
    $params = array_merge($filterParams, [$offset, $limit]);
    $types = str_repeat('s', count($filterParams)) . 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pegawai - Sistem Data Pegawai HRD</title>
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
                    <h1>Data Pegawai</h1>
                    <div>
                        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pegawai</a>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Filter Data</h3>
                    <form action="" method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nip">NIP</label>
                                <input type="text" id="nip" name="nip" value="<?php echo isset($_GET['nip']) ? htmlspecialchars($_GET['nip']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="nama">Nama</label>
                                <input type="text" id="nama" name="nama" value="<?php echo isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="pangkat_golongan">Pangkat & Golongan</label>
                                <select id="pangkat_golongan" name="pangkat_golongan">
                                    <option value="">Semua</option>
                                    <?php foreach ($pangkatGolongan as $pg): ?>
                                        <option value="<?php echo $pg['id']; ?>" <?php echo (isset($_GET['pangkat_golongan']) && $_GET['pangkat_golongan'] == $pg['id']) ? 'selected' : ''; ?>>
                                            <?php echo $pg['nama_pangkat']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="unit_kerja">Unit Kerja</label>
                                <select id="unit_kerja" name="unit_kerja">
                                    <option value="">Semua</option>
                                    <?php foreach ($unitKerja as $uk): ?>
                                        <option value="<?php echo $uk['id']; ?>" <?php echo (isset($_GET['unit_kerja']) && $_GET['unit_kerja'] == $uk['id']) ? 'selected' : ''; ?>>
                                            <?php echo $uk['nama_unit']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Semua</option>
                                    <option value="PNS" <?php echo (isset($_GET['status']) && $_GET['status'] == 'PNS') ? 'selected' : ''; ?>>PNS</option>
                                    <option value="PPPK" <?php echo (isset($_GET['status']) && $_GET['status'] == 'PPPK') ? 'selected' : ''; ?>>PPPK</option>
                                    <option value="TENAGA KONTRAK" <?php echo (isset($_GET['status']) && $_GET['status'] == 'TENAGA KONTRAK') ? 'selected' : ''; ?>>TENAGA KONTRAK</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="jabatan">Jabatan</label>
                                <select id="jabatan" name="jabatan">
                                    <option value="">Semua</option>
                                    <?php foreach ($jabatan as $jab): ?>
                                        <option value="<?php echo $jab['id']; ?>" <?php echo (isset($_GET['jabatan']) && $_GET['jabatan'] == $jab['id']) ? 'selected' : ''; ?>>
                                            <?php echo $jab['nama_jabatan']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="jenis_kelamin">Jenis Kelamin</label>
                                <select id="jenis_kelamin" name="jenis_kelamin">
                                    <option value="">Semua</option>
                                    <option value="L" <?php echo (isset($_GET['jenis_kelamin']) && $_GET['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="P" <?php echo (isset($_GET['jenis_kelamin']) && $_GET['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="lokasi">Lokasi</label>
                                <select id="lokasi" name="lokasi">
                                    <option value="">Semua</option>
                                    <?php foreach ($lokasi as $lok): ?>
                                        <option value="<?php echo $lok['id']; ?>" <?php echo (isset($_GET['lokasi']) && $_GET['lokasi'] == $lok['id']) ? 'selected' : ''; ?>>
                                            <?php echo $lok['nama_lokasi']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <a href="list.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
                            <button type="button" class="btn btn-success" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Export</button>
                        </div>
                    </form>
                </div>
                
                <div class="data-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Pangkat/Gol</th>
                                <th>Unit Kerja</th>
                                <th>Status</th>
                                <th>Jabatan</th>
                                <th>Jenis Kelamin</th>
                                <th>Lokasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result->num_rows > 0) {
                                $no = ($page - 1) * $limit + 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nip']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_pangkat']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_unit']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_jabatan']) . "</td>";
                                    echo "<td>" . ($row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_lokasi']) . "</td>";
                                    echo "<td class='action-buttons'>";
                                    echo "<a href='view.php?id=" . $row['id'] . "' class='btn-sm btn-info' title='Lihat Detail'><i class='fas fa-eye'></i></a> ";
                                    echo "<a href='edit.php?id=" . $row['id'] . "' class='btn-sm btn-warning' title='Edit'><i class='fas fa-edit'></i></a> ";
                                    echo "<a href='history.php?id=" . $row['id'] . "' class='btn-sm btn-primary' title='Riwayat Perubahan'><i class='fas fa-history'></i></a> ";
                                    echo "<a href='javascript:void(0)' onclick='confirmDelete(" . $row['id'] . ")' class='btn-sm btn-danger' title='Hapus'><i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>Tidak ada data pegawai</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($start + 4, $totalPages);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <?php include '../templates/footer.php'; ?>
        </div>
    </div>
    
    <script>
    function confirmDelete(id) {
        if (confirm("Apakah Anda yakin ingin menghapus data pegawai ini?")) {
            window.location.href = "delete.php?id=" + id;
        }
    }
    
    function exportToExcel() {
        // Mendapatkan semua parameter filter
        const queryString = window.location.search;
        window.location.href = "export_excel.php" + queryString;
    }
    </script>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>