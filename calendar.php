<?php include "connection.php"; ?>
<!DOCTYPE html>
<html lang="mn">
<head>
  <meta charset="UTF-8">
  <title>Календар</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">📷 Дурсамж</a>
    <div>
      <a href="upload.php" class="btn btn-light btn-sm me-2">Зураг байрлуулах</a>
      <a href="search.php" class="btn btn-light btn-sm me-2">Хайх</a>
      <a href="calendar.php" class="btn btn-light btn-sm me-2">Календар</a>
      <a href="login.php" class="btn btn-light btn-sm">Нэвтрэх</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">📅 Огноогоор зураг харах</h2>

  <form method="GET" class="card p-4 shadow-sm mb-4">
    <label class="form-label">Огноо сонгох:</label>
    <input type="date" name="date" class="form-control mb-3" required>
    <button class="btn btn-primary w-100">Харах</button>
  </form>

  <div class="row">

  <?php
  if (isset($_GET['date'])) {
      $date = $_GET['date'];
      $sql = "SELECT * FROM photos WHERE date_taken = '$date'";
      $result = mysqli_query($conn, $sql);

      if (mysqli_num_rows($result) == 0) {
          echo "<p class='text-center text-muted'>Энэ өдөр зураг олдсонгүй.</p>";
      }

      while ($row = mysqli_fetch_assoc($result)) {
          echo "
          <div class='col-md-3 mb-4'>
            <div class='card shadow-sm'>
              <a href='photo.php?id={$row['photo_id']}'>
                <img src='{$row['file_path']}' class='card-img-top' style='height:200px; object-fit:cover;'>
              </a>
              <div class='card-body text-center'>
                <p class='mb-1'><strong>Үйл явдал:</strong> {$row['event']}</p>
                <p class='text-muted' style='font-size: 14px;'>Түлхүүр үг: {$row['tag']}</p>
              </div>
            </div>
          </div>
          ";
      }
  }
  ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
