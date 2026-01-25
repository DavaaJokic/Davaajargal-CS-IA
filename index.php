<?php
// 🟢 Start session
session_start();

// 🟢 Include database connection
require_once "connection.php";

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
$selected_date = $_GET['date'] ?? null;
$timeline = $_GET['timeline'] ?? 'day';
$sort = $_GET['sort'] ?? 'new';
$filter = $_GET['filter'] ?? 'all'; // New: all, albums, photos
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
        $sql .= " WHERE YEARWEEK(p.date_taken, 1) = YEARWEEK('$selected_date', 1)";
        $feed_title = "📆 Photos from week of {$selected_date}";
    } else {
        $sql .= " WHERE p.date_taken = '$selected_date'";
        $feed_title = "📸 Photos from {$selected_date}";
    }
} else {
    $feed_title = "📸 Recently Added Photos";
}

// Add sorting
$sql .= " ORDER BY p.uploaded_at $order";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

/* ===========================
   ORGANIZE PHOTOS BY ALBUM
=========================== */
$albums = [];
$all_photos = [];
$single_photos = []; // Store single photos separately

while($row = mysqli_fetch_assoc($result)) {
    $all_photos[] = $row;
    
    if (!empty($row['album_id'])) {
        $album_id = $row['album_id'];
        
        if (!isset($albums[$album_id])) {
            $albums[$album_id] = [
                'album_id' => $album_id,
                'album_name' => $row['title'] . " (Album)",
                'photos' => [],
                'count' => 0,
                'date_taken' => $row['date_taken'],
                'username' => $row['username'],
                'uploaded_at' => $row['uploaded_at']
            ];
        }
        
        $albums[$album_id]['photos'][] = $row;
        $albums[$album_id]['count']++;
    } else {
        // This is a single photo (not in album)
        $single_photos[] = $row;
    }
}

/* ===========================
   SIDEBAR DATA
=========================== */
$users_sql = "SELECT username FROM users ORDER BY user_id DESC LIMIT 5";
$users_result = mysqli_query($conn, $users_sql);

// Get photo count for statistics
$stats_sql = "SELECT COUNT(*) as total_photos, 
                     COUNT(DISTINCT uploader_id) as total_users,
                     COUNT(DISTINCT album_id) as total_albums 
              FROM photos WHERE album_id IS NULL";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Family Memories</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
:root {
    --primary-gradient: linear-gradient(to right, #004d99, #007bff);
    --album-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

body {
    background: var(--primary-gradient);
    min-height: 100vh;
    padding-bottom: 60px; /* Bottom navbar space */
}

.content-container {
    background-color: rgba(255,255,255,0.95);
    border-radius: 1rem;
    box-shadow: 0 0 30px rgba(0,0,0,0.2);
}

.photo-card-img {
    height: 220px;
    object-fit: cover;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}

/* 🟢 ALBUM STYLES */
.album-container {
    background: var(--album-gradient);
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

/* 🟢 Carousel Customization */
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

/* 🟢 Photo Card */
.photo-card {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
}

.photo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* 🟢 STATIC SIDEBAR */
.static-sidebar {
    position: sticky;
    top: 80px;
    height: calc(100vh - 100px);
    overflow-y: auto;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    z-index: 90;
}

.static-sidebar .card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.static-sidebar .card-header {
    font-weight: bold;
    border-bottom: 2px solid rgba(0,0,0,0.1);
}

/* 🟢 Calendar Styles */
.static-calendar {
    background: white;
    border-radius: 8px;
    padding: 15px;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    text-align: center;
    margin-bottom: 10px;
}

.calendar-day {
    padding: 8px 5px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
}

.calendar-day:hover {
    background: #e9ecef;
    transform: scale(1.05);
}

.calendar-day.active {
    background: #007bff;
    color: white;
    font-weight: bold;
}

.calendar-day.today {
    background: #ffc107;
    color: #212529;
    font-weight: bold;
}

.calendar-day.other-month {
    color: #adb5bd;
    cursor: default;
}

.calendar-day.other-month:hover {
    background: transparent;
    transform: none;
}

/* 🟢 Statistics Cards */
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

/* 🟢 Recent Users List */
.recent-user-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f1f1;
    transition: background 0.2s;
}

.recent-user-item:hover {
    background: #f8f9fa;
    border-radius: 8px;
}

.recent-user-item:last-child {
    border-bottom: none;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 12px;
}

/* 🟢 Bottom Navigation Bar */
.bottom-navbar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    border-top: 1px solid #dee2e6;
    z-index: 1000;
    padding: 10px 0;
}

.bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #6c757d;
    transition: all 0.3s;
    padding: 5px 10px;
}

.bottom-nav-item:hover {
    color: #007bff;
}

