<?php

declare(strict_types=1);

namespace SeoLocalRank;

/**
 * Minimal .env loader (key=value lines, comments with #).
 * Avoids pulling in vlucas/phpdotenv.
 */
final class Env
{
    /** @var array<string,string> */
    private static array $cache = [];

    public static function load(?string $path = null): void
    {
        if (self::$cache !== []) {
            return;
        }
        $path ??= dirname(__DIR__) . '/.env';
        if (!is_file($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\"'");
            self::$cache[$key] = $value;
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $env = getenv($key);
        return $env === false || $env === '' ? $default : $env;
    }
}
