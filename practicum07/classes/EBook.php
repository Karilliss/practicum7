<?php

require_once __DIR__ . '/Book.php';

class EBook extends Book
{
    private float $fileSizeMb;
    private string $format;

    public function __construct(
        string $title,
        string $author,
        int $year,
        string $isbn,
        float $fileSizeMb,
        string $format,
        bool $isAvailable = true
    ) {
        parent::__construct($title, $author, $year, $isbn, $isAvailable);
        $this->fileSizeMb = $fileSizeMb;
        $this->format     = $format;
    }

    public function getFileSizeMb(): float { return $this->fileSizeMb; }
    public function getFormat(): string     { return $this->format; }

    public function getInfo(): string
    {
        return parent::getInfo() . sprintf(
            ' [електронна: %s, %.1f МБ]',
            $this->format,
            $this->fileSizeMb
        );
    }
}