.bottom-nav-item.active {
    color: #007bff;
    transform: translateY(-2px);
}

.bottom-nav-icon {
    font-size: 1.5rem;
    margin-bottom: 3px;
}

/* 🟢 Scrollbar Styling */
.static-sidebar::-webkit-scrollbar {
    width: 6px;
}

.static-sidebar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.static-sidebar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.static-sidebar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
</head>
<body>

<!-- ===========================
     TOP NAVBAR
=========================== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-lg">
<div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-camera2"></i> Family Memories
    </a>

    <div class="d-flex align-items-center">
        <a href="upload.php" class="btn btn-secondary btn-sm me-2 fw-bold">
            <i class="bi bi-cloud-arrow-up"></i> Upload
        </a>
        <a href="search.php" class="btn btn-secondary btn-sm me-2 fw-bold">
            <i class="bi bi-search"></i> Search
        </a>

        <?php if ($logged_in): ?>
            <a href="create_group.php" class="btn btn-warning btn-sm me-2 fw-bold">
                <i class="bi bi-plus-circle"></i> Create Group
            </a>
            <a href="profile.php" class="btn btn-outline-light btn-sm me-2 fw-bold">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($current_username) ?>
            </a>
            <a href="logout.php" class="btn btn-danger btn-sm fw-bold">
                <i class="bi bi-box-arrow-right"></i> Logout
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
    <i class="bi bi-house-heart"></i> Family Memory Dashboard
</h1>

<div class="row">

<!-- ===========================
     STATIC SIDEBAR
=========================== -->
<div class="col-lg-4 mb-4">
    <div class="static-sidebar">
        <!-- 🔍 Search by Date -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calendar3"></i> Search by Date
            </div>
            <div class="card-body p-3">
                <div class="static-calendar mb-3">
                    <div class="calendar-header">
                        <button class="btn btn-sm btn-outline-primary" onclick="prevMonth()">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <h6 class="mb-0 fw-bold" id="currentMonth">January 2026</h6>
                        <button class="btn btn-sm btn-outline-primary" onclick="nextMonth()">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    
                    <!-- Day headers -->
                    <div class="calendar-days mb-2">
                        <div class="text-danger fw-bold">Sun</div>
                        <div class="fw-bold">Mon</div>
                        <div class="fw-bold">Tue</div>
                        <div class="fw-bold">Wed</div>
                        <div class="fw-bold">Thu</div>
                        <div class="fw-bold">Fri</div>
                        <div class="text-danger fw-bold">Sat</div>
                    </div>
                    
                    <!-- Calendar days -->
                    <div class="calendar-days" id="calendarDays">
                        <?php
                        // Calendar for January 2026
                        $year = 2026;
                        $month = 1;
                        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                        $first_day = date('N', strtotime("$year-$month-01"));
                        
                        // Empty days for alignment
                        for ($i = 1; $i < $first_day; $i++) {
                            echo '<div class="calendar-day other-month">&nbsp;</div>';
                        }
                        
                        // Days of the month
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $date_str = sprintf("%04d-%02d-%02d", $year, $month, $day);
                            $is_today = ($date_str == date('Y-m-d'));
                            $is_selected = ($selected_date == $date_str);
                            $class = "calendar-day";
                            if ($is_today) $class .= " today";
                            if ($is_selected) $class .= " active";
                            
                            echo "<div class='$class' onclick=\"selectDate('$date_str')\">$day</div>";
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Date picker form -->
                <form method="GET" class="row g-2">
                    <div class="col-8">
                        <input type="date" name="date" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($selected_date ?? '') ?>"
                               onchange="this.form.submit()">
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    <input type="hidden" name="timeline" value="<?= $timeline ?>">
                    <input type="hidden" name="sort" value="<?= $sort ?>">
                    <input type="hidden" name="filter" value="<?= $filter ?>">
                </form>
            </div>
        </div>

        <!-- 📊 Statistics -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-graph-up"></i> Statistics
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_photos'] ?? 0 ?></div>
                            <div class="stat-label">Photos</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_users'] ?? 0 ?></div>
                            <div class="stat-label">Users</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_albums'] ?? 0 ?></div>
                            <div class="stat-label">Albums</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 👥 Recent Users -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-people-fill"></i> Recent Users
            </div>
            <div class="card-body p-3">
                <div class="recent-users-list">
                    <?php 
                    $user_counter = 0;
                    mysqli_data_seek($users_result, 0);
                    while($u = mysqli_fetch_assoc($users_result)): 
                        $user_counter++;
                        $initial = mb_substr($u['username'], 0, 1, 'UTF-8');
                    ?>
                        <div class="recent-user-item">
                            <div class="user-avatar">
                                <?= strtoupper($initial) ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="small text-muted">
                                    Recently registered
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark">#<?= $user_counter ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($user_counter == 0): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-people display-6"></i>
                            <p class="mt-2">No users yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===========================
     PHOTO FEED (RIGHT COLUMN - FIXED)
