<?php

require_once __DIR__ . '/vendor/autoload.php';

use ORM\Database;

// install.php
$dbName = 'mini_orm';
$dbNameTest = 'mini_orm_test';

try {
    $pdo = $pdo ?? Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Veritabanını oluştur (varsa atla)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database `$dbName` created or exists.\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbNameTest` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$dbName`");
    // Önce tüm tabloları sil
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped table `$table`.\n";
    }

    // --- USERS ---
    $pdo->exec("
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50),
        email VARCHAR(100) UNIQUE,
        status VARCHAR(20),
        age INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("
    INSERT INTO users (name, email, status, age) VALUES
    ('Ali', 'ali@gmail.com', 'active', 25),
    ('Veli', 'veli@gmail.com', 'active', 30),
    ('Ayşe', 'ayse@yahoo.com', 'inactive', 22)");
    echo "Table `users` created and data inserted.\n";

    // --- POSTS ---
    $pdo->exec("
    CREATE TABLE posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100),
        content TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("
    INSERT INTO posts (title, content, user_id) VALUES
    ('İlk Post', 'İçerik 1', 1),
    ('İkinci Post', 'İçerik 2', 2),
    ('Üçüncü Post', 'İçerik 3', 1)");
    echo "Table `posts` created and data inserted.\n";

    // --- PRODUCTS ---
    $pdo->exec("
    CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        price DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("
    INSERT INTO products (name, price) VALUES
    ('Laptop', 3500.50),
    ('Mouse', 150.75),
    ('Klavye', 300.00)");
    echo "Table `products` created and data inserted.\n";

    // --- ROLES ---
    $pdo->exec("
    CREATE TABLE roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("
    INSERT INTO roles (name) VALUES
    ('Admin'),
    ('Editor'),
    ('Member')");
    echo "Table `roles` created and data inserted.\n";

    // --- ROLE_USER ---
    $pdo->exec("
    CREATE TABLE role_user (
        user_id INT,
        role_id INT,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    )");
    $pdo->exec("
    INSERT INTO role_user (user_id, role_id) VALUES
    (1,1),(1,3),(2,3),(3,2)");
    echo "Table `role_user` created and data inserted.\n";

    // --- PROFILES ---
    $pdo->exec("
    CREATE TABLE profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bio TEXT NULL,
        avatar VARCHAR(255) NULL,
        phone VARCHAR(50) NULL,
        address VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $pdo->exec("
    INSERT INTO profiles (user_id, bio, avatar, phone, address) VALUES
    (1, 'Back-end geliştirici', 'ahmet.jpg', '+901111111111', 'İstanbul'),
    (2, 'Frontend geliştirici', 'ayse.png', '+901111111111', 'Ankara'),
    (3, 'Fullstack developer', 'test.png', '+901111111111', 'İzmir')");
    echo "Table `profiles` created and data inserted.\n";

    echo "\nDatabase installation and reset complete!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

