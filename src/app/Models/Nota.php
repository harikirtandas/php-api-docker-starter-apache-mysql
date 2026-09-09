<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

// modelo de ejemplo del vertical slice demo, descartable. Un CRUD minimo
// (notas de un usuario) que sirve para probar de punta a punta los 5 verbos
// HTTP, los codigos de estado y la auth por token. En un proyecto real se
// borra entero y se reemplaza por los modelos del dominio.
final class Nota
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function delUsuario(int $usuarioId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, titulo, cuerpo, created_at, updated_at
             FROM notas WHERE usuario_id = :uid ORDER BY id DESC'
        );
        $stmt->execute(['uid' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buscarDeUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, titulo, cuerpo, created_at, updated_at
             FROM notas WHERE id = :id AND usuario_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $usuarioId]);

        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public static function crear(int $usuarioId, string $titulo, string $cuerpo): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO notas (usuario_id, titulo, cuerpo, created_at, updated_at)
             VALUES (:uid, :titulo, :cuerpo, NOW(), NOW())'
        );
        $stmt->execute(['uid' => $usuarioId, 'titulo' => $titulo, 'cuerpo' => $cuerpo]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function actualizar(int $id, int $usuarioId, string $titulo, string $cuerpo): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notas SET titulo = :titulo, cuerpo = :cuerpo, updated_at = NOW()
             WHERE id = :id AND usuario_id = :uid'
        );
        $stmt->execute(['titulo' => $titulo, 'cuerpo' => $cuerpo, 'id' => $id, 'uid' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }

    public static function eliminar(int $id, int $usuarioId): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM notas WHERE id = :id AND usuario_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }
}
