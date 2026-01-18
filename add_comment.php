<?php
session_start();
include "connection.php";

if (isset($_POST['photo_id'], $_POST['comment_text'])) {
    $photo_id = intval($_POST['photo_id']);
    $comment_text = trim($_POST['comment_text']);

    if (!empty($comment_text)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO comments (photo_id, comment_text, commented_at) VALUES (?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "is", $photo_id, $comment_text);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
