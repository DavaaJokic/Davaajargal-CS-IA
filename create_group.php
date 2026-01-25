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
        $message = "<div class='alert alert-danger'>Please enter an event name.</div>";
    } else {
        $insert_sql = "INSERT INTO groups (group_name, creator_id, created_at) VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "si", $group_name, $creator_id);

        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success'>'{$group_name}' event created successfully! </div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Event Group</title>
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
        <a href="search.php" class="btn btn-light btn-sm me-2">Search</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="content-container">
                <h2 class="text-center mb-4 text-primary">
                    <i class="bi bi-people-fill"></i> Create New Event Group
                </h2>
                <?php echo $message; ?>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card shadow-lg p-4">
                            <p class="text-muted">Enter a name for your family event or collection. Photos will be grouped under this name.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-bookmark-fill"></i> Event Name:
                                    </label>
                                    <input type="text" name="group_name" class="form-control form-control-lg" 
                                           placeholder="Example: 2026 Summer Vacation" required>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100 btn-lg">
                                    <i class="bi bi-check-circle"></i> Create Group
                                </button>
                                <hr>
                                <a href="upload.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left"></i> Back to Upload Page
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>