<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class PwaController extends Controller
{
    public function manifest(Request $request): never
    {
        $base = rtrim(app_base_path(), '/');
        $start = $base === '' ? '/' : $base . '/';
        $icon192 = asset('img/pwa/icon-192.png');
        $icon512 = asset('img/pwa/icon-512.png');
        $maskable = asset('img/pwa/icon-maskable-512.png');

        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode([
            'id' => $start,
            'name' => (string) config('app.name', 'PL Pharma'),
            'short_name' => 'PL Pharma',
            'description' => 'PL Pharma wholesale and retail workspace',
            'start_url' => $start,
            'scope' => $start,
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
            'theme_color' => '#1565c0',
            'lang' => 'en-GH',
            'icons' => [
                ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $maskable, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
