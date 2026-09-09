<?php

declare(strict_types=1);

namespace App\Core;

// base de todos los controladores de la API. En el hermano MVC este archivo
// tenia render() con output buffering para armar vistas HTML; aca todo es JSON,
// asi que lo unico que ofrece son helpers para responder con el codigo de
// estado correcto.
abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * Responde con JSON y un codigo de estado. $datos se serializa tal cual;
     * pasar null para un body vacio con, por ejemplo, un 200 sin contenido.
     */
    protected function json(mixed $datos, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        if ($datos !== null) {
            echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * 201 Created. Si se pasa $ubicacion agrega el header Location (buena
     * practica REST: donde quedo el recurso que se acaba de crear).
     */
    protected function creado(mixed $datos, ?string $ubicacion = null): void
    {
        if ($ubicacion !== null) {
            header('Location: ' . $ubicacion);
        }

        $this->json($datos, 201);
    }

    /**
     * 204 No Content: para DELETE y para PUT/PATCH que no devuelven cuerpo.
     */
    protected function sinContenido(): void
    {
        http_response_code(204);
    }
}
