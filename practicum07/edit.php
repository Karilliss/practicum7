<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/classes/Library.php';
require_once __DIR__ . '/lib/functions.php';

$pdo     = require __DIR__ . '/db.php';
$library = new Library($pdo);

$currentYear = (int) date('Y');
$id          = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit      = $id > 0;

$old = [
    'title'        => '',
    'author'       => '',
    'year'         => '',
    'isbn'         => '',
    'is_available' => 1,
    'is_ebook'     => 0,
    'file_size_mb' => '',
    'format'       => 'PDF',
];

$errors = [];

if ($isEdit) {
    $book = $library->findById($id);
    if ($book === null) {
        header('Location: index.php?msg=' . urlencode('Запис не знайдено'));
        exit;
    }
    $old = [
        'title'        => $book->getTitle(),
        'author'       => $book->getAuthor(),
        'year'         => $book->getYear(),
        'isbn'         => $book->getIsbn(),
        'is_available' => $book->isAvailable() ? 1 : 0,
        'is_ebook'     => $book instanceof EBook ? 1 : 0,
        'file_size_mb' => $book instanceof EBook ? $book->getFileSizeMb() : '',
        'format'       => $book instanceof EBook ? $book->getFormat() : 'PDF',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'title'        => trim($_POST['title'] ?? ''),
        'author'       => trim($_POST['author'] ?? ''),
        'year'         => trim($_POST['year'] ?? ''),
        'isbn'         => trim($_POST['isbn'] ?? ''),
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
        'is_ebook'     => isset($_POST['is_ebook']) ? 1 : 0,
        'file_size_mb' => trim($_POST['file_size_mb'] ?? ''),
        'format'       => trim($_POST['format'] ?? 'PDF'),
    ];

    $errors = validateBookData($_POST, $currentYear);

    if ($old['is_ebook']) {
        if ($old['file_size_mb'] === '' || !is_numeric($old['file_size_mb']) || (float)$old['file_size_mb'] <= 0) {
            $errors['file_size_mb'] = 'Для електронної книги вкажіть розмір файлу (додатне число).';
        }
        if (!in_array($old['format'], ['PDF', 'EPUB', 'FB2', 'MOBI'], true)) {
            $errors['format'] = 'Формат має бути PDF, EPUB, FB2 або MOBI.';
        }
    }

    if (empty($errors)) {
        $cleanIsbn = strtoupper(str_replace(['-', ' ', "\t"], '', $old['isbn']));

        if ($isEdit) {
            $library->updateBook($id, [
                'title'        => $old['title'],
                'author'       => $old['author'],
                'year'         => (int) $old['year'],
                'isbn'         => $cleanIsbn,
                'is_available' => (bool) $old['is_available'],
                'file_size_mb' => $old['is_ebook'] ? (float) $old['file_size_mb'] : null,
                'format'       => $old['is_ebook'] ? $old['format'] : null,
            ]);
            $msg = 'Книгу оновлено.';
        } else {
            if ($old['is_ebook']) {
                $book = new EBook(
                    $old['title'],
                    $old['author'],
                    (int) $old['year'],
                    $cleanIsbn,
                    (float) $old['file_size_mb'],
                    $old['format'],
                    (bool) $old['is_available']
                );
            } else {
                $book = new Book(
                    $old['title'],
                    $old['author'],
                    (int) $old['year'],
                    $cleanIsbn,
                    (bool) $old['is_available']
                );
            }
            $library->addBook($book);
            $msg = 'Книгу «' . $old['title'] . '» додано.';
        }

        header('Location: index.php?msg=' . urlencode($msg));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Редагування книги' : 'Додавання книги' ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><?= $isEdit ? 'Редагування книги #' . $id : 'Додавання книги' ?></h1>

    <section class="form-section">
        <?php if (!empty($errors)): ?>
            <ul class="msg msg--error">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="field">
                <label for="title">Назва *</label>
                <input type="text" id="title" name="title" required minlength="2"
                       value="<?= htmlspecialchars($old['title']) ?>">
            </div>

            <div class="field">
                <label for="author">Автор *</label>
                <input type="text" id="author" name="author" required
                       value="<?= htmlspecialchars($old['author']) ?>">
            </div>

            <div class="field">
                <label for="year">Рік видання *</label>
                <input type="number" id="year" name="year" required
                       min="1450" max="<?= $currentYear ?>"
                       value="<?= htmlspecialchars((string)$old['year']) ?>">
            </div>

            <div class="field">
                <label for="isbn">ISBN *</label>
                <input type="text" id="isbn" name="isbn" required
                       placeholder="напр. 978-966-03-1234-0"
                       value="<?= htmlspecialchars($old['isbn']) ?>">
                <small class="hint">
                    Приклад валідного ISBN:
                    <code>978-966-03-1234-0</code>,
                    <code>978-966-03-1235-7</code>
                </small>
            </div>

            <div class="field">
                <label>
                    <input type="checkbox" name="is_available" value="1"
                           <?= $old['is_available'] ? 'checked' : '' ?>>
                    Доступна у фонді
                </label>
            </div>

            <div class="field">
                <label>
                    <input type="checkbox" name="is_ebook" id="is_ebook" value="1"
                           <?= $old['is_ebook'] ? 'checked' : '' ?>>
                    Це електронна книга
                </label>
            </div>

            <div id="ebook-fields" style="<?= $old['is_ebook'] ? '' : 'display:none;' ?>">
                <div class="field">
                    <label for="format">Формат файлу</label>
                    <select id="format" name="format">
                        <?php foreach (['PDF','EPUB','FB2','MOBI'] as $f): ?>
                            <option value="<?= $f ?>" <?= $old['format'] === $f ? 'selected' : '' ?>>
                                <?= $f ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="file_size_mb">Розмір файлу (МБ)</label>
                    <input type="number" id="file_size_mb" name="file_size_mb"
                           step="0.1" min="0.1"
                           value="<?= htmlspecialchars((string)$old['file_size_mb']) ?>">
                </div>
            </div>

            <button type="submit"><?= $isEdit ? 'Зберегти зміни' : 'Додати книгу' ?></button>
            <a href="index.php" class="btn-back">Скасувати</a>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cb     = document.getElementById('is_ebook');
            const fields = document.getElementById('ebook-fields');
            cb.addEventListener('change', function () {
                fields.style.display = cb.checked ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>