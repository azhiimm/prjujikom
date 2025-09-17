<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';

// Get available date ranges from pegawai table
$sql_date_range = "SELECT MIN(created_at) as min_date, MAX(created_at) as max_date FROM pegawai";
$date_range_result = $conn->query($sql_date_range);
$date_range = $date_range_result->fetch_assoc();

$min_year = date('Y', strtotime($date_range['min_date']));
$max_year = date('Y', strtotime($date_range['max_date']));

// Default values - use current month/year if they're within the data range, otherwise use the max date from data
$current_year = date('Y');
$current_month = date('n');

// If current date is outside data range, default to the most recent data month/year
if ($current_year > $max_year || ($current_year == $max_year && $current_month > date('n', strtotime($date_range['max_date'])))) {
    $default_month = date('n', strtotime($date_range['max_date']));
    $default_year = date('Y', strtotime($date_range['max_date']));
} else {
    $default_month = $current_month;
    $default_year = $current_year;
}

// Set month and year from GET parameters or use defaults
$month = isset($_GET['month']) ? intval($_GET['month']) : $default_month;
$year = isset($_GET['year']) ? intval($_GET['year']) : $default_year;
$selected_fields = isset($_GET['fields']) ? $_GET['fields'] : array('nip', 'nama', 'status', 'jabatan_id', 'unit_kerja_id', 'pangkat_golongan_id');

// Get report data if already exists
$report_id = null;
$sql_check_report = "SELECT id FROM report_bulanan WHERE bulan = $month AND tahun = $year";
$report_result = $conn->query($sql_check_report);

if ($report_result->num_rows > 0) {
    $report_row = $report_result->fetch_assoc();
    $report_id = $report_row['id'];
}

