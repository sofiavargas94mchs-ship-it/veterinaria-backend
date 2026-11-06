<?php

declare(strict_types=1);

final class Cita
{
    public function __construct(private PDO $pdo)
    {
    }

    public function agendar(array $data): int
    {
        $duration = (int) $data['duracion_min'];
        if ($duration < 5 || $duration > 480) {
            throw new InvalidArgumentException('La duración debe estar entre 5 y 480 minutos.');
        }

        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare(
                "SELECT id_usuario FROM usuarios
                 WHERE id_usuario = :veterinario_id AND rol = 'Veterinario' AND activo = 1
                 FOR UPDATE"
            );
            $lock->execute(['veterinario_id' => $data['veterinario_id']]);
            if (!$lock->fetch()) {
                throw new InvalidArgumentException('Selecciona un veterinario activo.');
            }

            $availability = $this->pdo->prepare(
                "SELECT COUNT(*)
                 FROM citas
                 WHERE veterinario_id = :veterinario_id
                   AND estado <> 'Cancelada'
                   AND :inicio < DATE_ADD(fecha_hora, INTERVAL duracion_min MINUTE)
                   AND DATE_ADD(:fin_inicio, INTERVAL :duracion MINUTE) > fecha_hora"
            );
            $availability->execute([
                'veterinario_id' => $data['veterinario_id'],
                'inicio' => $data['fecha_hora'],
                'fin_inicio' => $data['fecha_hora'],
                'duracion' => $duration,
            ]);
            if ((int) $availability->fetchColumn() > 0) {
                throw new InvalidArgumentException('El veterinario ya tiene una cita en ese horario.');
            }

            $insert = $this->pdo->prepare(
                "INSERT INTO citas
                    (mascota_id, veterinario_id, creada_por, fecha_hora, duracion_min, motivo, estado)
                 VALUES
                    (:mascota_id, :veterinario_id, :creada_por, :fecha_hora, :duracion_min, :motivo, 'Programada')"
            );
            $insert->execute($data);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function listarAgenda(string $date, ?int $veterinarianId = null, ?string $status = null): array
    {
        $conditions = ['DATE(c.fecha_hora) = :fecha'];
        $params = ['fecha' => $date];
        if ($veterinarianId !== null) {
            $conditions[] = 'c.veterinario_id = :veterinario_id';
            $params['veterinario_id'] = $veterinarianId;
        }
        if ($status !== null && in_array($status, ['Programada', 'En consulta', 'Completada', 'Cancelada'], true)) {
            $conditions[] = 'c.estado = :estado';
            $params['estado'] = $status;
        }

        $statement = $this->pdo->prepare(
            'SELECT c.id_cita, c.fecha_hora, c.duracion_min, c.motivo, c.estado,
                    m.id_mascota, m.nombre AS mascota, p.nombres AS propietario_nombres,
                    p.apellidos AS propietario_apellidos, p.telefono,
                    u.id_usuario AS veterinario_id, u.nombre AS veterinario_nombre,
                    u.apellido AS veterinario_apellido
             FROM citas c
             INNER JOIN mascotas m ON m.id_mascota = c.mascota_id
             INNER JOIN propietarios p ON p.id_propietario = m.propietario_id
             INNER JOIN usuarios u ON u.id_usuario = c.veterinario_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY c.fecha_hora'
        );
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function cambiarEstado(int $id, string $status): void
    {
        $allowed = ['Programada', 'En consulta', 'Completada', 'Cancelada'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('El estado seleccionado no es válido.');
        }
        $statement = $this->pdo->prepare('UPDATE citas SET estado = :estado WHERE id_cita = :id');
        $statement->execute(['estado' => $status, 'id' => $id]);
        if ($statement->rowCount() === 0) {
            $check = $this->pdo->prepare('SELECT 1 FROM citas WHERE id_cita = :id');
            $check->execute(['id' => $id]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('No se encontró la cita solicitada.');
            }
        }
    }

    public function veterinarioDeCita(int $id): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT veterinario_id FROM citas WHERE id_cita = :id'
        );
        $statement->execute(['id' => $id]);
        $veterinarianId = $statement->fetchColumn();
        return $veterinarianId === false ? null : (int) $veterinarianId;
    }

    public function listarMascotasActivas(): array
    {
        return $this->pdo->query(
            'SELECT m.id_mascota, m.nombre AS mascota, p.nombres, p.apellidos
             FROM mascotas m
             INNER JOIN propietarios p ON p.id_propietario = m.propietario_id
             WHERE m.activo = 1
             ORDER BY m.nombre'
        )->fetchAll();
    }
}
