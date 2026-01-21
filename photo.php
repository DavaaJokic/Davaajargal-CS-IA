<?php
session_start();
include "connection.php";

// Check if photo ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$photo_id = intval($_GET['id']);

// Get photo details
$sql = "SELECT p.*, g.group_name, u.username, u.full_name 
        FROM photos p
        JOIN groups g ON p.group_id = g.group_id
        JOIN users u ON p.uploader_id = u.user_id
        WHERE p.photo_id = $photo_id";
        
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "Зураг олдсонгүй!";
    exit;
}

$photo = mysqli_fetch_assoc($result);

// Get comments for this photo
$comments_sql = "SELECT c.*, u.username 
                 FROM comments c
                 JOIN users u ON c.user_id = u.user_id
                 WHERE c.photo_id = $photo_id
                 ORDER BY c.commented_at DESC";
$comments_result = mysqli_query($conn, $comments_sql);
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($photo['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">📷 Дурсамж</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">Буцах</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Photo -->
        <div class="card mb-4">
            <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" 
                 class="card-img-top" alt="<?php echo htmlspecialchars($photo['title']); ?>">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($photo['title']); ?></h3>
                <p><?php echo htmlspecialchars($photo['description']); ?></p>
                
                <div class="text-muted mb-3">
                    <strong>Үйл явдал:</strong> <?php echo htmlspecialchars($photo['group_name']); ?><br>
                    <strong>Байршуулсан:</strong> <?php echo htmlspecialchars($photo['full_name']); ?> (@<?php echo htmlspecialchars($photo['username']); ?>)<br>
                    <strong>Огноо:</strong> <?php echo $photo['date_taken']; ?><br>
                    <?php if (!empty($photo['tag'])): ?>
                        <strong>Тэгш:</strong> <?php echo htmlspecialchars($photo['tag']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Comments -->
        <div class="card">
            <div class="card-header">
                <h5>Сэтгэгдэлүүд</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($comments_result) == 0): ?>
                    <p class="text-muted">Одоогоор сэтгэгдэл байхгүй байна.</p>
                <?php else: ?>
                    <?php while($comment = mysqli_fetch_assoc($comments_result)): ?>
                        <div class="mb-3 border-bottom pb-3">
                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                            <small class="text-muted"><?php echo $comment['commented_at']; ?></small>
                            <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                
                <!-- Comment Form -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="add_comment.php" method="POST">
                        <input type="hidden" name="photo_id" value="<?php echo $photo_id; ?>">
                        <div class="mb-3">
                            <textarea name="comment_text" class="form-control" 
                                      rows="3" placeholder="Сэтгэгдэлээ бичнэ үү..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Сэтгэгдэл илгээх</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        Сэтгэгдэл бичихийн тулд <a href="login.php">нэвтэрнэ үү</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>