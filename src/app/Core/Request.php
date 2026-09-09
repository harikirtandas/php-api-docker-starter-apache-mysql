<?php

declare(strict_types=1);

namespace App\Core;

// acceso tipado a los datos de la request entrante. No es un objeto que se
// inyecta: los controladores hacen `new Request()` cuando lo necesitan (o usan
// los helpers de Controller que ya lo arman).
final class Request
{
    /** @var array<string, mixed>|null cache del body JSON ya parseado */
    private ?array $jsonCache = null;

    /**
     * Body de la request parseado como JSON asociativo.
     * Si el body esta vacio devuelve []. Si trae algo que no es JSON valido o
     * no es un objeto/array, tira 400.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        $crudo = file_get_contents('php://input');

        if ($crudo === false || trim($crudo) === '') {
            return $this->jsonCache = [];
        }

        try {
            $data = json_decode($crudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException(400, 'El body no es JSON valido.', previa: $e);
        }

        if (!is_array($data)) {
            throw new ApiException(400, 'El body tiene que ser un objeto JSON.');
        }

        /** @var array<string, mixed> $data */
        return $this->jsonCache = $data;
    }

    /**
     * Valor de un campo del body JSON, con default si no vino.
     */
    public function input(string $clave, mixed $default = null): mixed
    {
        return $this->json()[$clave] ?? $default;
    }

    /**
     * Valor de un parametro del query string (?clave=valor).
     */
    public function query(string $clave, mixed $default = null): mixed
    {
        return $_GET[$clave] ?? $default;
    }

    /**
     * Token del header Authorization: Bearer xxxxx, o null si no vino.
     * El header lo puede descartar Apache: el .htaccess de este starter lo
     * reinyecta como HTTP_AUTHORIZATION via mod_rewrite (ver public/.htaccess).
     */
    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim((string) $header), $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }

    public function metodo(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}
