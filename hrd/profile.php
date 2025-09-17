<?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    // Include database connection
    require_once "config/connection.php";
    
    // Get user information
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, username, nama_lengkap, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nama_lengkap = $_POST['nama_lengkap'];
        $email = $_POST['email'];
        
        // Update user information
        $update = $conn->prepare("UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?");
        $update->bind_param("ssi", $nama_lengkap, $email, $user_id);
        
        if ($update->execute()) {
            // Update session variable
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $success_message = "Profil berhasil diperbarui!";
            
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Gagal memperbarui profil: " . $conn->error;
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include_once "templates/sidebar.php"; ?>
        
        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <?php include_once "templates/header.php"; ?>
            
            <!-- Main Content -->
            <main>
                <h1><i class="fas fa-user-circle"></i> Profil Pengguna</h1>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-container">
                    <form action="profile.php" method="POST">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="nama_lengkap">Nama Lengkap</label>
                                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo $user['nama_lengkap']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" value="<?php echo $user['username']; ?>" readonly>
                                    <small>Username tidak dapat diubah</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    <small>Role hanya dapat diubah oleh administrator</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </main>
            
            <!-- Footer -->
            <?php include_once "templates/footer.php"; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>