=========================== -->
<div class="col-lg-8">
    <!-- Timeline + Sort + Filter Controls -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded shadow-sm">
        <!-- Timeline buttons (Left side) -->
        <div class="btn-group" role="group">
            <a href="?timeline=day&date=<?= $selected_date ?>&sort=<?= $sort ?>&filter=<?= $filter ?>"
               class="btn btn-outline-primary btn-sm <?= $timeline==='day'?'active':'' ?>">
               <i class="bi bi-calendar-day"></i> Daily
            </a>
            <a href="?timeline=week&date=<?= $selected_date ?>&sort=<?= $sort ?>&filter=<?= $filter ?>"
               class="btn btn-outline-primary btn-sm <?= $timeline==='week'?'active':'' ?>">
               <i class="bi bi-calendar-week"></i> Weekly
            </a>
        </div>

        <!-- Filter buttons (Middle) -->
        <div class="btn-group" role="group">
            <a href="?filter=all&date=<?= $selected_date ?>&timeline=<?= $timeline ?>&sort=<?= $sort ?>"
               class="btn btn-outline-info btn-sm <?= $filter==='all'?'active':'' ?>">
               <i class="bi bi-grid"></i> All
            </a>
            <a href="?filter=albums&date=<?= $selected_date ?>&timeline=<?= $timeline ?>&sort=<?= $sort ?>"
               class="btn btn-outline-info btn-sm <?= $filter==='albums'?'active':'' ?>">
               <i class="bi bi-collection-play"></i> Albums
            </a>
            <a href="?filter=photos&date=<?= $selected_date ?>&timeline=<?= $timeline ?>&sort=<?= $sort ?>"
               class="btn btn-outline-info btn-sm <?= $filter==='photos'?'active':'' ?>">
               <i class="bi bi-image"></i> Photos
            </a>
        </div>

        <!-- Sort buttons (Right side) -->
        <div class="btn-group" role="group">
            <a href="?sort=new&date=<?= $selected_date ?>&timeline=<?= $timeline ?>&filter=<?= $filter ?>"
               class="btn btn-outline-secondary btn-sm <?= $sort==='new'?'active':'' ?>">
               <i class="bi bi-sort-down"></i> Newest
            </a>
            <a href="?sort=old&date=<?= $selected_date ?>&timeline=<?= $timeline ?>&filter=<?= $filter ?>"
               class="btn btn-outline-secondary btn-sm <?= $sort==='old'?'active':'' ?>">
               <i class="bi bi-sort-up"></i> Oldest
            </a>
        </div>
    </div>

    <h3 class="border-bottom pb-3 mb-4 text-primary fw-bold">
        <?= $feed_title ?>
    </h3>

    <!-- 🟢 PHOTO CONTENT WITH FILTER LOGIC -->
    <?php 
    // Check if we have any content
    $has_albums = !empty($albums);
    $has_single_photos = !empty($single_photos);
    $show_albums = ($filter === 'all' || $filter === 'albums') && $has_albums;
    $show_photos = ($filter === 'all' || $filter === 'photos') && $has_single_photos;
    ?>
    
    <?php if (empty($all_photos)): ?>
        <!-- No photos at all -->
        <div class="text-center py-5">
            <i class="bi bi-camera-off display-4 text-muted"></i>
            <h4 class="text-muted mt-3">No photos found</h4>
            <?php if ($selected_date): ?>
                <p class="text-muted">No photos uploaded on this date.</p>
            <?php endif; ?>
            <a href="upload.php" class="btn btn-primary mt-3">
                <i class="bi bi-cloud-arrow-up me-2"></i>Upload First Photo
            </a>
        </div>
    
    <?php elseif (!$show_albums && !$show_photos): ?>
        <!-- Filter shows no content -->
        <div class="text-center py-5">
            <i class="bi bi-folder-x display-4 text-muted"></i>
            <h4 class="text-muted mt-3">
                <?php if ($filter === 'albums'): ?>
                    No albums found
                <?php elseif ($filter === 'photos'): ?>
                    No single photos found
                <?php endif; ?>
            </h4>
            <p class="text-muted">Try changing your filter or upload new content</p>
            <a href="upload.php" class="btn btn-primary mt-3">
                <i class="bi bi-cloud-arrow-up me-2"></i>Upload Content
            </a>
        </div>
    
    <?php else: ?>
        <!-- We have content to show -->
        
        <?php if ($show_albums): ?>
            <!-- 🟢 ALBUMS SECTION -->
            <?php if ($filter === 'all'): ?>
                <h4 class="mb-4 text-primary">
                    <i class="bi bi-collection-play me-2"></i> Albums
                </h4>
            <?php endif; ?>
            
            <?php foreach($albums as $album_id => $album): ?>
                <div class="album-container mb-4">
                    <div class="album-header">
                        <div class="album-title">
                            <i class="bi bi-collection-play me-2"></i>
                            <?= htmlspecialchars($album['album_name']) ?>
                        </div>
                        <div class="album-count">
                            <i class="bi bi-camera me-1"></i><?= $album['count'] ?> photos
                        </div>
                    </div>
                    
                    <div class="album-info text-white mb-3">
                        <small>
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($album['username']) ?> |
                            <i class="bi bi-calendar3 me-1"></i><?= $album['date_taken'] ?>
                        </small>
                    </div>
                    
                    <!-- Carousel -->
                    <?php if ($album['count'] > 0): ?>
                        <div id="carousel-<?= $album_id ?>" class="carousel slide album-carousel" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php for($i = 0; $i < min(3, $album['count']); $i++): ?>
                                    <button type="button" data-bs-target="#carousel-<?= $album_id ?>" 
                                            data-bs-slide-to="<?= $i ?>" 
                                            class="<?= $i === 0 ? 'active' : '' ?>"></button>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="carousel-inner">
                                <?php foreach($album['photos'] as $index => $photo): ?>
                                    <?php if ($index < 5): ?>
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
                            
                            <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $album_id ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $album_id ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="view_album.php?id=<?= $album_id ?>" class="btn btn-light btn-sm">
                            <i class="bi bi-eye me-1"></i>View All Photos
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($show_photos): ?>
            <!-- 🟢 SINGLE PHOTOS SECTION -->
            <?php if ($filter === 'all' && $show_albums): ?>
                <h4 class="mb-4 text-primary mt-5">
                    <i class="bi bi-image me-2"></i> Single Photos
                </h4>
            <?php endif; ?>
            
            <div class="row">
            <?php foreach($single_photos as $row): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="photo-card shadow-sm">
                        <a href="photo.php?id=<?= $row['photo_id'] ?>">
                            <img src="<?= htmlspecialchars($row['file_path']) ?>" 
                                 class="card-img-top photo-card-img"
                                 alt="<?= htmlspecialchars($row['title']) ?>">
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
                                        <span class="badge bg-primary me-1 mb-1">
                                            <?= htmlspecialchars(trim($tag)) ?>
                                        </span>
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
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div> <!-- End col-lg-8 -->
</div> <!-- End row -->
</div> <!-- End content-container -->
</div> <!-- End container -->

