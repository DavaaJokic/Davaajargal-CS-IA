<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$creator_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $group_name = mysqli_real_escape_string($conn, trim($_POST['group_name']));

    if (empty($group_name)) {
        $message = "<div class='alert alert-danger'>Үйл явдлын нэрийг заавал оруулна уу.</div>";
    } else {
        $insert_sql = "INSERT INTO groups (group_name, creator_id, created_at) VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "si", $group_name, $creator_id);

        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success'>'{$group_name}' нэртэй шинэ үйл явдал амжилттай үүслээ! </div>";
        } else {
            $message = "<div class='alert alert-danger'>Алдаа гарлаа: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
  <meta charset="UTF-8">
  <title>Үйл явдал Үүсгэх</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid container">
    <a class="navbar-brand" href="index.php">📷 Дурсамж</a>
    <div>
      <a href="upload.php" class="btn btn-light btn-sm me-2">Зураг байрлуулах</a>
      <a href="search.php" class="btn btn-light btn-sm me-2">Хайх</a>
      <a href="calendar.php" class="btn btn-light btn-sm me-2">Календар</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Гарах</a>
    </div>
  </div>
</nav>

<div class="container py-5">
    <h2 class="text-center mb-4"><i class="bi bi-people-fill"></i> Шинэ Үйл Явдлын Групп Үүсгэх</h2>
    <?php echo $message; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4">
                <p class="text-muted">Гэр бүлийн шинэ үйл явдал эсвэл цуглуулгын нэрийг оруулна уу. Энэ нэрээр зургуудыг нэгтгэх болно.</p>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="bi bi-bookmark-fill"></i> Үйл явдлын Нэр:</label>
                        <input type="text" name="group_name" class="form-control form-control-lg" placeholder="Жишээ: 2026 Оны Зуны Амралт" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 btn-lg">Групп Үүсгэх</button>
                    <hr>
                    <a href="upload.php" class="btn btn-outline-secondary w-100">Зураг Байршуулах Хуудас руу буцах</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>