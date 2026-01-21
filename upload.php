<?php
// 🟢 SESSION START: Initialize session to store user login information
// This allows us to remember the user across different pages
session_start();

// 🟢 DATABASE CONNECTION: Include the file that connects to MySQL database
// This file has $conn variable that we can use to run SQL queries
include "connection.php";

// 🟢 AUTHENTICATION CHECK: Make sure user is logged in before uploading
// $_SESSION['user_id'] is set when user logs in successfully
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit; // Stop script execution
}

// 🟢 GET USER INFO FROM SESSION: Get data stored during login
// We store user info in session when they login
$username = $_SESSION['username'];  // User's login name
$user_id = $_SESSION['user_id'];    // User's unique ID in database

// 🟢 VARIABLES FOR MESSAGES: To show success/error messages to user
$upload_message = '';  // Will store the message text
$upload_status = '';   // Will store type: 'success' or 'danger' for Bootstrap styling

// =============================================
// 🟢 HANDLE FORM SUBMISSION
// =============================================
// $_SERVER["REQUEST_METHOD"] tells us how the page was accessed
// "POST" means form was submitted (as opposed to "GET" for normal page load)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 🟡 GET FORM DATA: Collect data user entered in the form
    // trim() removes extra spaces from beginning and end
    // $_POST["field_name"] gets data from form inputs
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $tag = trim($_POST["tag"]);
    $date_taken = $_POST["date_taken"];
    $group_id = $_POST["group_id"];
    
    // 🟡 VALIDATE REQUIRED FIELDS: Check if user filled all required fields
    if (empty($title) || empty($date_taken) || empty($group_id)) {
        // empty() checks if variable is empty (null, 0, "", false, [])
        $upload_message = '<div class="alert alert-danger">Бүх шаардлагатай талбаруудыг бөглөнө үү!</div>';
        $upload_status = 'danger';
    } else {
        // 🟡 COUNT UPLOADED FILES: Check how many photos user selected
        // $_FILES['photo']['name'] is an array containing all file names
        $file_count = count($_FILES['photo']['name']);
        
        // 🟡 ALBUM LOGIC: If multiple files, create album
        $album_id = null;     // Will store album ID if album is created
        $album_name = null;   // Will store album name
        
        if ($file_count > 1) {
            // CREATE ALBUM ID: Generate unique ID for the album
            // date('Ymd_His') gives current date and time: 20241225153045
            // uniqid() generates unique identifier
            // substr(md5(uniqid()), 0, 6) creates 6-character random string
            $album_id = 'ALB_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6);
            $album_name = $title . ' (Альбом)'; // Album name = photo title + "(Альбом)"
            
            // 🟡 CREATE ALBUM RECORD IN DATABASE (IMPORTANT!)
            // We need to create album FIRST before adding photos to it
            $album_sql = "INSERT INTO albums (album_id, album_name, created_by) VALUES (?, ?, ?)";
            
            // PREPARED STATEMENT: Prevents SQL injection attacks
            // "ssi" means: string, string, integer (types of parameters)
            $album_stmt = mysqli_prepare($conn, $album_sql);
            mysqli_stmt_bind_param($album_stmt, "ssi", $album_id, $album_name, $user_id);
            
            // EXECUTE THE QUERY
            if (!mysqli_stmt_execute($album_stmt)) {
                $upload_message = '<div class="alert alert-danger">Альбом үүсгэхэд алдаа гарлаа: ' . mysqli_error($conn) . '</div>';
                $upload_status = 'danger';
                exit; // Stop if album creation fails
            }
            mysqli_stmt_close($album_stmt); // Close the statement to free memory
        }
        
        // 🟡 VARIABLES FOR UPLOAD TRACKING
        $success_count = 0;   // Count successfully uploaded files
        $album_order = 0;     // Track order of photos in album
        
        // 🟡 LOOP THROUGH EACH UPLOADED FILE
        // We use for loop to process each file one by one
        for ($i = 0; $i < $file_count; $i++) {
            // Skip if file upload had error
            // $_FILES['photo']['error'][$i] = 0 means no error
            if ($_FILES['photo']['error'][$i] != 0) continue;
            
            // Get file information
            $file_name = $_FILES['photo']['name'][$i];       // Original filename
            $file_tmp = $_FILES['photo']['tmp_name'][$i];    // Temporary storage location
            $file_size = $_FILES['photo']['size'][$i];       // File size in bytes
            
            // 🟡 CHECK FILE TYPE (SECURITY)
            $allowed = ['jpg', 'jpeg', 'png', 'gif']; // Allowed extensions
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            // pathinfo() extracts file extension
            // strtolower() converts to lowercase (JPG → jpg)
            
            if (!in_array($file_ext, $allowed)) {
                // If file type not allowed, show warning and skip this file
                $upload_message .= '<div class="alert alert-warning">' . $file_name . ' файлын төрөл зөвшөөрөгдөөгүй</div>';
                continue; // Skip to next file in loop
            }
            
            // 🟡 CHECK FILE SIZE (5MB = 5,000,000 bytes)
            if ($file_size > 5000000) {
                $upload_message .= '<div class="alert alert-warning">' . $file_name . ' файл хэт том байна (5MB хүртэл)</div>';
                continue; // Skip to next file
            }
            
            // 🟡 CREATE SAFE FILENAME
            // Remove special characters to prevent security issues
            $safe_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $file_name);
            // Create unique filename to avoid overwriting existing files
            $new_filename = time() . '_' . $user_id . '_' . uniqid() . '_' . $safe_name;
            $target_path = 'uploads/' . $new_filename; // Final destination
            
            // 🟡 MOVE UPLOADED FILE
            // move_uploaded_file() moves from temp location to permanent location
            if (move_uploaded_file($file_tmp, $target_path)) {
                $album_order++; // Increase order number for album photos
                
                // 🟡 PREPARE SQL QUERY FOR PHOTO
                // Different queries for album vs single photo
                if ($album_id) {
                    // FOR ALBUM PHOTOS: Include album_id and album_order
                    $sql = "INSERT INTO photos (file_path, title, description, tag, date_taken, group_id, uploader_id, album_id, album_order) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    // "sssssiisi" = 9 parameters: file_path(string), title(string), description(string), 
                    // tag(string), date_taken(string), group_id(int), uploader_id(int), album_id(string), album_order(int)
                    mysqli_stmt_bind_param($stmt, "sssssiisi", 
                        $target_path, $title, $description, $tag, $date_taken, $group_id, $user_id, $album_id, $album_order);
                } else {
                    // FOR SINGLE PHOTO: No album_id or album_order
                    $sql = "INSERT INTO photos (file_path, title, description, tag, date_taken, group_id, uploader_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    // "sssssii" = 7 parameters
                    mysqli_stmt_bind_param($stmt, "sssssii", 
                        $target_path, $title, $description, $tag, $date_taken, $group_id, $user_id);
                }
                
                // 🟡 EXECUTE THE INSERT QUERY
                if (mysqli_stmt_execute($stmt)) {
                    $success_count++; // Increase success counter
                } else {
                    // If query fails, show error
                    $upload_message .= '<div class="alert alert-danger">Алдаа: ' . mysqli_error($conn) . '</div>';
                }
                
                mysqli_stmt_close($stmt); // Close statement
            } else {
                // If file move fails, show error
                $upload_message .= '<div class="alert alert-danger">' . $file_name . ' файл хадгалахад алдаа гарлаа!</div>';
            }
        }
        
        // 🟡 SHOW FINAL RESULT MESSAGE
        if ($success_count > 0) {
            if ($album_id) {
                $message = "🎉 {$success_count} зураг амжилттай альбом болгон хадгаллаа!";
            } else {
                $message = "✅ Зураг амжилттай байршууллаа!";
            }
            $upload_message = '<div class="alert alert-success">' . $message . '</div>';
            $upload_status = 'success';
        } else {
            $upload_message = '<div class="alert alert-danger">Ямар ч зураг байршуулагдаагүй байна!</div>';
            $upload_status = 'danger';
        }
    }
}

