<?php

declare(strict_types=1);

namespace LocalMcp\Core;

final class Version
{
    public const NAME = 'Local MCP Server';

    public static function read(string $basePath): string
    {
        $path = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'VERSION';

        if (!is_file($path)) {
            return '0.0.0';
        }

        $version = trim((string) file_get_contents($path));

        return $version !== '' ? $version : '0.0.0';
    }
}
