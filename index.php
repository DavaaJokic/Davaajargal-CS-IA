<?php
session_start();
include "connection.php";

/* ===========================
   LOGIN CHECK
=========================== */
$logged_in = isset($_SESSION['user_id']);
$current_username = $logged_in ? $_SESSION['username'] : 'Нэвтрэх';

/* ===========================
   DATE, TIMELINE & SORT
=========================== */
// Selected date from calendar
$selected_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;

// [ADDED] Timeline mode: day | week
$timeline = isset($_GET['timeline']) ? $_GET['timeline'] : 'day';

// [ADDED] Sort order
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';
$order = ($sort === 'old') ? 'ASC' : 'DESC';

/* ===========================
   BASE PHOTO QUERY
=========================== */
$photo_sql_template = "SELECT 
    p.photo_id, 
    p.file_path, 
    g.group_name AS event, 
    p.tag, 
    p.date_taken, 
    u.username AS uploader_name
FROM photos p
JOIN groups g ON p.group_id = g.group_id
JOIN users u ON p.uploader_id = u.user_id";

/* ===========================
   DATE FILTER + TIMELINE
=========================== */
if ($selected_date) {

    if ($timeline === 'week') {
        // [ADDED] Weekly timeline filter
        $photo_sql = $photo_sql_template . "
            WHERE YEARWEEK(p.date_taken, 1) = YEARWEEK(?, 1)
            ORDER BY p.date_taken $order";
        $feed_title = "📆 {$selected_date}-ны долоо хоногийн зургууд";
    } else {
        // [MODIFIED] Daily timeline
        $photo_sql = $photo_sql_template . "
            WHERE p.date_taken = ?
            ORDER BY p.date_taken $order";
        $feed_title = "📸 {$selected_date}-ны зургууд";
    }

    $stmt = mysqli_prepare($conn, $photo_sql);
    mysqli_stmt_bind_param($stmt, "s", $selected_date);

} else {
    // [MODIFIED] Default feed
    $photo_sql = $photo_sql_template . "
        ORDER BY p.date_taken $order
        LIMIT 12";
    $feed_title = "📸 Сүүлд нэмэгдсэн зургууд";
    $stmt = mysqli_prepare($conn, $photo_sql);
}

mysqli_stmt_execute($stmt);
$photo_result = mysqli_stmt_get_result($stmt);

/* ===========================
   SIDEBAR USERS
=========================== */
$users_sql = "SELECT username FROM users ORDER BY user_id DESC LIMIT 5";
$users_result = mysqli_query($conn, $users_sql);
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Гэр Бүлийн Дурсамж</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/main.min.css' rel='stylesheet' />

