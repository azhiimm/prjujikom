<?php
// Mendapatkan nama file saat ini untuk menentukan menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="assets/img/logo.png" alt="Logo">
        </div>
        <div class="sidebar-title">Sistem Data Pegawai</div>
        <div class="sidebar-subtitle">DP3APPKB</div>
        <div class="sidebar-subtitle">Kota Surabaya</div>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <a href="index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
        
        <div class="menu-item <?php echo ($current_page == 'pegawai.php') ? 'active' : ''; ?>">
            <a href="pegawai.php">
                <i class="fas fa-users"></i> Data Pegawai
            </a>
        </div>
        
        <div class="menu-item <?php echo ($current_page == 'tambah_pegawai.php') ? 'active' : ''; ?>">
            <a href="tambah_pegawai.php">
                <i class="fas fa-user-plus"></i> Tambah Pegawai
            </a>
        </div>
        
        <div class="menu-item <?php echo ($current_page == 'history.php') ? 'active' : ''; ?>">
            <a href="history.php">
                <i class="fas fa-history"></i> History Perubahan
            </a>
        </div>
        
        <div class="menu-item <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>">
            <a href="report.php">
                <i class="fas fa-file-alt"></i> Laporan Bulanan
            </a>
        </div>
    </div>
</div>