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

// 🟢 Get user info
$logged_in = true;
$current_username = $_SESSION['username'];

/* ===========================
   DATE, TIMELINE & SORT
=========================== */
// Selected date from calendar
$selected_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;

// Timeline mode: day | week
$timeline = isset($_GET['timeline']) ? $_GET['timeline'] : 'day';

// Sort order
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';
$order = ($sort === 'old') ? 'ASC' : 'DESC';

/* ===========================
   GET PHOTOS FROM DATABASE
=========================== */
$sql = "SELECT p.*, g.group_name, u.username 
        FROM photos p
        JOIN groups g ON p.group_id = g.group_id
        JOIN users u ON p.uploader_id = u.user_id";

// Add date filter if selected
if ($selected_date) {
    if ($timeline === 'week') {
        // Weekly timeline filter
        $sql .= " WHERE YEARWEEK(p.date_taken, 1) = YEARWEEK('$selected_date', 1)";
        $feed_title = "📆 {$selected_date}-ны долоо хоногийн зургууд";
    } else {
        // Daily timeline
        $sql .= " WHERE p.date_taken = '$selected_date'";
        $feed_title = "📸 {$selected_date}-ны зургууд";
    }
} else {
    // No date selected
    $feed_title = "📸 Сүүлд нэмэгдсэн зургууд";
}

// Add sorting
$sql .= " ORDER BY p.uploaded_at $order";

$result = mysqli_query($conn, $sql);

// Check for errors
if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

/* ===========================
   ORGANIZE PHOTOS BY ALBUM
=========================== */
$albums = array(); // Will store albums with their photos
$all_photos = array(); // Will store all photos in order

while($row = mysqli_fetch_assoc($result)) {
    $all_photos[] = $row; // Add to all photos array
    
    if (!empty($row['album_id'])) {
        // This photo belongs to an album
        $album_id = $row['album_id'];
        
        // If this album doesn't exist in our array yet, create it
        if (!isset($albums[$album_id])) {
            $albums[$album_id] = array(
                'album_id' => $album_id,
                'album_name' => $row['title'] . " (Альбом)",
                'photos' => array(),
                'count' => 0,
                'date_taken' => $row['date_taken'],
                'username' => $row['username'],
                'uploaded_at' => $row['uploaded_at']
            );
        }
        
        // Add photo to this album
        $albums[$album_id]['photos'][] = $row;
        $albums[$album_id]['count']++;
    }
}

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
    height: 220px;
    object-fit: cover;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}
.content-container {
    background-color: rgba(255,255,255,0.95);
    border-radius: 1rem;
    box-shadow: 0 0 30px rgba(0,0,0,0.2);
}

/* 🟢 ALBUM CONTAINER STYLE */
.album-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    color: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.album-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.album-title {
    font-size: 1.3rem;
    font-weight: bold;
}
.album-count {
    background: rgba(255,255,255,0.2);
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
}

