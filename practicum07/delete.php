<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/classes/Library.php';

$pdo     = require __DIR__ . '/db.php';
$library = new Library($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $library->deleteBook($id);
    $msg = 'Запис видалено.';
} else {
    $msg = 'Некоректний ID.';
}

header('Location: index.php?msg=' . urlencode($msg));
exit;