// Generate report if requested
if (isset($_POST['generate_report'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    
    // Check if report for this month already exists
    $sql_check = "SELECT id FROM report_bulanan WHERE bulan = $month AND tahun = $year";
    $check_result = $conn->query($sql_check);
    
    if ($check_result->num_rows > 0) {
        // Update existing report
        $report_row = $check_result->fetch_assoc();
        $report_id = $report_row['id'];
        
        // Delete old report details
        $sql_delete = "DELETE FROM report_detail WHERE report_id = $report_id";
        $conn->query($sql_delete);
    } else {
        // Create new report
        $user_id = $_SESSION['user_id'];
        $sql_insert = "INSERT INTO report_bulanan (bulan, tahun, generated_by) VALUES ($month, $year, $user_id)";
        $conn->query($sql_insert);
        $report_id = $conn->insert_id;
    }
    
    // Define time period for the selected month
    $start_date = date("Y-m-01 00:00:00", strtotime("$year-$month-01"));
    $end_date = date("Y-m-t 23:59:59", strtotime("$year-$month-01"));
    
    // Get all employees first - we'll check both creation and updates later
    $sql_employees = "SELECT * FROM pegawai";
    $employees_result = $conn->query($sql_employees);
    
    while ($employee = $employees_result->fetch_assoc()) {
        $pegawai_id = $employee['id'];
        $created_in_period = false;
        $updated_in_period = false;
        $has_changes = 0;
        $changes_detail = null;
        
        // Check if employee was created in the selected month/year
        $created_at = strtotime($employee['created_at']);
        $period_start = strtotime($start_date);
        $period_end = strtotime($end_date);
        
        if ($created_at >= $period_start && $created_at <= $period_end) {
            $created_in_period = true;
        }
        
        // Check if employee was updated in the selected month/year
        $updated_at = strtotime($employee['updated_at']);
        if ($updated_at >= $period_start && $updated_at <= $period_end && $updated_at != $created_at) {
            $updated_in_period = true;
        }
        
        // Only include employees created or updated in this period
        if ($created_in_period || $updated_in_period) {
            // Check if employee has changes in the selected month/year
            $sql_changes = "SELECT * FROM history_pegawai 
                          WHERE pegawai_id = $pegawai_id 
                          AND changed_at BETWEEN '$start_date' AND '$end_date'
                          ORDER BY changed_at ASC";
            $changes_result = $conn->query($sql_changes);
            
            if ($changes_result->num_rows > 0) {
                $has_changes = 1;
                $changes = array();
                
                while ($change = $changes_result->fetch_assoc()) {
                    $changes[] = array(
                        'field' => $change['field_name'],
                        'old' => $change['old_value'],
                        'new' => $change['new_value'],
                        'date' => $change['changed_at']
                    );
                }
                
                $changes_detail = json_encode($changes);
            }
            
            // Insert to report_detail
            $sql_detail = "INSERT INTO report_detail (report_id, pegawai_id, has_changes, changes_detail) 
                          VALUES ($report_id, $pegawai_id, $has_changes, " . ($changes_detail ? "'$changes_detail'" : "NULL") . ")";
            $conn->query($sql_detail);
        }
    }
    
    // Redirect to show the report
    header("Location: report.php?month=$month&year=$year");
    exit();
}

// Export to Excel if requested
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_pegawai_' . $month . '_' . $year . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Include a simple HTML table for Excel
    echo '<table border="1">';
    echo '<tr><th colspan="' . (count($selected_fields) + 1) . '">Laporan Data Pegawai - ' . date("F Y", strtotime("$year-$month-01")) . '</th></tr>';
    echo '<tr><th>No</th>';
    
    // Get field headers
    foreach ($selected_fields as $field) {
        $header_text = $field;
        
        // Map field to readable name
        switch ($field) {
            case 'nip': $header_text = "NIP"; break;
            case 'nama': $header_text = "Nama"; break;
            case 'status': $header_text = "Status"; break;
            case 'jenis_kelamin': $header_text = "Jenis Kelamin"; break;
            case 'pendidikan': $header_text = "Pendidikan"; break;
            case 'alamat': $header_text = "Alamat"; break;
            case 'jabatan_id': $header_text = "Jabatan"; break;
            case 'unit_kerja_id': $header_text = "Unit Kerja"; break;
            case 'pangkat_golongan_id': $header_text = "Pangkat/Golongan"; break;
            case 'eselon_id': $header_text = "Eselon"; break;
            case 'lokasi_id': $header_text = "Lokasi"; break;
            case 'kelas_jabatan_id': $header_text = "Kelas Jabatan"; break;
        }
        
        echo '<th>' . $header_text . '</th>';
    }
    echo '</tr>';
    
    // Define time period for filtering data
    $start_date = date("Y-m-01 00:00:00", strtotime("$year-$month-01"));
    $end_date = date("Y-m-t 23:59:59", strtotime("$year-$month-01"));
    
    // Get all pegawai data and join with report details if available
    $sql = "SELECT p.*, rd.has_changes, rd.changes_detail FROM pegawai p
            LEFT JOIN report_detail rd ON p.id = rd.pegawai_id AND rd.report_id = " . ($report_id ? $report_id : 0) . "
            WHERE (p.created_at BETWEEN '$start_date' AND '$end_date') OR 
                  (p.updated_at BETWEEN '$start_date' AND '$end_date')
            ORDER BY p.nama ASC";
    $result = $conn->query($sql);
    
    $no = 1;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            
            foreach ($selected_fields as $field) {
                $value = $row[$field];
                $has_changed = false;
                
                // Check if this field has changes during the report period
                if ($row['has_changes'] && $row['changes_detail']) {
                    $changes = json_decode($row['changes_detail'], true);
                    if ($changes) {
                        foreach ($changes as $change) {
                            if ($change['field'] == $field) {
                                $has_changed = true;
                                break;
                            }
                        }
                    }
                }
                
                // Replace IDs with actual names for related fields
                if ($field == 'jabatan_id' && $value) {
                    $sql_jabatan = "SELECT nama_jabatan FROM jabatan WHERE id = $value";
                    $jabatan_result = $conn->query($sql_jabatan);
                    if ($jabatan_result->num_rows > 0) {
                        $jabatan_row = $jabatan_result->fetch_assoc();
                        $value = $jabatan_row['nama_jabatan'];
                    }
                } elseif ($field == 'unit_kerja_id' && $value) {
                    $sql_unit = "SELECT nama_unit FROM unit_kerja WHERE id = $value";
                    $unit_result = $conn->query($sql_unit);
                    if ($unit_result->num_rows > 0) {
                        $unit_row = $unit_result->fetch_assoc();
                        $value = $unit_row['nama_unit'];
                    }
                } elseif ($field == 'pangkat_golongan_id' && $value) {
                    $sql_pangkat = "SELECT nama_pangkat FROM pangkat_golongan WHERE id = $value";
                    $pangkat_result = $conn->query($sql_pangkat);
                    if ($pangkat_result->num_rows > 0) {
                        $pangkat_row = $pangkat_result->fetch_assoc();
                        $value = $pangkat_row['nama_pangkat'];
                    }
                } elseif ($field == 'eselon_id' && $value) {
                    $sql_eselon = "SELECT nama_eselon FROM eselon WHERE id = $value";
                    $eselon_result = $conn->query($sql_eselon);
                    if ($eselon_result->num_rows > 0) {
                        $eselon_row = $eselon_result->fetch_assoc();
                        $value = $eselon_row['nama_eselon'];
                    }
                } elseif ($field == 'lokasi_id' && $value) {
                    $sql_lokasi = "SELECT nama_lokasi FROM lokasi WHERE id = $value";
                    $lokasi_result = $conn->query($sql_lokasi);
                    if ($lokasi_result->num_rows > 0) {
                        $lokasi_row = $lokasi_result->fetch_assoc();
                        $value = $lokasi_row['nama_lokasi'];
                    }
                } elseif ($field == 'kelas_jabatan_id' && $value) {
                    $sql_kelas = "SELECT nama_kelas FROM kelas_jabatan WHERE id = $value";
                    $kelas_result = $conn->query($sql_kelas);
                    if ($kelas_result->num_rows > 0) {
                        $kelas_row = $kelas_result->fetch_assoc();
                        $value = $kelas_row['nama_kelas'];
                    }
                } elseif ($field == 'jenis_kelamin') {
                    $value = ($value == 'L') ? 'Laki-laki' : 'Perempuan';
                }
                
                // Add highlight style for changed fields
                if ($has_changed) {
                    echo '<td style="background-color: #fffacd;">' . ($value ?: '-') . '</td>';
                } else {
                    echo '<td>' . ($value ?: '-') . '</td>';
                }
            }
            echo '</tr>';
        }
    }
    
    echo '</table>';
    exit();
}

