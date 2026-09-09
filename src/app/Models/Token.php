<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

// tabla `tokens`: una fila por sesion activa. Parte del vertical slice demo
// (auth), pero es codigo que un proyecto real de este starter se queda.
final class Token
{
    public static function crear(string $token, int $usuarioId, string $expiraEn): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO tokens (token, usuario_id, expira_en, created_at)
             VALUES (:token, :usuario_id, :expira_en, NOW())'
        );
        $stmt->execute([
            'token' => $token,
            'usuario_id' => $usuarioId,
            'expira_en' => $expiraEn,
        ]);
    }

    /**
     * Devuelve la fila de `usuarios` dueno del token si el token existe y no
     * vencio; null en cualquier otro caso.
     *
     * @return array<string, mixed>|null
     */
    public static function usuarioValido(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.nombre, u.email, u.password_hash, u.created_at, u.updated_at
             FROM tokens t
             JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.token = :token AND t.expira_en > NOW()'
        );
        $stmt->execute(['token' => $token]);

        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public static function borrar(string $token): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
    }
}
