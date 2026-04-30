<?php

declare(strict_types=1);

require __DIR__ . '/../src/Database.php';

use App\Database;

$sql = file_get_contents(__DIR__ . '/001_init.sql');
if ($sql === false) {
    fwrite(STDERR, "Cannot read migration file\n");
    exit(1);
}

$pdo = Database::pdo();

$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    static fn(string $s): bool => $s !== ''
);

foreach ($statements as $stmt) {
    $pdo->exec($stmt);
}

echo "Migration applied successfully\n";
