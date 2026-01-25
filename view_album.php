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
$album_creator = $first_photo['full_name'] ?? $first_photo['username'];
$album_created = $first_photo['album_created'];
$photo_count = mysqli_num_rows($result) + 1; // +1 because we already fetched one

// Reset pointer to beginning
mysqli_data_seek($result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($album_name); ?> - Album</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    :root {
        --primary-gradient: linear-gradient(to right, #004d99, #007bff);
        --album-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    
    .album-header-container {
        background: var(--album-gradient);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    
    .photo-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .photo-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .photo-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-bottom: 1px solid #eee;
    }
    
    .photo-info {
        padding: 15px;
    }
    
    .photo-order {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(0,0,0,0.7);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    
    .album-stats {
        display: flex;
        gap: 20px;
        margin-top: 15px;
    }
    
    .stat-badge {
        background: rgba(255,255,255,0.2);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .empty-album {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .empty-album i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #ddd;
    }
    
    @media (max-width: 768px) {
        .photo-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .album-header-container {
            padding: 20px;
        }
        
        .album-stats {
            flex-direction: column;
            gap: 10px;
        }
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
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="upload.php" class="btn btn-light btn-sm">
                <i class="bi bi-cloud-arrow-up"></i> Upload
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="content-container">
        <!-- 🟢 ALBUM HEADER -->
        <div class="album-header-container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3">
                        <i class="bi bi-collection-play me-2"></i>
                        <?php echo htmlspecialchars($album_name); ?>
                    </h1>
                    
                    <div class="album-stats">
                        <div class="stat-badge">
                            <i class="bi bi-person"></i>
                            <span>Created by: <?php echo htmlspecialchars($album_creator); ?></span>
                        </div>
                        <div class="stat-badge">
                            <i class="bi bi-calendar3"></i>
                            <span><?php echo date('F j, Y', strtotime($album_created)); ?></span>
                        </div>
                        <div class="stat-badge">
                            <i class="bi bi-images"></i>
                            <span><?php echo $photo_count; ?> photos</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="btn-group" role="group">
                        <a href="index.php" class="btn btn-outline-light">
                            <i class="bi bi-house me-1"></i> Home
                        </a>
                        <a href="upload.php" class="btn btn-light">
                            <i class="bi bi-plus-circle me-1"></i> Add Photos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🟢 PHOTO GRID -->
        <?php if ($photo_count > 0): ?>
            <div class="photo-grid">
                <?php while($photo = mysqli_fetch_assoc($result)): ?>
                    <div class="photo-item">
                        <div class="photo-order"><?php echo $photo['album_order']; ?></div>
                        <a href="photo.php?id=<?php echo $photo['photo_id']; ?>" class="text-decoration-none">
                            <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['title']); ?>"
                                 class="img-fluid">
                            <div class="photo-info">
                                <h6 class="text-dark mb-2"><?php echo htmlspecialchars($photo['title']); ?></h6>
                                <p class="small text-muted mb-1">
                                    <i class="bi bi-person"></i> 
                                    <?php echo htmlspecialchars($photo['username']); ?>
                                </p>
                                <?php if (!empty($photo['tag'])): ?>
                                    <div class="mb-2">
                                        <?php
                                        $tags = explode(',', $photo['tag']);
                                        foreach ($tags as $tag):
                                            if (trim($tag) !== ''):
                                        ?>
                                            <span class="badge bg-primary me-1 mb-1">
                                                <?php echo htmlspecialchars(trim($tag)); ?>
                                            </span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-calendar3"></i> 
                                    <?php echo date('M j, Y', strtotime($photo['date_taken'])); ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-album">
                <i class="bi bi-images"></i>
                <h3 class="mb-3">No photos in this album</h3>
                <p class="text-muted mb-4">This album is empty. Start by adding some photos!</p>
                <a href="upload.php" class="btn btn-primary">
                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload Photos
                </a>
            </div>
        <?php endif; ?>
        
        <!-- 🟢 Back to Home Button -->
        <div class="text-center mt-5 pt-4 border-top">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
    const photoItems = document.querySelectorAll('.photo-item');
    
    photoItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.zIndex = '10';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.zIndex = '1';
        });
    });
});
</script>
</body>
</html>