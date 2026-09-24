# API бібліотеки — варіант 1

Єдина точка входу: `api.php`
Формат відповіді:

  успіх:   {"success": true,  "data": ...}
  помилка: {"success": false, "error": "..."}

## Ресурс books (id, title, author, year, isbn, is_available)

### GET api.php?resource=books
Список усіх книг.
Код: 200
{
  "success": true,
  "data": {
    "count": 3,
    "books": [
      {"id":1,"title":"Кобзар","author":"Т. Шевченко","year":1840,
       "isbn":"9789660312340","isbn_pretty":"978-966-03-1234-0",
       "is_available":true}
    ]
  }
}

### GET api.php?resource=books&id=N
Одна книга за id.
Коди: 200 | 404

### POST api.php?resource=books
Створення книги. Тіло: JSON або form-data.
Обов'язкові поля: title, author, year, isbn.
Необов'язкове поле: is_available (bool/0/1). Якщо не передано — true.
Коди: 201 | 400

### POST api.php?resource=books&id=N&action=checkout
Видати книгу (is_available = 0).
Коди: 200 | 400 (вже видана) | 404 (немає такого id)

### POST api.php?resource=books&id=N&action=return
Повернути книгу у фонд (is_available = 1).
Коди: 200 | 400 (вже у фонді) | 404 (немає такого id)

## Інші коди
- 404 — неіснуючий ресурс
- 405 — метод не підтримується
- 500 — помилка сервера / БД