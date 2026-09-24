<?php
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Каталог бібліотеки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Каталог бібліотеки</h1>

    <div class="field">
        <label for="search">Пошук за назвою книги:</label>
        <input type="text" id="search" placeholder="Почніть вводити назву…" autocomplete="off">
    </div>

    <p>Знайдено: <strong id="count">0</strong></p>

    <div id="results" class="cards"></div>
    <p id="status" class="msg" hidden></p>

    <section class="form-section">
        <h2>Додати книгу</h2>
        <form id="add-form" novalidate>
            <div class="field">
                <label for="f-title">Назва *</label>
                <input type="text" id="f-title" name="title" required minlength="2">
            </div>
            <div class="field">
                <label for="f-author">Автор *</label>
                <input type="text" id="f-author" name="author" required>
            </div>
            <div class="field">
                <label for="f-year">Рік *</label>
                <input type="number" id="f-year" name="year" min="1450"
                       max="<?= (int)date('Y') ?>" required>
            </div>
            <div class="field">
                <label for="f-isbn">ISBN *</label>
                <input type="text" id="f-isbn" name="isbn"
                       placeholder="напр. 978-966-03-1234-0" required>
            </div>
            <div class="field">
                <label>
                    <input type="checkbox" name="is_available" value="1" checked>
                    Доступна у фонді
                </label>
            </div>
            <button type="submit">Додати книгу</button>
        </form>
        <ul id="form-errors" class="msg msg--error" hidden></ul>
    </section>

    <script src="script.js"></script>
</body>
</html>