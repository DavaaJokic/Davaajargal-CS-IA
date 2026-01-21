<?php
// 🟢 This file connects to MySQL database

// Database connection settings
$host = "localhost";    // Database server (usually localhost for XAMPP)
$user = "root";         // Default XAMPP username
$pass = "";             // Default XAMPP password (empty)
$db = "family_photos";  // Database name we created

// Create connection using mysqli (MySQL Improved extension)
$conn = mysqli_connect($host, $user, $pass, $db);

// Check if connection was successful
if (!$conn) {
    // If connection fails, show error and stop script
    die("❌ Database холболт амжилтгүй: " . mysqli_connect_error());
}

// If connection is successful, continue with rest of code
?>