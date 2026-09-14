<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile(self::resolve($template), $data);

        if ($layout === null) {
            return $content;
        }

        $data['slot'] = $content;
        $data['content'] = $content;

        return self::renderFile(self::resolve('layouts.' . $layout), $data);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::renderFile(self::resolve($template), $data);
    }

    public static function include(string $template, array $data = []): void
    {
        echo self::partial($template, $data);
    }

    private static function resolve(string $template): string
    {
        $relative = str_replace('.', '/', $template) . '.php';
        $path = base_path('app/Views/' . $relative);

        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        return $path;
    }

    private static function renderFile(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
