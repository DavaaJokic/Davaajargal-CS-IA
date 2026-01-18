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
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']); 

    $update_sql = "UPDATE users SET full_name = ?, username = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssi", $full_name, $username, $user_id);

    if (mysqli_stmt_execute($stmt)) {
       
        $_SESSION['username'] = $username; 
        $message = "<div class='alert alert-success'>Профайл амжилттай шинэчлэгдлээ!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Алдаа гарлаа: " . mysqli_error($conn) . "</div>";
    }
}


$fetch_sql = "SELECT username, full_name, profile_picture FROM users WHERE user_id = ?";
$stmt_fetch = mysqli_prepare($conn, $fetch_sql);
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);
$user_data = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="mn">
<head>
  <meta charset="UTF-8">
  <title>Профайл Удирдах</title>
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
    <h2 class="text-center mb-4"><i class="bi bi-person-circle"></i> Профайл Удирдах</h2>
    <?php echo $message; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4">
                
                <div class="text-center mb-4">
                    <img src="<?php echo $user_data['profile_picture'] ?? 'default_profile.png'; ?>" 
                         alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="bi bi-tag-fill"></i> Хэрэглэгчийн Нэр (Login):</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="bi bi-person"></i> Бүтэн Нэр (Display):</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold"><i class="bi bi-image"></i> Профайл зураг:</label>
                        <input type="file" name="profile_photo" class="form-control" disabled>
                        <div class="form-text"></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">Хадгалах</button>
                    
                    <hr>
                    <a href="change_password.php" class="btn btn-outline-secondary w-100">Нууц үг Солих</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>