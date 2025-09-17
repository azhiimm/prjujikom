<?php
// Konfigurasi koneksi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_hrd_pegawai';

// Membuat koneksi
$conn = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Fungsi untuk mencegah SQL injection
function clean($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// Fungsi untuk mencatat history perubahan
function logChange($conn, $pegawaiId, $fieldName, $oldValue, $newValue, $userId) {
    $query = "INSERT INTO history_pegawai (pegawai_id, field_name, old_value, new_value, changed_by) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssi", $pegawaiId, $fieldName, $oldValue, $newValue, $userId);
    $stmt->execute();
    $stmt->close();
}

// Fungsi untuk mendapatkan nama lengkap dari ID referensi
function getNamaFromId($conn, $table, $id, $idColumn = 'id', $nameColumn) {
    $query = "SELECT $nameColumn FROM $table WHERE $idColumn = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row[$nameColumn];
    }
    
    return "";
}

// Fungsi untuk mendapatkan data dropdown
function getDropdownData($conn, $table, $idColumn = 'id', $nameColumn) {
    $data = array();
    $query = "SELECT $idColumn, $nameColumn FROM $table ORDER BY $nameColumn";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}
?>