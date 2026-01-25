<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$upload_message = '';
$upload_status = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $tag = trim($_POST["tag"]);
    $date_taken = $_POST["date_taken"];
    $group_id = $_POST["group_id"];
    
    if (empty($title) || empty($date_taken) || empty($group_id)) {
        $upload_message = '<div class="alert alert-danger">Please fill all required fields!</div>';
        $upload_status = 'danger';
    } else {
        $file_count = count($_FILES['photo']['name']);
        $album_id = null;
        $album_name = null;
        
        if ($file_count > 1) {
            $album_id = 'ALB_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6);
            $album_name = $title . ' (Album)';
            
            $album_sql = "INSERT INTO albums (album_id, album_name, created_by) VALUES (?, ?, ?)";
            $album_stmt = mysqli_prepare($conn, $album_sql);
            mysqli_stmt_bind_param($album_stmt, "ssi", $album_id, $album_name, $user_id);
            
            if (!mysqli_stmt_execute($album_stmt)) {
                $upload_message = '<div class="alert alert-danger">Album creation error: ' . mysqli_error($conn) . '</div>';
                $upload_status = 'danger';
                exit;
            }
            mysqli_stmt_close($album_stmt);
        }
        
        $success_count = 0;
        $album_order = 0;
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['photo']['error'][$i] != 0) continue;
            
            $file_name = $_FILES['photo']['name'][$i];
            $file_tmp = $_FILES['photo']['tmp_name'][$i];
            $file_size = $_FILES['photo']['size'][$i];
            
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed)) {
                $upload_message .= '<div class="alert alert-warning">' . $file_name . ' file type not allowed</div>';
                continue;
            }
            
            if ($file_size > 5000000) {
                $upload_message .= '<div class="alert alert-warning">' . $file_name . ' file too large (max 5MB)</div>';
                continue;
            }
            
            $safe_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $file_name);
            $new_filename = time() . '_' . $user_id . '_' . uniqid() . '_' . $safe_name;
            $target_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                $album_order++;
                
                if ($album_id) {
                    $sql = "INSERT INTO photos (file_path, title, description, tag, date_taken, group_id, uploader_id, album_id, album_order) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssssiisi", 
                        $target_path, $title, $description, $tag, $date_taken, $group_id, $user_id, $album_id, $album_order);
                } else {
                    $sql = "INSERT INTO photos (file_path, title, description, tag, date_taken, group_id, uploader_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssssii", 
                        $target_path, $title, $description, $tag, $date_taken, $group_id, $user_id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_count++;
                } else {
                    $upload_message .= '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
                }
                
                mysqli_stmt_close($stmt);
            } else {
                $upload_message .= '<div class="alert alert-danger">' . $file_name . ' file save error!</div>';
            }
        }
        
        if ($success_count > 0) {
            if ($album_id) {
                $message = "🎉 {$success_count} photos successfully saved as album!";
            } else {
                $message = "✅ Photo uploaded successfully!";
            }
            $upload_message = '<div class="alert alert-success">' . $message . '</div>';
            $upload_status = 'success';
        } else {
            $upload_message = '<div class="alert alert-danger">No photos were uploaded!</div>';
            $upload_status = 'danger';
        }
    }
}

$groups_result = mysqli_query($conn, "SELECT * FROM groups");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Photos</title>
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
        
        .file-upload-area {
            border: 3px dashed #adb5bd;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .file-upload-area:hover {
            border-color: #0d6efd;
            background: #e9ecef;
        }
        
        .form-label {
            font-weight: bold;
            color: #333;
        }
        
        .upload-btn {
            background: linear-gradient(to right, #0d6efd, #6610f2);
            border: none;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            color: white;
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
            <span class="text-white me-3">
                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?>
            </span>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">Home</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="content-container">
                <h2 class="text-center mb-4 text-primary">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i>
                    Upload New Photos
                </h2>
                
                <?php echo $upload_message; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <!-- File Upload -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-image me-1"></i>Select Photos
                        </label>
                        <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-cloud-arrow-up display-4 text-muted mb-3"></i>
                            <h5>Drag & drop photos here or click to select</h5>
                            <p class="text-muted">
                                Supported formats: JPG, PNG, GIF<br>
                                Max 5MB per file<br>
                                Multiple files will create an album automatically
                            </p>
                        </div>
                        <input type="file" name="photo[]" id="fileInput" 
                               class="form-control d-none" multiple required 
                               onchange="showSelectedFiles(this)">
                        <div id="fileInfo" class="mt-2"></div>
                    </div>
                    
                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-heading me-1"></i>Photo Title
                        </label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="Example: Birthday Party">
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-text-paragraph me-1"></i>Description
                        </label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Photo description..."></textarea>
                    </div>
                    
                    <!-- Tags -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tags me-1"></i>Tags
                        </label>
                        <input type="text" name="tag" class="form-control" 
                               placeholder="family, friends, vacation, party">
                        <small class="text-muted">Separate with commas</small>
                    </div>
                    
                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-calendar-date me-1"></i>Photo Date
                        </label>
                        <input type="date" name="date_taken" class="form-control" required
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <!-- Group Selection -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-people me-1"></i>Event (Group)
                        </label>
                        <select name="group_id" class="form-select" required>
                            <option value="">-- Select a Group --</option>
                            <?php while($group = mysqli_fetch_assoc($groups_result)): ?>
                                <option value="<?php echo $group['group_id']; ?>">
                                    <?php echo htmlspecialchars($group['group_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="mt-2">
                            <a href="create_group.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>Create New Group
                            </a>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn upload-btn w-100">
                        <i class="bi bi-cloud-check me-2"></i>UPLOAD PHOTOS
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showSelectedFiles(input) {
    const files = input.files;
    const fileInfo = document.getElementById('fileInfo');
    
    if (files.length === 0) {
        fileInfo.innerHTML = '';
        return;
    }
    
    let info = `<strong>${files.length} photos</strong> selected:`;
    
    const maxShow = Math.min(files.length, 3);
    for (let i = 0; i < maxShow; i++) {
        info += `<br>• ${files[i].name}`;
    }
    
    if (files.length > 3) {
        info += `<br>... and ${files.length - 3} more`;
    }
    
    if (files.length > 1) {
        info += `<br><br><span class="text-success">
                 <i class="bi bi-info-circle"></i> 
                 Multiple photos selected - <strong>album</strong> will be created automatically
                 </span>`;
    }
    
    fileInfo.innerHTML = info;
}

// Drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.querySelector('.file-upload-area');
    const fileInput = document.getElementById('fileInput');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropArea.style.borderColor = '#0d6efd';
        dropArea.style.background = '#e9ecef';
    }
    
    function unhighlight() {
        dropArea.style.borderColor = '#adb5bd';
        dropArea.style.background = '#f8f9fa';
    }
    
    dropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        const event = new Event('change', { bubbles: true });
        fileInput.dispatchEvent(event);
        showSelectedFiles(fileInput);
    }
});
</script>

</body>
</html>