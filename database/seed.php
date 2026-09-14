<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

(new App\Database\Seeders\DemoDataSeeder())->run();
echo "Demo data seeded.\n";
