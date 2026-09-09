<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

// parte del vertical slice demo (auth), descartable. En un proyecto real este
// modelo se queda, pero se adapta a las columnas reales de la tabla usuarios.
final class Usuario
{
    /**
     * @return array<string, mixed>|null
     */
    public static function porEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, nombre, email, password_hash, created_at, updated_at
             FROM usuarios WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);

        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buscar(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, nombre, email, created_at, updated_at FROM usuarios WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Alta de usuario con la contrasena ya hasheada aca (nunca se guarda en texto).
     */
    public static function crear(string $nombre, string $email, string $password): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, created_at, updated_at)
             VALUES (:nombre, :email, :hash, NOW(), NOW())'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
