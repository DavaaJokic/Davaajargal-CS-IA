<?php
session_start();

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>Дурсамж - Нүүр</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
    </style>
</head>
<body class="hero-section">
    <nav class="navbar navbar-dark">
        <div class="container">
            <span class="navbar-brand fw-bold">
                <i class="bi bi-images me-2"></i>📸 Дурсамж
            </span>
        </div>
    </nav>
    
    <div class="container py-5">
        <div class="text-center py-5">
            <h1 class="display-3 fw-bold mb-4">Гэр Бүлийн Дурсамж</h1>
            <p class="lead mb-5 fs-4">Дурсамжийн зургуудаа нэг дор хадгалаарай</p>
            
            <div class="d-flex justify-content-center gap-3 mb-5">
                <a href="login.php" class="btn btn-light btn-lg px-5 py-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Нэвтрэх
                </a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-5 py-3">
                    <i class="bi bi-person-plus me-2"></i> Бүртгүүлэх
                </a>
            </div>
            
            <!-- Features -->
            <div class="row mt-5 pt-5">
                <div class="col-md-4 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>
                    <h4>Зураг байршуулах</h4>
                    <p>Гэр бүлийн дурсамжийн зургаа альбом болгон хадгал</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <h4>Календараар харах</h4>
                    <p>Огноогоор ангилсан зургуудаа хялбархан харах</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h4>Гэр бүлтэйгээ хуваалцах</h4>
                    <p>Гэр бүлийн гишүүдтэйгээ зургаа хуваалцах</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="text-center py-4 text-white-50">
        <p>© 2024 Гэр Бүлийн Дурсамж. Бүх эрх хуулиар хамгаалагдсан.</p>
    </footer>
</body>
</html>