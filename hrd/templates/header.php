<header>
    <div class="menu-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <div class="user-info">
        <span class="user-name">Halo, <?php echo $_SESSION['nama_lengkap']; ?></span>
        <div class="dropdown">
            <button class="dropdown-btn">
                <i class="fas fa-user-circle"></i>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                <a href="change_password.php"><i class="fas fa-key"></i> Ganti Password</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>