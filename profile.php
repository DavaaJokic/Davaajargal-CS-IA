<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'>New passwords do not match!</div>";
    } else {
        // Verify current password
        $check_sql = "SELECT password FROM users WHERE user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_bind_result($check_stmt, $hashed_password);
        mysqli_stmt_fetch($check_stmt);
        mysqli_stmt_close($check_stmt);
        
        if (password_verify($current_password, $hashed_password)) {
            // Update password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $new_hashed_password, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = "<div class='alert alert-success'>Password changed successfully!</div>";
                header("Refresh: 2; url=profile.php");
            } else {
                $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $message = "<div class='alert alert-danger'>Current password is incorrect!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
        --primary-gradient: linear-gradient(to right, #004d99, #007bff);
    }
    
    body {
        background: var(--primary-gradient);
        min-height: 100vh;
        font-family: Arial, sans-serif;
    }
    
    .content-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 30px;
        margin-top: 20px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .password-card {
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 20px;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    
    .input-group-text {
        cursor: pointer;
    }
    
    .input-group-text:hover {
        background-color: #e9ecef;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-lg">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-camera2"></i> Family Memories
    </a>
    <div class="d-flex align-items-center">
        <a href="index.php" class="btn btn-outline-light btn-sm me-2">Home</a>
        <a href="upload.php" class="btn btn-light btn-sm me-2">Upload</a>
        <a href="profile.php" class="btn btn-light btn-sm me-2">Profile</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
    <div class="content-container">
        <h2 class="text-center mb-4 text-primary">
            <i class="bi bi-shield-lock"></i> Change Password
        </h2>
        
        <?php echo $message; ?>
        
        <div class="password-card">
            <form method="POST" id="passwordForm">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-lock-fill"></i> Current Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="current_password" 
                               class="form-control" 
                               id="currentPassword"
                               placeholder="Enter current password" 
                               required>
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="togglePassword('currentPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-key-fill"></i> New Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="new_password" 
                               class="form-control" 
                               id="newPassword"
                               placeholder="Enter new password" 
                               required>
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="togglePassword('newPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-key"></i> Confirm New Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" 
                               class="form-control" 
                               id="confirmPassword"
                               placeholder="Confirm new password" 
                               required>
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="togglePassword('confirmPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-shield-check me-2"></i>
                        Update Password
                    </button>
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Profile
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match. Please confirm your new password.');
        return;
    }
    
    if (newPassword.length < 8) {
        e.preventDefault();
        alert('New password must be at least 8 characters long.');
        return;
    }
});
</script>

</body>
</html>