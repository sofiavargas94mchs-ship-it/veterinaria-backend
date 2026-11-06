<?php

declare(strict_types=1);

final class ReporteController extends Controller
{
    public function index(): void
    {
        $this->requireRole('Administrador');
        $reports = new Reporte($this->pdo);
        $this->view('reportes/index', [
            'monthly' => $reports->resumenMensual(),
            'appointments' => $reports->citasPorVeterinario(),
            'vaccinations' => $reports->vacunacionesMensuales(),
            'upcomingVaccines' => $reports->vacunasProximas(),
            'attendedLastMonth' => $reports->conteoCitasAtendidas(),
        ]);
    }
}
