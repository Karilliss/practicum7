<?php

function cacheDir(): string
{
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function cacheGet(string $key, int $ttl): ?array
{
    $file = cacheDir() . '/cache_' . md5($key) . '.json';
    if (!is_file($file)) {
        return null;
    }
    if ((time() - filemtime($file)) >= $ttl) {
        return null;
    }
    $raw = file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function cacheSet(string $key, array $data): void
{
    $file = cacheDir() . '/cache_' . md5($key) . '.json';
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function cacheInvalidate(string $key): void
{
    $file = cacheDir() . '/cache_' . md5($key) . '.json';
    if (is_file($file)) {
        unlink($file);
    }
}