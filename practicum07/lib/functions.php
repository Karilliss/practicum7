<?php

function formatIsbn(string $isbn): string
{
    $clean = strtoupper(str_replace(['-', ' ', "\t"], '', trim($isbn)));

    if (strlen($clean) === 13) {
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($clean, 0, 3),
            substr($clean, 3, 3),
            substr($clean, 6, 2),
            substr($clean, 8, 4),
            substr($clean, 12, 1)
        );
    }
    if (strlen($clean) === 10) {
        return sprintf(
            '%s-%s-%s-%s',
            substr($clean, 0, 1),
            substr($clean, 1, 3),
            substr($clean, 4, 5),
            substr($clean, 9, 1)
        );
    }
    return $isbn;
}

function isOverdue(?string $dueDate): bool
{
    if ($dueDate === null || $dueDate === '') {
        return false;
    }
    $ts = strtotime($dueDate);
    if ($ts === false) {
        return false;
    }
    return $ts < time();
}

function isValidIsbn(string $isbn): bool
{
    $clean = strtoupper(str_replace(['-', ' ', "\t"], '', trim($isbn)));

    if (preg_match('/^\d{9}[\dX]$/', $clean)) {
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $d = $clean[$i] === 'X' ? 10 : (int)$clean[$i];
            $sum += $d * (10 - $i);
        }
        return $sum % 11 === 0;
    }

    if (preg_match('/^\d{13}$/', $clean)) {
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int)$clean[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return $sum % 10 === 0;
    }

    return false;
}

function validateBookData(array $post, int $currentYear): array
{
    $errors = [];

    $title = trim($post['title'] ?? '');
    if ($title === '') {
        $errors['title'] = 'Назва книги обов’язкова.';
    } elseif (mb_strlen($title) < 2) {
        $errors['title'] = 'Назва має містити щонайменше 2 символи.';
    }

    $author = trim($post['author'] ?? '');
    if ($author === '') {
        $errors['author'] = 'Автор обов’язковий.';
    }

    $year = trim($post['year'] ?? '');
    if ($year === '') {
        $errors['year'] = 'Вкажіть рік видання.';
    } elseif (!is_numeric($year) || (int)$year < 1450 || (int)$year > $currentYear) {
        $errors['year'] = "Рік має бути числом у діапазоні 1450–{$currentYear}.";
    }

    $isbn = trim($post['isbn'] ?? '');
    if ($isbn === '') {
        $errors['isbn'] = 'Вкажіть ISBN.';
    } elseif (!isValidIsbn($isbn)) {
        $errors['isbn'] = 'ISBN некоректний (перевірте контрольну суму).';
    }

    return $errors;
}