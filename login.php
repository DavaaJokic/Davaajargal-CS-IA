<?php
// 🟢 Start session
session_start();

// 🟢 Include database connection
include "connection.php";

// 🟢 Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Хэрэглэгчийн нэр болон нууц үгээ оруулна уу!";
    } else {
        // Query to find user
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Redirect to index.php
                header("Location: index.php");
                exit;
            } else {
                $error = "Нууц үг буруу байна!";
            }
        } else {
            $error = "Хэрэглэгч олдсонгүй!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>🔑 Нэвтрэх</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Нэвтрэх</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Хэрэглэгчийн нэр:</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Нууц үг:</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Нэвтрэх</button>
                        </form>
                        
                        <hr>
                        <p class="text-center">
                            Шинэ хэрэглэгч үү? 
                            <a href="register.php">Бүртгүүлэх</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>