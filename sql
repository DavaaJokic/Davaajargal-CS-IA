CREATE DATABASE family_memories
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE family_memories;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE photos (
    photo_id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    `event` VARCHAR(100) NOT NULL,
    tag VARCHAR(50),
    date_taken DATE NOT NULL,
    group_id INT NOT NULL,
    uploader_id INT NOT NULL,
    album_id VARCHAR(50) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (group_id) REFERENCES groups(group_id)
        ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    commented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (photo_id) REFERENCES photos(photo_id)
        ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE INDEX idx_date_taken ON photos(date_taken);
CREATE INDEX idx_group_id ON photos(group_id);
CREATE INDEX idx_uploader_id ON photos(uploader_id);
CREATE INDEX idx_album_id ON photos(album_id);
