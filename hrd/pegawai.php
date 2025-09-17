<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';

// Variabel untuk paging
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Query dasar untuk mendapatkan total data
$countQuery = "SELECT COUNT(*) as total FROM pegawai";

// Query dasar untuk mendapatkan data pegawai
$query = "SELECT p.*, j.nama_jabatan, u.nama_unit 
          FROM pegawai p
          LEFT JOIN jabatan j ON p.jabatan_id = j.id
          LEFT JOIN unit_kerja u ON p.unit_kerja_id = u.id";

// Inisialisasi array filter
$whereConditions = [];
$params = [];
$paramTypes = "";

// Proses filter pencarian jika ada
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean($conn, $_GET['search']);
    $whereConditions[] = "(p.nama LIKE ? OR p.nip LIKE ? OR p.status LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $paramTypes .= "sss";
}

// Filter berdasarkan status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = clean($conn, $_GET['status']);
    $whereConditions[] = "p.status = ?";
    $params[] = $status;
    $paramTypes .= "s";
}

// Filter berdasarkan jabatan
if (isset($_GET['jabatan']) && !empty($_GET['jabatan'])) {
    $jabatan = (int)$_GET['jabatan'];
    $whereConditions[] = "p.jabatan_id = ?";
    $params[] = $jabatan;
    $paramTypes .= "i";
}

// Filter berdasarkan unit kerja
if (isset($_GET['unit_kerja']) && !empty($_GET['unit_kerja'])) {
    $unit_kerja = (int)$_GET['unit_kerja'];
    $whereConditions[] = "p.unit_kerja_id = ?";
    $params[] = $unit_kerja;
    $paramTypes .= "i";
}

// Filter berdasarkan pendidikan
if (isset($_GET['pendidikan']) && !empty($_GET['pendidikan'])) {
    $pendidikan = clean($conn, $_GET['pendidikan']);
    $whereConditions[] = "p.pendidikan = ?";
    $params[] = $pendidikan;
    $paramTypes .= "s";
}

// Filter dinamis jika ada
if (isset($_GET['filter_field']) && is_array($_GET['filter_field'])) {
    for ($i = 0; $i < count($_GET['filter_field']); $i++) {
        if (!empty($_GET['filter_field'][$i]) && isset($_GET['filter_value'][$i]) && !empty($_GET['filter_value'][$i])) {
            $field = clean($conn, $_GET['filter_field'][$i]);
            $operator = isset($_GET['filter_operator'][$i]) ? clean($conn, $_GET['filter_operator'][$i]) : 'contains';
            $value = clean($conn, $_GET['filter_value'][$i]);
            
            switch ($operator) {
                case 'equals':
                    $whereConditions[] = "p.$field = ?";
                    $params[] = $value;
                    $paramTypes .= "s";
                    break;
                case 'starts':
                    $whereConditions[] = "p.$field LIKE ?";
                    $params[] = "$value%";
                    $paramTypes .= "s";
                    break;
                case 'ends':
                    $whereConditions[] = "p.$field LIKE ?";
                    $params[] = "%$value";
                    $paramTypes .= "s";
                    break;
                case 'contains':
                default:
                    $whereConditions[] = "p.$field LIKE ?";
                    $params[] = "%$value%";
                    $paramTypes .= "s";
                    break;
            }
        }
    }
}

// Gabungkan kondisi WHERE jika ada
if (count($whereConditions) > 0) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $countQuery .= $whereClause;
    $query .= $whereClause;
}

// Tambahkan ORDER BY
$query .= " ORDER BY p.nama ASC";

// Tambahkan LIMIT untuk paging
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$paramTypes .= "ii";

