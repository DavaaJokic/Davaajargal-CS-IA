<?php
session_start();
include "connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle comment submission
if (isset($_POST['photo_id'], $_POST['comment_text'])) {
    $photo_id = intval($_POST['photo_id']);
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_text)) {
        // Insert comment
        $stmt = mysqli_prepare($conn, "INSERT INTO comments (photo_id, user_id, comment_text, commented_at) VALUES (?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "iis", $photo_id, $user_id, $comment_text);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Redirect back to photo page
    header("Location: photo.php?id=" . $photo_id);
    exit;
}

// Handle comment deletion (optional - add if needed)
if (isset($_GET['delete_comment'])) {
    $comment_id = intval($_GET['delete_comment']);
    $user_id = $_SESSION['user_id'];
    
    // Check if user owns the comment
    $check_sql = "SELECT user_id FROM comments WHERE comment_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $comment_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_bind_result($check_stmt, $comment_user_id);
    mysqli_stmt_fetch($check_stmt);
    mysqli_stmt_close($check_stmt);
    
    if ($user_id == $comment_user_id || $_SESSION['is_admin']) {
        $delete_sql = "DELETE FROM comments WHERE comment_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $comment_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// If no action specified, redirect to home
header("Location: index.php");
exit;
?>