<?php

class CountingPDO extends PDO
{
    private int $queryCount = 0;

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->queryCount++;
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function exec(string $statement): int|false
    {
        $this->queryCount++;
        return parent::exec($statement);
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queryCount++;
        return parent::prepare($query, $options);
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function resetQueryCount(): void
    {
        $this->queryCount = 0;
    }
}