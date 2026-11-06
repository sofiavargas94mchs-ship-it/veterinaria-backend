<?php

declare(strict_types=1);

final class Dashboard
{
    public function __construct(private PDO $pdo)
    {
    }

    public function metricas(string $role, int $userId): array
    {
        $petCount = (int) $this->pdo->query('SELECT COUNT(*) FROM mascotas WHERE activo = 1')->fetchColumn();

        $appointmentSql = "SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora) = CURDATE()";
        $appointmentParams = [];
        if ($role === 'Veterinario') {
            $appointmentSql .= ' AND veterinario_id = :user_id';
            $appointmentParams['user_id'] = $userId;
        }
        $appointment = $this->pdo->prepare($appointmentSql);
        $appointment->execute($appointmentParams);

        $vaccineCount = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM control_vacunacion
             WHERE fecha_proxima_dosis IS NOT NULL
               AND fecha_proxima_dosis <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
        )->fetchColumn();

        $monthlyIncome = null;
        if ($role === 'Administrador') {
            $income = $this->pdo->query(
                "SELECT COALESCE(SUM(df.cantidad * df.precio_unitario), 0)
                 FROM facturas f
                 INNER JOIN detalle_factura df ON df.factura_id = f.id_factura
                 WHERE f.estado = 'Emitida'
                   AND YEAR(f.fecha_emision) = YEAR(CURDATE())
                   AND MONTH(f.fecha_emision) = MONTH(CURDATE())"
            );
            $monthlyIncome = (float) $income->fetchColumn();
        }

        return [
            'mascotas' => $petCount,
            'citas_hoy' => (int) $appointment->fetchColumn(),
            'ingresos_mes' => $monthlyIncome,
            'vacunas_proximas' => $vaccineCount,
        ];
    }

    public function citasHoy(string $role, int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT c.id_cita, c.fecha_hora, c.estado, c.motivo, m.nombre AS mascota,
                    CONCAT(p.nombres, ' ', p.apellidos) AS propietario,
                    CONCAT(u.nombre, ' ', u.apellido) AS veterinario
             FROM citas c
             INNER JOIN mascotas m ON m.id_mascota = c.mascota_id
             INNER JOIN propietarios p ON p.id_propietario = m.propietario_id
             INNER JOIN usuarios u ON u.id_usuario = c.veterinario_id
             WHERE DATE(c.fecha_hora) = CURDATE()
               AND (:all_vets = 1 OR c.veterinario_id = :user_id)
             ORDER BY c.fecha_hora"
        );
        $statement->execute([
            'all_vets' => $role === 'Veterinario' ? 0 : 1,
            'user_id' => $userId,
        ]);
        return $statement->fetchAll();
    }
}
