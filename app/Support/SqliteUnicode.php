<?php

namespace App\Support;

use Illuminate\Database\Connection;
use PDO;
use Pdo\Sqlite;

/**
 * SQLite folds case only for ASCII, so `like`, `lower()` and `upper()` treat
 * "Северная" and "северная" as different values while MySQL and PostgreSQL do not.
 * Multibyte-aware replacements keep search results identical on every driver.
 */
final class SqliteUnicode
{
    public static function register(Connection $connection): void
    {
        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $connection->getPdo();

        self::define($pdo, 'like', self::matches(...), 2);
        self::define($pdo, 'like', self::matches(...), 3);
        self::define($pdo, 'lower', self::lower(...), 1);
        self::define($pdo, 'upper', self::upper(...), 1);
    }

    public static function matches(?string $pattern, ?string $value, ?string $escape = null): ?bool
    {
        if ($pattern === null || $value === null) {
            return null;
        }

        $regex = self::regex($pattern, $escape);
        $matched = preg_match('/^'.$regex.'$/uis', $value);

        return ($matched === false ? preg_match('/^'.$regex.'$/is', $value) : $matched) === 1;
    }

    public static function lower(?string $value): ?string
    {
        return $value === null ? null : mb_strtolower($value);
    }

    public static function upper(?string $value): ?string
    {
        return $value === null ? null : mb_strtoupper($value);
    }

    private static function regex(string $pattern, ?string $escape): string
    {
        $escapeChar = ($escape === null || $escape === '') ? null : mb_substr($escape, 0, 1);

        $compiled = array_reduce(
            mb_str_split($pattern),
            static function (array $carry, string $char) use ($escapeChar): array {
                if ($carry['escaped']) {
                    return ['regex' => $carry['regex'].preg_quote($char, '/'), 'escaped' => false];
                }

                if ($char === $escapeChar) {
                    return ['regex' => $carry['regex'], 'escaped' => true];
                }

                return match ($char) {
                    '%' => ['regex' => $carry['regex'].'.*', 'escaped' => false],
                    '_' => ['regex' => $carry['regex'].'.', 'escaped' => false],
                    default => ['regex' => $carry['regex'].preg_quote($char, '/'), 'escaped' => false],
                };
            },
            ['regex' => '', 'escaped' => false],
        );

        return $compiled['regex'];
    }

    private static function define(PDO $pdo, string $name, callable $callback, int $arity): void
    {
        $pdo instanceof Sqlite
            ? $pdo->createFunction($name, $callback, $arity)
            : $pdo->sqliteCreateFunction($name, $callback, $arity);
    }
}