// 🟢 GET GROUPS FOR DROPDOWN
// Query to get all groups for the select dropdown
$groups_result = mysqli_query($conn, "SELECT * FROM groups");
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>Зураг байрлуулах</title>
    <!-- 🟢 BOOTSTRAP CSS: For responsive design and pre-made styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 🟢 BOOTSTRAP ICONS: For icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* 🟢 CUSTOM CSS STYLES */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        .upload-container {
            background: white;
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
        }
    </style>
</head>
<body>

<!-- 🟢 NAVIGATION BAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- Logo/Brand -->
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-images me-2"></i>📷 Дурсамж
        </a>
        <div class="d-flex align-items-center">
            <!-- Show username -->
            <span class="text-white me-3">
                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?>
            </span>
            <!-- Navigation buttons -->
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">Нүүр</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Гарах</a>
        </div>
    </div>
</nav>

<!-- 🟢 MAIN CONTENT AREA -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="upload-container">
                
                <!-- 🟢 PAGE TITLE -->
                <h2 class="text-center mb-4 text-primary">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i>
                    Шинэ Зураг Нэмэх
                </h2>
                
                <!-- 🟢 SHOW MESSAGES (Success/Error) -->
                <?php echo $upload_message; ?>
                
                <!-- 🟢 UPLOAD FORM -->
                <!-- enctype="multipart/form-data" is REQUIRED for file uploads -->
                <form method="POST" enctype="multipart/form-data">
                    
                    <!-- 🟡 FILE UPLOAD SECTION -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-image me-1"></i>Зургууд сонгох
                        </label>
                        <!-- Clickable area for file upload -->
                        <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-cloud-arrow-up display-4 text-muted mb-3"></i>
                            <h5>Зургаа энд чирж тавих эсвэл дарж сонгоно уу</h5>
                            <p class="text-muted">
                                Дэмжих формат: JPG, PNG, GIF<br>
                                Файл бүр 5MB-аас бага<br>
                                Олон зураг сонгосон тохиолдолд автоматаар альбом үүснэ
                            </p>
                        </div>
                        <!-- Hidden file input (triggered by clicking the area above) -->
                        <input type="file" name="photo[]" id="fileInput" 
                               class="form-control d-none" multiple required 
                               onchange="showSelectedFiles(this)">
                        <!-- Div to show file information -->
                        <div id="fileInfo" class="mt-2"></div>
                    </div>
                    
                    <!-- 🟡 TITLE INPUT -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-heading me-1"></i>Зургийн гарчиг
                        </label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="Жишээ: Төрсөн өдрийн баяр">
                    </div>
                    
                    <!-- 🟡 DESCRIPTION TEXTAREA -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-text-paragraph me-1"></i>Тайлбар
                        </label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Зургийн тайлбар..."></textarea>
                    </div>
                    
                    <!-- 🟡 TAGS INPUT -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tags me-1"></i>Тэгш (Tags)
                        </label>
                        <input type="text" name="tag" class="form-control" 
                               placeholder="гэр бүл, найзууд, амралт, баяр">
                        <small class="text-muted">Таслалаар тусгаарлан оруулна уу</small>
                    </div>
                    
                    <!-- 🟡 DATE INPUT -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-calendar-date me-1"></i>Зураг авах огноо
                        </label>
                        <input type="date" name="date_taken" class="form-control" required
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <!-- 🟡 GROUP SELECTION DROPDOWN -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-people me-1"></i>Үйл явдал (Бүлэг)
                        </label>
                        <select name="group_id" class="form-select" required>
                            <option value="">-- Бүлгээ сонгоно уу --</option>
                            <?php while($group = mysqli_fetch_assoc($groups_result)): ?>
                                <option value="<?php echo $group['group_id']; ?>">
                                    <?php echo htmlspecialchars($group['group_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="mt-2">
                            <a href="create_group.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>Шинэ бүлэг үүсгэх
                            </a>
                        </div>
                    </div>
                    
                    <!-- 🟡 SUBMIT BUTTON -->
                    <button type="submit" class="btn upload-btn text-white w-100">
                        <i class="bi bi-cloud-check me-2"></i>ЗУРАГ БАЙРШУУЛАХ
                    </button>
                    
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 🟢 JAVASCRIPT FUNCTIONS -->
<script>
// Function to show selected files information
function showSelectedFiles(input) {
    // input.files contains all selected files
    const files = input.files;
    const fileInfo = document.getElementById('fileInfo');
    
    // If no files selected, clear the info
    if (files.length === 0) {
        fileInfo.innerHTML = '';
        return;
    }
    
    // Build information string
    let info = `<strong>${files.length} зураг</strong> сонгогдлоо:`;
    
    // Show first few file names (limit to 3 to avoid too much text)
    const maxShow = Math.min(files.length, 3);
    for (let i = 0; i < maxShow; i++) {
        info += `<br>• ${files[i].name}`;
    }
    
    // If more than 3 files, show count
    if (files.length > 3) {
        info += `<br>... ба ${files.length - 3} нэмэлт зураг`;
    }
    
    // Album notice for multiple files
    if (files.length > 1) {
        info += `<br><br><span class="text-success">
                 <i class="bi bi-info-circle"></i> 
                 Олон зураг сонгосон тул автоматаар <strong>альбом</strong> үүснэ
                 </span>`;
    }
    
    // Display the information
    fileInfo.innerHTML = info;
}

// 🟢 DRAG AND DROP FUNCTIONALITY
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.querySelector('.file-upload-area');
    const fileInput = document.getElementById('fileInput');
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();  // Stop browser default behavior
        e.stopPropagation(); // Stop event from bubbling up
    }
    
    // Highlight drop area when dragging over
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
    
    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        // Get files from drag event
        const dt = e.dataTransfer;
        const files = dt.files;
        
        // Update file input with dragged files
        fileInput.files = files;
        
        // Trigger change event to update file info display
        const event = new Event('change', { bubbles: true });
        fileInput.dispatchEvent(event);
        
        // Show file info
        showSelectedFiles(fileInput);
    }
});
</script>

</body>
</html>