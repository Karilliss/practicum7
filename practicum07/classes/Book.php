<?php

class Book
{
    protected ?int $id = null;
    protected string $title;
    protected string $author;
    protected int $year;
    protected string $isbn;
    protected bool $isAvailable;

    public function __construct(
        string $title,
        string $author,
        int $year,
        string $isbn,
        bool $isAvailable = true
    ) {
        $this->title       = $title;
        $this->author      = $author;
        $this->year        = $year;
        $this->isbn        = $isbn;
        $this->isAvailable = $isAvailable;
    }

    public function setId(int $id): void { $this->id = $id; }
    public function getId(): ?int       { return $this->id; }

    public function getTitle(): string  { return $this->title; }
    public function getAuthor(): string { return $this->author; }
    public function getYear(): int      { return $this->year; }
    public function getIsbn(): string   { return $this->isbn; }
    public function isAvailable(): bool { return $this->isAvailable; }

    public function getInfo(): string
    {
        return sprintf(
            '«%s» — %s, %d р., ISBN: %s (%s)',
            $this->title,
            $this->author,
            $this->year,
            $this->isbn,
            $this->isAvailable ? 'доступна' : 'видана'
        );
    }
}