// Export to PDF if requested
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    // For PDF export, you would normally use a library like FPDF or MPDF
    // This is a placeholder for that functionality
    header('Content-Type: text/html');
    echo '<html><body>';
    echo '<h1>PDF Export would go here</h1>';
    echo '<p>In a production environment, integrate a PHP PDF library like FPDF or MPDF.</p>';
    echo '</body></html>';
    exit();
}

// Get field list for display selection
$fields = array(
    'nip' => 'NIP',
    'nama' => 'Nama',
    'status' => 'Status',
    'jenis_kelamin' => 'Jenis Kelamin',
    'pendidikan' => 'Pendidikan',
    'alamat' => 'Alamat',
    'jabatan_id' => 'Jabatan',
    'unit_kerja_id' => 'Unit Kerja',
    'pangkat_golongan_id' => 'Pangkat/Golongan',
    'eselon_id' => 'Eselon',
    'lokasi_id' => 'Lokasi',
    'kelas_jabatan_id' => 'Kelas Jabatan'
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .field-selector {
            margin-bottom: 20px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .field-selector h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .field-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .field-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        
        .field-item input {
            margin-right: 5px;
        }
        
        .export-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn-export {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .changes-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: #fffacd;
            border: 1px solid #e0e0e0;
            margin-right: 5px;
        }
        
        .legend {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .highlight {
            background-color: #fffacd;
        }

        .history-details {
            padding: 10px;
            margin-top: 5px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .history-item {
            padding: 8px;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-field {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .history-change {
            margin-left: 10px;
            margin-bottom: 4px;
        }

        .history-old {
            color: #d9534f;
        }

        .history-new {
            color: #5cb85c;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Include sidebar -->
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            <main>
                <h1>Generate Laporan Bulanan</h1>
                <!-- Filter section -->
                <div class="filter-section">
                    <form method="post" class="filter-form">
                        <div class="filter-group">
                            <label for="month">Bulan</label>
                            <select name="month" id="month">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == $month) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="year">Tahun</label>
                            <select name="year" id="year">
                                <?php 
                                // Generate year options based on data range
                                for ($i = $max_year; $i >= $min_year; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == $year) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="filter-buttons">
                            <button type="submit" name="generate_report" class="btn">
                                <i class="fas fa-sync-alt"></i> Generate Laporan
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Field selection -->
                <div class="field-selector">
                    <h3>Pilih Kolom yang Ditampilkan</h3>
                    <form method="get" id="field-form">
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        
                        <div class="field-list">
                            <?php foreach ($fields as $field_name => $field_label): ?>
                                <div class="field-item">
                                    <input type="checkbox" name="fields[]" id="field_<?php echo $field_name; ?>" 
                                           value="<?php echo $field_name; ?>" 
                                           <?php echo (in_array($field_name, $selected_fields)) ? 'checked' : ''; ?>>
                                    <label for="field_<?php echo $field_name; ?>"><?php echo $field_label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="filter-buttons">
                            <button type="submit" class="btn">
                                <i class="fas fa-filter"></i> Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Export options and legend -->
                <div class="filter-section">
                    <div class="legend">
                        <div class="changes-indicator"></div> Data yang berubah pada bulan ini
                    </div>
                    
                    <div class="export-buttons">
                        <a href="?month=<?php echo $month; ?>&year=<?php echo $year; ?>&export=excel&<?php echo http_build_query(array('fields' => $selected_fields)); ?>" class="btn btn-export">
                            <i class="fas fa-file-excel"></i> Export ke Excel
                        </a>
                        <a href="?month=<?php echo $month; ?>&year=<?php echo $year; ?>&export=pdf&<?php echo http_build_query(array('fields' => $selected_fields)); ?>" class="btn btn-export">
                            <i class="fas fa-file-pdf"></i> Export ke PDF
                        </a>
                    </div>
                </div>
                
                <!-- Report table -->
                <div class="data-table-container">
                    <h2>Laporan Data Pegawai - <?php echo date("F Y", strtotime("$year-$month-01")); ?></h2>
                    
                    <div class="activity-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <?php foreach ($selected_fields as $field): ?>
                                        <th>
                                            <?php 
                                                switch ($field) {
                                                    case 'nip': echo "NIP"; break;
                                                    case 'nama': echo "Nama"; break;
                                                    case 'status': echo "Status"; break;
                                                    case 'jenis_kelamin': echo "Jenis Kelamin"; break;
                                                    case 'pendidikan': echo "Pendidikan"; break;
                                                    case 'alamat': echo "Alamat"; break;
                                                    case 'jabatan_id': echo "Jabatan"; break;
                                                    case 'unit_kerja_id': echo "Unit Kerja"; break;
                                                    case 'pangkat_golongan_id': echo "Pangkat/Golongan"; break;
                                                    case 'eselon_id': echo "Eselon"; break;
                                                    case 'lokasi_id': echo "Lokasi"; break;
                                                    case 'kelas_jabatan_id': echo "Kelas Jabatan"; break;
                                                }
                                            ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <?php if ($report_id): ?>
                                        <th>Detail Perubahan</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Define time period for filtering data
                                $start_date = date("Y-m-01 00:00:00", strtotime("$year-$month-01"));
                                $end_date = date("Y-m-t 23:59:59", strtotime("$year-$month-01"));
                                
                                // Get all pegawai data with the date filter criteria
                                $sql = "SELECT p.*, rd.has_changes, rd.changes_detail FROM pegawai p
                                        LEFT JOIN report_detail rd ON p.id = rd.pegawai_id AND rd.report_id = " . ($report_id ? $report_id : 0) . "
                                        WHERE (p.created_at BETWEEN '$start_date' AND '$end_date') OR 
                                              (p.updated_at BETWEEN '$start_date' AND '$end_date')
                                        ORDER BY p.nama ASC";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0) {
                                    $no = 1;
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        
                                        // Determine if this employee was created or updated in this period
                                        $created_in_period = (strtotime($row['created_at']) >= strtotime($start_date) && 
                                                             strtotime($row['created_at']) <= strtotime($end_date));
                                        $updated_in_period = (strtotime($row['updated_at']) >= strtotime($start_date) && 
                                                             strtotime($row['updated_at']) <= strtotime($end_date) &&
                                                             $row['updated_at'] != $row['created_at']);
                                        
                                        foreach ($selected_fields as $field) {
                                            $value = $row[$field];
                                            $has_changed = false;
                                            
                                            // Check if this field has changes in the history data
                                            if ($row['has_changes'] && $row['changes_detail']) {
                                                $changes = json_decode($row['changes_detail'], true);
                                                if ($changes) {
                                                    foreach ($changes as $change) {
                                                        if ($change['field'] == $field) {
                                                            $has_changed = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Replace IDs with actual names for related fields
                                            if ($field == 'jabatan_id' && $value) {
                                                $sql_jabatan = "SELECT nama_jabatan FROM jabatan WHERE id = $value";
                                                $jabatan_result = $conn->query($sql_jabatan);
                                                if ($jabatan_result->num_rows > 0) {
                                                    $jabatan_row = $jabatan_result->fetch_assoc();
                                                    $value = $jabatan_row['nama_jabatan'];
                                                }
                                            } elseif ($field == 'unit_kerja_id' && $value) {
                                                $sql_unit = "SELECT nama_unit FROM unit_kerja WHERE id = $value";
                                                $unit_result = $conn->query($sql_unit);
                                                if ($unit_result->num_rows > 0) {
                                                    $unit_row = $unit_result->fetch_assoc();
                                                    $value = $unit_row['nama_unit'];
                                                }
                                            } elseif ($field == 'pangkat_golongan_id' && $value) {
                                                $sql_pangkat = "SELECT nama_pangkat FROM pangkat_golongan WHERE id = $value";
                                                $pangkat_result = $conn->query($sql_pangkat);
                                                if ($pangkat_result->num_rows > 0) {
                                                    $pangkat_row = $pangkat_result->fetch_assoc();
                                                    $value = $pangkat_row['nama_pangkat'];
                                                }
                                            } elseif ($field == 'eselon_id' && $value) {
                                                $sql_eselon = "SELECT nama_eselon FROM eselon WHERE id = $value";
                                                $eselon_result = $conn->query($sql_eselon);
                                                if ($eselon_result->num_rows > 0) {
                                                    $eselon_row = $eselon_result->fetch_assoc();
                                                    $value = $eselon_row['nama_eselon'];
                                                }
                                            } elseif ($field == 'lokasi_id' && $value) {
                                                $sql_lokasi = "SELECT nama_lokasi FROM lokasi WHERE id = $value";
                                                $lokasi_result = $conn->query($sql_lokasi);
                                                if ($lokasi_result->num_rows > 0) {
                                                    $lokasi_row = $lokasi_result->fetch_assoc();
                                                    $value = $lokasi_row['nama_lokasi'];
                                                }
                                            } elseif ($field == 'kelas_jabatan_id' && $value) {
                                                $sql_kelas = "SELECT nama_kelas FROM kelas_jabatan WHERE id = $value";
                                                $kelas_result = $conn->query($sql_kelas);
                                                if ($kelas_result->num_rows > 0) {
                                                    $kelas_row = $kelas_result->fetch_assoc();
                                                    $value = $kelas_row['nama_kelas'];
                                                }
                                            } elseif ($field == 'jenis_kelamin') {
                                                $value = ($value == 'L') ? 'Laki-laki' : 'Perempuan';
                                            }
                                            
                                            // Add highlight class for changed fields
                                            if ($has_changed) {
                                                echo "<td class='highlight'>" . ($value ?? '-') . "</td>";
                                            } else {
                                                echo "<td>" . ($value ?? '-') . "</td>";
                                            }
                                        }
                                        
                                        // Add detail column if report exists
                                        if ($report_id) {
                                            echo "<td>";
                                            if ($row['has_changes'] && $row['changes_detail']) {
                                                echo "<button type='button' class='btn btn-sm' onclick='showHistoryDetails(" . $row['id'] . ")'>
                                                      <i class='fas fa-history'></i> Lihat Detail
                                                  </button>";
                                                  
                                                // Hidden container for history details
                                                echo "<div id='history_" . $row['id'] . "' class='history-details' style='display:none;'>";
                                                
                                                $changes = json_decode($row['changes_detail'], true);
                                                if ($changes) {
                                                    foreach ($changes as $change) {
                                                        echo "<div class='history-item'>";
                                                        echo "<div class='history-field'>" . mapFieldName($change['field']) . "</div>";
                                                        echo "<div class='history-change'>";
                                                        echo "<div class='history-old'>Sebelumnya: " . formatValue($change['field'], $change['old']) . "</div>";
                                                        echo "<div class='history-new'>Menjadi: " . formatValue($change['field'], $change['new']) . "</div>";
                                                        echo "</div>";
                                                        echo "<div>Tanggal perubahan: " . date("d-m-Y H:i", strtotime($change['date'])) . "</div>";
                                                        echo "</div>";
                                                    }
                                                }
                                                
                                                echo "</div>";
                                            } else {
                                                echo "-";
                                            }
                                            echo "</td>";
                                        }
                                        
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='" . (count($selected_fields) + 2) . "' class='text-center'>Tidak ada data pegawai untuk periode ini</td></tr>";
                                }
                                
                                // Helper function to map field names to readable names
                                function mapFieldName($field) {
                                    switch ($field) {
                                        case 'nip': return "NIP";
                                        case 'nama': return "Nama";
                                        case 'status': return "Status";
                                        case 'jenis_kelamin': return "Jenis Kelamin";
                                        case 'pendidikan': return "Pendidikan";
                                        case 'alamat': return "Alamat";
                                        case 'jabatan_id': return "Jabatan";
                                        case 'unit_kerja_id': return "Unit Kerja";
                                        case 'pangkat_golongan_id': return "Pangkat/Golongan";
                                        case 'eselon_id': return "Eselon";
                                        case 'lokasi_id': return "Lokasi";
                                        case 'kelas_jabatan_id': return "Kelas Jabatan";
                                        default: return $field;
                                    }
                                }
                                                                
                                // Helper function to format values based on their field type
                                function formatValue($field, $value) {
                                    global $conn;
                                    
                                    if ($value === null || $value === '') {
                                        return '-';
                                    }
                                    
                                    switch ($field) {
                                        case 'jabatan_id':
                                            $sql = "SELECT nama_jabatan FROM jabatan WHERE id = $value";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                $row = $result->fetch_assoc();
                                                return $row['nama_jabatan'];
                                            }
                                            return $value;
                                            
                                        case 'unit_kerja_id':
                                            $sql = "SELECT nama_unit FROM unit_kerja WHERE id = $value";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                $row = $result->fetch_assoc();
                                                return $row['nama_unit'];
                                            }
                                            return $value;
                                            
                                        case 'pangkat_golongan_id':
                                            $sql = "SELECT nama_pangkat FROM pangkat_golongan WHERE id = $value";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                $row = $result->fetch_assoc();
                                                return $row['nama_pangkat'];
                                            }
                                            return $value;
                                            
                                        case 'eselon_id':
                                            $sql = "SELECT nama_eselon FROM eselon WHERE id = $value";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                $row = $result->fetch_assoc();
                                                return $row['nama_eselon'];
                                            }
                                            return $value;
                                            
                                        case 'lokasi_id':
                                            $sql = "SELECT nama_lokasi FROM lokasi WHERE id = $value";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                $row = $result->fetch_assoc();
                                                return $row['nama_lokasi'];
                                            }
                                            return $value;
                                            
                                        case 'kelas_jabatan_id':
                                            $sql = "SELECT nama_kelas FROM kelas_jabatan WHERE id = $value";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                $row = $result->fetch_assoc();
                                                return $row['nama_kelas'];
                                            }
                                            return $value;
                                            
                                        case 'jenis_kelamin':
                                            return ($value == 'L') ? 'Laki-laki' : 'Perempuan';
                                            
                                        default:
                                            return $value;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            
            <footer>
                <p>&copy; <?php echo date('Y'); ?> Sistem Informasi Pegawai - DP3APPKB</p>
            </footer>
        </div>
    </div>
    
    <script>
        // Function to toggle history details
        function showHistoryDetails(id) {
            const detailsDiv = document.getElementById('history_' + id);
            if (detailsDiv.style.display === 'none') {
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }
        }
        
        // Enable auto-submit when checkboxes change
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="fields[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    // Ensure at least one checkbox is selected
                    const checkedBoxes = document.querySelectorAll('input[name="fields[]"]:checked');
                    if (checkedBoxes.length > 0) {
                        document.getElementById('field-form').submit();
                    } else {
                        alert('Anda harus memilih minimal satu kolom');
                        checkbox.checked = true;
                    }
                });
            });
        });
    </script>
</body>
</html>