<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';

// Ambil jumlah pegawai untuk statistik dashboard
$queryTotalPegawai = "SELECT COUNT(*) as total FROM pegawai";
$totalPegawai = $conn->query($queryTotalPegawai)->fetch_assoc()['total'];

$queryTotalPNS = "SELECT COUNT(*) as total FROM pegawai WHERE status = 'PNS'";
$totalPNS = $conn->query($queryTotalPNS)->fetch_assoc()['total'];

$queryTotalPPPK = "SELECT COUNT(*) as total FROM pegawai WHERE status = 'PPPK'";
$totalPPPK = $conn->query($queryTotalPPPK)->fetch_assoc()['total'];

$queryTotalKontrak = "SELECT COUNT(*) as total FROM pegawai WHERE status = 'TENAGA KONTRAK'";
$totalKontrak = $conn->query($queryTotalKontrak)->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Data Pegawai HRD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'templates/sidebar.php'; ?>
        
        <div class="content">
            <?php include 'templates/header.php'; ?>
            
            <main>
                <h1>Dashboard</h1>
                
                <div class="dashboard-stats">
                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Pegawai</h3>
                            <p><?php echo $totalPegawai; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-content">
                            <h3>PNS</h3>
                            <p><?php echo $totalPNS; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-icon green">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3>PPPK</h3>
                            <p><?php echo $totalPPPK; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Tenaga Kontrak</h3>
                            <p><?php echo $totalKontrak; ?></p>
                        </div>
                    </div>
                </div>

                <div class="recent-activity">
                    <h2>Aktivitas Terbaru</h2>
                    <div class="activity-container">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Pegawai</th>
                                    <th>Field</th>
                                    <th>Perubahan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $queryActivity = "SELECT h.*, p.nama as nama_pegawai, u.nama_lengkap as nama_user 
                                                 FROM history_pegawai h
                                                 JOIN pegawai p ON h.pegawai_id = p.id
                                                 JOIN users u ON h.changed_by = u.id
                                                 ORDER BY h.changed_at DESC
                                                 LIMIT 10";
                                $resultActivity = $conn->query($queryActivity);
                                
                                if ($resultActivity->num_rows > 0) {
                                    while ($row = $resultActivity->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . date("d-m-Y H:i", strtotime($row['changed_at'])) . "</td>";
                                        echo "<td>" . $row['nama_user'] . "</td>";
                                        echo "<td>" . $row['nama_pegawai'] . "</td>";
                                        echo "<td>" . $row['field_name'] . "</td>";
                                        echo "<td>" . $row['old_value'] . " → " . $row['new_value'] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Belum ada aktivitas</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            
            <?php include 'templates/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>