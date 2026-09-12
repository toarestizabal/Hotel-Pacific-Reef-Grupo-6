<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Connection
{
    public static function create(): PDO
    {
        $projectRoot = dirname(__DIR__, 2);
        $configFile = $projectRoot . '/config/database.php';
        $config = require (is_file($configFile)
            ? $configFile
            : $projectRoot . '/config/database.example.php');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

