<?php

declare(strict_types=1);

function app_base_path(): string
{
    $basePath = '';

    if (class_exists('Flight')) {
        try {
            $configuredBasePath = Flight::get('app.base_path');

            if (is_string($configuredBasePath)) {
                $basePath = $configuredBasePath;
            }
        } catch (\Throwable) {
            $basePath = '';
        }
    }

    if ($basePath === '') {
        $appUrl = getenv('APP_URL') ?: '';
        $parsedPath = $appUrl === '' ? '' : (parse_url($appUrl, PHP_URL_PATH) ?: '');
        $basePath = is_string($parsedPath) ? $parsedPath : '';
    }

    $basePath = trim($basePath, '/');

    return $basePath === '' ? '' : '/' . $basePath;
}

function app_path(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $basePath = app_base_path();
    $path = trim($path);

    if ($path === '' || $path === '/') {
        return $basePath === '' ? '/' : $basePath . '/';
    }

    return $basePath . '/' . ltrim($path, '/');
}

function app_redirect(string $path, int $statusCode = 302): never
{
    header('Location: ' . app_path($path), true, $statusCode);
    exit;
}

function asset_path(string $path): string
{
    return app_path('/public/assets/' . ltrim($path, '/'));
}
