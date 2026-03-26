<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    public static function get(string $name, ?string $default = null): string
    {
        $localValue = getenv($name, true);

        if ($localValue !== false && $localValue !== '') {
            return (string) $localValue;
        }

        if (array_key_exists($name, $_ENV) && $_ENV[$name] !== '') {
            return (string) $_ENV[$name];
        }

        if (array_key_exists($name, $_SERVER) && $_SERVER[$name] !== '') {
            return (string) $_SERVER[$name];
        }

        $globalValue = getenv($name);

        if ($globalValue !== false && $globalValue !== '') {
            return (string) $globalValue;
        }

        return $default ?? '';
    }
}
