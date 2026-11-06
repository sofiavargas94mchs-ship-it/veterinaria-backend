<?php

declare(strict_types=1);

final class Mascota
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(string $search = ''): array
    {
        $statement = $this->pdo->prepare(
            "SELECT m.id_mascota, m.nombre, m.sexo, m.fecha_nacimiento, m.activo,
                    p.id_propietario, p.nombres AS propietario_nombres,
                    p.apellidos AS propietario_apellidos, p.telefono,
                    e.nombre AS especie, r.nombre AS raza
             FROM mascotas m
             INNER JOIN propietarios p ON p.id_propietario = m.propietario_id
             INNER JOIN razas r ON r.id_raza = m.raza_id
             INNER JOIN especies e ON e.id_especie = r.especie_id
             WHERE (:search = '' OR m.nombre LIKE :mascota
                    OR p.nombres LIKE :nombres OR p.apellidos LIKE :apellidos
                    OR p.documento LIKE :documento)
             ORDER BY m.activo DESC, m.nombre"
        );
        $like = '%' . $search . '%';
        $statement->execute([
            'search' => $search,
            'mascota' => $like,
            'nombres' => $like,
            'apellidos' => $like,
            'documento' => $like,
        ]);
        return $statement->fetchAll();
    }

    public function buscarConExpediente(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT m.*, p.nombres AS propietario_nombres, p.apellidos AS propietario_apellidos,
                    p.documento, p.telefono, p.correo, e.nombre AS especie,
                    r.nombre AS raza
             FROM mascotas m
             INNER JOIN propietarios p ON p.id_propietario = m.propietario_id
             INNER JOIN razas r ON r.id_raza = m.raza_id
             INNER JOIN especies e ON e.id_especie = r.especie_id
             WHERE m.id_mascota = :id'
        );
        $statement->execute(['id' => $id]);
        $pet = $statement->fetch();
        return $pet ?: null;
    }

    public function crear(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO mascotas
                (propietario_id, raza_id, nombre, fecha_nacimiento, sexo, color, microchip)
             VALUES
                (:propietario_id, :raza_id, :nombre, :fecha_nacimiento, :sexo, :color, :microchip)'
        );
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function listarPropietarios(): array
    {
        return $this->pdo->query(
            'SELECT id_propietario, nombres, apellidos, documento
             FROM propietarios ORDER BY apellidos, nombres'
        )->fetchAll();
    }

    public function listarRazas(): array
    {
        return $this->pdo->query(
            'SELECT r.id_raza, r.nombre, e.nombre AS especie
             FROM razas r INNER JOIN especies e ON e.id_especie = r.especie_id
             ORDER BY e.nombre, r.nombre'
        )->fetchAll();
    }
}