// Eksekusi query untuk mendapatkan total data
$countStmt = $conn->prepare($countQuery);
if (count($params) > 0) {
    // Hapus parameter untuk LIMIT
    $countParamTypes = substr($paramTypes, 0, -2);
    $countParams = array_slice($params, 0, -2);
    
    if (!empty($countParamTypes)) {
        $countBindParams = array(&$countStmt, &$countParamTypes);
        foreach ($countParams as $key => $value) {
            $countBindParams[] = &$countParams[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', $countBindParams);
    }
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $per_page);
$countStmt->close();

// Eksekusi query untuk mendapatkan data pegawai
$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $bindParams = array(&$stmt, &$paramTypes);
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
}
$stmt->execute();
$result = $stmt->get_result();

// Ambil data untuk dropdown filter
$jabatanData = getDropdownData($conn, 'jabatan', 'id', 'nama_jabatan');
$unitKerjaData = getDropdownData($conn, 'unit_kerja', 'id', 'nama_unit');

// Status pegawai (nilai statis)
$statusOptions = ['PNS', 'PPPK', 'TENAGA KONTRAK'];

// Pendidikan (nilai statis)
$pendidikanOptions = ['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

// Hapus data pegawai jika ada request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Cek apakah user punya hak akses untuk menghapus
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hrd') {
        // Hapus data pegawai
        $deleteQuery = "DELETE FROM pegawai WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $deleteId);
        
        if ($deleteStmt->execute()) {
            // Hapus juga history perubahan
            $deleteHistoryQuery = "DELETE FROM history_pegawai WHERE pegawai_id = ?";
            $deleteHistoryStmt = $conn->prepare($deleteHistoryQuery);
            $deleteHistoryStmt->bind_param("i", $deleteId);
            $deleteHistoryStmt->execute();
            
            // Set pesan sukses
            $_SESSION['success_message'] = "Data pegawai berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data pegawai.";
        }
        
        $deleteStmt->close();
        
        // Redirect kembali ke halaman pegawai
        header("Location: pegawai.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Anda tidak memiliki hak akses untuk menghapus data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pegawai - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            
            <main>
                <h1>Data Pegawai</h1>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="filter-section">
                    <h2>Filter Data</h2>
                    <form action="pegawai.php" method="get" id="filter-form">
                        <div class="filter-form">
                            <div class="filter-group">
                                <label for="search">Pencarian</label>
                                <input type="text" id="search" name="search" placeholder="Nama / NIP / Status" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <?php foreach ($statusOptions as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="jabatan">Jabatan</label>
                                <select id="jabatan" name="jabatan">
                                    <option value="">Semua Jabatan</option>
                                    <?php foreach ($jabatanData as $jabatan): ?>
                                        <option value="<?php echo $jabatan['id']; ?>" <?php echo (isset($_GET['jabatan']) && $_GET['jabatan'] == $jabatan['id']) ? 'selected' : ''; ?>><?php echo $jabatan['nama_jabatan']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="unit_kerja">Unit Kerja</label>
                                <select id="unit_kerja" name="unit_kerja">
                                    <option value="">Semua Unit Kerja</option>
                                    <?php foreach ($unitKerjaData as $unit): ?>
                                        <option value="<?php echo $unit['id']; ?>" <?php echo (isset($_GET['unit_kerja']) && $_GET['unit_kerja'] == $unit['id']) ? 'selected' : ''; ?>><?php echo $unit['nama_unit']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="pendidikan">Pendidikan</label>
                                <select id="pendidikan" name="pendidikan">
                                    <option value="">Semua Pendidikan</option>
                                    <?php foreach ($pendidikanOptions as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo (isset($_GET['pendidikan']) && $_GET['pendidikan'] == $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-buttons">
                                <button type="submit" class="btn">Filter</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                                <button type="button" id="add-filter" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Tambah Filter
                                </button>
                            </div>
                        </div>
                        
                        <!-- Untuk filter dinamis -->
                        <div id="dynamic-filters">
                            <?php 
                            if (isset($_GET['filter_field']) && is_array($_GET['filter_field'])) {
                                for ($i = 0; $i < count($_GET['filter_field']); $i++) {
                                    if (!empty($_GET['filter_field'][$i]) && isset($_GET['filter_value'][$i]) && !empty($_GET['filter_value'][$i])) {
                                        $field = htmlspecialchars($_GET['filter_field'][$i]);
                                        $operator = isset($_GET['filter_operator'][$i]) ? htmlspecialchars($_GET['filter_operator'][$i]) : 'contains';
                                        $value = htmlspecialchars($_GET['filter_value'][$i]);
                                        echo '<div class="filter-row">';
                                        echo '<div class="filter-group">';
                                        echo '<select name="filter_field[]" class="filter-field">';
                                        echo '<option value="">Pilih Field</option>';
                                        echo '<option value="nama"' . ($field == 'nama' ? ' selected' : '') . '>Nama</option>';
                                        echo '<option value="nip"' . ($field == 'nip' ? ' selected' : '') . '>NIP</option>';
                                        echo '<option value="status"' . ($field == 'status' ? ' selected' : '') . '>Status</option>';
                                        echo '<option value="jabatan"' . ($field == 'jabatan' ? ' selected' : '') . '>Jabatan</option>';
                                        echo '<option value="unit_kerja"' . ($field == 'unit_kerja' ? ' selected' : '') . '>Unit Kerja</option>';
                                        echo '<option value="pendidikan"' . ($field == 'pendidikan' ? ' selected' : '') . '>Pendidikan</option>';
                                        echo '</select>';
                                        echo '</div>';
                                        echo '<div class="filter-group">';
                                        echo '<select name="filter_operator[]" class="filter-operator">';
                                        echo '<option value="contains"' . ($operator == 'contains' ? ' selected' : '') . '>Mengandung</option>';
                                        echo '<option value="equals"' . ($operator == 'equals' ? ' selected' : '') . '>Sama Dengan</option>';
                                        echo '<option value="starts"' . ($operator == 'starts' ? ' selected' : '') . '>Dimulai Dengan</option>';
                                        echo '<option value="ends"' . ($operator == 'ends' ? ' selected' : '') . '>Diakhiri Dengan</option>';
                                        echo '</select>';
                                        echo '</div>';
                                        echo '<div class="filter-group">';
                                        echo '<input type="text" name="filter_value[]" class="filter-value" placeholder="Nilai" value="' . $value . '">';
                                        echo '</div>';
                                        echo '<div class="filter-group filter-action">';
                                        echo '<button type="button" class="btn btn-danger btn-sm remove-filter"><i class="fas fa-times"></i></button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </form>
                </div>
                
                <div class="data-table-container">
                    <div class="action-buttons">
                        <a href="tambah_pegawai.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Tambah Pegawai
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>NIP</th>
                                    <th>Status</th>
                                    <th>Jabatan</th>
                                    <th>Unit Kerja</th>
                                    <th>Pendidikan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result->num_rows > 0) {
                                    $no = $offset + 1;
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nip']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_jabatan']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_unit']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['pendidikan']) . "</td>";
                                        echo "<td class='actions'>";
                                        echo "<a href='view_pegawai.php?id=" . $row['id'] . "' class='btn btn-sm' title='Lihat'><i class='fas fa-eye'></i></a>";
                                        echo "<a href='edit_pegawai.php?id=" . $row['id'] . "' class='btn btn-sm btn-secondary' title='Edit'><i class='fas fa-edit'></i></a>";
                                        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'hrd') {
                                            echo "<a href='pegawai.php?delete=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete' title='Hapus'><i class='fas fa-trash'></i></a>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>Tidak ada data pegawai</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['jabatan']) ? '&jabatan='.urlencode($_GET['jabatan']) : ''; ?><?php echo isset($_GET['unit_kerja']) ? '&unit_kerja='.urlencode($_GET['unit_kerja']) : ''; ?><?php echo isset($_GET['pendidikan']) ? '&pendidikan='.urlencode($_GET['pendidikan']) : ''; ?>"><<</a>
                            <a href="?page=<?php echo $page-1; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['jabatan']) ? '&jabatan='.urlencode($_GET['jabatan']) : ''; ?><?php echo isset($_GET['unit_kerja']) ? '&unit_kerja='.urlencode($_GET['unit_kerja']) : ''; ?><?php echo isset($_GET['pendidikan']) ? '&pendidikan='.urlencode($_GET['pendidikan']) : ''; ?>"><</a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<a href="?page=' . $i . 
                                (isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '') . 
                                (isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '') . 
                                (isset($_GET['jabatan']) ? '&jabatan='.urlencode($_GET['jabatan']) : '') . 
                                (isset($_GET['unit_kerja']) ? '&unit_kerja='.urlencode($_GET['unit_kerja']) : '') . 
                                (isset($_GET['pendidikan']) ? '&pendidikan='.urlencode($_GET['pendidikan']) : '') . 
                                '"' . ($i == $page ? ' class="active"' : '') . '>' . $i . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['jabatan']) ? '&jabatan='.urlencode($_GET['jabatan']) : ''; ?><?php echo isset($_GET['unit_kerja']) ? '&unit_kerja='.urlencode($_GET['unit_kerja']) : ''; ?><?php echo isset($_GET['pendidikan']) ? '&pendidikan='.urlencode($_GET['pendidikan']) : ''; ?>">></a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['jabatan']) ? '&jabatan='.urlencode($_GET['jabatan']) : ''; ?><?php echo isset($_GET['unit_kerja']) ? '&unit_kerja='.urlencode($_GET['unit_kerja']) : ''; ?><?php echo isset($_GET['pendidikan']) ? '&pendidikan='.urlencode($_GET['pendidikan']) : ''; ?>">>></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <?php include 'templates/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>