<!-- ===========================
     BOTTOM NAVIGATION BAR
=========================== -->
<nav class="bottom-navbar d-lg-none">
    <div class="container">
        <div class="row justify-content-around">
            <div class="col">
                <a href="index.php" class="bottom-nav-item active">
                    <i class="bi bi-house bottom-nav-icon"></i>
                    <span class="small">Home</span>
                </a>
            </div>
            <div class="col">
                <a href="search.php" class="bottom-nav-item">
                    <i class="bi bi-search bottom-nav-icon"></i>
                    <span class="small">Search</span>
                </a>
            </div>
            <div class="col">
                <a href="upload.php" class="bottom-nav-item">
                    <i class="bi bi-cloud-arrow-up bottom-nav-icon"></i>
                    <span class="small">Upload</span>
                </a>
            </div>
            <div class="col">
                <a href="profile.php" class="bottom-nav-item">
                    <i class="bi bi-person bottom-nav-icon"></i>
                    <span class="small">Profile</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ===========================
     SCRIPTS
=========================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Date selection function
function selectDate(dateStr) {
    const url = new URL(window.location.href);
    url.searchParams.set('date', dateStr);
    // Keep existing filter
    url.searchParams.set('filter', '<?= $filter ?>');
    window.location.href = url.toString();
}

// Calendar navigation functions
function prevMonth() {
    alert('Previous month calendar function will be implemented');
}

function nextMonth() {
    alert('Next month calendar function will be implemented');
}

// Initialize carousels
document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.album-carousel');
    
    carousels.forEach(function(carousel) {
        const carouselInstance = new bootstrap.Carousel(carousel, {
            interval: 3000,
            ride: 'carousel'
        });
        
        carousel.addEventListener('mouseenter', () => carouselInstance.pause());
        carousel.addEventListener('mouseleave', () => carouselInstance.cycle());
    });
    
    // Auto-scroll for static sidebar if content is too long
    const staticSidebar = document.querySelector('.static-sidebar');
    if (staticSidebar.scrollHeight > window.innerHeight) {
        staticSidebar.style.overflowY = 'auto';
    }
});
</script>

</body>
</html>