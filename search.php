<?php include "connection.php"; ?>
<!DOCTYPE html>
<html lang="mn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Хайлт</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid container">
    <a class="navbar-brand" href="index.php">📷Дурсамж</a>
    <div>
      <a href="upload.php" class="btn btn-light btn-sm me-2">Зураг байрлуулах</a>
      <a href="search.php" class="btn btn-light btn-sm me-2">Хайх</a>
      <a href="calendar.php" class="btn btn-light btn-sm me-2">Календар</a>
      <a href="login.php" class="btn btn-light btn-sm">Нэвтрэх</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h2 class="text-center mb-5 text-primary"><i class="bi bi-search"></i> Зураг хайх</h2>

  <form class="card p-4 shadow-lg mb-5" method="GET">
    <div class="input-group input-group-lg">
        <span class="input-group-text bg-light"><i class="bi bi-key-fill"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Жишээ: Төрсөн өдөр, Шинэ жил, 2024" 
               value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" required>
        <button class="btn btn-primary" type="submit"><i class="bi bi-binoculars-fill"></i> Хайх</button>
    </div>
  </form>

  <div class="row">

  <?php
  if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
      $search = trim($_GET['q']);
      

      $search_param = "%" . $search . "%";
      $sql = "SELECT photo_id, file_path, event, tag FROM photos WHERE event LIKE ? OR tag LIKE ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      echo "<h3 class='mb-4'> Хайлтын үр дүн: **" . htmlspecialchars($search) . "**</h3>";

      if (mysqli_num_rows($result) == 0) {
          echo "<div class='col-12'><p class='text-center text-muted'>'**" . htmlspecialchars($search) . "**' -ээр үр дүн олдсонгүй.</p></div>";
      }

      while ($row = mysqli_fetch_assoc($result)) {
        echo "
        <div class='col-md-3 mb-4'>
          <div class='card shadow-sm h-100'>
            <a href='photo.php?id={$row['photo_id']}'>
              <img src='{$row['file_path']}' class='card-img-top' alt='{$row['event']}' style='height:200px; object-fit:cover;'>
            </a>
            <div class='card-body text-center'>
              <p class='mb-1 text-primary text-truncate'><strong>{$row['event']}</strong></p>
              <p class='text-muted' style='font-size: 14px;'>#{$row['tag']}</p>
            </div>
          </div>
        </div>
        ";
      }
  } else {
    echo "<div class='col-12'><p class='text-center text-muted'>Хайх үг эсвэл үйл явдлын нэрийг оруулна уу.</p></div>";
  }
  ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>