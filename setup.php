<?php
// setup.php - Run this once to create database

// Connect to MySQL
$conn = mysqli_connect("localhost", "root", "");

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS family_photos 
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
mysqli_query($conn, $sql);

// Select the database
mysqli_select_db($conn, "family_photos");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Create groups table
$sql = "CREATE TABLE IF NOT EXISTS groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL,
    creator_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Create photos table
$sql = "CREATE TABLE IF NOT EXISTS photos (
    photo_id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    event VARCHAR(100),
    tag VARCHAR(255),
    date_taken DATE,
    group_id INT,
    uploader_id INT,
    album_id VARCHAR(50) NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Create comments table
$sql = "CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    photo_id INT,
    user_id INT,
    comment_text TEXT,
    commented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Insert test user (password: 123456)
$hashed_password = password_hash('123456', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, password, full_name) 
        VALUES ('admin', '$hashed_password', 'Админ Хэрэглэгч')";
mysqli_query($conn, $sql);

// Insert some groups
$sql = "INSERT IGNORE INTO groups (group_name, creator_id) VALUES
        ('Гэр бүлийн цуглаан', 1),
        ('Төрсөн өдөр', 1),
        ('Зуны амралт', 1)";
mysqli_query($conn, $sql);

echo "✅ Database setup complete! You can now use the website.";
?>