<style>
body {
    background: linear-gradient(to right, #004d99, #007bff);
    min-height: 100vh;
}
.photo-card-img {
    height: 220px; /* [MODIFIED] uniform photo size */
    object-fit: cover;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}
.content-container {
    background-color: rgba(255,255,255,0.95);
    border-radius: 1rem;
    box-shadow: 0 0 30px rgba(0,0,0,0.2);
}
.comment-toggle {
    cursor: pointer;
    color: #0d6efd;
    text-decoration: underline;
}
</style>
</head>
<body>

<!-- ===========================
     NAVBAR
=========================== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-lg">
<div class="container">
    <a class="navbar-brand fw-bold" href="index.php">📷 Дурсамж</a>

    <div class="d-flex align-items-center">
        <a href="upload.php" class="btn btn-secondary btn-sm me-2 fw-bold">
            <i class="bi bi-cloud-arrow-up"></i> Байрлуулах
        </a>
        <a href="search.php" class="btn btn-secondary btn-sm me-2 fw-bold">
            <i class="bi bi-search"></i> Хайх
        </a>

        <?php if ($logged_in): ?>
            <a href="create_group.php" class="btn btn-warning btn-sm me-2 fw-bold">
                <i class="bi bi-plus-circle"></i> Групп
            </a>
            <a href="profile.php" class="btn btn-outline-light btn-sm me-2 fw-bold">
                <i class="bi bi-person-circle"></i> Профайл
            </a>
            <a href="logout.php" class="btn btn-danger btn-sm fw-bold">
                <i class="bi bi-box-arrow-right"></i> Гарах
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-success btn-sm fw-bold">
                <i class="bi bi-box-arrow-in-right"></i> Нэвтрэх
            </a>
        <?php endif; ?>
    </div>
</div>
</nav>

<!-- ===========================
     MAIN CONTENT
=========================== -->
<div class="container py-5">
<div class="content-container p-4 p-md-5">

<h1 class="text-center mb-5 text-primary fw-light">
    Гэр бүлийн дурсамжийн самбар
</h1>

<div class="row">

<!-- ===========================
     SIDEBAR
=========================== -->
<div class="col-lg-4 mb-4">

    <!-- Calendar -->
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-secondary text-white fw-bold">
            <i class="bi bi-calendar3"></i> Огноогоор хайх
        </div>
        <div class="card-body">
            <div id="photoCalendar"></div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="card shadow-lg">
        <div class="card-header bg-success text-white fw-bold">
            <i class="bi bi-people-fill"></i> Шинэ хэрэглэгчид
        </div>
        <ul class="list-group list-group-flush">
            <?php while($u = mysqli_fetch_assoc($users_result)): ?>
                <li class="list-group-item">
                    <i class="bi bi-person-fill text-warning"></i> <?= $u['username'] ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>

<!-- ===========================
     PHOTO FEED
=========================== -->
<div class="col-lg-8">

<!-- [ADDED] Timeline + Sort Controls -->
<div class="d-flex justify-content-between mb-3">
    <div>
        <a href="?timeline=day<?= $selected_date ? '&date='.$selected_date : '' ?>"
           class="btn btn-outline-primary btn-sm <?= $timeline==='day'?'active':'' ?>">
           Өдрөөр
        </a>
        <a href="?timeline=week<?= $selected_date ? '&date='.$selected_date : '' ?>"
           class="btn btn-outline-primary btn-sm <?= $timeline==='week'?'active':'' ?>">
           Долоо хоногоор
        </a>
    </div>

    <div>
        <a href="?sort=new" class="btn btn-outline-secondary btn-sm <?= $sort==='new'?'active':'' ?>">
            Шинэ → Хуучин
        </a>
        <a href="?sort=old" class="btn btn-outline-secondary btn-sm <?= $sort==='old'?'active':'' ?>">
            Хуучин → Шинэ
        </a>
    </div>
</div>

<h3 class="border-bottom pb-2 mb-4 text-primary fw-bold">
    <?= $feed_title ?>
</h3>

<div class="row g-3">

<?php
$last_date = ''; // [ADDED] Timeline header
while($row = mysqli_fetch_assoc($photo_result)):
    if ($row['date_taken'] !== $last_date):
        $last_date = $row['date_taken'];
?>
    <div class="col-12">
        <h5 class="mt-4 text-secondary border-bottom pb-1">
            📅 <?= $last_date ?>
        </h5>
    </div>
<?php endif; ?>

<div class="col-md-6 col-xl-4">

    <a href="photo.php?id=<?= $row['photo_id'] ?>">
        <img src="<?= $row['file_path'] ?>" class="img-fluid photo-card-img">
    </a>

    <div class="small text-muted mt-1">
        <i class="bi bi-person"></i> <?= $row['uploader_name'] ?>
    </div>

    <span class="badge bg-primary"><?= $row['tag'] ?></span>

</div>
<?php endwhile; ?>

</div>
</div>
</div>
</div>

<!-- ===========================
     SCRIPTS
=========================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('photoCalendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'mn',
        dateClick: function(info) {
            window.location.href = 'index.php?date=' + info.dateStr;
        }
    });

    calendar.render();
});
</script>

</body>
</html>
