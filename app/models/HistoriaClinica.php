<?php

declare(strict_types=1);

final class HistoriaClinica
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarPorMascota(int $petId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT h.*, CONCAT(u.nombre, \' \', u.apellido) AS veterinario
             FROM historias_clinicas h
             INNER JOIN usuarios u ON u.id_usuario = h.veterinario_id
             WHERE h.mascota_id = :mascota_id
             ORDER BY h.fecha_atencion DESC'
        );
        $statement->execute(['mascota_id' => $petId]);
        return $statement->fetchAll();
    }

    public function listarVacunasDisponibles(int $petId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT v.id_vacuna, v.nombre
             FROM vacunas v
             INNER JOIN razas r ON r.especie_id = v.especie_id
             INNER JOIN mascotas m ON m.raza_id = r.id_raza
             WHERE m.id_mascota = :mascota_id AND v.activo = 1
             ORDER BY v.nombre'
        );
        $statement->execute(['mascota_id' => $petId]);
        return $statement->fetchAll();
    }

    public function listarCitasDisponibles(int $petId, int $veterinarianId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT c.id_cita, c.fecha_hora, c.motivo
             FROM citas c
             LEFT JOIN historias_clinicas h ON h.cita_id = c.id_cita
             WHERE c.mascota_id = :mascota_id
               AND c.veterinario_id = :veterinario_id
               AND c.estado IN ('En consulta', 'Completada')
               AND h.id_historia IS NULL
             ORDER BY c.fecha_hora DESC"
        );
        $statement->execute([
            'mascota_id' => $petId,
            'veterinario_id' => $veterinarianId,
        ]);
        return $statement->fetchAll();
    }

    public function registrar(array $data, ?int $vaccineId): int
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO historias_clinicas
                    (mascota_id, veterinario_id, cita_id, fecha_atencion, peso_kg,
                     temperatura_c, sintomas, diagnostico, tratamiento, observaciones, proxima_revision)
                 VALUES
                    (:mascota_id, :veterinario_id, :cita_id, NOW(), :peso_kg,
                     :temperatura_c, :sintomas, :diagnostico, :tratamiento, :observaciones, :proxima_revision)'
            );
            $historyData = $data;
            unset($historyData['lote']);
            $statement->execute($historyData);
            $historyId = (int) $this->pdo->lastInsertId();

            if ($vaccineId !== null) {
                $vaccination = $this->pdo->prepare(
                    'INSERT INTO control_vacunacion
                        (mascota_id, vacuna_id, veterinario_id, historia_id,
                         fecha_aplicacion, lote, observaciones)
                     VALUES
                        (:mascota_id, :vacuna_id, :veterinario_id, :historia_id,
                         CURDATE(), :lote, :vacuna_observaciones)'
                );
                $vaccination->execute([
                    'mascota_id' => $data['mascota_id'],
                    'vacuna_id' => $vaccineId,
                    'veterinario_id' => $data['veterinario_id'],
                    'historia_id' => $historyId,
                    'lote' => $data['lote'],
                    'vacuna_observaciones' => $data['observaciones'],
                ]);
            }

            $this->pdo->commit();
            return $historyId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
