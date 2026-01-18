<?php
session_start();
include "connection.php";


if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$photo_id = intval($_GET['id']);


$stmt = mysqli_prepare($conn, "SELECT p.file_path, g.group_name AS event, p.tag, p.date_taken, u.username AS uploader_name
                                FROM photos p
                                JOIN groups g ON p.group_id = g.group_id
                                JOIN users u ON p.uploader_id = u.user_id
                                WHERE p.photo_id = ?");
mysqli_stmt_bind_param($stmt, "i", $photo_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo "Зураг олдсонгүй!";
    exit;
}

$photo = mysqli_fetch_assoc($result);
$uploader_display = $photo['uploader_name'] ?? 'Unknown';
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title><?= $photo['event'] ?> - Дурсамж</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>



<body >


<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg sticky-top">
    <div class="container-fluid container">
        <a class="navbar-brand fs-4 fw-bold" href="index.php">📷 **Дурсамж**</a>
        
        
   
    </div>
</nav>

<div class="container py-5">

    <h3 class="mb-4"><?= $photo['event'] ?></h3>

    <div class="card mb-4">
       <img src="<?= $photo['file_path'] ?>" class="img-fluid rounded" alt="<?= $photo['event'] ?>">

        <div class="card-body">
            <p><strong>Байршуулсан:</strong> <?= $uploader_display ?></p>
            <p><strong>Огноо:</strong> <?= $photo['date_taken'] ?></p>
            <p><strong>Tag:</strong> <?= $photo['tag'] ?></p>
        </div>
    </div>


    <?php if (isset($_SESSION['user_id'])): ?>
    <form action="add_comment.php" method="POST" class="mb-4">
        <input type="hidden" name="photo_id" value="<?= $photo_id ?>">
        <div class="input-group">
            <input type="text" name="comment_text" class="form-control" placeholder="Сэтгэгдэл бичих..." required>
            <button class="btn btn-primary" type="submit">    <i class="bi bi-send"></i></button>
        </div>
    </form>
    <?php else: ?>
        <p class="text-muted">Сэтгэгдэл бичихийн тулд нэвтэрнэ үү.</p>
    <?php endif; ?>


    <h5>Сэтгэгдэлүүд</h5>
    <?php
    $comments_query = mysqli_query($conn, "SELECT comment_text, commented_at FROM comments WHERE photo_id = {$photo_id} ORDER BY commented_at ASC");
    if (mysqli_num_rows($comments_query) == 0) {
        echo "<p class='text-muted'>Одоогоор сэтгэгдэл байхгүй байна.</p>";
    } else {
        while ($comment = mysqli_fetch_assoc($comments_query)):
            $comment_text = htmlspecialchars($comment['comment_text']);
            $commented_at = $comment['commented_at'];
    ?>
    <div class="p-2 mb-2 border rounded bg-light">
        <?= $comment_text ?>
        <div class="text-end" style="font-size:0.8rem; color:#555;"><?= $commented_at ?></div>
    </div>
    <?php
        endwhile;
    }
    ?>

</div>
</body>
</html>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
