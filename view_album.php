<?php
// 🟢 Start session
session_start();

// 🟢 Include database connection
include "connection.php";

// 🟢 Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 🟢 Check if album ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$album_id = $_GET['id'];

// 🟢 Get album details and photos
$sql = "SELECT p.*, g.group_name, u.username, u.full_name,
               a.album_name, a.created_by, a.created_at as album_created
        FROM photos p
        JOIN groups g ON p.group_id = g.group_id
        JOIN users u ON p.uploader_id = u.user_id
        JOIN albums a ON p.album_id = a.album_id
        WHERE p.album_id = '$album_id'
        ORDER BY p.album_order ASC";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

// Get first row for album info
$first_photo = mysqli_fetch_assoc($result);
$album_name = $first_photo['album_name'];
$album_creator = $first_photo['full_name'];
$album_created = $first_photo['album_created'];
$photo_count = mysqli_num_rows($result) + 1; // +1 because we already fetched one

// Reset pointer to beginning
mysqli_data_seek($result, 0);
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($album_name); ?> - Альбом</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    .album-header {
        background: rgba(255,255,255,0.95);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    .photo-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        background: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .photo-item:hover {
        transform: translateY(-5px);
    }
    .photo-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    .photo-info {
        padding: 15px;
    }
    .photo-order {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-images me-2"></i>📷 Дурсамж
        </a>
        <a href="index.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Буцах
        </a>
    </div>
</nav>

<div class="container py-4">
    <!-- 🟢 ALBUM HEADER -->
    <div class="album-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="text-primary">
                    <i class="bi bi-collection-play me-2"></i>
                    <?php echo htmlspecialchars($album_name); ?>
                </h1>
                <div class="text-muted mb-3">
                    <i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($album_creator); ?> |
                    <i class="bi bi-calendar3 me-1"></i> <?php echo date('Y-m-d', strtotime($album_created)); ?> |
                    <i class="bi bi-images me-1"></i> <?php echo $photo_count; ?> зураг
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-house me-1"></i>Нүүр
                    </a>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Зураг нэмэх
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 🟢 PHOTO GRID -->
    <div class="photo-grid">
        <?php while($photo = mysqli_fetch_assoc($result)): ?>
            <div class="photo-item">
                <div class="photo-order"><?php echo $photo['album_order']; ?></div>
                <a href="photo.php?id=<?php echo $photo['photo_id']; ?>">
                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" 
                         alt="<?php echo htmlspecialchars($photo['title']); ?>">
                </a>
                <div class="photo-info">
                    <h6 class="mb-1"><?php echo htmlspecialchars($photo['title']); ?></h6>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-calendar3"></i> <?php echo $photo['date_taken']; ?>
                    </p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>