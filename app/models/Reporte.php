<?php

declare(strict_types=1);

final class Reporte
{
    public function __construct(private PDO $pdo)
    {
    }

    public function resumenMensual(int $months = 12): array
    {
        $statement = $this->pdo->prepare(
            "SELECT DATE_FORMAT(f.fecha_emision, '%Y-%m') AS periodo,
                    COUNT(DISTINCT f.id_factura) AS facturas,
                    SUM(df.cantidad * df.precio_unitario) AS ingresos
             FROM facturas f
             INNER JOIN detalle_factura df ON df.factura_id = f.id_factura
             WHERE f.estado = 'Emitida'
               AND f.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
             GROUP BY DATE_FORMAT(f.fecha_emision, '%Y-%m')
             ORDER BY periodo"
        );
        $statement->bindValue('months', $months, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function citasPorVeterinario(): array
    {
        return $this->pdo->query(
            "SELECT u.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS veterinario,
                    COUNT(c.id_cita) AS total_citas,
                    SUM(CASE WHEN c.estado = 'Completada' THEN 1 ELSE 0 END) AS atendidas
             FROM usuarios u
             LEFT JOIN citas c ON c.veterinario_id = u.id_usuario
             WHERE u.rol = 'Veterinario' AND u.activo = 1
             GROUP BY u.id_usuario, u.nombre, u.apellido
             ORDER BY total_citas DESC, veterinario"
        )->fetchAll();
    }

    public function vacunacionesMensuales(): array
    {
        return $this->pdo->query(
            "SELECT DATE_FORMAT(cv.fecha_aplicacion, '%Y-%m') AS periodo,
                    COUNT(*) AS vacunaciones
             FROM control_vacunacion cv
             WHERE cv.fecha_aplicacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(cv.fecha_aplicacion, '%Y-%m')
             ORDER BY periodo"
        )->fetchAll();
    }

    public function vacunasProximas(): array
    {
        return $this->pdo->query(
            'SELECT mascota, propietario, vacuna, fecha_proxima_dosis, dias_restantes, estado
             FROM vista_vacunas_pendientes
             ORDER BY fecha_proxima_dosis
             LIMIT 20'
        )->fetchAll();
    }

    public function conteoCitasAtendidas(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM citas
             WHERE estado = 'Completada'
               AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
    }
}
