<?php

require_once __DIR__ . '/Book.php';
require_once __DIR__ . '/EBook.php';
require_once __DIR__ . '/../lib/cache.php';

class Library
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM books ORDER BY id ASC');
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function getAllWithAuthorCountSlow(): array
    {
        $books = $this->getAll();

        foreach ($books as $book) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM books WHERE author = :author'
            );
            $stmt->execute([':author' => $book->getAuthor()]);
            $count = (int) $stmt->fetchColumn();
        }

        return $books;
    }

    public function getAllWithAuthorCount(): array
    {
        $books = $this->getAll();

        $counts = $this->pdo->query(
            'SELECT author, COUNT(*) AS cnt FROM books GROUP BY author'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        return $books;
    }

    public function searchByTitle(string $q): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM books WHERE title LIKE :q ORDER BY id ASC'
        );
        $stmt->execute([':q' => '%' . $q . '%']);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findByAuthor(string $author): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE author = :author');
        $stmt->execute([':author' => $author]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function listAvailable(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM books WHERE is_available = 1');
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function listAvailableCached(int $ttl = 60): array
    {
        $cacheKey = 'books_listAvailable';
        $cached = cacheGet($cacheKey, $ttl);
        if ($cached !== null) {
            return array_map([$this, 'hydrate'], $cached);
        }

        $stmt = $this->pdo->query('SELECT * FROM books WHERE is_available = 1');
        $rows = $stmt->fetchAll();
        cacheSet($cacheKey, $rows);

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findById(int $id): ?Book
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function addBook(Book $book): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (title, author, year, isbn, is_available, file_size_mb, format)
             VALUES (:title, :author, :year, :isbn, :is_available, :file_size_mb, :format)'
        );
        $stmt->execute([
            ':title'        => $book->getTitle(),
            ':author'       => $book->getAuthor(),
            ':year'         => $book->getYear(),
            ':isbn'         => $book->getIsbn(),
            ':is_available' => $book->isAvailable() ? 1 : 0,
            ':file_size_mb' => $book instanceof EBook ? $book->getFileSizeMb() : null,
            ':format'       => $book instanceof EBook ? $book->getFormat() : null,
        ]);
        cacheInvalidate('books_listAvailable');
        return (int) $this->pdo->lastInsertId();
    }

    public function updateBook(int $id, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE books
                SET title        = :title,
                    author       = :author,
                    year         = :year,
                    isbn         = :isbn,
                    is_available = :is_available,
                    file_size_mb = :file_size_mb,
                    format       = :format
              WHERE id = :id'
        );
        $stmt->execute([
            ':title'        => $data['title'],
            ':author'       => $data['author'],
            ':year'         => $data['year'],
            ':isbn'         => $data['isbn'],
            ':is_available' => $data['is_available'] ? 1 : 0,
            ':file_size_mb' => $data['file_size_mb'] ?? null,
            ':format'       => $data['format'] ?? null,
            ':id'           => $id,
        ]);
        cacheInvalidate('books_listAvailable');
        return $stmt->rowCount();
    }

    public function deleteBook(int $id): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        cacheInvalidate('books_listAvailable');
        return $stmt->rowCount();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
    }

    private function hydrate(array $row): Book
    {
        if (!empty($row['format']) && $row['file_size_mb'] !== null) {
            $book = new EBook(
                $row['title'],
                $row['author'],
                (int) $row['year'],
                $row['isbn'],
                (float) $row['file_size_mb'],
                $row['format'],
                (bool) $row['is_available']
            );
        } else {
            $book = new Book(
                $row['title'],
                $row['author'],
                (int) $row['year'],
                $row['isbn'],
                (bool) $row['is_available']
            );
        }
        $book->setId((int) $row['id']);
        return $book;
    }
}