<?php
session_start();
include "connection.php";

/* ---------- AUTH CHECK ---------- */
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$upload_message = '';
$upload_status  = '';

/* ---------- FORM SUBMIT ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $event_name = trim($_POST["event"] ?? '');
    $tag        = trim($_POST["tag"] ?? '');
    $date_taken = $_POST["date_taken"] ?? '';
    $group_id   = $_POST["group_id"] ?? '';

    if (
        empty($event_name) ||
        empty($date_taken) ||
        empty($group_id) ||
        empty($_FILES["photo"]["name"][0])   // [MODIFIED]
    ) {
        $upload_message = "Бүх шаардлагатай талбаруудыг бөглөнө үү!";
        $upload_status  = "danger";
    } else {

        /* ---------- GET USER ID ---------- */
        $username = $_SESSION['username'];
        $result = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
        $row = mysqli_fetch_assoc($result);
        $uploader_id = $row['user_id'];

        /* ---------- ALBUM ID ---------- */
        // [ADDED] if more than one photo → album
        $album_id = count($_FILES["photo"]["name"]) > 1
            ? uniqid("album_")
            : null;

        $allowed = ["jpg", "jpeg", "png", "gif"];
        $success = 0;

        /* ---------- MULTIPLE UPLOAD LOOP ---------- */
        foreach ($_FILES["photo"]["name"] as $i => $file_name) {

            $tmp_name = $_FILES["photo"]["tmp_name"][$i];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) continue;

            // [MODIFIED] safe unique filename
            $target = "uploads/" . time() . "_" . uniqid() . "_" . basename($file_name);

            if (move_uploaded_file($tmp_name, $target)) {

                /* ---------- DATABASE INSERT ---------- */
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO photos 
                    (file_path, `event`, tag, date_taken, group_id, uploader_id, album_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssiss",
                    $target,
                    $event_name,
                    $tag,
                    $date_taken,
                    $group_id,
                    $uploader_id,
                    $album_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    $success++;
                }
            }
        }

        if ($success > 0) {
            $upload_message = "🎉 {$success} зураг амжилттай байршууллаа!";
            $upload_status  = "success";
        } else {
            $upload_message = "Зураг байршуулахад алдаа гарлаа!";
            $upload_status  = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<title>Зураг байрлуулах</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
<div class="container">
<a class="navbar-brand" href="index.php">📷 Дурсамж</a>
<a href="logout.php" class="btn btn-light btn-sm">
Гарах (<?= $_SESSION['username']; ?>)
</a>
</div>
</nav>

<div class="container">
<h3 class="text-center mb-4">Шинэ зураг нэмэх</h3>

<?php if (!empty($upload_message)): ?>
<div class="alert alert-<?= $upload_status ?>"><?= $upload_message ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="card p-4 shadow">

<!-- PHOTO -->
<div class="mb-3">
<label class="fw-bold">Зураг (олноор сонгож болно)</label>
<input type="file" name="photo[]" class="form-control" multiple required>
</div>

<!-- EVENT -->
<div class="mb-3">
<label class="fw-bold">Үйл явдал</label>
<input type="text" name="event" class="form-control" required>
</div>

<!-- TAG -->
<div class="mb-3">
<label class="fw-bold">Түлхүүр үг</label>
<input type="text" name="tag" class="form-control">
</div>

<!-- DATE -->
<div class="mb-3">
<label class="fw-bold">Огноо</label>
<input type="date" name="date_taken" value="<?= date('Y-m-d') ?>" class="form-control" required>
</div>

<!-- GROUP -->
<div class="mb-3">
<label class="fw-bold">Бүлэг</label>
<select name="group_id" class="form-select" required>
<option value="">-- Сонгох --</option>
<?php
$groups = mysqli_query($conn, "SELECT group_id, group_name FROM groups");
while ($g = mysqli_fetch_assoc($groups)) {
    echo "<option value='{$g['group_id']}'>{$g['group_name']}</option>";
}
?>
</select>
</div>

<button class="btn btn-success w-100">Байршуулах</button>
</form>
</div>
</body>
</html>
