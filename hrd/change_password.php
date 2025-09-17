<?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    // Include database connection
    require_once "config/connection.php";
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        $errors = [];
        
        // Get current password from database
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Check if the current password is correct - Note: In this example DB passwords aren't hashed
        if ($current_password !== $user['password']) {
            $errors[] = "Password saat ini tidak sesuai.";
        }
        
        // Check if new password matches confirmation
        if ($new_password !== $confirm_password) {
            $errors[] = "Password baru dan konfirmasi password tidak cocok.";
        }
        
        // Validate password strength
        if (strlen($new_password) < 8) {
            $errors[] = "Password minimal 8 karakter.";
        }
        
        // If no errors, update password
        if (empty($errors)) {
            // Note: Normally we would hash the password, but based on the provided database schema,
            // it seems passwords are stored as plain text.
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_password, $user_id);
            
            if ($update->execute()) {
                $success_message = "Password berhasil diubah!";
            } else {
                $error_message = "Gagal mengubah password: " . $conn->error;
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password</title>
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
                <h1><i class="fas fa-key"></i> Ganti Password</h1>
                
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
                    <form action="change_password.php" method="POST">
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="new_password">Password Baru</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <small>Minimal 8 karakter</small>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="confirm_password">Konfirmasi Password Baru</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="profile.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="form-container">
                    <h2>Tips Keamanan Password</h2>
                    <ul>
                        <li>Gunakan minimal 8 karakter</li>
                        <li>Kombinasikan huruf besar dan kecil</li>
                        <li>Sertakan angka dan karakter khusus</li>
                        <li>Hindari menggunakan informasi pribadi</li>
                        <li>Jangan gunakan password yang sama untuk akun yang berbeda</li>
                    </ul>
                </div>
            </main>
            
            <!-- Footer -->
            <?php include_once "templates/footer.php"; ?>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>