<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Token;
use App\Models\Usuario;

// autenticacion por token opaco guardado en la tabla `tokens`. Deliberadamente
// NO es JWT: un token aleatorio en una tabla es mas facil de explicar y de
// revocar (basta con borrar la fila), y sirve igual para el cliente JavaScript
// y para la app Android que consume la misma API mas adelante.
//
// Flujo:
//   POST /api/auth/login  -> Auth::login()  -> devuelve { token, expira, usuario }
//   requests siguientes   -> header Authorization: Bearer <token>
//   en cada request protegida -> Auth::usuarioActual($request)
final class Auth
{
    /**
     * Valida email + password y emite un token nuevo.
     *
     * @return array{token: string, expira: string, usuario: array<string, mixed>}
     * @throws ApiException 401 si las credenciales no coinciden
     */
    public static function login(string $email, string $password): array
    {
        $usuario = Usuario::porEmail($email);

        if ($usuario === null || !password_verify($password, (string) $usuario['password_hash'])) {
            // mismo mensaje para "no existe" y "password mala": no filtra si el
            // email esta registrado.
            throw new ApiException(401, 'Email o contrasena incorrectos.');
        }

        $ttlHoras = (int) (require __DIR__ . '/../../config/app.php')['token_ttl_horas'];
        $token = bin2hex(random_bytes(32));
        $expira = (new \DateTimeImmutable("+{$ttlHoras} hours"))->format('Y-m-d H:i:s');

        Token::crear($token, (int) $usuario['id'], $expira);

        unset($usuario['password_hash']);

        return ['token' => $token, 'expira' => $expira, 'usuario' => $usuario];
    }

    /**
     * Resuelve el Bearer token de la request al usuario dueno, o corta con 401.
     *
     * @return array<string, mixed> fila de `usuarios` sin password_hash
     * @throws ApiException 401 si falta el token, no existe o vencio
     */
    public static function usuarioActual(Request $request): array
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new ApiException(401, 'Falta el header Authorization: Bearer <token>.');
        }

        $usuario = Token::usuarioValido($token);

        if ($usuario === null) {
            throw new ApiException(401, 'Token invalido o vencido.');
        }

        unset($usuario['password_hash']);

        return $usuario;
    }

    /**
     * Invalida el token actual (borra la fila). No falla si ya no existe.
     */
    public static function logout(Request $request): void
    {
        $token = $request->bearerToken();

        if ($token !== null) {
            Token::borrar($token);
        }
    }
}
