<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\ApiException;
use App\Core\Router;

$config = require __DIR__ . '/../config/app.php';

if ($config['debug']) {
    ini_set('display_errors', '1');
}

// ---------------------------------------------------------------------------
// CORS: se aplica a TODA respuesta de la API. En desarrollo cors_origin es '*';
// en produccion se setea CORS_ORIGIN al dominio del frontend.
// ---------------------------------------------------------------------------
header('Access-Control-Allow-Origin: ' . $config['cors_origin']);
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-HTTP-Method-Override');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Vary: Origin');

// preflight: el navegador manda un OPTIONS antes del request real. Se contesta
// 204 aca mismo, sin llegar al router.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------------------------------------------------------------------------
// Manejo central de errores: cualquier excepcion que burbujee hasta aca se
// traduce a un JSON { "error": {...} } con el codigo correcto. ApiException
// lleva su propio status; cualquier otra cosa es un 500.
// ---------------------------------------------------------------------------
set_exception_handler(function (Throwable $e) use ($config): void {
    if (headers_sent()) {
        return;
    }

    if ($e instanceof ApiException) {
        $status = $e->status();
        $payload = ['status' => $status, 'mensaje' => $e->getMessage()];
        if ($e->errores !== []) {
            $payload['errores'] = $e->errores;
        }
    } else {
        $status = 500;
        $payload = ['status' => 500, 'mensaje' => 'Error interno del servidor.'];
        if ($config['debug']) {
            $payload['debug'] = [
                'excepcion' => $e::class,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ];
        }
        error_log((string) $e);
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $payload], JSON_UNESCAPED_UNICODE);
});

// ---------------------------------------------------------------------------
// Tabla de rutas. UNICO lugar del repo donde se arma. Las rutas literales van
// ANTES que las parametricas con el mismo prefijo (ver README, seccion Rutas).
// Todo cuelga de /api; el frontend estatico (index.html, css/, js/) lo sirve
// Apache sin pasar por aca.
// ---------------------------------------------------------------------------
$router = new Router();

// -- slice demo, descartable --
$router->get('/api/ping', 'PingController@ping');
$router->get('/api/health/db', 'PingController@db');

$router->post('/api/auth/login', 'AuthController@login');
$router->post('/api/auth/logout', 'AuthController@logout');
$router->get('/api/auth/me', 'AuthController@me');

$router->get('/api/notas', 'NotaController@index');
$router->post('/api/notas', 'NotaController@store');
$router->get('/api/notas/{id:\d+}', 'NotaController@show');
$router->put('/api/notas/{id:\d+}', 'NotaController@update');
$router->patch('/api/notas/{id:\d+}', 'NotaController@update');
$router->delete('/api/notas/{id:\d+}', 'NotaController@destroy');
// -- fin slice demo --

$router->dispatch();
