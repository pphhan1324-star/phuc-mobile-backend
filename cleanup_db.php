<?php
$host = '127.0.0.1';
$db = 'db_tttn';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ATTR_ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::ATTR_FETCH_MODE_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table` CASCADE");
        echo "Dropped table: $table\n";
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "All tables dropped successfully.\n";
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage();
}
