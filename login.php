<?php
session_start();
include "connection.php";
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>🔑 Нэвтрэх</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="text-center mb-4 text-success"><i class="bi bi-lock-fill"></i> Системд Нэвтрэх</h2>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = mysqli_real_escape_string($conn, trim($_POST['username']));
        $password = $_POST['password'];

     
        $sql = "SELECT user_id, username, password FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
         
            if (password_verify($password, $user['password'])) {
                
           
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];

               
                header("Location: index.php");
                exit;
            } else {
                echo "<div class='alert alert-danger'>Нууц үг буруу байна ❌</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>'{$username}' нэртэй хэрэглэгч олдсонгүй ❌</div>";
        }
    }
    ?>

    <form method="POST" class="card p-4 shadow-lg">
        <div class="mb-3">
            <label class="form-label fw-bold"><i class="bi bi-person-fill"></i> Хэрэглэгчийн нэр:</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold"><i class="bi bi-key-fill"></i> Нууц үг:</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-success w-100 btn-lg mt-3"><i class="bi bi-box-arrow-in-right"></i> Нэвтрэх</button>
    </form>

    <p class="text-center mt-3">Шинэ хэрэглэгч бол <a href="register.php">энд дарж бүртгүүлнэ</a> үү.</p>
</div>

</body>
</html>