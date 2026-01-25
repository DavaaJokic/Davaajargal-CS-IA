<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Photos</title>
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
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 30px;
        margin-top: 20px;
    }
    
    .search-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="content-container">
                <h2 class="text-center mb-5 text-primary">
                    <i class="bi bi-search"></i> Search Photos
                </h2>

                <div class="search-card mb-5">
                    <form method="GET">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-key-fill"></i>
                            </span>
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Search by title, description, tags, or date" 
                                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" 
                                   required>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-binoculars-fill"></i> Search
                            </button>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Search by keywords like "birthday", "vacation", "2024", or specific tags
                            </small>
                        </div>
                    </form>
                </div>

                <div class="row">
                <?php
                if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
                    $search = trim($_GET['q']);
                    $search_param = "%" . $search . "%";
                    
                    // Updated SQL to search across multiple fields
                    $sql = "SELECT p.*, g.group_name, u.username 
                            FROM photos p
                            JOIN groups g ON p.group_id = g.group_id
                            JOIN users u ON p.uploader_id = u.user_id
                            WHERE p.title LIKE ? 
                               OR p.description LIKE ? 
                               OR p.tag LIKE ? 
                               OR p.date_taken LIKE ? 
                               OR g.group_name LIKE ?
                            ORDER BY p.uploaded_at DESC";
                    
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    echo "<h3 class='mb-4'> Search Results for: <strong>" . htmlspecialchars($search) . "</strong></h3>";

                    if (mysqli_num_rows($result) == 0) {
                        echo "<div class='col-12 text-center py-5'>
                                <i class='bi bi-search display-4 text-muted'></i>
                                <h4 class='text-muted mt-3'>No results found</h4>
                                <p class='text-muted'>Try different keywords or browse all photos</p>
                              </div>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "
                            <div class='col-md-4 col-lg-3 mb-4'>
                                <div class='card shadow-sm h-100'>
                                    <a href='photo.php?id={$row['photo_id']}'>
                                        <img src='{$row['file_path']}' class='card-img-top' alt='{$row['title']}' 
                                             style='height:200px; object-fit:cover;'>
                                    </a>
                                    <div class='card-body'>
                                        <h6 class='card-title text-truncate' title='{$row['title']}'>
                                            {$row['title']}
                                        </h6>
                                        <p class='small text-muted mb-1'>
                                            <i class='bi bi-person'></i> {$row['username']}
                                        </p>
                                        <p class='small text-muted mb-1'>
                                            <i class='bi bi-calendar'></i> {$row['date_taken']}
                                        </p>";
                                        if (!empty($row['tag'])) {
                                            echo "<p class='small text-primary mb-0'>
                                                    <i class='bi bi-tag'></i> {$row['tag']}
                                                  </p>";
                                        }
                                        echo "
                                    </div>
                                </div>
                            </div>";
                        }
                    }
                } else {
                    echo "<div class='col-12 text-center py-5'>
                            <i class='bi bi-search display-4 text-muted'></i>
                            <h4 class='text-muted mt-3'>Enter search keywords</h4>
                            <p class='text-muted'>Type in the search box above to find photos</p>
                          </div>";
                }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>