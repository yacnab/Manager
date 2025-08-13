<?php

declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_NAME = 'my_project';
$DB_USER = 'root';
$DB_PASS = ''; 

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    
    exit('Database connection failed: ' . $e->getMessage());
}
