<?php

declare(strict_types=1);

final class Usuario
{
    public function __construct(private PDO $pdo)
    {
    }

    public function obtenerPorCorreo(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id_usuario, nombre, apellido, correo, password_hash, rol, activo
             FROM usuarios
             WHERE correo = :correo
             LIMIT 1'
        );
        $statement->execute(['correo' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (
            $user === false
            || !is_array($user)
            || !isset($user['password_hash'], $user['rol'])
        ) {
            return null;
        }

        return $user;
    }

    public function authenticate(string $email, string $password, string $role): ?array
    {
        $allowedRoles = ['Administrador', 'Recepcionista', 'Veterinario'];
        if (!in_array($role, $allowedRoles, true)) {
            return null;
        }

        $user = $this->obtenerPorCorreo($email);
        if (
            $user === null
            || (int) $user['activo'] !== 1
            || !hash_equals((string) $user['rol'], $role)
            || !password_verify($password, (string) $user['password_hash'])
        ) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }

    public function findActiveVeterinarians(): array
    {
        $statement = $this->pdo->query(
            "SELECT id_usuario, nombre, apellido
             FROM usuarios
             WHERE rol = 'Veterinario' AND activo = 1
             ORDER BY apellido, nombre"
        );
        return $statement->fetchAll();
    }

    public function updateLastAccess(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id_usuario = :id'
        );
        $statement->execute(['id' => $id]);
    }
}
