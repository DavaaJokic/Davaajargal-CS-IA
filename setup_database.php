<?php
// setup_database.php
// Run this once to set up your database

echo "<h2>Setting up Database...</h2>";

// Connect to MySQL (no database selected yet)
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS family_photos 
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
if ($conn->query($sql) === TRUE) {
    echo "✅ Database created or already exists<br>";
} else {
    echo "❌ Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("family_photos");

// SQL commands from above
$sql_commands = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        profile_picture VARCHAR(255) DEFAULT 'default_profile.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Groups table
    "CREATE TABLE IF NOT EXISTS groups (
        group_id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL,
        description TEXT,
        cover_photo VARCHAR(255) NULL,
        creator_id INT NOT NULL,
        is_public BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (creator_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Albums table
    "CREATE TABLE IF NOT EXISTS albums (
        album_id VARCHAR(50) PRIMARY KEY,
        album_name VARCHAR(100) NOT NULL,
        description TEXT,
        cover_photo VARCHAR(255) NULL,
        created_by INT NOT NULL,
        photo_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Photos table
    "CREATE TABLE IF NOT EXISTS photos (
        photo_id INT AUTO_INCREMENT PRIMARY KEY,
        file_path VARCHAR(255) NOT NULL,
        title VARCHAR(100),
        description TEXT,
        tag VARCHAR(255),
        group_id INT NOT NULL,
        uploader_id INT NOT NULL,
        album_id VARCHAR(50) NULL,
        album_order INT DEFAULT 0,
        date_taken DATE NOT NULL,
        location VARCHAR(100),
        view_count INT DEFAULT 0,
        like_count INT DEFAULT 0,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_public BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE,
        FOREIGN KEY (uploader_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (album_id) REFERENCES albums(album_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Comments table
    "CREATE TABLE IF NOT EXISTS comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        photo_id INT NOT NULL,
        user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        commented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (photo_id) REFERENCES photos(photo_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Create indexes
    "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);",
    "CREATE INDEX IF NOT EXISTS idx_photos_date ON photos(date_taken);",
    "CREATE INDEX IF NOT EXISTS idx_photos_uploader ON photos(uploader_id);",
    "CREATE INDEX IF NOT EXISTS idx_comments_photo ON comments(photo_id);"
];

// Execute each SQL command
foreach ($sql_commands as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Table created successfully<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Insert default data
$insert_commands = [
    // Default admin user (password: admin123)
    "INSERT IGNORE INTO users (username, password, full_name, email) VALUES
    ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Админ Хэрэглэгч', 'admin@family.com');",
    
    // Default groups
    "INSERT IGNORE INTO groups (group_name, description, creator_id) VALUES
    ('Гэр бүлийн цуглаан', 'Гэр бүлийн ерөнхий цуглаанууд', 1),
    ('Төрсөн өдөр', 'Гэр бүлийн гишүүдийн төрсөн өдөр', 1),
    ('Зуны амралт', 'Зуны амралтын зураг', 1);"
];

foreach ($insert_commands as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Default data inserted<br>";
    } else {
        echo "⚠️ Note: " . $conn->error . "<br>";
    }
}

echo "<h3>✅ Database setup complete!</h3>";
echo "<p>You can now:</p>";
echo "<ol>";
echo "<li><a href='register.php'>Register a new user</a></li>";
echo "<li><a href='login.php'>Login with admin/admin123</a></li>";
echo "<li><a href='upload.php'>Upload photos</a></li>";
echo "</ol>";

$conn->close();
?>