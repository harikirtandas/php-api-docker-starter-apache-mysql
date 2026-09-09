<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

// conexion PDO unica (singleton estatico) a MySQL.
// las credenciales se leen con getenv() y no $_ENV porque docker-compose las
// inyecta como variables de entorno reales del proceso, y $_ENV depende de la
// directiva variables_order de php.ini (no siempre incluye "E" por default)
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: 'mysql';
            $port = getenv('DB_PORT') ?: '3306';
            $database = getenv('DB_DATABASE') ?: 'app';
            $username = getenv('DB_USERNAME') ?: 'app';
            $password = getenv('DB_PASSWORD') ?: 'secret';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            self::$pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}