/* 🟢 Bootstrap Carousel Customization */
.album-carousel .carousel-item img {
    height: 250px;
    object-fit: cover;
    border-radius: 10px;
}
.album-carousel .carousel-caption {
    background: rgba(0,0,0,0.5);
    border-radius: 10px;
    padding: 8px;
    bottom: 20px;
}
.album-carousel .carousel-caption h6 {
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* 🟢 Single photo card */
.photo-card {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s;
    height: 100%;
}
.photo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                    <i class="bi bi-person-fill text-warning"></i> <?= htmlspecialchars($u['username']) ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>

<!-- ===========================
     PHOTO FEED
=========================== -->
<div class="col-lg-8">

<!-- Timeline + Sort Controls -->
<div class="d-flex justify-content-between mb-3">
    <div>
        <a href="?timeline=day<?= $selected_date ? '&date='.$selected_date : '' ?>&sort=<?= $sort ?>"
           class="btn btn-outline-primary btn-sm <?= $timeline==='day'?'active':'' ?>">
           Өдрөөр
        </a>
        <a href="?timeline=week<?= $selected_date ? '&date='.$selected_date : '' ?>&sort=<?= $sort ?>"
           class="btn btn-outline-primary btn-sm <?= $timeline==='week'?'active':'' ?>">
           Долоо хоногоор
        </a>
    </div>

    <div>
        <a href="?sort=new<?= $selected_date ? '&date='.$selected_date.'&timeline='.$timeline : '' ?>"
           class="btn btn-outline-secondary btn-sm <?= $sort==='new'?'active':'' ?>">
            Шинэ → Хуучин
        </a>
        <a href="?sort=old<?= $selected_date ? '&date='.$selected_date.'&timeline='.$timeline : '' ?>"
           class="btn btn-outline-secondary btn-sm <?= $sort==='old'?'active':'' ?>">
            Хуучин → Шинэ
        </a>
    </div>
</div>

<h3 class="border-bottom pb-2 mb-4 text-primary fw-bold">
    <?= $feed_title ?>
</h3>

<!-- 🟢 MIXED FEED - Albums and Photos Together -->
<?php if (empty($all_photos)): ?>
    <div class="col-12 text-center py-5">
        <i class="bi bi-camera-off display-4 text-muted"></i>
        <h4 class="text-muted mt-3">Зураг олдсонгүй</h4>
        <?php if ($selected_date): ?>
            <p class="text-muted">Энэ өдөр зураг байршуулаагүй байна.</p>
        <?php else: ?>
            <p class="text-muted">Одоогоор зураг байхгүй байна.</p>
        <?php endif; ?>
        <a href="upload.php" class="btn btn-primary mt-3">
            <i class="bi bi-cloud-arrow-up me-2"></i>Эхний зураг байршуулах
        </a>
    </div>
<?php else: ?>
    <?php
    $displayed_albums = array(); // Track which albums we've displayed
    $album_shown = false;
    
    // First display albums
    foreach($all_photos as $row): 
        // Check if this photo is part of an album we haven't displayed yet
        if (!empty($row['album_id']) && !in_array($row['album_id'], $displayed_albums)):
            $album_id = $row['album_id'];
            $album = $albums[$album_id];
            $displayed_albums[] = $album_id;
            $album_shown = true;
    ?>
            <!-- 🟢 ALBUM DISPLAY - Full width -->
            <div class="album-container mb-4">
                <div class="album-header">
                    <div class="album-title">
                        <i class="bi bi-collection-play me-2"></i><?= htmlspecialchars($album['album_name']) ?>
                    </div>
                    <div class="album-count">
                        <i class="bi bi-camera me-1"></i><?= $album['count'] ?> зураг
                    </div>
                </div>
                
                <div class="album-info text-white mb-3">
                    <small>
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($album['username']) ?> |
                        <i class="bi bi-calendar3 me-1"></i><?= $album['date_taken'] ?>
                    </small>
                </div>
                
                <!-- 🟢 BOOTSTRAP CAROUSEL FOR ALBUM -->
                <?php if ($album['count'] > 0): ?>
                    <div id="carousel-<?= $album_id ?>" class="carousel slide album-carousel" data-bs-ride="carousel">
                        <!-- Carousel Indicators -->
                        <div class="carousel-indicators">
                            <?php for($i = 0; $i < min(3, $album['count']); $i++): ?>
                                <button type="button" data-bs-target="#carousel-<?= $album_id ?>" 
                                        data-bs-slide-to="<?= $i ?>" 
                                        class="<?= $i === 0 ? 'active' : '' ?>" 
                                        aria-current="<?= $i === 0 ? 'true' : 'false' ?>">
                                </button>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- Carousel Items -->
                        <div class="carousel-inner">
                            <?php foreach($album['photos'] as $index => $photo): ?>
                                <?php if ($index < 5): // Limit to 5 photos in carousel ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <a href="photo.php?id=<?= $photo['photo_id'] ?>">
                                            <img src="<?= htmlspecialchars($photo['file_path']) ?>" 
                                                 class="d-block w-100" 
                                                 alt="<?= htmlspecialchars($photo['title']) ?>">
                                        </a>
                                        <div class="carousel-caption d-none d-md-block">
                                            <h6><?= htmlspecialchars($photo['title']) ?></h6>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Carousel Controls -->
                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $album_id ?>" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Өмнөх</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $album_id ?>" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Дараах</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Album view all link -->
                <div class="text-center mt-3">
                    <a href="view_album.php?id=<?= $album_id ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-eye me-1"></i>Бүх зургуудыг харах
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <!-- 🟢 SINGLE PHOTOS DISPLAY - Now in a row -->
    <div class="row"> <!-- ADD THIS ROW CONTAINER -->
    <?php
    // Display single photos
    foreach($all_photos as $row): 
        if (empty($row['album_id'])): // Only single photos
    ?>
            <!-- 🟢 SINGLE PHOTO DISPLAY - 3 per row -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="photo-card shadow-sm">
                    <a href="photo.php?id=<?= $row['photo_id'] ?>">
                        <img src="<?= htmlspecialchars($row['file_path']) ?>" class="card-img-top photo-card-img">
                    </a>
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($row['title']) ?></h6>
                        <p class="small text-muted mb-1">
                            <i class="bi bi-person"></i> <?= htmlspecialchars($row['username']) ?>
                        </p>
                        <?php if (!empty($row['tag'])): ?>
                            <div class="mb-2">
                                <?php
                                $tags = explode(',', $row['tag']);
                                foreach ($tags as $tag):
                                    if (trim($tag) !== ''):
                                ?>
                                    <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars(trim($tag)) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                        <p class="small text-muted mb-0">
                            <i class="bi bi-calendar3"></i> <?= $row['date_taken'] ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    </div> <!-- END ROW -->
<?php endif; ?>

</div> <!-- End photo feed -->
</div> <!-- End row -->
</div> <!-- End content container -->
</div> <!-- End main container -->
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
    
    // 🟢 Auto-play carousels
    var carousels = document.querySelectorAll('.album-carousel');
    carousels.forEach(function(carousel) {
        // Start auto-slide
        var carouselInstance = new bootstrap.Carousel(carousel, {
            interval: 3000, // 3 seconds
            ride: 'carousel'
        });
        
        // Pause on hover
        carousel.addEventListener('mouseenter', function() {
            carouselInstance.pause();
        });
        
        carousel.addEventListener('mouseleave', function() {
            carouselInstance.cycle();
        });
    });
});
</script>

</body>
</html>