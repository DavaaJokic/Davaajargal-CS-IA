<?php 
include "connection.php"; 
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>📝 Бүртгүүлэх</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            background-color: #ffffff;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card register-card p-4">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4 text-primary"><i class="bi bi-person-plus-fill me-2"></i>Бүртгэл Үүсгэх</h2>

                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        
                     
                        $username = trim($_POST['username']);
                        $full_name = trim($_POST['full_name']);
                        $password = $_POST['password'];

                        if (empty($username) || empty($full_name) || empty($password) || strlen($username) < 3 || strlen($password) < 6) {
                            echo "<div class='alert alert-danger'>Талбарын мэдээлэл дутуу эсвэл буруу байна!</div>";
                        } else {
                
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
             
                            $check_sql = "SELECT user_id FROM users WHERE username = ?";
                            $stmt_check = mysqli_prepare($conn, $check_sql);
                     
                            mysqli_stmt_bind_param($stmt_check, "s", $username); 
                            
                            mysqli_stmt_execute($stmt_check);
                            mysqli_stmt_store_result($stmt_check);

                            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                         
                                echo "<div class='alert alert-warning'>'{$username}' нэртэй хэрэглэгч аль хэдийн бүртгэгдсэн байна! ⚠️</div>";
                            } else {
                         
                                $insert_sql = "INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)";
                                $stmt = mysqli_prepare($conn, $insert_sql);
                                
                         
                                mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $full_name);

                                if (mysqli_stmt_execute($stmt)) {
                                    echo "<div class='alert alert-success'>Бүртгэл амжилттай . Одоо <a href='login.php'>Нэвтрэх</a> боломжтой.</div>";
                                } else {
                                    echo "<div class='alert alert-danger'>Бүртгэхэд алдаа гарлаа: " . mysqli_error($conn) . "</div>";
                                }
                            }
                            mysqli_stmt_close($stmt_check);
                            if (isset($stmt)) mysqli_stmt_close($stmt);
                        }
                    }
                    ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="bi bi-person-fill me-2"></i>Хэрэглэгчийн нэр (Login):</label>
                            <input type="text" name="username" id="username" class="form-control form-control-lg" placeholder="Нэвтрэх нэрээ оруулна уу" required minlength="3">
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label"><i class="bi bi-person me-2"></i>Бүтэн Нэр:</label>
                            <input type="text" name="full_name" id="full_name" class="form-control form-control-lg" placeholder="Өөрийн бүтэн нэрээ оруулна уу" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label"><i class="bi bi-key-fill me-2"></i>Нууц үг:</label>
                            <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Нууц үгээ үүсгэнэ үү" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-check-circle-fill me-2"></i>Бүртгүүлэх</button>
                    </form>
                    <p class="text-center mt-4">Аль хэдийн бүртгэлтэй юу? <a href="login.php">Нэвтрэх</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>