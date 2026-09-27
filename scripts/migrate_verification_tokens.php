<?php

declare(strict_types=1);

use App\Database\Connection;

require dirname(__DIR__) . '/src/Database/Connection.php';

$pdo = Connection::create();
$pdo->exec('ALTER TABLE reservations ADD COLUMN IF NOT EXISTS verification_token CHAR(64) NULL AFTER reservation_code');
$pdo->exec("UPDATE reservations SET verification_token = SHA2(CONCAT(UUID(), '-', id, '-', RAND()), 256) WHERE verification_token IS NULL OR verification_token = ''");
$pdo->exec('ALTER TABLE reservations MODIFY COLUMN verification_token CHAR(64) NOT NULL');

$index = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'reservations'
       AND INDEX_NAME = 'uq_reservations_verification_token'"
);
$index->execute();
if ((int) $index->fetchColumn() === 0) {
    $pdo->exec('CREATE UNIQUE INDEX uq_reservations_verification_token ON reservations (verification_token)');
}

echo "TOKENS DE VERIFICACIÓN: OK\n";
