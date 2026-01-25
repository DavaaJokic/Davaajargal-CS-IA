<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if photo ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$photo_id = intval($_GET['id']);

// Get photo details
$sql = "SELECT p.*, g.group_name, u.username, u.full_name, u.profile_picture
        FROM photos p
        JOIN groups g ON p.group_id = g.group_id
        JOIN users u ON p.uploader_id = u.user_id
        WHERE p.photo_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $photo_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

$photo = mysqli_fetch_assoc($result);

// Get comments for this photo
$comments_sql = "SELECT c.*, u.username, u.profile_picture 
                FROM comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.photo_id = ?
                ORDER BY c.commented_at DESC";
$comments_stmt = mysqli_prepare($conn, $comments_sql);
mysqli_stmt_bind_param($comments_stmt, "i", $photo_id);
mysqli_stmt_execute($comments_stmt);
$comments_result = mysqli_stmt_get_result($comments_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($photo['title']); ?> - Photo</title>
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
    
    .photo-container {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .photo-container img {
        width: 100%;
        max-height: 600px;
        object-fit: contain;
        background: #f8f9fa;
    }
    
    .photo-info {
        padding: 25px;
    }
    
    .tag-badge {
        background: #e9ecef;
        color: #495057;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-right: 5px;
        margin-bottom: 5px;
        display: inline-block;
    }
    
    .comment-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-top: 30px;
    }
    
    .comment-item {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        background: white;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    
    .comment-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
    }
    
    .comment-form {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    
    .back-button {
        text-decoration: none;
        color: #6c757d;
        transition: color 0.3s;
    }
    
    .back-button:hover {
        color: #007bff;
    }
    
    @media (max-width: 768px) {
        .photo-container img {
            max-height: 400px;
        }
        
        .photo-info {
            padding: 20px;
        }
        
        .comment-section {
            padding: 20px;
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
                <i class="bi bi-house"></i> Home
            </a>
            <a href="upload.php" class="btn btn-light btn-sm">
                <i class="bi bi-cloud-arrow-up"></i> Upload
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="content-container">
        <!-- 🟢 Back Button -->
        <div class="mb-4">
            <a href="javascript:history.back()" class="back-button">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>
        
        <!-- 🟢 Photo Display -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="photo-container">
                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" 
                         alt="<?php echo htmlspecialchars($photo['title']); ?>"
                         class="img-fluid">
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="photo-info">
                    <h2 class="mb-3"><?php echo htmlspecialchars($photo['title']); ?></h2>
                    
                    <?php if (!empty($photo['description'])): ?>
                        <p class="mb-4"><?php echo htmlspecialchars($photo['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <p class="mb-2">
                            <i class="bi bi-person text-primary me-2"></i>
                            <strong>Uploaded by:</strong> 
                            <?php echo htmlspecialchars($photo['username']); ?>
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-calendar3 text-primary me-2"></i>
                            <strong>Date taken:</strong> 
                            <?php echo date('F j, Y', strtotime($photo['date_taken'])); ?>
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-people text-primary me-2"></i>
                            <strong>Event:</strong> 
                            <?php echo htmlspecialchars($photo['group_name']); ?>
                        </p>
                        <?php if (!empty($photo['uploaded_at'])): ?>
                            <p class="mb-2">
                                <i class="bi bi-clock text-primary me-2"></i>
                                <strong>Uploaded:</strong> 
                                <?php echo date('F j, Y g:i A', strtotime($photo['uploaded_at'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($photo['tag'])): ?>
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-tags me-2"></i>Tags
                            </h6>
                            <div>
                                <?php
                                $tags = explode(',', $photo['tag']);
                                foreach ($tags as $tag):
                                    if (trim($tag) !== ''):
                                ?>
                                    <span class="tag-badge">
                                        <?php echo htmlspecialchars(trim($tag)); ?>
                                    </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($photo['album_id'])): ?>
                        <div class="mt-4 pt-3 border-top">
                            <a href="view_album.php?id=<?php echo $photo['album_id']; ?>" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-collection-play me-2"></i>
                                View Album
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 🟢 Comments Section -->
        <div class="comment-section">
            <h4 class="mb-4">
                <i class="bi bi-chat-text me-2"></i>
                Comments
                <span class="badge bg-primary ms-2">
                    <?php echo mysqli_num_rows($comments_result); ?>
                </span>
            </h4>
            
            <!-- Comment Form -->
            <div class="comment-form mb-4">
                <form action="comments.php" method="POST">
                    <input type="hidden" name="photo_id" value="<?php echo $photo_id; ?>">
                    <div class="mb-3">
                        <label for="comment_text" class="form-label">
                            <i class="bi bi-pencil me-1"></i>Add a comment
                        </label>
                        <textarea name="comment_text" class="form-control" 
                                  rows="3" 
                                  placeholder="Write your comment here..."
                                  required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Post Comment
                    </button>
                </form>
            </div>
            
            <!-- Comments List -->
            <div class="comments-list">
                <?php if (mysqli_num_rows($comments_result) > 0): ?>
                    <?php while($comment = mysqli_fetch_assoc($comments_result)): ?>
                        <div class="comment-item">
                            <div class="d-flex align-items-start">
                                <?php if (!empty($comment['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($comment['username']); ?>"
                                         class="user-avatar">
                                <?php else: ?>
                                    <div class="user-avatar bg-secondary d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($comment['username']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($comment['commented_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['is_admin'] ?? false): ?>
                                            <a href="comments.php?delete_comment=<?php echo $comment['comment_id']; ?>&photo_id=<?php echo $photo_id; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this comment?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat-dots display-4 text-muted mb-3"></i>
                        <p class="text-muted">No comments yet. Be the first to comment!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-focus comment textarea
document.addEventListener('DOMContentLoaded', function() {
    const commentTextarea = document.querySelector('textarea[name="comment_text"]');
    if (commentTextarea) {
        commentTextarea.focus();
    }
});
</script>
</body>
</html>