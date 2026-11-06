<?php

declare(strict_types=1);

final class Propietario
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(string $search = ''): array
    {
        $statement = $this->pdo->prepare(
            "SELECT p.id_propietario, p.tipo_documento, p.documento, p.nombres, p.apellidos,
                    p.telefono, p.correo, p.fecha_registro,
                    COUNT(DISTINCT m.id_mascota) AS total_mascotas
             FROM propietarios p
             LEFT JOIN mascotas m ON m.propietario_id = p.id_propietario
             WHERE (:search = ''
                    OR p.documento LIKE :documento
                    OR p.nombres LIKE :nombres
                    OR p.apellidos LIKE :apellidos
                    OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :nombre_completo)
             GROUP BY p.id_propietario
             ORDER BY p.apellidos, p.nombres"
        );
        $like = '%' . $search . '%';
        $statement->execute([
            'search' => $search,
            'documento' => $like,
            'nombres' => $like,
            'apellidos' => $like,
            'nombre_completo' => $like,
        ]);
        return $statement->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM propietarios WHERE id_propietario = :id');
        $statement->execute(['id' => $id]);
        $owner = $statement->fetch();
        return $owner ?: null;
    }

    public function crear(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO propietarios
                (tipo_documento, documento, nombres, apellidos, telefono, direccion, correo)
             VALUES
                (:tipo_documento, :documento, :nombres, :apellidos, :telefono, :direccion, :correo)'
        );
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        unset($data['id_propietario']);
        $statement = $this->pdo->prepare(
            'UPDATE propietarios
             SET tipo_documento = :tipo_documento, documento = :documento,
                 nombres = :nombres, apellidos = :apellidos, telefono = :telefono,
                 direccion = :direccion, correo = :correo
             WHERE id_propietario = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }
}
