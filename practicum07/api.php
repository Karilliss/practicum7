<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/classes/Library.php';
require_once __DIR__ . '/lib/functions.php';

header('Content-Type: application/json; charset=utf-8');

function sendJson(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ok($data, int $code = 200): void
{
    sendJson(['success' => true, 'data' => $data], $code);
}

function fail(string $message, int $code = 400, array $extra = []): void
{
    sendJson(array_merge(
        ['success' => false, 'error' => $message],
        $extra
    ), $code);
}

function bookToArray(Book $b): array
{
    $row = [
        'id'           => $b->getId(),
        'title'        => $b->getTitle(),
        'author'       => $b->getAuthor(),
        'year'         => $b->getYear(),
        'isbn'         => $b->getIsbn(),
        'isbn_pretty'  => formatIsbn($b->getIsbn()),
        'is_available' => $b->isAvailable(),
    ];
    if ($b instanceof EBook) {
        $row['is_ebook']     = true;
        $row['format']       = $b->getFormat();
        $row['file_size_mb'] = $b->getFileSizeMb();
    }
    return $row;
}

$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$id       = isset($_GET['id']) ? (int) $_GET['id'] : null;
$action   = $_GET['action'] ?? null;

try {
    $pdo     = require __DIR__ . '/db.php';
    $library = new Library($pdo);
} catch (Throwable $e) {
    fail('Помилка підключення до БД', 500);
}

if ($resource !== 'books') {
    fail('Неіснуючий ресурс або метод', 404);
}

switch (true) {

    case $method === 'GET' && $id === null:
        $start = microtime(true);
        $pdo->resetQueryCount();

        $books = $library->listAvailableCached(60);

        $elapsed    = (microtime(true) - $start) * 1000;
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
        $queries    = $pdo->getQueryCount();

        error_log(sprintf(
            'GET books: %.2f мс, пам’ять: %.2f МБ, SQL-запитів: %d',
            $elapsed,
            $peakMemory,
            $queries
        ));

        ok([
            'count' => count($books),
            'books' => array_map('bookToArray', $books),
        ]);
        break;

    case $method === 'GET' && $id !== null:
        $book = $library->findById($id);
        if ($book === null) {
            fail("Книгу з id={$id} не знайдено", 404);
        }
        ok(bookToArray($book));
        break;

    case $method === 'POST' && $action === null:
        $raw  = file_get_contents('php://input');
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            $data = $_POST;
        }

        $currentYear = (int) date('Y');
        $errors = validateBookData($data, $currentYear);

        if ($errors) {
            fail('Некоректні дані', 400, ['errors' => $errors]);
        }

        $cleanIsbn = strtoupper(str_replace(['-', ' ', "\t"], '', $data['isbn']));

        if (array_key_exists('is_available', $data)) {
            $isAvail = filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $isAvail = true;
        }

        $book = new Book(
            trim($data['title']),
            trim($data['author']),
            (int) $data['year'],
            $cleanIsbn,
            $isAvail
        );

        $newId = $library->addBook($book);
        $book->setId($newId);

        ok(bookToArray($book), 201);
        break;

    case $method === 'POST' && $action === 'checkout':
        if ($id === null || $id <= 0) {
            fail('Параметр id обов’язковий і має бути додатним числом', 400);
        }

        $book = $library->findById($id);
        if ($book === null) {
            fail("Книгу з id={$id} не знайдено", 404);
        }
        if (!$book->isAvailable()) {
            fail('Книга вже видана', 400);
        }

        $library->updateBook($id, [
            'title'        => $book->getTitle(),
            'author'       => $book->getAuthor(),
            'year'         => $book->getYear(),
            'isbn'         => $book->getIsbn(),
            'is_available' => false,
            'file_size_mb' => $book instanceof EBook ? $book->getFileSizeMb() : null,
            'format'       => $book instanceof EBook ? $book->getFormat() : null,
        ]);

        $updated = $library->findById($id);
        ok([
            'message' => 'Книгу видано',
            'book'    => bookToArray($updated),
        ]);
        break;

    case $method === 'POST' && $action === 'return':
        if ($id === null || $id <= 0) {
            fail('Параметр id обов’язковий і має бути додатним числом', 400);
        }

        $book = $library->findById($id);
        if ($book === null) {
            fail("Книгу з id={$id} не знайдено", 404);
        }
        if ($book->isAvailable()) {
            fail('Книга вже у фонді (не видана)', 400);
        }

        $library->updateBook($id, [
            'title'        => $book->getTitle(),
            'author'       => $book->getAuthor(),
            'year'         => $book->getYear(),
            'isbn'         => $book->getIsbn(),
            'is_available' => true,
            'file_size_mb' => $book instanceof EBook ? $book->getFileSizeMb() : null,
            'format'       => $book instanceof EBook ? $book->getFormat() : null,
        ]);

        $updated = $library->findById($id);
        ok([
            'message' => 'Книгу повернуто у фонд',
            'book'    => bookToArray($updated),
        ]);
        break;

    case $method === 'POST':
        fail("Невідома дія: {$action}", 400);
        break;

    default:
        fail('Метод не підтримується для цього ресурсу', 405);
}