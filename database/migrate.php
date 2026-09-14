<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$root = dirname(__DIR__);
$config = require $root . '/config/database.php';

$adminDsn = sprintf(
    'mysql:host=%s;port=%d;charset=utf8mb4',
    $config['host'],
    $config['port']
);

try {
    $admin = new PDO($adminDsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $config['name']);
    $admin->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    fwrite(STDERR, "Could not create database: {$e->getMessage()}\n");
    exit(1);
}

$db = Database::instance();
$db->execute(
    'CREATE TABLE IF NOT EXISTS `migrations` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `filename` VARCHAR(255) NOT NULL UNIQUE,
        `applied_at` DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$appliedRows = $db->fetchAll('SELECT filename FROM migrations');
$applied = array_column($appliedRows, 'filename');

$files = glob($root . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        echo "skip  {$name}\n";
        continue;
    }

    $sql = (string) file_get_contents($file);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    try {
        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            $db->pdo()->exec($statement);
        }
        $db->execute(
            'INSERT INTO migrations (filename, applied_at) VALUES (:filename, :applied_at)',
            [':filename' => $name, ':applied_at' => date('Y-m-d H:i:s')]
        );
        echo "apply {$name}\n";
        $ran++;
    } catch (Throwable $e) {
        fwrite(STDERR, "failed {$name}: {$e->getMessage()}\n");
        exit(1);
    }
}

echo $ran === 0 ? "No new migrations.\n" : "Applied {$ran} migration(s).\n";
