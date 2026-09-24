<?php

require_once __DIR__ . '/classes/CountingPDO.php';

$dsn = 'mysql:host=localhost;dbname=practicum4;charset=utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new CountingPDO($dsn, 'root', '', $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Помилка підключення до БД: ' . htmlspecialchars($e->getMessage()));
}

return $pdo;