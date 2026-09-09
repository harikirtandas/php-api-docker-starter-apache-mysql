<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

// slice demo minimo: smoke test de que Apache, PHP, el router, el autoloader y
// MySQL estan vivos y hablando entre si. Descartable en un proyecto real.
final class PingController extends Controller
{
    // GET /api/ping -> no toca la base, solo prueba PHP + router + autoloader.
    public function ping(): void
    {
        $this->json([
            'pong' => true,
            'hora' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    // GET /api/health/db -> hace un SELECT 1 real contra MySQL.
    public function db(): void
    {
        try {
            Database::connection()->query('SELECT 1');
        } catch (PDOException $e) {
            $this->json(['db' => 'error', 'detalle' => $e->getMessage()], 503);
            return;
        }

        $this->json(['db' => 'ok']);
    }
}
