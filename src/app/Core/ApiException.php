<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

// excepcion de la capa HTTP: lleva el codigo de estado que hay que devolver y,
// opcionalmente, un detalle de errores de validacion campo -> mensaje.
// El front controller (public/index.php) la captura y la traduce a un JSON
// { "error": { "status": ..., "mensaje": ..., "errores": {...} } }.
//
// Los controladores tiran esto en vez de armar la respuesta de error a mano:
//   throw new ApiException(404, 'Nota no encontrada.');
//   throw ApiException::validacion(['email' => 'Formato invalido']);
final class ApiException extends RuntimeException
{
    /**
     * @param array<string, string> $errores
     */
    public function __construct(
        int $status,
        string $mensaje,
        public readonly array $errores = [],
        ?Throwable $previa = null,
    ) {
        parent::__construct($mensaje, $status, $previa);
    }

    public function status(): int
    {
        return $this->getCode();
    }

    /**
     * @param array<string, string> $errores
     */
    public static function validacion(array $errores, string $mensaje = 'Datos invalidos.'): self
    {
        return new self(422, $mensaje, $errores);
    }
}
